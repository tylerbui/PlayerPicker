<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const selectedLevel = computed(() => page.props.selectedLevel as string);

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Players',
        href: '/players',
    },
];

const levelTitle = computed(() => {
    const level = selectedLevel.value;
    return level.charAt(0).toUpperCase() + level.slice(1);
});
</script>

<template>
    <Head title="Players" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <div class="mb-4">
                <h1 class="text-3xl font-bold text-slate-900 dark:text-slate-50">
                    {{ levelTitle }} Players
                </h1>
                <p class="text-sm text-slate-600 dark:text-slate-400">
                    Browse players at the {{ levelTitle.toLowerCase() }} level
                </p>
            </div>

            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                <!-- Placeholder cards -->
                <div
                    v-for="i in 8"
                    :key="i"
                    class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-800"
                >
                    <div class="mb-4 h-24 w-24 animate-pulse rounded-full bg-slate-200 dark:bg-slate-700"></div>
                    <div class="mb-2 h-6 w-3/4 animate-pulse rounded bg-slate-200 dark:bg-slate-700"></div>
                    <div class="h-4 w-1/2 animate-pulse rounded bg-slate-100 dark:bg-slate-600"></div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
