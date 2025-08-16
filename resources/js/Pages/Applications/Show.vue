<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    application: Object,
});

const activeTab = ref('summary');
const copySuccess = ref('');

const copyToClipboard = async (text, type) => {
    try {
        await navigator.clipboard.writeText(text);
        copySuccess.value = type;
        setTimeout(() => {
            copySuccess.value = '';
        }, 2000);
    } catch (err) {
        console.error('Failed to copy: ', err);
    }
};

const markAsApplied = () => {
    router.post(route('applications.mark-applied', props.application.id));
};

const deleteApplication = () => {
    if (confirm('Are you sure you want to delete this application?')) {
        router.delete(route('applications.destroy', props.application.id));
    }
};

const exportPdf = () => {
    window.open(route('applications.export', [props.application.id, 'pdf']), '_blank');
};

const shareLinkedIn = () => {
    const text = encodeURIComponent(props.application.linkedin_post);
    window.open(`https://www.linkedin.com/sharing/share-offsite/?url=${window.location.href}&summary=${text}`, '_blank');
};
</script>

<template>
    <Head :title="`Application - ${application.job_title}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                        {{ application.job_title }} at {{ application.company_name }}
                    </h2>
                    <p class="text-sm text-gray-500 mt-1">
                        Created on {{ new Date(application.created_at).toLocaleDateString() }}
                    </p>
                </div>
                <div class="flex gap-2">
                    <Link :href="route('applications.index')" class="text-gray-600 hover:text-gray-900">
                        ← Back to Applications
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Action Buttons -->
                <div class="mb-6 bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 flex flex-wrap gap-3">
                        <PrimaryButton @click="markAsApplied" v-if="application.status !== 'applied'">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            Mark as Applied
                        </PrimaryButton>
                        
                        <SecondaryButton @click="exportPdf">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            Export as PDF
                        </SecondaryButton>
                        
                        <SecondaryButton @click="shareLinkedIn">
                            <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.79-1.75-1.764s.784-1.764 1.75-1.764 1.75.79 1.75 1.764-.783 1.764-1.75 1.764zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z"/>
                            </svg>
                            Share on LinkedIn
                        </SecondaryButton>
                        
                        <DangerButton @click="deleteApplication">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                            Delete
                        </DangerButton>
                    </div>
                </div>

                <!-- Content Tabs -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="border-b border-gray-200">
                        <nav class="-mb-px flex space-x-8 px-6" aria-label="Tabs">
                            <button
                                @click="activeTab = 'summary'"
                                :class="[
                                    activeTab === 'summary'
                                        ? 'border-indigo-500 text-indigo-600'
                                        : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                                    'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm'
                                ]"
                            >
                                Resume Summary
                            </button>
                            <button
                                @click="activeTab = 'keywords'"
                                :class="[
                                    activeTab === 'keywords'
                                        ? 'border-indigo-500 text-indigo-600'
                                        : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                                    'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm'
                                ]"
                            >
                                ATS Keywords
                            </button>
                            <button
                                @click="activeTab = 'experience'"
                                :class="[
                                    activeTab === 'experience'
                                        ? 'border-indigo-500 text-indigo-600'
                                        : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                                    'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm'
                                ]"
                            >
                                Experience Bullets
                            </button>
                            <button
                                @click="activeTab = 'cover'"
                                :class="[
                                    activeTab === 'cover'
                                        ? 'border-indigo-500 text-indigo-600'
                                        : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                                    'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm'
                                ]"
                            >
                                Cover Letter
                            </button>
                            <button
                                @click="activeTab = 'linkedin'"
                                :class="[
                                    activeTab === 'linkedin'
                                        ? 'border-indigo-500 text-indigo-600'
                                        : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                                    'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm'
                                ]"
                            >
                                LinkedIn Post
                            </button>
                        </nav>
                    </div>

                    <div class="p-6">
                        <!-- Resume Summary Tab -->
                        <div v-show="activeTab === 'summary'" class="space-y-4">
                            <div class="flex justify-between items-start">
                                <h3 class="text-lg font-semibold text-gray-900">Professional Summary</h3>
                                <button
                                    @click="copyToClipboard(application.resume_summary, 'summary')"
                                    class="text-sm text-indigo-600 hover:text-indigo-500 flex items-center"
                                >
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                    </svg>
                                    {{ copySuccess === 'summary' ? 'Copied!' : 'Copy' }}
                                </button>
                            </div>
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <p class="text-gray-800 whitespace-pre-wrap">{{ application.resume_summary }}</p>
                            </div>
                        </div>

                        <!-- ATS Keywords Tab -->
                        <div v-show="activeTab === 'keywords'" class="space-y-4">
                            <div class="flex justify-between items-start">
                                <h3 class="text-lg font-semibold text-gray-900">ATS Keywords</h3>
                                <button
                                    @click="copyToClipboard(application.ats_keywords, 'keywords')"
                                    class="text-sm text-indigo-600 hover:text-indigo-500 flex items-center"
                                >
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                    </svg>
                                    {{ copySuccess === 'keywords' ? 'Copied!' : 'Copy' }}
                                </button>
                            </div>
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <div class="flex flex-wrap gap-2">
                                    <span v-for="keyword in application.ats_keywords.split(',')" :key="keyword" class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                                        {{ keyword.trim() }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Experience Bullets Tab -->
                        <div v-show="activeTab === 'experience'" class="space-y-4">
                            <div class="flex justify-between items-start">
                                <h3 class="text-lg font-semibold text-gray-900">Experience Bullet Points</h3>
                                <button
                                    @click="copyToClipboard(application.resume_experience, 'experience')"
                                    class="text-sm text-indigo-600 hover:text-indigo-500 flex items-center"
                                >
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                    </svg>
                                    {{ copySuccess === 'experience' ? 'Copied!' : 'Copy' }}
                                </button>
                            </div>
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <ul class="space-y-2">
                                    <li v-for="(bullet, index) in application.resume_experience.split('\n').filter(b => b.trim())" :key="index" class="flex items-start">
                                        <span class="text-indigo-600 mr-2">•</span>
                                        <span class="text-gray-800">{{ bullet.trim() }}</span>
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <!-- Cover Letter Tab -->
                        <div v-show="activeTab === 'cover'" class="space-y-4">
                            <div class="flex justify-between items-start">
                                <h3 class="text-lg font-semibold text-gray-900">Cover Letter</h3>
                                <button
                                    @click="copyToClipboard(application.cover_letter, 'cover')"
                                    class="text-sm text-indigo-600 hover:text-indigo-500 flex items-center"
                                >
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                    </svg>
                                    {{ copySuccess === 'cover' ? 'Copied!' : 'Copy' }}
                                </button>
                            </div>
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <div class="prose prose-gray max-w-none">
                                    <p v-for="(paragraph, index) in application.cover_letter.split('\n\n')" :key="index" class="mb-4 text-gray-800">
                                        {{ paragraph }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- LinkedIn Post Tab -->
                        <div v-show="activeTab === 'linkedin'" class="space-y-4">
                            <div class="flex justify-between items-start">
                                <h3 class="text-lg font-semibold text-gray-900">LinkedIn Post</h3>
                                <button
                                    @click="copyToClipboard(application.linkedin_post, 'linkedin')"
                                    class="text-sm text-indigo-600 hover:text-indigo-500 flex items-center"
                                >
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                    </svg>
                                    {{ copySuccess === 'linkedin' ? 'Copied!' : 'Copy' }}
                                </button>
                            </div>
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <p class="text-gray-800 whitespace-pre-wrap">{{ application.linkedin_post }}</p>
                            </div>
                            <div class="mt-4">
                                <button
                                    @click="shareLinkedIn"
                                    class="w-full sm:w-auto inline-flex justify-center items-center px-4 py-2 bg-blue-700 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-800 focus:bg-blue-800 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                >
                                    <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.79-1.75-1.764s.784-1.764 1.75-1.764 1.75.79 1.75 1.764-.783 1.764-1.75 1.764zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z"/>
                                    </svg>
                                    Post on LinkedIn
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>