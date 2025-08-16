<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="flex min-h-screen flex-col items-center bg-gradient-to-br from-blue-50 to-indigo-100 pt-6 sm:justify-center sm:pt-0">
        <div>
            <Link href="/" class="flex items-center">
                <ApplicationLogo width="80px" height="80px" />
                <span class="ml-3 text-3xl font-bold text-gray-900">ApplyMate</span>
            </Link>
        </div>

        <div class="mt-6 w-full overflow-hidden bg-white px-6 py-8 shadow-lg sm:max-w-md sm:rounded-lg">
            <slot />
        </div>
    </div>
</template>
