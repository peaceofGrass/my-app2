<script setup>
import { computed } from 'vue';

const props = defineProps({
    // Picks the variation, so a post always gets the same illustration.
    seed: {
        type: Number,
        required: true,
    },
});

const tones = [
    'from-green-100 to-emerald-200 dark:from-green-950 dark:to-emerald-900',
    'from-lime-100 to-green-200 dark:from-lime-950 dark:to-green-900',
    'from-emerald-100 to-teal-100 dark:from-emerald-950 dark:to-teal-900',
    'from-green-50 to-lime-200 dark:from-green-950 dark:to-lime-900',
];

const tone = computed(() => tones[props.seed % tones.length]);
const mirrored = computed(() => props.seed % 2 === 1);
</script>

<template>
    <div class="h-full w-full bg-gradient-to-br" :class="tone">
        <svg
            viewBox="0 0 320 180"
            preserveAspectRatio="xMidYMid slice"
            class="h-full w-full"
            :class="{ '-scale-x-100': mirrored }"
            aria-hidden="true"
        >
            <circle
                cx="248"
                cy="50"
                r="20"
                class="fill-white/60 dark:fill-green-200/15"
            />
            <path
                d="M0 120C60 90 120 95 180 115s100 20 140-10v75H0Z"
                class="fill-green-300/60 dark:fill-green-800/60"
            />
            <path
                d="M0 150c70-30 140-20 200-5s90 15 120-5v40H0Z"
                class="fill-green-400/70 dark:fill-green-700/70"
            />
            <g transform="translate(38 58)">
                <g
                    class="origin-bottom transition-transform duration-300 ease-out [transform-box:fill-box] motion-safe:group-hover:-rotate-6"
                >
                    <path
                        d="M72 78V42"
                        fill="none"
                        stroke-width="4"
                        stroke-linecap="round"
                        class="stroke-green-700 dark:stroke-green-400"
                    />
                    <path
                        d="M72 42C70 30 59 24 50 27c2 10 11 16 22 15Z"
                        class="fill-green-600 dark:fill-green-400"
                    />
                    <path
                        d="M72 42c3-13 15-19 24-15-3 10-13 16-24 15Z"
                        class="fill-green-700 dark:fill-green-300"
                    />
                </g>
            </g>
        </svg>
    </div>
</template>
