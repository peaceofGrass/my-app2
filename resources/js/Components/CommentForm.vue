<script setup>
import ImageInput from '@/Components/ImageInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
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
            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:focus:border-indigo-600 dark:focus:ring-indigo-600"
            required
        />
        <InputError :message="form.errors.content" />
        <InputError :message="form.errors.parent_id" />

        <ImageInput v-model="form.attachments" :errors="attachmentErrors" />

        <div class="flex items-center gap-3">
            <PrimaryButton :disabled="form.processing">
                {{ parentId ? '답글 등록' : '댓글 등록' }}
            </PrimaryButton>
            <SecondaryButton v-if="cancelable" @click="emit('cancel')">
                취소
            </SecondaryButton>
        </div>
    </form>
</template>
