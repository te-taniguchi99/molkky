<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import { usePolling } from '@/composables/usePolling';

// サーバー(GameController@index / GameBoard)から渡される試合の型
type BoardGame = {
    id: number;
    tournament: { id: number; name: string };
    round: number;
    status: string;
    participants: { id: number; name: string; score: number; eliminated: boolean; is_current: boolean }[];
};

defineProps<{
    games: BoardGame[];
}>();

// 5 秒ごとに、最新の試合の状況を取り直す
usePolling(['games']);

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: '進行中の試合',
                href: '/games',
            },
        ],
    },
});
</script>

<template>
    <Head title="進行中の試合" />

    <div class="mx-auto flex w-full max-w-2xl flex-col gap-6 p-4">
        <Heading
            title="進行中の試合"
            description="試合を選ぶと、点数を入力・確認できます(5秒ごとに自動で更新されます)"
        />

        <p v-if="games.length === 0" class="text-sm text-muted-foreground">
            進行中の試合はありません。大会を開始すると、試合がここに表示されます。
        </p>

        <ul v-else class="flex flex-col gap-3">
            <li v-for="game in games" :key="game.id">
                <Link
                    :href="`/games/${game.id}`"
                    class="block rounded-xl border border-sidebar-border/70 p-4 transition-colors hover:bg-accent dark:border-sidebar-border"
                >
                    <p class="text-sm text-muted-foreground">
                        {{ game.tournament.name }} / {{ game.round }}回戦 / {{ game.status }}
                    </p>
                    <ul class="mt-2 flex flex-col gap-1">
                        <li
                            v-for="participant in game.participants"
                            :key="participant.id"
                            class="flex items-baseline justify-between gap-3"
                        >
                            <span class="min-w-0 truncate" :class="participant.is_current ? 'font-semibold' : ''">
                                {{ participant.name }}
                                <span v-if="participant.is_current" class="text-sm">← 投げる番</span>
                                <span v-if="participant.eliminated" class="text-sm text-destructive">(失格)</span>
                            </span>
                            <span class="text-2xl font-bold tabular-nums">{{ participant.score }}</span>
                        </li>
                    </ul>
                </Link>
            </li>
        </ul>
    </div>
</template>
