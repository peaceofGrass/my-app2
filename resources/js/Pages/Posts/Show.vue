<script setup>
import AttachmentImages from '@/Components/AttachmentImages.vue';
import CommentForm from '@/Components/CommentForm.vue';
import CommentItem from '@/Components/CommentItem.vue';
import DangerButton from '@/Components/DangerButton.vue';
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
                    class="group inline-flex items-center gap-2 rounded-full border border-sky-200 bg-white py-1.5 pl-1.5 pr-4 text-sm font-medium text-sky-700 shadow-sm transition hover:-translate-y-0.5 hover:border-sky-300 hover:bg-sky-50 hover:shadow focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2 dark:border-sky-800 dark:bg-stone-800 dark:text-sky-300 dark:hover:bg-stone-700 dark:focus:ring-offset-stone-900"
                >
                    <span
                        class="flex h-7 w-7 items-center justify-center rounded-full bg-sky-100 text-sky-600 transition group-hover:-translate-x-0.5 group-hover:bg-sky-500 group-hover:text-white dark:bg-sky-900/60 dark:text-sky-300"
                    >
                        <svg
                            viewBox="0 0 20 20"
                            fill="currentColor"
                            class="h-4 w-4"
                            aria-hidden="true"
                        >
                            <path
                                fill-rule="evenodd"
                                d="M17 10a.75.75 0 0 1-.75.75H5.61l4.17 3.97a.75.75 0 1 1-1.04 1.08l-5.5-5.25a.75.75 0 0 1 0-1.08l5.5-5.25a.75.75 0 1 1 1.04 1.08L5.6 9.25h10.65A.75.75 0 0 1 17 10Z"
                                clip-rule="evenodd"
                            />
                        </svg>
                    </span>
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
                                    class="inline-flex items-center rounded-md border border-stone-300 bg-white px-4 py-2 text-xs font-semibold uppercase tracking-widest text-stone-700 shadow-sm transition duration-150 ease-in-out hover:bg-stone-50 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2 dark:border-stone-500 dark:bg-stone-800 dark:text-stone-300 dark:hover:bg-stone-700 dark:focus:ring-offset-stone-800"
                                >
                                    수정
                                </Link>
                                <DangerButton @click="destroy"
                                    >삭제</DangerButton
                                >
                            </div>
                        </div>
                    </header>

                    <div
                        class="whitespace-pre-line p-6 leading-relaxed text-stone-800 sm:p-8 dark:text-stone-200"
                    >
                        {{ post.content }}
                    </div>

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
