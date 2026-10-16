<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { usePolling } from '@/composables/usePolling';

// サーバー(StandingController@show)から渡される順位の行
type Row = {
    rank: number;
    id: number;
    name: string;
    wins: number;
    losses: number;
};

defineProps<{
    tournament: { id: number; name: string; status: string };
    rows: Row[];
}>();

// 5 秒ごとに、最新の順位を取り直す
usePolling(['tournament', 'rows']);

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: '順位表',
                href: '/standings',
            },
            {
                title: '大会の順位',
                href: '/standings',
            },
        ],
    },
});
</script>

<template>
    <Head :title="`${tournament.name} の順位表`" />

    <div class="mx-auto flex w-full max-w-2xl flex-col gap-6 p-4">
        <Heading
            :title="tournament.name"
            :description="`順位表 / ${tournament.status}(勝った数の多い順。5秒ごとに自動で更新されます)`"
        />

        <p v-if="rows.length === 0" class="text-sm text-muted-foreground">参加者がいません。</p>

        <table v-else class="w-full text-left">
            <thead class="text-sm text-muted-foreground">
                <tr>
                    <th class="py-2 pr-2">順位</th>
                    <th class="py-2 pr-2">名前</th>
                    <th class="py-2 pr-2 text-right">勝</th>
                    <th class="py-2 text-right">負</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="row in rows" :key="row.id" class="border-t">
                    <td class="py-3 pr-2 text-lg font-bold tabular-nums">{{ row.rank }}</td>
                    <td class="py-3 pr-2">{{ row.name }}</td>
                    <td class="py-3 pr-2 text-right tabular-nums">{{ row.wins }}</td>
                    <td class="py-3 text-right tabular-nums">{{ row.losses }}</td>
                </tr>
            </tbody>
        </table>

        <Button as-child variant="ghost">
            <Link href="/standings">大会の選択へ戻る</Link>
        </Button>
    </div>
</template>
