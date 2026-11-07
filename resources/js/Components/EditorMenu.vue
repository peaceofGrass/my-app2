<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue';

defineProps({
    label: {
        type: String,
        required: true,
    },
});

const open = ref(false);
const root = ref(null);
const trigger = ref(null);

const close = (refocus = false) => {
    open.value = false;

    if (refocus) {
        trigger.value?.focus();
    }
};

const closeOnOutsideClick = (event) => {
    if (open.value && !root.value?.contains(event.target)) {
        close();
    }
};

onMounted(() => document.addEventListener('pointerdown', closeOnOutsideClick));
onBeforeUnmount(() =>
    document.removeEventListener('pointerdown', closeOnOutsideClick),
);
</script>

<template>
    <div ref="root" class="relative" @keydown.esc.stop="close(true)">
        <button
            ref="trigger"
            type="button"
            :title="label"
            :aria-label="label"
            aria-haspopup="true"
            :aria-expanded="open"
            class="inline-flex items-center gap-1 rounded px-2 py-1 text-sm text-stone-700 transition hover:bg-stone-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-orange-500 dark:text-stone-300 dark:hover:bg-stone-700"
            :class="{ 'bg-stone-200 dark:bg-stone-700': open }"
            @click="open = !open"
        >
            <slot name="trigger" />
            <svg
                viewBox="0 0 20 20"
                fill="currentColor"
                class="h-3.5 w-3.5 text-stone-400"
                aria-hidden="true"
            >
                <path
                    fill-rule="evenodd"
                    d="M5.22 8.22a.75.75 0 0 1 1.06 0L10 11.94l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 9.28a.75.75 0 0 1 0-1.06Z"
                    clip-rule="evenodd"
                />
            </svg>
        </button>

        <div
            v-if="open"
            :aria-label="label"
            role="group"
            class="absolute left-0 top-full z-20 mt-1 min-w-max rounded-md border border-stone-200 bg-white p-1 shadow-lg dark:border-stone-700 dark:bg-stone-800"
        >
            <slot :close="close" />
        </div>
    </div>
</template>
