<template>
    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="mb-8">
                <h2 class="text-2xl font-bold text-gray-900">Setup Progress</h2>
                <p class="mt-1 text-sm text-gray-600">Complete the setup tasks for each team</p>
            </div>

            <!-- Overall Progress -->
            <div class="bg-white shadow rounded-lg p-6 mb-8">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-medium text-gray-900">Overall Progress</h3>
                    <span class="text-2xl font-bold text-primary-600">{{ overallProgress }}%</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-4 overflow-hidden">
                    <div 
                        class="bg-primary-600 h-full rounded-full transition-all duration-500"
                        :style="{ width: `${overallProgress}%` }"
                    ></div>
                </div>
            </div>

            <!-- Team Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Reservation Team -->
                <div class="bg-white shadow rounded-lg overflow-hidden">
                    <div class="bg-blue-600 px-6 py-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <svg class="h-8 w-8 text-white mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                <h3 class="text-lg font-semibold text-white">Reservation Team</h3>
                            </div>
                            <span class="text-white font-bold">{{ reservationProgress }}%</span>
                        </div>
                    </div>
                    <div class="p-6">
                        <div class="space-y-4">
                            <SetupSectionItem
                                v-for="section in reservationSections"
                                :key="section.key"
                                :section="section"
                                :team="'reservation'"
                                @click="navigateToSection('reservation', section.key)"
                            />
                        </div>
                    </div>
                </div>

                <!-- Marketing Team -->
                <div class="bg-white shadow rounded-lg overflow-hidden">
                    <div class="bg-purple-600 px-6 py-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <svg class="h-8 w-8 text-white mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01" />
                                </svg>
                                <h3 class="text-lg font-semibold text-white">Marketing Team</h3>
                            </div>
                            <span class="text-white font-bold">{{ marketingProgress }}%</span>
                        </div>
                    </div>
                    <div class="p-6">
                        <div class="space-y-4">
                            <SetupSectionItem
                                v-for="section in marketingSections"
                                :key="section.key"
                                :section="section"
                                :team="'marketing'"
                                @click="navigateToSection('marketing', section.key)"
                            />
                        </div>
                    </div>
                </div>

                <!-- IT Team -->
                <div class="bg-white shadow rounded-lg overflow-hidden">
                    <div class="bg-green-600 px-6 py-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <svg class="h-8 w-8 text-white mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                <h3 class="text-lg font-semibold text-white">IT Team</h3>
                            </div>
                            <span class="text-white font-bold">{{ itProgress }}%</span>
                        </div>
                    </div>
                    <div class="p-6">
                        <div class="space-y-4">
                            <SetupSectionItem
                                v-for="section in itSections"
                                :key="section.key"
                                :section="section"
                                :team="'it'"
                                @click="navigateToSection('it', section.key)"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { computed } from 'vue'
import { router } from '@inertiajs/vue3'
import SetupSectionItem from './Components/SetupSectionItem.vue'

export default {
    name: 'SetupOverview',
    components: {
        SetupSectionItem
    },
    props: {
        setupData: {
            type: Array,
            required: true
        }
    },
    setup(props) {
        const getTeamSections = (team) => {
            return props.setupData.filter(item => item.team === team)
        }

        const calculateTeamProgress = (team) => {
            const sections = getTeamSections(team)
            if (sections.length === 0) return 0
            
            const totalProgress = sections.reduce((sum, section) => sum + section.progress, 0)
            return Math.round(totalProgress / sections.length)
        }

        const reservationSections = computed(() => [
            { key: 'hotel_settings', name: 'Hotel Settings', progress: getTeamSections('reservation').find(s => s.section === 'hotel_settings')?.progress || 0 },
            { key: 'user_settings', name: 'User Settings', progress: getTeamSections('reservation').find(s => s.section === 'user_settings')?.progress || 0 },
            { key: 'reservation_settings', name: 'Reservation Settings', progress: getTeamSections('reservation').find(s => s.section === 'reservation_settings')?.progress || 0 },
        ])

        const marketingSections = computed(() => [
            { key: 'banner_pictures', name: 'Banner Pictures', progress: getTeamSections('marketing').find(s => s.section === 'banner_pictures')?.progress || 0 },
            { key: 'logos', name: 'Logos', progress: getTeamSections('marketing').find(s => s.section === 'logos')?.progress || 0 },
            { key: 'colors_fonts', name: 'Colors and Fonts', progress: getTeamSections('marketing').find(s => s.section === 'colors_fonts')?.progress || 0 },
            { key: 'room_pictures_texts', name: 'Room Pictures and Texts', progress: getTeamSections('marketing').find(s => s.section === 'room_pictures_texts')?.progress || 0 },
            { key: 'greetings_texts', name: 'Greetings Texts', progress: getTeamSections('marketing').find(s => s.section === 'greetings_texts')?.progress || 0 },
            { key: 'promotions', name: 'Promotions', progress: getTeamSections('marketing').find(s => s.section === 'promotions')?.progress || 0 },
        ])

        const itSections = computed(() => [
            { key: 'pms_settings', name: 'PMS Settings', progress: getTeamSections('it').find(s => s.section === 'pms_settings')?.progress || 0 },
            { key: 'email_settings', name: 'Email Settings', progress: getTeamSections('it').find(s => s.section === 'email_settings')?.progress || 0 },
            { key: 'security_settings', name: 'Security Settings', progress: getTeamSections('it').find(s => s.section === 'security_settings')?.progress || 0 },
        ])

        const reservationProgress = computed(() => calculateTeamProgress('reservation'))
        const marketingProgress = computed(() => calculateTeamProgress('marketing'))
        const itProgress = computed(() => calculateTeamProgress('it'))
        
        const overallProgress = computed(() => {
            const allSections = props.setupData
            if (allSections.length === 0) return 0
            
            const totalProgress = allSections.reduce((sum, section) => sum + section.progress, 0)
            return Math.round(totalProgress / allSections.length)
        })

        const navigateToSection = (team, section) => {
            router.visit(`/dashboard/setup/${team}/${section}`)
        }

        return {
            reservationSections,
            marketingSections,
            itSections,
            reservationProgress,
            marketingProgress,
            itProgress,
            overallProgress,
            navigateToSection
        }
    }
}
</script>