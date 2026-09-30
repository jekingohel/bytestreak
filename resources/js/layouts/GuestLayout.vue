<script setup>
import { Link } from '@inertiajs/vue3';
import { Flame, Trophy, Zap } from '@lucide/vue';
import Logo from '@/components/Logo.vue';
import Toasts from '@/components/Toasts.vue';

const perks = [
    { icon: Zap, title: 'One question a day', text: 'Two minutes, one concept, an explanation you will remember.' },
    { icon: Flame, title: 'Keep the streak alive', text: 'Show up daily, earn freezes, and watch the flame grow.' },
    { icon: Trophy, title: 'Climb with your team', text: 'XP, levels, badges and a leaderboard that resets every Monday.' },
];
</script>

<template>
    <div class="grid min-h-dvh lg:grid-cols-[1.05fr_1fr]">
        <!-- Brand panel -->
        <aside class="relative hidden overflow-hidden border-r border-line bg-surface lg:flex lg:flex-col lg:justify-between lg:p-12">
            <div class="pointer-events-none absolute -top-40 -left-32 size-[34rem] rounded-full bg-flame/10 blur-3xl" aria-hidden="true" />
            <div class="pointer-events-none absolute -right-40 bottom-0 size-[28rem] rounded-full bg-ember/10 blur-3xl" aria-hidden="true" />

            <Link :href="route('home')" class="relative"><Logo :size="34" /></Link>

            <div class="relative max-w-md">
                <h1 class="text-5xl leading-[1.05] font-extrabold">
                    One byte a day.<br />
                    <span class="flame-text">Keep the streak alive.</span>
                </h1>
                <ul class="mt-10 space-y-6">
                    <li v-for="perk in perks" :key="perk.title" class="flex gap-4">
                        <span class="grid size-11 shrink-0 place-items-center rounded-2xl border border-line bg-raised text-flame">
                            <component :is="perk.icon" :size="20" :stroke-width="2.25" />
                        </span>
                        <div>
                            <p class="font-bold">{{ perk.title }}</p>
                            <p class="mt-0.5 text-sm leading-relaxed text-soft">{{ perk.text }}</p>
                        </div>
                    </li>
                </ul>
            </div>

            <p class="relative text-sm text-faint">Learn something small every day.</p>
        </aside>

        <main class="flex flex-col items-center justify-center px-5 py-12">
            <Link :href="route('home')" class="mb-10 lg:hidden"><Logo :size="34" /></Link>
            <div class="w-full max-w-sm animate-rise">
                <slot />
            </div>
        </main>

        <Toasts />
    </div>
</template>
