<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';

// サーバー(TournamentController@index)から渡される大会の型
type Tournament = {
    id: number;
    name: string;
    format: string;
    participant_type: string;
    team_size: number | null;
    status: string;
};

defineProps<{
    tournaments: Tournament[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: '大会の一覧',
                href: '/tournaments',
            },
        ],
    },
});
</script>

<template>
    <Head title="大会の一覧" />

    <div class="mx-auto flex w-full max-w-2xl flex-col gap-6 p-4">
        <Heading title="大会の一覧" description="大会を選ぶか、新しく作ります" />

        <!-- スマホで押しやすいように、大きめのボタンにする -->
        <Button as-child size="lg" class="w-full">
            <Link href="/tournaments/create">新しい大会を作る</Link>
        </Button>

        <p v-if="tournaments.length === 0" class="text-sm text-muted-foreground">
            まだ大会がありません。
        </p>

        <ul v-else class="flex flex-col gap-3">
            <li
                v-for="tournament in tournaments"
                :key="tournament.id"
                class="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border"
            >
                <p class="text-base font-semibold">{{ tournament.name }}</p>
                <p class="mt-1 text-sm text-muted-foreground">
                    {{ tournament.format }} /
                    {{ tournament.participant_type
                    }}<template v-if="tournament.team_size">
                        ({{ tournament.team_size }}人)</template
                    >
                    / {{ tournament.status }}
                </p>
            </li>
        </ul>
    </div>
</template>
