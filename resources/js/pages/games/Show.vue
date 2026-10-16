<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { usePolling } from '@/composables/usePolling';

// サーバー(GameController@show / GameBoard)から渡される試合の盤面の型
type BoardParticipant = {
    id: number;
    name: string;
    members: string[];
    score: number;
    misses: number;
    eliminated: boolean;
    is_current: boolean;
    is_winner: boolean;
};

type Board = {
    id: number;
    tournament: {
        id: number;
        name: string;
        target_score: number;
        reset_score: number;
        max_consecutive_misses: number;
    };
    round: number;
    status: string;
    is_finished: boolean;
    is_bye: boolean;
    winner_name: string | null;
    current_participant_id: number | null;
    participants: BoardParticipant[];
    history: { id: number; name: string; label: string }[];
    has_throws: boolean;
};

const props = defineProps<{
    board: Board;
}>();

// 5 秒ごとに、最新の点数を取り直す(他の端末の入力も、自動で反映される)
usePolling(['board']);

// 手番の参加者
const current = computed(() =>
    props.board.participants.find((participant) => participant.is_current),
);

// 送信中か(押し間違い・二重送信を防ぐため、送信中はボタンを押せなくする)
const sending = ref(false);

// 入力に失敗したときのメッセージ(手番が変わったときなど)
const errorMessage = ref('');

/**
 * 1投を記録する。
 * 画面に表示している手番の参加者の id もいっしょに送り、別の端末で手番が変わっていないかを、サーバーで確かめる。
 */
function record(kind: 'score' | 'miss', value = 0): void {
    if (!current.value || sending.value) {
        return;
    }

    errorMessage.value = '';

    router.post(
        `/games/${props.board.id}/throws`,
        { participant_id: current.value.id, kind, value },
        {
            preserveScroll: true,
            onStart: () => (sending.value = true),
            onFinish: () => (sending.value = false),
            onError: (errors) => {
                errorMessage.value = Object.values(errors)[0] ?? '記録できませんでした。';
                // 手番が変わっていた場合に備えて、最新の状態を取り直す
                router.reload({ only: ['board'] });
            },
        },
    );
}

// 最後の1投を取り消す(1つ前に戻す)
function undo(): void {
    if (sending.value) {
        return;
    }

    errorMessage.value = '';

    router.delete(`/games/${props.board.id}/throws/last`, {
        preserveScroll: true,
        onStart: () => (sending.value = true),
        onFinish: () => (sending.value = false),
        onError: (errors) => {
            errorMessage.value = Object.values(errors)[0] ?? '取り消せませんでした。';
        },
    });
}

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: '進行中の試合',
                href: '/games',
            },
            {
                title: '点数の入力',
                href: '/games',
            },
        ],
    },
});
</script>

<template>
    <Head title="点数の入力" />

    <div class="mx-auto flex w-full max-w-2xl flex-col gap-6 p-4">
        <Heading
            :title="board.tournament.name"
            :description="`${board.round}回戦 / ${board.status} / ${board.tournament.target_score}点ちょうどで勝ち`"
        />

        <!-- 各参加者の点数 -->
        <ul class="grid gap-3">
            <li
                v-for="participant in board.participants"
                :key="participant.id"
                class="rounded-xl border p-4"
                :class="
                    participant.is_current
                        ? 'border-primary bg-accent'
                        : 'border-sidebar-border/70 dark:border-sidebar-border'
                "
            >
                <div class="flex items-baseline justify-between gap-3">
                    <p class="min-w-0 truncate text-lg font-semibold">
                        {{ participant.name }}
                        <span v-if="participant.is_winner" class="text-sm text-green-600">(勝ち)</span>
                        <span v-if="participant.eliminated" class="text-sm text-destructive">(失格)</span>
                    </p>
                    <p class="text-4xl font-bold tabular-nums">{{ participant.score }}</p>
                </div>
                <p v-if="participant.members.length > 0" class="truncate text-sm text-muted-foreground">
                    {{ participant.members.join('、') }}
                </p>
                <p class="mt-1 text-sm text-muted-foreground">
                    連続ミス {{ participant.misses }} / {{ board.tournament.max_consecutive_misses }}
                    <span v-if="participant.is_current" class="ml-2 font-semibold text-foreground">← 投げる番</span>
                </p>
            </li>
        </ul>

        <!-- 試合が終わったとき -->
        <p
            v-if="board.is_finished"
            class="rounded-xl border border-green-600 p-4 text-center text-lg font-semibold"
        >
            試合終了:{{ board.winner_name }} の勝ちです
        </p>

        <!-- 点数の入力(終了した試合では、取り消しだけ) -->
        <section v-if="!board.is_finished && current" class="flex flex-col gap-4">
            <h2 class="text-center text-lg font-semibold">{{ current.name }} の番</h2>

            <p v-if="errorMessage" class="rounded-md border border-destructive p-3 text-sm text-destructive">
                {{ errorMessage }}
            </p>

            <!-- 得点を押す(1本だけ倒れたなら、倒れた番号。2本以上なら、倒れた本数) -->
            <div>
                <p class="mb-2 text-sm text-muted-foreground">得点(1本なら倒れた番号 / 2本以上なら倒れた本数)</p>
                <div class="grid grid-cols-4 gap-2">
                    <Button
                        v-for="point in 12"
                        :key="point"
                        size="lg"
                        variant="outline"
                        class="h-16 text-2xl"
                        :disabled="sending"
                        @click="record('score', point)"
                    >
                        {{ point }}
                    </Button>
                </div>
            </div>

            <!-- 1本も倒れなかったとき -->
            <Button
                size="lg"
                variant="destructive"
                class="h-14 w-full text-lg"
                :disabled="sending"
                @click="record('miss')"
            >
                ミス(1本も倒れなかった)
            </Button>
        </section>

        <!-- 1つ前に戻す -->
        <Button
            v-if="board.has_throws"
            size="lg"
            variant="outline"
            class="h-12 w-full"
            :disabled="sending"
            @click="undo"
        >
            1つ前に戻す
        </Button>

        <!-- 直近の記録 -->
        <section v-if="board.history.length > 0" class="flex flex-col gap-2">
            <h2 class="text-sm font-semibold text-muted-foreground">直近の記録(新しい順)</h2>
            <ul class="flex flex-col gap-1 text-sm">
                <li v-for="item in board.history" :key="item.id">
                    {{ item.name }}:{{ item.label }}
                </li>
            </ul>
        </section>

        <Button as-child variant="ghost">
            <Link href="/games">進行中の試合の一覧へ戻る</Link>
        </Button>
    </div>
</template>
