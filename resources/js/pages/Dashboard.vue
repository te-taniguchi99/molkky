<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ListOrdered, Play, Trophy, Users } from '@lucide/vue';
import Heading from '@/components/Heading.vue';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'メイン',
                href: '/dashboard',
            },
        ],
    },
});

// メイン画面に並べるボタン(スマホで押しやすいように、大きく表示する)
const menus = [
    { title: '大会の一覧', description: '大会を選ぶ・新しく作る', href: '/tournaments', icon: Trophy },
    { title: '進行中の試合', description: '点数の入力・確認', href: '/games', icon: Play },
    { title: '順位表', description: '大会ごとの順位', href: '/standings', icon: ListOrdered },
    { title: 'ユーザー管理', description: 'ユーザーの追加・有効/無効', href: '/users', icon: Users },
];
</script>

<template>
    <Head title="メイン" />

    <div class="mx-auto flex w-full max-w-2xl flex-col gap-6 p-4">
        <Heading title="メイン" description="見たい項目を選んでください" />

        <div class="grid gap-4 sm:grid-cols-2">
            <Link
                v-for="menu in menus"
                :key="menu.href"
                :href="menu.href"
                class="flex min-h-28 flex-col justify-center gap-2 rounded-xl border border-sidebar-border/70 p-5 transition-colors hover:bg-accent dark:border-sidebar-border"
            >
                <component :is="menu.icon" class="size-7" />
                <span class="text-lg font-semibold">{{ menu.title }}</span>
                <span class="text-sm text-muted-foreground">{{ menu.description }}</span>
            </Link>
        </div>
    </div>
</template>
