<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { Line } from 'vue-chartjs';
import {
    Chart as ChartJS,
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    Title,
    Tooltip,
    Legend,
} from 'chart.js';

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, Title, Tooltip, Legend);

const COLORS = [
    '#3b82f6', '#ef4444', '#10b981', '#f59e0b', '#8b5cf6',
    '#06b6d4', '#ec4899', '#84cc16', '#f97316', '#6366f1',
];

const TABS = [
    { value: 'expense', label: '支出', activeClass: 'bg-red-500 text-white' },
    { value: 'income',  label: '収入', activeClass: 'bg-green-500 text-white' },
    { value: 'balance', label: '収支', activeClass: 'bg-indigo-500 text-white' },
];

const props = defineProps({
    type: { type: String, required: true },
    filters: { type: Object, required: true },
    splitByPayer: { type: Boolean, required: true },
    labels: { type: Array, required: true },
    datasets: { type: Array, required: true },
    availableMonths: { type: Array, required: true },
    payers: { type: Array, required: true },
});

const filterForm = ref({
    start_month: props.filters.start_month,
    end_month: props.filters.end_month,
});

// カテゴリ名の一覧（重複除去）
const categoryNames = computed(() =>
    [...new Set(props.datasets.map(ds => ds.name))]
);

// 選択中のカテゴリ（初期値: 全選択）
const selectedCategories = ref(new Set(categoryNames.value));

const toggleCategory = (name) => {
    if (selectedCategories.value.has(name)) {
        selectedCategories.value.delete(name);
    } else {
        selectedCategories.value.add(name);
    }
    // Set の変更を Vue に検知させる
    selectedCategories.value = new Set(selectedCategories.value);
};

const navigate = (overrides = {}) => {
    const params = {
        type: props.type,
        start_month: filterForm.value.start_month,
        end_month: filterForm.value.end_month,
        ...(props.splitByPayer ? { split_by_payer: '1' } : {}),
        ...overrides,
    };
    // 不要なキーを除去
    Object.keys(params).forEach(k => params[k] === undefined && delete params[k]);
    router.get(route('trends.index'), params);
};

const switchType = (type) => navigate({ type, split_by_payer: props.splitByPayer ? '1' : undefined });
const applyFilters = () => navigate();
const toggleSplitByPayer = () => navigate({ split_by_payer: props.splitByPayer ? undefined : '1' });

const formattedMonths = computed(() =>
    props.availableMonths.map(ym => {
        const [year, month] = ym.split('-');
        return { value: ym, label: `${year}年${parseInt(month, 10)}月` };
    })
);

// カテゴリ＋支払人フィルター適用後のデータセット
const visibleDatasets = computed(() =>
    props.datasets.filter(ds =>
        selectedCategories.value.has(ds.name) && ds.data.some(v => v !== 0)
    )
);

const chartData = computed(() => ({
    labels: props.labels.map(ym => {
        const [year, month] = ym.split('-');
        return `${year}年${parseInt(month, 10)}月`;
    }),
    datasets: visibleDatasets.value.map((ds, i) => ({
        label: props.splitByPayer ? `${ds.name}（${ds.payerLabel}）` : ds.name,
        data: ds.data,
        borderColor: COLORS[i % COLORS.length],
        backgroundColor: COLORS[i % COLORS.length] + '33',
        tension: 0.3,
        pointRadius: 4,
    })),
}));

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { position: 'bottom' },
    },
    scales: {
        y: {
            ticks: {
                callback: (v) => v.toLocaleString('ja-JP') + '円',
            },
        },
    },
};

const chartMinWidth = computed(() => Math.max(props.labels.length * 60, 300));
</script>

<template>
    <Head title="推移グラフ" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                推移グラフ
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <!-- タブ切替 -->
                <div class="mb-6 flex gap-2">
                    <button
                        v-for="tab in TABS"
                        :key="tab.value"
                        @click="switchType(tab.value)"
                        class="rounded-md px-4 py-2 text-sm font-medium transition-colors"
                        :class="type === tab.value
                            ? tab.activeClass
                            : 'bg-white text-gray-700 hover:bg-gray-50 border border-gray-300'"
                    >
                        {{ tab.label }}
                    </button>
                </div>

                <!-- フィルター -->
                <div class="mb-6 overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6 space-y-4">
                        <!-- 期間 -->
                        <div class="flex flex-wrap items-end gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">開始月</label>
                                <select
                                    v-model="filterForm.start_month"
                                    class="mt-1 block rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                                >
                                    <option
                                        v-for="m in formattedMonths"
                                        :key="m.value"
                                        :value="m.value"
                                    >
                                        {{ m.label }}
                                    </option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">終了月</label>
                                <select
                                    v-model="filterForm.end_month"
                                    class="mt-1 block rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                                >
                                    <option
                                        v-for="m in formattedMonths"
                                        :key="m.value"
                                        :value="m.value"
                                    >
                                        {{ m.label }}
                                    </option>
                                </select>
                            </div>
                            <button
                                @click="applyFilters"
                                class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700"
                            >
                                適用
                            </button>
                        </div>

                        <!-- 支払人別トグル -->
                        <div class="flex items-center gap-3">
                            <button
                                @click="toggleSplitByPayer"
                                class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors"
                                :class="splitByPayer ? 'bg-indigo-600' : 'bg-gray-200'"
                                type="button"
                            >
                                <span
                                    class="inline-block h-4 w-4 transform rounded-full bg-white shadow transition-transform"
                                    :class="splitByPayer ? 'translate-x-6' : 'translate-x-1'"
                                />
                            </button>
                            <span class="text-sm font-medium text-gray-700">支払人別に表示</span>
                        </div>

                        <!-- カテゴリ絞り込み -->
                        <div v-if="type !== 'balance' && categoryNames.length > 0">
                            <p class="mb-2 text-sm font-medium text-gray-700">カテゴリ絞り込み</p>
                            <div class="flex flex-wrap gap-2">
                                <button
                                    v-for="name in categoryNames"
                                    :key="name"
                                    @click="toggleCategory(name)"
                                    type="button"
                                    class="rounded-full border px-3 py-1 text-xs font-medium transition-colors"
                                    :class="selectedCategories.has(name)
                                        ? 'bg-indigo-600 border-indigo-600 text-white'
                                        : 'bg-white border-gray-300 text-gray-600 hover:bg-gray-50'"
                                >
                                    {{ name }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- グラフ -->
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div v-if="visibleDatasets.length > 0" class="overflow-x-auto">
                            <div :style="{ minWidth: chartMinWidth + 'px', height: '320px' }">
                                <Line :data="chartData" :options="chartOptions" />
                            </div>
                        </div>
                        <div v-else class="py-12 text-center text-gray-500">
                            選択した期間・カテゴリにデータがありません。
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
