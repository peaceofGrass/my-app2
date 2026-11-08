<script setup>
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    // A Laravel length-aware paginator, as serialized to the page props.
    paginator: {
        type: Object,
        required: true,
    },
});

// The first and last links are the previous/next arrows, rendered separately.
const pages = computed(() => props.paginator.links.slice(1, -1));

const progress = computed(
    () =>
        `${(props.paginator.current_page / props.paginator.last_page) * 100}%`,
);

// In the compact layout, a new page should start from the top of the list.
const keepScrollOnWideScreens = () =>
    window.matchMedia('(min-width: 768px)').matches;

const arrowClass = (url) => [
    'flex h-11 w-11 shrink-0 items-center justify-center rounded-full transition focus:outline-none focus-visible:ring-2 focus-visible:ring-orange-500',
    url
        ? 'text-stone-700 hover:bg-green-50 hover:text-green-700 active:scale-95 dark:text-stone-200 dark:hover:bg-green-900/40 dark:hover:text-green-300'
        : 'cursor-not-allowed text-stone-300 dark:text-stone-600',
];
</script>

<template>
    <nav
        v-if="paginator.last_page > 1"
        class="flex justify-center"
        aria-label="페이지 이동"
    >
        <div
            class="flex w-full max-w-xs items-center justify-between gap-0.5 rounded-full bg-white p-1.5 shadow-sm ring-1 ring-stone-200 md:w-auto md:max-w-none dark:bg-stone-800 dark:ring-stone-700"
        >
            <component
                :is="paginator.prev_page_url ? Link : 'span'"
                :href="paginator.prev_page_url ?? undefined"
                :preserve-scroll="
                    paginator.prev_page_url
                        ? keepScrollOnWideScreens
                        : undefined
                "
                :class="arrowClass(paginator.prev_page_url)"
                aria-label="이전 페이지"
                :aria-disabled="paginator.prev_page_url ? undefined : 'true'"
            >
                <svg
                    class="h-5 w-5"
                    viewBox="0 0 20 20"
                    fill="currentColor"
                    aria-hidden="true"
                >
                    <path
                        fill-rule="evenodd"
                        d="M12.8 4.2a.75.75 0 0 1 0 1.06L8.06 10l4.74 4.74a.75.75 0 1 1-1.06 1.06l-5.27-5.27a.75.75 0 0 1 0-1.06l5.27-5.27a.75.75 0 0 1 1.06 0Z"
                        clip-rule="evenodd"
                    />
                </svg>
            </component>

            <!-- Small screens: a compact "3 / 12" with a progress bar. -->
            <div class="flex flex-1 flex-col items-center gap-1.5 md:hidden">
                <p
                    class="text-sm tabular-nums text-stone-500 dark:text-stone-400"
                >
                    <span class="sr-only">
                        전체 {{ paginator.last_page }}페이지 중
                        {{ paginator.current_page }}페이지
                    </span>
                    <span aria-hidden="true">
                        <span
                            class="font-bold text-stone-900 dark:text-stone-100"
                            >{{ paginator.current_page }}</span
                        >
                        / {{ paginator.last_page }}
                    </span>
                </p>
                <div
                    class="h-1 w-20 overflow-hidden rounded-full bg-stone-200 dark:bg-stone-700"
                    aria-hidden="true"
                >
                    <div
                        class="h-full rounded-full bg-green-600 transition-all duration-300 dark:bg-green-500"
                        :style="{ width: progress }"
                    />
                </div>
            </div>

            <!-- Wider screens: every page number. -->
            <ul class="hidden items-center gap-0.5 md:flex">
                <li v-for="(link, index) in pages" :key="index">
                    <span
                        v-if="link.url === null"
                        class="flex h-10 w-10 items-center justify-center text-stone-400 dark:text-stone-500"
                        aria-hidden="true"
                    >
                        …
                    </span>
                    <span
                        v-else-if="link.active"
                        class="flex h-10 w-10 items-center justify-center rounded-full bg-green-700 text-sm font-bold tabular-nums text-white shadow-sm"
                        aria-current="page"
                        :aria-label="`${link.label} 페이지`"
                    >
                        {{ link.label }}
                    </span>
                    <Link
                        v-else
                        :href="link.url"
                        preserve-scroll
                        class="flex h-10 w-10 items-center justify-center rounded-full text-sm font-medium tabular-nums text-stone-600 transition hover:bg-green-50 hover:text-green-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-orange-500 active:scale-95 dark:text-stone-300 dark:hover:bg-green-900/40 dark:hover:text-green-300"
                        :aria-label="`${link.label} 페이지`"
                    >
                        {{ link.label }}
                    </Link>
                </li>
            </ul>

            <component
                :is="paginator.next_page_url ? Link : 'span'"
                :href="paginator.next_page_url ?? undefined"
                :preserve-scroll="
                    paginator.next_page_url
                        ? keepScrollOnWideScreens
                        : undefined
                "
                :class="arrowClass(paginator.next_page_url)"
                aria-label="다음 페이지"
                :aria-disabled="paginator.next_page_url ? undefined : 'true'"
            >
                <svg
                    class="h-5 w-5"
                    viewBox="0 0 20 20"
                    fill="currentColor"
                    aria-hidden="true"
                >
                    <path
                        fill-rule="evenodd"
                        d="M7.2 15.8a.75.75 0 0 1 0-1.06L11.94 10 7.2 5.26A.75.75 0 1 1 8.26 4.2l5.27 5.27a.75.75 0 0 1 0 1.06l-5.27 5.27a.75.75 0 0 1-1.06 0Z"
                        clip-rule="evenodd"
                    />
                </svg>
            </component>
        </div>
    </nav>
</template>
