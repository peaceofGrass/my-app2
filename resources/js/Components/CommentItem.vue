<script setup>
import AttachmentImages from '@/Components/AttachmentImages.vue';
import CommentForm from '@/Components/CommentForm.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import ReactionButtons from '@/Components/ReactionButtons.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    comment: {
        type: Object,
        required: true,
    },
    // Author name of the comment being replied to; set only on replies.
    replyTo: {
        type: String,
        default: null,
    },
});

const page = usePage();

const isAuthor = computed(
    () => page.props.auth.user?.id === props.comment.user_id,
);

// Replies are not indented further: every reply under a top-level comment
// is listed at one level, oldest first, YouTube style.
const replies = computed(() => {
    if (props.replyTo !== null) {
        return [];
    }

    const flatten = (comment) =>
        comment.children.flatMap((child) => [
            { comment: child, replyTo: comment.user.name },
            ...flatten(child),
        ]);

    return flatten(props.comment).sort(
        (a, b) =>
            new Date(a.comment.created_at) - new Date(b.comment.created_at) ||
            a.comment.id - b.comment.id,
    );
});

const replying = ref(false);
const editing = ref(false);

const editForm = useForm({
    content: props.comment.content,
});

const startEditing = () => {
    editForm.content = props.comment.content;
    editForm.clearErrors();
    editing.value = true;
};

const update = () => {
    editForm.patch(route('comments.update', props.comment.id), {
        preserveScroll: true,
        onSuccess: () => (editing.value = false),
    });
};

const destroy = () => {
    if (confirm('이 댓글을 삭제할까요? 달린 답글도 함께 삭제됩니다.')) {
        router.delete(route('comments.destroy', props.comment.id), {
            preserveScroll: true,
        });
    }
};

const formatDate = (value) =>
    new Date(value).toLocaleString('ko-KR', {
        dateStyle: 'medium',
        timeStyle: 'short',
    });
</script>

<template>
    <li>
        <div class="rounded-md bg-stone-50 p-4 dark:bg-stone-900/50">
            <div class="flex items-center gap-2 text-sm">
                <span class="font-semibold text-stone-800 dark:text-stone-200">
                    {{ comment.user.name }}
                </span>
                <time
                    :datetime="comment.created_at"
                    class="text-stone-500 dark:text-stone-400"
                >
                    {{ formatDate(comment.created_at) }}
                </time>
            </div>

            <form
                v-if="editing"
                class="mt-2 space-y-2"
                @submit.prevent="update"
            >
                <textarea
                    v-model="editForm.content"
                    rows="3"
                    maxlength="5000"
                    aria-label="댓글 수정"
                    class="block w-full rounded-md border-stone-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 dark:border-stone-700 dark:bg-stone-900 dark:text-stone-300 dark:focus:border-orange-600 dark:focus:ring-orange-600"
                    required
                />
                <InputError :message="editForm.errors.content" />
                <div class="flex gap-2">
                    <PrimaryButton :disabled="editForm.processing">
                        저장
                    </PrimaryButton>
                    <SecondaryButton @click="editing = false">
                        취소
                    </SecondaryButton>
                </div>
            </form>
            <p
                v-else
                class="mt-2 whitespace-pre-line text-stone-700 dark:text-stone-300"
            >
                <span
                    v-if="replyTo !== null"
                    class="font-medium text-orange-600 dark:text-orange-400"
                    >@{{ replyTo }}:</span
                >
                {{ comment.content }}
            </p>

            <AttachmentImages
                class="mt-3"
                :attachments="comment.attachments"
                compact
            />

            <div
                v-if="!editing"
                class="mt-3 flex flex-wrap items-center gap-4 text-xs font-medium"
            >
                <ReactionButtons
                    :url="route('comments.reaction', comment.id)"
                    :likes="comment.likes_count"
                    :dislikes="comment.dislikes_count"
                    :my-reaction="comment.my_reaction"
                />
                <button
                    v-if="$page.props.auth.user"
                    type="button"
                    class="text-stone-600 hover:text-stone-900 dark:text-stone-400 dark:hover:text-stone-100"
                    :aria-expanded="replying"
                    @click="replying = !replying"
                >
                    {{ replying ? '답글 취소' : '답글 작성' }}
                </button>
                <template v-if="isAuthor">
                    <button
                        type="button"
                        class="text-stone-600 hover:text-stone-900 dark:text-stone-400 dark:hover:text-stone-100"
                        @click="startEditing"
                    >
                        수정
                    </button>
                    <button
                        type="button"
                        class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300"
                        @click="destroy"
                    >
                        삭제
                    </button>
                </template>
            </div>
        </div>

        <div v-if="replying" :class="['mt-3', { 'ms-4 ps-4': !replyTo }]">
            <CommentForm
                :post-id="comment.post_id"
                :parent-id="comment.id"
                cancelable
                @done="replying = false"
                @cancel="replying = false"
            />
        </div>

        <ul
            v-if="replies.length"
            class="ms-4 mt-3 space-y-3 border-s-2 border-orange-300 ps-4 dark:border-orange-800"
        >
            <CommentItem
                v-for="reply in replies"
                :key="reply.comment.id"
                :comment="reply.comment"
                :reply-to="reply.replyTo"
            />
        </ul>
    </li>
</template>
