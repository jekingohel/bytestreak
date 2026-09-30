<script setup>
defineProps({
    category: { type: Object, required: true }, // { name, emoji, color }
    size: { type: String, default: 'md' }, // sm | md
});
</script>

<template>
    <span
        class="pill category-chip"
        :class="size === 'sm' ? 'h-6! px-2! text-[0.6875rem]!' : ''"
        :style="{ '--c': category.color }"
    >
        <span aria-hidden="true">{{ category.emoji }}</span>
        {{ category.name }}
    </span>
</template>

<style scoped>
.category-chip {
    /* Tint the brand colour toward the text colour so it stays readable in both themes. */
    color: color-mix(in oklab, var(--c) 70%, var(--ink));
    background: color-mix(in srgb, var(--c) 15%, transparent);
    border: 1px solid color-mix(in srgb, var(--c) 28%, transparent);
}
</style>
