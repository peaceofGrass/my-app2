<script setup>
import InputError from '@/Components/InputError.vue';
import { computed, onBeforeUnmount, ref, watch } from 'vue';

const model = defineModel({
    type: Array,
    required: true,
});

const props = defineProps({
    max: {
        type: Number,
        default: 10,
    },
    // Images already saved that count toward the limit.
    existing: {
        type: Number,
        default: 0,
    },
    errors: {
        type: Array,
        default: () => [],
    },
});

const input = ref(null);

const total = computed(() => props.existing + model.value.length);

// Object URLs for previewing the selected files; revoked once a file is dropped.
const previews = ref([]);

watch(
    model,
    (files) => {
        previews.value
            .filter((preview) => !files.includes(preview.file))
            .forEach((preview) => URL.revokeObjectURL(preview.url));

        previews.value = files.map(
            (file) =>
                previews.value.find((preview) => preview.file === file) ?? {
                    file,
                    url: URL.createObjectURL(file),
                },
        );
    },
    { immediate: true },
);

onBeforeUnmount(() =>
    previews.value.forEach((preview) => URL.revokeObjectURL(preview.url)),
);

const add = (event) => {
    model.value = [...model.value, ...event.target.files].slice(
        0,
        Math.max(props.max - props.existing, 0),
    );
    event.target.value = '';
};

const remove = (index) => {
    model.value = model.value.filter((_, i) => i !== index);
};
</script>

<template>
    <div>
        <input
            ref="input"
            type="file"
            accept="image/jpeg,image/png,image/gif,image/webp"
            multiple
            class="hidden"
            @change="add"
        />

        <div class="flex flex-wrap items-center gap-3">
            <button
                type="button"
                class="group inline-flex items-center gap-2 rounded-full border border-dashed border-orange-300 bg-orange-50/60 px-4 py-2 text-sm font-medium text-orange-700 transition duration-150 ease-in-out hover:border-solid hover:border-orange-400 hover:bg-orange-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-orange-500 focus-visible:ring-offset-2 active:scale-[0.98] disabled:cursor-not-allowed disabled:opacity-40 disabled:hover:border-dashed disabled:hover:bg-orange-50/60 motion-safe:hover:-translate-y-0.5 motion-safe:active:translate-y-0 dark:border-orange-800 dark:bg-orange-900/20 dark:text-orange-300 dark:hover:border-orange-600 dark:hover:bg-orange-900/40 dark:focus-visible:ring-offset-stone-800"
                :disabled="total >= max"
                @click="input.click()"
            >
                <svg
                    class="h-4 w-4 transition-transform duration-200 motion-safe:group-hover:rotate-6 motion-safe:group-hover:scale-110"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    aria-hidden="true"
                >
                    <rect x="3" y="3" width="18" height="18" rx="3" />
                    <circle cx="9" cy="9" r="1.5" />
                    <path d="m21 15-4.5-4.5L6 21" />
                </svg>
                이미지 선택
            </button>
            <span class="text-xs text-stone-500 dark:text-stone-400">
                {{ total }} / {{ max }} · JPG, PNG, GIF, WEBP (최대 5MB)
            </span>
            <span
                v-if="total > max"
                class="text-xs font-medium text-red-600 dark:text-red-400"
            >
                {{ total - max }}장을 줄여야 저장할 수 있습니다.
            </span>
        </div>

        <ul
            v-if="previews.length"
            class="mt-3 grid grid-cols-3 gap-3 sm:grid-cols-5"
        >
            <li
                v-for="(preview, index) in previews"
                :key="preview.url"
                class="relative"
            >
                <img
                    :src="preview.url"
                    :alt="preview.file.name"
                    class="aspect-square w-full rounded-md object-cover"
                />
                <button
                    type="button"
                    class="absolute right-1 top-1 rounded-full bg-black/60 px-2 text-sm leading-6 text-white hover:bg-black/80"
                    :aria-label="`${preview.file.name} 제거`"
                    @click="remove(index)"
                >
                    &times;
                </button>
            </li>
        </ul>

        <InputError
            v-for="error in errors"
            :key="error"
            class="mt-2"
            :message="error"
        />
    </div>
</template>
