<script setup lang="ts">
import { Form, Link } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';

// 選択肢の型(値と表示名の組)
type Option = {
    value: string;
    label: string;
};

// 入力欄に最初から入れておく値(編集のとき)
type Initial = {
    name: string;
    held_at: string | null;
    venue: string | null;
    format: string;
    participant_type: string;
    target_score: number;
    reset_score: number;
    max_consecutive_misses: number;
};

// 作成と編集で共通に使う、大会の入力フォーム
const props = withDefaults(
    defineProps<{
        action: string;
        method: 'post' | 'put';
        formats: Option[];
        participantTypes: Option[];
        submitLabel: string;
        cancelHref: string;
        initial?: Initial;
        // 方式とルール(目標点など)を変更できるか(開始後は、変更できない)
        canEditRules?: boolean;
        // 参加形態を変更できるか(参加者の登録後は、変更できない)
        canEditType?: boolean;
    }>(),
    {
        canEditRules: true,
        canEditType: true,
    },
);

// 選択欄(select)に付ける見た目。入力欄(Input)と同じ見た目にそろえる
const selectClass =
    'h-9 w-full rounded-md border border-input bg-background text-foreground px-3 text-base dark:[color-scheme:dark] shadow-xs focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none md:text-sm';

// 値から表示名を探す(変更できない項目を、文字で表示するため)
function labelOf(options: Option[], value: string | undefined): string {
    return options.find((option) => option.value === value)?.label ?? '';
}
</script>

<template>
    <Form
        :action="props.action"
        :method="props.method"
        class="flex flex-col gap-6"
        v-slot="{ errors, processing }"
    >
        <div class="grid gap-2">
            <Label for="name">大会名</Label>
            <Input
                id="name"
                name="name"
                required
                :default-value="initial?.name"
                placeholder="例:第1回 モルック大会"
            />
            <InputError :message="errors.name" />
        </div>

        <div class="grid gap-2">
            <Label for="held_at">開催日時(未定なら空でも構いません)</Label>
            <Input
                id="held_at"
                name="held_at"
                type="datetime-local"
                :default-value="initial?.held_at ?? ''"
            />
            <InputError :message="errors.held_at" />
        </div>

        <div class="grid gap-2">
            <Label for="venue">場所(未定なら空でも構いません)</Label>
            <Input
                id="venue"
                name="venue"
                :default-value="initial?.venue ?? ''"
                placeholder="例:○○公園 第2広場"
            />
            <InputError :message="errors.venue" />
        </div>

        <!-- 方式とルールは、大会の開始後は変更できない(試合がすでに作られているため) -->
        <p v-if="!canEditRules" class="rounded-md border p-3 text-sm text-muted-foreground">
            大会を開始したため、方式とルールは変更できません。
        </p>

        <template v-if="canEditRules">
            <div class="grid gap-2">
                <Label for="format">方式</Label>
                <select id="format" name="format" :class="selectClass" required>
                    <option class="bg-background text-foreground"
                        v-for="option in formats"
                        :key="option.value"
                        :value="option.value"
                        :selected="option.value === initial?.format"
                    >
                        {{ option.label }}
                    </option>
                </select>
                <InputError :message="errors.format" />
            </div>

            <div v-if="canEditType" class="grid gap-2">
                <Label for="participant_type">参加形態</Label>
                <select
                    id="participant_type"
                    name="participant_type"
                    :class="selectClass"
                    required
                >
                    <option class="bg-background text-foreground"
                        v-for="option in participantTypes"
                        :key="option.value"
                        :value="option.value"
                        :selected="option.value === initial?.participant_type"
                    >
                        {{ option.label }}
                    </option>
                </select>
                <InputError :message="errors.participant_type" />
            </div>
            <p v-else class="text-sm text-muted-foreground">
                参加形態: {{ labelOf(participantTypes, initial?.participant_type) }}
                (参加者を登録したため、変更できません)
            </p>

            <div class="grid gap-2">
                <Label for="target_score">目標点(この点数ちょうどで勝ち)</Label>
                <Input
                    id="target_score"
                    name="target_score"
                    type="number"
                    inputmode="numeric"
                    min="1"
                    :default-value="initial?.target_score ?? 50"
                    required
                />
                <InputError :message="errors.target_score" />
            </div>

            <div class="grid gap-2">
                <Label for="reset_score">戻る得点(目標点を超えたとき)</Label>
                <Input
                    id="reset_score"
                    name="reset_score"
                    type="number"
                    inputmode="numeric"
                    min="0"
                    :default-value="initial?.reset_score ?? 25"
                    required
                />
                <InputError :message="errors.reset_score" />
            </div>

            <div class="grid gap-2">
                <Label for="max_consecutive_misses">失格になる連続ミス回数</Label>
                <Input
                    id="max_consecutive_misses"
                    name="max_consecutive_misses"
                    type="number"
                    inputmode="numeric"
                    min="1"
                    max="10"
                    :default-value="initial?.max_consecutive_misses ?? 3"
                    required
                />
                <InputError :message="errors.max_consecutive_misses" />
            </div>
        </template>

        <div class="flex flex-col gap-3">
            <Button type="submit" size="lg" :disabled="processing">
                <Spinner v-if="processing" />
                {{ submitLabel }}
            </Button>
            <Button as-child variant="outline" size="lg">
                <Link :href="cancelHref">戻る</Link>
            </Button>
        </div>
    </Form>
</template>
