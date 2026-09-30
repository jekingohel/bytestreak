<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Check, Eye, Pencil, X } from '@lucide/vue';
import AdminNav from '@/components/AdminNav.vue';
import Avatar from '@/components/Avatar.vue';
import CategoryChip from '@/components/CategoryChip.vue';
import CodeBlock from '@/components/CodeBlock.vue';
import DifficultyMeter from '@/components/DifficultyMeter.vue';
import { dayLabel, timeAgo } from '@/lib/format';

defineProps({
    challenge: { type: Object, required: true },
    stats: { type: Object, required: true },
    attempts: { type: Array, required: true },
});
</script>

<template>
    <Head :title="`${challenge.title} · Admin`" />
    <AdminNav />

    <Link :href="route('admin.challenges.index')" class="inline-flex items-center gap-1.5 text-sm font-semibold text-soft hover:text-ink">
        <ArrowLeft :size="16" :stroke-width="2.5" /> All challenges
    </Link>

    <header class="mt-3 flex flex-wrap items-start justify-between gap-4">
        <div class="min-w-0">
            <h1 class="text-2xl font-extrabold sm:text-3xl">{{ challenge.title }}</h1>
            <div class="mt-2.5 flex flex-wrap items-center gap-2.5">
                <CategoryChip :category="challenge.category" />
                <span class="pill border border-line bg-raised text-soft">{{ challenge.type.label }}</span>
                <DifficultyMeter :level="challenge.difficulty" />
                <span class="text-sm text-faint capitalize">{{ challenge.status }} · {{ challenge.publish_date ? dayLabel(challenge.publish_date) : 'no date' }}</span>
            </div>
        </div>
        <div class="flex gap-2">
            <Link :href="route('challenges.show', challenge.id)" class="btn btn-ghost"><Eye :size="17" /> View as player</Link>
            <Link :href="route('admin.challenges.edit', challenge.id)" class="btn btn-ghost"><Pencil :size="16" /> Edit</Link>
        </div>
    </header>

    <section class="mt-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
        <div class="card p-5">
            <p class="text-xs font-semibold text-faint">Answers</p>
            <p class="num mt-2 text-3xl font-extrabold">{{ stats.attempts }}</p>
        </div>
        <div class="card p-5">
            <p class="text-xs font-semibold text-faint">Correct</p>
            <p class="num mt-2 text-3xl font-extrabold">{{ stats.accuracy ?? '—' }}<span v-if="stats.accuracy !== null" class="text-lg text-faint">%</span></p>
        </div>
        <div class="card p-5">
            <p class="text-xs font-semibold text-faint">Answered on the day</p>
            <p class="num mt-2 text-3xl font-extrabold">{{ stats.daily_attempts }}<span class="text-lg text-faint"> / {{ stats.team_size }}</span></p>
        </div>
        <div class="card p-5">
            <p class="text-xs font-semibold text-faint">Played later as practice</p>
            <p class="num mt-2 text-3xl font-extrabold">{{ stats.attempts - stats.daily_attempts }}</p>
        </div>
    </section>

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        <section class="card p-6">
            <h2 class="text-lg font-bold">How people answered</h2>
            <ul class="mt-4 space-y-3">
                <li
                    v-for="(option, index) in challenge.options"
                    :key="option.id"
                    class="relative overflow-hidden rounded-xl border px-4 py-3"
                    :class="option.is_correct ? 'border-mint/50 text-mint' : 'border-line text-soft'"
                >
                    <span class="absolute inset-y-0 left-0 bg-current opacity-10" :style="{ width: `${option.percent}%` }" aria-hidden="true" />
                    <div class="relative flex items-center gap-3">
                        <span class="kbd">{{ 'ABCDEF'[index] }}</span>
                        <span class="min-w-0 flex-1 text-sm font-medium break-words text-ink">{{ option.label }}</span>
                        <Check v-if="option.is_correct" :size="16" :stroke-width="3" />
                        <span class="num text-sm font-bold">{{ option.percent }}%</span>
                        <span class="num w-8 text-right text-xs text-faint">{{ option.picks }}</span>
                    </div>
                </li>
            </ul>
        </section>

        <section class="card p-6">
            <h2 class="text-lg font-bold">The question</h2>
            <p class="mt-3 leading-relaxed whitespace-pre-line text-soft">{{ challenge.question }}</p>
            <CodeBlock v-if="challenge.code_snippet" class="mt-4" :code="challenge.code_snippet" :language="challenge.code_language" />
            <h3 class="eyebrow mt-5">Explanation</h3>
            <p class="mt-2 text-sm leading-relaxed whitespace-pre-line text-soft">{{ challenge.explanation }}</p>
        </section>
    </div>

    <section class="card mt-6 overflow-hidden">
        <h2 class="px-6 pt-6 text-lg font-bold">Latest answers</h2>
        <div class="mt-3 overflow-x-auto">
            <table class="table">
                <thead>
                    <tr><th>Developer</th><th>Picked</th><th>Result</th><th>When</th></tr>
                </thead>
                <tbody>
                    <tr v-for="attempt in attempts" :key="attempt.id">
                        <td>
                            <span class="flex items-center gap-2.5 font-semibold"><Avatar :user="attempt.user" :size="26" /> {{ attempt.user.name }}</span>
                        </td>
                        <td class="max-w-72 truncate text-soft">{{ attempt.option ?? '—' }}</td>
                        <td>
                            <span class="inline-flex items-center gap-1 font-semibold" :class="attempt.is_correct ? 'text-mint' : 'text-rose'">
                                <Check v-if="attempt.is_correct" :size="15" :stroke-width="3" /><X v-else :size="15" :stroke-width="3" />
                                {{ attempt.is_correct ? 'Correct' : 'Wrong' }}
                            </span>
                        </td>
                        <td class="whitespace-nowrap text-faint">{{ timeAgo(attempt.submitted_at) }}{{ attempt.is_daily ? '' : ' · practice' }}</td>
                    </tr>
                    <tr v-if="!attempts.length">
                        <td colspan="4" class="py-12! text-center text-soft">Nobody has answered this one yet.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>
</template>
