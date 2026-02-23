<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import CalculatorInput from '@/Components/CalculatorInput.vue';
import DangerButton from '@/Components/DangerButton.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, computed, reactive } from 'vue';

const props = defineProps({
    transactions: {
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
    filters: {
        type: Object,
        default: () => ({}),
    },
    summary: {
        type: Object,
        default: () => ({ income: 0, expense: 0 }),
    },
    yearMonths: {
        type: Array,
        default: () => [],
    },
});

// 今日の日付
const today = new Date().toISOString().split('T')[0];

// 現在の年月
const currentYearMonth = new Date().toISOString().slice(0, 7);

// フィルター状態
const filterForm = reactive({
    category_id: props.filters.category_id || '',
    payer: props.filters.payer || '',
    type: props.filters.type || '',
    memo: props.filters.memo || '',
    // null = 「すべて」選択済み（空文字にマップ）、undefined/string = そのまま使用
    year_month: props.filters.year_month === null ? '' : (props.filters.year_month || currentYearMonth),
});

// フィルター適用
const applyFilters = () => {
    const params = {};
    if (filterForm.category_id) params.category_id = filterForm.category_id;
    if (filterForm.payer) params.payer = filterForm.payer;
    if (filterForm.type) params.type = filterForm.type;
    if (filterForm.memo) params.memo = filterForm.memo;
    params.year_month = filterForm.year_month;

    router.get(route('transactions.index'), params, {
        preserveState: true,
        preserveScroll: true,
    });
};

// フィルターリセット
const resetFilters = () => {
    filterForm.category_id = '';
    filterForm.payer = '';
    filterForm.type = '';
    filterForm.memo = '';
    filterForm.year_month = currentYearMonth;
    router.get(route('transactions.index'), {}, {
        preserveState: true,
        preserveScroll: true,
    });
};

// フィルターが適用されているかどうか
const hasFilters = computed(() => {
    return filterForm.category_id || filterForm.payer || filterForm.type || filterForm.memo || (filterForm.year_month !== currentYearMonth);
});

// 年月リストを表示用フォーマットに変換: '2026-02' → '2026年2月'
const formattedYearMonths = computed(() =>
    props.yearMonths.map(ym => {
        const [year, month] = ym.split('-');
        return { value: ym, label: `${year}年${parseInt(month, 10)}月` };
    })
);

// 収支差額
const balance = computed(() => {
    return props.summary.income - props.summary.expense;
});

// モーダル表示状態
const showCreateModal = ref(false);
const showEditModal = ref(false);
const showDeleteModal = ref(false);

// 作成フォーム
const createForm = useForm({
    date: today,
    type: 'expense',
    category_id: '',
    payer: '',
    amount: '',
    memo: '',
});

// 編集フォーム
const editForm = useForm({
    id: null,
    date: '',
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

// 日付フォーマット
const formatDate = (dateString) => {
    const date = new Date(dateString);
    return date.toLocaleDateString('ja-JP', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
    });
};

// 作成モーダル
const openCreateModal = () => {
    createForm.reset();
    createForm.date = today;
    createForm.type = 'expense';
    createForm.clearErrors();
    showCreateModal.value = true;
};

const closeCreateModal = () => {
    showCreateModal.value = false;
    createForm.reset();
};

const submitCreate = () => {
    createForm.post(route('transactions.store'), {
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
const openEditModal = (transaction) => {
    editForm.id = transaction.id;
    editForm.date = transaction.date.split('T')[0];
    editForm.type = transaction.type;
    editForm.category_id = transaction.category_id;
    editForm.payer = transaction.payer;
    editForm.amount = transaction.amount;
    editForm.memo = transaction.memo || '';
    editForm.clearErrors();
    showEditModal.value = true;
};

const closeEditModal = () => {
    showEditModal.value = false;
    editForm.reset();
};

const submitEdit = () => {
    editForm.put(route('transactions.update', editForm.id), {
        preserveScroll: true,
        onSuccess: () => closeEditModal(),
    });
};

// 削除確認モーダル
const openDeleteModal = (transaction) => {
    deleteTarget.value = transaction;
    showDeleteModal.value = true;
};

const closeDeleteModal = () => {
    showDeleteModal.value = false;
    deleteTarget.value = null;
};

const submitDelete = () => {
    useForm({}).delete(route('transactions.destroy', deleteTarget.value.id), {
        preserveScroll: true,
        onSuccess: () => closeDeleteModal(),
    });
};
</script>

<template>
    <Head title="取引一覧" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    取引一覧
                </h2>
                <PrimaryButton @click="openCreateModal">
                    取引を追加
                </PrimaryButton>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <!-- フィルターセクション -->
                <div class="mb-6 overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-6">
                            <div>
                                <label for="filter-year-month" class="block text-sm font-medium text-gray-700">年月</label>
                                <select
                                    id="filter-year-month"
                                    v-model="filterForm.year_month"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                                >
                                    <option value="">すべて</option>
                                    <option
                                        v-for="ym in formattedYearMonths"
                                        :key="ym.value"
                                        :value="ym.value"
                                    >
                                        {{ ym.label }}
                                    </option>
                                </select>
                            </div>

                            <div>
                                <label for="filter-category" class="block text-sm font-medium text-gray-700">カテゴリ</label>
                                <select
                                    id="filter-category"
                                    v-model="filterForm.category_id"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                                >
                                    <option value="">すべて</option>
                                    <option
                                        v-for="category in categories"
                                        :key="category.id"
                                        :value="category.id"
                                    >
                                        {{ category.name }}
                                    </option>
                                </select>
                            </div>

                            <div>
                                <label for="filter-payer" class="block text-sm font-medium text-gray-700">支払元/受取人</label>
                                <select
                                    id="filter-payer"
                                    v-model="filterForm.payer"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                                >
                                    <option value="">すべて</option>
                                    <option
                                        v-for="payer in payers"
                                        :key="payer.value"
                                        :value="payer.value"
                                    >
                                        {{ payer.label }}
                                    </option>
                                </select>
                            </div>

                            <div>
                                <label for="filter-type" class="block text-sm font-medium text-gray-700">種別</label>
                                <select
                                    id="filter-type"
                                    v-model="filterForm.type"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                                >
                                    <option value="">すべて</option>
                                    <option value="income">収入</option>
                                    <option value="expense">支出</option>
                                </select>
                            </div>

                            <div>
                                <label for="filter-memo" class="block text-sm font-medium text-gray-700">メモ</label>
                                <input
                                    id="filter-memo"
                                    v-model="filterForm.memo"
                                    type="text"
                                    placeholder="キーワード検索"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                                />
                            </div>

                            <div class="flex items-end gap-2">
                                <PrimaryButton @click="applyFilters" class="flex-1">
                                    検索
                                </PrimaryButton>
                                <SecondaryButton v-if="hasFilters" @click="resetFilters">
                                    クリア
                                </SecondaryButton>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- サマリーセクション -->
                <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-3">
                    <div class="overflow-hidden rounded-lg bg-white p-6 shadow-sm">
                        <div class="text-sm font-medium text-gray-500">収入合計</div>
                        <div class="mt-1 text-2xl font-semibold text-green-600">
                            +{{ formatAmount(summary.income) }}円
                        </div>
                    </div>
                    <div class="overflow-hidden rounded-lg bg-white p-6 shadow-sm">
                        <div class="text-sm font-medium text-gray-500">支出合計</div>
                        <div class="mt-1 text-2xl font-semibold text-red-600">
                            -{{ formatAmount(summary.expense) }}円
                        </div>
                    </div>
                    <div class="overflow-hidden rounded-lg bg-white p-6 shadow-sm">
                        <div class="text-sm font-medium text-gray-500">収支</div>
                        <div
                            class="mt-1 text-2xl font-semibold"
                            :class="balance >= 0 ? 'text-green-600' : 'text-red-600'"
                        >
                            {{ balance >= 0 ? '+' : '' }}{{ formatAmount(balance) }}円
                        </div>
                    </div>
                </div>

                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <!-- 取引一覧 -->
                    <div v-if="transactions.length > 0">
                        <!-- PC: テーブル形式 -->
                        <table class="hidden md:table min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">日付</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">種別</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">カテゴリ</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">支払元</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">金額</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">メモ</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">操作</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <tr v-for="transaction in transactions" :key="transaction.id" class="hover:bg-gray-50">
                                    <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-900">
                                        {{ formatDate(transaction.date) }}
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-sm">
                                        <span
                                            class="inline-flex rounded-full px-2 text-xs font-semibold leading-5"
                                            :class="typeClass(transaction.type)"
                                        >
                                            {{ typeLabel(transaction.type) }}
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-900">
                                        {{ transaction.category?.name || '-' }}
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-900">
                                        {{ payerLabel(transaction.payer) }}
                                    </td>
                                    <td
                                        class="whitespace-nowrap px-4 py-3 text-sm text-right font-medium"
                                        :class="transaction.type === 'income' ? 'text-green-600' : 'text-red-600'"
                                    >
                                        {{ transaction.type === 'income' ? '+' : '-' }}{{ formatAmount(transaction.amount) }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-500 max-w-xs truncate">
                                        {{ transaction.memo || '-' }}
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-right text-sm">
                                        <button @click="openEditModal(transaction)" class="text-indigo-600 hover:text-indigo-900 mr-2">編集</button>
                                        <button @click="openDeleteModal(transaction)" class="text-red-600 hover:text-red-900">削除</button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>

                        <!-- スマホ: コンパクトリスト形式 -->
                        <ul class="md:hidden divide-y divide-gray-200">
                            <li
                                v-for="transaction in transactions"
                                :key="transaction.id"
                                class="p-3 hover:bg-gray-50"
                            >
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2 min-w-0 flex-1">
                                        <span
                                            class="inline-flex shrink-0 rounded-full px-2 py-0.5 text-xs font-semibold"
                                            :class="typeClass(transaction.type)"
                                        >
                                            {{ typeLabel(transaction.type) }}
                                        </span>
                                        <span class="text-sm font-medium text-gray-900 truncate">
                                            {{ transaction.category?.name || '-' }}
                                        </span>
                                    </div>
                                    <span
                                        class="ml-2 text-sm font-bold shrink-0"
                                        :class="transaction.type === 'income' ? 'text-green-600' : 'text-red-600'"
                                    >
                                        {{ transaction.type === 'income' ? '+' : '-' }}{{ formatAmount(transaction.amount) }}
                                    </span>
                                </div>
                                <div class="mt-1 flex items-center justify-between text-xs text-gray-500">
                                    <div class="flex items-center gap-2">
                                        <span>{{ formatDate(transaction.date) }}</span>
                                        <span>{{ payerLabel(transaction.payer) }}</span>
                                        <span v-if="transaction.memo" class="truncate max-w-[100px]">{{ transaction.memo }}</span>
                                    </div>
                                    <div class="flex gap-3 shrink-0">
                                        <button @click="openEditModal(transaction)" class="text-indigo-600">編集</button>
                                        <button @click="openDeleteModal(transaction)" class="text-red-600">削除</button>
                                    </div>
                                </div>
                            </li>
                        </ul>
                    </div>

                    <!-- 取引がない場合 -->
                    <div v-else class="p-8 text-center text-gray-500">
                        取引がありません。「取引を追加」ボタンから追加してください。
                    </div>
                </div>
            </div>
        </div>

        <!-- 作成モーダル -->
        <Modal :show="showCreateModal" @close="closeCreateModal" max-width="md">
            <form @submit.prevent="submitCreate" class="p-6">
                <h2 class="text-lg font-medium text-gray-900">
                    取引を追加
                </h2>

                <div class="mt-6">
                    <InputLabel for="create-date" value="日付" />
                    <input
                        id="create-date"
                        v-model="createForm.date"
                        type="date"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    />
                    <InputError :message="createForm.errors.date" class="mt-2" />
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
                    <CalculatorInput
                        id="create-amount"
                        v-model="createForm.amount"
                        placeholder="例: 1000"
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
                        placeholder="例: ランチ代"
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
                    取引を編集
                </h2>

                <div class="mt-6">
                    <InputLabel for="edit-date" value="日付" />
                    <input
                        id="edit-date"
                        v-model="editForm.date"
                        type="date"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    />
                    <InputError :message="editForm.errors.date" class="mt-2" />
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
                    <CalculatorInput
                        id="edit-amount"
                        v-model="editForm.amount"
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
                    取引の削除
                </h2>

                <p class="mt-4 text-sm text-gray-600">
                    {{ formatDate(deleteTarget?.date) }} の
                    「{{ deleteTarget?.category?.name }}」
                    ({{ formatAmount(deleteTarget?.amount) }}円)
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
