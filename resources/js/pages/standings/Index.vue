<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';

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
        <Heading title="順位表" description="大会ごとの順位が、ここに表示されます" />

        <!-- 試合のデータを作る段階で、中身を作る -->
        <p class="text-sm text-muted-foreground">この画面は、準備中です。</p>
    </div>
</template>
