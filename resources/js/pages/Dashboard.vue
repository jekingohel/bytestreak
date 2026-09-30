<script setup>
import { computed } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ArrowRight, Award, Check, ChevronRight, Coffee, Flame, Library, Snowflake, Target, Trophy, Users, X, Zap } from '@lucide/vue';
import Avatar from '@/components/Avatar.vue';
import CategoryChip from '@/components/CategoryChip.vue';
import Countdown from '@/components/Countdown.vue';
import DifficultyMeter from '@/components/DifficultyMeter.vue';
import LevelRing from '@/components/LevelRing.vue';
import ProgressBar from '@/components/ProgressBar.vue';
import WeekDots from '@/components/WeekDots.vue';
import { dayLabel, formatNumber, greeting, plural } from '@/lib/format';

const props = defineProps({
    today: { type: Object, default: null },
    nextChallengeAt: { type: String, required: true },
    summary: { type: Object, required: true },
    week: { type: Array, required: true },
    mastery: { type: Array, required: true },
    nextBadges: { type: Array, required: true },
    leaderboard: { type: Object, required: true },
    unplayed: { type: Number, default: 0 },
    recent: { type: Array, required: true },
});

const user = computed(() => usePage().props.auth.user);
const firstName = computed(() => user.value.name.split(' ')[0]);
const waiting = computed(() => props.today && !props.today.attempt);

const headline = computed(() => {
    if (!props.today) return 'No challenge today. Enjoy the break, or warm up in the archive.';
    if (props.today.attempt) return 'You are done for today. Come back tomorrow for the next one.';
    if (user.value.streak > 0) return `Answer today to make it a ${user.value.streak + 1}-day streak.`;
    return 'Today’s challenge is live. Answer it to start a streak.';
});

const played = computed(() => props.mastery.filter((c) => c.attempts > 0).sort((a, b) => b.attempts - a.attempts));
const untouched = computed(() => props.mastery.filter((c) => c.attempts === 0));
const meInTop = computed(() => props.leaderboard.top.some((row) => row.id === user.value.id));

const HOW_IT_WORKS = [
    { icon: Zap, colour: 'text-gold', title: 'Answer once a day', text: 'One short question each working day. Right answers earn XP, and harder ones earn more.' },
    { icon: Flame, colour: 'text-flame', title: 'Build your streak', text: 'Every day you answer adds to it, right or wrong. Longer streaks add bonus XP.' },
    { icon: Award, colour: 'text-ember', title: 'Collect badges', text: 'The first one unlocks with your first answer, and there are plenty more to find.' },
];

const accuracyColour = (value) => (value >= 75 ? 'var(--mint)' : value >= 50 ? 'var(--gold)' : 'var(--rose)');
</script>

<template>
    <Head title="Home" />

    <header class="mb-6">
        <h1 class="text-2xl font-extrabold sm:text-3xl">{{ greeting() }}, {{ firstName }}</h1>
        <p class="mt-1 text-soft">{{ headline }}</p>
    </header>

    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_340px]">
        <div class="space-y-6">
            <!-- Today's challenge -->
            <section
                v-if="today"
                class="card relative overflow-hidden p-6 sm:p-8"
                :style="{ '--c': today.category.color }"
            >
                <div class="today-glow pointer-events-none absolute inset-0" aria-hidden="true" />

                <div class="relative">
                    <div class="flex flex-wrap items-center gap-2.5">
                        <span class="eyebrow mr-1 text-flame!">Today’s challenge</span>
                        <CategoryChip :category="today.category" />
                        <span class="pill border border-line bg-raised text-soft">{{ today.type.label }}</span>
                        <DifficultyMeter :level="today.difficulty" />
                    </div>

                    <template v-if="waiting">
                        <h2 class="mt-5 max-w-xl text-3xl leading-tight font-extrabold sm:text-4xl">{{ today.title }}</h2>

                        <div class="mt-5 flex flex-wrap items-center gap-x-5 gap-y-2 text-sm text-soft">
                            <span class="inline-flex items-center gap-1.5 font-semibold text-gold">
                                <Zap :size="16" :stroke-width="2.5" class="fill-gold/30" /> +{{ today.xp }} XP
                                <span v-if="user.streak > 0" class="font-normal text-faint">+ streak bonus</span>
                            </span>
                            <span v-if="today.players > 0" class="inline-flex items-center gap-1.5">
                                <Users :size="16" /> {{ plural(today.players, 'teammate') }} already answered
                            </span>
                            <span v-else class="inline-flex items-center gap-1.5"><Users :size="16" /> Be the first to answer today</span>
                        </div>

                        <div class="mt-7 flex flex-wrap items-center gap-4">
                            <Link :href="route('challenges.show', today.id)" class="btn btn-primary btn-lg">
                                Play now <ArrowRight :size="19" :stroke-width="2.5" />
                            </Link>
                            <p class="text-sm text-faint">
                                Closes in <Countdown :to="nextChallengeAt" class="text-soft" @done="router.reload()" />
                            </p>
                        </div>
                    </template>

                    <template v-else>
                        <div class="mt-5 flex items-start gap-4">
                            <span
                                class="grid size-14 shrink-0 place-items-center rounded-2xl"
                                :class="today.attempt.is_correct ? 'bg-mint/15 text-mint' : 'bg-rose/15 text-rose'"
                            >
                                <Check v-if="today.attempt.is_correct" :size="28" :stroke-width="3" />
                                <X v-else :size="28" :stroke-width="3" />
                            </span>
                            <div>
                                <h2 class="text-2xl font-extrabold sm:text-3xl">
                                    {{ today.attempt.is_correct ? 'Nailed it.' : 'Not this time.' }}
                                </h2>
                                <p class="mt-1 text-soft">
                                    “{{ today.title }}” ·
                                    <span class="font-semibold text-gold">+{{ today.attempt.xp_awarded }} XP</span>
                                </p>
                            </div>
                        </div>

                        <div class="mt-7 flex flex-wrap items-center gap-3">
                            <Link :href="route('challenges.show', today.id)" class="btn btn-ghost">Review the answer</Link>
                            <Link v-if="unplayed > 0" :href="route('archive', { state: 'todo' })" class="btn btn-ghost">
                                <Library :size="17" /> {{ unplayed }} more in the archive
                            </Link>
                            <p class="ml-auto text-sm text-faint">
                                Next challenge in <Countdown :to="nextChallengeAt" class="text-soft" @done="router.reload()" />
                            </p>
                        </div>
                    </template>
                </div>
            </section>

            <section v-else class="card flex flex-col items-start gap-4 p-6 sm:flex-row sm:items-center sm:p-8">
                <span class="grid size-14 shrink-0 place-items-center rounded-2xl bg-raised text-soft"><Coffee :size="26" /></span>
                <div class="flex-1">
                    <h2 class="text-2xl font-extrabold">Rest day</h2>
                    <p class="mt-1 text-soft">Nothing new today, and your streak is safe. Days without a challenge never break it.</p>
                </div>
                <Link v-if="unplayed > 0" :href="route('archive', { state: 'todo' })" class="btn btn-ghost">
                    <Library :size="17" /> Practise ({{ unplayed }})
                </Link>
            </section>

            <!-- Numbers -->
            <section class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                <div class="card p-4">
                    <p class="flex items-center gap-1.5 text-xs font-semibold text-faint"><Target :size="14" /> Accuracy</p>
                    <p class="num mt-2 text-3xl font-extrabold">{{ summary.accuracy ?? '—' }}<span v-if="summary.accuracy !== null" class="text-lg text-faint">%</span></p>
                </div>
                <div class="card p-4">
                    <p class="flex items-center gap-1.5 text-xs font-semibold text-faint"><Check :size="14" /> Completed</p>
                    <p class="num mt-2 text-3xl font-extrabold">{{ formatNumber(summary.completed) }}</p>
                </div>
                <Link :href="route('badges')" class="card p-4 transition-colors hover:border-ember/40">
                    <p class="flex items-center gap-1.5 text-xs font-semibold text-faint"><Award :size="14" /> Badges</p>
                    <p class="num mt-2 text-3xl font-extrabold">{{ summary.badges }}</p>
                </Link>
                <Link :href="route('leaderboard')" class="card p-4 transition-colors hover:border-ember/40">
                    <p class="flex items-center gap-1.5 text-xs font-semibold text-faint"><Trophy :size="14" /> This week</p>
                    <p class="num mt-2 text-3xl font-extrabold">
                        <template v-if="leaderboard.me">#{{ leaderboard.me.rank }}<span class="text-lg text-faint"> / {{ leaderboard.size }}</span></template>
                        <template v-else>—</template>
                    </p>
                </Link>
            </section>

            <!-- First visit: explain the loop instead of showing empty stats -->
            <section v-if="summary.completed === 0" class="card p-6">
                <h2 class="text-lg font-bold">How ByteStreak works</h2>
                <ol class="mt-5 grid gap-5 sm:grid-cols-3">
                    <li v-for="step in HOW_IT_WORKS" :key="step.title">
                        <span class="grid size-10 place-items-center rounded-xl bg-raised ring-1 ring-line" :class="step.colour">
                            <component :is="step.icon" :size="19" :stroke-width="2.25" />
                        </span>
                        <p class="mt-3 text-sm font-bold">{{ step.title }}</p>
                        <p class="mt-1 text-sm leading-relaxed text-soft">{{ step.text }}</p>
                    </li>
                </ol>
            </section>

            <!-- Category mastery -->
            <section v-else class="card p-6">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-bold">Your strengths</h2>
                    <Link :href="route('archive')" class="inline-flex items-center gap-1 text-sm font-semibold text-soft hover:text-ink">
                        Archive <ChevronRight :size="16" />
                    </Link>
                </div>

                <div v-if="played.length" class="mt-5 grid gap-x-8 gap-y-4 sm:grid-cols-2">
                    <div v-for="category in played" :key="category.id">
                        <div class="mb-1.5 flex items-center justify-between text-sm">
                            <span class="font-semibold"><span aria-hidden="true">{{ category.emoji }}</span> {{ category.name }}</span>
                            <span class="text-faint">
                                <span class="num font-semibold text-soft">{{ category.accuracy }}%</span> · {{ category.correct }}/{{ category.attempts }}
                            </span>
                        </div>
                        <ProgressBar :percent="category.accuracy" :colour="accuracyColour(category.accuracy)" :height="6" />
                    </div>
                </div>
                <p v-else class="mt-4 text-sm leading-relaxed text-soft">
                    Answer a few challenges and this fills in with how you do in each topic, so you can see what to brush up on.
                </p>

                <div v-if="untouched.length && played.length" class="mt-5 flex flex-wrap items-center gap-2 border-t border-line pt-4">
                    <span class="text-xs font-semibold text-faint">Not tried yet</span>
                    <CategoryChip v-for="category in untouched" :key="category.id" :category="category" size="sm" class="opacity-70" />
                </div>
            </section>

            <!-- Recent -->
            <section v-if="recent.length" class="card p-6">
                <h2 class="text-lg font-bold">Recently played</h2>
                <ul class="mt-3 divide-y divide-line">
                    <li v-for="item in recent" :key="item.id">
                        <Link :href="route('challenges.show', item.id)" class="group flex items-center gap-3 py-3">
                            <span class="grid size-8 shrink-0 place-items-center rounded-full" :class="item.is_correct ? 'bg-mint/15 text-mint' : 'bg-rose/15 text-rose'">
                                <Check v-if="item.is_correct" :size="16" :stroke-width="3" />
                                <X v-else :size="16" :stroke-width="3" />
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-sm font-semibold group-hover:text-flame">{{ item.title }}</span>
                                <span class="text-xs text-faint">{{ item.category.emoji }} {{ item.category.name }} · {{ dayLabel(item.publish_date) }}</span>
                            </span>
                            <span class="num text-sm font-semibold text-gold">+{{ item.xp_awarded }}</span>
                        </Link>
                    </li>
                </ul>
            </section>
        </div>

        <!-- Right rail -->
        <aside class="space-y-6">
            <!-- Streak -->
            <section class="card p-6">
                <div class="flex items-center gap-4">
                    <span class="grid size-16 shrink-0 place-items-center rounded-3xl" :class="user.streak > 0 ? 'bg-flame/15 text-flame' : 'bg-raised text-faint'">
                        <Flame :size="34" :stroke-width="2.25" :class="user.streak > 0 ? 'animate-flicker fill-flame/30' : ''" />
                    </span>
                    <div>
                        <p class="num text-5xl leading-none font-extrabold">{{ user.streak }}</p>
                        <p class="mt-1 text-sm font-semibold text-soft">day streak</p>
                    </div>
                    <div v-if="user.freezes > 0" class="pill ml-auto self-start border border-sky/30 bg-sky/10 text-sky" :title="`${user.freezes} streak freeze(s): each one covers a missed day`">
                        <Snowflake :size="13" :stroke-width="2.5" /> {{ user.freezes }}
                    </div>
                </div>

                <WeekDots :days="week" class="mt-6" />

                <p class="mt-5 border-t border-line pt-4 text-xs leading-relaxed text-faint">
                    <template v-if="waiting && user.streak > 0">
                        <span class="font-semibold text-flame">Your streak is waiting.</span> Answer today’s challenge to keep it going.
                    </template>
                    <template v-else-if="user.longest_streak > user.streak">
                        Your best is <span class="font-semibold text-soft">{{ plural(user.longest_streak, 'day') }}</span>. Every 7 days in a row earns a streak freeze.
                    </template>
                    <template v-else-if="user.streak > 0">This is your longest streak so far. Every 7 days in a row earns a streak freeze.</template>
                    <template v-else>Right or wrong, answering counts. Every 7 days in a row earns a streak freeze.</template>
                </p>
            </section>

            <!-- Level -->
            <section class="card p-6">
                <div class="flex items-center gap-4">
                    <LevelRing :level="user.level.level" :percent="user.level.percent" :size="64" :stroke="6" />
                    <div class="min-w-0">
                        <p class="eyebrow">Level {{ user.level.level }}</p>
                        <p class="truncate text-xl font-extrabold">{{ user.level.title }}</p>
                    </div>
                </div>
                <ProgressBar class="mt-5" :percent="user.level.percent" colour="linear-gradient(90deg, var(--ember), var(--gold))" />
                <p class="mt-2 flex justify-between text-xs text-faint">
                    <span><span class="num font-semibold text-gold">{{ formatNumber(user.xp) }}</span> XP</span>
                    <span><span class="num font-semibold text-soft">{{ formatNumber(user.level.needed) }}</span> to level {{ user.level.level + 1 }}</span>
                </p>
            </section>

            <!-- Next badges -->
            <section v-if="nextBadges.length" class="card p-6">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-bold">Almost there</h2>
                    <Link :href="route('badges')" class="inline-flex items-center gap-1 text-sm font-semibold text-soft hover:text-ink">
                        All badges <ChevronRight :size="16" />
                    </Link>
                </div>
                <ul class="mt-4 space-y-4">
                    <li v-for="badge in nextBadges" :key="badge.id" class="flex items-center gap-3">
                        <span class="grid size-11 shrink-0 place-items-center rounded-2xl bg-raised text-xl ring-1 ring-line" aria-hidden="true">{{ badge.emoji }}</span>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-baseline justify-between gap-2">
                                <p class="truncate text-sm font-bold">{{ badge.name }}</p>
                                <p class="num shrink-0 text-xs font-semibold text-soft">{{ badge.progress.current }}/{{ badge.progress.target }}</p>
                            </div>
                            <ProgressBar class="mt-1.5" :percent="badge.progress.percent" :height="5" colour="var(--ember)" />
                            <p class="mt-1 truncate text-xs text-faint">{{ badge.description }}</p>
                        </div>
                    </li>
                </ul>
            </section>

            <!-- Weekly leaderboard -->
            <section class="card p-6">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-bold">This week</h2>
                    <Link :href="route('leaderboard')" class="inline-flex items-center gap-1 text-sm font-semibold text-soft hover:text-ink">
                        Leaderboard <ChevronRight :size="16" />
                    </Link>
                </div>

                <ol v-if="leaderboard.top.length" class="mt-3 space-y-1">
                    <li
                        v-for="row in leaderboard.top"
                        :key="row.id"
                        class="flex items-center gap-3 rounded-xl px-2 py-2"
                        :class="row.id === user.id ? 'bg-flame/10' : ''"
                    >
                        <span class="num w-5 text-center text-sm font-bold" :class="row.rank === 1 ? 'text-gold' : 'text-faint'">{{ row.rank }}</span>
                        <Avatar :user="row" :size="30" />
                        <span class="min-w-0 flex-1 truncate text-sm font-semibold">{{ row.id === user.id ? 'You' : row.name }}</span>
                        <span class="num text-sm font-bold text-gold">{{ formatNumber(row.xp) }}</span>
                    </li>
                    <li v-if="leaderboard.me && !meInTop" class="mt-1 flex items-center gap-3 rounded-xl border-t border-dashed border-line bg-flame/10 px-2 py-2">
                        <span class="num w-5 text-center text-sm font-bold text-faint">{{ leaderboard.me.rank }}</span>
                        <Avatar :user="leaderboard.me" :size="30" />
                        <span class="min-w-0 flex-1 truncate text-sm font-semibold">You</span>
                        <span class="num text-sm font-bold text-gold">{{ formatNumber(leaderboard.me.xp) }}</span>
                    </li>
                </ol>
                <p v-else class="mt-3 text-sm leading-relaxed text-soft">Nobody has scored yet this week. The first answer takes the top spot.</p>

                <p v-if="leaderboard.top.length && !leaderboard.me" class="mt-3 text-xs leading-relaxed text-faint">
                    {{ user.show_on_leaderboard ? 'Answer a challenge this week to get on the board.' : 'You are hidden from the leaderboard. Change it in your profile.' }}
                </p>
            </section>
        </aside>
    </div>
</template>

<style scoped>
.today-glow {
    background:
        radial-gradient(520px 260px at 0% 0%, color-mix(in srgb, var(--c) 22%, transparent), transparent 70%),
        radial-gradient(420px 220px at 100% 120%, color-mix(in srgb, var(--flame) 16%, transparent), transparent 70%);
}
</style>
