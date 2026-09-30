<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { CalendarClock, Plus, Target, TriangleAlert, Users, Zap } from '@lucide/vue';
import AdminNav from '@/components/AdminNav.vue';
import CategoryChip from '@/components/CategoryChip.vue';
import ProgressBar from '@/components/ProgressBar.vue';
import { dayLabel, parseDay, shortDay } from '@/lib/format';

const props = defineProps({
    stats: { type: Object, required: true },
    today: { type: Object, default: null },
    schedule: { type: Array, required: true },
    participation: { type: Array, required: true },
    hardest: { type: Array, required: true },
    categories: { type: Array, required: true },
});

const peak = computed(() => Math.max(1, ...props.participation.map((day) => day.count)));
const gaps = computed(() => props.schedule.filter((day) => day.source === 'gap').length);
const triedCategories = computed(() => props.categories.filter((c) => c.attempts > 0).sort((a, b) => a.accuracy - b.accuracy));

const SOURCES = {
    planned: { label: 'Scheduled', class: 'bg-mint/12 text-mint' },
    queue: { label: 'From queue', class: 'bg-sky/12 text-sky' },
    gap: { label: 'Nothing to publish', class: 'bg-rose/12 text-rose' },
    rest: { label: 'Rest day', class: 'bg-raised text-faint' },
};

const accuracyColour = (value) => (value >= 75 ? 'var(--mint)' : value >= 50 ? 'var(--gold)' : 'var(--rose)');
const weekday = (day) => new Intl.DateTimeFormat('en', { weekday: 'narrow' }).format(parseDay(day));
</script>

<template>
    <Head title="Admin" />
    <AdminNav />

    <header class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold sm:text-3xl">Overview</h1>
            <p class="mt-1 text-soft">
                <template v-if="today">Today: “{{ today.title }}” ({{ today.category.name }})</template>
                <template v-else>No challenge is live today.</template>
            </p>
        </div>
        <Link :href="route('admin.challenges.create')" class="btn btn-primary"><Plus :size="18" :stroke-width="2.5" /> New challenge</Link>
    </header>

    <p v-if="gaps > 0" class="mt-5 flex items-center gap-2.5 rounded-xl border border-rose/30 bg-rose/10 px-4 py-3 text-sm text-rose">
        <TriangleAlert :size="17" class="shrink-0" />
        <span><span class="font-bold">{{ gaps }} working {{ gaps === 1 ? 'day' : 'days' }}</span> in the next ten have nothing to publish. Add challenges to the queue.</span>
    </p>

    <section class="mt-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
        <div class="card p-5">
            <p class="flex items-center gap-1.5 text-xs font-semibold text-faint"><Users :size="14" /> Answered today</p>
            <p class="num mt-2 text-3xl font-extrabold">{{ stats.answered_today }}<span class="text-lg text-faint"> / {{ stats.team_size }}</span></p>
            <ProgressBar class="mt-3" :percent="stats.participation ?? 0" :height="5" colour="var(--flame)" />
        </div>
        <div class="card p-5">
            <p class="flex items-center gap-1.5 text-xs font-semibold text-faint"><Zap :size="14" /> Active this week</p>
            <p class="num mt-2 text-3xl font-extrabold">{{ stats.active_this_week }}<span class="text-lg text-faint"> / {{ stats.team_size }}</span></p>
            <ProgressBar class="mt-3" :percent="stats.team_size ? (stats.active_this_week / stats.team_size) * 100 : 0" :height="5" colour="var(--gold)" />
        </div>
        <div class="card p-5">
            <p class="flex items-center gap-1.5 text-xs font-semibold text-faint"><Target :size="14" /> Team accuracy</p>
            <p class="num mt-2 text-3xl font-extrabold">{{ stats.accuracy ?? '—' }}<span v-if="stats.accuracy !== null" class="text-lg text-faint">%</span></p>
            <p class="mt-3 text-xs text-faint">{{ stats.attempts }} answers in total</p>
        </div>
        <div class="card p-5">
            <p class="flex items-center gap-1.5 text-xs font-semibold text-faint"><CalendarClock :size="14" /> In the queue</p>
            <p class="num mt-2 text-3xl font-extrabold" :class="stats.queued < 5 ? 'text-rose' : ''">{{ stats.queued }}</p>
            <p class="mt-3 text-xs text-faint">{{ stats.drafts }} {{ stats.drafts === 1 ? 'draft' : 'drafts' }} not yet queued</p>
        </div>
    </section>

    <div class="mt-6 grid gap-6 lg:grid-cols-[minmax(0,1.15fr)_minmax(0,1fr)]">
        <!-- Schedule -->
        <section class="card p-6">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-bold">Next ten days</h2>
                <Link :href="route('admin.challenges.index', { status: 'scheduled' })" class="text-sm font-semibold text-soft hover:text-ink">Manage</Link>
            </div>
            <ul class="mt-3 divide-y divide-line">
                <li v-for="day in schedule" :key="day.date" class="flex items-center gap-3 py-3" :class="day.source === 'rest' ? 'opacity-55' : ''">
                    <div class="w-20 shrink-0">
                        <p class="text-sm font-bold">{{ dayLabel(day.date) === 'Today' || dayLabel(day.date) === 'Tomorrow' ? dayLabel(day.date) : shortDay(day.date) }}</p>
                        <p class="text-xs text-faint">{{ new Intl.DateTimeFormat('en', { weekday: 'long' }).format(parseDay(day.date)) }}</p>
                    </div>
                    <div class="min-w-0 flex-1">
                        <template v-if="day.challenge">
                            <Link :href="route('admin.challenges.edit', day.challenge.id)" class="block truncate text-sm font-semibold hover:text-flame">{{ day.challenge.title }}</Link>
                            <p class="text-xs text-faint">{{ day.challenge.category.emoji }} {{ day.challenge.category.name }} · {{ day.challenge.type.label }}</p>
                        </template>
                        <p v-else class="text-sm text-faint">{{ day.source === 'gap' ? 'Queue is empty' : 'No challenge' }}</p>
                    </div>
                    <span class="pill shrink-0" :class="SOURCES[day.source].class">{{ SOURCES[day.source].label }}</span>
                </li>
            </ul>
        </section>

        <div class="space-y-6">
            <!-- Participation -->
            <section class="card p-6">
                <h2 class="text-lg font-bold">Daily answers</h2>
                <p class="text-sm text-faint">Last 14 days</p>
                <div class="mt-5 flex h-28 items-end gap-1.5" role="img" aria-label="Daily answers over the last 14 days">
                    <div v-for="day in participation" :key="day.date" class="flex h-full flex-1 flex-col items-center justify-end gap-1.5" :title="`${shortDay(day.date)}: ${day.count} answers`">
                        <span class="num text-[0.625rem] font-semibold text-faint">{{ day.count || '' }}</span>
                        <span
                            class="w-full rounded-md"
                            :class="day.count ? 'bg-linear-to-t from-flame to-ember' : 'bg-raised'"
                            :style="{ height: `${day.count ? Math.max(8, (day.count / peak) * 72) : 4}px` }"
                        />
                        <span class="text-[0.625rem] text-faint">{{ weekday(day.date) }}</span>
                    </div>
                </div>
            </section>

            <!-- Topics the team finds difficult -->
            <section class="card p-6">
                <h2 class="text-lg font-bold">Accuracy by topic</h2>
                <p class="text-sm text-faint">Weakest first</p>
                <div v-if="triedCategories.length" class="mt-4 space-y-3.5">
                    <div v-for="category in triedCategories" :key="category.id">
                        <div class="mb-1.5 flex items-center justify-between text-sm">
                            <span class="font-semibold">{{ category.emoji }} {{ category.name }}</span>
                            <span class="text-faint"><span class="num font-semibold text-soft">{{ category.accuracy }}%</span> · {{ category.attempts }} answers</span>
                        </div>
                        <ProgressBar :percent="category.accuracy" :colour="accuracyColour(category.accuracy)" :height="6" />
                    </div>
                </div>
                <p v-else class="mt-4 text-sm text-soft">No answers yet.</p>
            </section>
        </div>
    </div>

    <!-- Hardest -->
    <section v-if="hardest.length" class="card mt-6 overflow-hidden">
        <div class="px-6 pt-6">
            <h2 class="text-lg font-bold">Hardest challenges</h2>
            <p class="text-sm text-faint">Lowest share of correct answers (3 answers or more)</p>
        </div>
        <div class="mt-3 overflow-x-auto">
            <table class="table">
                <thead>
                    <tr><th>Challenge</th><th>Topic</th><th class="text-right!">Answers</th><th class="text-right!">Correct</th></tr>
                </thead>
                <tbody>
                    <tr v-for="challenge in hardest" :key="challenge.id">
                        <td><Link :href="route('admin.challenges.show', challenge.id)" class="font-semibold hover:text-flame">{{ challenge.title }}</Link></td>
                        <td><CategoryChip :category="challenge.category" size="sm" /></td>
                        <td class="num text-right">{{ challenge.attempts }}</td>
                        <td class="num text-right font-bold" :style="{ color: accuracyColour(challenge.accuracy) }">{{ challenge.accuracy }}%</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>
</template>
