<script setup>
import { onMounted, ref } from 'vue';

const model = defineModel({
    type: String,
    required: true,
});

const input = ref(null);

onMounted(() => {
    if (input.value.hasAttribute('autofocus')) {
        input.value.focus();
    }
});

defineExpose({ focus: () => input.value.focus() });
</script>

<template>
    <input
        class="rounded-md border-stone-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 dark:border-stone-700 dark:bg-stone-900 dark:text-stone-300 dark:focus:border-orange-600 dark:focus:ring-orange-600"
        v-model="model"
        ref="input"
    />
</template>
