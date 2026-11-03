<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import UserMenu from '@/Components/UserMenu.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="min-h-screen bg-white dark:bg-stone-900">
        <nav
            class="border-b border-orange-300 bg-white dark:border-orange-800 dark:bg-stone-800"
        >
            <div
                class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8"
            >
                <Link
                    :href="route('posts.index')"
                    class="flex items-center gap-3"
                >
                    <ApplicationLogo
                        class="block h-9 w-auto fill-current text-green-600 dark:text-green-400"
                    />
                    <span
                        class="text-lg font-semibold text-stone-800 dark:text-stone-200"
                    >
                        Blog
                    </span>
                </Link>

                <div class="flex items-center gap-4 text-sm font-medium">
                    <template v-if="$page.props.auth.user">
                        <Link
                            :href="route('dashboard')"
                            class="text-stone-700 hover:text-stone-900 dark:text-stone-300 dark:hover:text-white"
                        >
                            Dashboard
                        </Link>
                        <UserMenu />
                    </template>
                    <template v-else>
                        <Link
                            :href="route('login')"
                            class="text-stone-700 hover:text-stone-900 dark:text-stone-300 dark:hover:text-white"
                        >
                            Log in
                        </Link>
                        <Link
                            :href="route('register')"
                            class="text-stone-700 hover:text-stone-900 dark:text-stone-300 dark:hover:text-white"
                        >
                            Register
                        </Link>
                    </template>
                </div>
            </div>
        </nav>

        <header
            class="border-b border-orange-300 bg-white dark:border-orange-800 dark:bg-stone-800"
            v-if="$slots.header"
        >
            <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                <slot name="header" />
            </div>
        </header>

        <main>
            <slot />
        </main>
    </div>
</template>
