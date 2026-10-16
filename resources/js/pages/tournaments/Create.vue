<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
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

defineProps<{
    formats: Option[];
    participantTypes: Option[];
}>();

// 選択欄(select)に付ける見た目。入力欄(Input)と同じ見た目にそろえる
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
                title: '新しい大会',
                href: '/tournaments/create',
            },
        ],
    },
});
</script>

<template>
    <Head title="新しい大会" />

    <div class="mx-auto flex w-full max-w-2xl flex-col gap-6 p-4">
        <Heading
            title="新しい大会"
            description="大会の設定を入力します。点数のルールは、大会ごとに変えられます"
        />

        <Form
            action="/tournaments"
            method="post"
            class="flex flex-col gap-6"
            v-slot="{ errors, processing }"
        >
            <div class="grid gap-2">
                <Label for="name">大会名</Label>
                <Input id="name" name="name" required placeholder="例:第1回 モルック大会" />
                <InputError :message="errors.name" />
            </div>

            <div class="grid gap-2">
                <Label for="held_at">開催日時(未定なら空でも構いません)</Label>
                <Input id="held_at" name="held_at" type="datetime-local" />
                <InputError :message="errors.held_at" />
            </div>

            <div class="grid gap-2">
                <Label for="venue">場所(未定なら空でも構いません)</Label>
                <Input id="venue" name="venue" placeholder="例:○○公園 第2広場" />
                <InputError :message="errors.venue" />
            </div>

            <div class="grid gap-2">
                <Label for="format">方式</Label>
                <select id="format" name="format" :class="selectClass" required>
                    <option v-for="option in formats" :key="option.value" :value="option.value">
                        {{ option.label }}
                    </option>
                </select>
                <InputError :message="errors.format" />
            </div>

            <div class="grid gap-2">
                <Label for="participant_type">参加形態</Label>
                <select
                    id="participant_type"
                    name="participant_type"
                    :class="selectClass"
                    required
                >
                    <option v-for="option in participantTypes" :key="option.value" :value="option.value">
                        {{ option.label }}
                    </option>
                </select>
                <InputError :message="errors.participant_type" />
            </div>

            <div class="grid gap-2">
                <Label for="target_score">目標点(この点数ちょうどで勝ち)</Label>
                <Input id="target_score" name="target_score" type="number" inputmode="numeric" min="1" default-value="50" required />
                <InputError :message="errors.target_score" />
            </div>

            <div class="grid gap-2">
                <Label for="reset_score">戻る得点(目標点を超えたとき)</Label>
                <Input id="reset_score" name="reset_score" type="number" inputmode="numeric" min="0" default-value="25" required />
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
                    default-value="3"
                    required
                />
                <InputError :message="errors.max_consecutive_misses" />
            </div>

            <div class="flex flex-col gap-3">
                <Button type="submit" size="lg" :disabled="processing">
                    <Spinner v-if="processing" />
                    大会を作成する
                </Button>
                <Button as-child variant="outline" size="lg">
                    <Link href="/tournaments">戻る</Link>
                </Button>
            </div>
        </Form>
    </div>
</template>
