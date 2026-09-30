<script setup>
import { ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { Pencil, Plus, Trash2 } from '@lucide/vue';
import AdminNav from '@/components/AdminNav.vue';
import CategoryChip from '@/components/CategoryChip.vue';
import Modal from '@/components/Modal.vue';

const props = defineProps({
    categories: { type: Array, required: true },
});

const editing = ref(null); // null = closed, {} = new, { id, … } = existing
const form = useForm({ name: '', emoji: '🧠', color: '#FF6B2C', description: '', sort_order: 0, is_active: true });

const open = (category = null) => {
    form.clearErrors();
    form.defaults({
        name: category?.name ?? '',
        emoji: category?.emoji ?? '🧠',
        color: category?.color ?? '#FF6B2C',
        description: category?.description ?? '',
        sort_order: category?.sort_order ?? props.categories.length + 1,
        is_active: category?.is_active ?? true,
    });
    form.reset();
    editing.value = category ?? {};
};

const save = () => {
    const options = { preserveScroll: true, onSuccess: () => (editing.value = null) };
    if (editing.value.id) {
        form.put(route('admin.categories.update', editing.value.id), options);
    } else {
        form.post(route('admin.categories.store'), options);
    }
};

const remove = (category) => router.delete(route('admin.categories.destroy', category.id), { preserveScroll: true });
</script>

<template>
    <Head title="Categories · Admin" />
    <AdminNav />

    <header class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold sm:text-3xl">Categories</h1>
            <p class="mt-1 text-soft">The topics challenges are grouped under.</p>
        </div>
        <button type="button" class="btn btn-primary" @click="open()"><Plus :size="18" :stroke-width="2.5" /> New category</button>
    </header>

    <section class="card mt-6 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="table">
                <thead>
                    <tr><th>Category</th><th>Description</th><th class="text-right!">Challenges</th><th>Visible</th><th><span class="sr-only">Actions</span></th></tr>
                </thead>
                <tbody>
                    <tr v-for="category in categories" :key="category.id">
                        <td><CategoryChip :category="category" /></td>
                        <td class="max-w-md text-soft">{{ category.description ?? '—' }}</td>
                        <td class="num text-right">{{ category.challenges }}</td>
                        <td>
                            <span class="pill" :class="category.is_active ? 'bg-mint/12 text-mint' : 'bg-raised text-faint'">{{ category.is_active ? 'Shown' : 'Hidden' }}</span>
                        </td>
                        <td>
                            <div class="flex justify-end gap-1">
                                <button type="button" class="btn btn-quiet btn-sm px-2!" aria-label="Edit" title="Edit" @click="open(category)"><Pencil :size="16" /></button>
                                <button v-if="!category.challenges" type="button" class="btn btn-quiet btn-sm px-2! hover:text-rose!" aria-label="Delete" title="Delete" @click="remove(category)">
                                    <Trash2 :size="16" />
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>

    <Modal :show="editing !== null" max-width="30rem" @close="editing = null">
        <form v-if="editing" class="space-y-5" @submit.prevent="save">
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-xl font-extrabold">{{ editing.id ? 'Edit category' : 'New category' }}</h2>
                <CategoryChip :category="{ name: form.name || 'Preview', emoji: form.emoji, color: form.color }" />
            </div>

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
            <p v-if="form.errors.emoji" class="field-error -mt-3!">{{ form.errors.emoji }}</p>

            <div>
                <label class="label" for="description">Description</label>
                <input id="description" v-model="form.description" type="text" class="input" maxlength="255" placeholder="What kind of questions belong here?" />
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="label" for="color">Colour</label>
                    <div class="flex items-center gap-2">
                        <input id="color" v-model="form.color" type="color" class="h-11 w-14 cursor-pointer rounded-xl border border-line bg-bg p-1" />
                        <input v-model="form.color" type="text" class="input font-mono text-sm!" aria-label="Colour hex code" :aria-invalid="!!form.errors.color" />
                    </div>
                    <p v-if="form.errors.color" class="field-error">Use a hex colour like #FF6B2C.</p>
                </div>
                <div>
                    <label class="label" for="sort_order">Order</label>
                    <input id="sort_order" v-model.number="form.sort_order" type="number" min="0" max="999" class="input" required />
                </div>
            </div>

            <label class="flex cursor-pointer items-center gap-3 text-sm font-medium">
                <input v-model="form.is_active" type="checkbox" class="size-4 accent-flame" />
                Show this category to developers
            </label>

            <div class="flex justify-end gap-3 pt-2">
                <button type="button" class="btn btn-ghost" @click="editing = null">Cancel</button>
                <button type="submit" class="btn btn-primary" :disabled="form.processing">Save</button>
            </div>
        </form>
    </Modal>
</template>
