<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="min-h-screen bg-gray-100 dark:bg-gray-900">
        <nav
            class="border-b border-gray-100 bg-white dark:border-gray-700 dark:bg-gray-800"
        >
            <div
                class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8"
            >
                <Link
                    :href="route('posts.index')"
                    class="flex items-center gap-3"
                >
                    <ApplicationLogo
                        class="block h-9 w-auto fill-current text-gray-800 dark:text-gray-200"
                    />
                    <span
                        class="text-lg font-semibold text-gray-800 dark:text-gray-200"
                    >
                        Blog
                    </span>
                </Link>

                <div class="flex items-center gap-4 text-sm font-medium">
                    <template v-if="$page.props.auth.user">
                        <span
                            class="hidden text-gray-500 sm:inline dark:text-gray-400"
                        >
                            {{ $page.props.auth.user.name }}
                        </span>
                        <Link
                            :href="route('dashboard')"
                            class="text-gray-700 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white"
                        >
                            Dashboard
                        </Link>
                    </template>
                    <template v-else>
                        <Link
                            :href="route('login')"
                            class="text-gray-700 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white"
                        >
                            Log in
                        </Link>
                        <Link
                            :href="route('register')"
                            class="text-gray-700 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white"
                        >
                            Register
                        </Link>
                    </template>
                </div>
            </div>
        </nav>

        <header class="bg-white shadow dark:bg-gray-800" v-if="$slots.header">
            <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                <slot name="header" />
            </div>
        </header>

        <main>
            <slot />
        </main>
    </div>
</template>
