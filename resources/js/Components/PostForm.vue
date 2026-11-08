<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import ExistingAttachments from '@/Components/ExistingAttachments.vue';
import ImageInput from '@/Components/ImageInput.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import RichTextEditor from '@/Components/RichTextEditor.vue';
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

// Validation errors arrive per file as `attachments.0`, `attachments.1`, ...
const errorsFor = (field) =>
    Object.entries(form.errors)
        .filter(([key]) => key === field || key.startsWith(`${field}.`))
        .map(([, message]) => message);

const attachmentErrors = computed(() => errorsFor('attachments'));
const removalErrors = computed(() => errorsFor('remove_attachments'));

const keptCount = computed(
    () =>
        (props.post?.attachments.length ?? 0) - form.remove_attachments.length,
);

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
            <InputLabel value="본문" />
            <RichTextEditor v-model="form.content" class="mt-1" />
            <InputError class="mt-2" :message="form.errors.content" />
        </div>

        <div>
            <label class="flex items-center gap-2">
                <Checkbox v-model:checked="form.is_published" />
                <span class="text-sm text-stone-700 dark:text-stone-300">
                    공개 (체크하지 않으면 나만 볼 수 있는 비공개 글로
                    저장됩니다)
                </span>
            </label>
            <InputError class="mt-2" :message="form.errors.is_published" />
        </div>

        <ExistingAttachments
            v-if="post?.attachments.length"
            v-model="form.remove_attachments"
            :attachments="post.attachments"
            :errors="removalErrors"
        />

        <div>
            <InputLabel value="이미지 첨부" />
            <ImageInput
                v-model="form.attachments"
                class="mt-2"
                :existing="keptCount"
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

        <div class="soft-divider-t flex items-center justify-end gap-3 pt-6">
            <Link
                :href="
                    post ? route('posts.show', post.id) : route('posts.index')
                "
                class="inline-flex items-center gap-1.5 rounded-full px-5 py-2.5 text-sm font-medium text-stone-600 transition duration-150 ease-in-out hover:bg-stone-100 hover:text-stone-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-stone-400 focus-visible:ring-offset-2 dark:text-stone-400 dark:hover:bg-stone-700 dark:hover:text-stone-100 dark:focus-visible:ring-offset-stone-800"
            >
                <svg
                    class="h-4 w-4"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    aria-hidden="true"
                >
                    <path d="M18 6 6 18M6 6l12 12" />
                </svg>
                취소
            </Link>
            <button
                type="submit"
                class="group inline-flex items-center gap-2 rounded-full bg-gradient-to-r from-orange-500 to-amber-500 px-6 py-2.5 text-sm font-semibold text-white shadow-md shadow-orange-500/30 transition duration-150 ease-in-out hover:from-orange-600 hover:to-amber-500 hover:shadow-lg hover:shadow-orange-500/40 focus:outline-none focus-visible:ring-2 focus-visible:ring-orange-500 focus-visible:ring-offset-2 active:scale-[0.98] disabled:cursor-wait disabled:opacity-70 motion-safe:hover:-translate-y-0.5 motion-safe:active:translate-y-0 dark:shadow-orange-900/40 dark:focus-visible:ring-offset-stone-800"
                :disabled="form.processing"
            >
                <svg
                    v-if="form.processing"
                    class="h-4 w-4 animate-spin"
                    viewBox="0 0 24 24"
                    fill="none"
                    aria-hidden="true"
                >
                    <circle
                        cx="12"
                        cy="12"
                        r="9"
                        stroke="currentColor"
                        stroke-width="3"
                        class="opacity-25"
                    />
                    <path
                        d="M21 12a9 9 0 0 0-9-9"
                        stroke="currentColor"
                        stroke-width="3"
                        stroke-linecap="round"
                    />
                </svg>
                <svg
                    v-else
                    class="h-4 w-4 transition-transform duration-200 motion-safe:group-hover:translate-x-0.5"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2.5"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    aria-hidden="true"
                >
                    <path d="M20 6 9 17l-5-5" />
                </svg>
                {{
                    form.processing
                        ? '저장 중...'
                        : post
                          ? '수정하기'
                          : '작성하기'
                }}
            </button>
        </div>
    </form>
</template>
