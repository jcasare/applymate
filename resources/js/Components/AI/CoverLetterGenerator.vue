<template>
    <div class="max-w-4xl mx-auto p-6">
        <div class="bg-white rounded-lg shadow-lg p-6">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">AI Cover Letter Generator</h2>
            
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Job Title
                    </label>
                    <input
                        v-model="formData.job_title"
                        type="text"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
                        placeholder="e.g., Senior Software Engineer"
                    />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Company Name
                    </label>
                    <input
                        v-model="formData.company_name"
                        type="text"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
                        placeholder="e.g., Tech Corp"
                    />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Job Description
                    </label>
                    <textarea
                        v-model="formData.job_description"
                        rows="4"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
                        placeholder="Paste the job description here..."
                    ></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Your Skills
                    </label>
                    <textarea
                        v-model="formData.user_skills"
                        rows="3"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
                        placeholder="List your relevant skills..."
                    ></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Your Experience
                    </label>
                    <textarea
                        v-model="formData.user_experience"
                        rows="3"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
                        placeholder="Describe your relevant experience..."
                    ></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Tone
                    </label>
                    <select
                        v-model="formData.tone"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
                    >
                        <option value="professional">Professional</option>
                        <option value="friendly">Friendly</option>
                        <option value="enthusiastic">Enthusiastic</option>
                    </select>
                </div>

                <div class="flex justify-between items-center">
                    <button
                        @click="generateCoverLetter"
                        :disabled="isGenerating"
                        class="px-6 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <span v-if="!isGenerating">Generate Cover Letter</span>
                        <span v-else class="flex items-center">
                            <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Generating...
                        </span>
                    </button>

                    <div v-if="providersUsed.length > 0" class="text-sm text-gray-500">
                        Using: {{ providersUsed.join(', ') }}
                    </div>
                </div>
            </div>

            <div v-if="generatedLetter" class="mt-8">
                <div class="border-t pt-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Generated Cover Letter</h3>
                    <div class="bg-gray-50 rounded-md p-4">
                        <pre class="whitespace-pre-wrap font-sans text-gray-700">{{ generatedLetter }}</pre>
                    </div>
                    <div class="mt-4 flex gap-2">
                        <button
                            @click="copyToClipboard"
                            class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700"
                        >
                            {{ copied ? 'Copied!' : 'Copy to Clipboard' }}
                        </button>
                        <button
                            @click="downloadLetter"
                            class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700"
                        >
                            Download as Text
                        </button>
                    </div>
                </div>
            </div>

            <div v-if="error" class="mt-4 p-4 bg-red-50 border border-red-200 rounded-md">
                <p class="text-red-600">{{ error }}</p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import axios from 'axios';

const formData = ref({
    job_title: '',
    company_name: '',
    job_description: '',
    user_skills: '',
    user_experience: '',
    tone: 'professional'
});

const isGenerating = ref(false);
const generatedLetter = ref('');
const providersUsed = ref([]);
const error = ref('');
const copied = ref(false);

const generateCoverLetter = async () => {
    if (!validateForm()) {
        error.value = 'Please fill in all required fields';
        return;
    }

    isGenerating.value = true;
    error.value = '';
    generatedLetter.value = '';
    providersUsed.value = [];

    try {
        const response = await axios.post('/api/ai/cover-letter', formData.value);
        generatedLetter.value = response.data.cover_letter;
        providersUsed.value = response.data.metadata || [];
    } catch (err) {
        error.value = err.response?.data?.message || 'Failed to generate cover letter';
    } finally {
        isGenerating.value = false;
    }
};

const validateForm = () => {
    return formData.value.job_title &&
           formData.value.company_name &&
           formData.value.job_description &&
           formData.value.user_skills &&
           formData.value.user_experience;
};

const copyToClipboard = async () => {
    try {
        await navigator.clipboard.writeText(generatedLetter.value);
        copied.value = true;
        setTimeout(() => {
            copied.value = false;
        }, 2000);
    } catch (err) {
        error.value = 'Failed to copy to clipboard';
    }
};

const downloadLetter = () => {
    const blob = new Blob([generatedLetter.value], { type: 'text/plain' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `cover_letter_${formData.value.company_name.replace(/\s+/g, '_')}.txt`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    window.URL.revokeObjectURL(url);
};
</script>