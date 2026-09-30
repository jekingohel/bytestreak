<script setup>
defineProps({
    percent: { type: Number, default: 0 },
    colour: { type: String, default: 'var(--flame)' },
    height: { type: Number, default: 8 },
});
</script>

<template>
    <div
        class="w-full overflow-hidden rounded-full bg-raised"
        :style="{ height: `${height}px` }"
        role="progressbar"
        :aria-valuenow="percent"
        aria-valuemin="0"
        aria-valuemax="100"
    >
        <div
            class="h-full rounded-full transition-[width] duration-1000 ease-out"
            :style="{ width: `${Math.min(100, Math.max(0, percent))}%`, background: colour }"
        />
    </div>
</template>
