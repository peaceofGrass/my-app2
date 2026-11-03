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
                    ? 'border-orange-500 bg-orange-50 text-orange-700 dark:border-orange-400 dark:bg-orange-900/40 dark:text-orange-300'
                    : 'border-stone-300 text-stone-600 hover:bg-stone-100 dark:border-stone-600 dark:text-stone-400 dark:hover:bg-stone-700'
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
                    : 'border-stone-300 text-stone-600 hover:bg-stone-100 dark:border-stone-600 dark:text-stone-400 dark:hover:bg-stone-700'
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
