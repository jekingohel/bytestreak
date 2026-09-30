<script setup>
import { computed } from 'vue';

const props = defineProps({
    user: { type: Object, required: true }, // { initials, hue, name }
    size: { type: Number, default: 36 },
});

const style = computed(() => ({
    width: `${props.size}px`,
    height: `${props.size}px`,
    fontSize: `${Math.round(props.size * 0.38)}px`,
    background: `linear-gradient(135deg, hsl(${props.user.hue} 72% 58%), hsl(${(props.user.hue + 38) % 360} 68% 42%))`,
}));
</script>

<template>
    <span
        class="inline-grid shrink-0 place-items-center rounded-full font-semibold tracking-tight text-white select-none"
        :style="style"
        :title="user.name"
        aria-hidden="true"
    >
        {{ user.initials }}
    </span>
</template>
