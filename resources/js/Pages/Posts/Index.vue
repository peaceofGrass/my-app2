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

const formatDate = (value) =>
    new Date(value).toLocaleDateString('ko-KR', { dateStyle: 'medium' });
</script>

<template>
    <Head title="Posts" />

    <BlogLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2
                    class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200"
                >
                    Posts
                </h2>
                <Link
                    v-if="$page.props.auth.user"
                    :href="route('posts.create')"
                    class="inline-flex items-center rounded-md border border-transparent bg-gray-800 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white transition duration-150 ease-in-out hover:bg-gray-700 focus:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 active:bg-gray-900 dark:bg-gray-200 dark:text-gray-800 dark:hover:bg-white dark:focus:bg-white dark:focus:ring-offset-gray-800 dark:active:bg-gray-300"
                >
                    글쓰기
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <p
                    v-if="posts.data.length === 0"
                    class="rounded-lg bg-white p-12 text-center text-gray-500 shadow-sm dark:bg-gray-800 dark:text-gray-400"
                >
                    아직 게시글이 없습니다.
                </p>

                <div v-else class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    <Link
                        v-for="post in posts.data"
                        :key="post.id"
                        :href="route('posts.show', post.id)"
                        class="group flex flex-col overflow-hidden rounded-lg bg-white shadow-sm transition hover:shadow-md focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:bg-gray-800"
                    >
                        <div
                            class="aspect-video w-full bg-gray-200 dark:bg-gray-700"
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
                                class="line-clamp-2 text-lg font-semibold text-gray-900 group-hover:text-indigo-600 dark:text-gray-100 dark:group-hover:text-indigo-400"
                            >
                                {{ post.title }}
                            </h3>
                            <p
                                class="mt-2 line-clamp-3 flex-1 text-sm text-gray-600 dark:text-gray-400"
                            >
                                {{ post.content }}
                            </p>

                            <div
                                class="mt-4 flex items-center justify-between text-xs text-gray-500 dark:text-gray-400"
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
                    class="mt-8 flex flex-wrap justify-center gap-1"
                    aria-label="Pagination"
                >
                    <template v-for="link in posts.links" :key="link.label">
                        <Link
                            v-if="link.url"
                            :href="link.url"
                            preserve-scroll
                            class="rounded-md px-3 py-2 text-sm"
                            :class="
                                link.active
                                    ? 'bg-gray-800 text-white dark:bg-gray-200 dark:text-gray-800'
                                    : 'bg-white text-gray-700 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700'
                            "
                        >
                            <span v-html="link.label" />
                        </Link>
                        <span
                            v-else
                            class="rounded-md px-3 py-2 text-sm text-gray-400 dark:text-gray-600"
                            v-html="link.label"
                        />
                    </template>
                </nav>
            </div>
        </div>
    </BlogLayout>
</template>
