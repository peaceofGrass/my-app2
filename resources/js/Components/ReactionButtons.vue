<script setup>
import { router, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    url: {
        type: String,
        required: true,
    },
    likes: {
        type: Number,
        default: 0,
    },
    dislikes: {
        type: Number,
        default: 0,
    },
    myReaction: {
        type: String,
        default: null,
    },
});

const page = usePage();

const processing = ref(false);

const react = (type) => {
    if (!page.props.auth.user) {
        alert('먼저 로그인을 해주세요.');
        return;
    }

    if (processing.value) {
        return;
    }

    router.post(
        props.url,
        { type },
        {
            preserveScroll: true,
            preserveState: true,
            onStart: () => (processing.value = true),
            onFinish: () => (processing.value = false),
        },
    );
};
</script>

<template>
    <div class="flex items-center gap-2 text-sm">
        <button
            type="button"
            class="inline-flex items-center gap-1.5 rounded-full border px-3 py-1 transition duration-150 ease-in-out disabled:opacity-60"
            :class="
                myReaction === 'like'
                    ? 'border-indigo-500 bg-indigo-50 text-indigo-700 dark:border-indigo-400 dark:bg-indigo-900/40 dark:text-indigo-300'
                    : 'border-gray-300 text-gray-600 hover:bg-gray-100 dark:border-gray-600 dark:text-gray-400 dark:hover:bg-gray-700'
            "
            :aria-pressed="myReaction === 'like'"
            :disabled="processing"
            aria-label="좋아요"
            title="좋아요"
            @click="react('like')"
        >
            <span aria-hidden="true">👍</span>
            <span class="tabular-nums">{{ likes }}</span>
        </button>
        <button
            type="button"
            class="inline-flex items-center gap-1.5 rounded-full border px-3 py-1 transition duration-150 ease-in-out disabled:opacity-60"
            :class="
                myReaction === 'dislike'
                    ? 'border-red-500 bg-red-50 text-red-700 dark:border-red-400 dark:bg-red-900/40 dark:text-red-300'
                    : 'border-gray-300 text-gray-600 hover:bg-gray-100 dark:border-gray-600 dark:text-gray-400 dark:hover:bg-gray-700'
            "
            :aria-pressed="myReaction === 'dislike'"
            :disabled="processing"
            aria-label="싫어요"
            title="싫어요"
            @click="react('dislike')"
        >
            <span aria-hidden="true">👎</span>
            <span class="tabular-nums">{{ dislikes }}</span>
        </button>
    </div>
</template>
