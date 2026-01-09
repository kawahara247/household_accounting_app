<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DangerButton from '@/Components/DangerButton.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    recurringTransactions: {
        type: Array,
        required: true,
    },
    categories: {
        type: Array,
        required: true,
    },
    payers: {
        type: Array,
        required: true,
    },
});

// モーダル表示状態
const showCreateModal = ref(false);
const showEditModal = ref(false);
const showDeleteModal = ref(false);

// 作成フォーム
const createForm = useForm({
    name: '',
    day_of_month: '',
    type: 'expense',
    category_id: '',
    payer: '',
    amount: '',
    memo: '',
});

// 編集フォーム
const editForm = useForm({
    id: null,
    name: '',
    day_of_month: '',
    type: 'expense',
    category_id: '',
    payer: '',
    amount: '',
    memo: '',
});

// 削除対象
const deleteTarget = ref(null);

// フィルタリングされたカテゴリ（作成フォーム用）
const filteredCreateCategories = computed(() => {
    return props.categories.filter(cat => cat.type === createForm.type);
});

// フィルタリングされたカテゴリ（編集フォーム用）
const filteredEditCategories = computed(() => {
    return props.categories.filter(cat => cat.type === editForm.type);
});

// 種別表示用
const typeLabel = (type) => {
    return type === 'income' ? '収入' : '支出';
};

const typeClass = (type) => {
    return type === 'income'
        ? 'bg-green-100 text-green-800'
        : 'bg-red-100 text-red-800';
};

// 支払元ラベル取得
const payerLabel = (payerValue) => {
    const payer = props.payers.find(p => p.value === payerValue);
    return payer ? payer.label : payerValue;
};

// 金額フォーマット
const formatAmount = (amount) => {
    return new Intl.NumberFormat('ja-JP').format(amount);
};

// 日付選択肢（1-28日）
const dayOptions = Array.from({ length: 28 }, (_, i) => i + 1);

// 作成モーダル
const openCreateModal = () => {
    createForm.reset();
    createForm.type = 'expense';
    createForm.clearErrors();
    showCreateModal.value = true;
};

const closeCreateModal = () => {
    showCreateModal.value = false;
    createForm.reset();
};

const submitCreate = () => {
    createForm.post(route('recurring-transactions.store'), {
        preserveScroll: true,
        onSuccess: () => closeCreateModal(),
    });
};

// 種別変更時にカテゴリをリセット
const onCreateTypeChange = () => {
    createForm.category_id = '';
};

const onEditTypeChange = () => {
    editForm.category_id = '';
};

// 編集モーダル
const openEditModal = (recurring) => {
    editForm.id = recurring.id;
    editForm.name = recurring.name;
    editForm.day_of_month = recurring.day_of_month;
    editForm.type = recurring.type;
    editForm.category_id = recurring.category_id;
    editForm.payer = recurring.payer;
    editForm.amount = recurring.amount;
    editForm.memo = recurring.memo || '';
    editForm.clearErrors();
    showEditModal.value = true;
};

const closeEditModal = () => {
    showEditModal.value = false;
    editForm.reset();
};

const submitEdit = () => {
    editForm.put(route('recurring-transactions.update', editForm.id), {
        preserveScroll: true,
        onSuccess: () => closeEditModal(),
    });
};

// 削除確認モーダル
const openDeleteModal = (recurring) => {
    deleteTarget.value = recurring;
    showDeleteModal.value = true;
};

const closeDeleteModal = () => {
    showDeleteModal.value = false;
    deleteTarget.value = null;
};

const submitDelete = () => {
    useForm({}).delete(route('recurring-transactions.destroy', deleteTarget.value.id), {
        preserveScroll: true,
        onSuccess: () => closeDeleteModal(),
    });
};
</script>

<template>
    <Head title="定期取引" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    定期取引
                </h2>
                <PrimaryButton @click="openCreateModal">
                    定期取引を追加
                </PrimaryButton>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <!-- 定期取引一覧 -->
                    <div v-if="recurringTransactions.length > 0">
                        <!-- PC: テーブル形式 -->
                        <table class="hidden md:table min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">名前</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">登録日</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">種別</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">カテゴリ</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">支払元</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">金額</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">メモ</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">操作</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <tr v-for="recurring in recurringTransactions" :key="recurring.id" class="hover:bg-gray-50">
                                    <td class="whitespace-nowrap px-4 py-3 text-sm font-medium text-gray-900">
                                        {{ recurring.name }}
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-900">
                                        毎月{{ recurring.day_of_month }}日
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-sm">
                                        <span
                                            class="inline-flex rounded-full px-2 text-xs font-semibold leading-5"
                                            :class="typeClass(recurring.type)"
                                        >
                                            {{ typeLabel(recurring.type) }}
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-900">
                                        {{ recurring.category?.name || '-' }}
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-900">
                                        {{ payerLabel(recurring.payer) }}
                                    </td>
                                    <td
                                        class="whitespace-nowrap px-4 py-3 text-sm text-right font-medium"
                                        :class="recurring.type === 'income' ? 'text-green-600' : 'text-red-600'"
                                    >
                                        {{ recurring.type === 'income' ? '+' : '-' }}{{ formatAmount(recurring.amount) }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-500 max-w-xs truncate">
                                        {{ recurring.memo || '-' }}
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-right text-sm">
                                        <button @click="openEditModal(recurring)" class="text-indigo-600 hover:text-indigo-900 mr-2">編集</button>
                                        <button @click="openDeleteModal(recurring)" class="text-red-600 hover:text-red-900">削除</button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>

                        <!-- スマホ: コンパクトリスト形式 -->
                        <ul class="md:hidden divide-y divide-gray-200">
                            <li
                                v-for="recurring in recurringTransactions"
                                :key="recurring.id"
                                class="p-3 hover:bg-gray-50"
                            >
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2 min-w-0 flex-1">
                                        <span class="text-sm font-medium text-gray-900 truncate">
                                            {{ recurring.name }}
                                        </span>
                                        <span
                                            class="inline-flex shrink-0 rounded-full px-2 py-0.5 text-xs font-semibold"
                                            :class="typeClass(recurring.type)"
                                        >
                                            {{ typeLabel(recurring.type) }}
                                        </span>
                                    </div>
                                    <span
                                        class="ml-2 text-sm font-bold shrink-0"
                                        :class="recurring.type === 'income' ? 'text-green-600' : 'text-red-600'"
                                    >
                                        {{ recurring.type === 'income' ? '+' : '-' }}{{ formatAmount(recurring.amount) }}
                                    </span>
                                </div>
                                <div class="mt-1 flex items-center justify-between text-xs text-gray-500">
                                    <div class="flex items-center gap-2">
                                        <span>毎月{{ recurring.day_of_month }}日</span>
                                        <span>{{ recurring.category?.name }}</span>
                                        <span>{{ payerLabel(recurring.payer) }}</span>
                                    </div>
                                    <div class="flex gap-3 shrink-0">
                                        <button @click="openEditModal(recurring)" class="text-indigo-600">編集</button>
                                        <button @click="openDeleteModal(recurring)" class="text-red-600">削除</button>
                                    </div>
                                </div>
                            </li>
                        </ul>
                    </div>

                    <!-- 定期取引がない場合 -->
                    <div v-else class="p-8 text-center text-gray-500">
                        定期取引がありません。「定期取引を追加」ボタンから追加してください。
                    </div>
                </div>
            </div>
        </div>

        <!-- 作成モーダル -->
        <Modal :show="showCreateModal" @close="closeCreateModal" max-width="md">
            <form @submit.prevent="submitCreate" class="p-6">
                <h2 class="text-lg font-medium text-gray-900">
                    定期取引を追加
                </h2>

                <div class="mt-6">
                    <InputLabel for="create-name" value="名前" />
                    <TextInput
                        id="create-name"
                        v-model="createForm.name"
                        type="text"
                        class="mt-1 block w-full"
                        placeholder="例: 家賃"
                    />
                    <InputError :message="createForm.errors.name" class="mt-2" />
                </div>

                <div class="mt-4">
                    <InputLabel for="create-day" value="毎月の登録日" />
                    <select
                        id="create-day"
                        v-model="createForm.day_of_month"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                        <option value="">選択してください</option>
                        <option v-for="day in dayOptions" :key="day" :value="day">
                            {{ day }}日
                        </option>
                    </select>
                    <InputError :message="createForm.errors.day_of_month" class="mt-2" />
                </div>

                <div class="mt-4">
                    <InputLabel value="種別" />
                    <div class="mt-2 flex gap-6">
                        <label class="inline-flex items-center">
                            <input
                                type="radio"
                                v-model="createForm.type"
                                value="expense"
                                @change="onCreateTypeChange"
                                class="rounded-full border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                            />
                            <span class="ml-2 text-sm text-gray-700">支出</span>
                        </label>
                        <label class="inline-flex items-center">
                            <input
                                type="radio"
                                v-model="createForm.type"
                                value="income"
                                @change="onCreateTypeChange"
                                class="rounded-full border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                            />
                            <span class="ml-2 text-sm text-gray-700">収入</span>
                        </label>
                    </div>
                    <InputError :message="createForm.errors.type" class="mt-2" />
                </div>

                <div class="mt-4">
                    <InputLabel for="create-category" value="カテゴリ" />
                    <select
                        id="create-category"
                        v-model="createForm.category_id"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                        <option value="">選択してください</option>
                        <option
                            v-for="category in filteredCreateCategories"
                            :key="category.id"
                            :value="category.id"
                        >
                            {{ category.name }}
                        </option>
                    </select>
                    <InputError :message="createForm.errors.category_id" class="mt-2" />
                </div>

                <div class="mt-4">
                    <InputLabel for="create-payer" :value="createForm.type === 'income' ? '受取人' : '支払元'" />
                    <select
                        id="create-payer"
                        v-model="createForm.payer"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                        <option value="">選択してください</option>
                        <option
                            v-for="payer in payers"
                            :key="payer.value"
                            :value="payer.value"
                        >
                            {{ payer.label }}
                        </option>
                    </select>
                    <InputError :message="createForm.errors.payer" class="mt-2" />
                </div>

                <div class="mt-4">
                    <InputLabel for="create-amount" value="金額" />
                    <TextInput
                        id="create-amount"
                        v-model="createForm.amount"
                        type="number"
                        min="1"
                        class="mt-1 block w-full"
                        placeholder="例: 80000"
                    />
                    <InputError :message="createForm.errors.amount" class="mt-2" />
                </div>

                <div class="mt-4">
                    <InputLabel for="create-memo" value="メモ（任意）" />
                    <TextInput
                        id="create-memo"
                        v-model="createForm.memo"
                        type="text"
                        class="mt-1 block w-full"
                        placeholder="例: 毎月の家賃"
                    />
                    <InputError :message="createForm.errors.memo" class="mt-2" />
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton @click="closeCreateModal">
                        キャンセル
                    </SecondaryButton>
                    <PrimaryButton :disabled="createForm.processing">
                        追加
                    </PrimaryButton>
                </div>
            </form>
        </Modal>

        <!-- 編集モーダル -->
        <Modal :show="showEditModal" @close="closeEditModal" max-width="md">
            <form @submit.prevent="submitEdit" class="p-6">
                <h2 class="text-lg font-medium text-gray-900">
                    定期取引を編集
                </h2>

                <div class="mt-6">
                    <InputLabel for="edit-name" value="名前" />
                    <TextInput
                        id="edit-name"
                        v-model="editForm.name"
                        type="text"
                        class="mt-1 block w-full"
                    />
                    <InputError :message="editForm.errors.name" class="mt-2" />
                </div>

                <div class="mt-4">
                    <InputLabel for="edit-day" value="毎月の登録日" />
                    <select
                        id="edit-day"
                        v-model="editForm.day_of_month"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                        <option value="">選択してください</option>
                        <option v-for="day in dayOptions" :key="day" :value="day">
                            {{ day }}日
                        </option>
                    </select>
                    <InputError :message="editForm.errors.day_of_month" class="mt-2" />
                </div>

                <div class="mt-4">
                    <InputLabel value="種別" />
                    <div class="mt-2 flex gap-6">
                        <label class="inline-flex items-center">
                            <input
                                type="radio"
                                v-model="editForm.type"
                                value="expense"
                                @change="onEditTypeChange"
                                class="rounded-full border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                            />
                            <span class="ml-2 text-sm text-gray-700">支出</span>
                        </label>
                        <label class="inline-flex items-center">
                            <input
                                type="radio"
                                v-model="editForm.type"
                                value="income"
                                @change="onEditTypeChange"
                                class="rounded-full border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                            />
                            <span class="ml-2 text-sm text-gray-700">収入</span>
                        </label>
                    </div>
                    <InputError :message="editForm.errors.type" class="mt-2" />
                </div>

                <div class="mt-4">
                    <InputLabel for="edit-category" value="カテゴリ" />
                    <select
                        id="edit-category"
                        v-model="editForm.category_id"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                        <option value="">選択してください</option>
                        <option
                            v-for="category in filteredEditCategories"
                            :key="category.id"
                            :value="category.id"
                        >
                            {{ category.name }}
                        </option>
                    </select>
                    <InputError :message="editForm.errors.category_id" class="mt-2" />
                </div>

                <div class="mt-4">
                    <InputLabel for="edit-payer" :value="editForm.type === 'income' ? '受取人' : '支払元'" />
                    <select
                        id="edit-payer"
                        v-model="editForm.payer"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                        <option value="">選択してください</option>
                        <option
                            v-for="payer in payers"
                            :key="payer.value"
                            :value="payer.value"
                        >
                            {{ payer.label }}
                        </option>
                    </select>
                    <InputError :message="editForm.errors.payer" class="mt-2" />
                </div>

                <div class="mt-4">
                    <InputLabel for="edit-amount" value="金額" />
                    <TextInput
                        id="edit-amount"
                        v-model="editForm.amount"
                        type="number"
                        min="1"
                        class="mt-1 block w-full"
                    />
                    <InputError :message="editForm.errors.amount" class="mt-2" />
                </div>

                <div class="mt-4">
                    <InputLabel for="edit-memo" value="メモ（任意）" />
                    <TextInput
                        id="edit-memo"
                        v-model="editForm.memo"
                        type="text"
                        class="mt-1 block w-full"
                    />
                    <InputError :message="editForm.errors.memo" class="mt-2" />
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton @click="closeEditModal">
                        キャンセル
                    </SecondaryButton>
                    <PrimaryButton :disabled="editForm.processing">
                        更新
                    </PrimaryButton>
                </div>
            </form>
        </Modal>

        <!-- 削除確認モーダル -->
        <Modal :show="showDeleteModal" @close="closeDeleteModal" max-width="md">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900">
                    定期取引の削除
                </h2>

                <p class="mt-4 text-sm text-gray-600">
                    「{{ deleteTarget?.name }}」(毎月{{ deleteTarget?.day_of_month }}日、{{ formatAmount(deleteTarget?.amount) }}円)
                    を削除してもよろしいですか？
                    この操作は取り消せません。
                </p>

                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton @click="closeDeleteModal">
                        キャンセル
                    </SecondaryButton>
                    <DangerButton @click="submitDelete">
                        削除
                    </DangerButton>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
