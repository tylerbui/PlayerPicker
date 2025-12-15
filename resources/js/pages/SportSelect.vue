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

interface Props {
    sports: Sport[];
}

const props = defineProps<Props>();
const hoveredSport = ref<number | null>(null);

const selectSport = (slug: string) => {
    router.visit(`/sports/${slug}`);
};

// Fallback icons for sports if not set in database
const getSportIcon = (sport: Sport): string => {
    if (sport.icon) return sport.icon;
    
    const iconMap: Record<string, string> = {
        basketball: '🏀',
        football: '🏈',
        soccer: '⚽',
        baseball: '⚾',
        hockey: '🏒',
        tennis: '🎾',
        volleyball: '🏐',
        golf: '⛳',
        boxing: '🥊',
        swimming: '🏊',
    };
    
    return iconMap[sport.slug.toLowerCase()] || '⚽';
};
</script>

<template>
    <Head title="Select a Sport" />
    <div
        class="flex min-h-screen flex-col items-center justify-center bg-gradient-to-br from-slate-50 to-slate-100 p-6 dark:from-slate-900 dark:to-slate-800"
    >
        <div class="mb-12 text-center">
            <h1
                class="mb-3 text-5xl font-bold tracking-tight text-slate-900 dark:text-slate-50"
            >
                PlayerPicker
            </h1>
            <p class="text-lg text-slate-600 dark:text-slate-400">
                Choose your sport
            </p>
        </div>

        <div
            class="grid w-full max-w-6xl grid-cols-2 gap-6 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5"
        >
            <button
                v-for="sport in sports"
                :key="sport.id"
                @click="selectSport(sport.slug)"
                @mouseenter="hoveredSport = sport.id"
                @mouseleave="hoveredSport = null"
                class="group flex flex-col items-center justify-center gap-3 transition-all duration-300 hover:scale-110"
            >
                <div
                    class="flex h-24 w-24 items-center justify-center rounded-full bg-gradient-to-br from-blue-400 to-blue-600 text-5xl shadow-lg transition-all duration-300 group-hover:shadow-2xl dark:from-blue-500 dark:to-blue-700"
                    :class="{
                        'ring-4 ring-blue-500 ring-offset-4 scale-110':
                            hoveredSport === sport.id,
                    }"
                >
                    {{ getSportIcon(sport) }}
                </div>
                <span
                    class="text-sm font-medium text-slate-700 transition-colors duration-300 group-hover:text-blue-600 dark:text-slate-300 dark:group-hover:text-blue-400"
                >
                    {{ sport.name }}
                </span>
            </button>
        </div>

        <div v-if="sports.length === 0" class="mt-8 text-center">
            <p class="text-slate-500 dark:text-slate-400">
                No sports available yet
            </p>
        </div>
    </div>
</template>
