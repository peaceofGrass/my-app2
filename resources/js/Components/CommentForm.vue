<script setup>
import CommentButton from '@/Components/CommentButton.vue';
import ImageInput from '@/Components/ImageInput.vue';
import InputError from '@/Components/InputError.vue';
import { useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    postId: {
        type: Number,
        required: true,
    },
    // Set to reply to an existing comment.
    parentId: {
        type: Number,
        default: null,
    },
    cancelable: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['done', 'cancel']);

const form = useForm({
    content: '',
    parent_id: props.parentId,
    attachments: [],
});

const attachmentErrors = computed(() =>
    Object.entries(form.errors)
        .filter(([key]) => key.startsWith('attachments'))
        .map(([, message]) => message),
);

const submit = () => {
    form.post(route('posts.comments.store', props.postId), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            emit('done');
        },
    });
};
</script>

<template>
    <form class="space-y-3" @submit.prevent="submit">
        <textarea
            v-model="form.content"
            rows="3"
            maxlength="5000"
            :placeholder="parentId ? '답글을 입력하세요' : '댓글을 입력하세요'"
            :aria-label="parentId ? '답글 내용' : '댓글 내용'"
            class="block w-full rounded-md border-stone-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 dark:border-stone-700 dark:bg-stone-900 dark:text-stone-300 dark:focus:border-orange-600 dark:focus:ring-orange-600"
            required
        />
        <InputError :message="form.errors.content" />
        <InputError :message="form.errors.parent_id" />

        <ImageInput v-model="form.attachments" :errors="attachmentErrors" />

        <div class="flex items-center justify-end gap-2">
            <CommentButton
                v-if="cancelable"
                variant="cancel"
                icon="close"
                @click="emit('cancel')"
            >
                취소
            </CommentButton>
            <CommentButton
                type="submit"
                variant="submit"
                icon="send"
                :processing="form.processing"
            >
                {{ parentId ? '답글 등록' : '댓글 등록' }}
            </CommentButton>
        </div>
    </form>
</template>
