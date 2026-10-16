<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';

// サーバー(StandingController@index)から渡される大会の型
type Tournament = {
    id: number;
    name: string;
    status: string;
};

defineProps<{
    tournaments: Tournament[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: '順位表',
                href: '/standings',
            },
        ],
    },
});
</script>

<template>
    <Head title="順位表" />

    <div class="mx-auto flex w-full max-w-2xl flex-col gap-6 p-4">
        <Heading title="順位表" description="順位を見る大会を選んでください" />

        <p v-if="tournaments.length === 0" class="text-sm text-muted-foreground">
            開始した大会がありません。
        </p>

        <ul v-else class="flex flex-col gap-3">
            <li v-for="tournament in tournaments" :key="tournament.id">
                <Link
                    :href="`/standings/${tournament.id}`"
                    class="block rounded-xl border border-sidebar-border/70 p-4 transition-colors hover:bg-accent dark:border-sidebar-border"
                >
                    <p class="text-base font-semibold">{{ tournament.name }}</p>
                    <p class="mt-1 text-sm text-muted-foreground">{{ tournament.status }}</p>
                </Link>
            </li>
        </ul>
    </div>
</template>
