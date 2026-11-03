<script setup>
import BlogLayout from '@/Layouts/BlogLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { reactive } from 'vue';

defineProps({
    posts: {
        type: Object,
        required: true,
    },
});

// Attachment ids whose image failed to load, so the card falls back to a placeholder.
const brokenImages = reactive(new Set());

const thumbnail = (post) =>
    post.attachments.find(
        (attachment) =>
            attachment.mime_type.startsWith('image/') &&
            !brokenImages.has(attachment.id),
    );

// Laravel's paginator labels the ends "&laquo; Previous" / "Next &raquo;";
// show them as arrows inside the footprint instead.
const isPrevious = (link) => link.label.includes('Previous');
const isNext = (link) => link.label.includes('Next');

const stepLabel = (link) => {
    if (isPrevious(link)) return '‹';
    if (isNext(link)) return '›';

    return link.label;
};

const pageLabel = (link) => {
    if (isPrevious(link)) return '이전 페이지';
    if (isNext(link)) return '다음 페이지';

    return `${link.label} 페이지`;
};

const formatDate = (value) =>
    new Date(value).toLocaleDateString('ko-KR', { dateStyle: 'medium' });
</script>

<template>
    <Head title="Posts" />

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
                <p
                    v-if="posts.data.length === 0"
                    class="rounded-lg bg-white p-12 text-center text-stone-500 shadow-sm dark:bg-stone-800 dark:text-stone-400"
                >
                    아직 게시글이 없습니다.
                </p>

                <div v-else class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    <Link
                        v-for="post in posts.data"
                        :key="post.id"
                        :href="route('posts.show', post.id)"
                        class="group flex flex-col overflow-hidden rounded-lg bg-white shadow-sm transition hover:shadow-md focus:outline-none focus:ring-2 focus:ring-orange-500 dark:bg-stone-800"
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
                                {{ post.content }}
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

                <nav
                    v-if="posts.last_page > 1"
                    class="mt-10 flex flex-wrap items-center justify-center gap-x-1 gap-y-4"
                    aria-label="Pagination"
                >
                    <template
                        v-for="(link, index) in posts.links"
                        :key="link.label"
                    >
                        <span
                            v-if="link.label === '...'"
                            class="px-1 text-green-400"
                        >
                            &middot;&middot;&middot;
                        </span>
                        <component
                            :is="link.url ? Link : 'span'"
                            v-else
                            :href="link.url"
                            preserve-scroll
                            class="group relative flex h-10 w-16 items-center justify-center transition"
                            :class="[
                                index % 2 === 0
                                    ? '-translate-y-2'
                                    : 'translate-y-2',
                                link.url && !link.active
                                    ? 'hover:scale-110'
                                    : '',
                            ]"
                            :aria-label="pageLabel(link)"
                            :aria-current="link.active ? 'page' : undefined"
                        >
                            <!-- A right-pointing footprint; odd steps are mirrored into the other foot. -->
                            <svg
                                viewBox="0 0 64 40"
                                class="absolute inset-0 h-full w-full fill-current drop-shadow-sm"
                                :class="[
                                    index % 2 === 0 ? '' : '-scale-y-100',
                                    link.active
                                        ? 'text-green-700 dark:text-green-500'
                                        : link.url
                                          ? 'text-green-500 group-hover:text-green-600 dark:text-green-700 dark:group-hover:text-green-600'
                                          : 'text-green-200 dark:text-green-900',
                                ]"
                                aria-hidden="true"
                            >
                                <path
                                    d="M5 21c0-6 5-9 11-8.5 7 .5 12-3 21-3 7 0 11 4 11 10.5S44 31 37 31c-8 0-13-3-21-2.5C10 29 5 27 5 21z"
                                />
                                <circle cx="54" cy="10" r="4.2" />
                                <circle cx="58.5" cy="16.5" r="3" />
                                <circle cx="59.5" cy="22" r="2.6" />
                                <circle cx="58.5" cy="27" r="2.2" />
                                <circle cx="55.5" cy="31" r="1.9" />
                            </svg>
                            <span
                                class="relative pr-3 text-sm font-bold"
                                :class="
                                    link.url || link.active
                                        ? 'text-white'
                                        : 'text-green-50 dark:text-green-800'
                                "
                            >
                                {{ stepLabel(link) }}
                            </span>
                        </component>
                    </template>
                </nav>
            </div>
        </div>
    </BlogLayout>
</template>
