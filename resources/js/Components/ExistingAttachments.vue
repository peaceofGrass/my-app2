<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';

// The ids of the attachments marked for removal.
const model = defineModel({
    type: Array,
    required: true,
});

defineProps({
    attachments: {
        type: Array,
        required: true,
    },
    errors: {
        type: Array,
        default: () => [],
    },
});

const isImage = (attachment) => attachment.mime_type.startsWith('image/');

const toggle = (id) => {
    model.value = model.value.includes(id)
        ? model.value.filter((removeId) => removeId !== id)
        : [...model.value, id];
};
</script>

<template>
    <div>
        <InputLabel value="기존 첨부파일" />
        <p class="mt-1 text-xs text-stone-500 dark:text-stone-400">
            클릭하면 삭제 예정으로 표시되고, 저장할 때 삭제됩니다.
        </p>
        <ul class="mt-3 grid grid-cols-3 gap-3 sm:grid-cols-5">
            <li v-for="attachment in attachments" :key="attachment.id">
                <button
                    type="button"
                    class="relative block aspect-square w-full overflow-hidden rounded-md bg-stone-100 focus:outline-none focus:ring-2 focus:ring-orange-500 dark:bg-stone-700"
                    :aria-pressed="model.includes(attachment.id)"
                    @click="toggle(attachment.id)"
                >
                    <img
                        v-if="isImage(attachment)"
                        :src="attachment.url"
                        :alt="attachment.original_name"
                        class="h-full w-full object-cover"
                    />
                    <span
                        v-else
                        class="flex h-full items-center justify-center break-all p-2 text-xs text-stone-500 dark:text-stone-400"
                    >
                        {{ attachment.original_name }}
                    </span>
                    <span
                        v-if="model.includes(attachment.id)"
                        class="absolute inset-0 flex items-center justify-center bg-red-600/70 text-sm font-semibold text-white"
                    >
                        삭제 예정
                    </span>
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
