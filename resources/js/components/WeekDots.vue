<script setup>
import { Check, Flame, Minus, X } from '@lucide/vue';

defineProps({
    // [{ date, label, state }] — state: correct | wrong | missed | today | upcoming | rest
    days: { type: Array, required: true },
});

const DESCRIPTIONS = {
    correct: 'answered correctly',
    wrong: 'answered (not quite right)',
    missed: 'missed',
    today: 'waiting for you',
    upcoming: 'coming up',
    rest: 'rest day',
};
</script>

<template>
    <ol class="grid grid-cols-7 gap-1.5">
        <li v-for="day in days" :key="day.date" class="flex flex-col items-center gap-1.5">
            <span
                class="grid size-9 place-items-center rounded-full border text-sm transition-colors"
                :class="{
                    'border-transparent bg-mint text-bg': day.state === 'correct',
                    'border-transparent bg-flame/20 text-flame': day.state === 'wrong',
                    'border-dashed border-line text-faint': day.state === 'missed',
                    'animate-beacon border-flame bg-flame/10 text-flame': day.state === 'today',
                    'border-line text-transparent': day.state === 'upcoming',
                    'border-transparent text-faint': day.state === 'rest',
                }"
                :title="`${day.label}: ${DESCRIPTIONS[day.state]}`"
            >
                <Check v-if="day.state === 'correct'" :size="18" :stroke-width="3" />
                <Flame v-else-if="day.state === 'wrong' || day.state === 'today'" :size="17" :stroke-width="2.5" />
                <X v-else-if="day.state === 'missed'" :size="15" />
                <Minus v-else-if="day.state === 'rest'" :size="14" />
                <span class="sr-only">{{ DESCRIPTIONS[day.state] }}</span>
            </span>
            <span
                class="text-[0.6875rem] font-semibold"
                :class="day.state === 'today' ? 'text-flame' : 'text-faint'"
            >
                {{ day.label.slice(0, 2) }}
            </span>
        </li>
    </ol>
</template>
