<script setup>
import { computed } from 'vue';

const props = defineProps({
    type: {
        type: String,
        default: 'button',
    },
    // submit: main action of a form, cancel: dismiss a form,
    // action / danger: small buttons in a comment's action row.
    variant: {
        type: String,
        default: 'action',
    },
    icon: {
        type: String,
        default: null,
    },
    // Highlights an action whose panel is open, e.g. the reply form.
    active: {
        type: Boolean,
        default: false,
    },
    processing: {
        type: Boolean,
        default: false,
    },
});

const icons = {
    send: ['M22 2 11 13', 'M22 2 15 22l-4-9-9-4Z'],
    check: ['M20 6 9 17l-5-5'],
    close: ['M18 6 6 18M6 6l12 12'],
    reply: ['m9 17-5-5 5-5', 'M4 12h11a5 5 0 0 1 5 5v2'],
    pencil: ['M12 20h9', 'M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z'],
    trash: [
        'M3 6h18',
        'M8 6V4a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2',
        'm19 6-1 14a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1L5 6',
    ],
};

const variantClass = computed(() => {
    switch (props.variant) {
        case 'submit':
            return 'gap-1.5 bg-gradient-to-r from-orange-500 to-amber-500 px-4 py-1.5 text-sm font-semibold text-white shadow-sm shadow-orange-500/30 hover:from-orange-600 hover:to-amber-500 hover:shadow-md hover:shadow-orange-500/40 focus-visible:ring-orange-500 motion-safe:hover:-translate-y-0.5 motion-safe:active:translate-y-0 dark:shadow-orange-900/40';
        case 'cancel':
            return 'gap-1.5 px-4 py-1.5 text-sm font-medium text-stone-600 hover:bg-stone-200/70 hover:text-stone-900 focus-visible:ring-stone-400 dark:text-stone-400 dark:hover:bg-stone-700 dark:hover:text-stone-100';
        case 'danger':
            return 'gap-1 px-2.5 py-1 text-xs font-medium text-red-600 hover:bg-red-50 hover:text-red-700 focus-visible:ring-red-500 dark:text-red-400 dark:hover:bg-red-900/30 dark:hover:text-red-300';
        default:
            return [
                'gap-1 px-2.5 py-1 text-xs font-medium focus-visible:ring-orange-500',
                props.active
                    ? 'bg-orange-100 text-orange-700 dark:bg-orange-900/40 dark:text-orange-300'
                    : 'text-stone-600 hover:bg-orange-50 hover:text-orange-700 dark:text-stone-400 dark:hover:bg-orange-900/30 dark:hover:text-orange-300',
            ];
    }
});
</script>

<template>
    <button
        :type="type"
        :disabled="processing"
        class="group inline-flex items-center rounded-full transition duration-150 ease-in-out focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 active:scale-[0.98] disabled:cursor-wait disabled:opacity-70 dark:focus-visible:ring-offset-stone-800"
        :class="variantClass"
    >
        <svg
            v-if="processing"
            class="h-3.5 w-3.5 animate-spin"
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
            v-else-if="icon"
            class="h-3.5 w-3.5 transition-transform duration-200 motion-safe:group-hover:scale-110"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            stroke-linecap="round"
            stroke-linejoin="round"
            aria-hidden="true"
        >
            <path v-for="path in icons[icon]" :key="path" :d="path" />
        </svg>
        <slot />
    </button>
</template>
