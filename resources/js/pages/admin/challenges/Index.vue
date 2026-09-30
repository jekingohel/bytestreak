<script setup>
import { computed, ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { Archive, ChartColumn, ListPlus, Pencil, Plus, Search, Send, Trash2 } from '@lucide/vue';
import AdminNav from '@/components/AdminNav.vue';
import CategoryChip from '@/components/CategoryChip.vue';
import DifficultyMeter from '@/components/DifficultyMeter.vue';
import Modal from '@/components/Modal.vue';
import Pagination from '@/components/Pagination.vue';
import { dayLabel } from '@/lib/format';

const props = defineProps({
    challenges: { type: Object, required: true },
    filters: { type: Object, required: true },
    categories: { type: Array, required: true },
    counts: { type: Object, required: true },
});

const STATUSES = [
    { value: null, label: 'All' },
    { value: 'draft', label: 'Drafts' },
    { value: 'scheduled', label: 'Scheduled' },
    { value: 'published', label: 'Published' },
    { value: 'archived', label: 'Archived' },
];

const STATUS_STYLES = {
    draft: 'bg-raised text-soft',
    scheduled: 'bg-sky/12 text-sky',
    published: 'bg-mint/12 text-mint',
    archived: 'bg-rose/12 text-rose',
};

const total = computed(() => Object.values(props.counts).reduce((sum, n) => sum + Number(n), 0));
const search = ref(props.filters.search ?? '');

const apply = (changes) =>
    router.get(
        route('admin.challenges.index'),
        Object.fromEntries(Object.entries({ ...props.filters, ...changes }).filter(([, value]) => value)),
        { preserveState: true, preserveScroll: true, replace: true },
    );

let debounce = null;
watch(search, (value) => {
    clearTimeout(debounce);
    debounce = setTimeout(() => apply({ search: value }), 300);
});

// One confirm dialog for every row action.
const pending = ref(null);
const ACTIONS = {
    publish: { title: 'Publish now?', body: 'It goes live immediately: as today’s challenge if today is still free, otherwise as a bonus challenge in the archive.', button: 'Publish', method: 'post', route: 'admin.challenges.publish' },
    queue: { title: 'Add to the queue?', body: 'It will be published automatically on the next working day that has nothing scheduled.', button: 'Add to queue', method: 'post', route: 'admin.challenges.queue' },
    archive: { title: 'Archive this challenge?', body: 'Developers will no longer see it. Answers and XP already earned are kept.', button: 'Archive', method: 'post', route: 'admin.challenges.archive' },
    destroy: { title: 'Delete this challenge?', body: 'This cannot be undone. Challenges that already have answers cannot be deleted.', button: 'Delete', method: 'delete', route: 'admin.challenges.destroy', danger: true },
};

const ask = (action, challenge) => (pending.value = { ...ACTIONS[action], challenge });
const confirm = () => {
    const { method, route: name, challenge } = pending.value;
    const options = { preserveScroll: true, onFinish: () => (pending.value = null) };

    if (method === 'delete') {
        router.delete(route(name, challenge.id), options);
    } else {
        router.post(route(name, challenge.id), {}, options);
    }
};
</script>

<template>
    <Head title="Challenges · Admin" />
    <AdminNav />

    <header class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold sm:text-3xl">Challenges</h1>
            <p class="mt-1 text-soft">Write, schedule and review the daily questions.</p>
        </div>
        <Link :href="route('admin.challenges.create')" class="btn btn-primary"><Plus :size="18" :stroke-width="2.5" /> New challenge</Link>
    </header>

    <div class="mt-6 flex flex-wrap items-center gap-3">
        <div class="inline-flex rounded-xl border border-line bg-surface p-1">
            <button
                v-for="status in STATUSES"
                :key="status.label"
                type="button"
                class="flex h-9 items-center gap-1.5 rounded-lg px-3 text-sm font-semibold transition-colors"
                :class="(filters.status ?? null) === status.value ? 'bg-raised text-ink shadow-sm' : 'text-faint hover:text-ink'"
                @click="apply({ status: status.value })"
            >
                {{ status.label }}
                <span class="num text-xs opacity-70">{{ status.value ? (counts[status.value] ?? 0) : total }}</span>
            </button>
        </div>

        <select class="input w-auto! min-w-40" :value="filters.category ?? ''" aria-label="Filter by category" @change="apply({ category: $event.target.value })">
            <option value="">All categories</option>
            <option v-for="category in categories" :key="category.id" :value="category.id">{{ category.emoji }} {{ category.name }}</option>
        </select>

        <label class="relative ml-auto w-full sm:w-64">
            <Search :size="16" class="pointer-events-none absolute top-1/2 left-3.5 -translate-y-1/2 text-faint" />
            <input v-model="search" type="search" class="input pl-10!" placeholder="Search titles" aria-label="Search titles" />
        </label>
    </div>

    <section class="card mt-5 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="table">
                <thead>
                    <tr>
                        <th>Challenge</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th class="text-right!">Answers</th>
                        <th class="text-right!">Correct</th>
                        <th><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="challenge in challenges.data" :key="challenge.id">
                        <td class="min-w-72">
                            <Link :href="route('admin.challenges.edit', challenge.id)" class="font-semibold hover:text-flame">{{ challenge.title }}</Link>
                            <div class="mt-1.5 flex flex-wrap items-center gap-2">
                                <CategoryChip :category="challenge.category" size="sm" />
                                <span class="text-xs text-faint">{{ challenge.type.label }}</span>
                                <DifficultyMeter :level="challenge.difficulty" :label="false" />
                                <span class="num text-xs font-semibold text-gold">{{ challenge.xp }} XP</span>
                            </div>
                        </td>
                        <td>
                            <span class="pill capitalize" :class="STATUS_STYLES[challenge.status]">{{ challenge.is_queued ? 'In queue' : challenge.status }}</span>
                        </td>
                        <td class="whitespace-nowrap text-soft">
                            {{ challenge.publish_date ? dayLabel(challenge.publish_date) : challenge.status === 'published' ? 'Bonus' : '—' }}
                        </td>
                        <td class="num text-right">{{ challenge.attempts || '—' }}</td>
                        <td class="num text-right font-semibold">{{ challenge.accuracy === null ? '—' : `${challenge.accuracy}%` }}</td>
                        <td>
                            <div class="flex items-center justify-end gap-1">
                                <Link :href="route('admin.challenges.show', challenge.id)" class="btn btn-quiet btn-sm px-2!" title="Answer statistics" aria-label="Answer statistics">
                                    <ChartColumn :size="16" />
                                </Link>
                                <Link :href="route('admin.challenges.edit', challenge.id)" class="btn btn-quiet btn-sm px-2!" title="Edit" aria-label="Edit">
                                    <Pencil :size="16" />
                                </Link>
                                <button v-if="challenge.status !== 'published'" type="button" class="btn btn-quiet btn-sm px-2!" title="Publish now" aria-label="Publish now" @click="ask('publish', challenge)">
                                    <Send :size="16" />
                                </button>
                                <button v-if="challenge.status === 'draft' || challenge.status === 'archived'" type="button" class="btn btn-quiet btn-sm px-2!" title="Add to queue" aria-label="Add to queue" @click="ask('queue', challenge)">
                                    <ListPlus :size="16" />
                                </button>
                                <button v-if="challenge.status !== 'archived'" type="button" class="btn btn-quiet btn-sm px-2!" title="Archive" aria-label="Archive" @click="ask('archive', challenge)">
                                    <Archive :size="16" />
                                </button>
                                <button v-if="!challenge.attempts" type="button" class="btn btn-quiet btn-sm px-2! hover:text-rose!" title="Delete" aria-label="Delete" @click="ask('destroy', challenge)">
                                    <Trash2 :size="16" />
                                </button>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="!challenges.data.length">
                        <td colspan="6" class="py-14! text-center text-soft">No challenges match these filters.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>

    <Pagination class="mt-5" :paginator="challenges" />

    <Modal :show="pending !== null" @close="pending = null">
        <template v-if="pending">
            <h2 class="text-xl font-extrabold">{{ pending.title }}</h2>
            <p class="mt-1 text-sm font-semibold text-soft">“{{ pending.challenge.title }}”</p>
            <p class="mt-3 text-sm leading-relaxed text-soft">{{ pending.body }}</p>
            <div class="mt-6 flex justify-end gap-3">
                <button type="button" class="btn btn-ghost" @click="pending = null">Cancel</button>
                <button type="button" class="btn" :class="pending.danger ? 'btn-danger' : 'btn-primary'" @click="confirm">{{ pending.button }}</button>
            </div>
        </template>
    </Modal>
</template>
