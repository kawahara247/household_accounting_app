<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    categories: {
        type: Array,
        required: true,
    },
});
</script>

<template>
    <Head title="カテゴリ一覧" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                カテゴリ一覧
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <ul>
                            <li v-for="category in categories" :key="category.id">
                                {{ category.name }} ({{ category.type }})
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
