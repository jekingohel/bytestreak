<script setup>
/**
 * The ByteStreak mark: a streak flame built from bytes (pixels).
 */
defineProps({
    size: { type: Number, default: 28 },
    wordmark: { type: Boolean, default: true },
});

// 5 × 6 pixel grid. o = outer flame, c = hot core.
const rows = ['..o..', '.oo..', '.ooo.', 'oocoo', 'ooCoo', '.ooo.'];
const colours = ['#ffc53d', '#ffab2e', '#ff8a2a', '#ff6b2c', '#ff5230', '#ff3d33'];

const pixels = rows.flatMap((row, y) =>
    [...row].flatMap((cell, x) =>
        cell === '.' ? [] : [{ x: x * 4.8, y: y * 4.8, fill: cell === 'o' ? colours[y] : '#ffe9a8' }],
    ),
);
</script>

<template>
    <span class="inline-flex items-center gap-2.5">
        <svg :width="size * 0.82" :height="size" viewBox="0 0 23.2 28" aria-hidden="true" class="shrink-0">
            <rect v-for="(p, i) in pixels" :key="i" :x="p.x" :y="p.y" width="4" height="4" rx="1" :fill="p.fill" />
        </svg>
        <span v-if="wordmark" class="font-display text-[1.35em] font-extrabold leading-none tracking-tight">
            byte<span class="flame-text">streak</span>
        </span>
        <span v-else class="sr-only">ByteStreak</span>
    </span>
</template>
