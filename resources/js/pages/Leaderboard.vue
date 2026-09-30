<script setup>
import { computed } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { Crown, Flame, Trophy } from '@lucide/vue';
import Avatar from '@/components/Avatar.vue';
import Countdown from '@/components/Countdown.vue';
import { formatNumber } from '@/lib/format';

const props = defineProps({
    period: { type: String, required: true }, // week | all
    rows: { type: Array, required: true },
    weekEndsAt: { type: String, required: true },
});

const user = computed(() => usePage().props.auth.user);
const me = computed(() => props.rows.find((row) => row.id === user.value.id));

// Podium order on screen: 2nd, 1st, 3rd.
const podium = computed(() => {
    const [first, second, third] = props.rows;
    return props.rows.length >= 3 ? [second, first, third] : [];
});
const rest = computed(() => (podium.value.length ? props.rows.slice(3) : props.rows));

const MEDALS = {
    1: { colour: '#ffc53d', height: 'h-28', size: 76 },
    2: { colour: '#b9c2d6', height: 'h-20', size: 60 },
    3: { colour: '#d98a55', height: 'h-14', size: 60 },
};

// The gap to the person directly ahead: a small, reachable goal.
const chase = computed(() => {
    if (!me.value || me.value.rank === 1) return null;
    const index = props.rows.findIndex((row) => row.id === me.value.id);
    const ahead = props.rows[index - 1];
    return ahead ? { name: ahead.name.split(' ')[0], gap: ahead.xp - me.value.xp + 1 } : null;
});

const setPeriod = (period) =>
    router.get(route('leaderboard'), period === 'week' ? {} : { period }, { preserveState: true, preserveScroll: true, replace: true });
</script>

<template>
    <Head title="Leaderboard" />

    <header class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold sm:text-3xl">Leaderboard</h1>
            <p class="mt-1 text-soft">
                <template v-if="period === 'week'">
                    XP earned since Monday. Resets in <Countdown :to="weekEndsAt" class="text-ink" />
                </template>
                <template v-else>Total XP since everyone started.</template>
            </p>
        </div>

        <div class="inline-flex rounded-xl border border-line bg-surface p-1" role="tablist">
            <button
                v-for="tab in [{ value: 'week', label: 'This week' }, { value: 'all', label: 'All time' }]"
                :key="tab.value"
                type="button"
                role="tab"
                :aria-selected="period === tab.value"
                class="h-9 rounded-lg px-4 text-sm font-semibold transition-colors"
                :class="period === tab.value ? 'bg-raised text-ink shadow-sm' : 'text-faint hover:text-ink'"
                @click="setPeriod(tab.value)"
            >
                {{ tab.label }}
            </button>
        </div>
    </header>

    <p v-if="chase" class="mt-5 flex items-center gap-2.5 rounded-xl border border-flame/30 bg-flame/10 px-4 py-3 text-sm">
        <Trophy :size="17" class="shrink-0 text-flame" />
        <span>
            You are <span class="font-bold">#{{ me.rank }}</span>.
            <span class="num font-bold text-gold">{{ formatNumber(chase.gap) }} XP</span> more and you pass {{ chase.name }}.
        </span>
    </p>
    <p v-else-if="me?.rank === 1" class="mt-5 flex items-center gap-2.5 rounded-xl border border-gold/30 bg-gold/10 px-4 py-3 text-sm">
        <Crown :size="17" class="shrink-0 text-gold" /> <span>You are in first place. Everyone else is chasing you.</span>
    </p>

    <!-- Podium -->
    <section v-if="podium.length" class="mt-8 grid grid-cols-3 items-end gap-3 sm:gap-6" aria-label="Top three">
        <div v-for="row in podium" :key="row.id" class="flex flex-col items-center text-center">
            <div class="relative">
                <Crown v-if="row.rank === 1" :size="26" class="absolute -top-7 left-1/2 -translate-x-1/2 fill-gold/30 text-gold" />
                <span class="block rounded-full p-1" :style="{ boxShadow: `0 0 0 3px ${MEDALS[row.rank].colour}` }">
                    <Avatar :user="row" :size="MEDALS[row.rank].size" />
                </span>
            </div>
            <p class="mt-3 w-full truncate text-sm font-bold sm:text-base">{{ row.id === user.id ? 'You' : row.name }}</p>
            <p class="w-full truncate text-xs text-faint">Lv {{ row.level }} · {{ row.title }}</p>
            <p class="num mt-1 text-lg font-extrabold text-gold">{{ formatNumber(row.xp) }}<span class="ml-1 text-xs font-semibold">XP</span></p>
            <div
                class="mt-3 grid w-full place-items-start justify-center rounded-t-2xl border border-b-0 border-line bg-surface pt-3"
                :class="MEDALS[row.rank].height"
                :style="{ background: `linear-gradient(180deg, color-mix(in srgb, ${MEDALS[row.rank].colour} 22%, var(--surface)), var(--surface))` }"
            >
                <span class="num text-3xl font-extrabold" :style="{ color: MEDALS[row.rank].colour }">{{ row.rank }}</span>
            </div>
        </div>
    </section>

    <!-- Everyone else -->
    <section v-if="rest.length" class="card overflow-hidden" :class="podium.length ? 'rounded-t-none' : 'mt-8'">
        <ol class="divide-y divide-line">
            <li
                v-for="row in rest"
                :key="row.id"
                class="flex items-center gap-3 px-4 py-3 sm:gap-4 sm:px-6"
                :class="row.id === user.id ? 'bg-flame/10' : ''"
            >
                <span class="num w-7 text-center text-base font-bold text-faint">{{ row.rank }}</span>
                <Avatar :user="row" :size="38" />
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-bold">
                        {{ row.name }} <span v-if="row.id === user.id" class="pill ml-1 h-5! bg-flame/15 px-2! text-[0.625rem]! text-flame">You</span>
                    </p>
                    <p class="truncate text-xs text-faint">Level {{ row.level }} · {{ row.title }}</p>
                </div>
                <span v-if="row.streak > 0" class="hidden items-center gap-1 text-sm font-semibold text-flame sm:inline-flex" :title="`${row.streak}-day streak`">
                    <Flame :size="15" :stroke-width="2.5" class="fill-flame/30" /><span class="num">{{ row.streak }}</span>
                </span>
                <span class="num w-20 text-right text-base font-extrabold text-gold">{{ formatNumber(row.xp) }}<span class="ml-1 text-xs font-semibold">XP</span></span>
            </li>
        </ol>
    </section>

    <div v-if="!rows.length" class="card mt-8 flex flex-col items-center gap-3 px-6 py-16 text-center">
        <span class="grid size-14 place-items-center rounded-2xl bg-raised text-gold"><Trophy :size="26" /></span>
        <h2 class="text-lg font-bold">{{ period === 'week' ? 'A fresh week' : 'No scores yet' }}</h2>
        <p class="max-w-sm text-sm text-soft">Nobody has earned XP {{ period === 'week' ? 'this week' : '' }} yet. Answer today’s challenge and the top spot is yours.</p>
        <Link :href="route('today')" class="btn btn-primary mt-2">Play today’s challenge</Link>
    </div>

    <p v-if="!user.show_on_leaderboard" class="mt-5 text-center text-sm text-faint">
        You are hidden from the leaderboard.
        <Link :href="route('profile')" class="font-semibold text-soft underline hover:text-ink">Change this in your profile</Link>
    </p>
</template>
