<script setup>
import { ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { ArrowRight, Award, Check, Flame, Moon, Snowflake, Sun, Trophy, X, Zap } from '@lucide/vue';
import CategoryChip from '@/components/CategoryChip.vue';
import CodeBlock from '@/components/CodeBlock.vue';
import Logo from '@/components/Logo.vue';
import { celebrate } from '@/lib/celebrate';
import { plural } from '@/lib/format';
import { useTheme } from '@/lib/theme';

defineProps({
    categories: { type: Array, required: true },
    stats: { type: Object, required: true },
});

const { theme, toggle } = useTheme();

// A playable sample so visitors feel the loop before signing up.
const sample = {
    code: '$a = "10";\n$b = 10;\n\nvar_dump($a == $b);\nvar_dump($a === $b);',
    options: ['true, true', 'true, false', 'false, true', 'false, false'],
    answer: 1,
};
const picked = ref(null);

const pick = (index) => {
    if (picked.value !== null) return;
    picked.value = index;
    if (index === sample.answer) celebrate();
};

const steps = [
    { n: '01', title: 'Open today’s challenge', text: 'One short question on PHP, Laravel, SQL, Git, JavaScript and more. It takes about two minutes.' },
    { n: '02', title: 'Lock in your answer', text: 'You see straight away whether you were right, why, and how the rest of the team answered.' },
    { n: '03', title: 'Come back tomorrow', text: 'XP adds up, your streak grows, badges unlock and the weekly leaderboard keeps it friendly.' },
];

const hooks = [
    { icon: Flame, colour: 'text-flame', title: 'Streaks', text: 'Every day you answer keeps the flame burning. Weekends off never break it.' },
    { icon: Snowflake, colour: 'text-sky', title: 'Streak freezes', text: 'Earn a freeze every 7 days. It covers you when a busy day gets in the way.' },
    { icon: Zap, colour: 'text-gold', title: 'XP and levels', text: 'From Hello World to 10x Legend. Harder questions and longer streaks pay more.' },
    { icon: Award, colour: 'text-ember', title: 'Badges', text: 'Bug Hunter, SQL Solver, Week Warrior: small trophies for real habits.' },
    { icon: Trophy, colour: 'text-mint', title: 'Weekly leaderboard', text: 'A fresh race every Monday, so everyone has a shot at the top.' },
];
</script>

<template>
    <Head title="" />

    <div class="relative overflow-hidden">
        <div class="pointer-events-none absolute -top-72 left-1/2 size-[44rem] -translate-x-1/2 rounded-full bg-flame/[0.07] blur-3xl" aria-hidden="true" />

        <header class="relative mx-auto flex h-20 max-w-6xl items-center justify-between px-5 sm:px-8">
            <Logo :size="32" />
            <div class="flex items-center gap-2">
                <button type="button" class="btn btn-quiet px-2.5!" :aria-label="theme === 'dark' ? 'Switch to light theme' : 'Switch to dark theme'" @click="toggle">
                    <component :is="theme === 'dark' ? Sun : Moon" :size="18" />
                </button>
                <Link :href="route('login')" class="btn btn-quiet">Log in</Link>
                <Link :href="route('register')" class="btn btn-primary hidden sm:inline-flex">Get started</Link>
            </div>
        </header>

        <!-- Hero -->
        <section class="relative mx-auto grid max-w-6xl items-center gap-14 px-5 pt-10 pb-20 sm:px-8 lg:grid-cols-[1.05fr_1fr] lg:pt-16 lg:pb-28">
            <div class="animate-rise">
                <p class="pill border border-flame/30 bg-flame/10 text-flame">
                    <Flame :size="14" :stroke-width="2.5" class="animate-flicker" /> Daily developer challenge
                </p>
                <h1 class="mt-6 text-5xl leading-[1.02] font-extrabold sm:text-6xl lg:text-7xl">
                    One byte a day.<br />
                    <span class="flame-text">Keep the streak alive.</span>
                </h1>
                <p class="mt-6 max-w-xl text-lg leading-relaxed text-soft">
                    A two-minute technical challenge every working day. Answer it, learn why, earn XP, and try not to be the one who
                    breaks the streak.
                </p>
                <div class="mt-9 flex flex-wrap items-center gap-4">
                    <Link :href="route('register')" class="btn btn-primary btn-lg">
                        Start your streak <ArrowRight :size="19" :stroke-width="2.5" />
                    </Link>
                    <Link :href="route('login')" class="btn btn-ghost btn-lg">I have an account</Link>
                </div>
                <p v-if="stats.players > 1" class="mt-7 text-sm text-faint">
                    <span class="font-semibold text-soft">{{ plural(stats.players, 'developer') }}</span> playing ·
                    <span class="font-semibold text-soft">{{ plural(stats.challenges, 'challenge') }}</span> in the archive
                </p>
            </div>

            <!-- Playable sample -->
            <div class="relative animate-rise [animation-delay:120ms]">
                <span class="pill absolute -top-4 left-6 z-10 animate-float border border-flame/30 bg-surface text-flame shadow-lg">
                    <Flame :size="14" :stroke-width="2.5" class="fill-flame/30" /> 12-day streak
                </span>
                <span class="pill absolute right-8 -bottom-3.5 z-10 animate-float border border-gold/30 bg-surface text-gold shadow-lg [animation-delay:1.2s]">
                    <Zap :size="14" :stroke-width="2.5" class="fill-gold/30" /> +20 XP
                </span>

                <div class="card p-5 sm:p-6 lg:rotate-1">
                    <div class="flex items-center justify-between">
                        <CategoryChip :category="{ name: 'PHP', emoji: '🐘', color: '#8892F6' }" />
                        <span class="text-xs font-semibold text-faint">Try it · no sign-up</span>
                    </div>
                    <p class="mt-4 font-display text-xl font-bold">What will this PHP code output?</p>
                    <CodeBlock class="mt-4" :code="sample.code" language="php" />

                    <div class="mt-4 grid gap-2.5 sm:grid-cols-2">
                        <button
                            v-for="(option, index) in sample.options"
                            :key="option"
                            type="button"
                            class="flex items-center gap-3 rounded-xl border px-3.5 py-3 text-left font-mono text-sm font-medium transition-all"
                            :class="[
                                picked === null ? 'border-line bg-raised/60 hover:-translate-y-0.5 hover:border-flame/60' : 'cursor-default',
                                picked !== null && index === sample.answer ? 'border-mint bg-mint/12 text-mint' : '',
                                picked === index && index !== sample.answer ? 'animate-shake border-rose bg-rose/12 text-rose' : '',
                                picked !== null && picked !== index && index !== sample.answer ? 'border-line opacity-45' : '',
                            ]"
                            :disabled="picked !== null"
                            @click="pick(index)"
                        >
                            <span class="kbd">{{ 'ABCD'[index] }}</span>
                            <span class="flex-1">{{ option }}</span>
                            <Check v-if="picked !== null && index === sample.answer" :size="16" :stroke-width="3" />
                            <X v-else-if="picked === index" :size="16" :stroke-width="3" />
                        </button>
                    </div>

                    <div v-if="picked !== null" class="mt-4 animate-rise rounded-xl border border-line bg-raised/60 p-4 text-sm leading-relaxed text-soft">
                        <p class="font-bold" :class="picked === sample.answer ? 'text-mint' : 'text-rose'">
                            {{ picked === sample.answer ? 'Correct! That would be +10 XP.' : 'Not quite, and that is how it sticks.' }}
                        </p>
                        <p class="mt-1.5">
                            <code class="font-mono text-ink">==</code> compares values after type juggling, while
                            <code class="font-mono text-ink">===</code> also compares the type.
                        </p>
                        <Link :href="route('register')" class="mt-3 inline-flex items-center gap-1.5 font-semibold text-flame hover:underline">
                            Get one of these every day <ArrowRight :size="15" :stroke-width="2.5" />
                        </Link>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <!-- How it works -->
    <section class="border-y border-line bg-surface/60">
        <div class="mx-auto max-w-6xl px-5 py-20 sm:px-8">
            <p class="eyebrow">How it works</p>
            <h2 class="mt-3 max-w-2xl text-3xl font-extrabold sm:text-4xl">Small enough to do every day. Good enough to remember.</h2>
            <ol class="mt-12 grid gap-6 md:grid-cols-3">
                <li v-for="step in steps" :key="step.n" class="card p-6">
                    <span class="num flame-text text-4xl font-extrabold">{{ step.n }}</span>
                    <h3 class="mt-4 text-lg font-bold">{{ step.title }}</h3>
                    <p class="mt-2 text-sm leading-relaxed text-soft">{{ step.text }}</p>
                </li>
            </ol>
        </div>
    </section>

    <!-- Categories -->
    <section class="mx-auto max-w-6xl px-5 py-20 sm:px-8">
        <div class="grid gap-10 lg:grid-cols-[1fr_1.3fr] lg:items-center">
            <div>
                <p class="eyebrow">What you will practise</p>
                <h2 class="mt-3 text-3xl font-extrabold sm:text-4xl">The things you use at work, in rotation.</h2>
                <p class="mt-4 leading-relaxed text-soft">
                    Predict the output, find the bug, pick the right query or choose the tool you would reach for. A different topic
                    every day of the week.
                </p>
            </div>
            <div class="flex flex-wrap gap-2.5">
                <CategoryChip v-for="category in categories" :key="category.id" :category="category" class="h-10! px-4! text-sm!" />
            </div>
        </div>
    </section>

    <!-- Why it sticks -->
    <section class="border-t border-line bg-surface/60">
        <div class="mx-auto max-w-6xl px-5 py-20 sm:px-8">
            <p class="eyebrow">Why people come back</p>
            <h2 class="mt-3 max-w-2xl text-3xl font-extrabold sm:text-4xl">Built like a game, because habits need a reason.</h2>
            <div class="mt-12 grid gap-5 sm:grid-cols-2 lg:grid-cols-5">
                <article v-for="hook in hooks" :key="hook.title" class="card p-5">
                    <component :is="hook.icon" :size="26" :stroke-width="2.25" :class="hook.colour" />
                    <h3 class="mt-4 font-bold">{{ hook.title }}</h3>
                    <p class="mt-1.5 text-sm leading-relaxed text-soft">{{ hook.text }}</p>
                </article>
            </div>

            <div class="card mt-14 flex flex-col items-center gap-6 bg-linear-to-br from-flame/15 via-surface to-surface p-10 text-center">
                <h2 class="text-3xl font-extrabold sm:text-4xl">Today’s challenge is live.</h2>
                <p class="max-w-md text-soft">It takes two minutes, and tomorrow there is another one.</p>
                <Link :href="route('register')" class="btn btn-primary btn-lg">
                    Start your streak <ArrowRight :size="19" :stroke-width="2.5" />
                </Link>
            </div>
        </div>
    </section>

    <footer class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-4 px-5 py-8 text-sm text-faint sm:px-8">
        <Logo :size="22" />
        <p>Learn something small every day.</p>
    </footer>
</template>
