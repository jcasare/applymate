<script setup>
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import Avatar from '@/Components/Avatar.vue';
import { useForm, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

const user = usePage().props.auth.user;

const form = useForm({
    avatar: null,
});

const avatarPreview = ref(null);
const fileInput = ref(null);

const selectAvatar = () => {
    fileInput.value.click();
};

const handleAvatarChange = (event) => {
    const file = event.target.files[0];
    if (file) {
        form.avatar = file;
        
        // Create preview
        const reader = new FileReader();
        reader.onload = (e) => {
            avatarPreview.value = e.target.result;
        };
        reader.readAsDataURL(file);
    }
};

const removeAvatar = () => {
    form.avatar = null;
    avatarPreview.value = null;
    if (fileInput.value) {
        fileInput.value.value = '';
    }
};

const submit = () => {
    form.post(route('profile.avatar.update'), {
        preserveScroll: true,
        onSuccess: () => {
            removeAvatar();
        },
    });
};

const deleteAvatar = () => {
    form.delete(route('profile.avatar.destroy'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-medium text-gray-900">Profile Picture</h2>
            <p class="mt-1 text-sm text-gray-600">
                Update your profile picture. We support Gravatar, uploaded images, and social login avatars.
            </p>
        </header>

        <div class="mt-6 space-y-6">
            <!-- Current Avatar Display -->
            <div class="flex items-center space-x-6">
                <div class="shrink-0">
                    <Avatar 
                        v-if="!avatarPreview" 
                        :user="user" 
                        size="2xl" 
                    />
                    <img
                        v-else
                        :src="avatarPreview"
                        alt="Avatar preview"
                        class="w-20 h-20 rounded-full object-cover border border-gray-200 shadow-sm"
                    />
                </div>
                
                <div class="flex-1">
                    <div class="flex items-center space-x-4">
                        <SecondaryButton @click="selectAvatar" type="button">
                            Choose New Photo
                        </SecondaryButton>
                        
                        <SecondaryButton 
                            v-if="user.avatar_path || avatarPreview" 
                            @click="deleteAvatar"
                            type="button"
                            class="text-red-600 hover:text-red-500"
                        >
                            Remove
                        </SecondaryButton>
                    </div>
                    
                    <p class="mt-2 text-xs text-gray-500">
                        JPG, GIF or PNG. 2MB max. Recommended: 400x400px.
                    </p>
                </div>
            </div>

            <!-- Hidden File Input -->
            <input
                ref="fileInput"
                type="file"
                accept="image/*"
                class="hidden"
                @change="handleAvatarChange"
            />

            <!-- Gravatar Info -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-blue-800">
                            About Gravatar
                        </h3>
                        <div class="mt-2 text-sm text-blue-700">
                            <p>
                                If you don't upload a custom avatar, we'll show your 
                                <a href="https://gravatar.com" target="_blank" class="underline">Gravatar</a> 
                                associated with {{ user.email }}. Create or update your Gravatar to have it appear across all sites that support it.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Save Changes -->
            <div v-if="form.avatar" class="flex items-center gap-4">
                <PrimaryButton 
                    @click="submit" 
                    :class="{ 'opacity-25': form.processing }" 
                    :disabled="form.processing"
                >
                    Save Changes
                </PrimaryButton>

                <SecondaryButton @click="removeAvatar" type="button">
                    Cancel
                </SecondaryButton>

                <InputError class="mt-2" :message="form.errors.avatar" />
            </div>
        </div>
    </section>
</template>