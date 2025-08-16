<script setup>
import { Head, Link } from '@inertiajs/vue3';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { ref } from 'vue';

defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
});

const features = [
    {
        icon: '🎯',
        title: 'ATS-Optimized Keywords',
        description: 'Extract and highlight relevant keywords that ATS systems look for',
    },
    {
        icon: '📝',
        title: 'Professional Summaries',
        description: 'Generate tailored resume summaries that match your target role',
    },
    {
        icon: '💼',
        title: 'Experience Bullets',
        description: 'Create impactful, quantified achievements for your resume',
    },
    {
        icon: '✉️',
        title: 'Cover Letters',
        description: 'Personalized, compelling cover letters that stand out',
    },
    {
        icon: '🔗',
        title: 'LinkedIn Posts',
        description: 'Engaging posts to announce your job applications',
    },
    {
        icon: '📊',
        title: 'Application Tracking',
        description: 'Keep track of all your applications in one place',
    },
];

const testimonials = [
    {
        name: 'Sarah Johnson',
        role: 'Software Engineer',
        company: 'Tech Corp',
        content: 'ApplyMate helped me land my dream job! The AI-generated materials were spot-on and saved me hours.',
        avatar: 'SJ',
    },
    {
        name: 'Michael Chen',
        role: 'Product Manager',
        company: 'StartupX',
        content: 'The ATS optimization feature is a game-changer. My application response rate increased by 300%!',
        avatar: 'MC',
    },
    {
        name: 'Emily Davis',
        role: 'Marketing Director',
        company: 'Global Inc',
        content: 'Professional, polished, and personalized. ApplyMate made my job search so much easier.',
        avatar: 'ED',
    },
];

const faqs = [
    {
        question: 'How does ApplyMate work?',
        answer: 'Simply paste a job description and your career details. Our AI analyzes the requirements and generates tailored application materials optimized for both ATS systems and human recruiters.',
    },
    {
        question: 'What AI technology do you use?',
        answer: 'We use Claude AI, one of the most advanced language models, to ensure high-quality, contextually relevant content that sounds natural and professional.',
    },
    {
        question: 'Can I edit the generated content?',
        answer: 'Absolutely! All generated content is fully editable. You can refine and personalize it further before using it in your applications.',
    },
    {
        question: 'Is my data secure?',
        answer: 'Yes, we take data security seriously. Your information is encrypted and never shared with third parties. You can delete your data at any time.',
    },
];

const isMenuOpen = ref(false);
</script>

<template>
    <Head title="ApplyMate - AI-Powered Job Application Generator" />
    
    <div class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100">
        <!-- Navigation -->
        <nav class="bg-white shadow-sm sticky top-0 z-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center">
                        <Link href="/" class="flex items-center">
                            <ApplicationLogo width="50px" height="50px" />
                            <span class="ml-3 text-2xl font-bold text-gray-900">ApplyMate</span>
                        </Link>
                    </div>
                    
                    <!-- Desktop Navigation -->
                    <div class="hidden md:flex items-center space-x-8">
                        <a href="#features" class="text-gray-700 hover:text-blue-600 transition">Features</a>
                        <a href="#how-it-works" class="text-gray-700 hover:text-blue-600 transition">How It Works</a>
                        <a href="#testimonials" class="text-gray-700 hover:text-blue-600 transition">Testimonials</a>
                        <a href="#pricing" class="text-gray-700 hover:text-blue-600 transition">Pricing</a>
                        <div class="flex items-center space-x-4 ml-8">
                            <Link
                                v-if="canLogin"
                                :href="route('login')"
                                class="text-gray-700 hover:text-blue-600 font-medium transition"
                            >
                                Log in
                            </Link>
                            <Link
                                v-if="canRegister"
                                :href="route('register')"
                                class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition"
                            >
                                Get Started
                            </Link>
                        </div>
                    </div>
                    
                    <!-- Mobile menu button -->
                    <div class="md:hidden flex items-center">
                        <button @click="isMenuOpen = !isMenuOpen" class="text-gray-700">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path v-if="!isMenuOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                                <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>
                
                <!-- Mobile Navigation -->
                <div v-if="isMenuOpen" class="md:hidden border-t">
                    <div class="px-2 pt-2 pb-3 space-y-1">
                        <a href="#features" class="block px-3 py-2 text-gray-700 hover:text-blue-600">Features</a>
                        <a href="#how-it-works" class="block px-3 py-2 text-gray-700 hover:text-blue-600">How It Works</a>
                        <a href="#testimonials" class="block px-3 py-2 text-gray-700 hover:text-blue-600">Testimonials</a>
                        <a href="#pricing" class="block px-3 py-2 text-gray-700 hover:text-blue-600">Pricing</a>
                        <div class="pt-4 pb-3 border-t">
                            <Link v-if="canLogin" :href="route('login')" class="block px-3 py-2 text-gray-700 hover:text-blue-600">
                                Log in
                            </Link>
                            <Link v-if="canRegister" :href="route('register')" class="block px-3 py-2 mt-1 bg-blue-600 text-white rounded-lg text-center">
                                Get Started
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Hero Section -->
        <section class="relative overflow-hidden">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20 lg:py-32">
                <div class="text-center">
                    <h1 class="text-4xl md:text-6xl font-bold text-gray-900 mb-6">
                        Land Your Dream Job with
                        <span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-indigo-600">
                            AI-Powered Applications
                        </span>
                    </h1>
                    <p class="text-xl text-gray-600 mb-8 max-w-3xl mx-auto">
                        Generate tailored resumes, cover letters, and LinkedIn posts in seconds. 
                        Beat ATS systems and impress recruiters with professionally crafted application materials.
                    </p>
                    <div class="flex flex-col sm:flex-row gap-4 justify-center">
                        <Link
                            :href="route('register')"
                            class="inline-flex items-center justify-center px-8 py-4 text-lg font-semibold text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition transform hover:scale-105"
                        >
                            Start Free Trial
                            <svg class="ml-2 w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                            </svg>
                        </Link>
                        <a
                            href="#how-it-works"
                            class="inline-flex items-center justify-center px-8 py-4 text-lg font-semibold text-gray-700 bg-white border-2 border-gray-300 rounded-lg hover:border-blue-600 hover:text-blue-600 transition"
                        >
                            Watch Demo
                            <svg class="ml-2 w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </a>
                    </div>
                    <p class="mt-4 text-sm text-gray-500">No credit card required • 5 free applications</p>
                </div>
            </div>
            
            <!-- Decorative elements -->
            <div class="absolute top-0 right-0 -mt-20 -mr-20 w-80 h-80 bg-blue-100 rounded-full opacity-20 blur-3xl"></div>
            <div class="absolute bottom-0 left-0 -mb-20 -ml-20 w-80 h-80 bg-indigo-100 rounded-full opacity-20 blur-3xl"></div>
        </section>

        <!-- Features Section -->
        <section id="features" class="py-20 bg-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-16">
                    <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
                        Everything You Need to Get Hired
                    </h2>
                    <p class="text-xl text-gray-600 max-w-2xl mx-auto">
                        Our AI analyzes job descriptions and creates perfectly tailored application materials
                    </p>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <div v-for="feature in features" :key="feature.title" class="text-center p-6 rounded-xl hover:shadow-lg transition">
                        <div class="text-4xl mb-4">{{ feature.icon }}</div>
                        <h3 class="text-xl font-semibold text-gray-900 mb-2">{{ feature.title }}</h3>
                        <p class="text-gray-600">{{ feature.description }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- How It Works Section -->
        <section id="how-it-works" class="py-20 bg-gray-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-16">
                    <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
                        How It Works
                    </h2>
                    <p class="text-xl text-gray-600 max-w-2xl mx-auto">
                        Three simple steps to perfect job applications
                    </p>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <div class="text-center">
                        <div class="w-16 h-16 bg-blue-600 text-white rounded-full flex items-center justify-center text-2xl font-bold mx-auto mb-4">
                            1
                        </div>
                        <h3 class="text-xl font-semibold text-gray-900 mb-2">Paste Job Description</h3>
                        <p class="text-gray-600">Copy and paste the job posting you're interested in</p>
                    </div>
                    
                    <div class="text-center">
                        <div class="w-16 h-16 bg-blue-600 text-white rounded-full flex items-center justify-center text-2xl font-bold mx-auto mb-4">
                            2
                        </div>
                        <h3 class="text-xl font-semibold text-gray-900 mb-2">Add Your Details</h3>
                        <p class="text-gray-600">Provide your experience, skills, and career highlights</p>
                    </div>
                    
                    <div class="text-center">
                        <div class="w-16 h-16 bg-blue-600 text-white rounded-full flex items-center justify-center text-2xl font-bold mx-auto mb-4">
                            3
                        </div>
                        <h3 class="text-xl font-semibold text-gray-900 mb-2">Get Tailored Materials</h3>
                        <p class="text-gray-600">Receive AI-generated resume, cover letter, and more</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Testimonials Section -->
        <section id="testimonials" class="py-20 bg-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-16">
                    <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
                        Loved by Job Seekers
                    </h2>
                    <p class="text-xl text-gray-600 max-w-2xl mx-auto">
                        Join thousands who've landed their dream jobs with ApplyMate
                    </p>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <div v-for="testimonial in testimonials" :key="testimonial.name" class="bg-gray-50 p-6 rounded-xl">
                        <div class="flex items-center mb-4">
                            <div class="w-12 h-12 bg-blue-600 text-white rounded-full flex items-center justify-center font-semibold">
                                {{ testimonial.avatar }}
                            </div>
                            <div class="ml-4">
                                <h4 class="font-semibold text-gray-900">{{ testimonial.name }}</h4>
                                <p class="text-sm text-gray-600">{{ testimonial.role }} at {{ testimonial.company }}</p>
                            </div>
                        </div>
                        <p class="text-gray-700 italic">"{{ testimonial.content }}"</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Pricing Section -->
        <section id="pricing" class="py-20 bg-gray-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-16">
                    <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
                        Simple, Transparent Pricing
                    </h2>
                    <p class="text-xl text-gray-600 max-w-2xl mx-auto">
                        Start free, upgrade when you need more
                    </p>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8 max-w-5xl mx-auto">
                    <!-- Free Plan -->
                    <div class="bg-white p-8 rounded-xl shadow-sm border-2 border-gray-200">
                        <h3 class="text-2xl font-bold text-gray-900 mb-2">Free</h3>
                        <p class="text-gray-600 mb-4">Perfect for getting started</p>
                        <div class="text-4xl font-bold text-gray-900 mb-6">$0</div>
                        <ul class="space-y-3 mb-8">
                            <li class="flex items-start">
                                <svg class="w-5 h-5 text-green-500 mt-1 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                <span class="text-gray-700">5 applications per month</span>
                            </li>
                            <li class="flex items-start">
                                <svg class="w-5 h-5 text-green-500 mt-1 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                <span class="text-gray-700">Basic AI generation</span>
                            </li>
                            <li class="flex items-start">
                                <svg class="w-5 h-5 text-green-500 mt-1 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                <span class="text-gray-700">Export to PDF</span>
                            </li>
                        </ul>
                        <Link :href="route('register')" class="block w-full text-center py-3 px-4 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition">
                            Get Started
                        </Link>
                    </div>
                    
                    <!-- Pro Plan -->
                    <div class="bg-blue-600 p-8 rounded-xl shadow-lg transform scale-105 text-white">
                        <div class="text-sm font-semibold uppercase tracking-wide mb-2">Most Popular</div>
                        <h3 class="text-2xl font-bold mb-2">Pro</h3>
                        <p class="mb-4 opacity-90">For serious job seekers</p>
                        <div class="text-4xl font-bold mb-6">$19<span class="text-lg font-normal">/mo</span></div>
                        <ul class="space-y-3 mb-8">
                            <li class="flex items-start">
                                <svg class="w-5 h-5 text-white mt-1 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                <span>Unlimited applications</span>
                            </li>
                            <li class="flex items-start">
                                <svg class="w-5 h-5 text-white mt-1 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                <span>Advanced AI with Claude 3.5</span>
                            </li>
                            <li class="flex items-start">
                                <svg class="w-5 h-5 text-white mt-1 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                <span>Priority support</span>
                            </li>
                            <li class="flex items-start">
                                <svg class="w-5 h-5 text-white mt-1 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                <span>Application tracking</span>
                            </li>
                        </ul>
                        <Link :href="route('register')" class="block w-full text-center py-3 px-4 bg-white text-blue-600 rounded-lg hover:bg-gray-100 transition font-semibold">
                            Start Free Trial
                        </Link>
                    </div>
                    
                    <!-- Enterprise Plan -->
                    <div class="bg-white p-8 rounded-xl shadow-sm border-2 border-gray-200">
                        <h3 class="text-2xl font-bold text-gray-900 mb-2">Enterprise</h3>
                        <p class="text-gray-600 mb-4">For teams and organizations</p>
                        <div class="text-4xl font-bold text-gray-900 mb-6">Custom</div>
                        <ul class="space-y-3 mb-8">
                            <li class="flex items-start">
                                <svg class="w-5 h-5 text-green-500 mt-1 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                <span class="text-gray-700">Team collaboration</span>
                            </li>
                            <li class="flex items-start">
                                <svg class="w-5 h-5 text-green-500 mt-1 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                <span class="text-gray-700">Custom AI training</span>
                            </li>
                            <li class="flex items-start">
                                <svg class="w-5 h-5 text-green-500 mt-1 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                <span class="text-gray-700">API access</span>
                            </li>
                        </ul>
                        <a href="mailto:enterprise@applymate.com" class="block w-full text-center py-3 px-4 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition">
                            Contact Sales
                        </a>
                    </div>
                </div>
            </div>
        </section>

        <!-- FAQ Section -->
        <section class="py-20 bg-white">
            <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-16">
                    <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
                        Frequently Asked Questions
                    </h2>
                </div>
                
                <div class="space-y-6">
                    <div v-for="faq in faqs" :key="faq.question" class="bg-gray-50 p-6 rounded-lg">
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">{{ faq.question }}</h3>
                        <p class="text-gray-600">{{ faq.answer }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA Section -->
        <section class="py-20 bg-gradient-to-r from-blue-600 to-indigo-600">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                <h2 class="text-3xl md:text-4xl font-bold text-white mb-4">
                    Ready to Land Your Dream Job?
                </h2>
                <p class="text-xl text-blue-100 mb-8">
                    Join thousands of successful job seekers using ApplyMate
                </p>
                <Link
                    :href="route('register')"
                    class="inline-flex items-center justify-center px-8 py-4 text-lg font-semibold text-blue-600 bg-white rounded-lg hover:bg-gray-100 transition transform hover:scale-105"
                >
                    Start Your Free Trial
                    <svg class="ml-2 w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                    </svg>
                </Link>
                <p class="mt-4 text-blue-100">No credit card required • 5 free applications</p>
            </div>
        </section>

        <!-- Footer -->
        <footer class="bg-gray-900 text-white py-12">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                    <div>
                        <div class="flex items-center mb-4">
                            <ApplicationLogo width="40px" height="40px" />
                            <span class="ml-2 text-xl font-bold">ApplyMate</span>
                        </div>
                        <p class="text-gray-400">AI-powered job application generator</p>
                    </div>
                    
                    <div>
                        <h4 class="font-semibold mb-4">Product</h4>
                        <ul class="space-y-2 text-gray-400">
                            <li><a href="#features" class="hover:text-white transition">Features</a></li>
                            <li><a href="#pricing" class="hover:text-white transition">Pricing</a></li>
                            <li><a href="#" class="hover:text-white transition">API</a></li>
                        </ul>
                    </div>
                    
                    <div>
                        <h4 class="font-semibold mb-4">Company</h4>
                        <ul class="space-y-2 text-gray-400">
                            <li><a href="#" class="hover:text-white transition">About</a></li>
                            <li><a href="#" class="hover:text-white transition">Blog</a></li>
                            <li><a href="#" class="hover:text-white transition">Careers</a></li>
                        </ul>
                    </div>
                    
                    <div>
                        <h4 class="font-semibold mb-4">Support</h4>
                        <ul class="space-y-2 text-gray-400">
                            <li><a href="#" class="hover:text-white transition">Help Center</a></li>
                            <li><a href="#" class="hover:text-white transition">Privacy Policy</a></li>
                            <li><a href="#" class="hover:text-white transition">Terms of Service</a></li>
                        </ul>
                    </div>
                </div>
                
                <div class="mt-8 pt-8 border-t border-gray-800 text-center text-gray-400">
                    <p>&copy; 2024 ApplyMate. All rights reserved.</p>
                </div>
            </div>
        </footer>
    </div>
</template>