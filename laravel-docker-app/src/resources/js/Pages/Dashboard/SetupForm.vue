<template>
    <Head :title="`${sectionData.section_name} - ${sectionData.team_name}`" />

    <AuthenticatedLayout>
        <div class="py-6">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Breadcrumb -->
                <nav class="flex mb-8" aria-label="Breadcrumb">
                    <ol class="flex items-center space-x-4">
                        <li>
                            <Link :href="route('dashboard')" class="text-gray-400 hover:text-gray-500">
                                <svg class="flex-shrink-0 h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z" />
                                </svg>
                            </Link>
                        </li>
                        <li>
                            <div class="flex items-center">
                                <svg class="flex-shrink-0 h-5 w-5 text-gray-300" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                                </svg>
                                <span class="ml-4 text-sm font-medium text-gray-500">{{ sectionData.team_name }}</span>
                            </div>
                        </li>
                        <li>
                            <div class="flex items-center">
                                <svg class="flex-shrink-0 h-5 w-5 text-gray-300" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                                </svg>
                                <span class="ml-4 text-sm font-medium text-gray-700">{{ sectionData.section_name }}</span>
                            </div>
                        </li>
                    </ol>
                </nav>

                <!-- Form Card -->
                <div class="bg-white shadow rounded-lg">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <div class="flex items-center justify-between">
                            <div>
                                <h2 class="text-xl font-semibold text-gray-900">{{ sectionData.section_name }}</h2>
                                <p class="mt-1 text-sm text-gray-600">Complete all required fields to proceed</p>
                            </div>
                            <div class="flex items-center">
                                <span class="text-sm text-gray-500 mr-2">Progress:</span>
                                <span class="text-lg font-bold" :class="progressColor">{{ sectionData.progress }}%</span>
                            </div>
                        </div>
                    </div>

                    <form @submit.prevent="submitForm" class="p-6 space-y-6">
                        <div v-for="(field, fieldKey) in sectionData.fields" :key="fieldKey" class="form-group">
                            <DynamicField
                                :field="field"
                                :fieldKey="fieldKey"
                                v-model="formData[fieldKey]"
                                :errors="errors[fieldKey]"
                            />
                        </div>

                        <div class="flex items-center justify-between pt-6 border-t">
                            <Link 
                                :href="route('dashboard')" 
                                class="text-gray-600 hover:text-gray-900"
                            >
                                ← Back to Overview
                            </Link>
                            <div class="flex space-x-3">
                                <button
                                    type="button"
                                    @click="saveProgress"
                                    class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
                                >
                                    Save Progress
                                </button>
                                <button
                                    type="submit"
                                    :disabled="processing"
                                    class="px-4 py-2 bg-primary-600 text-white rounded-md text-sm font-medium hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 disabled:opacity-50"
                                >
                                    {{ processing ? 'Saving...' : 'Save & Continue' }}
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script>
import { ref, computed } from 'vue'
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import DynamicField from './Components/DynamicField.vue'

export default {
    name: 'SetupForm',
    components: {
        Head,
        Link,
        AuthenticatedLayout,
        DynamicField
    },
    props: {
        sectionData: {
            type: Object,
            required: true
        },
        errors: {
            type: Object,
            default: () => ({})
        }
    },
    setup(props) {
        const formData = ref({})
        const processing = ref(false)

        // Initialize form data with existing values
        if (props.sectionData.completed_fields) {
            Object.assign(formData.value, props.sectionData.completed_fields)
        }

        const progressColor = computed(() => {
            if (props.sectionData.progress === 100) return 'text-green-600'
            if (props.sectionData.progress > 50) return 'text-blue-600'
            if (props.sectionData.progress > 0) return 'text-yellow-600'
            return 'text-gray-600'
        })

        const saveProgress = () => {
            processing.value = true
            router.post(`/dashboard/setup/${props.sectionData.team}/${props.sectionData.section}/save`, {
                fields: formData.value,
                complete: false
            }, {
                preserveScroll: true,
                onFinish: () => {
                    processing.value = false
                }
            })
        }

        const submitForm = () => {
            processing.value = true
            router.post(`/dashboard/setup/${props.sectionData.team}/${props.sectionData.section}/save`, {
                fields: formData.value,
                complete: true
            }, {
                onSuccess: () => {
                    router.visit('/dashboard')
                },
                onFinish: () => {
                    processing.value = false
                }
            })
        }

        return {
            formData,
            processing,
            progressColor,
            saveProgress,
            submitForm
        }
    }
}
</script>