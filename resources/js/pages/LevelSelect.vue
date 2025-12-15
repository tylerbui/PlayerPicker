<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';

interface LevelOption {
    id: string;
    label: string;
    description: string;
    icon: string;
}

const levels: LevelOption[] = [
    {
        id: 'professional',
        label: 'Professional',
        description: 'Elite athletes and pro leagues',
        icon: '⭐',
    },
    {
        id: 'college',
        label: 'College',
        description: 'NCAA and university sports',
        icon: '🎓',
    },
    {
        id: 'amateur',
        label: 'Amateur',
        description: 'Local leagues and recreational',
        icon: '🏃',
    },
];

const hoveredLevel = ref<string | null>(null);

const selectLevel = (levelId: string) => {
    // Store level in session storage for persistence
    sessionStorage.setItem('selectedLevel', levelId);
    // Navigate to dashboard with level parameter
    router.visit(`/dashboard?level=${levelId}`);
};
</script>

<template>
    <Head title="Choose Your Level" />
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
                Choose your level to get started
            </p>
        </div>

        <div
            class="grid w-full max-w-5xl grid-cols-1 gap-8 md:grid-cols-3"
        >
            <button
                v-for="level in levels"
                :key="level.id"
                @click="selectLevel(level.id)"
                @mouseenter="hoveredLevel = level.id"
                @mouseleave="hoveredLevel = null"
                class="group relative flex flex-col items-center justify-center overflow-hidden rounded-3xl bg-white p-12 shadow-lg transition-all duration-300 hover:scale-105 hover:shadow-2xl dark:bg-slate-800"
                :class="{
                    'ring-4 ring-blue-500 ring-offset-4':
                        hoveredLevel === level.id,
                }"
            >
                <div
                    class="mb-6 flex h-32 w-32 items-center justify-center rounded-full bg-gradient-to-br from-blue-400 to-blue-600 text-6xl shadow-xl transition-transform duration-300 group-hover:rotate-6 group-hover:scale-110 dark:from-blue-500 dark:to-blue-700"
                >
                    {{ level.icon }}
                </div>
                <h2
                    class="mb-2 text-2xl font-bold text-slate-900 dark:text-slate-50"
                >
                    {{ level.label }}
                </h2>
                <p
                    class="text-center text-sm text-slate-600 dark:text-slate-400"
                >
                    {{ level.description }}
                </p>
                <div
                    class="absolute inset-0 -z-10 bg-gradient-to-br from-blue-50 to-purple-50 opacity-0 transition-opacity duration-300 group-hover:opacity-100 dark:from-blue-950 dark:to-purple-950"
                />
            </button>
        </div>

        <div class="mt-12 text-center">
            <p class="text-sm text-slate-500 dark:text-slate-500">
                Not sure?
                <button
                    @click="selectLevel('all')"
                    class="ml-1 font-medium text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300"
                >
                    Browse all levels
                </button>
            </p>
        </div>
    </div>
</template>
