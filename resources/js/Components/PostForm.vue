<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import ImageInput from '@/Components/ImageInput.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    // Omit to create a new post; pass an existing post (with attachments) to edit it.
    post: {
        type: Object,
        default: null,
    },
});

const form = useForm({
    title: props.post?.title ?? '',
    content: props.post?.content ?? '',
    is_published: props.post?.is_published ?? true,
    attachments: [],
    remove_attachments: [],
});

const isImage = (attachment) => attachment.mime_type.startsWith('image/');

const toggleRemoval = (id) => {
    form.remove_attachments = form.remove_attachments.includes(id)
        ? form.remove_attachments.filter((removeId) => removeId !== id)
        : [...form.remove_attachments, id];
};

// Validation errors arrive per file as `attachments.0`, `attachments.1`, ...
const errorsFor = (field) =>
    Object.entries(form.errors)
        .filter(([key]) => key === field || key.startsWith(`${field}.`))
        .map(([, message]) => message);

const attachmentErrors = computed(() => errorsFor('attachments'));
const removalErrors = computed(() => errorsFor('remove_attachments'));

const submit = () => {
    // PHP cannot parse multipart PATCH bodies, so file uploads use method spoofing.
    if (props.post) {
        form.transform((data) => ({ ...data, _method: 'patch' })).post(
            route('posts.update', props.post.id),
            { forceFormData: true },
        );
    } else {
        form.post(route('posts.store'), { forceFormData: true });
    }
};
</script>

<template>
    <form class="space-y-6" @submit.prevent="submit">
        <div>
            <InputLabel for="title" value="제목" />
            <TextInput
                id="title"
                v-model="form.title"
                type="text"
                class="mt-1 block w-full"
                maxlength="255"
                required
                autofocus
            />
            <InputError class="mt-2" :message="form.errors.title" />
        </div>

        <div>
            <InputLabel for="content" value="본문" />
            <textarea
                id="content"
                v-model="form.content"
                rows="14"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:focus:border-indigo-600 dark:focus:ring-indigo-600"
                required
            />
            <InputError class="mt-2" :message="form.errors.content" />
        </div>

        <div>
            <label class="flex items-center gap-2">
                <Checkbox v-model:checked="form.is_published" />
                <span class="text-sm text-gray-700 dark:text-gray-300">
                    공개 (체크하지 않으면 나만 볼 수 있는 비공개 글로
                    저장됩니다)
                </span>
            </label>
            <InputError class="mt-2" :message="form.errors.is_published" />
        </div>

        <div v-if="post?.attachments.length">
            <InputLabel value="기존 첨부파일" />
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                클릭하면 삭제 예정으로 표시되고, 저장할 때 삭제됩니다.
            </p>
            <ul class="mt-3 grid grid-cols-3 gap-3 sm:grid-cols-5">
                <li v-for="attachment in post.attachments" :key="attachment.id">
                    <button
                        type="button"
                        class="relative block aspect-square w-full overflow-hidden rounded-md bg-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:bg-gray-700"
                        :aria-pressed="
                            form.remove_attachments.includes(attachment.id)
                        "
                        @click="toggleRemoval(attachment.id)"
                    >
                        <img
                            v-if="isImage(attachment)"
                            :src="attachment.url"
                            :alt="attachment.original_name"
                            class="h-full w-full object-cover"
                        />
                        <span
                            v-else
                            class="flex h-full items-center justify-center break-all p-2 text-xs text-gray-500 dark:text-gray-400"
                        >
                            {{ attachment.original_name }}
                        </span>
                        <span
                            v-if="
                                form.remove_attachments.includes(attachment.id)
                            "
                            class="absolute inset-0 flex items-center justify-center bg-red-600/70 text-sm font-semibold text-white"
                        >
                            삭제 예정
                        </span>
                    </button>
                </li>
            </ul>
            <InputError
                v-for="error in removalErrors"
                :key="error"
                class="mt-2"
                :message="error"
            />
        </div>

        <div>
            <InputLabel value="이미지 첨부" />
            <ImageInput
                v-model="form.attachments"
                class="mt-2"
                :errors="attachmentErrors"
            />
        </div>

        <progress
            v-if="form.progress"
            :value="form.progress.percentage"
            max="100"
            class="w-full"
        >
            {{ form.progress.percentage }}%
        </progress>

        <div class="flex items-center gap-4">
            <PrimaryButton :disabled="form.processing">
                {{ post ? '수정하기' : '작성하기' }}
            </PrimaryButton>
            <Link
                :href="
                    post ? route('posts.show', post.id) : route('posts.index')
                "
                class="text-sm text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-100"
            >
                취소
            </Link>
        </div>
    </form>
</template>
