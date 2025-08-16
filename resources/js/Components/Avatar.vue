<script setup>
import { computed } from 'vue';

const props = defineProps({
    user: {
        type: Object,
        required: true,
    },
    size: {
        type: String,
        default: 'md', // xs, sm, md, lg, xl
    },
    showInitials: {
        type: Boolean,
        default: true,
    },
});

const sizeClasses = {
    xs: 'w-6 h-6 text-xs',
    sm: 'w-8 h-8 text-sm',
    md: 'w-10 h-10 text-base',
    lg: 'w-12 h-12 text-lg',
    xl: 'w-16 h-16 text-xl',
    '2xl': 'w-20 h-20 text-2xl',
};

const avatarUrl = computed(() => {
    // Priority: uploaded avatar -> social avatar -> Gravatar
    if (props.user.avatar_path) {
        return `/storage/${props.user.avatar_path}`;
    }
    
    if (props.user.avatar) {
        return props.user.avatar;
    }
    
    // Gravatar fallback
    const email = props.user.email.toLowerCase().trim();
    const hash = btoa(email); // Simple hash for demo - in production use MD5
    return `https://www.gravatar.com/avatar/${hash}?d=404&s=200`;
});

const initials = computed(() => {
    if (!props.showInitials) return '';
    const nameParts = props.user.name.split(' ');
    return nameParts.map(part => part.charAt(0).toUpperCase()).join('').slice(0, 2);
});

const showFallback = computed(() => {
    return !props.user.avatar_path && !props.user.avatar;
});
</script>

<template>
    <div class="relative inline-block">
        <div 
            v-if="showFallback" 
            :class="[
                sizeClasses[size],
                'rounded-full bg-gradient-to-r from-blue-500 to-indigo-600 flex items-center justify-center text-white font-semibold'
            ]"
        >
            {{ initials }}
        </div>
        
        <img
            v-else
            :src="avatarUrl"
            :alt="`${user.name}'s avatar`"
            :class="[
                sizeClasses[size],
                'rounded-full object-cover border border-gray-200 shadow-sm'
            ]"
            @error="showFallback = true"
        />
        
        <!-- Online indicator (optional) -->
        <div 
            v-if="$slots.indicator" 
            class="absolute bottom-0 right-0 block h-2.5 w-2.5 rounded-full bg-green-400 ring-2 ring-white"
        >
            <slot name="indicator" />
        </div>
    </div>
</template>