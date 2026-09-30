<script setup>
import { computed } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { Flame, ShieldCheck } from '@lucide/vue';
import AdminNav from '@/components/AdminNav.vue';
import Avatar from '@/components/Avatar.vue';
import { dayLabel, formatNumber } from '@/lib/format';

defineProps({
    users: { type: Array, required: true },
});

const me = computed(() => usePage().props.auth.user);

const setAdmin = (user, isAdmin) =>
    router.put(route('admin.users.update', user.id), { is_admin: isAdmin }, { preserveScroll: true });
</script>

<template>
    <Head title="Team · Admin" />
    <AdminNav />

    <header>
        <h1 class="text-2xl font-extrabold sm:text-3xl">Team</h1>
        <p class="mt-1 text-soft">Who is playing and how it is going. Use this to encourage, not to evaluate.</p>
    </header>

    <section class="card mt-6 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="table">
                <thead>
                    <tr>
                        <th>Developer</th>
                        <th class="text-right!">Level</th>
                        <th class="text-right!">XP</th>
                        <th class="text-right!">Streak</th>
                        <th class="text-right!">Answers</th>
                        <th class="text-right!">Correct</th>
                        <th>Last played</th>
                        <th>Admin</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="user in users" :key="user.id">
                        <td class="min-w-60">
                            <div class="flex items-center gap-3">
                                <Avatar :user="user" :size="34" />
                                <div class="min-w-0">
                                    <p class="truncate font-semibold">{{ user.name }} <span v-if="user.id === me.id" class="text-xs font-normal text-faint">(you)</span></p>
                                    <p class="truncate text-xs text-faint">{{ user.email }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="num text-right">{{ user.level }}</td>
                        <td class="num text-right font-semibold text-gold">{{ formatNumber(user.xp) }}</td>
                        <td class="text-right">
                            <span class="num inline-flex items-center gap-1 font-semibold" :class="user.streak > 0 ? 'text-flame' : 'text-faint'">
                                <Flame :size="14" :stroke-width="2.5" /> {{ user.streak }}
                            </span>
                        </td>
                        <td class="num text-right">{{ user.attempts }}</td>
                        <td class="num text-right">{{ user.accuracy === null ? '—' : `${user.accuracy}%` }}</td>
                        <td class="whitespace-nowrap text-soft">{{ user.last_completed_on ? dayLabel(user.last_completed_on) : 'Not yet' }}</td>
                        <td>
                            <button
                                type="button"
                                class="pill border transition-colors"
                                :class="user.is_admin ? 'border-sky/30 bg-sky/12 text-sky' : 'border-line text-faint hover:text-ink'"
                                :disabled="user.id === me.id"
                                :title="user.id === me.id ? 'You cannot remove your own admin access' : user.is_admin ? 'Remove admin access' : 'Make admin'"
                                @click="setAdmin(user, !user.is_admin)"
                            >
                                <ShieldCheck :size="13" :stroke-width="2.5" /> {{ user.is_admin ? 'Admin' : 'Make admin' }}
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>
</template>
