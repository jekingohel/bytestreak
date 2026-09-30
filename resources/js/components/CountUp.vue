<script setup>
import { onMounted, ref, watch } from 'vue';

const props = defineProps({
    value: { type: Number, required: true },
    from: { type: Number, default: 0 },
    duration: { type: Number, default: 900 },
    delay: { type: Number, default: 0 },
});

const shown = ref(props.from);

const run = (start, end) => {
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches || start === end) {
        shown.value = end;
        return;
    }

    const began = performance.now() + props.delay;
    const step = (now) => {
        const t = Math.min(1, Math.max(0, (now - began) / props.duration));
        const eased = 1 - Math.pow(1 - t, 3);
        shown.value = Math.round(start + (end - start) * eased);
        if (t < 1) requestAnimationFrame(step);
    };
    requestAnimationFrame(step);
};

onMounted(() => run(props.from, props.value));
watch(() => props.value, (next, previous) => run(previous, next));
</script>

<template>
    <span class="tabular-nums">{{ shown.toLocaleString('en') }}</span>
</template>
