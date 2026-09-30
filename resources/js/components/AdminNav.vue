<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { Award, ChartColumn, ListChecks, Tag, Users } from '@lucide/vue';

const page = usePage();

const tabs = [
    { label: 'Overview', href: route('admin.dashboard'), icon: ChartColumn, match: 'admin/Dashboard' },
    { label: 'Challenges', href: route('admin.challenges.index'), icon: ListChecks, match: 'admin/challenges/' },
    { label: 'Categories', href: route('admin.categories.index'), icon: Tag, match: 'admin/Categories' },
    { label: 'Badges', href: route('admin.badges.index'), icon: Award, match: 'admin/Badges' },
    { label: 'Team', href: route('admin.users.index'), icon: Users, match: 'admin/Users' },
];
</script>

<template>
    <div class="mb-6">
        <p class="eyebrow">Admin</p>
        <nav class="-mx-4 mt-2 flex gap-1 overflow-x-auto overflow-y-hidden border-b border-line px-4 sm:mx-0 sm:px-0" aria-label="Admin sections">
            <Link
                v-for="tab in tabs"
                :key="tab.label"
                :href="tab.href"
                class="flex h-11 shrink-0 items-center gap-2 border-b-2 px-3 text-sm font-semibold transition-colors"
                :class="page.component.startsWith(tab.match) ? 'border-flame text-flame' : 'border-transparent text-soft hover:text-ink'"
                :aria-current="page.component.startsWith(tab.match) ? 'page' : undefined"
            >
                <component :is="tab.icon" :size="16" :stroke-width="2.25" /> {{ tab.label }}
            </Link>
        </nav>
    </div>
</template>
