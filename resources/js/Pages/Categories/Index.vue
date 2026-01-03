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
import { ref, nextTick } from 'vue';

defineProps({
    categories: {
        type: Array,
        required: true,
    },
});

// モーダル表示状態
const showCreateModal = ref(false);
const showEditModal = ref(false);
const showDeleteModal = ref(false);

// 入力フィールドの参照
const nameInput = ref(null);

// 作成フォーム
const createForm = useForm({
    name: '',
    type: 'expense',
    icon: '',
    color: '',
});

// 編集フォーム
const editForm = useForm({
    id: null,
    name: '',
    type: 'expense',
    icon: '',
    color: '',
});

// 削除対象
const deleteTarget = ref(null);

// 種別表示用
const typeLabel = (type) => {
    return type === 'income' ? '収入' : '支出';
};

const typeClass = (type) => {
    return type === 'income'
        ? 'bg-green-100 text-green-800'
        : 'bg-red-100 text-red-800';
};

// 作成モーダル
const openCreateModal = () => {
    createForm.reset();
    createForm.clearErrors();
    showCreateModal.value = true;
    nextTick(() => nameInput.value?.focus());
};

const closeCreateModal = () => {
    showCreateModal.value = false;
    createForm.reset();
};

const submitCreate = () => {
    createForm.post(route('categories.store'), {
        preserveScroll: true,
        onSuccess: () => closeCreateModal(),
    });
};

// 編集モーダル
const openEditModal = (category) => {
    editForm.id = category.id;
    editForm.name = category.name;
    editForm.type = category.type;
    editForm.icon = category.icon || '';
    editForm.color = category.color || '';
    editForm.clearErrors();
    showEditModal.value = true;
};

const closeEditModal = () => {
    showEditModal.value = false;
    editForm.reset();
};

const submitEdit = () => {
    editForm.put(route('categories.update', editForm.id), {
        preserveScroll: true,
        onSuccess: () => closeEditModal(),
    });
};

// 削除確認モーダル
const openDeleteModal = (category) => {
    deleteTarget.value = category;
    showDeleteModal.value = true;
};

const closeDeleteModal = () => {
    showDeleteModal.value = false;
    deleteTarget.value = null;
};

const submitDelete = () => {
    useForm({}).delete(route('categories.destroy', deleteTarget.value.id), {
        preserveScroll: true,
        onSuccess: () => closeDeleteModal(),
    });
};
</script>

<template>
    <Head title="カテゴリ一覧" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    カテゴリ一覧
                </h2>
                <PrimaryButton @click="openCreateModal">
                    カテゴリを追加
                </PrimaryButton>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <!-- カテゴリ一覧テーブル -->
                        <table v-if="categories.length > 0" class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                        カテゴリ名
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                        種別
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                        アイコン
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                        色
                                    </th>
                                    <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">
                                        操作
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white">
                                <tr v-for="category in categories" :key="category.id">
                                    <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900">
                                        {{ category.name }}
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm">
                                        <span
                                            class="inline-flex rounded-full px-2 text-xs font-semibold leading-5"
                                            :class="typeClass(category.type)"
                                        >
                                            {{ typeLabel(category.type) }}
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                        {{ category.icon || '-' }}
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                        <span
                                            v-if="category.color"
                                            class="inline-block h-5 w-5 rounded"
                                            :style="{ backgroundColor: category.color }"
                                        ></span>
                                        <span v-else>-</span>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                                        <button
                                            @click="openEditModal(category)"
                                            class="mr-3 text-indigo-600 hover:text-indigo-900"
                                        >
                                            編集
                                        </button>
                                        <button
                                            @click="openDeleteModal(category)"
                                            class="text-red-600 hover:text-red-900"
                                        >
                                            削除
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>

                        <!-- カテゴリがない場合 -->
                        <div v-else class="text-center py-8 text-gray-500">
                            カテゴリがありません。「カテゴリを追加」ボタンから追加してください。
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 作成モーダル -->
        <Modal :show="showCreateModal" @close="closeCreateModal" max-width="md">
            <form @submit.prevent="submitCreate" class="p-6">
                <h2 class="text-lg font-medium text-gray-900">
                    カテゴリを追加
                </h2>

                <div class="mt-6">
                    <InputLabel for="create-name" value="カテゴリ名" />
                    <TextInput
                        id="create-name"
                        ref="nameInput"
                        v-model="createForm.name"
                        type="text"
                        class="mt-1 block w-full"
                        placeholder="例: 食費"
                    />
                    <InputError :message="createForm.errors.name" class="mt-2" />
                </div>

                <div class="mt-4">
                    <InputLabel value="種別" />
                    <div class="mt-2 flex gap-6">
                        <label class="inline-flex items-center">
                            <input
                                type="radio"
                                v-model="createForm.type"
                                value="expense"
                                class="rounded-full border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                            />
                            <span class="ml-2 text-sm text-gray-700">支出</span>
                        </label>
                        <label class="inline-flex items-center">
                            <input
                                type="radio"
                                v-model="createForm.type"
                                value="income"
                                class="rounded-full border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                            />
                            <span class="ml-2 text-sm text-gray-700">収入</span>
                        </label>
                    </div>
                    <InputError :message="createForm.errors.type" class="mt-2" />
                </div>

                <div class="mt-4">
                    <InputLabel for="create-icon" value="アイコン（任意）" />
                    <TextInput
                        id="create-icon"
                        v-model="createForm.icon"
                        type="text"
                        class="mt-1 block w-full"
                        placeholder="例: 🍔"
                    />
                    <InputError :message="createForm.errors.icon" class="mt-2" />
                </div>

                <div class="mt-4">
                    <InputLabel for="create-color" value="色（任意）" />
                    <div class="mt-1 flex items-center gap-2">
                        <input
                            id="create-color"
                            v-model="createForm.color"
                            type="color"
                            class="h-10 w-10 cursor-pointer rounded border border-gray-300"
                        />
                        <TextInput
                            v-model="createForm.color"
                            type="text"
                            class="block w-full"
                            placeholder="例: #FF5733"
                        />
                    </div>
                    <InputError :message="createForm.errors.color" class="mt-2" />
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
                    カテゴリを編集
                </h2>

                <div class="mt-6">
                    <InputLabel for="edit-name" value="カテゴリ名" />
                    <TextInput
                        id="edit-name"
                        v-model="editForm.name"
                        type="text"
                        class="mt-1 block w-full"
                    />
                    <InputError :message="editForm.errors.name" class="mt-2" />
                </div>

                <div class="mt-4">
                    <InputLabel value="種別" />
                    <div class="mt-2 flex gap-6">
                        <label class="inline-flex items-center">
                            <input
                                type="radio"
                                v-model="editForm.type"
                                value="expense"
                                class="rounded-full border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                            />
                            <span class="ml-2 text-sm text-gray-700">支出</span>
                        </label>
                        <label class="inline-flex items-center">
                            <input
                                type="radio"
                                v-model="editForm.type"
                                value="income"
                                class="rounded-full border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                            />
                            <span class="ml-2 text-sm text-gray-700">収入</span>
                        </label>
                    </div>
                    <InputError :message="editForm.errors.type" class="mt-2" />
                </div>

                <div class="mt-4">
                    <InputLabel for="edit-icon" value="アイコン（任意）" />
                    <TextInput
                        id="edit-icon"
                        v-model="editForm.icon"
                        type="text"
                        class="mt-1 block w-full"
                    />
                    <InputError :message="editForm.errors.icon" class="mt-2" />
                </div>

                <div class="mt-4">
                    <InputLabel for="edit-color" value="色（任意）" />
                    <div class="mt-1 flex items-center gap-2">
                        <input
                            id="edit-color"
                            v-model="editForm.color"
                            type="color"
                            class="h-10 w-10 cursor-pointer rounded border border-gray-300"
                        />
                        <TextInput
                            v-model="editForm.color"
                            type="text"
                            class="block w-full"
                        />
                    </div>
                    <InputError :message="editForm.errors.color" class="mt-2" />
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
                    カテゴリの削除
                </h2>

                <p class="mt-4 text-sm text-gray-600">
                    「{{ deleteTarget?.name }}」を削除してもよろしいですか？
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
