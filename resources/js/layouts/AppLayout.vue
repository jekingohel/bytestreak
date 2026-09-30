<script setup>
import { computed, ref } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { Award, Flame, House, Library, LogOut, Moon, ShieldCheck, Snowflake, Sun, Trophy, User, Zap } from '@lucide/vue';
import Avatar from '@/components/Avatar.vue';
import LevelRing from '@/components/LevelRing.vue';
import Logo from '@/components/Logo.vue';
import Toasts from '@/components/Toasts.vue';
import { formatNumber } from '@/lib/format';
import { useTheme } from '@/lib/theme';

const page = usePage();
const user = computed(() => page.props.auth.user);
const { theme, toggle } = useTheme();
const menuOpen = ref(false);

const waiting = computed(() => user.value.has_today && !user.value.done_today);
const playingToday = computed(() => page.component === 'challenges/Show' && page.props.challenge?.is_today);

const nav = computed(() => [
    { label: 'Home', short: 'Home', href: route('dashboard'), icon: House, active: page.component === 'Dashboard' },
    { label: "Today's challenge", short: 'Today', href: route('today'), icon: Zap, active: playingToday.value, dot: waiting.value },
    {
        label: 'Archive',
        short: 'Archive',
        href: route('archive'),
        icon: Library,
        active: page.component === 'Archive' || (page.component === 'challenges/Show' && !playingToday.value),
    },
    { label: 'Leaderboard', short: 'Ranks', href: route('leaderboard'), icon: Trophy, active: page.component === 'Leaderboard' },
    { label: 'Badges', short: 'Badges', href: route('badges'), icon: Award, active: page.component === 'Badges' },
]);

const onAdmin = computed(() => page.component.startsWith('admin/'));
</script>

<template>
    <div class="min-h-dvh lg:pl-64">
        <!-- Desktop sidebar -->
        <aside class="fixed inset-y-0 left-0 z-40 hidden w-64 flex-col border-r border-line bg-surface/70 px-4 py-6 backdrop-blur-xl lg:flex">
            <Link :href="route('dashboard')" class="px-2 text-[0.95rem]">
                <Logo :size="30" />
            </Link>

            <nav class="mt-9 flex flex-col gap-1" aria-label="Main">
                <Link
                    v-for="item in nav"
                    :key="item.label"
                    :href="item.href"
                    class="group relative flex h-11 items-center gap-3 rounded-xl px-3 text-[0.9375rem] font-semibold transition-colors"
                    :class="item.active ? 'bg-flame/12 text-flame' : 'text-soft hover:bg-raised hover:text-ink'"
                    :aria-current="item.active ? 'page' : undefined"
                >
                    <component :is="item.icon" :size="19" :stroke-width="2.25" />
                    {{ item.label }}
                    <span v-if="item.dot" class="ml-auto size-2 animate-beacon rounded-full bg-flame" title="Waiting for you" />
                </Link>
            </nav>

            <div class="mt-6 border-t border-line pt-4">
                <p class="eyebrow px-3 pb-2">You</p>
                <Link
                    :href="route('profile')"
                    class="flex h-11 items-center gap-3 rounded-xl px-3 text-[0.9375rem] font-semibold transition-colors"
                    :class="page.component === 'Profile' ? 'bg-flame/12 text-flame' : 'text-soft hover:bg-raised hover:text-ink'"
                >
                    <User :size="19" :stroke-width="2.25" /> Profile
                </Link>
                <Link
                    v-if="user.is_admin"
                    :href="route('admin.dashboard')"
                    class="flex h-11 items-center gap-3 rounded-xl px-3 text-[0.9375rem] font-semibold transition-colors"
                    :class="onAdmin ? 'bg-flame/12 text-flame' : 'text-soft hover:bg-raised hover:text-ink'"
                >
                    <ShieldCheck :size="19" :stroke-width="2.25" /> Admin
                </Link>
            </div>

            <!-- Level card: always in the corner of your eye -->
            <Link :href="route('profile')" class="mt-auto block rounded-2xl border border-line bg-raised/60 p-3.5 transition-colors hover:border-gold/40">
                <div class="flex items-center gap-3">
                    <LevelRing :level="user.level.level" :percent="user.level.percent" :size="42" />
                    <div class="min-w-0">
                        <p class="truncate text-sm font-bold">{{ user.level.title }}</p>
                        <p class="text-xs text-faint"><span class="num font-semibold text-gold">{{ formatNumber(user.level.needed) }} XP</span> to level {{ user.level.level + 1 }}</p>
                    </div>
                </div>
            </Link>
        </aside>

        <!-- Top bar -->
        <header class="sticky top-0 z-30 border-b border-line bg-bg/80 backdrop-blur-xl">
            <div class="mx-auto flex h-16 max-w-6xl items-center gap-3 px-4 sm:px-6 lg:px-8">
                <Link :href="route('dashboard')" class="lg:hidden" aria-label="ByteStreak home">
                    <Logo :size="28" :wordmark="false" />
                </Link>

                <div class="ml-auto flex items-center gap-2">
                    <Link
                        :href="route('today')"
                        class="pill h-9! gap-1.5 border px-3! text-sm!"
                        :class="user.streak > 0 ? 'border-flame/30 bg-flame/10 text-flame' : 'border-line bg-surface text-faint'"
                        :title="user.streak > 0 ? `${user.streak}-day streak${waiting ? ' — answer today to keep it' : ''}` : 'No streak yet — answer today to start one'"
                    >
                        <Flame :size="17" :stroke-width="2.5" :class="user.streak > 0 ? 'animate-flicker fill-flame/30' : ''" />
                        <span class="num font-bold">{{ user.streak }}</span>
                        <span v-if="waiting && user.streak > 0" class="size-1.5 animate-beacon rounded-full bg-flame" />
                    </Link>

                    <span
                        v-if="user.freezes > 0"
                        class="pill hidden h-9! border border-sky/30 bg-sky/10 px-3! text-sm! text-sky sm:inline-flex"
                        :title="`${user.freezes} streak freeze${user.freezes === 1 ? '' : 's'}: each one covers a missed day`"
                    >
                        <Snowflake :size="16" :stroke-width="2.5" />
                        <span class="num font-bold">{{ user.freezes }}</span>
                    </span>

                    <Link :href="route('profile')" class="pill h-9! border border-gold/30 bg-gold/10 px-3! text-sm! text-gold" :title="`${formatNumber(user.xp)} XP in total`">
                        <Zap :size="16" :stroke-width="2.5" class="fill-gold/30" />
                        <span class="num font-bold">{{ formatNumber(user.xp) }}</span>
                    </Link>

                    <div class="relative ml-1">
                        <button
                            type="button"
                            class="flex items-center rounded-full ring-2 ring-transparent transition hover:ring-line"
                            aria-haspopup="menu"
                            :aria-expanded="menuOpen"
                            aria-label="Account menu"
                            @click="menuOpen = !menuOpen"
                        >
                            <Avatar :user="user" :size="36" />
                        </button>

                        <template v-if="menuOpen">
                            <button type="button" class="fixed inset-0 z-40 cursor-default" aria-hidden="true" tabindex="-1" @click="menuOpen = false" />
                            <div class="card absolute right-0 z-50 mt-2 w-60 animate-rise p-2" role="menu" @click="menuOpen = false">
                                <div class="px-3 py-2">
                                    <p class="truncate text-sm font-bold">{{ user.name }}</p>
                                    <p class="truncate text-xs text-faint">Level {{ user.level.level }} · {{ user.level.title }}</p>
                                </div>
                                <div class="my-1 border-t border-line" />
                                <Link :href="route('profile')" class="flex h-10 items-center gap-2.5 rounded-lg px-3 text-sm font-medium text-soft hover:bg-raised hover:text-ink" role="menuitem">
                                    <User :size="16" /> Profile &amp; settings
                                </Link>
                                <Link v-if="user.is_admin" :href="route('admin.dashboard')" class="flex h-10 items-center gap-2.5 rounded-lg px-3 text-sm font-medium text-soft hover:bg-raised hover:text-ink" role="menuitem">
                                    <ShieldCheck :size="16" /> Admin
                                </Link>
                                <button type="button" class="flex h-10 w-full items-center gap-2.5 rounded-lg px-3 text-sm font-medium text-soft hover:bg-raised hover:text-ink" role="menuitem" @click="toggle">
                                    <component :is="theme === 'dark' ? Sun : Moon" :size="16" />
                                    {{ theme === 'dark' ? 'Light theme' : 'Dark theme' }}
                                </button>
                                <Link :href="route('logout')" method="post" as="button" class="flex h-10 w-full items-center gap-2.5 rounded-lg px-3 text-sm font-medium text-soft hover:bg-raised hover:text-ink" role="menuitem">
                                    <LogOut :size="16" /> Log out
                                </Link>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </header>

        <main class="mx-auto w-full max-w-6xl px-4 pt-6 pb-28 sm:px-6 lg:px-8 lg:pt-8 lg:pb-16">
            <div :key="page.component" class="animate-rise">
                <slot />
            </div>
        </main>

        <!-- Mobile tab bar -->
        <nav class="fixed inset-x-0 bottom-0 z-40 border-t border-line bg-surface/90 pb-[env(safe-area-inset-bottom)] backdrop-blur-xl lg:hidden" aria-label="Main">
            <div class="mx-auto grid max-w-md grid-cols-5">
                <Link
                    v-for="item in nav"
                    :key="item.label"
                    :href="item.href"
                    class="relative flex h-16 flex-col items-center justify-center gap-1 text-[0.6875rem] font-semibold"
                    :class="item.active ? 'text-flame' : 'text-faint'"
                    :aria-current="item.active ? 'page' : undefined"
                >
                    <span class="relative">
                        <component :is="item.icon" :size="21" :stroke-width="2.25" />
                        <span v-if="item.dot" class="absolute -top-0.5 -right-1 size-2 animate-beacon rounded-full bg-flame" />
                    </span>
                    {{ item.short }}
                </Link>
            </div>
        </nav>

        <Toasts />
    </div>
</template>
