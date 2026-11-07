<script setup>
import Image from '@tiptap/extension-image';
import Placeholder from '@tiptap/extension-placeholder';
import StarterKit from '@tiptap/starter-kit';
import { EditorContent, useEditor } from '@tiptap/vue-3';
import { computed, ref, watch } from 'vue';

const model = defineModel({
    type: String,
    required: true,
});

const props = defineProps({
    placeholder: {
        type: String,
        default: '본문을 입력하세요.',
    },
    label: {
        type: String,
        default: '본문',
    },
});

const ACCEPTED_TYPES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
const MAX_SIZE = 5 * 1024 * 1024;

// Keeps the uploaded image's row id in the HTML (data-image-id), so the server can tie
// the embedded image to its post_images row when the post is saved.
const EditorImage = Image.extend({
    addAttributes() {
        return {
            ...this.parent?.(),
            imageId: {
                default: null,
                parseHTML: (element) => element.getAttribute('data-image-id'),
                renderHTML: (attributes) =>
                    attributes.imageId
                        ? { 'data-image-id': attributes.imageId }
                        : {},
            },
        };
    },
});

const fileInput = ref(null);
const uploading = ref(0);
const uploadError = ref('');

const upload = async (file) => {
    if (!ACCEPTED_TYPES.includes(file.type)) {
        uploadError.value = `${file.name}: JPG, PNG, GIF, WEBP 이미지만 올릴 수 있습니다.`;
        return;
    }

    if (file.size > MAX_SIZE) {
        uploadError.value = `${file.name}: 5MB 이하의 이미지만 올릴 수 있습니다.`;
        return;
    }

    const data = new FormData();
    data.append('image', file);
    uploading.value++;

    try {
        const response = await window.axios.post(
            route('post-images.store'),
            data,
            { headers: { Accept: 'application/json' } },
        );

        if (!response.data?.url) {
            throw new Error('Unexpected upload response');
        }

        editor.value
            ?.chain()
            .focus()
            .setImage({
                src: response.data.url,
                alt: file.name,
                imageId: response.data.id,
            })
            .run();
    } catch (error) {
        const status = error.response?.status;

        uploadError.value =
            error.response?.data?.errors?.image?.[0] ??
            {
                401: '로그인이 만료되었습니다. 다시 로그인한 뒤 시도하세요.',
                419: '페이지가 만료되었습니다. 새로고침한 뒤 다시 시도하세요.',
                429: '업로드가 너무 잦습니다. 잠시 후 다시 시도하세요.',
            }[status] ??
            `${file.name}: 이미지를 업로드하지 못했습니다.`;
    } finally {
        uploading.value--;
    }
};

const uploadAll = (files) => {
    uploadError.value = '';
    Array.from(files).forEach(upload);
};

// Pasted or dropped image files are uploaded instead of being inlined as base64.
const takeImages = (files) => {
    const images = Array.from(files ?? []).filter((file) =>
        file.type.startsWith('image/'),
    );

    if (!images.length) {
        return false;
    }

    uploadAll(images);

    return true;
};

const editor = useEditor({
    content: model.value,
    extensions: [
        StarterKit.configure({
            heading: { levels: [2, 3] },
            link: {
                openOnClick: false,
                autolink: true,
                defaultProtocol: 'https',
                protocols: ['http', 'https', 'mailto'],
            },
        }),
        EditorImage,
        Placeholder.configure({ placeholder: props.placeholder }),
    ],
    editorProps: {
        attributes: {
            'aria-label': props.label,
            class: 'prose prose-stone min-h-[20rem] max-w-none px-4 py-3 focus:outline-none dark:prose-invert prose-a:text-orange-600 prose-img:rounded-md dark:prose-a:text-orange-400',
        },
        handlePaste: (view, event) => takeImages(event.clipboardData?.files),
        handleDrop: (view, event, slice, moved) =>
            !moved && takeImages(event.dataTransfer?.files),
    },
    // An empty editor still produces "<p></p>"; send an empty string so `required` catches it.
    onUpdate: ({ editor }) => {
        model.value = editor.isEmpty ? '' : editor.getHTML();
    },
});

// Follow outside changes to the value (e.g. a form reset) without echoing our own updates.
watch(model, (value) => {
    const current = editor.value?.isEmpty ? '' : editor.value?.getHTML();

    if (editor.value && value !== current) {
        editor.value.commands.setContent(value, { emitUpdate: false });
    }
});

const setLink = () => {
    const previous = editor.value.getAttributes('link').href ?? '';
    const url = window.prompt('링크 주소를 입력하세요.', previous);

    if (url === null) {
        return;
    }

    const chain = editor.value.chain().focus().extendMarkRange('link');

    if (url.trim() === '') {
        chain.unsetLink().run();
    } else {
        chain.setLink({ href: url.trim() }).run();
    }
};

const pickImages = (event) => {
    uploadAll(event.target.files);
    event.target.value = '';
};

const tools = computed(() => {
    const e = editor.value;

    return [
        [
            {
                label: '굵게',
                text: 'B',
                class: 'font-bold',
                run: () => e.chain().focus().toggleBold().run(),
                active: () => e.isActive('bold'),
            },
            {
                label: '기울임',
                text: 'I',
                class: 'italic',
                run: () => e.chain().focus().toggleItalic().run(),
                active: () => e.isActive('italic'),
            },
            {
                label: '밑줄',
                text: 'U',
                class: 'underline',
                run: () => e.chain().focus().toggleUnderline().run(),
                active: () => e.isActive('underline'),
            },
            {
                label: '취소선',
                text: 'S',
                class: 'line-through',
                run: () => e.chain().focus().toggleStrike().run(),
                active: () => e.isActive('strike'),
            },
        ],
        [
            {
                label: '제목',
                text: 'H2',
                run: () => e.chain().focus().toggleHeading({ level: 2 }).run(),
                active: () => e.isActive('heading', { level: 2 }),
            },
            {
                label: '소제목',
                text: 'H3',
                run: () => e.chain().focus().toggleHeading({ level: 3 }).run(),
                active: () => e.isActive('heading', { level: 3 }),
            },
        ],
        [
            {
                label: '글머리 목록',
                text: '•',
                run: () => e.chain().focus().toggleBulletList().run(),
                active: () => e.isActive('bulletList'),
            },
            {
                label: '번호 목록',
                text: '1.',
                run: () => e.chain().focus().toggleOrderedList().run(),
                active: () => e.isActive('orderedList'),
            },
            {
                label: '인용',
                text: '❝',
                run: () => e.chain().focus().toggleBlockquote().run(),
                active: () => e.isActive('blockquote'),
            },
            {
                label: '코드 블록',
                text: '</>',
                class: 'font-mono',
                run: () => e.chain().focus().toggleCodeBlock().run(),
                active: () => e.isActive('codeBlock'),
            },
            {
                label: '구분선',
                text: '―',
                run: () => e.chain().focus().setHorizontalRule().run(),
                active: () => false,
            },
        ],
        [
            {
                label: '링크',
                text: '링크',
                run: setLink,
                active: () => e.isActive('link'),
            },
            {
                label: '이미지',
                text: '이미지',
                run: () => fileInput.value.click(),
                active: () => false,
            },
        ],
        [
            {
                label: '실행 취소',
                text: '↶',
                run: () => e.chain().focus().undo().run(),
                active: () => false,
                disabled: () => !e.can().undo(),
            },
            {
                label: '다시 실행',
                text: '↷',
                run: () => e.chain().focus().redo().run(),
                active: () => false,
                disabled: () => !e.can().redo(),
            },
        ],
    ];
});
</script>

<template>
    <div>
        <div
            class="overflow-hidden rounded-md border border-stone-300 bg-white shadow-sm focus-within:border-orange-500 focus-within:ring-1 focus-within:ring-orange-500 dark:border-stone-700 dark:bg-stone-900 dark:focus-within:border-orange-600 dark:focus-within:ring-orange-600"
        >
            <div
                v-if="editor"
                role="toolbar"
                :aria-label="`${label} 서식`"
                class="flex flex-wrap items-center gap-1 border-b border-stone-200 bg-stone-50 px-2 py-1.5 dark:border-stone-700 dark:bg-stone-800"
            >
                <template v-for="(group, index) in tools" :key="index">
                    <span
                        v-if="index > 0"
                        class="mx-1 h-5 w-px bg-stone-300 dark:bg-stone-600"
                        aria-hidden="true"
                    />
                    <button
                        v-for="tool in group"
                        :key="tool.label"
                        type="button"
                        :title="tool.label"
                        :aria-label="tool.label"
                        :aria-pressed="tool.active()"
                        :disabled="tool.disabled?.()"
                        class="min-w-8 rounded px-2 py-1 text-sm text-stone-700 transition hover:bg-stone-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-orange-500 disabled:cursor-not-allowed disabled:opacity-40 dark:text-stone-300 dark:hover:bg-stone-700"
                        :class="[
                            tool.class,
                            tool.active() &&
                                'bg-orange-100 text-orange-700 hover:bg-orange-100 dark:bg-orange-900/50 dark:text-orange-300 dark:hover:bg-orange-900/50',
                        ]"
                        @click="tool.run"
                    >
                        {{ tool.text }}
                    </button>
                </template>
                <span
                    v-if="uploading"
                    class="ms-auto text-xs text-stone-500 dark:text-stone-400"
                    role="status"
                >
                    이미지 업로드 중…
                </span>
            </div>

            <EditorContent :editor="editor" />
        </div>

        <input
            ref="fileInput"
            type="file"
            accept="image/jpeg,image/png,image/gif,image/webp"
            class="hidden"
            multiple
            @change="pickImages"
        />

        <p
            v-if="uploadError"
            class="mt-2 text-sm text-red-600 dark:text-red-400"
            role="alert"
        >
            {{ uploadError }}
        </p>
    </div>
</template>
