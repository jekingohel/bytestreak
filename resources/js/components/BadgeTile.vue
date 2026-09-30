<script setup>
import { Lock } from '@lucide/vue';
import ProgressBar from '@/components/ProgressBar.vue';

defineProps({
    badge: { type: Object, required: true }, // { name, description, emoji, xp_bonus, earned_at?, progress? }
    earned: { type: Boolean, default: false },
});
</script>

<template>
    <article
        class="card relative flex flex-col items-center gap-3 overflow-hidden p-5 text-center transition-transform duration-200"
        :class="earned ? 'hover:-translate-y-1' : ''"
    >
        <!-- Earned badges catch the light every few seconds. -->
        <span
            v-if="earned"
            class="pointer-events-none absolute inset-y-0 left-0 w-1/3 animate-shine bg-linear-to-r from-transparent via-white/10 to-transparent"
            aria-hidden="true"
        />

        <span
            class="relative grid size-16 place-items-center rounded-[22px] text-3xl"
            :class="earned ? 'bg-linear-to-br from-ember/25 to-flame/25 ring-1 ring-ember/40' : 'bg-raised ring-1 ring-line'"
        >
            <span :class="earned ? '' : 'opacity-30 grayscale'" aria-hidden="true">{{ badge.emoji }}</span>
            <span
                v-if="!earned"
                class="absolute -right-1.5 -bottom-1.5 grid size-6 place-items-center rounded-full border border-line bg-surface text-faint"
            >
                <Lock :size="12" :stroke-width="2.5" />
            </span>
        </span>

        <div>
            <h3 class="text-[0.9375rem] font-bold" :class="earned ? '' : 'text-soft'">{{ badge.name }}</h3>
            <p class="mt-1 text-xs leading-relaxed text-faint">{{ badge.description }}</p>
        </div>

        <div class="mt-auto w-full">
            <p v-if="earned" class="pill mx-auto bg-gold/12 text-gold">
                +{{ badge.xp_bonus }} XP earned
            </p>
            <template v-else-if="badge.progress">
                <ProgressBar :percent="badge.progress.percent" :height="6" colour="var(--ember)" />
                <p class="mt-2 text-xs font-semibold text-soft">
                    <span class="num">{{ badge.progress.current }} / {{ badge.progress.target }}</span>
                    <span class="font-normal text-faint"> · +{{ badge.xp_bonus }} XP</span>
                </p>
            </template>
        </div>
    </article>
</template>
