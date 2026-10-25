<script setup>
import PostForm from '@/Components/PostForm.vue';
import BlogLayout from '@/Layouts/BlogLayout.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    post: {
        type: Object,
        required: true,
    },
});
</script>

<template>
    <Head :title="`${post.title} 수정`" />

    <BlogLayout>
        <template #header>
            <h2
                class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200"
            >
                글 수정
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                <div
                    class="rounded-lg bg-white p-6 shadow-sm sm:p-8 dark:bg-gray-800"
                >
                    <PostForm :post="post" />
                </div>
            </div>
        </div>
    </BlogLayout>
</template>
