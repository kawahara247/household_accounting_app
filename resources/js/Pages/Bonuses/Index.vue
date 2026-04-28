<script setup>
import CalculatorInput from '@/Components/CalculatorInput.vue';
import DangerButton from '@/Components/DangerButton.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    bonuses: {
        type: Array,
        required: true,
    },
    bonusTotals: {
        type: Array,
        required: true,
    },
    payers: {
        type: Array,
        required: true,
    },
});

const showCreateModal = ref(false);
const showEditModal = ref(false);
const showDeleteModal = ref(false);
const deleteTarget = ref(null);

const createForm = useForm({
    year_month: '',
    payer: '',
    amount: '',
});

const editForm = useForm({
    id: null,
    year_month: '',
    payer: '',
    amount: '',
});

const deleteForm = useForm({});

const payerLabel = (payerValue) => {
    const payer = props.payers.find(item => item.value === payerValue);
    return payer ? payer.label : payerValue;
};

const formatAmount = (amount) => {
    return new Intl.NumberFormat('ja-JP').format(amount);
};

const openCreateModal = () => {
    createForm.reset();
    createForm.clearErrors();
    showCreateModal.value = true;
};

const closeCreateModal = () => {
    showCreateModal.value = false;
    createForm.reset();
};

const submitCreate = () => {
    createForm.post(route('bonuses.store'), {
        preserveScroll: true,
        onSuccess: () => closeCreateModal(),
    });
};

const openEditModal = (bonus) => {
    editForm.id = bonus.id;
    editForm.year_month = bonus.year_month;
    editForm.payer = bonus.payer;
    editForm.amount = bonus.amount;
    editForm.clearErrors();
    showEditModal.value = true;
};

const closeEditModal = () => {
    showEditModal.value = false;
    editForm.reset();
};

const submitEdit = () => {
    editForm.put(route('bonuses.update', editForm.id), {
        preserveScroll: true,
        onSuccess: () => closeEditModal(),
    });
};

const openDeleteModal = (bonus) => {
    deleteTarget.value = bonus;
    showDeleteModal.value = true;
};

const closeDeleteModal = () => {
    showDeleteModal.value = false;
    deleteTarget.value = null;
};

const submitDelete = () => {
    deleteForm.delete(route('bonuses.destroy', deleteTarget.value.id), {
        preserveScroll: true,
        onSuccess: () => closeDeleteModal(),
    });
};
</script>

<template>
    <Head title="ボーナス記録" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    ボーナス記録
                </h2>
                <PrimaryButton @click="openCreateModal">
                    ボーナスを追加
                </PrimaryButton>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div
                        v-for="bonusTotal in bonusTotals"
                        :key="bonusTotal.value"
                        class="rounded-lg bg-gray-50 p-4 text-center"
                    >
                        <div class="text-sm text-gray-600">{{ bonusTotal.label }}</div>
                        <div class="text-xl font-bold text-green-700">
                            +{{ formatAmount(bonusTotal.amount) }}
                        </div>
                    </div>
                </div>

                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div v-if="bonuses.length > 0">
                        <table class="hidden min-w-full divide-y divide-gray-200 md:table">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">年月</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">受取人</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">金額</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">操作</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <tr v-for="bonus in bonuses" :key="bonus.id" class="hover:bg-gray-50">
                                    <td class="whitespace-nowrap px-4 py-3 text-sm font-medium text-gray-900">
                                        {{ bonus.year_month }}
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-900">
                                        {{ payerLabel(bonus.payer) }}
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-right text-sm font-semibold text-green-600">
                                        +{{ formatAmount(bonus.amount) }}
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-right text-sm">
                                        <button @click="openEditModal(bonus)" class="mr-2 text-indigo-600 hover:text-indigo-900">編集</button>
                                        <button @click="openDeleteModal(bonus)" class="text-red-600 hover:text-red-900">削除</button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>

                        <ul class="divide-y divide-gray-200 md:hidden">
                            <li v-for="bonus in bonuses" :key="bonus.id" class="p-4 hover:bg-gray-50">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0 flex-1">
                                        <div class="text-sm font-semibold text-gray-900">
                                            {{ bonus.year_month }}
                                        </div>
                                        <div class="mt-1 text-xs text-gray-500">
                                            {{ payerLabel(bonus.payer) }}
                                        </div>
                                    </div>
                                    <div class="shrink-0 text-right">
                                        <div class="text-sm font-bold text-green-600">
                                            +{{ formatAmount(bonus.amount) }}
                                        </div>
                                        <div class="mt-2 flex justify-end gap-3 text-xs">
                                            <button @click="openEditModal(bonus)" class="text-indigo-600">編集</button>
                                            <button @click="openDeleteModal(bonus)" class="text-red-600">削除</button>
                                        </div>
                                    </div>
                                </div>
                            </li>
                        </ul>
                    </div>

                    <div v-else class="p-8 text-center text-gray-500">
                        ボーナス記録がありません。「ボーナスを追加」から登録してください。
                    </div>
                </div>
            </div>
        </div>

        <Modal :show="showCreateModal" @close="closeCreateModal" max-width="md">
            <form class="p-6" @submit.prevent="submitCreate">
                <h2 class="text-lg font-medium text-gray-900">
                    ボーナスを追加
                </h2>

                <div class="mt-6">
                    <InputLabel for="create-year-month" value="年月" />
                    <TextInput
                        id="create-year-month"
                        v-model="createForm.year_month"
                        type="month"
                        class="mt-1 block w-full"
                    />
                    <InputError :message="createForm.errors.year_month" class="mt-2" />
                </div>

                <div class="mt-4">
                    <InputLabel for="create-payer" value="受取人" />
                    <select
                        id="create-payer"
                        v-model="createForm.payer"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                        <option value="">選択してください</option>
                        <option v-for="payer in payers" :key="payer.value" :value="payer.value">
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
                        placeholder="金額を入力"
                    />
                    <InputError :message="createForm.errors.amount" class="mt-2" />
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton @click="closeCreateModal" type="button">
                        キャンセル
                    </SecondaryButton>
                    <PrimaryButton :disabled="createForm.processing">
                        保存
                    </PrimaryButton>
                </div>
            </form>
        </Modal>

        <Modal :show="showEditModal" @close="closeEditModal" max-width="md">
            <form class="p-6" @submit.prevent="submitEdit">
                <h2 class="text-lg font-medium text-gray-900">
                    ボーナスを編集
                </h2>

                <div class="mt-6">
                    <InputLabel for="edit-year-month" value="年月" />
                    <TextInput
                        id="edit-year-month"
                        v-model="editForm.year_month"
                        type="month"
                        class="mt-1 block w-full"
                    />
                    <InputError :message="editForm.errors.year_month" class="mt-2" />
                </div>

                <div class="mt-4">
                    <InputLabel for="edit-payer" value="受取人" />
                    <select
                        id="edit-payer"
                        v-model="editForm.payer"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                        <option value="">選択してください</option>
                        <option v-for="payer in payers" :key="payer.value" :value="payer.value">
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
                        placeholder="金額を入力"
                    />
                    <InputError :message="editForm.errors.amount" class="mt-2" />
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton @click="closeEditModal" type="button">
                        キャンセル
                    </SecondaryButton>
                    <PrimaryButton :disabled="editForm.processing">
                        更新
                    </PrimaryButton>
                </div>
            </form>
        </Modal>

        <Modal :show="showDeleteModal" @close="closeDeleteModal" max-width="md">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900">
                    ボーナスを削除
                </h2>
                <p class="mt-2 text-sm text-gray-600">
                    {{ deleteTarget?.year_month }} の {{ deleteTarget ? payerLabel(deleteTarget.payer) : '' }} の記録を削除します。
                </p>
                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton @click="closeDeleteModal" type="button">
                        キャンセル
                    </SecondaryButton>
                    <DangerButton @click="submitDelete" type="button">
                        削除する
                    </DangerButton>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
