<script setup>
import { router, usePage } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const page = usePage();

// The search shown on /posts?q=…, so the box keeps it after navigating.
const currentSearch = () =>
    new URLSearchParams(page.url.split('?')[1] ?? '').get('q') ?? '';

const search = ref(currentSearch());

watch(
    () => page.url,
    () => {
        search.value = currentSearch();
    },
);

const submit = () => {
    const q = search.value.trim();

    router.get(route('posts.index'), q ? { q } : {});
};
</script>

<template>
    <form role="search" class="relative" @submit.prevent="submit">
        <svg
            viewBox="0 0 20 20"
            fill="currentColor"
            class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-stone-400 dark:text-stone-500"
            aria-hidden="true"
        >
            <path
                fill-rule="evenodd"
                d="M9 3.5a5.5 5.5 0 1 0 0 11 5.5 5.5 0 0 0 0-11ZM2 9a7 7 0 1 1 12.45 4.39l3.08 3.08a.75.75 0 1 1-1.06 1.06l-3.08-3.08A7 7 0 0 1 2 9Z"
                clip-rule="evenodd"
            />
        </svg>
        <input
            v-model="search"
            type="search"
            name="q"
            maxlength="100"
            placeholder="검색"
            aria-label="게시글 검색"
            class="w-28 rounded-full border-stone-200 bg-stone-50 py-1.5 pl-9 pr-3 text-sm text-stone-700 transition-[width] placeholder:text-stone-400 focus:border-orange-400 focus:bg-white focus:ring-orange-400 sm:w-48 sm:focus:w-60 dark:border-stone-700 dark:bg-stone-900 dark:text-stone-200 dark:placeholder:text-stone-500 dark:focus:border-orange-600 dark:focus:bg-stone-900 dark:focus:ring-orange-600"
        />
    </form>
</template>
