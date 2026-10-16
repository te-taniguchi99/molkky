<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'ユーザー管理',
                href: '/users',
            },
            {
                title: 'ユーザーを追加',
                href: '/users/create',
            },
        ],
    },
});
</script>

<template>
    <Head title="ユーザーを追加" />

    <div class="mx-auto flex w-full max-w-2xl flex-col gap-6 p-4">
        <Heading
            title="ユーザーを追加"
            description="名前とログインIDを入力します"
        />

        <Form
            action="/users"
            method="post"
            class="flex flex-col gap-6"
            v-slot="{ errors, processing }"
        >
            <div class="grid gap-2">
                <Label for="name">名前</Label>
                <Input id="name" name="name" required placeholder="例:谷口" />
                <InputError :message="errors.name" />
            </div>

            <div class="grid gap-2">
                <Label for="username">ログインID(英小文字・数字・ . _ - が使えます)</Label>
                <Input
                    id="username"
                    name="username"
                    required
                    autocapitalize="none"
                    placeholder="例:te-taniguchi"
                />
                <InputError :message="errors.username" />
            </div>

            <div class="grid gap-2">
                <Label for="password">パスワード(空でも構いません)</Label>
                <Input id="password" name="password" type="password" autocomplete="new-password" />
                <InputError :message="errors.password" />
            </div>

            <div class="flex flex-col gap-3">
                <Button type="submit" size="lg" :disabled="processing">
                    <Spinner v-if="processing" />
                    追加する
                </Button>
                <Button as-child variant="outline" size="lg">
                    <Link href="/users">戻る</Link>
                </Button>
            </div>
        </Form>
    </div>
</template>
