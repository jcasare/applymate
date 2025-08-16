<script setup>
defineProps({
    width: {
        type: String,
        default: 'auto',
    },
    height: {
        type: String,
        default: 'auto',
    },
});
</script>

<template>
    <img 
        src="/images/applymate_logo.png" 
        alt="ApplyMate Logo" 
        :style="`width: ${width}; height: ${height};`"
        class="object-contain"
    />
</template>
