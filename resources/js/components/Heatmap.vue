<script setup>
import { computed } from 'vue';
import { longDay, monthName, parseDay, plural } from '@/lib/format';

const props = defineProps({
    // Columns are weeks (Mon → Sun): [[{ date, count, correct, future }]]
    weeks: { type: Array, required: true },
});

// Show a month name above the first column that starts in that month.
const months = computed(() => {
    let last = null;
    return props.weeks.map((week) => {
        const month = parseDay(week[0].date).getMonth();
        const label = month !== last ? monthName(week[0].date) : '';
        last = month;
        return label;
    });
});

const shade = (day) => {
    if (day.future) return 'transparent';
    if (day.count === 0) return 'var(--raised)';
    const strength = [0, 42, 68, 100][Math.min(day.count, 3)];
    return `color-mix(in srgb, var(--flame) ${strength}%, var(--raised))`;
};

const describe = (day) =>
    day.count === 0
        ? `${longDay(day.date)}: no challenges`
        : `${longDay(day.date)}: ${plural(day.count, 'challenge')}, ${day.correct} correct`;
</script>

<template>
    <div class="overflow-x-auto pb-1">
        <div class="inline-flex gap-2">
            <div class="grid grid-rows-7 gap-[3px] pt-5 text-[0.625rem] leading-[15px] text-faint" aria-hidden="true">
                <span>Mon</span><span /><span>Wed</span><span /><span>Fri</span><span /><span />
            </div>
            <div>
                <div class="mb-1 flex gap-[3px] text-[0.625rem] leading-4 text-faint" aria-hidden="true">
                    <span v-for="(label, i) in months" :key="i" class="w-[15px] overflow-visible whitespace-nowrap">{{ label }}</span>
                </div>
                <div class="flex gap-[3px]" role="img" aria-label="Activity over the last few months">
                    <div v-for="(week, w) in weeks" :key="w" class="grid grid-rows-7 gap-[3px]">
                        <span
                            v-for="day in week"
                            :key="day.date"
                            class="size-[15px] rounded-[4px]"
                            :class="day.future ? 'border border-dashed border-line/60' : ''"
                            :style="{ background: shade(day) }"
                            :title="day.future ? '' : describe(day)"
                        />
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
