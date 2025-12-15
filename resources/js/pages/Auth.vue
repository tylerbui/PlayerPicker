<script setup lang="ts">
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import AuthBase from '@/layouts/AuthLayout.vue';
import { store as loginStore } from '@/routes/login';
import { request } from '@/routes/password';
import { store as registerStore } from '@/routes/register';
import { Form, Head } from '@inertiajs/vue3';

const props = defineProps<{
    status?: string;
    canResetPassword: boolean;
    canRegister: boolean;
}>();

const activeTab = ref('login');
</script>

<template>
    <AuthBase>
        <Head title="Welcome" />

        <Tabs v-model="activeTab" class="w-full">
            <TabsList class="grid w-full grid-cols-2">
                <TabsTrigger value="login">Log in</TabsTrigger>
                <TabsTrigger value="signup" v-if="canRegister">Sign up</TabsTrigger>
            </TabsList>

            <!-- Login Tab -->
            <TabsContent value="login">
                <div class="mb-4 text-center">
                    <h2 class="text-2xl font-semibold tracking-tight">
                        Welcome back
                    </h2>
                    <p class="text-sm text-muted-foreground mt-1">
                        Enter your credentials to access your account
                    </p>
                </div>

                <div
                    v-if="status"
                    class="mb-4 text-center text-sm font-medium text-green-600"
                >
                    {{ status }}
                </div>

                <Form
                    v-bind="loginStore.form()"
                    :reset-on-success="['password']"
                    v-slot="{ errors, processing }"
                    class="flex flex-col gap-6"
                >
                    <div class="grid gap-4">
                        <div class="grid gap-2">
                            <Label for="login-email">Email address</Label>
                            <Input
                                id="login-email"
                                type="email"
                                name="email"
                                required
                                autofocus
                                autocomplete="email"
                                placeholder="email@example.com"
                            />
                            <InputError :message="errors.email" />
                        </div>

                        <div class="grid gap-2">
                            <div class="flex items-center justify-between">
                                <Label for="login-password">Password</Label>
                                <TextLink
                                    v-if="canResetPassword"
                                    :href="request()"
                                    class="text-sm"
                                >
                                    Forgot password?
                                </TextLink>
                            </div>
                            <Input
                                id="login-password"
                                type="password"
                                name="password"
                                required
                                autocomplete="current-password"
                                placeholder="Enter your password"
                            />
                            <InputError :message="errors.password" />
                        </div>

                        <div class="flex items-center">
                            <Label for="remember" class="flex items-center space-x-3">
                                <Checkbox id="remember" name="remember" />
                                <span class="text-sm">Remember me</span>
                            </Label>
                        </div>

                        <Button
                            type="submit"
                            class="w-full"
                            :disabled="processing"
                            data-test="login-button"
                        >
                            <Spinner v-if="processing" />
                            Log in
                        </Button>
                    </div>
                </Form>
            </TabsContent>

            <!-- Signup Tab -->
            <TabsContent value="signup" v-if="canRegister">
                <div class="mb-4 text-center">
                    <h2 class="text-2xl font-semibold tracking-tight">
                        Create an account
                    </h2>
                    <p class="text-sm text-muted-foreground mt-1">
                        Enter your details to get started
                    </p>
                </div>

                <Form
                    v-bind="registerStore.form()"
                    :reset-on-success="['password', 'password_confirmation']"
                    v-slot="{ errors, processing }"
                    class="flex flex-col gap-6"
                >
                    <div class="grid gap-4">
                        <div class="grid gap-2">
                            <Label for="name">Name</Label>
                            <Input
                                id="name"
                                type="text"
                                required
                                autofocus
                                autocomplete="name"
                                name="name"
                                placeholder="Full name"
                            />
                            <InputError :message="errors.name" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="signup-email">Email address</Label>
                            <Input
                                id="signup-email"
                                type="email"
                                required
                                autocomplete="email"
                                name="email"
                                placeholder="email@example.com"
                            />
                            <InputError :message="errors.email" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="signup-password">Password</Label>
                            <Input
                                id="signup-password"
                                type="password"
                                required
                                autocomplete="new-password"
                                name="password"
                                placeholder="Create a password"
                            />
                            <InputError :message="errors.password" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="password_confirmation">Confirm password</Label>
                            <Input
                                id="password_confirmation"
                                type="password"
                                required
                                autocomplete="new-password"
                                name="password_confirmation"
                                placeholder="Confirm your password"
                            />
                            <InputError :message="errors.password_confirmation" />
                        </div>

                        <Button
                            type="submit"
                            class="w-full"
                            :disabled="processing"
                            data-test="register-user-button"
                        >
                            <Spinner v-if="processing" />
                            Create account
                        </Button>
                    </div>
                </Form>
            </TabsContent>
        </Tabs>
    </AuthBase>
</template>
