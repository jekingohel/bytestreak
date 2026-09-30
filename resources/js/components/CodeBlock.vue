<script setup>
import { computed } from 'vue';
import { highlight, LANGUAGE_LABELS } from '@/lib/highlight';

const props = defineProps({
    code: { type: String, required: true },
    language: { type: String, default: null },
});

const lines = computed(() => props.code.replace(/\n$/, '').split('\n'));
const html = computed(() => highlight(props.code.replace(/\n$/, ''), props.language));
</script>

<template>
    <figure class="overflow-hidden rounded-2xl border border-line bg-bg">
        <figcaption class="flex items-center justify-between border-b border-line px-4 py-2">
            <span class="flex gap-1.5" aria-hidden="true">
                <span class="size-2.5 rounded-full bg-rose/70" />
                <span class="size-2.5 rounded-full bg-gold/70" />
                <span class="size-2.5 rounded-full bg-mint/70" />
            </span>
            <span class="font-mono text-[0.6875rem] font-medium tracking-wide text-faint uppercase">
                {{ LANGUAGE_LABELS[language] ?? 'Code' }}
            </span>
        </figcaption>
        <div class="flex overflow-x-auto py-4 font-mono text-[0.8125rem] leading-6 sm:text-sm sm:leading-7">
            <div class="shrink-0 pr-4 pl-4 text-right text-faint/70 select-none" aria-hidden="true">
                <div v-for="n in lines.length" :key="n">{{ n }}</div>
            </div>
            <!-- highlight() returns escaped, highlighted HTML -->
            <pre class="pr-5"><code v-html="html" /></pre>
        </div>
    </figure>
</template>
