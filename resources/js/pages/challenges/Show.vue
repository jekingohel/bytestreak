<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { ArrowLeft, ArrowRight, Check, Eye, Flame, Lightbulb, Snowflake, Users, X, Zap } from '@lucide/vue';
import CategoryChip from '@/components/CategoryChip.vue';
import CodeBlock from '@/components/CodeBlock.vue';
import Countdown from '@/components/Countdown.vue';
import CountUp from '@/components/CountUp.vue';
import DifficultyMeter from '@/components/DifficultyMeter.vue';
import Modal from '@/components/Modal.vue';
import { celebrate, shower } from '@/lib/celebrate';
import { dayLabel, plural } from '@/lib/format';

const props = defineProps({
    challenge: { type: Object, required: true },
    attempt: { type: Object, default: null },
    team: { type: Object, default: null },
    nextUp: { type: Object, default: null },
    nextChallengeAt: { type: String, required: true },
});

const page = usePage();
const form = useForm({ option_id: null });

const answered = computed(() => props.attempt !== null);
const letters = 'ABCDEF';
const monoOptions = computed(() => !!props.challenge.code_language && !props.challenge.code_snippet);
const twoColumns = computed(() => props.challenge.options.every((o) => o.label.length <= 22) && !monoOptions.value);
const correctOption = computed(() => props.challenge.options.find((o) => o.is_correct));

// Celebration payload flashed by the server right after answering (absent when just reviewing).
const result = ref(null);
const rewards = ref([]); // queue of level-up / badge modals
const reward = computed(() => rewards.value[0] ?? null);

watch(
    () => page.flash?.result,
    (flash) => {
        if (!flash) return;
        result.value = flash;

        if (flash.is_correct) celebrate();

        const queue = [];
        if (flash.level_up) queue.push({ kind: 'level', ...flash.level_up });
        flash.badges.forEach((badge) => queue.push({ kind: 'badge', ...badge }));
        if (queue.length) {
            setTimeout(() => {
                rewards.value = queue;
                shower();
            }, flash.is_correct ? 1300 : 600);
        }
    },
    { immediate: true },
);

// Leaving for another challenge reuses this component, so reset the local state.
watch(
    () => props.challenge.id,
    () => {
        form.reset();
        form.clearErrors();
        if (!page.flash?.result) result.value = null;
    },
);

const nextReward = () => {
    rewards.value = rewards.value.slice(1);
    if (rewards.value.length) shower();
};

const choose = (option) => {
    if (answered.value || form.processing || props.challenge.is_preview) return;
    form.option_id = option.id;
};

const submit = () => {
    if (!form.option_id || answered.value || form.processing) return;
    form.post(route('challenges.attempt', props.challenge.id), { preserveScroll: true });
};

// Keyboard: 1–6 or A–F to choose, Enter to lock in.
const onKey = (event) => {
    if (answered.value || event.metaKey || event.ctrlKey || event.altKey) return;
    if (['INPUT', 'TEXTAREA', 'SELECT'].includes(event.target.tagName)) return;

    if (event.key === 'Enter' && form.option_id) {
        event.preventDefault();
        submit();
        return;
    }

    const key = event.key.toUpperCase();
    const index = /^[1-6]$/.test(key) ? Number(key) - 1 : letters.indexOf(key);
    if (key.length === 1 && index >= 0 && props.challenge.options[index]) {
        choose(props.challenge.options[index]);
    }
};

onMounted(() => document.addEventListener('keydown', onKey));
onBeforeUnmount(() => document.removeEventListener('keydown', onKey));

const optionState = (option) => {
    if (!answered.value) return form.option_id === option.id ? 'selected' : 'idle';
    if (option.is_correct) return 'correct';
    if (option.id === props.attempt.option_id) return 'wrong';
    return 'dim';
};

const streakNote = computed(() => {
    const streak = result.value?.streak;
    if (!streak?.extended) return null;
    if (streak.was_reset) return 'A fresh streak starts today.';
    if (streak.freezes_used > 0) return `A streak freeze covered ${plural(streak.freezes_used, 'missed day')}.`;
    if (streak.current === 1) return 'Your streak has started. Come back tomorrow to grow it.';
    return 'See you tomorrow to keep it going.';
});
</script>

<template>
    <Head :title="challenge.title" />

    <div class="mx-auto max-w-3xl">
        <Link
            :href="challenge.is_today ? route('dashboard') : route('archive')"
            class="inline-flex items-center gap-1.5 text-sm font-semibold text-soft hover:text-ink"
        >
            <ArrowLeft :size="16" :stroke-width="2.5" /> {{ challenge.is_today ? 'Home' : 'Archive' }}
        </Link>

        <p v-if="challenge.is_preview" class="mt-4 flex items-center gap-2 rounded-xl border border-sky/30 bg-sky/10 px-4 py-3 text-sm font-medium text-sky">
            <Eye :size="16" /> Preview only. This challenge is not live yet, so it cannot be answered.
        </p>

        <!-- Question -->
        <article class="card mt-4 p-5 sm:p-8">
            <div class="flex flex-wrap items-center gap-2.5">
                <span class="eyebrow mr-1" :class="challenge.is_today ? 'text-flame!' : ''">
                    {{ challenge.is_today ? 'Today’s challenge' : challenge.publish_date ? dayLabel(challenge.publish_date) : 'Bonus challenge' }}
                </span>
                <CategoryChip :category="challenge.category" />
                <span class="pill border border-line bg-raised text-soft">{{ challenge.type.label }}</span>
                <DifficultyMeter :level="challenge.difficulty" />
                <span v-if="!answered" class="pill ml-auto border border-gold/30 bg-gold/10 text-gold" :title="challenge.is_today ? 'XP for a correct answer' : 'Archive challenges pay half XP and do not affect your streak'">
                    <Zap :size="13" :stroke-width="2.5" class="fill-gold/30" /> +{{ challenge.reward }} XP
                    <span v-if="!challenge.is_today" class="font-medium opacity-80">practice</span>
                </span>
            </div>

            <h1 class="mt-5 text-2xl leading-tight font-extrabold sm:text-3xl">{{ challenge.title }}</h1>
            <p class="mt-3 text-[1.0625rem] leading-relaxed whitespace-pre-line text-soft">{{ challenge.question }}</p>

            <CodeBlock v-if="challenge.code_snippet" class="mt-5" :code="challenge.code_snippet" :language="challenge.code_language" />

            <!-- Options -->
            <div class="mt-6 grid gap-3" :class="twoColumns ? 'sm:grid-cols-2' : ''" role="radiogroup" aria-label="Answer options">
                <button
                    v-for="(option, index) in challenge.options"
                    :key="option.id"
                    type="button"
                    role="radio"
                    :aria-checked="form.option_id === option.id || attempt?.option_id === option.id"
                    :disabled="answered || challenge.is_preview"
                    class="option relative flex min-h-14 items-center gap-3.5 overflow-hidden rounded-2xl border-2 px-4 py-3 text-left transition-all duration-150"
                    :class="[`option-${optionState(option)}`, optionState(option) === 'wrong' && result ? 'animate-shake' : '']"
                    @click="choose(option)"
                >
                    <!-- How the team answered -->
                    <span
                        v-if="answered"
                        class="absolute inset-y-0 left-0 bg-current opacity-[0.07] transition-[width] duration-1000 ease-out"
                        :style="{ width: `${option.percent}%` }"
                        aria-hidden="true"
                    />

                    <span class="option-key relative grid size-8 shrink-0 place-items-center rounded-lg border font-mono text-sm font-bold">
                        <Check v-if="optionState(option) === 'correct'" :size="17" :stroke-width="3.5" />
                        <X v-else-if="optionState(option) === 'wrong'" :size="17" :stroke-width="3.5" />
                        <template v-else>{{ letters[index] }}</template>
                    </span>

                    <span class="relative flex-1 text-ink" :class="monoOptions ? 'font-mono text-[0.8125rem] leading-relaxed break-words sm:text-sm' : 'text-[0.9375rem] font-medium'">
                        {{ option.label }}
                    </span>

                    <span v-if="answered" class="num relative shrink-0 text-sm font-bold opacity-80">{{ option.percent }}%</span>
                </button>
            </div>

            <p v-if="form.errors.option_id" class="field-error">{{ form.errors.option_id }}</p>

            <!-- Lock in -->
            <div v-if="!answered && !challenge.is_preview" class="mt-7 flex flex-wrap items-center gap-4">
                <button type="button" class="btn btn-primary btn-lg min-w-48" :disabled="!form.option_id || form.processing" @click="submit">
                    {{ form.processing ? 'Checking…' : 'Lock in answer' }}
                </button>
                <p class="hidden items-center gap-1.5 text-sm text-faint sm:flex">
                    <span v-for="(option, index) in challenge.options" :key="option.id" class="kbd">{{ letters[index] }}</span> to choose · <span class="kbd">Enter</span> to lock in
                </p>
                <p class="w-full text-xs text-faint">One attempt only, so take a second look before you lock it in.</p>
            </div>
        </article>

        <!-- Result -->
        <template v-if="answered">
            <section
                class="card mt-5 animate-rise overflow-hidden border-2 p-5 sm:p-7"
                :class="attempt.is_correct ? 'border-mint/50 bg-mint/[0.06]' : 'border-rose/50 bg-rose/[0.06]'"
            >
                <div class="flex flex-wrap items-center gap-4">
                    <span class="grid size-14 shrink-0 animate-pop place-items-center rounded-2xl" :class="attempt.is_correct ? 'bg-mint text-bg' : 'bg-rose text-white'">
                        <Check v-if="attempt.is_correct" :size="30" :stroke-width="3.5" />
                        <X v-else :size="30" :stroke-width="3.5" />
                    </span>
                    <div class="min-w-0 flex-1">
                        <h2 class="text-2xl font-extrabold" :class="attempt.is_correct ? 'text-mint' : 'text-rose'">
                            {{ attempt.is_correct ? 'Correct!' : 'Not quite.' }}
                        </h2>
                        <p v-if="!attempt.is_correct && correctOption" class="mt-0.5 text-sm text-soft">
                            The answer was <span class="font-semibold text-ink" :class="monoOptions ? 'font-mono text-[0.8125rem]' : ''">{{ correctOption.label }}</span>
                        </p>
                        <p v-else class="mt-0.5 text-sm text-soft">{{ attempt.is_daily ? 'That one counts for your streak.' : 'Nice practice round.' }}</p>
                    </div>
                    <p class="num text-4xl font-extrabold text-gold">
                        +<CountUp v-if="result" :value="result.xp_total" :delay="250" /><template v-else>{{ attempt.xp_awarded }}</template>
                        <span class="text-lg">XP</span>
                    </p>
                </div>

                <!-- Only right after answering: where the XP came from, and what happened to the streak -->
                <div v-if="result" class="mt-5 grid gap-4 border-t border-line pt-5 sm:grid-cols-2">
                    <ul v-if="result.xp_lines.length" class="space-y-1.5 text-sm">
                        <li v-for="line in result.xp_lines" :key="line.label" class="flex items-center justify-between gap-3">
                            <span class="text-soft">{{ line.label }}</span>
                            <span class="num font-bold text-gold">+{{ line.amount }}</span>
                        </li>
                    </ul>
                    <p v-else class="text-sm text-soft">No XP this time. Archive challenges only pay when you get them right.</p>

                    <div v-if="result.streak?.extended" class="flex items-center gap-3 rounded-xl bg-flame/10 px-4 py-3">
                        <Flame :size="26" :stroke-width="2.25" class="shrink-0 animate-flicker fill-flame/30 text-flame" />
                        <div class="text-sm">
                            <p class="font-bold text-flame"><span class="num">{{ result.streak.current }}</span>-day streak</p>
                            <p class="text-soft">{{ streakNote }}</p>
                            <p v-if="result.streak.freeze_earned" class="mt-1 inline-flex items-center gap-1 font-semibold text-sky">
                                <Snowflake :size="14" :stroke-width="2.5" /> Streak freeze earned
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            <section class="card mt-5 animate-rise p-5 [animation-delay:120ms] sm:p-7">
                <h2 class="flex items-center gap-2 text-lg font-bold">
                    <Lightbulb :size="19" class="text-gold" /> Why
                </h2>
                <p class="mt-3 leading-relaxed whitespace-pre-line text-soft">{{ challenge.explanation }}</p>

                <p v-if="team && team.answers > 1" class="mt-5 flex items-center gap-2 border-t border-line pt-4 text-sm text-soft">
                    <Users :size="16" class="shrink-0 text-faint" />
                    <span><span class="num font-bold text-ink">{{ team.correct_percent }}%</span> of {{ plural(team.answers, 'developer') }} got this one right.</span>
                </p>
            </section>

            <!-- What next -->
            <section class="mt-5 grid animate-rise gap-4 [animation-delay:220ms] sm:grid-cols-2">
                <Link v-if="nextUp" :href="route('challenges.show', nextUp.id)" class="card group flex items-center gap-4 p-5 transition-colors hover:border-flame/50">
                    <div class="min-w-0 flex-1">
                        <p class="eyebrow">Keep going · practice</p>
                        <p class="mt-1.5 truncate font-bold group-hover:text-flame">{{ nextUp.title }}</p>
                        <p class="mt-0.5 text-xs text-faint">{{ nextUp.category.emoji }} {{ nextUp.category.name }} · {{ dayLabel(nextUp.publish_date) }}</p>
                    </div>
                    <ArrowRight :size="20" class="shrink-0 text-faint transition-transform group-hover:translate-x-1 group-hover:text-flame" />
                </Link>
                <div v-else class="card p-5">
                    <p class="eyebrow">All caught up</p>
                    <p class="mt-1.5 font-bold">You have played everything in the archive.</p>
                </div>

                <div class="card flex items-center gap-4 p-5">
                    <div class="flex-1">
                        <p class="eyebrow">Next daily challenge</p>
                        <p class="mt-1.5 text-xl"><Countdown :to="nextChallengeAt" /></p>
                    </div>
                    <Link :href="route('dashboard')" class="btn btn-ghost">Home</Link>
                </div>
            </section>
        </template>
    </div>

    <!-- Level-up and badge celebrations, one at a time -->
    <Modal :show="reward !== null" max-width="24rem" @close="nextReward">
        <div v-if="reward" :key="reward.kind + (reward.id ?? reward.to)" class="text-center">
            <template v-if="reward.kind === 'level'">
                <p class="eyebrow text-gold!">Level up</p>
                <p class="num mx-auto mt-4 grid size-24 animate-pop place-items-center rounded-full bg-linear-to-br from-gold to-flame text-5xl font-extrabold text-on-flame shadow-[0_0_60px_-10px_var(--gold)]">
                    {{ reward.to }}
                </p>
                <h2 class="mt-5 text-2xl font-extrabold">{{ reward.title }}</h2>
                <p class="mt-2 text-sm text-soft">You reached level {{ reward.to }}. Keep the streak going to climb faster.</p>
            </template>
            <template v-else>
                <p class="eyebrow text-ember!">Badge unlocked</p>
                <p class="mx-auto mt-4 grid size-24 animate-pop place-items-center rounded-[32px] bg-linear-to-br from-ember/30 to-flame/30 text-5xl ring-1 ring-ember/50 shadow-[0_0_60px_-12px_var(--ember)]" aria-hidden="true">
                    {{ reward.emoji }}
                </p>
                <h2 class="mt-5 text-2xl font-extrabold">{{ reward.name }}</h2>
                <p class="mt-2 text-sm text-soft">{{ reward.description }}</p>
                <p v-if="reward.xp_bonus" class="pill mx-auto mt-4 bg-gold/12 text-gold">+{{ reward.xp_bonus }} XP bonus</p>
            </template>

            <button type="button" class="btn btn-primary btn-lg mt-7 w-full" @click="nextReward">
                {{ rewards.length > 1 ? 'Next' : 'Nice!' }}
            </button>
        </div>
    </Modal>
</template>

<style scoped>
.option {
    border-color: var(--line);
    background: color-mix(in srgb, var(--raised) 55%, transparent);
    color: var(--soft);
}

.option-key {
    border-color: var(--line);
    background: var(--surface);
    color: var(--soft);
}

.option-idle:not(:disabled):hover {
    border-color: color-mix(in srgb, var(--flame) 55%, var(--line));
    transform: translateY(-1px);
}

.option-selected {
    border-color: var(--flame);
    background: color-mix(in srgb, var(--flame) 10%, transparent);
    box-shadow: 0 0 0 4px color-mix(in srgb, var(--flame) 14%, transparent);
    color: var(--flame);
}

.option-selected .option-key {
    border-color: var(--flame);
    background: var(--flame);
    color: var(--on-flame);
}

.option-correct {
    border-color: var(--mint);
    background: color-mix(in srgb, var(--mint) 9%, transparent);
    color: var(--mint);
}

.option-correct .option-key {
    border-color: var(--mint);
    background: var(--mint);
    color: var(--bg);
}

.option-wrong {
    border-color: var(--rose);
    background: color-mix(in srgb, var(--rose) 9%, transparent);
    color: var(--rose);
}

.option-wrong .option-key {
    border-color: var(--rose);
    background: var(--rose);
    color: #fff;
}

.option-dim {
    opacity: 0.6;
}
</style>
