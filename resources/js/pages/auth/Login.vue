<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { store } from '@/routes/login';

defineOptions({
    layout: {
        title: 'ログイン',
        description: 'ログインIDとパスワードを入力してください',
    },
});

defineProps<{
    status?: string;
}>();
</script>

<template>
    <Head title="ログイン" />

    <!-- 無効なアカウントでログインしたときなどのメッセージ -->
    <div
        v-if="status"
        class="mb-4 text-center text-sm font-medium text-green-600"
    >
        {{ status }}
    </div>

    <Form
        v-bind="store.form()"
        :reset-on-success="['password']"
        v-slot="{ errors, processing }"
        class="flex flex-col gap-6"
    >
        <div class="grid gap-6">
            <div class="grid gap-2">
                <Label for="username">ログインID</Label>
                <Input
                    id="username"
                    type="text"
                    name="username"
                    required
                    v-focus
                    :tabindex="1"
                    autocomplete="username"
                    autocapitalize="none"
                    placeholder="例:te-taniguchi"
                />
                <InputError :message="errors.username" />
            </div>

            <div class="grid gap-2">
                <Label for="password">パスワード</Label>
                <PasswordInput
                    id="password"
                    name="password"
                    required
                    :tabindex="2"
                    autocomplete="current-password"
                    placeholder="パスワード"
                />
                <InputError :message="errors.password" />
            </div>

            <Button
                type="submit"
                class="mt-2 w-full"
                :tabindex="3"
                :disabled="processing"
                data-test="login-button"
            >
                <Spinner v-if="processing" />
                ログイン
            </Button>
        </div>
    </Form>
</template>
