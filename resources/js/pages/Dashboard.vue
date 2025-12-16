<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { Heart, Users, User } from 'lucide-vue-next';

interface Props {
    selectedLevel?: string;
    favoriteTeams?: any[];
    favoritePlayers?: any[];
}

const props = withDefaults(defineProps<Props>(), {
    selectedLevel: 'all',
    favoriteTeams: () => [],
    favoritePlayers: () => [],
});

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
];

const levelTitle = computed(() => {
    const level = props.selectedLevel;
    return level.charAt(0).toUpperCase() + level.slice(1);
});
</script>

<template>
    <Head :title="`Dashboard - ${levelTitle}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div
            class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4"
        >
            <!-- Level Header -->
            <div class="mb-2">
                <h1 class="text-3xl font-bold text-slate-900 dark:text-slate-50">
                    {{ levelTitle }} Dashboard
                </h1>
                <p class="text-sm text-slate-600 dark:text-slate-400">
                    Welcome back! Here are your favorite teams and players
                </p>
            </div>

            <!-- Favorite Teams Section -->
            <div>
                <div class="mb-4 flex items-center gap-2">
                    <Users class="h-5 w-5 text-slate-700 dark:text-slate-300" />
                    <h2 class="text-xl font-semibold text-slate-900 dark:text-slate-50">
                        Favorite Teams
                    </h2>
                </div>
                
                <div v-if="props.favoriteTeams && props.favoriteTeams.length > 0" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <Link
                        v-for="team in props.favoriteTeams"
                        :key="team.id"
                        :href="`/teams/${team.slug}`"
                        class="group relative overflow-hidden rounded-lg border border-sidebar-border/70 bg-white p-4 transition-all hover:shadow-lg dark:border-sidebar-border dark:bg-slate-800"
                    >
                        <div class="flex items-center gap-3">
                            <div v-if="team.logo_url" class="h-12 w-12 flex-shrink-0">
                                <img :src="team.logo_url" :alt="team.name" class="h-full w-full object-contain" />
                            </div>
                            <div class="flex-1 min-w-0">
                                <h3 class="font-semibold text-slate-900 truncate group-hover:text-blue-600 dark:text-slate-50 dark:group-hover:text-blue-400">
                                    {{ team.name }}
                                </h3>
                                <div v-if="team.league" class="flex items-center gap-1.5">
                                    <img v-if="team.league.logo_url" :src="team.league.logo_url" :alt="team.league.name" class="h-4 w-4 object-contain" />
                                    <p class="text-sm text-slate-600 dark:text-slate-400">
                                        {{ team.league.name }}
                                    </p>
                                </div>
                            </div>
                            <Heart class="h-5 w-5 fill-red-500 text-red-500" />
                        </div>
                    </Link>
                </div>
                
                <div v-else class="rounded-lg border border-dashed border-sidebar-border/70 bg-slate-50 p-8 text-center dark:border-sidebar-border dark:bg-slate-800/50">
                    <Users class="mx-auto h-12 w-12 text-slate-400" />
                    <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">
                        You haven't added any favorite teams yet.
                    </p>
                    <Link href="/teams" class="mt-2 inline-block text-sm font-medium text-blue-600 hover:text-blue-700 dark:text-blue-400">
                        Browse Teams
                    </Link>
                </div>
            </div>

            <!-- Favorite Players Section -->
            <div>
                <div class="mb-4 flex items-center gap-2">
                    <User class="h-5 w-5 text-slate-700 dark:text-slate-300" />
                    <h2 class="text-xl font-semibold text-slate-900 dark:text-slate-50">
                        Favorite Players
                    </h2>
                </div>
                
                <div v-if="props.favoritePlayers && props.favoritePlayers.length > 0" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <Link
                        v-for="player in props.favoritePlayers"
                        :key="player.id"
                        :href="`/players/${player.slug}`"
                        class="group relative overflow-hidden rounded-lg border border-sidebar-border/70 bg-white p-4 transition-all hover:shadow-lg dark:border-sidebar-border dark:bg-slate-800"
                    >
                        <div class="flex items-center gap-3">
                            <div v-if="player.photo_url" class="h-12 w-12 flex-shrink-0">
                                <img :src="player.photo_url" :alt="player.full_name" class="h-full w-full rounded-full object-cover" />
                            </div>
                            <div class="flex-1 min-w-0">
                                <h3 class="font-semibold text-slate-900 truncate group-hover:text-blue-600 dark:text-slate-50 dark:group-hover:text-blue-400">
                                    {{ player.full_name }}
                                </h3>
                                <p v-if="player.team" class="text-sm text-slate-600 dark:text-slate-400">
                                    {{ player.team.name }}
                                </p>
                                <p v-if="player.position" class="text-xs text-slate-500 dark:text-slate-500">
                                    {{ player.position }}
                                </p>
                            </div>
                            <Heart class="h-5 w-5 fill-red-500 text-red-500" />
                        </div>
                    </Link>
                </div>
                
                <div v-else class="rounded-lg border border-dashed border-sidebar-border/70 bg-slate-50 p-8 text-center dark:border-sidebar-border dark:bg-slate-800/50">
                    <User class="mx-auto h-12 w-12 text-slate-400" />
                    <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">
                        You haven't added any favorite players yet.
                    </p>
                    <Link href="/players" class="mt-2 inline-block text-sm font-medium text-blue-600 hover:text-blue-700 dark:text-blue-400">
                        Browse Players
                    </Link>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
