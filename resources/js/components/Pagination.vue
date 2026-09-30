<script setup>
import { Link } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight } from '@lucide/vue';

defineProps({
    // A Laravel paginator: { current_page, last_page, prev_page_url, next_page_url, total, from, to }
    paginator: { type: Object, required: true },
});
</script>

<template>
    <nav v-if="paginator.last_page > 1" class="flex items-center justify-between gap-4" aria-label="Pagination">
        <p class="text-sm text-faint">
            <span class="font-semibold text-soft">{{ paginator.from }}–{{ paginator.to }}</span> of {{ paginator.total }}
        </p>
        <div class="flex items-center gap-2">
            <component
                :is="paginator.prev_page_url ? Link : 'span'"
                :href="paginator.prev_page_url"
                preserve-scroll
                class="btn btn-ghost btn-sm"
                :class="paginator.prev_page_url ? '' : 'pointer-events-none opacity-40'"
            >
                <ChevronLeft :size="16" /> Previous
            </component>
            <span class="px-1 text-sm font-semibold text-soft tabular-nums">{{ paginator.current_page }} / {{ paginator.last_page }}</span>
            <component
                :is="paginator.next_page_url ? Link : 'span'"
                :href="paginator.next_page_url"
                preserve-scroll
                class="btn btn-ghost btn-sm"
                :class="paginator.next_page_url ? '' : 'pointer-events-none opacity-40'"
            >
                Next <ChevronRight :size="16" />
            </component>
        </div>
    </nav>
</template>
