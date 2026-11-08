<script setup>
import AttachmentImages from '@/Components/AttachmentImages.vue';
import CommentForm from '@/Components/CommentForm.vue';
import CommentItem from '@/Components/CommentItem.vue';
import ReactionButtons from '@/Components/ReactionButtons.vue';
import BlogLayout from '@/Layouts/BlogLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    post: {
        type: Object,
        required: true,
    },
    comments: {
        type: Array,
        required: true,
    },
});

const page = usePage();

const isAuthor = computed(
    () => page.props.auth.user?.id === props.post.user_id,
);

const images = computed(() =>
    props.post.attachments.filter((attachment) =>
        attachment.mime_type.startsWith('image/'),
    ),
);

const files = computed(() =>
    props.post.attachments.filter(
        (attachment) => !attachment.mime_type.startsWith('image/'),
    ),
);

const commentCount = computed(() => {
    const count = (comments) =>
        comments.reduce(
            (total, comment) => total + 1 + count(comment.children),
            0,
        );

    return count(props.comments);
});

const formatDate = (value) =>
    new Date(value).toLocaleString('ko-KR', {
        dateStyle: 'long',
        timeStyle: 'short',
    });

const formatFileSize = (bytes) => {
    const units = ['B', 'KB', 'MB', 'GB'];
    let size = bytes;
    let unit = 0;

    while (size >= 1024 && unit < units.length - 1) {
        size /= 1024;
        unit++;
    }

    return `${size.toFixed(unit === 0 ? 0 : 1)} ${units[unit]}`;
};

const destroy = () => {
    if (confirm('이 게시글을 삭제할까요? 댓글과 첨부파일도 함께 삭제됩니다.')) {
        router.delete(route('posts.destroy', props.post.id));
    }
};
</script>

<template>
    <Head :title="post.title" />

    <BlogLayout>
        <div class="py-12">
            <div class="mx-auto max-w-4xl space-y-6 px-4 sm:px-6 lg:px-8">
                <Link
                    :href="route('posts.index')"
                    class="group inline-flex items-center gap-1.5 rounded-full border border-stone-200 bg-white py-1.5 pe-4 ps-3 text-sm font-medium text-stone-600 shadow-sm transition duration-150 ease-in-out hover:border-orange-300 hover:text-orange-600 hover:shadow focus:outline-none focus-visible:ring-2 focus-visible:ring-orange-500 focus-visible:ring-offset-2 active:scale-[0.98] dark:border-stone-700 dark:bg-stone-800 dark:text-stone-300 dark:hover:border-orange-700 dark:hover:text-orange-400 dark:focus-visible:ring-offset-stone-900"
                >
                    <svg
                        viewBox="0 0 20 20"
                        fill="currentColor"
                        class="h-4 w-4 transition-transform duration-200 motion-safe:group-hover:-translate-x-0.5"
                        aria-hidden="true"
                    >
                        <path
                            fill-rule="evenodd"
                            d="M17 10a.75.75 0 0 1-.75.75H5.61l4.17 3.97a.75.75 0 1 1-1.04 1.08l-5.5-5.25a.75.75 0 0 1 0-1.08l5.5-5.25a.75.75 0 1 1 1.04 1.08L5.6 9.25h10.65A.75.75 0 0 1 17 10Z"
                            clip-rule="evenodd"
                        />
                    </svg>
                    목록으로
                </Link>

                <article
                    class="overflow-hidden rounded-lg bg-white shadow-sm dark:bg-stone-800"
                >
                    <header
                        class="border-b border-orange-300 p-6 sm:p-8 dark:border-orange-800"
                    >
                        <div class="flex items-start justify-between gap-4">
                            <h1
                                class="text-2xl font-bold text-stone-900 sm:text-3xl dark:text-stone-100"
                            >
                                {{ post.title }}
                            </h1>
                            <span
                                v-if="!post.is_published"
                                class="shrink-0 rounded-full bg-yellow-100 px-3 py-1 text-xs font-medium text-yellow-800 dark:bg-yellow-900/50 dark:text-yellow-300"
                            >
                                비공개
                            </span>
                        </div>
                        <div
                            class="mt-3 flex flex-wrap items-center justify-between gap-3 text-sm text-stone-500 dark:text-stone-400"
                        >
                            <span>
                                {{ post.user.name }} ·
                                <time :datetime="post.created_at">
                                    {{ formatDate(post.created_at) }}
                                </time>
                            </span>
                            <div v-if="isAuthor" class="flex gap-2">
                                <Link
                                    :href="route('posts.edit', post.id)"
                                    class="group inline-flex items-center gap-1.5 rounded-full border border-orange-200 bg-orange-50 px-4 py-1.5 text-sm font-medium text-orange-700 transition duration-150 ease-in-out hover:border-orange-300 hover:bg-orange-100 hover:shadow-sm focus:outline-none focus-visible:ring-2 focus-visible:ring-orange-500 focus-visible:ring-offset-2 active:scale-[0.98] motion-safe:hover:-translate-y-0.5 motion-safe:active:translate-y-0 dark:border-orange-800 dark:bg-orange-900/30 dark:text-orange-300 dark:hover:border-orange-700 dark:hover:bg-orange-900/50 dark:focus-visible:ring-offset-stone-800"
                                >
                                    <svg
                                        class="h-4 w-4 transition-transform duration-200 motion-safe:group-hover:-rotate-12"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        aria-hidden="true"
                                    >
                                        <path d="M12 20h9" />
                                        <path
                                            d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"
                                        />
                                    </svg>
                                    수정
                                </Link>
                                <button
                                    type="button"
                                    class="group inline-flex items-center gap-1.5 rounded-full border border-transparent px-4 py-1.5 text-sm font-medium text-red-600 transition duration-150 ease-in-out hover:border-red-200 hover:bg-red-50 hover:text-red-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-500 focus-visible:ring-offset-2 active:scale-[0.98] dark:text-red-400 dark:hover:border-red-900 dark:hover:bg-red-900/30 dark:hover:text-red-300 dark:focus-visible:ring-offset-stone-800"
                                    @click="destroy"
                                >
                                    <svg
                                        class="h-4 w-4 transition-transform duration-200 motion-safe:group-hover:scale-110"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        aria-hidden="true"
                                    >
                                        <path d="M3 6h18" />
                                        <path
                                            d="M8 6V4a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2"
                                        />
                                        <path
                                            d="m19 6-1 14a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1L5 6"
                                        />
                                        <path d="M10 11v6M14 11v6" />
                                    </svg>
                                    삭제
                                </button>
                            </div>
                        </div>
                    </header>

                    <!-- Sanitized on the server (PostRequest / PostContent) before it is stored. -->
                    <div
                        class="prose prose-stone max-w-none p-6 dark:prose-invert prose-a:text-orange-600 prose-img:rounded-md sm:p-8 dark:prose-a:text-orange-400"
                        v-html="post.content"
                    />

                    <div class="px-6 pb-6 sm:px-8 sm:pb-8">
                        <ReactionButtons
                            :url="route('posts.reaction', post.id)"
                            :likes="post.likes_count"
                            :dislikes="post.dislikes_count"
                            :my-reaction="post.my_reaction"
                        />
                    </div>

                    <section
                        v-if="images.length"
                        class="border-t border-orange-300 p-6 sm:p-8 dark:border-orange-800"
                    >
                        <h2
                            class="mb-4 text-sm font-semibold uppercase tracking-wide text-stone-500 dark:text-stone-400"
                        >
                            이미지 ({{ images.length }})
                        </h2>
                        <AttachmentImages :attachments="images" />
                    </section>

                    <section
                        v-if="files.length"
                        class="border-t border-orange-300 p-6 sm:p-8 dark:border-orange-800"
                    >
                        <h2
                            class="mb-4 text-sm font-semibold uppercase tracking-wide text-stone-500 dark:text-stone-400"
                        >
                            첨부파일 ({{ files.length }})
                        </h2>
                        <ul
                            class="divide-y divide-orange-200 rounded-md border border-orange-300 dark:divide-orange-900 dark:border-orange-800"
                        >
                            <li
                                v-for="file in files"
                                :key="file.id"
                                class="flex items-center justify-between gap-4 px-4 py-3 text-sm"
                            >
                                <a
                                    :href="file.url"
                                    class="truncate text-orange-600 hover:underline dark:text-orange-400"
                                    download
                                >
                                    {{ file.original_name }}
                                </a>
                                <span
                                    class="shrink-0 text-stone-500 dark:text-stone-400"
                                >
                                    {{ formatFileSize(file.file_size) }}
                                </span>
                            </li>
                        </ul>
                    </section>
                </article>

                <section
                    class="space-y-6 rounded-lg bg-white p-6 shadow-sm sm:p-8 dark:bg-stone-800"
                >
                    <h2
                        class="text-lg font-semibold text-stone-900 dark:text-stone-100"
                    >
                        댓글 {{ commentCount }}
                    </h2>

                    <CommentForm
                        v-if="$page.props.auth.user"
                        :post-id="post.id"
                    />
                    <p
                        v-else
                        class="rounded-md bg-stone-50 p-4 text-sm text-stone-600 dark:bg-stone-900/50 dark:text-stone-400"
                    >
                        댓글을 작성하려면
                        <Link
                            :href="route('login')"
                            class="font-medium text-orange-600 hover:underline dark:text-orange-400"
                        >
                            로그인
                        </Link>
                        하세요.
                    </p>

                    <ul v-if="comments.length" class="space-y-3">
                        <CommentItem
                            v-for="comment in comments"
                            :key="comment.id"
                            :comment="comment"
                        />
                    </ul>
                    <p
                        v-else
                        class="text-sm text-stone-500 dark:text-stone-400"
                    >
                        아직 댓글이 없습니다.
                    </p>
                </section>
            </div>
        </div>
    </BlogLayout>
</template>
