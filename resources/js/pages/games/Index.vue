<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: '進行中の試合',
                href: '/games',
            },
        ],
    },
});
</script>

<template>
    <Head title="進行中の試合" />

    <div class="mx-auto flex w-full max-w-2xl flex-col gap-6 p-4">
        <Heading title="進行中の試合" description="進行中の試合が、ここに表示されます" />

        <!-- 試合のデータを作る段階で、中身を作る -->
        <p class="text-sm text-muted-foreground">この画面は、準備中です。</p>
    </div>
</template>
