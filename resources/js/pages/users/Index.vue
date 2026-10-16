<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';

// サーバー(UserController@index)から渡されるユーザーの型
type ManagedUser = {
    id: number;
    name: string;
    username: string;
    is_active: boolean;
    is_me: boolean;
};

defineProps<{
    users: ManagedUser[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'ユーザー管理',
                href: '/users',
            },
        ],
    },
});
</script>

<template>
    <Head title="ユーザー管理" />

    <div class="mx-auto flex w-full max-w-2xl flex-col gap-6 p-4">
        <Heading
            title="ユーザー管理"
            description="ユーザーを追加する、ログインできないようにする(無効)ことができます"
        />

        <Button as-child size="lg" class="w-full">
            <Link href="/users/create">ユーザーを追加する</Link>
        </Button>

        <ul class="flex flex-col gap-3">
            <li
                v-for="user in users"
                :key="user.id"
                class="flex items-center justify-between gap-3 rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border"
            >
                <div class="min-w-0">
                    <p class="truncate text-base font-semibold">
                        {{ user.name }}
                        <span v-if="user.is_me" class="text-xs font-normal text-muted-foreground">(自分)</span>
                    </p>
                    <p class="truncate text-sm text-muted-foreground">
                        {{ user.username }} / {{ user.is_active ? '有効' : '無効' }}
                    </p>
                </div>

                <!-- 自分自身は、無効にできない -->
                <Button
                    v-if="!user.is_me"
                    as-child
                    :variant="user.is_active ? 'outline' : 'default'"
                >
                    <Link :href="`/users/${user.id}/active`" method="patch" as="button">
                        {{ user.is_active ? '無効にする' : '有効にする' }}
                    </Link>
                </Button>
            </li>
        </ul>
    </div>
</template>
