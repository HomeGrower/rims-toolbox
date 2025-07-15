<template>
    <div class="min-h-screen bg-gray-100">
        <Head :title="`${project.hotel_name} - Dashboard`" />

        <!-- Header -->
        <header class="bg-white shadow">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center py-6">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">{{ project.hotel_name }}</h1>
                        <p class="text-sm text-gray-600 mt-1">{{ project.name }}</p>
                    </div>
                    <form @submit.prevent="logout" class="inline">
                        <button type="submit" class="text-sm text-gray-600 hover:text-gray-900">
                            Exit Dashboard
                        </button>
                    </form>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Overall Progress -->
            <div class="bg-white rounded-lg shadow p-6 mb-8">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Overall Progress</h2>
                <div class="relative pt-1">
                    <div class="flex mb-2 items-center justify-between">
                        <div>
                            <span class="text-xs font-semibold inline-block py-1 px-2 uppercase rounded-full text-blue-600 bg-blue-200">
                                {{ project.overall_progress }}% Complete
                            </span>
                        </div>
                    </div>
                    <div class="overflow-hidden h-2 mb-4 text-xs flex rounded bg-blue-200">
                        <div :style="`width: ${project.overall_progress}%`" 
                             class="shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center bg-blue-500 transition-all duration-500"></div>
                    </div>
                </div>
            </div>

            <!-- Module Statistics -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
                <div class="bg-white rounded-lg shadow p-6">
                    <p class="text-sm font-medium text-gray-600">Total Modules</p>
                    <p class="text-3xl font-bold text-gray-900">{{ moduleStats.total }}</p>
                </div>
                <div class="bg-white rounded-lg shadow p-6">
                    <p class="text-sm font-medium text-green-600">Completed</p>
                    <p class="text-3xl font-bold text-green-600">{{ moduleStats.completed }}</p>
                </div>
                <div class="bg-white rounded-lg shadow p-6">
                    <p class="text-sm font-medium text-yellow-600">In Progress</p>
                    <p class="text-3xl font-bold text-yellow-600">{{ moduleStats.in_progress }}</p>
                </div>
                <div class="bg-white rounded-lg shadow p-6">
                    <p class="text-sm font-medium text-gray-600">Pending</p>
                    <p class="text-3xl font-bold text-gray-600">{{ moduleStats.pending }}</p>
                </div>
            </div>

            <!-- Modules by Category -->
            <div class="space-y-8">
                <div v-for="(modules, category) in modulesByCategory" :key="category" class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4 capitalize">{{ category.replace('-', ' ') }} Modules</h3>
                    <div class="space-y-4">
                        <div v-for="module in modules" :key="module.id" class="border rounded-lg p-4">
                            <div class="flex items-center justify-between mb-2">
                                <h4 class="font-medium text-gray-900">{{ module.name }}</h4>
                                <span :class="getStatusClass(module.status)" class="px-2 py-1 text-xs font-medium rounded-full">
                                    {{ module.status.replace('_', ' ') }}
                                </span>
                            </div>
                            <p class="text-sm text-gray-600 mb-2">{{ module.description }}</p>
                            <div class="relative pt-1">
                                <div class="overflow-hidden h-2 text-xs flex rounded bg-gray-200">
                                    <div :style="`width: ${module.progress}%`" 
                                         :class="getProgressBarClass(module.status)"
                                         class="shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center transition-all duration-500"></div>
                                </div>
                                <p class="text-xs text-gray-600 mt-1">{{ module.progress }}% Complete</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</template>

<script setup>
import { Head, router } from '@inertiajs/vue3';

const props = defineProps({
    project: Object,
    moduleStats: Object,
    modulesByCategory: Object,
});

const logout = () => {
    router.post(route('code.logout'));
};

const getStatusClass = (status) => {
    const classes = {
        'completed': 'bg-green-100 text-green-800',
        'in_progress': 'bg-yellow-100 text-yellow-800',
        'pending': 'bg-gray-100 text-gray-800',
        'blocked': 'bg-red-100 text-red-800',
    };
    return classes[status] || 'bg-gray-100 text-gray-800';
};

const getProgressBarClass = (status) => {
    const classes = {
        'completed': 'bg-green-500',
        'in_progress': 'bg-yellow-500',
        'pending': 'bg-gray-400',
        'blocked': 'bg-red-500',
    };
    return classes[status] || 'bg-gray-400';
};
</script>