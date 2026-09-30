<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

const props = defineProps({
    to: { type: String, required: true }, // ISO timestamp
});

const emit = defineEmits(['done']);
const remaining = ref(0);
let timer = null;
let finished = false;

const tick = () => {
    remaining.value = Math.max(0, Math.floor((new Date(props.to).getTime() - Date.now()) / 1000));
    if (remaining.value === 0 && !finished) {
        finished = true;
        emit('done');
    }
};

const parts = computed(() => {
    const pad = (n) => String(n).padStart(2, '0');
    // More than a day away (the weekly reset): days matter more than seconds.
    if (remaining.value >= 86400) {
        return [
            { value: Math.floor(remaining.value / 86400), unit: 'd' },
            { value: pad(Math.floor((remaining.value % 86400) / 3600)), unit: 'h' },
            { value: pad(Math.floor((remaining.value % 3600) / 60)), unit: 'm' },
        ];
    }
    return [
        { value: pad(Math.floor(remaining.value / 3600)), unit: 'h' },
        { value: pad(Math.floor((remaining.value % 3600) / 60)), unit: 'm' },
        { value: pad(remaining.value % 60), unit: 's' },
    ];
});

onMounted(() => {
    tick();
    timer = setInterval(tick, 1000);
});
onBeforeUnmount(() => clearInterval(timer));
</script>

<template>
    <span class="inline-flex items-baseline gap-1.5 font-mono font-semibold tabular-nums" role="timer">
        <span v-for="part in parts" :key="part.unit">
            {{ part.value }}<span class="ml-px text-[0.7em] font-medium text-faint">{{ part.unit }}</span>
        </span>
    </span>
</template>
