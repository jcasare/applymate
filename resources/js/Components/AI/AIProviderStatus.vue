<template>
    <div class="bg-white rounded-lg shadow p-4">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">AI Provider Status</h3>
        
        <div v-if="loading" class="flex justify-center py-8">
            <svg class="animate-spin h-8 w-8 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
        </div>

        <div v-else-if="providers.length > 0" class="space-y-3">
            <div v-for="(provider, key) in providers" :key="key" class="border rounded-lg p-3">
                <div class="flex justify-between items-start mb-2">
                    <div>
                        <h4 class="font-medium text-gray-900">{{ provider.name }}</h4>
                        <p class="text-sm text-gray-500">{{ key }}</p>
                    </div>
                    <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">
                        Active
                    </span>
                </div>

                <div class="grid grid-cols-2 gap-2 text-sm">
                    <div>
                        <span class="text-gray-500">Rate Limit:</span>
                        <span class="ml-2 font-medium">
                            {{ provider.rate_limit.remaining }}/{{ provider.rate_limit.limit }}
                        </span>
                    </div>
                    <div>
                        <span class="text-gray-500">Models:</span>
                        <span class="ml-2 font-medium">
                            {{ Object.keys(provider.models).length }}
                        </span>
                    </div>
                </div>

                <div class="mt-2">
                    <details class="cursor-pointer">
                        <summary class="text-sm text-indigo-600 hover:text-indigo-800">
                            View Models
                        </summary>
                        <div class="mt-2 pl-4 text-xs text-gray-600 space-y-1">
                            <div v-if="provider.models.text_models">
                                <strong>Text:</strong> {{ provider.models.current_text_model }}
                            </div>
                            <div v-if="provider.models.vision_models">
                                <strong>Vision:</strong> {{ provider.models.current_vision_model }}
                            </div>
                            <div v-if="provider.models.embedding_models">
                                <strong>Embedding:</strong> {{ provider.models.current_embedding_model }}
                            </div>
                        </div>
                    </details>
                </div>
            </div>
        </div>

        <div v-else class="text-center py-8 text-gray-500">
            <p>No AI providers configured</p>
            <p class="text-sm mt-2">Add API keys in your .env file to enable providers</p>
        </div>

        <div class="mt-4 pt-4 border-t">
            <button
                @click="refreshStatus"
                class="w-full px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 text-sm"
            >
                Refresh Status
            </button>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const providers = ref([]);
const loading = ref(true);

const fetchProviders = async () => {
    loading.value = true;
    try {
        const response = await axios.get('/api/ai/providers');
        providers.value = response.data.providers || {};
    } catch (error) {
        console.error('Failed to fetch providers:', error);
        providers.value = {};
    } finally {
        loading.value = false;
    }
};

const refreshStatus = () => {
    fetchProviders();
};

onMounted(() => {
    fetchProviders();
});
</script>