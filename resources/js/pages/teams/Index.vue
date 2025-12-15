<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';

interface Team {
    id: number;
    name: string;
    slug: string;
    code?: string;
    city?: string;
    logo?: string;
    logo_url?: string;
}

interface League {
    id: number;
    name: string;
    slug: string;
}

interface Props {
    teams: Team[];
    league?: League;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Teams',
        href: '/teams',
    },
];

const goToTeam = (slug: string) => {
    router.visit(`/teams/${slug}`);
};
</script>

<template>
    <Head :title="league ? `${league.name} Teams` : 'Teams'" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <div class="mb-4">
                <h1 class="text-3xl font-bold text-slate-900 dark:text-slate-50">
                    {{ league ? `${league.name} Teams` : 'Teams' }}
                </h1>
                <p class="text-sm text-slate-600 dark:text-slate-400">
                    {{ teams.length }} teams
                </p>
            </div>

            <div v-if="teams.length > 0" class="grid gap-6 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5">
                <button
                    v-for="team in teams"
                    :key="team.id"
                    @click="goToTeam(team.slug)"
                    class="group flex flex-col items-center gap-3 rounded-lg border border-slate-200 bg-white p-6 shadow-sm transition-all hover:scale-105 hover:shadow-lg dark:border-slate-700 dark:bg-slate-800"
                >
                    <div class="flex h-20 w-20 items-center justify-center rounded-full bg-slate-100 dark:bg-slate-700">
                        <img
                            v-if="team.logo_url"
                            :src="team.logo_url"
                            :alt="team.name"
                            class="h-16 w-16 object-contain"
                        />
                        <span v-else class="text-2xl font-bold text-slate-400">
                            {{ team.code || team.name.substring(0, 2).toUpperCase() }}
                        </span>
                    </div>
                    <div class="text-center">
                        <h3 class="font-semibold text-slate-900 group-hover:text-blue-600 dark:text-slate-50 dark:group-hover:text-blue-400">
                            {{ team.name }}
                        </h3>
                        <p v-if="team.city" class="text-xs text-slate-500 dark:text-slate-400">
                            {{ team.city }}
                        </p>
                    </div>
                </button>
            </div>

            <div v-else class="py-12 text-center">
                <p class="text-slate-500 dark:text-slate-400">
                    No teams available
                </p>
            </div>
        </div>
    </AppLayout>
</template>
