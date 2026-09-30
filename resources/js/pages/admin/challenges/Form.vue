<script setup>
import { computed, ref, watch } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Check, Plus, Trash2, TriangleAlert } from '@lucide/vue';
import AdminNav from '@/components/AdminNav.vue';
import CategoryChip from '@/components/CategoryChip.vue';
import CodeBlock from '@/components/CodeBlock.vue';
import DifficultyMeter from '@/components/DifficultyMeter.vue';
import { longDay } from '@/lib/format';
import { LANGUAGE_LABELS } from '@/lib/highlight';

const props = defineProps({
    challenge: { type: Object, default: null },
    categories: { type: Array, required: true },
    types: { type: Array, required: true },
    difficulties: { type: Array, required: true },
    languages: { type: Array, required: true },
    takenDates: { type: Array, required: true },
    today: { type: String, required: true },
});

const editing = computed(() => props.challenge !== null);

const form = useForm({
    category_id: props.challenge?.category_id ?? props.categories[0]?.id ?? null,
    type: props.challenge?.type ?? 'multiple_choice',
    difficulty: props.challenge?.difficulty ?? 'easy',
    title: props.challenge?.title ?? '',
    question: props.challenge?.question ?? '',
    code_snippet: props.challenge?.code_snippet ?? '',
    code_language: props.challenge?.code_language ?? '',
    explanation: props.challenge?.explanation ?? '',
    xp: props.challenge?.xp ?? props.difficulties[0].xp,
    status: props.challenge?.status ?? 'scheduled',
    publish_date: props.challenge?.publish_date ?? null,
    options: props.challenge?.options.map((o) => ({ ...o })) ?? [{ label: '' }, { label: '' }, { label: '' }, { label: '' }],
    correct_index: props.challenge?.correct_index ?? 0,
});

/*
 * "When should this go live?" is one choice for the admin, stored as status + publish_date:
 *   draft → draft · queue → scheduled, no date · date → scheduled, with date
 *   live → published (keeps its date) · archived → archived
 */
const initialMode = () => {
    if (!props.challenge) return 'queue';
    if (props.challenge.status === 'scheduled') return props.challenge.publish_date ? 'date' : 'queue';
    return { draft: 'draft', published: 'live', archived: 'archived' }[props.challenge.status];
};
const mode = ref(initialMode());

const MODES = computed(() => [
    { value: 'draft', label: 'Draft', text: 'Keep working on it. Nobody can see it.' },
    { value: 'queue', label: 'Add to queue', text: 'Goes out on the next working day with nothing scheduled.' },
    { value: 'date', label: 'Pick a date', text: 'Becomes the daily challenge on the day you choose.' },
    ...(props.challenge?.status === 'published' ? [{ value: 'live', label: 'Published', text: 'Already live. Keep it that way.' }] : []),
    ...(editing.value ? [{ value: 'archived', label: 'Archived', text: 'Hidden from developers. Answers are kept.' }] : []),
]);

const dateTaken = computed(() => mode.value === 'date' && form.publish_date && props.takenDates.includes(form.publish_date));

// Picking a difficulty suggests its XP, unless the admin already set a custom value.
watch(
    () => form.difficulty,
    (next, previous) => {
        const xpOf = (value) => props.difficulties.find((d) => d.value === value)?.xp;
        if (form.xp === xpOf(previous)) form.xp = xpOf(next);
    },
);

// True / False always has exactly those two options.
watch(
    () => form.type,
    (type) => {
        if (type !== 'true_false') return;
        form.options = [
            { id: form.options[0]?.id, label: 'True' },
            { id: form.options[1]?.id, label: 'False' },
        ];
        form.correct_index = Math.min(form.correct_index, 1);
    },
);

const addOption = () => form.options.length < 6 && form.options.push({ label: '' });
const removeOption = (index) => {
    form.options.splice(index, 1);
    if (form.correct_index === index) form.correct_index = 0;
    else if (form.correct_index > index) form.correct_index--;
};

const submit = () => {
    form.transform((data) => ({
        ...data,
        status: { draft: 'draft', queue: 'scheduled', date: 'scheduled', live: 'published', archived: 'archived' }[mode.value],
        publish_date: mode.value === 'date' ? data.publish_date : ['live', 'archived'].includes(mode.value) ? (props.challenge?.publish_date ?? null) : null,
    }));

    if (editing.value) {
        form.put(route('admin.challenges.update', props.challenge.id), { preserveScroll: 'errors' });
    } else {
        form.post(route('admin.challenges.store'), { preserveScroll: 'errors' });
    }
};

const previewCategory = computed(() => props.categories.find((c) => c.id === form.category_id));
const previewType = computed(() => props.types.find((t) => t.value === form.type)?.label);
const optionError = (index) => form.errors[`options.${index}.label`];
</script>

<template>
    <Head :title="editing ? 'Edit challenge · Admin' : 'New challenge · Admin'" />
    <AdminNav />

    <Link :href="route('admin.challenges.index')" class="inline-flex items-center gap-1.5 text-sm font-semibold text-soft hover:text-ink">
        <ArrowLeft :size="16" :stroke-width="2.5" /> All challenges
    </Link>
    <h1 class="mt-3 text-2xl font-extrabold sm:text-3xl">{{ editing ? 'Edit challenge' : 'New challenge' }}</h1>

    <p v-if="editing && challenge.attempts > 0" class="mt-4 flex items-start gap-2.5 rounded-xl border border-gold/30 bg-gold/10 px-4 py-3 text-sm text-soft">
        <TriangleAlert :size="17" class="mt-0.5 shrink-0 text-gold" />
        <span>
            <span class="font-bold text-ink">{{ challenge.attempts }} {{ challenge.attempts === 1 ? 'person has' : 'people have' }} already answered.</span>
            Fixing wording is fine, but changing the correct answer will not re-score them.
        </span>
    </p>

    <form class="mt-6 grid gap-6 lg:grid-cols-[minmax(0,1fr)_380px]" @submit.prevent="submit">
        <div class="space-y-6">
            <!-- Question -->
            <section class="card space-y-5 p-6">
                <h2 class="text-lg font-bold">Question</h2>

                <div>
                    <label class="label" for="title">Title</label>
                    <input id="title" v-model="form.title" type="text" class="input" maxlength="160" placeholder="e.g. Loose vs strict comparison" required :aria-invalid="!!form.errors.title" />
                    <p v-if="form.errors.title" class="field-error">{{ form.errors.title }}</p>
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label class="label" for="category">Category</label>
                        <select id="category" v-model="form.category_id" class="input" required>
                            <option v-for="category in categories" :key="category.id" :value="category.id">{{ category.emoji }} {{ category.name }}</option>
                        </select>
                        <p v-if="form.errors.category_id" class="field-error">{{ form.errors.category_id }}</p>
                    </div>
                    <div>
                        <label class="label" for="type">Challenge type</label>
                        <select id="type" v-model="form.type" class="input" required>
                            <option v-for="type in types" :key="type.value" :value="type.value">{{ type.label }}</option>
                        </select>
                    </div>
                    <div>
                        <span class="label">Difficulty</span>
                        <div class="grid grid-cols-3 gap-2">
                            <button
                                v-for="difficulty in difficulties"
                                :key="difficulty.value"
                                type="button"
                                class="flex h-11 items-center justify-center rounded-xl border text-sm font-semibold transition-colors"
                                :class="form.difficulty === difficulty.value ? 'border-flame bg-flame/10 text-flame' : 'border-line text-soft hover:text-ink'"
                                :aria-pressed="form.difficulty === difficulty.value"
                                @click="form.difficulty = difficulty.value"
                            >
                                {{ difficulty.label }}
                            </button>
                        </div>
                    </div>
                    <div>
                        <label class="label" for="xp">XP for a correct answer</label>
                        <input id="xp" v-model.number="form.xp" type="number" min="1" max="200" class="input" required :aria-invalid="!!form.errors.xp" />
                        <p v-if="form.errors.xp" class="field-error">{{ form.errors.xp }}</p>
                    </div>
                </div>

                <div>
                    <label class="label" for="question">Question text</label>
                    <textarea id="question" v-model="form.question" rows="3" class="input" placeholder="What will this code output?" required :aria-invalid="!!form.errors.question" />
                    <p v-if="form.errors.question" class="field-error">{{ form.errors.question }}</p>
                </div>

                <div>
                    <div class="flex items-end justify-between gap-3">
                        <label class="label mb-0!" for="code">Code snippet <span class="font-normal text-faint">(optional)</span></label>
                        <select v-model="form.code_language" class="input h-9! min-h-0! w-auto! py-0! text-sm!" aria-label="Code language">
                            <option value="">No highlighting</option>
                            <option v-for="language in languages" :key="language" :value="language">{{ LANGUAGE_LABELS[language] ?? language }}</option>
                        </select>
                    </div>
                    <textarea id="code" v-model="form.code_snippet" rows="7" class="input mt-2 font-mono text-[0.8125rem]! leading-6" spellcheck="false" placeholder="$a = &quot;10&quot;;" />
                    <p class="mt-1.5 text-xs text-faint">With a language and no snippet, the answer options are shown in a code font.</p>
                </div>
            </section>

            <!-- Options -->
            <section class="card p-6">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-bold">Answer options</h2>
                    <p class="text-sm text-faint">Select the correct one</p>
                </div>

                <div class="mt-4 space-y-3">
                    <div v-for="(option, index) in form.options" :key="index">
                        <div class="flex items-center gap-3">
                            <button
                                type="button"
                                class="grid size-11 shrink-0 place-items-center rounded-xl border-2 font-mono text-sm font-bold transition-colors"
                                :class="form.correct_index === index ? 'border-mint bg-mint text-bg' : 'border-line text-faint hover:border-mint/60'"
                                :aria-pressed="form.correct_index === index"
                                :aria-label="`Mark option ${'ABCDEF'[index]} as correct`"
                                @click="form.correct_index = index"
                            >
                                <Check v-if="form.correct_index === index" :size="18" :stroke-width="3.5" />
                                <template v-else>{{ 'ABCDEF'[index] }}</template>
                            </button>
                            <input
                                v-model="option.label"
                                type="text"
                                class="input"
                                :placeholder="`Option ${'ABCDEF'[index]}`"
                                :aria-label="`Option ${'ABCDEF'[index]}`"
                                :readonly="form.type === 'true_false'"
                                :aria-invalid="!!optionError(index)"
                                required
                            />
                            <button
                                v-if="form.type !== 'true_false' && form.options.length > 2"
                                type="button"
                                class="btn btn-quiet px-2.5! hover:text-rose!"
                                :aria-label="`Remove option ${'ABCDEF'[index]}`"
                                @click="removeOption(index)"
                            >
                                <Trash2 :size="17" />
                            </button>
                        </div>
                        <p v-if="optionError(index)" class="field-error ml-14">{{ optionError(index) }}</p>
                    </div>
                </div>

                <p v-if="form.errors.options" class="field-error">{{ form.errors.options }}</p>
                <p v-if="form.errors.correct_index" class="field-error">{{ form.errors.correct_index }}</p>

                <button v-if="form.type !== 'true_false' && form.options.length < 6" type="button" class="btn btn-ghost btn-sm mt-4" @click="addOption">
                    <Plus :size="15" :stroke-width="2.5" /> Add option
                </button>
            </section>

            <!-- Explanation -->
            <section class="card p-6">
                <h2 class="text-lg font-bold">Explanation</h2>
                <p class="mt-1 text-sm text-faint">Shown right after answering. This is where the learning happens, so say why.</p>
                <textarea v-model="form.explanation" rows="4" class="input mt-4" aria-label="Explanation" required :aria-invalid="!!form.errors.explanation" />
                <p v-if="form.errors.explanation" class="field-error">{{ form.errors.explanation }}</p>
            </section>
        </div>

        <!-- Publishing + preview -->
        <aside class="space-y-6 lg:sticky lg:top-24 lg:self-start">
            <section class="card p-6">
                <h2 class="text-lg font-bold">When does it go live?</h2>

                <div class="mt-4 space-y-2" role="radiogroup">
                    <label
                        v-for="option in MODES"
                        :key="option.value"
                        class="flex cursor-pointer items-start gap-3 rounded-xl border p-3 transition-colors"
                        :class="mode === option.value ? 'border-flame bg-flame/8' : 'border-line hover:border-soft/40'"
                    >
                        <input v-model="mode" type="radio" :value="option.value" class="mt-1 size-4 accent-flame" />
                        <span>
                            <span class="block text-sm font-bold">{{ option.label }}</span>
                            <span class="block text-xs leading-relaxed text-faint">{{ option.text }}</span>
                        </span>
                    </label>
                </div>

                <div v-if="mode === 'date'" class="mt-4">
                    <label class="label" for="publish_date">Publish date</label>
                    <input id="publish_date" v-model="form.publish_date" type="date" class="input" :min="today" required :aria-invalid="!!form.errors.publish_date || dateTaken" />
                    <p v-if="dateTaken" class="field-error">Another challenge already owns {{ longDay(form.publish_date) }}.</p>
                </div>
                <p v-if="form.errors.publish_date" class="field-error">{{ form.errors.publish_date }}</p>
                <p v-if="form.errors.status" class="field-error">{{ form.errors.status }}</p>

                <button type="submit" class="btn btn-primary btn-lg mt-6 w-full" :disabled="form.processing || dateTaken">
                    {{ editing ? 'Save changes' : 'Create challenge' }}
                </button>
            </section>

            <!-- Live preview -->
            <section class="card p-5">
                <p class="eyebrow">Preview</p>
                <div class="mt-3 flex flex-wrap items-center gap-2">
                    <CategoryChip v-if="previewCategory" :category="previewCategory" size="sm" />
                    <span class="text-xs text-faint">{{ previewType }}</span>
                    <DifficultyMeter :level="form.difficulty" :label="false" />
                    <span class="num ml-auto text-xs font-bold text-gold">+{{ form.xp }} XP</span>
                </div>
                <p class="mt-3 font-display text-lg leading-snug font-bold">{{ form.title || 'Your title' }}</p>
                <p class="mt-1.5 text-sm leading-relaxed whitespace-pre-line text-soft">{{ form.question || 'Your question appears here.' }}</p>
                <CodeBlock v-if="form.code_snippet" class="mt-3 text-xs!" :code="form.code_snippet" :language="form.code_language || null" />
                <ul class="mt-3 space-y-2">
                    <li
                        v-for="(option, index) in form.options"
                        :key="index"
                        class="flex items-center gap-2.5 rounded-xl border px-3 py-2 text-sm"
                        :class="form.correct_index === index ? 'border-mint/60 bg-mint/8 text-mint' : 'border-line text-soft'"
                    >
                        <span class="kbd">{{ 'ABCDEF'[index] }}</span>
                        <span class="min-w-0 flex-1 break-words" :class="form.code_language && !form.code_snippet ? 'font-mono text-xs' : ''">{{ option.label || '…' }}</span>
                    </li>
                </ul>
            </section>
        </aside>
    </form>
</template>
