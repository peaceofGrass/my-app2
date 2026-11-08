<script setup>
import Pagination from '@/Components/Pagination.vue';
import PostThumbnailPlaceholder from '@/Components/PostThumbnailPlaceholder.vue';
import BlogLayout from '@/Layouts/BlogLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed, reactive } from 'vue';

const props = defineProps({
    posts: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        default: () => ({ q: '' }),
    },
});

const search = computed(() => props.filters.q ?? '');

// Attachment ids whose image failed to load, so the card falls back to a placeholder.
const brokenImages = reactive(new Set());

const thumbnail = (post) =>
    post.attachments.find(
        (attachment) =>
            attachment.mime_type.startsWith('image/') &&
            !brokenImages.has(attachment.id),
    );

const formatDate = (value) =>
    new Date(value).toLocaleDateString('ko-KR', { dateStyle: 'medium' });
</script>

<template>
    <Head :title="search ? `'${search}' 검색` : 'Posts'" />

    <BlogLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2
                    class="text-xl font-semibold leading-tight text-stone-800 dark:text-stone-200"
                >
                    Posts
                </h2>
                <Link
                    v-if="$page.props.auth.user"
                    :href="route('posts.create')"
                    class="group inline-flex items-center gap-2 rounded-full border border-green-200 bg-green-50 px-4 py-1.5 text-sm font-medium text-green-700 shadow-sm transition duration-150 ease-in-out hover:-translate-y-0.5 hover:border-green-300 hover:bg-green-100 hover:shadow focus:outline-none focus:ring-2 focus:ring-orange-400 focus:ring-offset-2 active:translate-y-0 dark:border-green-800 dark:bg-green-900/30 dark:text-green-300 dark:hover:border-green-700 dark:hover:bg-green-900/50 dark:focus:ring-offset-stone-800"
                >
                    <svg
                        class="h-4 w-4 transition-transform duration-150 group-hover:-rotate-12"
                        xmlns="http://www.w3.org/2000/svg"
                        viewBox="0 0 20 20"
                        fill="currentColor"
                        aria-hidden="true"
                    >
                        <path
                            d="M13.586 3.586a2 2 0 1 1 2.828 2.828l-.793.793-2.828-2.828.793-.793ZM11.379 5.793 3 14.172V17h2.828l8.38-8.379-2.83-2.828Z"
                        />
                    </svg>
                    글쓰기
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div
                    v-if="search && posts.total > 0"
                    class="mb-6 flex flex-wrap items-center justify-between gap-3"
                >
                    <p
                        class="text-sm text-stone-600 dark:text-stone-400"
                        role="status"
                    >
                        <span
                            class="font-semibold text-stone-900 dark:text-stone-100"
                            >'{{ search }}'</span
                        >
                        검색 결과
                        <span
                            class="font-semibold text-orange-600 dark:text-orange-400"
                            >{{ posts.total.toLocaleString('ko-KR') }}</span
                        >건
                    </p>
                    <Link
                        :href="route('posts.index')"
                        class="rounded text-sm text-stone-500 transition hover:text-orange-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-orange-500 dark:text-stone-400 dark:hover:text-orange-400"
                    >
                        전체 글 보기
                    </Link>
                </div>

                <!-- No search results: a puzzled little sprout with a magnifier. -->
                <div
                    v-if="search && posts.data.length === 0"
                    class="flex min-h-[55vh] flex-col items-center justify-center text-center"
                    role="status"
                >
                    <svg
                        viewBox="0 0 160 150"
                        class="h-40 w-40 motion-safe:animate-float"
                        aria-hidden="true"
                    >
                        <ellipse
                            cx="74"
                            cy="140"
                            rx="40"
                            ry="6"
                            class="fill-stone-200 dark:fill-stone-700"
                        />
                        <path
                            d="M72 50V40"
                            stroke-width="4"
                            stroke-linecap="round"
                            class="stroke-green-600 dark:stroke-green-500"
                        />
                        <path
                            d="M72 42C70 30 59 24 50 27c2 10 11 16 22 15Z"
                            class="fill-green-500 dark:fill-green-400"
                        />
                        <path
                            d="M72 42c3-13 15-19 24-15-3 10-13 16-24 15Z"
                            class="fill-green-600 dark:fill-green-500"
                        />
                        <ellipse
                            cx="72"
                            cy="94"
                            rx="44"
                            ry="42"
                            class="fill-green-300 dark:fill-green-600"
                        />
                        <path
                            d="M52 78l8-3M92 78l-8-3"
                            stroke-width="3"
                            stroke-linecap="round"
                            class="stroke-stone-700 dark:stroke-stone-900"
                        />
                        <circle
                            cx="58"
                            cy="88"
                            r="4.5"
                            class="fill-stone-800 dark:fill-stone-900"
                        />
                        <circle
                            cx="86"
                            cy="88"
                            r="4.5"
                            class="fill-stone-800 dark:fill-stone-900"
                        />
                        <circle cx="59.5" cy="86.5" r="1.5" fill="#fff" />
                        <circle cx="87.5" cy="86.5" r="1.5" fill="#fff" />
                        <ellipse
                            cx="48"
                            cy="100"
                            rx="7"
                            ry="4"
                            class="fill-pink-300/80 dark:fill-pink-400/60"
                        />
                        <ellipse
                            cx="96"
                            cy="100"
                            rx="7"
                            ry="4"
                            class="fill-pink-300/80 dark:fill-pink-400/60"
                        />
                        <path
                            d="M64 106q4-4 8 0t8 0"
                            fill="none"
                            stroke-width="3"
                            stroke-linecap="round"
                            class="stroke-stone-700 dark:stroke-stone-900"
                        />
                        <path
                            d="M110 62q6 9 0 13q-6-4 0-13Z"
                            class="fill-sky-300 dark:fill-sky-400"
                        />
                        <path
                            d="M128 118l16 16"
                            stroke-width="8"
                            stroke-linecap="round"
                            class="stroke-orange-500 dark:stroke-orange-400"
                        />
                        <circle
                            cx="118"
                            cy="106"
                            r="15"
                            stroke-width="5"
                            class="fill-sky-100/80 stroke-stone-500 dark:fill-sky-900/60 dark:stroke-stone-400"
                        />
                        <ellipse
                            cx="104"
                            cy="112"
                            rx="8"
                            ry="7"
                            class="fill-green-300 dark:fill-green-600"
                        />
                        <text
                            x="128"
                            y="40"
                            font-size="30"
                            font-weight="700"
                            class="fill-orange-400 motion-safe:animate-pulse"
                        >
                            ?
                        </text>
                    </svg>

                    <h3
                        class="mt-6 text-xl font-bold text-stone-800 dark:text-stone-100"
                    >
                        앗, 찾는 글이 없어요!
                    </h3>
                    <p class="mt-2 text-sm text-stone-500 dark:text-stone-400">
                        <span
                            class="font-semibold text-orange-600 dark:text-orange-400"
                            >'{{ search }}'</span
                        >에 대한 검색 결과가 0건이에요.
                        <br />
                        다른 검색어로 다시 찾아볼까요?
                    </p>
                    <Link
                        :href="route('posts.index')"
                        class="mt-6 inline-flex items-center gap-2 rounded-full border border-green-200 bg-green-50 px-5 py-2 text-sm font-medium text-green-700 shadow-sm transition hover:-translate-y-0.5 hover:border-green-300 hover:bg-green-100 hover:shadow focus:outline-none focus:ring-2 focus:ring-orange-400 focus:ring-offset-2 active:translate-y-0 dark:border-green-800 dark:bg-green-900/30 dark:text-green-300 dark:hover:border-green-700 dark:hover:bg-green-900/50 dark:focus:ring-offset-stone-900"
                    >
                        전체 글 보기
                    </Link>
                </div>

                <p
                    v-else-if="posts.data.length === 0"
                    class="rounded-lg bg-white p-12 text-center text-stone-500 shadow-sm dark:bg-stone-800 dark:text-stone-400"
                >
                    아직 게시글이 없습니다.
                </p>

                <div
                    v-else
                    class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4"
                >
                    <Link
                        v-for="post in posts.data"
                        :key="post.id"
                        :href="route('posts.show', post.id)"
                        class="group flex flex-col overflow-hidden rounded-lg bg-white shadow-md transition hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-orange-500 dark:bg-stone-800"
                    >
                        <div
                            class="aspect-video w-full bg-stone-200 dark:bg-stone-700"
                        >
                            <img
                                v-if="thumbnail(post)"
                                :src="thumbnail(post).url"
                                :alt="thumbnail(post).original_name"
                                class="h-full w-full object-cover"
                                loading="lazy"
                                @error="brokenImages.add(thumbnail(post).id)"
                            />
                            <PostThumbnailPlaceholder v-else :seed="post.id" />
                        </div>

                        <div class="flex flex-1 flex-col p-5">
                            <h3
                                class="line-clamp-2 text-lg font-semibold text-stone-900 group-hover:text-orange-600 dark:text-stone-100 dark:group-hover:text-orange-400"
                            >
                                {{ post.title }}
                            </h3>
                            <p
                                class="mt-2 line-clamp-3 flex-1 text-sm text-stone-600 dark:text-stone-400"
                            >
                                {{ post.excerpt }}
                            </p>

                            <div
                                class="mt-4 flex items-center justify-between text-xs text-stone-500 dark:text-stone-400"
                            >
                                <span>
                                    {{ post.user.name }} ·
                                    <time :datetime="post.created_at">
                                        {{ formatDate(post.created_at) }}
                                    </time>
                                </span>
                                <span v-if="post.attachments.length">
                                    첨부 {{ post.attachments.length }}
                                </span>
                            </div>
                        </div>
                    </Link>
                </div>

                <Pagination :paginator="posts" class="mt-10" />
            </div>
        </div>
    </BlogLayout>
</template>
