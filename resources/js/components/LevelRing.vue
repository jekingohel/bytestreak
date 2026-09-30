<script setup>
import { computed } from 'vue';

const props = defineProps({
    level: { type: Number, required: true },
    percent: { type: Number, default: 0 },
    size: { type: Number, default: 44 },
    stroke: { type: Number, default: 4 },
});

const radius = computed(() => (props.size - props.stroke) / 2);
const circumference = computed(() => 2 * Math.PI * radius.value);
const offset = computed(() => circumference.value * (1 - Math.min(100, Math.max(0, props.percent)) / 100));
</script>

<template>
    <span class="relative inline-grid shrink-0 place-items-center" :style="{ width: `${size}px`, height: `${size}px` }">
        <svg :width="size" :height="size" class="-rotate-90" aria-hidden="true">
            <circle :cx="size / 2" :cy="size / 2" :r="radius" fill="none" stroke="var(--line)" :stroke-width="stroke" />
            <circle
                :cx="size / 2"
                :cy="size / 2"
                :r="radius"
                fill="none"
                stroke="var(--gold)"
                stroke-linecap="round"
                :stroke-width="stroke"
                :stroke-dasharray="circumference"
                :stroke-dashoffset="offset"
                class="transition-[stroke-dashoffset] duration-1000 ease-out"
            />
        </svg>
        <span class="num absolute font-bold" :style="{ fontSize: `${size * 0.36}px` }">{{ level }}</span>
    </span>
</template>
