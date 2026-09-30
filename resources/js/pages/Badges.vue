<script setup>
import { computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import BadgeTile from '@/components/BadgeTile.vue';
import ProgressBar from '@/components/ProgressBar.vue';

const props = defineProps({
    badges: { type: Array, required: true },
});

const earned = computed(() =>
    props.badges.filter((b) => b.earned_at).sort((a, b) => new Date(b.earned_at) - new Date(a.earned_at)),
);
// Closest to unlocking first, so the next goal is always at the top.
const locked = computed(() =>
    props.badges
        .filter((b) => !b.earned_at)
        .sort((a, b) => a.progress.remaining - b.progress.remaining || b.progress.percent - a.progress.percent),
);
</script>

<template>
    <Head title="Badges" />

    <header class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold sm:text-3xl">Badges</h1>
            <p class="mt-1 text-soft">Small trophies for showing up, getting it right and exploring every topic.</p>
        </div>
        <div class="w-full sm:w-56">
            <p class="mb-1.5 flex justify-between text-xs font-semibold text-faint">
                <span>Collected</span>
                <span class="num text-soft">{{ earned.length }} / {{ badges.length }}</span>
            </p>
            <ProgressBar :percent="badges.length ? (earned.length / badges.length) * 100 : 0" :height="6" colour="var(--ember)" />
        </div>
    </header>

    <section v-if="earned.length" class="mt-8">
        <h2 class="eyebrow">Unlocked</h2>
        <div class="mt-3 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
            <BadgeTile v-for="badge in earned" :key="badge.id" :badge="badge" earned />
        </div>
    </section>

    <section v-if="locked.length" class="mt-10">
        <h2 class="eyebrow">{{ earned.length ? 'Still to unlock' : 'Your first badge is one answer away' }}</h2>
        <div class="mt-3 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
            <BadgeTile v-for="badge in locked" :key="badge.id" :badge="badge" />
        </div>
    </section>
</template>
