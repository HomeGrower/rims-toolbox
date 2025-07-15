<template>
    <div 
        @click="$emit('click')"
        class="flex items-center justify-between p-3 rounded-lg hover:bg-gray-50 cursor-pointer transition-colors"
    >
        <div class="flex items-center flex-1">
            <div class="flex-1">
                <h4 class="text-sm font-medium text-gray-900">{{ section.name }}</h4>
                <div class="mt-1">
                    <div class="flex items-center">
                        <div class="flex-1 bg-gray-200 rounded-full h-2 mr-3">
                            <div 
                                class="h-full rounded-full transition-all duration-300"
                                :class="progressBarColor"
                                :style="{ width: `${section.progress}%` }"
                            ></div>
                        </div>
                        <span class="text-xs font-medium text-gray-600">{{ section.progress }}%</span>
                    </div>
                </div>
            </div>
            <svg v-if="section.progress === 100" class="h-5 w-5 text-green-500 ml-2" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
            </svg>
            <svg v-else class="h-5 w-5 text-gray-400 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
        </div>
    </div>
</template>

<script>
import { computed } from 'vue'

export default {
    name: 'SetupSectionItem',
    props: {
        section: {
            type: Object,
            required: true
        },
        team: {
            type: String,
            required: true
        }
    },
    setup(props) {
        const progressBarColor = computed(() => {
            if (props.section.progress === 0) return 'bg-gray-300'
            if (props.section.progress === 100) return 'bg-green-500'
            if (props.team === 'reservation') return 'bg-blue-500'
            if (props.team === 'marketing') return 'bg-purple-500'
            if (props.team === 'it') return 'bg-green-500'
            return 'bg-primary-500'
        })

        return {
            progressBarColor
        }
    }
}
</script>