<script setup>
import { computed } from 'vue';

const props = defineProps({
    level: { type: String, required: true }, // easy | medium | hard
    label: { type: Boolean, default: true },
});

const LEVELS = {
    easy: { bars: 1, colour: 'var(--mint)', text: 'Easy' },
    medium: { bars: 2, colour: 'var(--gold)', text: 'Medium' },
    hard: { bars: 3, colour: 'var(--rose)', text: 'Hard' },
};

const meta = computed(() => LEVELS[props.level] ?? LEVELS.easy);
</script>

<template>
    <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-soft">
        <span class="flex items-end gap-[3px]" aria-hidden="true">
            <span
                v-for="n in 3"
                :key="n"
                class="w-1 rounded-full"
                :style="{
                    height: `${4 + n * 3}px`,
                    background: n <= meta.bars ? meta.colour : 'var(--line)',
                }"
            />
        </span>
        <span v-if="label">{{ meta.text }}</span>
        <span v-else class="sr-only">{{ meta.text }}</span>
    </span>
</template>
