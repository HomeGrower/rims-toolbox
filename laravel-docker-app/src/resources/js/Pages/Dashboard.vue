<script setup>
import ClientLayout from '@/Layouts/ClientLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    project: Object,
    teamProgress: Object,
    languages: Object,
    roomDetailsRequired: Boolean,
    templateExamples: Object,
});

// Modal state for template preview
const showTemplateModal = ref(false);
const templateModalImage = ref('');
const templateModalTitle = ref('');

const showTemplatePreview = (moduleName, templateImage) => {
    templateModalTitle.value = moduleName;
    templateModalImage.value = templateImage;
    showTemplateModal.value = true;
};

// Team cards configuration
const baseTeams = [
    {
        key: 'reservation',
        name: 'Reservation Team',
        icon: '📧',
        description: 'Hotel settings, user accounts, and reservation configurations',
        sections: [
            { key: 'reservation_settings', label: 'Reservation Settings', icon: '⚙️' },
            { key: 'hotel_settings', label: 'Hotel Settings' },
            { key: 'user_settings', label: 'User Settings' },
            { key: 'room_types', label: 'Room Types' },
        ]
    },
];

// Computed teams with dynamic sections
const teams = computed(() => {
    const computedTeams = JSON.parse(JSON.stringify(baseTeams));
    
    // Check if policy sections should be shown based on PMS configuration and settings
    if (props.project?.pms_type) {
        
        // Check if cancellation policies are configured
        const reservationSettings = props.teamProgress?.reservation?.reservation_settings;
        if (reservationSettings?.completed_fields?.cancellation_policies_configured === true) {
            // Add cancellation policies to reservation team sections
            const reservationTeam = computedTeams.find(t => t.key === 'reservation');
            if (reservationTeam) {
                reservationTeam.sections.push({
                    key: 'cancellation_policies',
                    label: 'Cancellation Policies'
                });
            }
        }
        
        // Check if special requests are configured
        if (reservationSettings?.completed_fields?.special_requests_configured === true) {
            // Add special requests to reservation team sections
            const reservationTeam = computedTeams.find(t => t.key === 'reservation');
            if (reservationTeam) {
                reservationTeam.sections.push({
                    key: 'special_requests',
                    label: 'Special Requests'
                });
            }
        }
        
        // Check if deposit policies are configured
        if (reservationSettings?.completed_fields?.deposit_policies_configured === true) {
            // Add deposit policies to reservation team sections
            const reservationTeam = computedTeams.find(t => t.key === 'reservation');
            if (reservationTeam) {
                reservationTeam.sections.push({
                    key: 'deposit_policies',
                    label: 'Deposit Policies'
                });
            }
        }
        
        // Check if payment methods are configured
        if (reservationSettings?.completed_fields?.payment_methods_configured === true) {
            // Add payment methods to reservation team sections
            const reservationTeam = computedTeams.find(t => t.key === 'reservation');
            if (reservationTeam) {
                reservationTeam.sections.push({
                    key: 'payment_methods',
                    label: 'Payment Methods'
                });
            }
        }
        
        // Check if transfer types are configured
        if (reservationSettings?.completed_fields?.transfer_types_configured === true) {
            // Add transfer types to reservation team sections
            const reservationTeam = computedTeams.find(t => t.key === 'reservation');
            if (reservationTeam) {
                reservationTeam.sections.push({
                    key: 'transfer_types',
                    label: 'Transfer Types'
                });
            }
        }
    }
    
    // Add other teams
    const marketingSections = [
        { key: 'banner_pictures', label: 'Banner Pictures' },
        { key: 'logos', label: 'Logos' },
        { key: 'colors_fonts', label: 'Colors & Fonts' },
    ];
    
    // Only add room_details if required
    if (props.roomDetailsRequired) {
        marketingSections.push({ key: 'room_details', label: 'Room Details' });
    }
    
    marketingSections.push(
        { key: 'greetings_texts', label: 'Greetings', customRoute: 'client.greeting-texts.index' },
        { key: 'promotions', label: 'Promotions' }
    );
    
    computedTeams.push({
        key: 'marketing',
        name: 'Marketing Team',
        icon: '🎨',
        description: 'Visual assets, branding, and promotional content',
        sections: marketingSections
    },
    {
        key: 'it',
        name: 'IT Team',
        icon: '💻',
        description: 'Technical setup and system integration',
        sections: [
            { key: 'it_settings', label: 'Settings' },
        ]
    });
    
    return computedTeams;
});

// Calculate progress for a team
const getTeamProgress = (teamKey) => {
    const progress = props.teamProgress?.[teamKey] || {};
    const sections = Object.values(progress);
    
    if (sections.length === 0) return 0;
    
    const totalProgress = sections.reduce((sum, section) => {
        return sum + (section.progress || 0);
    }, 0);
    
    return Math.round(totalProgress / sections.length);
};

// Get status color
const getStatusColor = (status) => {
    switch (status) {
        case 'completed': return 'text-green-600 bg-green-100';
        case 'in_progress': return 'text-yellow-600 bg-yellow-100';
        default: return 'text-gray-600 bg-gray-100';
    }
};

// Get progress bar color
const getProgressBarColor = (progress) => {
    if (progress === 100) return 'bg-green-600';
    if (progress > 0) return 'bg-yellow-600';
    return 'bg-gray-300';
};
</script>

<template>
    <Head title="Setup Dashboard" />

    <ClientLayout>
        <template #pageTitle>
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    {{ project.hotel_name }}
                </h2>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    {{ project.hotelChain?.name }}
                </p>
            </div>
        </template>

        <template #pageActions>
            <div class="text-right">
                <p class="text-sm text-gray-600 dark:text-gray-400">Access Code</p>
                <p class="text-lg font-mono font-bold text-gray-800 dark:text-gray-200">{{ project.access_code }}</p>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <!-- Project Overview Card -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                            Project Overview
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                            <div>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Hotel Brand</p>
                                <p class="font-medium text-gray-900 dark:text-gray-100">{{ project.hotelBrand?.name || '-' }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600 dark:text-gray-400">PMS Type</p>
                                <p class="font-medium text-gray-900 dark:text-gray-100">{{ project.pmsType?.name || '-' }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Primary Language</p>
                                <p class="font-medium text-gray-900 dark:text-gray-100">{{ languages[project.primary_language] || project.primary_language || '-' }}</p>
                            </div>
                        </div>
                        
                        <!-- Additional Languages -->
                        <div v-if="project.languages && project.languages.length > 0" class="border-t border-gray-200 dark:border-gray-700 pt-4 mt-4">
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">Additional Languages</p>
                            <div class="flex flex-wrap gap-2">
                                <span v-for="language in project.languages" 
                                      :key="language"
                                      class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                    {{ languages[language] || language }}
                                </span>
                            </div>
                        </div>
                        
                        <!-- Selected Modules -->
                        <div class="border-t border-gray-200 dark:border-gray-700 pt-4 mt-4">
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">Selected Modules</p>
                            <div class="flex flex-wrap gap-2">
                                <button v-for="module in project.modules" 
                                      :key="module.id"
                                      @click="templateExamples && templateExamples[module.id] ? showTemplatePreview(module.name, templateExamples[module.id]) : null"
                                      :class="[
                                          'inline-flex items-center px-3 py-1 rounded-full text-xs font-medium transition-colors',
                                          templateExamples && templateExamples[module.id] 
                                              ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200 hover:bg-blue-200 dark:hover:bg-blue-800 cursor-pointer' 
                                              : 'bg-gray-100 text-gray-600 dark:bg-gray-900 dark:text-gray-400 cursor-default'
                                      ]"
                                      :title="templateExamples && templateExamples[module.id] ? 'Click to view template example' : null">
                                    {{ module.name }}
                                    <svg v-if="templateExamples && templateExamples[module.id]" 
                                         class="ml-1 h-3 w-3" 
                                         xmlns="http://www.w3.org/2000/svg" 
                                         fill="none" 
                                         viewBox="0 0 24 24" 
                                         stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Team Cards -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <div v-for="team in teams" :key="team.key" 
                         class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <!-- Team Header -->
                            <div class="flex items-start justify-between mb-4">
                                <div class="flex items-center">
                                    <span class="text-3xl mr-3">{{ team.icon }}</span>
                                    <div>
                                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                            {{ team.name }}
                                        </h3>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">
                                            {{ team.description }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Progress Bar -->
                            <div class="mb-4">
                                <div class="flex justify-between text-sm mb-1">
                                    <span class="text-gray-600 dark:text-gray-400">Progress</span>
                                    <span class="font-medium text-gray-900 dark:text-gray-100">
                                        {{ getTeamProgress(team.key) }}%
                                    </span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2.5 dark:bg-gray-700">
                                    <div :class="getProgressBarColor(getTeamProgress(team.key))"
                                         class="h-2.5 rounded-full transition-all duration-500"
                                         :style="`width: ${getTeamProgress(team.key)}%`"></div>
                                </div>
                            </div>

                            <!-- Section List -->
                            <div class="space-y-2">
                                <Link v-for="section in team.sections" 
                                      :key="section.key"
                                      :href="section.customRoute ? route(section.customRoute) : route('client.setup.show', { team: team.key, section: section.key })"
                                      class="block p-3 rounded-lg border border-gray-200 dark:border-gray-700 
                                             hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                    <div class="space-y-2">
                                        <div class="flex items-center justify-between">
                                            <span class="text-sm font-medium text-gray-900 dark:text-gray-100 flex items-center gap-2">
                                                <span v-if="section.icon">{{ section.icon }}</span>
                                                {{ section.label }}
                                            </span>
                                            <span v-if="!['reservation_settings', 'colors_fonts'].includes(section.key)"
                                                  :class="getStatusColor(teamProgress[team.key]?.[section.key]?.status || 'pending')"
                                                  class="text-xs px-2 py-1 rounded-full">
                                                {{ (teamProgress[team.key]?.[section.key]?.status || 'pending').replace('_', ' ') }}
                                            </span>
                                        </div>
                                        <!-- Section Progress Bar -->
                                        <div v-if="!['reservation_settings', 'colors_fonts'].includes(section.key)" class="w-full bg-gray-200 rounded-full h-1.5 dark:bg-gray-700">
                                            <div :class="getProgressBarColor(teamProgress[team.key]?.[section.key]?.progress || 0)"
                                                 class="h-1.5 rounded-full transition-all duration-500"
                                                 :style="`width: ${teamProgress[team.key]?.[section.key]?.progress || 0}%`"></div>
                                        </div>
                                    </div>
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Template Preview Modal -->
        <div v-show="showTemplateModal" class="fixed z-50 inset-0 overflow-hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen p-4 text-center sm:block sm:p-0">
                <!-- Background overlay -->
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="showTemplateModal = false"></div>

                <!-- Modal panel -->
                <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
                    <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-gray-100">
                                {{ templateModalTitle }}
                            </h3>
                            <button @click="showTemplateModal = false" class="text-gray-400 hover:text-gray-500">
                                <span class="sr-only">Close</span>
                                <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                        
                        <!-- Scrollable image container with fixed height -->
                        <div class="relative h-[80vh] overflow-y-auto border border-gray-200 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-900 scrollbar-thin scrollbar-thumb-gray-400 scrollbar-track-gray-100 dark:scrollbar-thumb-gray-600 dark:scrollbar-track-gray-800">
                            <img v-if="templateModalImage" 
                                 :src="`/storage/${templateModalImage}`"
                                 :alt="`${templateModalTitle} template`"
                                 class="w-full h-auto block" />
                            <!-- Loading placeholder -->
                            <div v-else class="flex items-center justify-center h-full">
                                <div class="text-gray-400">
                                    <svg class="animate-spin h-8 w-8 mx-auto mb-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <p class="text-sm">Loading template...</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="button" 
                                @click="showTemplateModal = false"
                                class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </ClientLayout>
</template>