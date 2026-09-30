<script setup>
import { computed, ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { Pencil, Plus, Trash2 } from '@lucide/vue';
import AdminNav from '@/components/AdminNav.vue';
import Modal from '@/components/Modal.vue';

const props = defineProps({
    badges: { type: Array, required: true },
    criteria: { type: Array, required: true },
    categories: { type: Array, required: true },
});

const editing = ref(null);
const form = useForm({
    name: '', description: '', emoji: '🏅', criteria_type: 'correct_total', criteria_value: 10,
    category_id: null, xp_bonus: 20, sort_order: 0, is_active: true,
});

const needsCategory = computed(() => form.criteria_type === 'category_correct');
const categoryName = (id) => props.categories.find((c) => c.id === id)?.name;

const rule = (badge) =>
    badge.criteria_type === 'category_correct'
        ? `${badge.criteria_value} correct in ${categoryName(badge.category_id) ?? 'a category'}`
        : `${badge.criteria_label}: ${badge.criteria_value}`;

const open = (badge = null) => {
    form.clearErrors();
    form.defaults({
        name: badge?.name ?? '',
        description: badge?.description ?? '',
        emoji: badge?.emoji ?? '🏅',
        criteria_type: badge?.criteria_type ?? 'correct_total',
        criteria_value: badge?.criteria_value ?? 10,
        category_id: badge?.category_id ?? null,
        xp_bonus: badge?.xp_bonus ?? 20,
        sort_order: badge?.sort_order ?? props.badges.length + 1,
        is_active: badge?.is_active ?? true,
    });
    form.reset();
    editing.value = badge ?? {};
};

const save = () => {
    const options = { preserveScroll: true, onSuccess: () => (editing.value = null) };
    if (editing.value.id) {
        form.put(route('admin.badges.update', editing.value.id), options);
    } else {
        form.post(route('admin.badges.store'), options);
    }
};

const remove = (badge) => router.delete(route('admin.badges.destroy', badge.id), { preserveScroll: true });
</script>

<template>
    <Head title="Badges · Admin" />
    <AdminNav />

    <header class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold sm:text-3xl">Badges</h1>
            <p class="mt-1 text-soft">Milestones that unlock automatically when a developer meets the rule.</p>
        </div>
        <button type="button" class="btn btn-primary" @click="open()"><Plus :size="18" :stroke-width="2.5" /> New badge</button>
    </header>

    <section class="card mt-6 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="table">
                <thead>
                    <tr><th>Badge</th><th>Unlocks when</th><th class="text-right!">Bonus</th><th class="text-right!">Earned by</th><th>Status</th><th><span class="sr-only">Actions</span></th></tr>
                </thead>
                <tbody>
                    <tr v-for="badge in badges" :key="badge.id">
                        <td class="min-w-64">
                            <div class="flex items-center gap-3">
                                <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-raised text-xl ring-1 ring-line" aria-hidden="true">{{ badge.emoji }}</span>
                                <div>
                                    <p class="font-semibold">{{ badge.name }}</p>
                                    <p class="text-xs text-faint">{{ badge.description }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="whitespace-nowrap text-soft">{{ rule(badge) }}</td>
                        <td class="num text-right font-semibold text-gold">+{{ badge.xp_bonus }}</td>
                        <td class="num text-right">{{ badge.earned_by }}</td>
                        <td><span class="pill" :class="badge.is_active ? 'bg-mint/12 text-mint' : 'bg-raised text-faint'">{{ badge.is_active ? 'Active' : 'Off' }}</span></td>
                        <td>
                            <div class="flex justify-end gap-1">
                                <button type="button" class="btn btn-quiet btn-sm px-2!" aria-label="Edit" title="Edit" @click="open(badge)"><Pencil :size="16" /></button>
                                <button v-if="!badge.earned_by" type="button" class="btn btn-quiet btn-sm px-2! hover:text-rose!" aria-label="Delete" title="Delete" @click="remove(badge)">
                                    <Trash2 :size="16" />
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>

    <Modal :show="editing !== null" max-width="32rem" @close="editing = null">
        <form v-if="editing" class="space-y-5" @submit.prevent="save">
            <h2 class="text-xl font-extrabold">{{ editing.id ? 'Edit badge' : 'New badge' }}</h2>

            <div class="grid grid-cols-[5rem_1fr] gap-4">
                <div>
                    <label class="label" for="emoji">Emoji</label>
                    <input id="emoji" v-model="form.emoji" type="text" class="input text-center text-xl" maxlength="8" required />
                </div>
                <div>
                    <label class="label" for="name">Name</label>
                    <input id="name" v-model="form.name" type="text" class="input" maxlength="60" required :aria-invalid="!!form.errors.name" />
                </div>
            </div>
            <p v-if="form.errors.name" class="field-error -mt-3!">{{ form.errors.name }}</p>

            <div>
                <label class="label" for="description">Description</label>
                <input id="description" v-model="form.description" type="text" class="input" maxlength="255" placeholder="Tell people how to earn it" required />
                <p v-if="form.errors.description" class="field-error">{{ form.errors.description }}</p>
            </div>

            <div class="grid grid-cols-[1fr_7rem] gap-4">
                <div>
                    <label class="label" for="criteria_type">Unlocks when</label>
                    <select id="criteria_type" v-model="form.criteria_type" class="input">
                        <option v-for="option in criteria" :key="option.value" :value="option.value">{{ option.label }}</option>
                    </select>
                </div>
                <div>
                    <label class="label" for="criteria_value">Reaches</label>
                    <input id="criteria_value" v-model.number="form.criteria_value" type="number" min="1" class="input" required />
                </div>
            </div>

            <div v-if="needsCategory">
                <label class="label" for="category_id">In category</label>
                <select id="category_id" v-model="form.category_id" class="input" required>
                    <option :value="null" disabled>Choose a category</option>
                    <option v-for="category in categories" :key="category.id" :value="category.id">{{ category.emoji }} {{ category.name }}</option>
                </select>
                <p v-if="form.errors.category_id" class="field-error">{{ form.errors.category_id }}</p>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="label" for="xp_bonus">Bonus XP</label>
                    <input id="xp_bonus" v-model.number="form.xp_bonus" type="number" min="0" max="1000" class="input" required />
                </div>
                <div>
                    <label class="label" for="sort_order">Order</label>
                    <input id="sort_order" v-model.number="form.sort_order" type="number" min="0" max="999" class="input" required />
                </div>
            </div>

            <label class="flex cursor-pointer items-center gap-3 text-sm font-medium">
                <input v-model="form.is_active" type="checkbox" class="size-4 accent-flame" />
                Active (can be earned and is shown to developers)
            </label>

            <div class="flex justify-end gap-3 pt-2">
                <button type="button" class="btn btn-ghost" @click="editing = null">Cancel</button>
                <button type="submit" class="btn btn-primary" :disabled="form.processing">Save</button>
            </div>
        </form>
    </Modal>
</template>
