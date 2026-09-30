<script setup>
import { nextTick, onBeforeUnmount, ref, watch } from 'vue';

const props = defineProps({
    show: { type: Boolean, default: false },
    maxWidth: { type: String, default: '28rem' },
    closeable: { type: Boolean, default: true },
});

const emit = defineEmits(['close']);
const panel = ref(null);

const close = () => props.closeable && emit('close');
const onKey = (event) => event.key === 'Escape' && close();

watch(
    () => props.show,
    async (open) => {
        document.body.style.overflow = open ? 'hidden' : '';
        if (open) {
            document.addEventListener('keydown', onKey);
            await nextTick();
            panel.value?.focus();
        } else {
            document.removeEventListener('keydown', onKey);
        }
    },
    { immediate: true },
);

onBeforeUnmount(() => {
    document.body.style.overflow = '';
    document.removeEventListener('keydown', onKey);
});
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0"
            leave-active-class="transition duration-150 ease-in"
            leave-to-class="opacity-0"
        >
            <div
                v-if="show"
                class="fixed inset-0 z-50 grid place-items-center overflow-y-auto bg-black/60 p-4 backdrop-blur-sm"
                @mousedown.self="close"
            >
                <div
                    ref="panel"
                    class="card w-full animate-pop p-6 outline-none sm:p-8"
                    :style="{ maxWidth }"
                    role="dialog"
                    aria-modal="true"
                    tabindex="-1"
                >
                    <slot />
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
