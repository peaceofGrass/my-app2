<script setup>
import InputError from '@/Components/InputError.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { onBeforeUnmount, ref, watch } from 'vue';

const model = defineModel({
    type: Array,
    required: true,
});

const props = defineProps({
    max: {
        type: Number,
        default: 10,
    },
    errors: {
        type: Array,
        default: () => [],
    },
});

const input = ref(null);

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
    model.value = [...model.value, ...event.target.files].slice(0, props.max);
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
            <SecondaryButton
                :disabled="model.length >= max"
                @click="input.click()"
            >
                이미지 선택
            </SecondaryButton>
            <span class="text-xs text-gray-500 dark:text-gray-400">
                {{ model.length }} / {{ max }} · JPG, PNG, GIF, WEBP (최대 5MB)
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
