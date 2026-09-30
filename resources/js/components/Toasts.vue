<script setup>
import { ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { CircleCheck, Info, TriangleAlert, X } from '@lucide/vue';

const page = usePage();
const toasts = ref([]);
let nextId = 1;

const ICONS = { success: CircleCheck, error: TriangleAlert, info: Info };

const dismiss = (id) => {
    toasts.value = toasts.value.filter((toast) => toast.id !== id);
};

// The server flashes { toast: { type, message } }; every visit brings a fresh flash object.
watch(
    () => page.flash,
    (flash) => {
        if (!flash?.toast?.message) return;
        const id = nextId++;
        toasts.value.push({ id, type: flash.toast.type ?? 'info', message: flash.toast.message });
        setTimeout(() => dismiss(id), 4500);
    },
    { immediate: true },
);
</script>

<template>
    <div class="pointer-events-none fixed inset-x-0 bottom-24 z-[60] flex flex-col items-center gap-2 px-4 lg:bottom-6" aria-live="polite">
        <TransitionGroup
            enter-active-class="transition duration-300 ease-out"
            enter-from-class="translate-y-3 opacity-0"
            leave-active-class="transition duration-200 ease-in"
            leave-to-class="translate-y-2 opacity-0"
        >
            <div
                v-for="toast in toasts"
                :key="toast.id"
                class="card pointer-events-auto flex max-w-md items-start gap-3 py-3 pr-3 pl-4 text-sm font-medium"
                role="status"
            >
                <component
                    :is="ICONS[toast.type] ?? Info"
                    :size="18"
                    class="mt-0.5 shrink-0"
                    :class="{ 'text-mint': toast.type === 'success', 'text-rose': toast.type === 'error', 'text-sky': toast.type === 'info' }"
                />
                <span class="flex-1">{{ toast.message }}</span>
                <button type="button" class="rounded-md p-0.5 text-faint hover:text-ink" aria-label="Dismiss" @click="dismiss(toast.id)">
                    <X :size="16" />
                </button>
            </div>
        </TransitionGroup>
    </div>
</template>
