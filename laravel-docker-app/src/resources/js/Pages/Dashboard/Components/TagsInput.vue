<template>
    <div class="flex flex-wrap gap-2 p-2 border rounded-md focus-within:ring-2 focus-within:ring-primary-500 focus-within:border-primary-500">
        <span
            v-for="(tag, index) in tags"
            :key="index"
            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-primary-100 text-primary-800"
        >
            {{ tag }}
            <button
                type="button"
                @click="removeTag(index)"
                class="ml-1 inline-flex items-center justify-center w-4 h-4 text-primary-400 hover:text-primary-600"
            >
                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                </svg>
            </button>
        </span>
        <input
            v-model="inputValue"
            @keydown.enter.prevent="addTag"
            @keydown.comma.prevent="addTag"
            type="text"
            :placeholder="placeholder"
            class="flex-1 min-w-[200px] border-0 focus:ring-0 text-sm"
        />
    </div>
</template>

<script>
import { ref, computed, watch } from 'vue'

export default {
    name: 'TagsInput',
    props: {
        modelValue: {
            type: Array,
            default: () => []
        },
        placeholder: {
            type: String,
            default: 'Add tag and press Enter'
        }
    },
    emits: ['update:modelValue'],
    setup(props, { emit }) {
        const inputValue = ref('')
        const tags = computed({
            get: () => props.modelValue || [],
            set: (value) => emit('update:modelValue', value)
        })

        const addTag = () => {
            const tag = inputValue.value.trim()
            if (tag && !tags.value.includes(tag)) {
                tags.value = [...tags.value, tag]
                inputValue.value = ''
            }
        }

        const removeTag = (index) => {
            tags.value = tags.value.filter((_, i) => i !== index)
        }

        return {
            inputValue,
            tags,
            addTag,
            removeTag
        }
    }
}
</script>