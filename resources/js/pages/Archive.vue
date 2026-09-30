<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { Check, Inbox, Play, X, Zap } from '@lucide/vue';
import CategoryChip from '@/components/CategoryChip.vue';
import DifficultyMeter from '@/components/DifficultyMeter.vue';
import Pagination from '@/components/Pagination.vue';
import ProgressBar from '@/components/ProgressBar.vue';
import { dayLabel } from '@/lib/format';

const props = defineProps({
    challenges: { type: Object, required: true }, // paginator
    categories: { type: Array, required: true },
    filters: { type: Object, required: true },
    totals: { type: Object, required: true },
});

const STATES = [
    { value: 'all', label: 'All' },
    { value: 'todo', label: 'To play' },
    { value: 'correct', label: 'Correct' },
    { value: 'wrong', label: 'Missed' },
];

const apply = (changes) => {
    const next = { ...props.filters, ...changes };
    router.get(
        route('archive'),
        { state: next.state === 'all' ? undefined : next.state, category: next.category ?? undefined },
        { preserveState: true, preserveScroll: true, replace: true },
    );
};
</script>

<template>
    <Head title="Archive" />

    <header class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold sm:text-3xl">Archive</h1>
            <p class="mt-1 text-soft">Every challenge that has gone live. Missed one? Play it for half XP.</p>
        </div>
        <div class="w-full sm:w-56">
            <p class="mb-1.5 flex justify-between text-xs font-semibold text-faint">
                <span>Played</span>
                <span class="num text-soft">{{ totals.played }} / {{ totals.available }}</span>
            </p>
            <ProgressBar :percent="totals.available ? (totals.played / totals.available) * 100 : 0" :height="6" colour="var(--mint)" />
        </div>
    </header>

    <!-- Filters -->
    <div class="mt-6 flex flex-col gap-3">
        <div class="inline-flex w-fit rounded-xl border border-line bg-surface p-1" role="tablist" aria-label="Filter by result">
            <button
                v-for="state in STATES"
                :key="state.value"
                type="button"
                role="tab"
                :aria-selected="filters.state === state.value"
                class="h-9 rounded-lg px-3.5 text-sm font-semibold transition-colors"
                :class="filters.state === state.value ? 'bg-raised text-ink shadow-sm' : 'text-faint hover:text-ink'"
                @click="apply({ state: state.value })"
            >
                {{ state.label }}
            </button>
        </div>

        <div class="-mx-4 flex gap-2 overflow-x-auto px-4 pb-1 sm:mx-0 sm:flex-wrap sm:px-0">
            <button
                type="button"
                class="pill h-8! shrink-0 border px-3! text-[0.8125rem]! transition-colors"
                :class="!filters.category ? 'border-flame/40 bg-flame/10 text-flame' : 'border-line bg-surface text-soft hover:text-ink'"
                @click="apply({ category: null })"
            >
                All topics
            </button>
            <button
                v-for="category in categories"
                :key="category.id"
                type="button"
                class="shrink-0 rounded-full transition-opacity"
                :class="filters.category && filters.category !== category.slug ? 'opacity-45 hover:opacity-100' : ''"
                :aria-pressed="filters.category === category.slug"
                @click="apply({ category: filters.category === category.slug ? null : category.slug })"
            >
                <CategoryChip :category="category" class="h-8! px-3! text-[0.8125rem]!" />
            </button>
        </div>
    </div>

    <!-- List -->
    <div v-if="challenges.data.length" class="mt-6 grid gap-4 md:grid-cols-2">
        <Link
            v-for="challenge in challenges.data"
            :key="challenge.id"
            :href="route('challenges.show', challenge.id)"
            class="card group flex flex-col p-5 transition-all duration-150 hover:-translate-y-0.5 hover:border-flame/50"
        >
            <div class="flex items-center gap-2.5">
                <CategoryChip :category="challenge.category" size="sm" />
                <DifficultyMeter :level="challenge.difficulty" :label="false" />
                <span class="ml-auto text-xs font-semibold" :class="challenge.is_today ? 'text-flame' : 'text-faint'">
                    {{ dayLabel(challenge.publish_date) }}
                </span>
            </div>

            <h2 class="mt-3.5 font-display text-lg leading-snug font-bold group-hover:text-flame">{{ challenge.title }}</h2>
            <p class="mt-1 text-xs text-faint">{{ challenge.type.label }}</p>

            <div class="mt-4 flex items-center justify-between border-t border-line pt-3.5 text-sm font-semibold">
                <template v-if="challenge.attempt">
                    <span class="inline-flex items-center gap-1.5" :class="challenge.attempt.is_correct ? 'text-mint' : 'text-rose'">
                        <Check v-if="challenge.attempt.is_correct" :size="16" :stroke-width="3" />
                        <X v-else :size="16" :stroke-width="3" />
                        {{ challenge.attempt.is_correct ? 'Correct' : 'Missed' }}
                    </span>
                    <span class="num text-gold">+{{ challenge.attempt.xp_awarded }} XP</span>
                </template>
                <template v-else>
                    <span class="inline-flex items-center gap-1.5 text-flame">
                        <Play :size="15" :stroke-width="2.5" class="fill-flame/30" /> {{ challenge.is_today ? 'Play today’s challenge' : 'Play' }}
                    </span>
                    <span class="inline-flex items-center gap-1 text-gold">
                        <Zap :size="14" :stroke-width="2.5" class="fill-gold/30" /><span class="num">+{{ challenge.reward }} XP</span>
                    </span>
                </template>
            </div>
        </Link>
    </div>

    <div v-else class="card mt-6 flex flex-col items-center gap-3 px-6 py-16 text-center">
        <span class="grid size-14 place-items-center rounded-2xl bg-raised text-faint"><Inbox :size="26" /></span>
        <h2 class="text-lg font-bold">
            {{ filters.state === 'todo' ? 'You are all caught up' : 'Nothing here yet' }}
        </h2>
        <p class="max-w-sm text-sm text-soft">
            {{ filters.state === 'todo' ? 'You have played every challenge that matches. A new one arrives each working day.' : 'No challenges match these filters.' }}
        </p>
        <button v-if="filters.state !== 'all' || filters.category" type="button" class="btn btn-ghost btn-sm mt-2" @click="apply({ state: 'all', category: null })">
            Clear filters
        </button>
    </div>

    <Pagination class="mt-6" :paginator="challenges" />
</template>
