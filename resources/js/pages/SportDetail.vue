<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';

interface Sport {
    id: number;
    name: string;
    slug: string;
    icon?: string;
    description?: string;
}

interface League {
    id: number;
    name: string;
    slug: string;
    logo?: string;
    country?: string;
    category?: string;
}

interface Props {
    sport: Sport;
    leagues: League[];
}

const props = defineProps<Props>();
const hoveredLeague = ref<number | null>(null);

const selectLeague = (leagueSlug: string) => {
    router.visit(`/leagues/${leagueSlug}/teams`);
};

const goBack = () => {
    router.visit('/');
};

// Group leagues by category
const groupedLeagues = {
    professional: props.leagues.filter(l => l.category === 'professional'),
    college: props.leagues.filter(l => l.category === 'college'),
    amateur: props.leagues.filter(l => l.category === 'amateur'),
    other: props.leagues.filter(l => !l.category || !['professional', 'college', 'amateur'].includes(l.category)),
};

const categoryLabels: Record<string, string> = {
    professional: 'Professional',
    college: 'College',
    amateur: 'Amateur',
    other: 'Other',
};
</script>

<template>
    <Head :title="`${sport.name} Leagues`" />
    <div
        class="min-h-screen bg-gradient-to-br from-slate-50 to-slate-100 p-6 dark:from-slate-900 dark:to-slate-800"
    >
        <!-- Header -->
        <div class="mx-auto max-w-6xl">
            <button
                @click="goBack"
                class="mb-6 flex items-center gap-2 text-slate-600 transition-colors hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-100"
            >
                <span class="text-xl">←</span>
                <span>Back to Sports</span>
            </button>

            <div class="mb-12 text-center">
                <h1
                    class="mb-3 text-5xl font-bold tracking-tight text-slate-900 dark:text-slate-50"
                >
                    {{ sport.name }}
                </h1>
                <p class="text-lg text-slate-600 dark:text-slate-400">
                    Choose a league
                </p>
            </div>

            <!-- Leagues by Category -->
            <div class="space-y-12">
                <div
                    v-for="(leagues, category) in groupedLeagues"
                    :key="category"
                    v-show="leagues.length > 0"
                >
                    <h2
                        class="mb-6 text-2xl font-bold text-slate-800 dark:text-slate-200"
                    >
                        {{ categoryLabels[category] }}
                    </h2>
                    <div
                        class="grid grid-cols-2 gap-6 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5"
                    >
                        <button
                            v-for="league in leagues"
                            :key="league.id"
                            @click="selectLeague(league.slug)"
                            @mouseenter="hoveredLeague = league.id"
                            @mouseleave="hoveredLeague = null"
                            class="group flex flex-col items-center justify-center gap-3 transition-all duration-300 hover:scale-110"
                        >
                            <div
                                class="flex h-24 w-24 items-center justify-center rounded-full bg-white p-4 shadow-lg transition-all duration-300 group-hover:shadow-2xl dark:bg-slate-800"
                                :class="{
                                    'ring-4 ring-blue-500 ring-offset-4 scale-110':
                                        hoveredLeague === league.id,
                                }"
                            >
                                <img
                                    v-if="league.logo"
                                    :src="league.logo"
                                    :alt="league.name"
                                    class="h-full w-full object-contain"
                                />
                                <span
                                    v-else
                                    class="text-3xl font-bold text-slate-400"
                                >
                                    {{ league.name.substring(0, 2).toUpperCase() }}
                                </span>
                            </div>
                            <span
                                class="text-center text-sm font-medium text-slate-700 transition-colors duration-300 group-hover:text-blue-600 dark:text-slate-300 dark:group-hover:text-blue-400"
                            >
                                {{ league.name }}
                            </span>
                            <span
                                v-if="league.country"
                                class="text-xs text-slate-500 dark:text-slate-500"
                            >
                                {{ league.country }}
                            </span>
                        </button>
                    </div>
                </div>
            </div>

            <div v-if="leagues.length === 0" class="mt-12 text-center">
                <p class="text-slate-500 dark:text-slate-400">
                    No leagues available for this sport yet
                </p>
            </div>
        </div>
    </div>
</template>
