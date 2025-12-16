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
    logo_url?: string;
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
        class="h-screen overflow-hidden bg-gradient-to-br from-slate-50 to-slate-100 dark:from-slate-900 dark:to-slate-800"
    >
        <!-- Container -->
        <div class="flex h-full w-full flex-col">
            <!-- Top Bar with Back Button (Left) and Title (Center) -->
            <div class="relative flex items-center justify-center px-4 py-4">
                <!-- Back Button - Absolute Left -->
                <button
                    @click="goBack"
                    class="absolute left-4 flex items-center gap-1 text-sm text-slate-600 transition-colors hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-100"
                >
                    <span class="text-lg">←</span>
                    <span>Back to Sports</span>
                </button>

                <!-- Centered Title -->
                <div class="text-center">
                    <h1
                        class="text-3xl font-bold tracking-tight text-slate-900 dark:text-slate-50"
                    >
                        {{ sport.name }}
                    </h1>
                </div>
            </div>

            <!-- Scrollable Content Area -->
            <div class="flex-1 overflow-y-auto overflow-x-hidden px-4 pb-4">
                <div class="mx-auto w-full max-w-7xl">
                    <!-- Leagues by Category -->
                    <div class="space-y-6">
                        <div
                            v-for="(leagues, category) in groupedLeagues"
                            :key="category"
                            v-show="leagues.length > 0"
                        >
                            <h2
                                class="mb-3 text-lg font-bold text-slate-800 dark:text-slate-200"
                            >
                                {{ categoryLabels[category] }}
                            </h2>
                            <div
                                class="grid grid-cols-3 gap-3 sm:grid-cols-4 md:grid-cols-6 lg:grid-cols-8 xl:grid-cols-10"
                            >
                                <button
                                    v-for="league in leagues"
                                    :key="league.id"
                                    @click="selectLeague(league.slug)"
                                    @mouseenter="hoveredLeague = league.id"
                                    @mouseleave="hoveredLeague = null"
                                    class="group flex flex-col items-center gap-1.5 transition-all duration-300 hover:scale-105"
                                >
                                    <!-- Circle Container - Much Smaller -->
                                    <div class="flex items-center justify-center" style="height: 64px;">
                                        <div
                                            class="flex h-16 w-16 items-center justify-center rounded-full bg-white p-3 shadow-md transition-all duration-300 group-hover:shadow-lg dark:bg-slate-800"
                                            :class="{
                                                'ring-2 ring-blue-500 ring-offset-1 scale-105':
                                                    hoveredLeague === league.id,
                                            }"
                                        >
                                            <img
                                                v-if="league.logo_url || league.logo"
                                                :src="league.logo_url || league.logo"
                                                :alt="league.name"
                                                class="h-full w-full object-contain"
                                            />
                                            <span
                                                v-else
                                                class="text-lg font-bold text-slate-400"
                                            >
                                                {{ league.name.substring(0, 2).toUpperCase() }}
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <!-- Text Container - Much Smaller -->
                                    <div class="flex flex-col items-center gap-0 min-h-[40px]">
                                        <span
                                            class="text-center text-xs font-medium text-slate-700 transition-colors duration-300 group-hover:text-blue-600 dark:text-slate-300 dark:group-hover:text-blue-400 line-clamp-2 px-0.5"
                                        >
                                            {{ league.name }}
                                        </span>
                                    </div>
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
        </div>
    </div>
</template>
