<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    user: Object,
});

const form = useForm({
    job_title: '',
    company_name: '',
    job_description: '',
    candidate_name: props.user?.name || '',
    current_role: '',
    years_experience: 0,
    skills_list: '',
    career_highlights: '',
    education_details: '',
});

const isLoading = ref(false);

const submit = () => {
    isLoading.value = true;
    form.post(route('applications.store'), {
        onFinish: () => {
            isLoading.value = false;
        },
    });
};
</script>

<template>
    <Head title="Generate Application" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Generate New Application</h2>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <form @submit.prevent="submit" class="space-y-6">
                            <!-- Job Information Section -->
                            <div class="border-b pb-6">
                                <h3 class="text-lg font-semibold mb-4 text-gray-900">Job Information</h3>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <InputLabel for="job_title" value="Job Title" />
                                        <TextInput
                                            id="job_title"
                                            type="text"
                                            class="mt-1 block w-full"
                                            v-model="form.job_title"
                                            required
                                            placeholder="e.g., Senior Software Engineer"
                                        />
                                        <InputError class="mt-2" :message="form.errors.job_title" />
                                    </div>

                                    <div>
                                        <InputLabel for="company_name" value="Company Name" />
                                        <TextInput
                                            id="company_name"
                                            type="text"
                                            class="mt-1 block w-full"
                                            v-model="form.company_name"
                                            required
                                            placeholder="e.g., Tech Company Inc."
                                        />
                                        <InputError class="mt-2" :message="form.errors.company_name" />
                                    </div>
                                </div>

                                <div class="mt-4">
                                    <InputLabel for="job_description" value="Job Description" />
                                    <textarea
                                        id="job_description"
                                        class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                        v-model="form.job_description"
                                        required
                                        rows="6"
                                        placeholder="Paste the full job description here..."
                                    ></textarea>
                                    <InputError class="mt-2" :message="form.errors.job_description" />
                                </div>
                            </div>

                            <!-- Candidate Profile Section -->
                            <div class="border-b pb-6">
                                <h3 class="text-lg font-semibold mb-4 text-gray-900">Your Profile</h3>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <InputLabel for="candidate_name" value="Full Name" />
                                        <TextInput
                                            id="candidate_name"
                                            type="text"
                                            class="mt-1 block w-full"
                                            v-model="form.candidate_name"
                                            required
                                        />
                                        <InputError class="mt-2" :message="form.errors.candidate_name" />
                                    </div>

                                    <div>
                                        <InputLabel for="current_role" value="Current Role" />
                                        <TextInput
                                            id="current_role"
                                            type="text"
                                            class="mt-1 block w-full"
                                            v-model="form.current_role"
                                            placeholder="e.g., Software Developer"
                                        />
                                        <InputError class="mt-2" :message="form.errors.current_role" />
                                    </div>

                                    <div>
                                        <InputLabel for="years_experience" value="Years of Experience" />
                                        <TextInput
                                            id="years_experience"
                                            type="number"
                                            class="mt-1 block w-full"
                                            v-model="form.years_experience"
                                            required
                                            min="0"
                                            max="50"
                                        />
                                        <InputError class="mt-2" :message="form.errors.years_experience" />
                                    </div>
                                </div>

                                <div class="mt-4">
                                    <InputLabel for="skills_list" value="Key Skills (comma-separated)" />
                                    <textarea
                                        id="skills_list"
                                        class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                        v-model="form.skills_list"
                                        required
                                        rows="2"
                                        placeholder="e.g., JavaScript, React, Node.js, AWS, Docker, PostgreSQL"
                                    ></textarea>
                                    <InputError class="mt-2" :message="form.errors.skills_list" />
                                </div>

                                <div class="mt-4">
                                    <InputLabel for="career_highlights" value="Career Highlights & Achievements" />
                                    <textarea
                                        id="career_highlights"
                                        class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                        v-model="form.career_highlights"
                                        required
                                        rows="4"
                                        placeholder="List your key achievements, projects, and quantifiable results..."
                                    ></textarea>
                                    <InputError class="mt-2" :message="form.errors.career_highlights" />
                                </div>

                                <div class="mt-4">
                                    <InputLabel for="education_details" value="Education" />
                                    <textarea
                                        id="education_details"
                                        class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                        v-model="form.education_details"
                                        required
                                        rows="2"
                                        placeholder="e.g., Bachelor's in Computer Science - University Name (Year)"
                                    ></textarea>
                                    <InputError class="mt-2" :message="form.errors.education_details" />
                                </div>
                            </div>

                            <div class="flex items-center justify-end">
                                <PrimaryButton :class="{ 'opacity-25': form.processing || isLoading }" :disabled="form.processing || isLoading">
                                    <span v-if="isLoading" class="flex items-center">
                                        <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        Generating with AI...
                                    </span>
                                    <span v-else>Generate Application Materials</span>
                                </PrimaryButton>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>