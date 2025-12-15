<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import PlaceholderPattern from '../components/PlaceholderPattern.vue';

const page = usePage();
const selectedLevel = computed(() => {
    const urlParams = new URLSearchParams(window.location.search);
    return urlParams.get('level') || sessionStorage.getItem('selectedLevel') || 'all';
});

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
];

const levelTitle = computed(() => {
    const level = selectedLevel.value;
    return level.charAt(0).toUpperCase() + level.slice(1);
});
</script>

<template>
    <Head :title="`Dashboard - ${levelTitle}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div
            class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
        >
            <!-- Level Header -->
            <div class="mb-4">
                <h1 class="text-3xl font-bold text-slate-900 dark:text-slate-50">
                    {{ levelTitle }} Dashboard
                </h1>
                <p class="text-sm text-slate-600 dark:text-slate-400">
                    Viewing {{ levelTitle.toLowerCase() }} level content
                </p>
            </div>

            <div class="grid auto-rows-min gap-4 md:grid-cols-3">
                <div
                    class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
                >
                    <PlaceholderPattern />
                </div>
                <div
                    class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
                >
                    <PlaceholderPattern />
                </div>
                <div
                    class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
                >
                    <PlaceholderPattern />
                </div>
            </div>
            <div
                class="relative min-h-[100vh] flex-1 rounded-xl border border-sidebar-border/70 md:min-h-min dark:border-sidebar-border"
            >
                <PlaceholderPattern />
            </div>
        </div>
    </AppLayout>
</template>
