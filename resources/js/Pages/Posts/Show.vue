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
                    class="text-sm text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-100"
                >
                    &larr; 목록으로
                </Link>

                <article
                    class="overflow-hidden rounded-lg bg-white shadow-sm dark:bg-gray-800"
                >
                    <header
                        class="border-b border-gray-100 p-6 sm:p-8 dark:border-gray-700"
                    >
                        <div class="flex items-start justify-between gap-4">
                            <h1
                                class="text-2xl font-bold text-gray-900 sm:text-3xl dark:text-gray-100"
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
                            class="mt-3 flex flex-wrap items-center justify-between gap-3 text-sm text-gray-500 dark:text-gray-400"
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
                                    class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-xs font-semibold uppercase tracking-widest text-gray-700 shadow-sm transition duration-150 ease-in-out hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:border-gray-500 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700 dark:focus:ring-offset-gray-800"
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
                        class="whitespace-pre-line p-6 leading-relaxed text-gray-800 sm:p-8 dark:text-gray-200"
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
                        class="border-t border-gray-100 p-6 sm:p-8 dark:border-gray-700"
                    >
                        <h2
                            class="mb-4 text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400"
                        >
                            이미지 ({{ images.length }})
                        </h2>
                        <AttachmentImages :attachments="images" />
                    </section>

                    <section
                        v-if="files.length"
                        class="border-t border-gray-100 p-6 sm:p-8 dark:border-gray-700"
                    >
                        <h2
                            class="mb-4 text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400"
                        >
                            첨부파일 ({{ files.length }})
                        </h2>
                        <ul
                            class="divide-y divide-gray-100 rounded-md border border-gray-200 dark:divide-gray-700 dark:border-gray-700"
                        >
                            <li
                                v-for="file in files"
                                :key="file.id"
                                class="flex items-center justify-between gap-4 px-4 py-3 text-sm"
                            >
                                <a
                                    :href="file.url"
                                    class="truncate text-indigo-600 hover:underline dark:text-indigo-400"
                                    download
                                >
                                    {{ file.original_name }}
                                </a>
                                <span
                                    class="shrink-0 text-gray-500 dark:text-gray-400"
                                >
                                    {{ formatFileSize(file.file_size) }}
                                </span>
                            </li>
                        </ul>
                    </section>
                </article>

                <section
                    class="space-y-6 rounded-lg bg-white p-6 shadow-sm sm:p-8 dark:bg-gray-800"
                >
                    <h2
                        class="text-lg font-semibold text-gray-900 dark:text-gray-100"
                    >
                        댓글 {{ commentCount }}
                    </h2>

                    <CommentForm
                        v-if="$page.props.auth.user"
                        :post-id="post.id"
                    />
                    <p
                        v-else
                        class="rounded-md bg-gray-50 p-4 text-sm text-gray-600 dark:bg-gray-900/50 dark:text-gray-400"
                    >
                        댓글을 작성하려면
                        <Link
                            :href="route('login')"
                            class="font-medium text-indigo-600 hover:underline dark:text-indigo-400"
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
                    <p v-else class="text-sm text-gray-500 dark:text-gray-400">
                        아직 댓글이 없습니다.
                    </p>
                </section>
            </div>
        </div>
    </BlogLayout>
</template>
