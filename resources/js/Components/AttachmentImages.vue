<script setup>
import Modal from '@/Components/Modal.vue';
import { computed, reactive, ref } from 'vue';

const props = defineProps({
    attachments: {
        type: Array,
        required: true,
    },
    compact: {
        type: Boolean,
        default: false,
    },
});

const images = computed(() =>
    props.attachments.filter((attachment) =>
        attachment.mime_type.startsWith('image/'),
    ),
);

// Attachment ids whose image failed to load, rendered as a placeholder instead.
const brokenImages = reactive(new Set());

// Lightbox state. The index is kept after closing so the image stays
// visible while the modal fades out.
const showViewer = ref(false);
const currentIndex = ref(0);
const current = computed(() => images.value[currentIndex.value]);

const open = (index) => {
    currentIndex.value = index;
    showViewer.value = true;
};

const step = (offset) => {
    const count = images.value.length;
    currentIndex.value = (currentIndex.value + offset + count) % count;
};

const onKeydown = (e) => {
    if (e.key === 'ArrowLeft') step(-1);
    if (e.key === 'ArrowRight') step(1);
};
</script>

<template>
    <div v-if="images.length">
        <ul
            :class="
                compact
                    ? 'grid grid-cols-4 gap-2 sm:grid-cols-6'
                    : 'grid grid-cols-2 gap-4 sm:grid-cols-3'
            "
        >
            <li v-for="(image, index) in images" :key="image.id">
                <button
                    v-if="!brokenImages.has(image.id)"
                    type="button"
                    class="block aspect-square w-full cursor-zoom-in overflow-hidden rounded-md bg-stone-100 focus:outline-none focus:ring-2 focus:ring-sky-500 dark:bg-stone-700"
                    @click="open(index)"
                >
                    <img
                        :src="image.url"
                        :alt="image.original_name"
                        class="h-full w-full object-cover transition hover:scale-105"
                        loading="lazy"
                        @error="brokenImages.add(image.id)"
                    />
                </button>
                <div
                    v-else
                    class="flex aspect-square items-center justify-center rounded-md bg-stone-100 p-2 text-center text-xs text-stone-400 dark:bg-stone-700 dark:text-stone-500"
                >
                    이미지를 불러올 수 없습니다
                </div>
                <p
                    v-if="!compact"
                    class="mt-1 truncate text-xs text-stone-500 dark:text-stone-400"
                >
                    {{ image.original_name }}
                </p>
            </li>
        </ul>

        <Modal :show="showViewer" max-width="5xl" @close="showViewer = false">
            <div
                v-if="current"
                class="relative bg-stone-950"
                @keydown="onKeydown"
            >
                <img
                    :src="current.url"
                    :alt="current.original_name"
                    class="mx-auto max-h-[80vh] w-auto object-contain"
                />

                <button
                    type="button"
                    class="absolute right-3 top-3 flex h-9 w-9 items-center justify-center rounded-full bg-black/50 text-xl text-white transition hover:bg-black/70"
                    aria-label="닫기"
                    @click="showViewer = false"
                >
                    &times;
                </button>

                <template v-if="images.length > 1">
                    <button
                        type="button"
                        class="absolute left-3 top-1/2 flex h-10 w-10 -translate-y-1/2 items-center justify-center rounded-full bg-black/50 text-2xl text-white transition hover:bg-black/70"
                        aria-label="이전 이미지"
                        @click="step(-1)"
                    >
                        &lsaquo;
                    </button>
                    <button
                        type="button"
                        class="absolute right-3 top-1/2 flex h-10 w-10 -translate-y-1/2 items-center justify-center rounded-full bg-black/50 text-2xl text-white transition hover:bg-black/70"
                        aria-label="다음 이미지"
                        @click="step(1)"
                    >
                        &rsaquo;
                    </button>
                </template>
            </div>
            <div
                v-if="current"
                class="flex items-center justify-between gap-4 px-4 py-3 text-sm text-stone-600 dark:text-stone-300"
            >
                <span class="truncate">{{ current.original_name }}</span>
                <span v-if="images.length > 1" class="shrink-0 text-stone-400">
                    {{ currentIndex + 1 }} / {{ images.length }}
                </span>
            </div>
        </Modal>
    </div>
</template>
