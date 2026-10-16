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
    status: string;
    is_preparing: boolean;
};

type Participant = {
    id: number;
    name: string;
    members: string[];
};

type Candidate = {
    id: number;
    name: string;
    username: string;
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
    candidates: Candidate[];
    games: Game[];
}>();

// 大会を開始できる条件(準備中で、参加者が 2 つ以上)
const canStart = computed(
    () => props.tournament.is_preparing && props.participants.length >= 2,
);

// 参加者の呼び方(チーム戦は「チーム」、個人戦は「参加者」)
const unit = computed(() => (props.tournament.is_team ? 'チーム' : '参加者'));

// 参加者を削除する前に、確認のダイアログを出す(false を返すと、削除を中止する)
function confirmDelete(): boolean {
    return window.confirm('削除しますか?');
}

// 大会を削除する前に、確認のダイアログを出す(参加者と試合も、すべて削除される)
function confirmDeleteTournament(): boolean {
    return window.confirm(
        'この大会を削除しますか?参加者と試合も、すべて削除されます。元に戻せません。',
    );
}

// 入力欄(Input)と同じ見た目にそろえる選択欄の見た目
const selectClass =
    'h-9 w-full rounded-md border border-input bg-transparent px-3 text-base shadow-xs focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none md:text-sm';

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
        <Heading :title="tournament.name" class="-mb-4" />

        <!-- 大会の情報(項目名つきで表示する) -->
        <dl class="grid grid-cols-[auto_1fr] gap-x-4 gap-y-1 text-sm">
            <dt class="text-muted-foreground">方式</dt>
            <dd>{{ tournament.format }}</dd>
            <dt class="text-muted-foreground">参加形態</dt>
            <dd>{{ tournament.participant_type }}</dd>
            <dt class="text-muted-foreground">状態</dt>
            <dd>{{ tournament.status }}</dd>
        </dl>

        <!-- 参加者 -->
        <section class="flex flex-col gap-4">
            <h2 class="text-lg font-semibold">
                {{ unit }}({{ participants.length }})
            </h2>

            <p v-if="participants.length === 0" class="text-sm text-muted-foreground">
                まだ{{ unit }}がありません。
            </p>

            <ul v-else class="flex flex-col gap-2">
                <li
                    v-for="participant in participants"
                    :key="participant.id"
                    class="flex items-center justify-between gap-3 rounded-xl border border-sidebar-border/70 p-3 dark:border-sidebar-border"
                >
                    <div class="min-w-0">
                        <p class="truncate font-medium">
                            {{ participant.name }}
                            <span v-if="tournament.is_team" class="text-sm font-normal text-muted-foreground">
                                ({{ participant.members.length }}人)
                            </span>
                        </p>
                        <!-- チーム戦は、所属するユーザーの名前を表示する -->
                        <p v-if="tournament.is_team" class="truncate text-sm text-muted-foreground">
                            {{ participant.members.join('、') }}
                        </p>
                    </div>
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
                :reset-on-success="['name', 'user_id', 'user_ids']"
                class="flex flex-col gap-4 rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border"
                v-slot="{ errors, processing }"
            >
                <p v-if="candidates.length === 0" class="text-sm text-muted-foreground">
                    選べるユーザーがいません。「ユーザー管理」で、ユーザーを追加してください。
                </p>

                <!-- 個人戦: ユーザーを 1 人選ぶ -->
                <div v-if="!tournament.is_team && candidates.length > 0" class="grid gap-2">
                    <Label for="user_id">ユーザー</Label>
                    <select id="user_id" name="user_id" :class="selectClass" required>
                        <option v-for="candidate in candidates" :key="candidate.id" :value="candidate.id">
                            {{ candidate.name }}({{ candidate.username }})
                        </option>
                    </select>
                    <InputError :message="errors.user_id" />
                </div>

                <!-- チーム戦: チーム名を入力し、所属するユーザーを選ぶ(人数は、選んだ数で決まる) -->
                <template v-if="tournament.is_team && candidates.length > 0">
                    <div class="grid gap-2">
                        <Label for="name">チーム名</Label>
                        <Input id="name" name="name" required />
                        <InputError :message="errors.name" />
                    </div>

                    <fieldset class="grid gap-2">
                        <legend class="mb-1 text-sm font-medium">所属するユーザー</legend>
                        <label
                            v-for="candidate in candidates"
                            :key="candidate.id"
                            class="flex items-center gap-3 rounded-md border border-input p-3 text-sm"
                        >
                            <input type="checkbox" name="user_ids[]" :value="candidate.id" class="size-5" />
                            {{ candidate.name }}({{ candidate.username }})
                        </label>
                        <InputError :message="errors.user_ids" />
                    </fieldset>
                </template>

                <Button v-if="candidates.length > 0" type="submit" size="lg" :disabled="processing">
                    <Spinner v-if="processing" />
                    {{ unit }}を追加する
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
                {{ unit }}が2つ以上になると、開始できます。開始すると、{{ unit }}は変更できません。
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

        <!-- 大会の削除(進行中・終了した大会でも、削除できる) -->
        <section class="flex flex-col gap-2 border-t pt-6">
            <Button as-child variant="destructive" size="lg">
                <Link
                    :href="`/tournaments/${tournament.id}`"
                    method="delete"
                    as="button"
                    @before="confirmDeleteTournament"
                >
                    この大会を削除する
                </Link>
            </Button>
        </section>

    </div>
</template>
