<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import TournamentForm from '@/components/TournamentForm.vue';

// 選択肢の型(値と表示名の組)
type Option = {
    value: string;
    label: string;
};

defineProps<{
    formats: Option[];
    participantTypes: Option[];
}>();

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

        <TournamentForm
            action="/tournaments"
            method="post"
            :formats="formats"
            :participant-types="participantTypes"
            submit-label="大会を作成する"
            cancel-href="/tournaments"
        />
    </div>
</template>
