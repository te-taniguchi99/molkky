<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';

// サーバー(TournamentController@show)から渡されるデータの型
type Tournament = {
    id: number;
    name: string;
    format: string;
    participant_type: string;
    is_team: boolean;
    team_size: number | null;
    status: string;
    is_preparing: boolean;
};

type Participant = {
    id: number;
    name: string;
    member_count: number;
};

type Game = {
    id: number;
    round: number;
    status: string;
    is_bye: boolean;
    participants: string[];
};

const props = defineProps<{
    tournament: Tournament;
    participants: Participant[];
    games: Game[];
}>();

// 大会を開始できる条件(準備中で、参加者が 2 つ以上)
const canStart = computed(
    () => props.tournament.is_preparing && props.participants.length >= 2,
);

// 削除の前に、確認のダイアログを出す(false を返すと、削除を中止する)
function confirmDelete(): boolean {
    return window.confirm('削除しますか?');
}

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: '大会の一覧',
                href: '/tournaments',
            },
            {
                title: '大会の詳細',
                href: '/tournaments',
            },
        ],
    },
});
</script>

<template>
    <Head :title="tournament.name" />

    <div class="mx-auto flex w-full max-w-2xl flex-col gap-8 p-4">
        <Heading
            :title="tournament.name"
            :description="`${tournament.format} / ${tournament.participant_type}${tournament.is_team && tournament.team_size ? `(目安 ${tournament.team_size}人)` : ''} / ${tournament.status}`"
        />

        <!-- 参加者 -->
        <section class="flex flex-col gap-4">
            <h2 class="text-lg font-semibold">
                参加者({{ participants.length }})
            </h2>

            <p v-if="participants.length === 0" class="text-sm text-muted-foreground">
                まだ参加者がいません。
            </p>

            <ul v-else class="flex flex-col gap-2">
                <li
                    v-for="participant in participants"
                    :key="participant.id"
                    class="flex items-center justify-between gap-3 rounded-xl border border-sidebar-border/70 p-3 dark:border-sidebar-border"
                >
                    <p class="min-w-0 truncate">
                        {{ participant.name }}
                        <span v-if="tournament.is_team" class="text-sm text-muted-foreground">
                            ({{ participant.member_count }}人)
                        </span>
                    </p>
                    <Button v-if="tournament.is_preparing" as-child variant="outline" size="sm">
                        <Link
                            :href="`/participants/${participant.id}`"
                            method="delete"
                            as="button"
                            @before="confirmDelete"
                        >
                            削除
                        </Link>
                    </Button>
                </li>
            </ul>

            <!-- 準備中の大会だけ、参加者を追加できる -->
            <Form
                v-if="tournament.is_preparing"
                :action="`/tournaments/${tournament.id}/participants`"
                method="post"
                :reset-on-success="['name']"
                class="flex flex-col gap-4 rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border"
                v-slot="{ errors, processing }"
            >
                <div class="grid gap-2">
                    <Label for="name">{{ tournament.is_team ? 'チーム名' : '名前' }}</Label>
                    <Input id="name" name="name" required />
                    <InputError :message="errors.name" />
                </div>

                <!-- チーム戦のときだけ、人数を入力する(チームごとに自由。割り切れなくてもよい) -->
                <div v-if="tournament.is_team" class="grid gap-2">
                    <Label for="member_count">人数</Label>
                    <Input
                        id="member_count"
                        name="member_count"
                        type="number"
                        inputmode="numeric"
                        min="1"
                        max="30"
                        :default-value="tournament.team_size ?? 1"
                    />
                    <InputError :message="errors.member_count" />
                </div>

                <Button type="submit" size="lg" :disabled="processing">
                    <Spinner v-if="processing" />
                    参加者を追加する
                </Button>
            </Form>
        </section>

        <!-- 大会の開始 -->
        <section v-if="tournament.is_preparing" class="flex flex-col gap-2">
            <Button as-child size="lg" :disabled="!canStart">
                <Link
                    v-if="canStart"
                    :href="`/tournaments/${tournament.id}/start`"
                    method="post"
                    as="button"
                >
                    大会を開始する(試合を作成)
                </Link>
                <span v-else>大会を開始する(試合を作成)</span>
            </Button>
            <p v-if="!canStart" class="text-sm text-muted-foreground">
                参加者が2つ以上になると、開始できます。開始すると、参加者は変更できません。
            </p>
        </section>

        <!-- 試合の一覧 -->
        <section v-if="games.length > 0" class="flex flex-col gap-4">
            <h2 class="text-lg font-semibold">試合({{ games.length }})</h2>

            <ul class="flex flex-col gap-2">
                <li
                    v-for="game in games"
                    :key="game.id"
                    class="rounded-xl border border-sidebar-border/70 p-3 dark:border-sidebar-border"
                >
                    <p class="text-sm text-muted-foreground">{{ game.round }}回戦 / {{ game.status }}</p>
                    <p v-if="game.is_bye">{{ game.participants[0] }}(不戦勝)</p>
                    <p v-else>{{ game.participants.join(' vs ') }}</p>
                </li>
            </ul>
        </section>
    </div>
</template>
