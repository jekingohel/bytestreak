<script setup>
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ArrowRight, ShieldCheck } from '@lucide/vue';
import Avatar from '@/components/Avatar.vue';
import { formatNumber } from '@/lib/format';

defineProps({
    devUsers: { type: Array, default: () => [] },
});

const form = useForm({ email: '', password: '', remember: true });

const submit = () => form.post(route('login.store'), { onFinish: () => form.reset('password') });
const devLogin = (id) => router.post(route('dev-login', id));
</script>

<template>
    <Head title="Log in" />

    <h2 class="text-3xl font-extrabold">Welcome back</h2>
    <p class="mt-2 text-soft">Today's challenge is waiting for you.</p>

    <form class="mt-8 space-y-5" @submit.prevent="submit">
        <div>
            <label class="label" for="email">Work email</label>
            <input id="email" v-model="form.email" type="email" class="input" autocomplete="username" required autofocus :aria-invalid="!!form.errors.email" />
            <p v-if="form.errors.email" class="field-error">{{ form.errors.email }}</p>
        </div>

        <div>
            <label class="label" for="password">Password</label>
            <input id="password" v-model="form.password" type="password" class="input" autocomplete="current-password" required :aria-invalid="!!form.errors.password" />
            <p v-if="form.errors.password" class="field-error">{{ form.errors.password }}</p>
        </div>

        <label class="flex items-center gap-2.5 text-sm text-soft">
            <input v-model="form.remember" type="checkbox" class="size-4 rounded accent-flame" />
            Keep me signed in
        </label>

        <button type="submit" class="btn btn-primary btn-lg w-full" :disabled="form.processing">
            Log in <ArrowRight :size="18" :stroke-width="2.5" />
        </button>
    </form>

    <p class="mt-6 text-center text-sm text-soft">
        New here?
        <Link :href="route('register')" class="font-semibold text-flame hover:underline">Create your account</Link>
    </p>

    <!-- Only rendered in the local environment -->
    <section v-if="devUsers.length" class="mt-10 rounded-2xl border border-dashed border-line p-4">
        <p class="eyebrow">Local dev · one-click sign in</p>
        <div class="mt-3 grid gap-2">
            <button
                v-for="user in devUsers"
                :key="user.id"
                type="button"
                class="flex items-center gap-3 rounded-xl border border-line bg-surface px-3 py-2 text-left text-sm transition-colors hover:border-flame/50"
                @click="devLogin(user.id)"
            >
                <Avatar :user="user" :size="28" />
                <span class="min-w-0 flex-1 truncate font-semibold">{{ user.name }}</span>
                <span v-if="user.is_admin" class="pill bg-sky/12 text-sky"><ShieldCheck :size="12" /> Admin</span>
                <span class="num text-xs font-semibold text-gold">{{ formatNumber(user.xp) }} XP</span>
            </button>
        </div>
    </section>
</template>
