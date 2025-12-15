<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';

interface Player {
    id: number;
    first_name: string;
    last_name: string;
    slug: string;
    number?: string;
    position?: string;
    height?: string;
    weight?: string;
    headshot?: string;
}

interface Team {
    id: number;
    name: string;
    slug: string;
    code?: string;
    city?: string;
    logo?: string;
    logo_url?: string;
    players: Player[];
}

interface Props {
    team: Team;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Teams',
        href: '/teams',
    },
    {
        title: props.team.name,
        href: `/teams/${props.team.slug}`,
    },
];

const goToPlayer = (slug: string) => {
    router.visit(`/players/${slug}`);
};
</script>

<template>
    <Head :title="team.name" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <!-- Team Header -->
            <div class="mb-6 flex items-center gap-6">
                <div class="flex h-24 w-24 items-center justify-center rounded-full bg-slate-100 dark:bg-slate-700">
                    <img
                        v-if="team.logo_url"
                        :src="team.logo_url"
                        :alt="team.name"
                        class="h-20 w-20 object-contain"
                    />
                    <span v-else class="text-3xl font-bold text-slate-400">
                        {{ team.code || team.name.substring(0, 2).toUpperCase() }}
                    </span>
                </div>
                <div>
                    <h1 class="text-4xl font-bold text-slate-900 dark:text-slate-50">
                        {{ team.name }}
                    </h1>
                    <p v-if="team.city" class="text-lg text-slate-600 dark:text-slate-400">
                        {{ team.city }}
                    </p>
                    <p class="text-sm text-slate-500 dark:text-slate-500">
                        {{ team.players.length }} players
                    </p>
                </div>
            </div>

            <!-- Players Grid -->
            <div v-if="team.players.length > 0" class="grid gap-4 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4">
                <button
                    v-for="player in team.players"
                    :key="player.id"
                    @click="goToPlayer(player.slug)"
                    class="group flex items-center gap-4 rounded-lg border border-slate-200 bg-white p-4 shadow-sm transition-all hover:scale-105 hover:shadow-lg dark:border-slate-700 dark:bg-slate-800"
                >
                    <div class="flex h-16 w-16 flex-shrink-0 items-center justify-center rounded-full bg-slate-100 dark:bg-slate-700">
                        <img
                            v-if="player.headshot"
                            :src="player.headshot"
                            :alt="`${player.first_name} ${player.last_name}`"
                            class="h-full w-full rounded-full object-cover"
                        />
                        <span v-else class="text-xl font-bold text-slate-400">
                            {{ player.number || '?' }}
                        </span>
                    </div>
                    <div class="flex-1 text-left">
                        <div class="flex items-center gap-2">
                            <span v-if="player.number" class="text-sm font-semibold text-slate-500 dark:text-slate-400">
                                #{{ player.number }}
                            </span>
                            <h3 class="font-semibold text-slate-900 group-hover:text-blue-600 dark:text-slate-50 dark:group-hover:text-blue-400">
                                {{ player.first_name }} {{ player.last_name }}
                            </h3>
                        </div>
                        <div class="flex gap-2 text-xs text-slate-500 dark:text-slate-400">
                            <span v-if="player.position">{{ player.position }}</span>
                            <span v-if="player.height && player.weight">•</span>
                            <span v-if="player.height">{{ player.height }}</span>
                            <span v-if="player.weight">{{ player.weight }}</span>
                        </div>
                    </div>
                </button>
            </div>

            <div v-else class="py-12 text-center">
                <p class="text-slate-500 dark:text-slate-400">
                    No players available for this team
                </p>
            </div>
        </div>
    </AppLayout>
</template>
