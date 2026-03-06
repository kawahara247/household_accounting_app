<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    previewRows: {
        type: Array,
        default: null,
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

// ---- プレビュー・インポートフォーム ----
// 全行を選択状態で初期化
const checkedIndices = ref(new Set(props.previewRows?.map((_, i) => i) ?? []));

const toggleAll = (e) => {
    if (e.target.checked) {
        checkedIndices.value = new Set(props.previewRows.map((_, i) => i));
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

const allChecked = computed(() =>
    props.previewRows != null && checkedIndices.value.size === props.previewRows.length
);

const selectedRows = computed(() =>
    (props.previewRows ?? []).filter((_, i) => checkedIndices.value.has(i))
);

const selectedTotal = computed(() =>
    selectedRows.value.reduce((sum, row) => sum + row.amount, 0)
);

const today = new Date().toISOString().split('T')[0];
const importForm = useForm({ date: today, transactions: [] });

const submitImport = () => {
    importForm.transactions = selectedRows.value;
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
            <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">

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
                                {{ previewRows.length }}件
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
                                        利用店名・商品名
                                    </th>
                                    <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">
                                        金額
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white">
                                <tr
                                    v-for="(row, index) in previewRows"
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
                                        {{ row.memo }}
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
                                v-for="(row, index) in previewRows"
                                :key="index"
                                :class="checkedIndices.has(index) ? '' : 'opacity-40'"
                                class="flex items-center gap-3 px-4 py-3"
                            >
                                <input
                                    type="checkbox"
                                    :checked="checkedIndices.has(index)"
                                    class="rounded border-gray-300 text-indigo-600"
                                    @change="toggleRow(index)"
                                />
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm text-gray-900">{{ row.memo }}</p>
                                </div>
                                <p class="text-sm font-medium text-red-600">
                                    ¥{{ formatAmount(row.amount) }}
                                </p>
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
