<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowRight } from '@lucide/vue';

defineProps({
    allowedDomain: { type: String, default: null },
});

const form = useForm({ name: '', email: '', password: '', password_confirmation: '' });

const submit = () => form.post(route('register.store'), { onFinish: () => form.reset('password', 'password_confirmation') });
</script>

<template>
    <Head title="Create account" />

    <h2 class="text-3xl font-extrabold">Start your streak</h2>
    <p class="mt-2 text-soft">Thirty seconds to sign up. Two minutes a day after that.</p>

    <form class="mt-8 space-y-5" @submit.prevent="submit">
        <div>
            <label class="label" for="name">Your name</label>
            <input id="name" v-model="form.name" type="text" class="input" autocomplete="name" required autofocus :aria-invalid="!!form.errors.name" />
            <p v-if="form.errors.name" class="field-error">{{ form.errors.name }}</p>
        </div>

        <div>
            <label class="label" for="email">Work email</label>
            <input id="email" v-model="form.email" type="email" class="input" autocomplete="username" required :placeholder="allowedDomain ? `you@${allowedDomain}` : ''" :aria-invalid="!!form.errors.email" />
            <p v-if="form.errors.email" class="field-error">{{ form.errors.email }}</p>
        </div>

        <div>
            <label class="label" for="password">Password</label>
            <input id="password" v-model="form.password" type="password" class="input" autocomplete="new-password" required :aria-invalid="!!form.errors.password" />
            <p v-if="form.errors.password" class="field-error">{{ form.errors.password }}</p>
            <p v-else class="mt-1.5 text-xs text-faint">At least 8 characters.</p>
        </div>

        <div>
            <label class="label" for="password_confirmation">Confirm password</label>
            <input id="password_confirmation" v-model="form.password_confirmation" type="password" class="input" autocomplete="new-password" required />
        </div>

        <button type="submit" class="btn btn-primary btn-lg w-full" :disabled="form.processing">
            Create account <ArrowRight :size="18" :stroke-width="2.5" />
        </button>
    </form>

    <p class="mt-6 text-center text-sm text-soft">
        Already playing?
        <Link :href="route('login')" class="font-semibold text-flame hover:underline">Log in</Link>
    </p>
</template>
