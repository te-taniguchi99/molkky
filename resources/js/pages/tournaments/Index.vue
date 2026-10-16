<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';

// サーバー(TournamentController@index)から渡される大会の型
type Tournament = {
    id: number;
    name: string;
    held_at: string | null;
    venue: string | null;
    format: string;
    participant_type: string;
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
            <li v-for="tournament in tournaments" :key="tournament.id">
                <Link
                    :href="`/tournaments/${tournament.id}`"
                    class="block rounded-xl border border-sidebar-border/70 p-4 transition-colors hover:bg-accent dark:border-sidebar-border"
                >
                    <p class="text-base font-semibold">{{ tournament.name }}</p>
                    <p
                        v-if="tournament.held_at || tournament.venue"
                        class="mt-1 text-sm"
                    >
                        {{ [tournament.held_at, tournament.venue].filter(Boolean).join(' / ') }}
                    </p>
                    <p class="mt-1 text-sm text-muted-foreground">
                        {{ tournament.format }} /
                        {{ tournament.participant_type }} /
                        {{ tournament.status }}
                    </p>
                </Link>
            </li>
        </ul>
    </div>
</template>
