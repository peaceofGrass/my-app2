<template>
    <svg viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg">
        <!-- Soft back foliage that peeks out behind the crown for depth. -->
        <g opacity=".35">
            <circle cx="23" cy="15" r="10" />
            <circle cx="42" cy="15" r="10" />
            <circle cx="13" cy="30" r="8" />
            <circle cx="51" cy="30" r="8" />
        </g>

        <!-- Trunk with a gentle curve and a small rounded branch. -->
        <path d="M28.5 60c1.2-6 1.6-11 1.2-17.5h4.6c-.4 6.5 0 11.5 1.2 17.5z" />
        <path
            d="M33.6 49c2.4-1.2 4.4-3 5.6-5.4"
            fill="none"
            stroke="currentColor"
            stroke-width="2.4"
            stroke-linecap="round"
        />

        <!-- Rounded, cloud-like crown. -->
        <g>
            <circle cx="32" cy="21" r="13" />
            <circle cx="20" cy="28" r="10" />
            <circle cx="44" cy="28" r="10" />
            <circle cx="26" cy="35" r="8.5" />
            <circle cx="38" cy="35" r="8.5" />
        </g>

        <!-- Ground. -->
        <rect x="16" y="59" width="32" height="3" rx="1.5" opacity=".5" />
    </svg>
</template>
