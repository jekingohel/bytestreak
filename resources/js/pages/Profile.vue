<script setup>
import { computed, ref, watch } from 'vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { Award, Check, Copy, Flame, KeyRound, LogOut, Moon, Sun, Target, Trash2, Trophy, Zap } from '@lucide/vue';
import Avatar from '@/components/Avatar.vue';
import Heatmap from '@/components/Heatmap.vue';
import LevelRing from '@/components/LevelRing.vue';
import ProgressBar from '@/components/ProgressBar.vue';
import { formatNumber, plural, timeAgo } from '@/lib/format';
import { useTheme } from '@/lib/theme';

const props = defineProps({
    summary: { type: Object, required: true },
    heatmap: { type: Object, required: true },
    mastery: { type: Array, required: true },
    rank: { type: Number, default: null },
    joined: { type: String, required: true },
    xpHistory: { type: Array, required: true },
    // Admins only: { base_url, choices_days, tokens }
    apiTokens: { type: Object, default: null },
});

const user = computed(() => usePage().props.auth.user);
const { theme, toggle } = useTheme();

const profile = useForm({ name: user.value.name, show_on_leaderboard: user.value.show_on_leaderboard });
const password = useForm({ current_password: '', password: '', password_confirmation: '' });

const saveProfile = () => profile.patch(route('profile.update'), { preserveScroll: true });
const savePassword = () =>
    password.put(route('profile.password'), { preserveScroll: true, onSuccess: () => password.reset(), onError: () => password.reset('current_password') });

// Personal API tokens. The plain-text token is flashed once, right after it is created.
const tokenForm = useForm({ name: '', expires_in_days: 90 });
const createToken = () => tokenForm.post(route('profile.tokens.store'), { preserveScroll: true, onSuccess: () => tokenForm.reset('name') });
const revokeToken = (token) => {
    if (confirm(`Revoke “${token.name}”? Anything using it loses access immediately.`)) {
        router.delete(route('profile.tokens.destroy', token.id), { preserveScroll: true });
    }
};

const page = usePage();
const newToken = ref(null);
const copied = ref(false);
watch(
    () => page.flash?.token,
    (token) => {
        if (!token) return;
        newToken.value = token;
        copied.value = false;
    },
    { immediate: true },
);
const copyToken = async () => {
    await navigator.clipboard.writeText(newToken.value.plain_text);
    copied.value = true;
};

const lifetimeLabel = (days) => (days === 0 ? 'Never expires' : days >= 365 ? `${days / 365} year` : `${days} days`);
const dateLabel = (iso) => new Intl.DateTimeFormat('en', { day: 'numeric', month: 'short', year: 'numeric' }).format(new Date(iso));

const joinedLabel = computed(() => new Intl.DateTimeFormat('en', { month: 'long', year: 'numeric' }).format(new Date(props.joined)));

const stats = computed(() => [
    { label: 'Total XP', value: formatNumber(user.value.xp), icon: Zap, colour: 'text-gold' },
    { label: 'Current streak', value: user.value.streak, icon: Flame, colour: 'text-flame' },
    { label: 'Longest streak', value: user.value.longest_streak, icon: Flame, colour: 'text-ember' },
    { label: 'Accuracy', value: props.summary.accuracy === null ? '—' : `${props.summary.accuracy}%`, icon: Target, colour: 'text-mint' },
    { label: 'Completed', value: formatNumber(props.summary.completed), icon: Check, colour: 'text-sky' },
    { label: 'Badges', value: props.summary.badges, icon: Award, colour: 'text-ember' },
]);

const accuracyColour = (value) => (value >= 75 ? 'var(--mint)' : value >= 50 ? 'var(--gold)' : 'var(--rose)');
const REASON_ICONS = { correct: Check, participation: Flame, streak_bonus: Flame, badge: Award };
</script>

<template>
    <Head title="Profile" />

    <!-- Identity -->
    <section class="card relative overflow-hidden p-6 sm:p-8">
        <div class="pointer-events-none absolute -top-24 -right-16 size-72 rounded-full bg-gold/10 blur-3xl" aria-hidden="true" />
        <div class="relative flex flex-wrap items-center gap-5">
            <Avatar :user="user" :size="80" />
            <div class="min-w-0 flex-1">
                <h1 class="truncate text-2xl font-extrabold sm:text-3xl">{{ user.name }}</h1>
                <p class="mt-1 truncate text-sm text-faint">{{ user.email }} · playing since {{ joinedLabel }}</p>
                <p v-if="rank" class="pill mt-3 border border-gold/30 bg-gold/10 text-gold"><Trophy :size="13" :stroke-width="2.5" /> #{{ rank }} all time</p>
            </div>
            <div class="flex items-center gap-4">
                <LevelRing :level="user.level.level" :percent="user.level.percent" :size="72" :stroke="6" />
                <div>
                    <p class="eyebrow">Level {{ user.level.level }}</p>
                    <p class="text-lg font-extrabold">{{ user.level.title }}</p>
                    <p class="text-xs text-faint"><span class="num font-semibold text-soft">{{ formatNumber(user.level.needed) }} XP</span> to next level</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Numbers -->
    <section class="mt-6 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
        <div v-for="stat in stats" :key="stat.label" class="card p-4">
            <p class="flex items-center gap-1.5 text-xs font-semibold text-faint">
                <component :is="stat.icon" :size="14" :class="stat.colour" /> {{ stat.label }}
            </p>
            <p class="num mt-2 text-2xl font-extrabold">{{ stat.value }}</p>
        </div>
    </section>

    <div class="mt-6 grid gap-6 lg:grid-cols-[minmax(0,1fr)_340px]">
        <div class="space-y-6">
            <!-- Activity -->
            <section class="card p-6">
                <div class="flex flex-wrap items-baseline justify-between gap-2">
                    <h2 class="text-lg font-bold">Activity</h2>
                    <p class="text-sm text-faint">
                        {{ plural(heatmap.total, 'challenge') }} on {{ plural(heatmap.active_days, 'day') }} in the last {{ heatmap.weeks.length }} weeks
                    </p>
                </div>
                <Heatmap class="mt-5" :weeks="heatmap.weeks" />
            </section>

            <!-- Mastery -->
            <section class="card p-6">
                <h2 class="text-lg font-bold">Topic by topic</h2>
                <div class="mt-5 grid gap-x-8 gap-y-4 sm:grid-cols-2">
                    <div v-for="category in mastery" :key="category.id" :class="category.attempts === 0 ? 'opacity-55' : ''">
                        <div class="mb-1.5 flex items-center justify-between text-sm">
                            <span class="font-semibold"><span aria-hidden="true">{{ category.emoji }}</span> {{ category.name }}</span>
                            <span v-if="category.attempts" class="text-faint">
                                <span class="num font-semibold text-soft">{{ category.accuracy }}%</span> · {{ category.correct }}/{{ category.attempts }}
                            </span>
                            <span v-else class="text-xs text-faint">Not tried yet</span>
                        </div>
                        <ProgressBar :percent="category.accuracy ?? 0" :colour="accuracyColour(category.accuracy ?? 0)" :height="6" />
                    </div>
                </div>
            </section>

            <!-- Settings -->
            <section class="card p-6">
                <h2 class="text-lg font-bold">Settings</h2>

                <form class="mt-5 grid gap-5 sm:grid-cols-2" @submit.prevent="saveProfile">
                    <div>
                        <label class="label" for="name">Display name</label>
                        <input id="name" v-model="profile.name" type="text" class="input" required :aria-invalid="!!profile.errors.name" />
                        <p v-if="profile.errors.name" class="field-error">{{ profile.errors.name }}</p>
                    </div>
                    <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-line p-3.5 sm:mt-6">
                        <input v-model="profile.show_on_leaderboard" type="checkbox" class="mt-0.5 size-4 accent-flame" />
                        <span>
                            <span class="block text-sm font-semibold">Show me on the leaderboard</span>
                            <span class="block text-xs leading-relaxed text-faint">Turn this off to keep your XP private. Your own progress still works.</span>
                        </span>
                    </label>
                    <div class="sm:col-span-2">
                        <button type="submit" class="btn btn-primary" :disabled="profile.processing || !profile.isDirty">Save profile</button>
                    </div>
                </form>

                <form class="mt-8 grid gap-5 border-t border-line pt-6 sm:grid-cols-3" @submit.prevent="savePassword">
                    <div>
                        <label class="label" for="current_password">Current password</label>
                        <input id="current_password" v-model="password.current_password" type="password" class="input" autocomplete="current-password" required :aria-invalid="!!password.errors.current_password" />
                        <p v-if="password.errors.current_password" class="field-error">{{ password.errors.current_password }}</p>
                    </div>
                    <div>
                        <label class="label" for="new_password">New password</label>
                        <input id="new_password" v-model="password.password" type="password" class="input" autocomplete="new-password" required :aria-invalid="!!password.errors.password" />
                        <p v-if="password.errors.password" class="field-error">{{ password.errors.password }}</p>
                    </div>
                    <div>
                        <label class="label" for="password_confirmation">Confirm new password</label>
                        <input id="password_confirmation" v-model="password.password_confirmation" type="password" class="input" autocomplete="new-password" required />
                    </div>
                    <div class="sm:col-span-3">
                        <button type="submit" class="btn btn-ghost" :disabled="password.processing">Change password</button>
                    </div>
                </form>

                <div class="mt-8 flex flex-wrap gap-3 border-t border-line pt-6">
                    <button type="button" class="btn btn-ghost" @click="toggle">
                        <component :is="theme === 'dark' ? Sun : Moon" :size="17" />
                        {{ theme === 'dark' ? 'Switch to light theme' : 'Switch to dark theme' }}
                    </button>
                    <Link :href="route('logout')" method="post" as="button" class="btn btn-ghost"><LogOut :size="17" /> Log out</Link>
                </div>
            </section>

            <!-- API tokens (admins) -->
            <section v-if="apiTokens" class="card p-6">
                <h2 class="flex items-center gap-2 text-lg font-bold"><KeyRound :size="19" class="text-gold" /> API tokens</h2>
                <p class="mt-1 text-sm leading-relaxed text-soft">
                    Lets a script or an MCP server act as you on the ByteStreak API, including adding questions. Send it as
                    <code class="text-xs">Authorization: Bearer &lt;token&gt;</code> to <code class="text-xs">{{ apiTokens.base_url }}</code>.
                </p>

                <div v-if="newToken" class="mt-5 rounded-xl border border-gold/40 bg-gold/8 p-4" role="status">
                    <p class="text-sm font-semibold">“{{ newToken.name }}” is ready. Copy it now: it will not be shown again.</p>
                    <div class="mt-3 flex flex-wrap items-center gap-2">
                        <code class="min-w-0 flex-1 rounded-lg border border-line bg-surface px-3 py-2 text-xs break-all select-all">{{ newToken.plain_text }}</code>
                        <button type="button" class="btn btn-ghost btn-sm" @click="copyToken">
                            <component :is="copied ? Check : Copy" :size="15" /> {{ copied ? 'Copied' : 'Copy' }}
                        </button>
                    </div>
                    <button type="button" class="mt-3 text-xs font-semibold text-faint hover:text-soft" @click="newToken = null">I have saved it</button>
                </div>

                <form class="mt-5 grid gap-4 sm:grid-cols-[minmax(0,1fr)_180px_auto] sm:items-end" @submit.prevent="createToken">
                    <div>
                        <label class="label" for="token_name">Token name</label>
                        <input id="token_name" v-model="tokenForm.name" type="text" class="input" placeholder="e.g. ByteStreak MCP" maxlength="60" required :aria-invalid="!!tokenForm.errors.name" />
                        <p v-if="tokenForm.errors.name" class="field-error">{{ tokenForm.errors.name }}</p>
                    </div>
                    <div>
                        <label class="label" for="token_expiry">Expires</label>
                        <select id="token_expiry" v-model.number="tokenForm.expires_in_days" class="input">
                            <option v-for="days in apiTokens.choices_days" :key="days" :value="days">{{ days === 0 ? 'Never' : `In ${lifetimeLabel(days)}` }}</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary" :disabled="tokenForm.processing || !tokenForm.name.trim()">Create token</button>
                </form>

                <ul v-if="apiTokens.tokens.length" class="mt-6 divide-y divide-line border-t border-line">
                    <li v-for="token in apiTokens.tokens" :key="token.id" class="flex flex-wrap items-center gap-3 py-3">
                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-sm font-semibold">{{ token.name }}</span>
                            <span class="text-xs text-faint">
                                Created {{ dateLabel(token.created_at) }} ·
                                {{ token.last_used_at ? `last used ${timeAgo(token.last_used_at)}` : 'never used' }} ·
                                <span :class="token.expired ? 'font-semibold text-rose' : ''">
                                    {{ token.expires_at ? `${token.expired ? 'expired' : 'expires'} ${dateLabel(token.expires_at)}` : 'never expires' }}
                                </span>
                            </span>
                        </span>
                        <button type="button" class="btn btn-danger btn-sm" @click="revokeToken(token)"><Trash2 :size="14" /> Revoke</button>
                    </li>
                </ul>
                <p v-else class="mt-6 border-t border-line pt-4 text-sm text-faint">No tokens yet.</p>
            </section>
        </div>

        <!-- XP history -->
        <aside>
            <section class="card p-6">
                <h2 class="text-lg font-bold">XP history</h2>
                <ul v-if="xpHistory.length" class="mt-3 divide-y divide-line">
                    <li v-for="entry in xpHistory" :key="entry.id" class="flex items-start gap-3 py-3">
                        <span class="mt-0.5 grid size-7 shrink-0 place-items-center rounded-full bg-gold/12 text-gold">
                            <component :is="REASON_ICONS[entry.reason] ?? Zap" :size="14" :stroke-width="2.5" />
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="block text-sm leading-snug font-medium">{{ entry.description }}</span>
                            <span class="text-xs text-faint">{{ timeAgo(entry.created_at) }}</span>
                        </span>
                        <span class="num text-sm font-bold text-gold">+{{ entry.amount }}</span>
                    </li>
                </ul>
                <p v-else class="mt-3 text-sm leading-relaxed text-soft">
                    Every XP you earn is listed here. Answer your first challenge to get started.
                </p>
            </section>
        </aside>
    </div>
</template>
