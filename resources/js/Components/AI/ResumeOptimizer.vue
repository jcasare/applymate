<template>
    <div class="max-w-4xl mx-auto p-6">
        <div class="bg-white rounded-lg shadow-lg p-6">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">AI Resume Optimizer</h2>
            
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Current Resume Content
                    </label>
                    <textarea
                        v-model="formData.resume_content"
                        rows="8"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
                        placeholder="Paste your current resume content here..."
                    ></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Target Job Description
                    </label>
                    <textarea
                        v-model="formData.job_description"
                        rows="6"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
                        placeholder="Paste the job description you're targeting..."
                    ></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Optimization Type
                    </label>
                    <select
                        v-model="formData.optimization_type"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
                    >
                        <option value="both">Keywords & Format</option>
                        <option value="keywords">Keywords Only</option>
                        <option value="format">Format Only</option>
                    </select>
                </div>

                <div class="flex justify-between items-center">
                    <button
                        @click="optimizeResume"
                        :disabled="isOptimizing"
                        class="px-6 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <span v-if="!isOptimizing">Optimize Resume</span>
                        <span v-else class="flex items-center">
                            <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Optimizing...
                        </span>
                    </button>

                    <div v-if="consensusScore !== null" class="text-sm text-gray-500">
                        Consensus Score: {{ (consensusScore * 100).toFixed(1) }}%
                    </div>
                </div>
            </div>

            <div v-if="optimizedResume" class="mt-8">
                <div class="border-t pt-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold text-gray-900">Optimized Resume</h3>
                        <div v-if="providersUsed.length > 0" class="text-sm text-gray-500">
                            Providers: {{ providersUsed.join(', ') }}
                        </div>
                    </div>
                    
                    <div class="bg-gray-50 rounded-md p-4">
                        <pre class="whitespace-pre-wrap font-sans text-gray-700">{{ optimizedResume }}</pre>
                    </div>
                    
                    <div class="mt-4 flex gap-2">
                        <button
                            @click="copyToClipboard"
                            class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700"
                        >
                            {{ copied ? 'Copied!' : 'Copy to Clipboard' }}
                        </button>
                        <button
                            @click="downloadResume"
                            class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700"
                        >
                            Download as Text
                        </button>
                        <button
                            @click="showDiff = !showDiff"
                            class="px-4 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700"
                        >
                            {{ showDiff ? 'Hide' : 'Show' }} Changes
                        </button>
                    </div>

                    <div v-if="showDiff" class="mt-4 p-4 bg-yellow-50 border border-yellow-200 rounded-md">
                        <h4 class="font-medium text-gray-900 mb-2">Key Changes:</h4>
                        <ul class="list-disc list-inside text-sm text-gray-700 space-y-1">
                            <li>Keywords have been optimized for ATS scanning</li>
                            <li>Format has been adjusted for better parsing</li>
                            <li>Quantifiable achievements have been highlighted</li>
                            <li>Industry-specific terminology has been incorporated</li>
                        </ul>
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
    resume_content: '',
    job_description: '',
    optimization_type: 'both'
});

const isOptimizing = ref(false);
const optimizedResume = ref('');
const consensusScore = ref(null);
const providersUsed = ref([]);
const error = ref('');
const copied = ref(false);
const showDiff = ref(false);

const optimizeResume = async () => {
    if (!validateForm()) {
        error.value = 'Please fill in all required fields';
        return;
    }

    isOptimizing.value = true;
    error.value = '';
    optimizedResume.value = '';
    consensusScore.value = null;
    providersUsed.value = [];

    try {
        const response = await axios.post('/api/ai/optimize-resume', formData.value);
        optimizedResume.value = response.data.optimized_resume;
        consensusScore.value = response.data.consensus_score;
        providersUsed.value = response.data.providers_used || [];
    } catch (err) {
        error.value = err.response?.data?.message || 'Failed to optimize resume';
    } finally {
        isOptimizing.value = false;
    }
};

const validateForm = () => {
    return formData.value.resume_content && formData.value.job_description;
};

const copyToClipboard = async () => {
    try {
        await navigator.clipboard.writeText(optimizedResume.value);
        copied.value = true;
        setTimeout(() => {
            copied.value = false;
        }, 2000);
    } catch (err) {
        error.value = 'Failed to copy to clipboard';
    }
};

const downloadResume = () => {
    const blob = new Blob([optimizedResume.value], { type: 'text/plain' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'optimized_resume.txt';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    window.URL.revokeObjectURL(url);
};
</script>