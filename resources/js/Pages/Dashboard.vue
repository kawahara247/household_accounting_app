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
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    year: {
        type: Number,
        required: true,
    },
    month: {
        type: Number,
        required: true,
    },
    dailyBalances: {
        type: Object,
        required: true,
    },
    monthlyBalance: {
        type: Object,
        required: true,
    },
    payerBalances: {
        type: Object,
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

// 曜日ラベル
const weekDays = ['日', '月', '火', '水', '木', '金', '土'];

// 今日の日付
const today = new Date();
const todayYear = today.getFullYear();
const todayMonth = today.getMonth() + 1;
const todayDate = today.getDate();

const isToday = (dayData) => {
    return dayData.isCurrentMonth
        && dayData.day
        && props.year === todayYear
        && props.month === todayMonth
        && dayData.day === todayDate;
};

// カレンダーデータを生成
const calendarDays = computed(() => {
    const firstDay = new Date(props.year, props.month - 1, 1);
    const lastDay = new Date(props.year, props.month, 0);
    const daysInMonth = lastDay.getDate();
    const startDayOfWeek = firstDay.getDay();

    const days = [];

    // 前月の空白
    for (let i = 0; i < startDayOfWeek; i++) {
        days.push({ day: null, isCurrentMonth: false });
    }

    // 当月の日付
    for (let day = 1; day <= daysInMonth; day++) {
        const balance = props.dailyBalances[day] || null;
        days.push({
            day,
            isCurrentMonth: true,
            balance,
        });
    }

    // 残りの空白（6週分になるまで）
    while (days.length < 42) {
        days.push({ day: null, isCurrentMonth: false });
    }

    return days;
});

// 月表示用
const monthLabel = computed(() => {
    return `${props.year}年${props.month}月`;
});

// 金額フォーマット
const formatAmount = (amount) => {
    return new Intl.NumberFormat('ja-JP').format(amount);
};

// 月切り替え
const goToPreviousMonth = () => {
    let newYear = props.year;
    let newMonth = props.month - 1;
    if (newMonth < 1) {
        newMonth = 12;
        newYear--;
    }
    router.get(route('dashboard', { year: newYear, month: newMonth }));
};

const goToNextMonth = () => {
    let newYear = props.year;
    let newMonth = props.month + 1;
    if (newMonth > 12) {
        newMonth = 1;
        newYear++;
    }
    router.get(route('dashboard', { year: newYear, month: newMonth }));
};

const goToCurrentMonth = () => {
    router.get(route('dashboard'));
};

// 日付選択モーダル
const showDayModal = ref(false);
const selectedDay = ref(null);
const selectedDayTransactions = ref([]);
const isLoadingTransactions = ref(false);

const openDayModal = async (dayData) => {
    if (!dayData.day) return;

    selectedDay.value = dayData.day;
    showDayModal.value = true;
    isLoadingTransactions.value = true;

    try {
        const dateStr = `${props.year}-${String(props.month).padStart(2, '0')}-${String(dayData.day).padStart(2, '0')}`;
        const response = await fetch(route('dashboard.transactions', { date: dateStr }));
        const data = await response.json();
        selectedDayTransactions.value = data.transactions;
    } catch (error) {
        console.error('Failed to fetch transactions:', error);
        selectedDayTransactions.value = [];
    } finally {
        isLoadingTransactions.value = false;
    }
};

const closeDayModal = () => {
    showDayModal.value = false;
    selectedDay.value = null;
    selectedDayTransactions.value = [];
};

// 選択日の日付文字列
const selectedDateString = computed(() => {
    if (!selectedDay.value) return '';
    return `${props.year}年${props.month}月${selectedDay.value}日`;
});

// 選択日のYYYY-MM-DD形式
const selectedDateForInput = computed(() => {
    if (!selectedDay.value) return '';
    return `${props.year}-${String(props.month).padStart(2, '0')}-${String(selectedDay.value).padStart(2, '0')}`;
});

// 取引追加モーダル
const showCreateModal = ref(false);

const createForm = useForm({
    date: '',
    type: 'expense',
    category_id: '',
    payer: '',
    amount: '',
    memo: '',
    _redirect: 'dashboard',
});

// 取引編集モーダル
const showEditModal = ref(false);
const editingTransaction = ref(null);

const editForm = useForm({
    date: '',
    type: 'expense',
    category_id: '',
    payer: '',
    amount: '',
    memo: '',
    _redirect: 'dashboard',
});

// 取引削除モーダル
const showDeleteModal = ref(false);
const deletingTransaction = ref(null);

// フィルタリングされたカテゴリ
const filteredCategories = computed(() => {
    return props.categories.filter(cat => cat.type === createForm.type);
});

const openCreateModal = () => {
    createForm.reset();
    createForm.date = selectedDateForInput.value;
    createForm.type = 'expense';
    createForm.clearErrors();
    showCreateModal.value = true;
};

const closeCreateModal = () => {
    showCreateModal.value = false;
    createForm.reset();
};

const onTypeChange = () => {
    createForm.category_id = '';
};

const submitCreate = () => {
    createForm.post(route('transactions.store'), {
        preserveScroll: true,
        onSuccess: () => {
            closeCreateModal();
            closeDayModal();
            // ダッシュボードをリロードして最新データを表示
            router.reload();
        },
    });
};

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

// 編集モーダルを開く
const openEditModal = (transaction) => {
    editingTransaction.value = transaction;
    editForm.date = transaction.date;
    editForm.type = transaction.type;
    editForm.category_id = transaction.category_id;
    editForm.payer = transaction.payer;
    editForm.amount = transaction.amount;
    editForm.memo = transaction.memo || '';
    editForm.clearErrors();
    showEditModal.value = true;
};

// 編集モーダルを閉じる
const closeEditModal = () => {
    showEditModal.value = false;
    editingTransaction.value = null;
    editForm.reset();
};

// 編集フォーム送信
const submitEdit = () => {
    editForm.put(route('transactions.update', editingTransaction.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            closeEditModal();
            closeDayModal();
            router.reload();
        },
    });
};

// 削除モーダルを開く
const openDeleteModal = (transaction) => {
    deletingTransaction.value = transaction;
    showDeleteModal.value = true;
};

// 削除モーダルを閉じる
const closeDeleteModal = () => {
    showDeleteModal.value = false;
    deletingTransaction.value = null;
};

// 削除実行
const confirmDelete = () => {
    router.delete(route('transactions.destroy', deletingTransaction.value.id), {
        data: { _redirect: 'dashboard' },
        preserveScroll: true,
        onSuccess: () => {
            closeDeleteModal();
            closeDayModal();
            router.reload();
        },
    });
};

// 編集時の種別変更ハンドラ
const onEditTypeChange = () => {
    editForm.category_id = '';
};

// フィルタリングされた編集用カテゴリ
const filteredEditCategories = computed(() => {
    return props.categories.filter(cat => cat.type === editForm.type);
});
</script>

<template>
    <Head title="ダッシュボード" />

    <AuthenticatedLayout>
        <div class="py-6">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <!-- 月間収支サマリー -->
                <div class="mb-6 grid grid-cols-3 gap-4">
                    <div class="rounded-lg bg-green-50 p-4 text-center">
                        <div class="text-sm text-green-600">収入</div>
                        <div class="text-xl font-bold text-green-700">
                            +{{ formatAmount(monthlyBalance.income) }}
                        </div>
                    </div>
                    <div class="rounded-lg bg-red-50 p-4 text-center">
                        <div class="text-sm text-red-600">支出</div>
                        <div class="text-xl font-bold text-red-700">
                            -{{ formatAmount(monthlyBalance.expense) }}
                        </div>
                    </div>
                    <div class="rounded-lg bg-blue-50 p-4 text-center">
                        <div class="text-sm text-blue-600">収支</div>
                        <div class="text-xl font-bold"
                             :class="monthlyBalance.balance >= 0 ? 'text-blue-700' : 'text-red-700'">
                            {{ monthlyBalance.balance >= 0 ? '+' : '' }}{{ formatAmount(monthlyBalance.balance) }}
                        </div>
                    </div>
                </div>

                <!-- payer別月次収支 -->
                <div class="mb-6 grid grid-cols-2 gap-4">
                    <div
                        v-for="(data, payerValue) in payerBalances"
                        :key="payerValue"
                        class="rounded-lg bg-gray-50 p-4 text-center"
                    >
                        <div class="text-sm text-gray-600">{{ data.label }}</div>
                        <div class="text-xl font-bold"
                             :class="data.balance >= 0 ? 'text-blue-700' : 'text-red-700'">
                            {{ data.balance >= 0 ? '+' : '' }}{{ formatAmount(data.balance) }}
                        </div>
                    </div>
                </div>

                <!-- カレンダー -->
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <!-- カレンダーヘッダー -->
                    <div class="flex items-center justify-between border-b bg-gray-50 px-6 py-4">
                        <button
                            @click="goToPreviousMonth"
                            class="rounded px-3 py-1 text-gray-600 hover:bg-gray-200"
                        >
                            &lt; 前月
                        </button>
                        <div class="flex items-center gap-4">
                            <h3 class="text-lg font-semibold text-gray-800">
                                {{ monthLabel }}
                            </h3>
                            <button
                                @click="goToCurrentMonth"
                                class="rounded bg-gray-200 px-2 py-1 text-sm text-gray-600 hover:bg-gray-300"
                            >
                                今月
                            </button>
                        </div>
                        <button
                            @click="goToNextMonth"
                            class="rounded px-3 py-1 text-gray-600 hover:bg-gray-200"
                        >
                            次月 &gt;
                        </button>
                    </div>

                    <!-- 曜日ヘッダー -->
                    <div class="grid grid-cols-7 border-b bg-gray-100">
                        <div
                            v-for="(day, index) in weekDays"
                            :key="day"
                            class="py-2 text-center text-sm font-medium"
                            :class="{
                                'text-red-500': index === 0,
                                'text-blue-500': index === 6,
                                'text-gray-600': index > 0 && index < 6,
                            }"
                        >
                            {{ day }}
                        </div>
                    </div>

                    <!-- カレンダーグリッド -->
                    <div class="grid grid-cols-7">
                        <div
                            v-for="(dayData, index) in calendarDays"
                            :key="index"
                            class="min-h-24 border-b border-r p-2"
                            :class="{
                                'bg-gray-50': !dayData.isCurrentMonth,
                                'bg-blue-50 ring-2 ring-blue-300 ring-inset': isToday(dayData),
                                'cursor-pointer hover:bg-blue-50': dayData.isCurrentMonth && dayData.day,
                            }"
                            @click="dayData.isCurrentMonth && openDayModal(dayData)"
                        >
                            <div
                                v-if="dayData.day"
                                class="text-sm font-medium"
                                :class="{
                                    'text-red-500': index % 7 === 0,
                                    'text-blue-500': index % 7 === 6,
                                    'text-gray-700': index % 7 !== 0 && index % 7 !== 6,
                                }"
                            >
                                {{ dayData.day }}
                            </div>
                            <div v-if="dayData.balance" class="mt-1 space-y-0.5">
                                <div v-if="dayData.balance.income > 0" class="text-xs text-green-600">
                                    +{{ formatAmount(dayData.balance.income) }}
                                </div>
                                <div v-if="dayData.balance.expense > 0" class="text-xs text-red-600">
                                    -{{ formatAmount(dayData.balance.expense) }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 日付詳細モーダル -->
        <Modal :show="showDayModal" @close="closeDayModal" max-width="lg">
            <div class="p-6">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-medium text-gray-900">
                        {{ selectedDateString }}の取引
                    </h2>
                    <PrimaryButton @click="openCreateModal">
                        + 取引を追加
                    </PrimaryButton>
                </div>

                <div v-if="isLoadingTransactions" class="mt-6 text-center text-gray-500">
                    読み込み中...
                </div>

                <div v-else-if="selectedDayTransactions.length === 0" class="mt-6 text-center text-gray-500">
                    この日の取引はありません
                </div>

                <div v-else class="mt-6">
                    <!-- PC: テーブル形式 -->
                    <div class="hidden overflow-x-auto sm:block">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase text-gray-500">
                                        種別
                                    </th>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase text-gray-500">
                                        カテゴリ
                                    </th>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase text-gray-500">
                                        支払元/受取人
                                    </th>
                                    <th class="px-4 py-2 text-right text-xs font-medium uppercase text-gray-500">
                                        金額
                                    </th>
                                    <th class="px-4 py-2 text-left text-xs font-medium uppercase text-gray-500">
                                        メモ
                                    </th>
                                    <th class="px-4 py-2 text-center text-xs font-medium uppercase text-gray-500">
                                        操作
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white">
                                <tr v-for="transaction in selectedDayTransactions" :key="transaction.id">
                                    <td class="whitespace-nowrap px-4 py-2 text-sm">
                                        <span
                                            class="inline-flex rounded-full px-2 text-xs font-semibold leading-5"
                                            :class="typeClass(transaction.type)"
                                        >
                                            {{ typeLabel(transaction.type) }}
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-2 text-sm text-gray-900">
                                        {{ transaction.category?.name || '-' }}
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-2 text-sm text-gray-900">
                                        {{ payerLabel(transaction.payer) }}
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-2 text-sm text-right font-medium"
                                        :class="transaction.type === 'income' ? 'text-green-600' : 'text-red-600'">
                                        {{ transaction.type === 'income' ? '+' : '-' }}{{ formatAmount(transaction.amount) }}
                                    </td>
                                    <td class="px-4 py-2 text-sm text-gray-900">
                                        <div class="max-w-[120px] break-words">
                                            {{ transaction.memo || '-' }}
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-2 text-sm text-center">
                                        <button
                                            @click="openEditModal(transaction)"
                                            class="mr-2 text-indigo-600 hover:text-indigo-900"
                                        >
                                            編集
                                        </button>
                                        <button
                                            @click="openDeleteModal(transaction)"
                                            class="text-red-600 hover:text-red-900"
                                        >
                                            削除
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- スマホ: コンパクトリスト形式 -->
                    <ul class="divide-y divide-gray-200 sm:hidden">
                        <li
                            v-for="transaction in selectedDayTransactions"
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

                <div class="mt-6 flex justify-end">
                    <SecondaryButton @click="closeDayModal">
                        閉じる
                    </SecondaryButton>
                </div>
            </div>
        </Modal>

        <!-- 取引追加モーダル -->
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
                                @change="onTypeChange"
                                class="rounded-full border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                            />
                            <span class="ml-2 text-sm text-gray-700">支出</span>
                        </label>
                        <label class="inline-flex items-center">
                            <input
                                type="radio"
                                v-model="createForm.type"
                                value="income"
                                @change="onTypeChange"
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
                            v-for="category in filteredCategories"
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

        <!-- 取引編集モーダル -->
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
                        placeholder="例: ランチ代"
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

        <!-- 取引削除確認モーダル -->
        <Modal :show="showDeleteModal" @close="closeDeleteModal" max-width="md">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900">
                    取引を削除
                </h2>

                <p class="mt-4 text-sm text-gray-600">
                    本当にこの取引を削除しますか？この操作は取り消せません。
                </p>

                <div v-if="deletingTransaction" class="mt-4 rounded-lg bg-gray-50 p-4">
                    <div class="text-sm">
                        <div class="mb-2">
                            <span class="font-medium">種別:</span>
                            <span
                                class="ml-2 inline-flex rounded-full px-2 text-xs font-semibold leading-5"
                                :class="typeClass(deletingTransaction.type)"
                            >
                                {{ typeLabel(deletingTransaction.type) }}
                            </span>
                        </div>
                        <div class="mb-2">
                            <span class="font-medium">カテゴリ:</span>
                            <span class="ml-2">{{ deletingTransaction.category?.name || '-' }}</span>
                        </div>
                        <div class="mb-2">
                            <span class="font-medium">金額:</span>
                            <span class="ml-2 font-bold"
                                  :class="deletingTransaction.type === 'income' ? 'text-green-600' : 'text-red-600'">
                                {{ deletingTransaction.type === 'income' ? '+' : '-' }}{{ formatAmount(deletingTransaction.amount) }}
                            </span>
                        </div>
                        <div v-if="deletingTransaction.memo">
                            <span class="font-medium">メモ:</span>
                            <span class="ml-2">{{ deletingTransaction.memo }}</span>
                        </div>
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton @click="closeDeleteModal">
                        キャンセル
                    </SecondaryButton>
                    <DangerButton @click="confirmDelete">
                        削除
                    </DangerButton>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
