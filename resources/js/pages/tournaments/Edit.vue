<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import TournamentForm from '@/components/TournamentForm.vue';

// 選択肢の型(値と表示名の組)
type Option = {
    value: string;
    label: string;
};

// サーバー(TournamentController@edit)から渡される大会の情報
type Tournament = {
    id: number;
    name: string;
    held_at: string | null;
    venue: string | null;
    format: string;
    participant_type: string;
    target_score: number;
    reset_score: number;
    max_consecutive_misses: number;
    can_edit_rules: boolean;
    can_edit_type: boolean;
};

const props = defineProps<{
    tournament: Tournament;
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
                title: '大会の編集',
                href: '/tournaments',
            },
        ],
    },
});
</script>

<template>
    <Head title="大会の編集" />

    <div class="mx-auto flex w-full max-w-2xl flex-col gap-6 p-4">
        <Heading title="大会の編集" :description="props.tournament.name" />

        <TournamentForm
            :action="`/tournaments/${props.tournament.id}`"
            method="put"
            :formats="formats"
            :participant-types="participantTypes"
            :initial="props.tournament"
            :can-edit-rules="props.tournament.can_edit_rules"
            :can-edit-type="props.tournament.can_edit_type"
            submit-label="更新する"
            :cancel-href="`/tournaments/${props.tournament.id}`"
        />
    </div>
</template>
