<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';

const props = defineProps({
    previewRows: {
        type: Array,
        default: null,
    },
    categories: {
        type: Array,
        default: () => [],
    },
});

// ---- アップロードフォーム ----
const uploadForm = useForm({ csv_file: null });
const fileInput = ref(null);

const onFileChange = (e) => {
    uploadForm.csv_file = e.target.files[0] ?? null;
};

const submitUpload = () => {
    uploadForm.post(route('csv-import.preview'), { forceFormData: true });
};

// ---- プレビュー・編集フォーム ----
// 編集可能なローカルstate（propsを直接書き換えない）
// サーバ側でmemo・category_idは既に埋め込まれている
const buildEditableRows = (rows) =>
    (rows ?? []).map((row) => ({
        memo: row.memo,
        amount: row.amount,
        category_id: row.category_id,
    }));

const editableRows = ref(buildEditableRows(props.previewRows));
const checkedIndices = ref(new Set(editableRows.value.map((_, i) => i)));

// preview後にInertiaがpropsを更新したらローカルstateも作り直す
watch(
    () => props.previewRows,
    (rows) => {
        editableRows.value = buildEditableRows(rows);
        checkedIndices.value = new Set(editableRows.value.map((_, i) => i));
    },
);

const toggleAll = (e) => {
    if (e.target.checked) {
        checkedIndices.value = new Set(editableRows.value.map((_, i) => i));
    } else {
        checkedIndices.value = new Set();
    }
};

const toggleRow = (index) => {
    const next = new Set(checkedIndices.value);
    if (next.has(index)) {
        next.delete(index);
    } else {
        next.add(index);
    }
    checkedIndices.value = next;
};

const allChecked = computed(
    () => editableRows.value.length > 0 && checkedIndices.value.size === editableRows.value.length,
);

const selectedRows = computed(() =>
    editableRows.value.filter((_, i) => checkedIndices.value.has(i)),
);

const selectedTotal = computed(() =>
    selectedRows.value.reduce((sum, row) => sum + row.amount, 0),
);

const today = new Date().toISOString().split('T')[0];
const importForm = useForm({ date: today, transactions: [] });

const submitImport = () => {
    importForm.transactions = selectedRows.value.map((row) => ({
        memo: row.memo,
        amount: row.amount,
        category_id: row.category_id,
    }));
    importForm.post(route('csv-import.store'));
};

const cancelImport = () => {
    router.get(route('csv-import.create'));
};

const formatAmount = (amount) => amount.toLocaleString('ja-JP');
</script>

<template>
    <Head title="CSVインポート" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                CSVインポート
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">

                <!-- アップロード状態 -->
                <div v-if="previewRows === null" class="rounded-lg bg-white p-6 shadow">
                    <h3 class="mb-4 text-lg font-medium text-gray-900">
                        楽天カード利用明細CSVを選択してください
                    </h3>
                    <form @submit.prevent="submitUpload" class="space-y-4">
                        <div>
                            <InputLabel for="csv_file" value="CSVファイル" />
                            <input
                                id="csv_file"
                                ref="fileInput"
                                type="file"
                                accept=".csv"
                                class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:rounded file:border-0 file:bg-gray-100 file:px-4 file:py-2 file:text-sm file:font-medium hover:file:bg-gray-200"
                                @change="onFileChange"
                            />
                            <InputError :message="uploadForm.errors.csv_file" class="mt-1" />
                        </div>
                        <div>
                            <PrimaryButton :disabled="uploadForm.processing || !uploadForm.csv_file">
                                読み込む
                            </PrimaryButton>
                        </div>
                    </form>
                </div>

                <!-- プレビュー状態 -->
                <div v-else class="space-y-6">

                    <!-- 日付選択 -->
                    <div class="rounded-lg bg-white p-6 shadow">
                        <div class="max-w-xs">
                            <InputLabel for="import_date" value="取引日（全件に適用）" />
                            <input
                                id="import_date"
                                v-model="importForm.date"
                                type="date"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                            />
                            <InputError :message="importForm.errors.date" class="mt-1" />
                        </div>
                    </div>

                    <!-- サマリー -->
                    <div class="grid grid-cols-2 gap-4 sm:grid-cols-3">
                        <div class="rounded-lg bg-white p-4 shadow text-center">
                            <div class="text-sm text-gray-500">CSVの件数</div>
                            <div class="mt-1 text-2xl font-bold text-gray-900">
                                {{ editableRows.length }}件
                            </div>
                        </div>
                        <div class="rounded-lg bg-white p-4 shadow text-center">
                            <div class="text-sm text-gray-500">選択中</div>
                            <div class="mt-1 text-2xl font-bold text-indigo-600">
                                {{ checkedIndices.size }}件
                            </div>
                        </div>
                        <div class="col-span-2 rounded-lg bg-white p-4 shadow text-center sm:col-span-1">
                            <div class="text-sm text-gray-500">選択合計</div>
                            <div class="mt-1 text-2xl font-bold text-red-600">
                                ¥{{ formatAmount(selectedTotal) }}
                            </div>
                        </div>
                    </div>

                    <!-- テーブル（PC） -->
                    <div class="rounded-lg bg-white shadow">
                        <table class="hidden w-full divide-y divide-gray-200 md:table">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="w-12 px-4 py-3 text-left">
                                        <input
                                            type="checkbox"
                                            :checked="allChecked"
                                            class="rounded border-gray-300 text-indigo-600"
                                            @change="toggleAll"
                                        />
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                        カテゴリ
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                        メモ
                                    </th>
                                    <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">
                                        金額
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white">
                                <tr
                                    v-for="(row, index) in editableRows"
                                    :key="index"
                                    :class="checkedIndices.has(index) ? '' : 'opacity-40'"
                                    class="hover:bg-gray-50"
                                >
                                    <td class="px-4 py-3">
                                        <input
                                            type="checkbox"
                                            :checked="checkedIndices.has(index)"
                                            class="rounded border-gray-300 text-indigo-600"
                                            @change="toggleRow(index)"
                                        />
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-900">
                                        <select
                                            v-model="row.category_id"
                                            :disabled="!checkedIndices.has(index)"
                                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 disabled:bg-gray-100"
                                        >
                                            <option
                                                v-for="category in categories"
                                                :key="category.id"
                                                :value="category.id"
                                            >
                                                {{ category.name }}
                                            </option>
                                        </select>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-900">
                                        <TextInput
                                            v-model="row.memo"
                                            type="text"
                                            class="block w-full"
                                            :disabled="!checkedIndices.has(index)"
                                        />
                                    </td>
                                    <td class="px-4 py-3 text-right text-sm font-medium text-red-600">
                                        ¥{{ formatAmount(row.amount) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>

                        <!-- カードリスト（モバイル） -->
                        <ul class="divide-y divide-gray-200 md:hidden">
                            <li
                                v-for="(row, index) in editableRows"
                                :key="index"
                                :class="checkedIndices.has(index) ? '' : 'opacity-40'"
                                class="space-y-2 px-4 py-3"
                            >
                                <div class="flex items-center gap-3">
                                    <input
                                        type="checkbox"
                                        :checked="checkedIndices.has(index)"
                                        class="rounded border-gray-300 text-indigo-600"
                                        @change="toggleRow(index)"
                                    />
                                    <select
                                        v-model="row.category_id"
                                        :disabled="!checkedIndices.has(index)"
                                        class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 disabled:bg-gray-100"
                                    >
                                        <option
                                            v-for="category in categories"
                                            :key="category.id"
                                            :value="category.id"
                                        >
                                            {{ category.name }}
                                        </option>
                                    </select>
                                    <p class="whitespace-nowrap text-sm font-medium text-red-600">
                                        ¥{{ formatAmount(row.amount) }}
                                    </p>
                                </div>
                                <TextInput
                                    v-model="row.memo"
                                    type="text"
                                    class="block w-full text-sm"
                                    :disabled="!checkedIndices.has(index)"
                                />
                            </li>
                        </ul>
                    </div>

                    <InputError :message="importForm.errors.transactions" />

                    <!-- 操作ボタン -->
                    <div class="flex items-center gap-4">
                        <PrimaryButton
                            :disabled="importForm.processing || checkedIndices.size === 0"
                            @click="submitImport"
                        >
                            {{ checkedIndices.size }}件をインポート
                        </PrimaryButton>
                        <SecondaryButton @click="cancelImport">
                            キャンセル
                        </SecondaryButton>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
