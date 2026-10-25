<script setup>
import { computed, reactive } from 'vue';

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
</script>

<template>
    <ul
        v-if="images.length"
        :class="
            compact
                ? 'grid grid-cols-4 gap-2 sm:grid-cols-6'
                : 'grid grid-cols-2 gap-4 sm:grid-cols-3'
        "
    >
        <li v-for="image in images" :key="image.id">
            <a
                v-if="!brokenImages.has(image.id)"
                :href="image.url"
                target="_blank"
                rel="noopener"
                class="block aspect-square overflow-hidden rounded-md bg-gray-100 dark:bg-gray-700"
            >
                <img
                    :src="image.url"
                    :alt="image.original_name"
                    class="h-full w-full object-cover transition hover:scale-105"
                    loading="lazy"
                    @error="brokenImages.add(image.id)"
                />
            </a>
            <div
                v-else
                class="flex aspect-square items-center justify-center rounded-md bg-gray-100 p-2 text-center text-xs text-gray-400 dark:bg-gray-700 dark:text-gray-500"
            >
                이미지를 불러올 수 없습니다
            </div>
            <p
                v-if="!compact"
                class="mt-1 truncate text-xs text-gray-500 dark:text-gray-400"
            >
                {{ image.original_name }}
            </p>
        </li>
    </ul>
</template>
