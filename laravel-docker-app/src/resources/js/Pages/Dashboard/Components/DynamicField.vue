<template>
    <div>
        <label :for="fieldKey" class="block text-sm font-medium text-gray-700">
            {{ field.label }}
            <span v-if="field.required" class="text-red-500">*</span>
        </label>
        
        <p v-if="field.description" class="mt-1 text-sm text-gray-500">
            {{ field.description }}
        </p>

        <!-- Text Input -->
        <input
            v-if="field.type === 'text'"
            :id="fieldKey"
            v-model="modelValue"
            type="text"
            :required="field.required"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
            :class="{ 'border-red-300': errors }"
        />

        <!-- Email Input -->
        <input
            v-else-if="field.type === 'email'"
            :id="fieldKey"
            v-model="modelValue"
            type="email"
            :required="field.required"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
            :class="{ 'border-red-300': errors }"
        />

        <!-- Password Input -->
        <input
            v-else-if="field.type === 'password'"
            :id="fieldKey"
            v-model="modelValue"
            type="password"
            :required="field.required"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
            :class="{ 'border-red-300': errors }"
        />

        <!-- Number Input -->
        <input
            v-else-if="field.type === 'number'"
            :id="fieldKey"
            v-model.number="modelValue"
            type="number"
            :required="field.required"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
            :class="{ 'border-red-300': errors }"
        />

        <!-- Textarea -->
        <textarea
            v-else-if="field.type === 'textarea'"
            :id="fieldKey"
            v-model="modelValue"
            :required="field.required"
            rows="3"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
            :class="{ 'border-red-300': errors }"
        ></textarea>

        <!-- Select -->
        <select
            v-else-if="field.type === 'select'"
            :id="fieldKey"
            v-model="modelValue"
            :required="field.required"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
            :class="{ 'border-red-300': errors }"
        >
            <option value="">Select an option</option>
            <option v-for="option in field.options" :key="option" :value="option">
                {{ option }}
            </option>
        </select>

        <!-- Boolean/Toggle -->
        <div v-else-if="field.type === 'boolean'" class="mt-1">
            <label :for="fieldKey" class="inline-flex items-center">
                <input
                    :id="fieldKey"
                    v-model="modelValue"
                    type="checkbox"
                    class="rounded border-gray-300 text-primary-600 shadow-sm focus:border-primary-300 focus:ring focus:ring-primary-200 focus:ring-opacity-50"
                />
                <span class="ml-2 text-sm text-gray-600">{{ field.label }}</span>
            </label>
        </div>

        <!-- Color Picker -->
        <input
            v-else-if="field.type === 'color'"
            :id="fieldKey"
            v-model="modelValue"
            type="color"
            :required="field.required"
            class="mt-1 block h-10 w-32 rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
            :class="{ 'border-red-300': errors }"
        />

        <!-- File Upload -->
        <input
            v-else-if="field.type === 'file'"
            :id="fieldKey"
            @change="handleFileChange"
            type="file"
            :accept="field.accept"
            :required="field.required && !modelValue"
            class="mt-1 block w-full text-sm text-gray-500
                file:mr-4 file:py-2 file:px-4
                file:rounded-md file:border-0
                file:text-sm file:font-medium
                file:bg-primary-50 file:text-primary-700
                hover:file:bg-primary-100"
        />

        <!-- Time Input -->
        <input
            v-else-if="field.type === 'time'"
            :id="fieldKey"
            v-model="modelValue"
            type="time"
            :required="field.required"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
            :class="{ 'border-red-300': errors }"
        />

        <!-- Date Input -->
        <input
            v-else-if="field.type === 'date'"
            :id="fieldKey"
            v-model="modelValue"
            type="date"
            :required="field.required"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
            :class="{ 'border-red-300': errors }"
        />

        <!-- Tags Input -->
        <div v-else-if="field.type === 'tags'" class="mt-1">
            <TagsInput
                v-model="modelValue"
                :placeholder="field.placeholder || 'Add tag and press Enter'"
            />
        </div>

        <!-- Template Preview -->
        <div v-else-if="field.type === 'template_preview'" class="mt-1">
            <button
                type="button"
                @click="showTemplatePreview"
                class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600"
            >
                <svg class="mr-2 h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                </svg>
                View Template Example
            </button>
            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">Click to view a scrollable preview of the template</p>
        </div>

        <!-- Error Message -->
        <p v-if="errors" class="mt-1 text-sm text-red-600">
            {{ errors }}
        </p>
    </div>
</template>

<script>
import { computed } from 'vue'
import TagsInput from './TagsInput.vue'

export default {
    name: 'DynamicField',
    components: {
        TagsInput
    },
    props: {
        field: {
            type: Object,
            required: true
        },
        fieldKey: {
            type: String,
            required: true
        },
        modelValue: {
            default: null
        },
        errors: {
            type: String,
            default: null
        }
    },
    emits: ['update:modelValue', 'showTemplatePreview'],
    setup(props, { emit }) {
        const modelValue = computed({
            get: () => props.modelValue,
            set: (value) => emit('update:modelValue', value)
        })

        const handleFileChange = (event) => {
            const file = event.target.files[0]
            if (file) {
                emit('update:modelValue', file)
            }
        }

        const showTemplatePreview = () => {
            if (props.field.template_image) {
                emit('showTemplatePreview', {
                    title: props.field.label,
                    image: props.field.template_image
                })
            }
        }

        return {
            modelValue,
            handleFileChange,
            showTemplatePreview
        }
    }
}
</script>