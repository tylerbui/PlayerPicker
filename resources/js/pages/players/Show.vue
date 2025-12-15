<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref } from 'vue';

interface Team {
    id: number;
    name: string;
    slug: string;
    logo_url?: string;
    primary_color?: string;
    secondary_color?: string;
}

interface Player {
    id: number;
    first_name: string;
    last_name: string;
    full_name?: string;
    slug: string;
    number?: string;
    position?: string;
    height?: string;
    weight?: string;
    photo_url?: string;
    headshot?: string;
    birth_date?: string;
    age?: number;
    college?: string;
    country?: string;
    nationality?: string;
    team: Team;
    career_stats?: any;
    current_season_stats?: any;
    previous_season_stats?: any;
    biography?: string;
}

interface Props {
    player: Player;
    needsSync?: boolean;
}

const props = defineProps<Props>();

const fullName = computed(() => props.player.full_name || `${props.player.first_name} ${props.player.last_name}`);
const teamPrimary = computed(() => props.player.team.primary_color || '#2563eb');
const teamSecondary = computed(() => props.player.team.secondary_color || '#60a5fa');

const goBack = () => {
    router.visit('/players');
};

const goToTeam = () => {
    router.visit(`/teams/${props.player.team.slug}`);
};

// Live game state
const liveData = ref<any>(null);
const showLiveBanner = ref(false);

// Recent games
const recentGames = ref<any[]>([]);
const loadingRecent = ref(true);

// Season averages
const currentAvg = ref<any>(null);
const previousAvg = ref<any>(null);
const loadingAvg = ref(true);

let liveInterval: any = null;

const fetchLiveStats = async () => {
    try {
        const res = await fetch(`/api/v1/players/${props.player.slug}/live`);
        if (res.ok) {
            const data = await res.json();
            liveData.value = data;
            showLiveBanner.value = true;
        } else {
            showLiveBanner.value = false;
        }
    } catch (e) {
        showLiveBanner.value = false;
    }
};

const fetchRecentGames = async () => {
    try {
        const res = await fetch(`/api/v1/players/${props.player.slug}/recent`);
        const data = await res.json();
        if (data.ok && data.games) {
            recentGames.value = data.games;
        }
    } catch (e) {
        console.error('Failed to load recent games', e);
    } finally {
        loadingRecent.value = false;
    }
};

const fetchAverages = async () => {
    try {
        const res = await fetch(`/api/v1/players/${props.player.slug}/averages`);
        const data = await res.json();
        if (data.ok) {
            currentAvg.value = data.current;
            previousAvg.value = data.previous;
        }
    } catch (e) {
        console.error('Failed to load averages', e);
    } finally {
        loadingAvg.value = false;
    }
};

const formatPlayerLine = (line: any) => {
    if (!line) return '—';
    const parts = [];
    if (line.minutes) parts.push(`${line.minutes}m`);
    if (line.pts != null) parts.push(`${line.pts} pts`);
    if (line.reb != null) parts.push(`${line.reb} reb`);
    if (line.ast != null) parts.push(`${line.ast} ast`);
    if (line.stl != null) parts.push(`${line.stl} stl`);
    if (line.blk != null) parts.push(`${line.blk} blk`);
    if (line.tov != null) parts.push(`${line.tov} TO`);
    return parts.join(' · ');
};

const formatDate = (dateStr: string) => {
    return new Date(dateStr).toLocaleDateString(undefined, { month: 'short', day: 'numeric' });
};

onMounted(() => {
    fetchLiveStats();
    fetchRecentGames();
    fetchAverages();
    
    // Poll live stats every 15 seconds
    liveInterval = setInterval(fetchLiveStats, 15000);
});

onUnmounted(() => {
    if (liveInterval) clearInterval(liveInterval);
});
</script>

<template>
    <Head :title="fullName" />
    
    <div 
        class="min-h-screen p-8"
        :style="{
            background: `linear-gradient(135deg, color-mix(in srgb, ${teamPrimary} 5%, white), color-mix(in srgb, ${teamSecondary} 5%, white))`
        }"
    >
        <div class="mx-auto max-w-[1400px]">
            <!-- Live Game Banner -->
            <div v-if="showLiveBanner && liveData" class="mb-4 rounded-lg border border-lime-400 bg-lime-100 p-4">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <strong class="text-lime-900">
                        {{ liveData.live ? 'Live' : (liveData.state === 'pre' ? 'Pregame' : 'Final') }}
                    </strong>
                    <span class="text-lime-800">{{ liveData.clock }}</span>
                    <div class="ml-auto font-semibold text-lime-950">
                        {{ formatPlayerLine(liveData.line) }}
                    </div>
                </div>
            </div>

            <button 
                @click="goBack"
                class="mb-4 inline-block text-lg transition-opacity hover:opacity-70"
                :style="{ color: teamPrimary }"
            >
                ← All players
            </button>

            <!-- Hero Section -->
            <div 
                class="mb-8 grid gap-12 rounded-2xl bg-white p-14 shadow-sm"
                style="grid-template-columns: 300px 1fr; border-left-width: 6px"
                :style="{ borderLeftColor: teamPrimary }"
            >
                <img 
                    :src="player.photo_url || player.headshot || '/placeholder.png'"
                    :alt="fullName"
                    class="h-80 w-80 rounded-xl object-cover"
                    :style="{ background: `color-mix(in srgb, ${teamPrimary} 10%, white)` }"
                />
                
                <div>
                    <h1 
                        class="mb-2 text-7xl font-bold"
                        :style="{ color: `color-mix(in srgb, ${teamPrimary} 90%, black)` }"
                    >
                        {{ fullName }}
                    </h1>
                    
                    <div class="mb-6 text-2xl text-gray-500">
                        <button 
                            @click="goToTeam"
                            class="hover:underline"
                            :style="{ color: teamPrimary }"
                        >
                            {{ player.team.name }}
                        </button>
                        <span v-if="player.number"> · #{{ player.number }}</span>
                    </div>
                    
                    <div class="mt-8 grid grid-cols-5 gap-4">
                        <div 
                            class="rounded-lg p-4 text-center"
                            :style="{ background: `color-mix(in srgb, ${teamPrimary} 8%, white)` }"
                        >
                            <div class="text-sm uppercase tracking-wide text-gray-500">Position</div>
                            <div 
                                class="mt-1 text-3xl font-semibold"
                                :style="{ color: teamPrimary }"
                            >
                                {{ player.position || 'N/A' }}
                            </div>
                        </div>
                        
                        <div 
                            class="rounded-lg p-4 text-center"
                            :style="{ background: `color-mix(in srgb, ${teamPrimary} 8%, white)` }"
                        >
                            <div class="text-sm uppercase tracking-wide text-gray-500">Age</div>
                            <div 
                                class="mt-1 text-3xl font-semibold"
                                :style="{ color: teamPrimary }"
                            >
                                {{ player.age || 'N/A' }}
                            </div>
                        </div>
                        
                        <div 
                            class="rounded-lg p-4 text-center"
                            :style="{ background: `color-mix(in srgb, ${teamPrimary} 8%, white)` }"
                        >
                            <div class="text-sm uppercase tracking-wide text-gray-500">Height</div>
                            <div 
                                class="mt-1 text-3xl font-semibold"
                                :style="{ color: teamPrimary }"
                            >
                                {{ player.height || 'N/A' }}
                            </div>
                        </div>
                        
                        <div 
                            class="rounded-lg p-4 text-center"
                            :style="{ background: `color-mix(in srgb, ${teamPrimary} 8%, white)` }"
                        >
                            <div class="text-sm uppercase tracking-wide text-gray-500">Weight</div>
                            <div 
                                class="mt-1 text-3xl font-semibold"
                                :style="{ color: teamPrimary }"
                            >
                                {{ player.weight || 'N/A' }}
                            </div>
                        </div>
                        
                        <div 
                            class="rounded-lg p-4 text-center"
                            :style="{ background: `color-mix(in srgb, ${teamPrimary} 8%, white)` }"
                        >
                            <div class="text-sm uppercase tracking-wide text-gray-500">Nationality</div>
                            <div 
                                class="mt-1 text-3xl font-semibold"
                                :style="{ color: teamPrimary }"
                            >
                                {{ player.nationality || player.country || 'N/A' }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Biography -->
            <div v-if="player.biography" class="mb-8 rounded-2xl bg-white p-12 shadow-sm">
                <h2 
                    class="mb-6 border-b pb-2 text-5xl font-bold"
                    :style="{ 
                        color: `color-mix(in srgb, ${teamPrimary} 85%, black)`,
                        borderBottomColor: `color-mix(in srgb, ${teamPrimary} 20%, white)`,
                        borderBottomWidth: '3px'
                    }"
                >
                    Biography
                </h2>
                <div class="text-xl leading-relaxed text-gray-700">
                    {{ player.biography }}
                </div>
            </div>

            <!-- Career Stats -->
            <div v-if="player.career_stats" class="mb-8 rounded-2xl bg-white p-12 shadow-sm">
                <h2 
                    class="mb-6 border-b pb-2 text-5xl font-bold"
                    :style="{ 
                        color: `color-mix(in srgb, ${teamPrimary} 85%, black)`,
                        borderBottomColor: `color-mix(in srgb, ${teamPrimary} 20%, white)`,
                        borderBottomWidth: '3px'
                    }"
                >
                    Career Statistics
                </h2>
                <div class="grid grid-cols-3 gap-8">
                    <div 
                        v-for="(value, key) in player.career_stats" 
                        :key="key"
                        class="rounded-xl border p-6"
                        :style="{ 
                            background: `color-mix(in srgb, ${teamPrimary} 3%, white)`,
                            borderColor: `color-mix(in srgb, ${teamPrimary} 15%, white)`
                        }"
                    >
                        <div class="text-lg text-gray-500">{{ key }}</div>
                        <div class="mt-2 text-4xl font-semibold text-gray-900">{{ value }}</div>
                    </div>
                </div>
            </div>

            <!-- Recent Games -->
            <div class="mb-8 rounded-2xl bg-white p-12 shadow-sm">
                <h2 
                    class="mb-6 border-b pb-2 text-5xl font-bold"
                    :style="{ 
                        color: `color-mix(in srgb, ${teamPrimary} 85%, black)`,
                        borderBottomColor: `color-mix(in srgb, ${teamPrimary} 20%, white)`,
                        borderBottomWidth: '3px'
                    }"
                >
                    Recent Games
                </h2>
                <div v-if="loadingRecent" class="py-12 text-center text-xl text-gray-400">
                    Loading recent games…
                </div>
                <div v-else-if="recentGames.length === 0" class="py-12 text-center text-xl text-gray-400">
                    No recent games found.
                </div>
                <div v-else class="space-y-4">
                    <div 
                        v-for="(game, idx) in recentGames" 
                        :key="idx"
                        class="grid items-center gap-6 rounded-xl border-l-4 p-7"
                        style="grid-template-columns: 160px 1fr auto"
                        :style="{ 
                            background: `color-mix(in srgb, ${teamSecondary} 5%, white)`,
                            borderLeftColor: teamSecondary
                        }"
                    >
                        <div class="font-medium text-gray-500">{{ formatDate(game.date) }}</div>
                        <div class="flex items-center gap-2 text-2xl font-medium text-gray-900">
                            <img v-if="game.opponent?.logo" :src="game.opponent.logo" :alt="game.opponent.abbreviation" class="h-7 w-7 object-contain" />
                            {{ game.homeAway === 'home' ? 'vs' : 'at' }} {{ game.opponent?.abbreviation || game.opponent?.name }}
                            <span v-if="game.score?.result && game.score?.team != null">
                                · {{ game.score.result }} {{ game.score.team }}-{{ game.score.opp }}
                            </span>
                        </div>
                        <div 
                            class="text-right text-xl font-semibold"
                            :style="{ color: teamPrimary }"
                        >
                            {{ formatPlayerLine(game.line) }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Season Averages -->
            <div class="mb-8 rounded-2xl bg-white p-12 shadow-sm">
                <h2 
                    class="mb-6 border-b pb-2 text-5xl font-bold"
                    :style="{ 
                        color: `color-mix(in srgb, ${teamPrimary} 85%, black)`,
                        borderBottomColor: `color-mix(in srgb, ${teamPrimary} 20%, white)`,
                        borderBottomWidth: '3px'
                    }"
                >
                    Averages (Last 2 Seasons)
                </h2>
                <div v-if="loadingAvg" class="py-12 text-center text-xl text-gray-400">
                    Loading averages…
                </div>
                <div v-else class="grid grid-cols-2 gap-8">
                    <div 
                        class="rounded-xl border p-6"
                        :style="{ 
                            background: `color-mix(in srgb, ${teamPrimary} 3%, white)`,
                            borderColor: `color-mix(in srgb, ${teamPrimary} 15%, white)`
                        }"
                    >
                        <h3 class="mb-4 text-3xl font-bold" :style="{ color: teamPrimary }">Current Season</h3>
                        <div v-if="currentAvg" class="space-y-3">
                            <div class="flex justify-between border-b border-gray-200 pb-3">
                                <span class="text-lg text-gray-500">GP</span>
                                <span class="text-lg font-semibold text-gray-900">{{ currentAvg.gp ?? '—' }}</span>
                            </div>
                            <div class="flex justify-between border-b border-gray-200 pb-3">
                                <span class="text-lg text-gray-500">PPG</span>
                                <span class="text-lg font-semibold text-gray-900">{{ currentAvg.ppg ?? '—' }}</span>
                            </div>
                            <div class="flex justify-between border-b border-gray-200 pb-3">
                                <span class="text-lg text-gray-500">RPG</span>
                                <span class="text-lg font-semibold text-gray-900">{{ currentAvg.rpg ?? '—' }}</span>
                            </div>
                            <div class="flex justify-between border-b border-gray-200 pb-3">
                                <span class="text-lg text-gray-500">APG</span>
                                <span class="text-lg font-semibold text-gray-900">{{ currentAvg.apg ?? '—' }}</span>
                            </div>
                            <div class="flex justify-between border-b border-gray-200 pb-3">
                                <span class="text-lg text-gray-500">SPG</span>
                                <span class="text-lg font-semibold text-gray-900">{{ currentAvg.spg ?? '—' }}</span>
                            </div>
                            <div class="flex justify-between border-b border-gray-200 pb-3">
                                <span class="text-lg text-gray-500">BPG</span>
                                <span class="text-lg font-semibold text-gray-900">{{ currentAvg.bpg ?? '—' }}</span>
                            </div>
                            <div class="flex justify-between border-b border-gray-200 pb-3">
                                <span class="text-lg text-gray-500">TPG</span>
                                <span class="text-lg font-semibold text-gray-900">{{ currentAvg.tpg ?? '—' }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-lg text-gray-500">MPG</span>
                                <span class="text-lg font-semibold text-gray-900">{{ currentAvg.mpg ?? '—' }}</span>
                            </div>
                        </div>
                        <div v-else class="py-8 text-center text-gray-400">N/A</div>
                    </div>
                    
                    <div 
                        class="rounded-xl border p-6"
                        :style="{ 
                            background: `color-mix(in srgb, ${teamPrimary} 3%, white)`,
                            borderColor: `color-mix(in srgb, ${teamPrimary} 15%, white)`
                        }"
                    >
                        <h3 class="mb-4 text-3xl font-bold" :style="{ color: teamPrimary }">Previous Season</h3>
                        <div v-if="previousAvg" class="space-y-3">
                            <div class="flex justify-between border-b border-gray-200 pb-3">
                                <span class="text-lg text-gray-500">GP</span>
                                <span class="text-lg font-semibold text-gray-900">{{ previousAvg.gp ?? '—' }}</span>
                            </div>
                            <div class="flex justify-between border-b border-gray-200 pb-3">
                                <span class="text-lg text-gray-500">PPG</span>
                                <span class="text-lg font-semibold text-gray-900">{{ previousAvg.ppg ?? '—' }}</span>
                            </div>
                            <div class="flex justify-between border-b border-gray-200 pb-3">
                                <span class="text-lg text-gray-500">RPG</span>
                                <span class="text-lg font-semibold text-gray-900">{{ previousAvg.rpg ?? '—' }}</span>
                            </div>
                            <div class="flex justify-between border-b border-gray-200 pb-3">
                                <span class="text-lg text-gray-500">APG</span>
                                <span class="text-lg font-semibold text-gray-900">{{ previousAvg.apg ?? '—' }}</span>
                            </div>
                            <div class="flex justify-between border-b border-gray-200 pb-3">
                                <span class="text-lg text-gray-500">SPG</span>
                                <span class="text-lg font-semibold text-gray-900">{{ previousAvg.spg ?? '—' }}</span>
                            </div>
                            <div class="flex justify-between border-b border-gray-200 pb-3">
                                <span class="text-lg text-gray-500">BPG</span>
                                <span class="text-lg font-semibold text-gray-900">{{ previousAvg.bpg ?? '—' }}</span>
                            </div>
                            <div class="flex justify-between border-b border-gray-200 pb-3">
                                <span class="text-lg text-gray-500">TPG</span>
                                <span class="text-lg font-semibold text-gray-900">{{ previousAvg.tpg ?? '—' }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-lg text-gray-500">MPG</span>
                                <span class="text-lg font-semibold text-gray-900">{{ previousAvg.mpg ?? '—' }}</span>
                            </div>
                        </div>
                        <div v-else class="py-8 text-center text-gray-400">N/A</div>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-if="!player.biography && !player.career_stats && recentGames.length === 0" class="mb-8 rounded-2xl bg-white p-12 shadow-sm">
                <div class="py-12 text-center text-xl text-gray-400">
                    No detailed profile data available yet.
                </div>
            </div>
        </div>
    </div>
</template>
