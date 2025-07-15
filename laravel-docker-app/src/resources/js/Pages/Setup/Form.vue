<script setup>
import ClientLayout from '@/Layouts/ClientLayout.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';

const props = defineProps({
    project: Object,
    team: String,
    section: String,
    fields: Object,
    currentData: Object,
    instructions: Array,
});

// Ensure currentData is an object, not an array
const initialData = Array.isArray(props.currentData) ? {} : (props.currentData || {});

// Helper function to extract all possible fields including conditional ones
const getAllPossibleFields = () => {
    const allFields = {};
    
    Object.entries(props.fields).forEach(([fieldKey, field]) => {
        // Add main field
        allFields[fieldKey] = field;
        
        // Add conditional fields
        if (field.conditions && Array.isArray(field.conditions)) {
            field.conditions.forEach(condition => {
                if (condition.fields && Array.isArray(condition.fields)) {
                    condition.fields.forEach(conditionalField => {
                        const conditionalFieldKey = conditionalField.field_name;
                        if (conditionalFieldKey) {
                            allFields[conditionalFieldKey] = conditionalField;
                        }
                    });
                }
            });
        }
    });
    
    return allFields;
};

// Form handling - Initialize form with all data at the root level including conditional fields
const form = useForm(
    Object.entries(getAllPossibleFields()).reduce((acc, [key, field]) => {
        if (field.type === 'repeater') {
            // Initialize repeater fields as arrays
            if (initialData[key] && Array.isArray(initialData[key]) && initialData[key].length > 0) {
                acc[key] = initialData[key];
            } else {
                // Use default_items if specified, otherwise empty array
                const defaultCount = field.default_items || 0;
                if (defaultCount > 0) {
                    // Create items with default values
                    const defaultItem = {};
                    if (field.fields) {
                        Object.entries(field.fields).forEach(([subKey, subField]) => {
                            defaultItem[subKey] = subField.default || '';
                        });
                    }
                    acc[key] = Array(defaultCount).fill(null).map(() => ({...defaultItem}));
                } else {
                    acc[key] = [];
                }
            }
        } else if (field.type === 'grid' && field.fields) {
            // Initialize grid fields
            Object.keys(field.fields).forEach(gridKey => {
                acc[gridKey] = initialData[gridKey] || field.fields[gridKey].default || '';
            });
        } else if (!['section_header', 'subsection_header', 'separator', 'section_separator', 'info_display', 'image_display'].includes(field.type)) {
            // Handle checkbox/boolean fields specially
            if (field.type === 'checkbox' || field.type === 'boolean') {
                if (initialData[key] !== undefined) {
                    acc[key] = initialData[key];
                } else {
                    acc[key] = field.default === true || field.default === 'true' || false;
                }
            } else {
                // Use existing data, or default value from field config, or empty string
                acc[key] = initialData[key] || field.default || '';
            }
        }
        return acc;
    }, {})
);

// File preview storage
const filePreview = ref({});

// Track files that will be compressed
const filesNeedingCompression = ref({});

// Track files that need upscaling
const filesNeedingUpscale = ref({});

// Computed property to get visible fields based on conditions
const visibleFields = computed(() => {
    const result = {};
    
    Object.entries(props.fields).forEach(([fieldKey, field]) => {
        // Always include non-conditional fields
        result[fieldKey] = { ...field };
        
        // If this field has conditions, add conditional fields based on current value
        if (field.conditions && Array.isArray(field.conditions)) {
            const currentValue = form[fieldKey];
            
            field.conditions.forEach(condition => {
                // Check if condition value matches current value
                // Convert string boolean values for comparison
                let conditionValue = condition.value;
                let compareValue = currentValue;
                
                // Handle boolean/checkbox comparisons
                if (field.type === 'boolean' || field.type === 'checkbox') {
                    if (conditionValue === 'true') conditionValue = true;
                    if (conditionValue === 'false') conditionValue = false;
                    compareValue = !!currentValue; // Convert to boolean
                }
                
                if (conditionValue === compareValue && condition.fields) {
                    // Add conditional fields
                    condition.fields.forEach(conditionalField => {
                        const conditionalFieldKey = conditionalField.field_name || fieldKey + '_' + conditionalField.field_name;
                        result[conditionalFieldKey] = {
                            ...conditionalField,
                            conditional_parent: fieldKey,
                            conditional_value: condition.value
                        };
                        
                        // Form value should already be initialized, no need to do it here
                    });
                }
            });
        }
    });
    
    // Remove fields whose conditions are not met
    Object.entries(result).forEach(([fieldKey, field]) => {
        if (field.conditional_parent) {
            const parentValue = form[field.conditional_parent];
            let conditionValue = field.conditional_value;
            let compareValue = parentValue;
            
            // Get parent field type
            const parentField = props.fields[field.conditional_parent];
            if (parentField && (parentField.type === 'boolean' || parentField.type === 'checkbox')) {
                if (conditionValue === 'true') conditionValue = true;
                if (conditionValue === 'false') conditionValue = false;
                compareValue = !!parentValue; // Convert to boolean
            }
            
            if (compareValue !== conditionValue) {
                delete result[fieldKey];
                // Clear form value when field is hidden
                form[fieldKey] = '';
            }
        }
    });
    
    return result;
});

// Add a new item to a repeater field
const addRepeaterItem = (fieldKey) => {
    if (!Array.isArray(form[fieldKey])) {
        form[fieldKey] = [];
    }
    const field = props.fields[fieldKey];
    const newItem = {};
    
    // Initialize fields with defaults
    if (field && field.fields) {
        Object.entries(field.fields).forEach(([subFieldKey, subField]) => {
            if (subField.type === 'grid' && subField.fields) {
                // Initialize all grid subfields
                Object.keys(subField.fields).forEach(gridFieldKey => {
                    newItem[gridFieldKey] = subField.fields[gridFieldKey].default || '';
                });
            } else {
                // Initialize regular fields with defaults
                newItem[subFieldKey] = subField.default || '';
            }
        });
    }
    
    form[fieldKey].push(newItem);
};

// Watch for changes in show_room_details_in_templates to update room type fields
const watchRoomDetailsToggle = () => {
    if (props.section === 'reservation_settings' && 'show_room_details_in_templates' in props.fields) {
        // Save the current form data and reload to get updated fields
        form.transform((data) => ({
            data: data
        })).post(route('client.setup.save', { team: props.team, section: props.section }), {
            preserveScroll: true,
            onSuccess: () => {
                // Reload the page to get updated fields
                window.location.reload();
            }
        });
    }
};

// Watch for changes in cancellation_policies_configured to show/hide mapping fields
const watchCancellationPolicyToggle = () => {
    if (props.section === 'reservation_settings' && 'cancellation_policies_configured' in props.fields) {
        // Save the current form data and reload to get updated fields
        form.transform((data) => ({
            data: data
        })).post(route('client.setup.save', { team: props.team, section: props.section }), {
            preserveScroll: true,
            onSuccess: () => {
                // Reload the page to get updated fields
                window.location.reload();
            }
        });
    }
};

// Remove an item from a repeater field
const removeRepeaterItem = async (fieldKey, index) => {
    if (Array.isArray(form[fieldKey])) {
        // Special handling for room_types - check if room has marketing data
        if (fieldKey === 'room_types' && props.team === 'reservation' && props.section === 'room_types') {
            try {
                // Check if this room type has associated marketing data
                const response = await fetch(`/api/projects/${props.project.id}/check-room-marketing-data/${index}`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    }
                });
                
                const data = await response.json();
                
                if (data.hasMarketingData) {
                    const roomName = form[fieldKey][index]?.display_name || 'This room type';
                    // If it's the first room type (index 0) with marketing data, don't allow deletion
                    if (index === 0) {
                        alert(`${roomName} has associated marketing data and cannot be deleted. Please remove the marketing content first.`);
                        return;
                    }
                    // For other room types, warn but allow deletion
                    if (!confirm(`${roomName} has associated marketing data (images, descriptions, etc.) that will be lost if you delete this room type. Are you sure you want to continue?`)) {
                        return;
                    }
                }
            } catch (error) {
                console.error('Error checking room marketing data:', error);
                // If check fails, still allow deletion with warning
                if (!confirm('Unable to check for associated marketing data. Deleting this room type may result in loss of marketing content. Continue?')) {
                    return;
                }
            }
        }
        
        form[fieldKey].splice(index, 1);
    }
};

// Function to clean and fix URL format
const cleanUrl = (url) => {
    if (!url || typeof url !== 'string') return url;
    
    let cleaned = url.trim();
    
    // Fix common typos like htttps://, htp://, htps://
    cleaned = cleaned.replace(/^h+t+p+s?:\/\//i, function(match) {
        return match.toLowerCase().includes('https') ? 'https://' : 'http://';
    });
    
    // Remove duplicate protocols
    cleaned = cleaned.replace(/^(https?:\/\/)+(https?:\/\/)?/i, '$1');
    
    // Add https:// if no protocol
    if (cleaned && !cleaned.match(/^https?:\/\//i)) {
        cleaned = 'https://' + cleaned;
    }
    
    return cleaned;
};

// Handle URL blur to auto-prepend https:// if missing (for repeater fields)
const handleUrlBlur = (fieldKey, index, subFieldKey) => {
    const value = form[fieldKey][index][subFieldKey];
    console.log('URL blur handler called:', { fieldKey, index, subFieldKey, value });
    
    if (value && value.trim() !== '') {
        const cleaned = cleanUrl(value);
        if (cleaned !== value) {
            form[fieldKey][index][subFieldKey] = cleaned;
            console.log('URL cleaned on blur:', value, '->', cleaned);
        }
    }
};

// Handle URL blur to auto-prepend https:// if missing (for regular fields)
const handleUrlFieldBlur = (fieldKey) => {
    const value = form[fieldKey];
    
    if (value && value.trim() !== '') {
        const cleaned = cleanUrl(value);
        if (cleaned !== value) {
            form[fieldKey] = cleaned;
            console.log('URL cleaned on blur:', value, '->', cleaned);
        }
    }
};


// Team and section titles
const teamNames = {
    'reservation': 'Reservation Team',
    'marketing': 'Marketing Team', 
    'it': 'IT Team',
};

const sectionNames = {
    'hotel_settings': 'Hotel Settings',
    'user_settings': 'User Settings',
    'reservation_settings': 'Reservation Settings',
    'room_types': 'Room Types',
    'banner_pictures': 'Banner Pictures',
    'logos': 'Logos',
    'colors_fonts': 'Colors & Fonts',
    'room_details': 'Room Details',
    'greetings_texts': 'Greetings',
    'promotions': 'Promotions',
    'email_settings': 'Email Settings',
    'pms_settings': 'PMS Settings',
    'security_settings': 'Security Settings',
};

// Submit form
const submit = () => {
    // Debug: Log form data before submitting
    console.log('Form data before submit:', form);
    console.log('File previews:', filePreview.value);
    console.log('Submitting to:', route('client.setup.save', { team: props.team, section: props.section }));
    
    // Process URL fields in repeaters before submission
    Object.entries(props.fields).forEach(([fieldKey, field]) => {
        if (field.type === 'repeater' && form[fieldKey] && Array.isArray(form[fieldKey])) {
            console.log(`Processing repeater field: ${fieldKey}`, field);
            form[fieldKey].forEach((item, index) => {
                if (field.fields) {
                    Object.entries(field.fields).forEach(([subFieldKey, subField]) => {
                        if (subField.type === 'url' && item[subFieldKey] !== undefined) {
                            const value = item[subFieldKey];
                            // Allow empty URLs
                            if (value === '' || value === null) {
                                console.log(`Empty URL field allowed: ${fieldKey}[${index}].${subFieldKey}`);
                            } else if (value && typeof value === 'string' && value.trim() !== '') {
                                const cleanedValue = cleanUrl(value);
                                if (cleanedValue !== value) {
                                    form[fieldKey][index][subFieldKey] = cleanedValue;
                                    console.log(`Fixed URL in ${fieldKey}[${index}].${subFieldKey}: "${value}" -> "${cleanedValue}"`);
                                } else {
                                    console.log(`URL already valid: ${fieldKey}[${index}].${subFieldKey} = ${value}`);
                                }
                            }
                        }
                    });
                }
            });
        }
        // Also handle regular URL fields
        if (field.type === 'url' && form[fieldKey] !== undefined) {
            const value = form[fieldKey];
            // Allow empty URLs
            if (value === '' || value === null) {
                console.log(`Empty URL field allowed: ${fieldKey}`);
            } else if (value && typeof value === 'string' && value.trim() !== '') {
                const cleanedValue = cleanUrl(value);
                if (cleanedValue !== value) {
                    form[fieldKey] = cleanedValue;
                    console.log(`Fixed URL in ${fieldKey}: "${value}" -> "${cleanedValue}"`);
                } else {
                    console.log(`URL already valid: ${fieldKey} = ${value}`);
                }
            }
        }
    });
    
    // Transform the data to match backend expectations
    form.transform((data) => ({
        data: data
    })).post(route('client.setup.save', { team: props.team, section: props.section }), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            console.log('Save successful!');
            // Clear file previews after successful save since files are now saved
            filePreview.value = {};
            repeaterFilePreviews.value = {};
        },
        onError: (errors) => {
            console.error('Save failed:', errors);
        },
        onFinish: () => {
            console.log('Save request finished');
        }
    });
};

// Render field based on type
const getFieldComponent = (type) => {
    const components = {
        'text': 'text',
        'email': 'email',
        'number': 'number',
        'textarea': 'textarea',
        'select': 'select',
        'checkbox': 'checkbox',
        'radio': 'radio',
        'file': 'file',
        'files': 'file',
        'url': 'url',
        'color': 'color',
        'date': 'date',
    };
    return components[type] || 'text';
};

// Check if a field should be shown based on dependencies
const shouldShowField = (field) => {
    if (field.depends_on) {
        // Show field only if the dependency field is truthy
        return !!form[field.depends_on];
    }
    if (field.depends_on_not) {
        // Show field only if the dependency field is falsy
        return !form[field.depends_on_not];
    }
    return true;
};

// Handle file change and create preview for images
const handleFileChange = (event, fieldKey, field) => {
    const files = event.target.files;
    if (!files || files.length === 0) return;
    
    const file = files[0];
    form[fieldKey] = file;
    
    // Reset flags
    delete filesNeedingCompression.value[fieldKey];
    delete filesNeedingUpscale.value[fieldKey];
    
    // Check if file needs compression (larger than 2MB)
    const maxSize = 2 * 1024 * 1024; // 2MB
    if (file.type.startsWith('image/') && file.size > maxSize) {
        filesNeedingCompression.value[fieldKey] = true;
    }
    
    // Create preview for image files and check dimensions
    if (field.accept?.includes('image') && file.type.startsWith('image/')) {
        const reader = new FileReader();
        reader.onload = (e) => {
            filePreview.value[fieldKey] = e.target.result;
            
            // Create an image to check dimensions
            const img = new Image();
            img.onload = () => {
                const minWidth = 2000;
                const minHeight = 1500;
                
                if (img.width < minWidth || img.height < minHeight) {
                    filesNeedingUpscale.value[fieldKey] = {
                        currentWidth: img.width,
                        currentHeight: img.height,
                        minWidth: minWidth,
                        minHeight: minHeight
                    };
                }
            };
            img.src = e.target.result;
        };
        reader.readAsDataURL(file);
    }
};

// Delete repeater file
const deleteRepeaterFile = (fieldKey, index, subFieldKey) => {
    // Clear the file from form data
    if (form[fieldKey] && form[fieldKey][index]) {
        form[fieldKey][index][subFieldKey] = null;
    }
    
    // Clear the preview
    if (repeaterFilePreviews.value[fieldKey] && 
        repeaterFilePreviews.value[fieldKey][index] && 
        repeaterFilePreviews.value[fieldKey][index][subFieldKey]) {
        delete repeaterFilePreviews.value[fieldKey][index][subFieldKey];
    }
    
    // Clear upscale/compression flags
    const compressionKey = `${fieldKey}_${index}_${subFieldKey}`;
    if (filesNeedingUpscale.value[compressionKey]) {
        delete filesNeedingUpscale.value[compressionKey];
    }
    if (filesNeedingCompression.value[compressionKey]) {
        delete filesNeedingCompression.value[compressionKey];
    }
};

// Store repeater file previews
const repeaterFilePreviews = ref({});

// Handle file change in repeater fields
const handleRepeaterFileChange = (event, fieldKey, index, subFieldKey) => {
    const files = event.target.files;
    if (!files || files.length === 0) return;
    
    const file = files[0];
    if (!form[fieldKey][index]) {
        form[fieldKey][index] = {};
    }
    form[fieldKey][index][subFieldKey] = file;
    
    // Reset flags
    const compressionKey = `${fieldKey}_${index}_${subFieldKey}`;
    delete filesNeedingCompression.value[compressionKey];
    delete filesNeedingUpscale.value[compressionKey];
    
    // Check if file needs compression (larger than 2MB)
    const maxSize = 2 * 1024 * 1024; // 2MB
    if (file.type.startsWith('image/') && file.size > maxSize) {
        filesNeedingCompression.value[compressionKey] = true;
    }
    
    // Create preview for image files and check dimensions
    if (file.type.startsWith('image/')) {
        const reader = new FileReader();
        reader.onload = (e) => {
            const previewKey = `${fieldKey}_${index}_${subFieldKey}`;
            if (!repeaterFilePreviews.value[fieldKey]) {
                repeaterFilePreviews.value[fieldKey] = {};
            }
            if (!repeaterFilePreviews.value[fieldKey][index]) {
                repeaterFilePreviews.value[fieldKey][index] = {};
            }
            repeaterFilePreviews.value[fieldKey][index][subFieldKey] = e.target.result;
            
            // Create an image to check dimensions
            const img = new Image();
            img.onload = () => {
                const minWidth = 2000;
                const minHeight = 1500;
                
                if (img.width < minWidth || img.height < minHeight) {
                    filesNeedingUpscale.value[compressionKey] = {
                        currentWidth: img.width,
                        currentHeight: img.height,
                        minWidth: minWidth,
                        minHeight: minHeight
                    };
                }
            };
            img.src = e.target.result;
        };
        reader.readAsDataURL(file);
    }
};

// Modal state for example images
const showExampleModal = ref(false);
const exampleModalImage = ref('');
const exampleModalTitle = ref('');

const showExampleImage = (imagePath, title) => {
    exampleModalImage.value = imagePath;
    exampleModalTitle.value = title;
    showExampleModal.value = true;
};

// Modal state for template preview
const showTemplateModal = ref(false);
const templateModalImage = ref('');
const templateModalTitle = ref('');

const handleTemplatePreview = (data) => {
    templateModalImage.value = data.image;
    templateModalTitle.value = data.title;
    showTemplateModal.value = true;
};
</script>

<template>
    <Head :title="`${sectionNames[section]} - ${teamNames[team]}`" />

    <ClientLayout>
        <template #pageTitle>
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    {{ sectionNames[section] }}
                </h2>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    {{ teamNames[team] }} - {{ project.hotel_name }}
                </p>
            </div>
        </template>

        <template #pageActions>
            <Link :href="route('client.dashboard')" 
                  class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 active:bg-gray-900 focus:outline-none focus:border-gray-900 focus:ring ring-gray-300 disabled:opacity-25 transition ease-in-out duration-150">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Back to Dashboard
            </Link>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-4xl sm:px-6 lg:px-8">
                <!-- Instructions Card -->
                <div v-if="instructions && instructions.length > 0" 
                     class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4 mb-6">
                    <h3 class="text-sm font-semibold text-blue-800 dark:text-blue-200 mb-2">
                        Important Instructions
                    </h3>
                    <ul class="list-disc list-inside space-y-1">
                        <li v-for="(instruction, index) in instructions" 
                            :key="index"
                            class="text-sm text-blue-700 dark:text-blue-300">
                            {{ instruction }}
                        </li>
                    </ul>
                </div>

                <!-- Form Card -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <form @submit.prevent="submit" class="p-6 space-y-6">
                        <!-- Dynamic Fields -->
                        <div v-for="(field, fieldKey) in visibleFields" :key="fieldKey" v-show="shouldShowField(field)" class="space-y-2">
                            <!-- Text Input -->
                            <div v-if="['text', 'email', 'url', 'number'].includes(field.type)">
                                <label :for="fieldKey" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    {{ field.label }}
                                    <span v-if="field.required" class="text-red-500">*</span>
                                </label>
                                <input 
                                    :id="fieldKey"
                                    v-model="form[fieldKey]"
                                    :type="field.type"
                                    :required="field.required"
                                    :placeholder="field.placeholder || (field.type === 'url' ? 'https://example.com' : '')"
                                    @blur="field.type === 'url' ? handleUrlFieldBlur(fieldKey) : null"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                />
                                <p v-if="field.description" class="mt-1 text-sm text-gray-500 dark:text-gray-400 whitespace-pre-line">
                                    {{ field.description }}
                                </p>
                            </div>

                            <!-- Textarea -->
                            <div v-else-if="field.type === 'textarea'">
                                <label :for="fieldKey" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    {{ field.label }}
                                    <span v-if="field.required" class="text-red-500">*</span>
                                </label>
                                <textarea 
                                    :id="fieldKey"
                                    v-model="form[fieldKey]"
                                    :required="field.required"
                                    :placeholder="field.placeholder"
                                    :rows="field.rows || 3"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                />
                                <p v-if="field.description" class="mt-1 text-sm text-gray-500 dark:text-gray-400 whitespace-pre-line">
                                    {{ field.description }}
                                </p>
                            </div>

                            <!-- Select -->
                            <div v-else-if="field.type === 'select'">
                                <label :for="fieldKey" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    {{ field.label }}
                                    <span v-if="field.required" class="text-red-500">*</span>
                                </label>
                                <select 
                                    :id="fieldKey"
                                    v-model="form[fieldKey]"
                                    :required="field.required"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                >
                                    <option value="">Select an option</option>
                                    <template v-if="typeof field.options === 'object' && !Array.isArray(field.options)">
                                        <option v-for="(label, value) in field.options" :key="value" :value="value">
                                            {{ label }}
                                        </option>
                                    </template>
                                    <template v-else>
                                        <option v-for="option in field.options" :key="option" :value="option">
                                            {{ option }}
                                        </option>
                                    </template>
                                </select>
                                <p v-if="field.description" class="mt-1 text-sm text-gray-500 dark:text-gray-400 whitespace-pre-line">
                                    {{ field.description }}
                                </p>
                            </div>

                            <!-- Checkbox/Boolean -->
                            <div v-else-if="field.type === 'checkbox' || field.type === 'boolean'">
                                <div class="flex items-start justify-between">
                                    <div class="flex items-start">
                                        <div class="flex items-center h-5">
                                            <input 
                                                :id="fieldKey"
                                                v-model="form[fieldKey]"
                                                type="checkbox"
                                                class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded dark:bg-gray-700 dark:border-gray-600"
                                            />
                                        </div>
                                        <div class="ml-3 text-sm">
                                            <label :for="fieldKey" class="font-medium text-gray-700 dark:text-gray-300">
                                                {{ field.label }}
                                        </label>
                                        <p v-if="field.description" class="text-gray-500 dark:text-gray-400 whitespace-pre-line">
                                            {{ field.description }}
                                        </p>
                                    </div>
                                    </div>
                                    <!-- Example Image for Policy Fields -->
                                    <div v-if="field.example_image && ['cancellation_policies_configured', 'special_requests_configured', 'deposit_policies_configured', 'payment_methods_configured', 'transfer_types_configured'].includes(fieldKey)" 
                                         class="ml-4">
                                        <img :src="`/storage/${field.example_image}`" 
                                             :alt="`${field.label} example`"
                                             class="h-32 w-auto rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 cursor-pointer hover:opacity-90 transition-opacity"
                                             @click="showExampleImage(field.example_image, field.label)" />
                                    </div>
                                </div>
                            </div>

                            <!-- File Upload -->
                            <div v-else-if="field.type === 'file' || field.type === 'files'">
                                <label :for="fieldKey" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    {{ field.label }}
                                    <span v-if="field.required" class="text-red-500">*</span>
                                </label>
                                
                                <!-- File Preview if exists -->
                                <div v-if="form[fieldKey] && typeof form[fieldKey] === 'string' && field.accept?.includes('image')" class="mt-2 mb-3">
                                    <img :src="`/storage/${form[fieldKey]}`" 
                                         :alt="field.label"
                                         class="max-w-full h-auto max-h-48 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700" />
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                        Current: {{ form[fieldKey].split('/').pop() }}
                                    </p>
                                </div>
                                
                                <!-- Preview for newly selected file -->
                                <div v-if="filePreview[fieldKey] && field.accept?.includes('image')" class="mt-2 mb-3">
                                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">Preview:</p>
                                    <img :src="filePreview[fieldKey]" 
                                         :alt="field.label"
                                         class="max-w-full h-auto max-h-48 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700" />
                                </div>
                                
                                <input 
                                    :id="fieldKey"
                                    type="file"
                                    :required="field.required"
                                    :accept="field.accept"
                                    :multiple="field.multiple || field.type === 'files'"
                                    @change="handleFileChange($event, fieldKey, field)"
                                    class="mt-1 block w-full text-sm text-gray-500 dark:text-gray-400
                                           file:mr-4 file:py-2 file:px-4
                                           file:rounded-full file:border-0
                                           file:text-sm file:font-semibold
                                           file:bg-indigo-50 file:text-indigo-700
                                           hover:file:bg-indigo-100
                                           dark:file:bg-gray-700 dark:file:text-gray-300"
                                />
                                <p v-if="field.description" class="mt-1 text-sm text-gray-500 dark:text-gray-400 whitespace-pre-line">
                                    {{ field.description }}
                                </p>
                                <!-- Image processing notices -->
                                <div v-if="filesNeedingUpscale[fieldKey] || filesNeedingCompression[fieldKey]" class="mt-2 space-y-2">
                                    <!-- Upscale notice -->
                                    <div v-if="filesNeedingUpscale[fieldKey]" class="flex items-start text-sm text-blue-600 dark:text-blue-400">
                                        <svg class="w-4 h-4 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                                        </svg>
                                        <span>
                                            This image ({{ filesNeedingUpscale[fieldKey].currentWidth }}×{{ filesNeedingUpscale[fieldKey].currentHeight }}px) 
                                            is smaller than the minimum required dimensions ({{ filesNeedingUpscale[fieldKey].minWidth }}×{{ filesNeedingUpscale[fieldKey].minHeight }}px) 
                                            and will be automatically upscaled.
                                        </span>
                                    </div>
                                    <!-- Compression notice -->
                                    <div v-if="filesNeedingCompression[fieldKey]" class="flex items-start text-sm text-amber-600 dark:text-amber-400">
                                        <svg class="w-4 h-4 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                        </svg>
                                        <span>This image is larger than 2MB and will be automatically compressed during upload.</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Tags Input -->
                            <div v-else-if="field.type === 'tags'">
                                <label :for="fieldKey" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    {{ field.label }}
                                    <span v-if="field.required" class="text-red-500">*</span>
                                </label>
                                <input 
                                    :id="fieldKey"
                                    v-model="form[fieldKey]"
                                    type="text"
                                    :required="field.required"
                                    :placeholder="field.placeholder || 'Enter values separated by commas'"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                />
                                <p v-if="field.description" class="mt-1 text-sm text-gray-500 dark:text-gray-400 whitespace-pre-line">
                                    {{ field.description }}
                                </p>
                            </div>

                            <!-- Color Picker -->
                            <div v-else-if="field.type === 'color'">
                                <label :for="fieldKey" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    {{ field.label }}
                                    <span v-if="field.required" class="text-red-500">*</span>
                                </label>
                                <div class="mt-1 flex items-center space-x-3">
                                    <input 
                                        :id="fieldKey"
                                        v-model="form[fieldKey]"
                                        type="color"
                                        :required="field.required"
                                        class="h-10 w-20 rounded border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    />
                                    <input 
                                        v-model="form[fieldKey]"
                                        type="text"
                                        :placeholder="'#000000'"
                                        class="block flex-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                    />
                                </div>
                                <p v-if="field.description" class="mt-1 text-sm text-gray-500 dark:text-gray-400 whitespace-pre-line">
                                    {{ field.description }}
                                </p>
                            </div>

                            <!-- Info Display (Read-only information) -->
                            <div v-else-if="field.type === 'info_display' || field.type === 'info'">
                                <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                                    <h4 class="text-sm font-semibold text-blue-800 dark:text-blue-200 mb-2">
                                        {{ field.label }}
                                    </h4>
                                    <p v-if="field.description" class="text-sm text-blue-700 dark:text-blue-300 mb-2 whitespace-pre-line">
                                        {{ field.description }}
                                    </p>
                                    <p v-if="field.example" class="text-sm text-blue-600 dark:text-blue-400 font-mono bg-white dark:bg-gray-800 p-2 rounded">
                                        Example: {{ field.example }}
                                    </p>
                                </div>
                            </div>

                            <!-- Image Display (Read-only example images) -->
                            <div v-else-if="field.type === 'image_display'">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    {{ field.label }}
                                </label>
                                <div class="p-4 bg-gray-50 dark:bg-gray-900 rounded-lg">
                                    <img :src="`/storage/${field.value}`" 
                                         :alt="field.label"
                                         class="max-w-full h-auto max-h-64 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 mb-2" />
                                    <p v-if="field.description" class="text-sm text-gray-600 dark:text-gray-400 whitespace-pre-line">
                                        {{ field.description }}
                                    </p>
                                </div>
                            </div>

                            <!-- Separator -->
                            <div v-else-if="field.type === 'separator'">
                                <hr class="border-t border-gray-200 dark:border-gray-700 my-6" />
                            </div>

                            <!-- Section Separator (thicker) -->
                            <div v-else-if="field.type === 'section_separator'">
                                <hr class="border-t-2 border-gray-300 dark:border-gray-600 my-8" />
                            </div>

                            <!-- Section Header -->
                            <div v-else-if="field.type === 'section_header'">
                                <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 mb-6">
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                        {{ field.label }}
                                    </h3>
                                    <p v-if="field.description" class="text-sm text-gray-600 dark:text-gray-400 mt-1 whitespace-pre-line">
                                        {{ field.description }}
                                    </p>
                                </div>
                            </div>

                            <!-- Subsection Header -->
                            <div v-else-if="field.type === 'subsection_header'">
                                <h4 class="text-md font-medium text-gray-700 dark:text-gray-300 mb-3 mt-4">
                                    {{ field.label }}
                                </h4>
                            </div>

                            <!-- Grid Layout -->
                            <div v-else-if="field.type === 'grid'">
                                <div :class="`grid grid-cols-1 md:grid-cols-${field.columns || 2} gap-4`">
                                    <div v-for="(gridField, gridFieldKey) in field.fields" :key="gridFieldKey">
                                        <!-- File field in grid -->
                                        <div v-if="gridField.type === 'file'">
                                            <label :for="gridFieldKey" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                                {{ gridField.label }}
                                                <span v-if="gridField.required" class="text-red-500">*</span>
                                            </label>
                                            
                                            <!-- File Preview - show saved image OR newly selected image -->
                                            <div v-if="(form[gridFieldKey] && typeof form[gridFieldKey] === 'string' && gridField.accept?.includes('image')) || filePreview[gridFieldKey]" class="mt-2 mb-3">
                                                <img :src="filePreview[gridFieldKey] || `/storage/${form[gridFieldKey]}`" 
                                                     :alt="gridField.label"
                                                     class="max-w-full h-auto max-h-32 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700" />
                                                <p v-if="form[gridFieldKey] && typeof form[gridFieldKey] === 'string'" class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                                    Current: {{ form[gridFieldKey].split('/').pop() }}
                                                </p>
                                            </div>
                                            
                                            <input 
                                                :id="gridFieldKey"
                                                type="file"
                                                :required="gridField.required"
                                                :accept="gridField.accept"
                                                @change="handleFileChange($event, gridFieldKey, gridField)"
                                                class="mt-1 block w-full text-sm text-gray-500 dark:text-gray-400
                                                       file:mr-4 file:py-2 file:px-4
                                                       file:rounded-full file:border-0
                                                       file:text-sm file:font-semibold
                                                       file:bg-indigo-50 file:text-indigo-700
                                                       hover:file:bg-indigo-100
                                                       dark:file:bg-gray-700 dark:file:text-gray-300"
                                            />
                                            <p v-if="gridField.description" class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                                {{ gridField.description }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Repeater Field -->
                            <div v-else-if="field.type === 'repeater'">
                                <label v-if="field.label !== false" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    {{ field.label }}
                                    <span v-if="field.required" class="text-red-500">*</span>
                                </label>
                                <p v-if="field.description" class="text-sm text-gray-500 dark:text-gray-400 mb-3 whitespace-pre-line">
                                    {{ field.description }}
                                </p>
                                
                                <div class="space-y-4">
                                    <!-- Show message when no items -->
                                    <div v-if="!form[fieldKey] || form[fieldKey].length === 0" 
                                         class="text-center py-8 text-gray-500 dark:text-gray-400 border-2 border-dashed border-gray-300 dark:border-gray-700 rounded-lg">
                                        <p class="text-sm">No items added yet. Click the button below to add one.</p>
                                    </div>
                                    
                                    <div v-for="(item, index) in form[fieldKey]" :key="index" 
                                         class="p-4 border border-gray-200 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-900">
                                        <!-- Grid layout for user settings -->
                                        <div v-if="fieldKey === 'users' && field.grid_layout" class="grid grid-cols-2 gap-4">
                                            <!-- Email (top-left) -->
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                                    {{ field.fields.email.label }}
                                                    <span v-if="field.fields.email.required" class="text-red-500">*</span>
                                                </label>
                                                <input 
                                                    v-model="form[fieldKey][index].email"
                                                    type="email"
                                                    :placeholder="field.fields.email.placeholder"
                                                    :required="field.fields.email.required"
                                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                                />
                                            </div>
                                            <!-- Role dropdown (top-right) -->
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                                    {{ field.fields.role.label }}
                                                    <span v-if="field.fields.role.required" class="text-red-500">*</span>
                                                </label>
                                                <select 
                                                    v-model="form[fieldKey][index].role"
                                                    :required="field.fields.role.required"
                                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                                >
                                                    <option v-for="(label, value) in field.fields.role.options" :key="value" :value="value">
                                                        {{ label }}
                                                    </option>
                                                </select>
                                            </div>
                                            <!-- First name (bottom-left) -->
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                                    {{ field.fields.first_name.label }}
                                                    <span v-if="field.fields.first_name.required" class="text-red-500">*</span>
                                                </label>
                                                <input 
                                                    v-model="form[fieldKey][index].first_name"
                                                    type="text"
                                                    :placeholder="field.fields.first_name.placeholder"
                                                    :required="field.fields.first_name.required"
                                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                                />
                                            </div>
                                            <!-- Last name (bottom-right) -->
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                                    {{ field.fields.last_name.label }}
                                                    <span v-if="field.fields.last_name.required" class="text-red-500">*</span>
                                                </label>
                                                <input 
                                                    v-model="form[fieldKey][index].last_name"
                                                    type="text"
                                                    :placeholder="field.fields.last_name.placeholder"
                                                    :required="field.fields.last_name.required"
                                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                                />
                                            </div>
                                        </div>
                                        <!-- Special layout for policy repeater fields and room types -->
                                        <div v-else-if="['room_types', 'cancellation_policies', 'special_requests', 'deposit_policies', 'payment_methods', 'transfer_types'].includes(fieldKey)" class="grid grid-cols-12 gap-4">
                                            <!-- Code field - 4 columns -->
                                            <div class="col-span-12 md:col-span-4">
                                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                                    {{ field.fields.code.label }}
                                                </label>
                                                <input 
                                                    v-model="form[fieldKey][index].code"
                                                    type="text"
                                                    :placeholder="field.fields.code.placeholder"
                                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                                />
                                                <p v-if="field.fields.code.description" class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                                    {{ field.fields.code.description }}
                                                </p>
                                            </div>
                                            <!-- Description/Display Name field - 8 columns -->
                                            <div class="col-span-12 md:col-span-8">
                                                <!-- For room_types use display_name -->
                                                <template v-if="fieldKey === 'room_types'">
                                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                                        {{ field.fields.display_name.label }}
                                                    </label>
                                                    <input 
                                                        v-model="form[fieldKey][index].display_name"
                                                        type="text"
                                                        :placeholder="field.fields.display_name.placeholder"
                                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                                    />
                                                    <p v-if="field.fields.display_name.description" class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                                        {{ field.fields.display_name.description }}
                                                    </p>
                                                </template>
                                                <!-- For other fields use description -->
                                                <template v-else>
                                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                                        {{ field.fields.description.label }}
                                                    </label>
                                                    <textarea 
                                                        v-model="form[fieldKey][index].description"
                                                        :rows="field.fields.description.rows || 3"
                                                        :placeholder="field.fields.description.placeholder"
                                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                                    />
                                                    <p v-if="field.fields.description.description" class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                                        {{ field.fields.description.description }}
                                                    </p>
                                                </template>
                                            </div>
                                        </div>
                                        <!-- Default layout for other repeaters -->
                                        <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                            <!-- Left column: All text fields -->
                                            <div class="space-y-4">
                                                <template v-for="(subField, subFieldKey) in field.fields" :key="subFieldKey">
                                                    <template v-if="subFieldKey !== 'image'">
                                                        <!-- Text/Email/URL fields -->
                                                        <div v-if="['text', 'email', 'url'].includes(subField.type)">
                                                            <label :for="`${fieldKey}_${index}_${subFieldKey}`" 
                                                                   class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                                                {{ subField.label }}
                                                            </label>
                                                            <input 
                                                                :id="`${fieldKey}_${index}_${subFieldKey}`"
                                                                v-model="form[fieldKey][index][subFieldKey]"
                                                                :type="subField.type"
                                                                :placeholder="subField.placeholder || (subField.type === 'url' ? 'https://example.com' : '')"
                                                                @blur="subField.type === 'url' ? handleUrlBlur(fieldKey, index, subFieldKey) : null"
                                                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                                            />
                                                        </div>
                                                        <!-- Textarea fields -->
                                                        <div v-else-if="subField.type === 'textarea'">
                                                            <label :for="`${fieldKey}_${index}_${subFieldKey}`" 
                                                                   class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                                                {{ subField.label }}
                                                            </label>
                                                            <textarea 
                                                                :id="`${fieldKey}_${index}_${subFieldKey}`"
                                                                v-model="form[fieldKey][index][subFieldKey]"
                                                                :rows="3"
                                                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                                            />
                                                        </div>
                                                        <!-- Select fields -->
                                                        <div v-else-if="subField.type === 'select'">
                                                            <label :for="`${fieldKey}_${index}_${subFieldKey}`" 
                                                                   class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                                                {{ subField.label }}
                                                            </label>
                                                            <select 
                                                                :id="`${fieldKey}_${index}_${subFieldKey}`"
                                                                v-model="form[fieldKey][index][subFieldKey]"
                                                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                                            >
                                                                <option value="">Select {{ subField.label }}</option>
                                                                <option v-for="(optLabel, optValue) in subField.options" 
                                                                        :key="optValue" 
                                                                        :value="optValue">
                                                                    {{ optLabel }}
                                                                </option>
                                                            </select>
                                                        </div>
                                                        <!-- Icon field (file but stays in left column) -->
                                                        <div v-else-if="subField.type === 'file' && subFieldKey === 'icon'">
                                                            <label :for="`${fieldKey}_${index}_${subFieldKey}`" 
                                                                   class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                                                {{ subField.label }}
                                                            </label>
                                                            
                                                            <!-- Preview container -->
                                                            <div v-if="subField.accept?.includes('image')" 
                                                                 class="mt-2 mb-3 h-20 w-20 flex items-center justify-center bg-gray-50 dark:bg-gray-800 rounded-lg border-2 border-dashed border-gray-300 dark:border-gray-600">
                                                                <img v-if="form[fieldKey][index][subFieldKey] && typeof form[fieldKey][index][subFieldKey] === 'string'" 
                                                                     :src="`/storage/${form[fieldKey][index][subFieldKey]}`" 
                                                                     :alt="subField.label"
                                                                     class="max-w-full h-auto max-h-20 rounded-lg shadow-sm" />
                                                                <img v-else-if="repeaterFilePreviews[fieldKey]?.[index]?.[subFieldKey]" 
                                                                     :src="repeaterFilePreviews[fieldKey][index][subFieldKey]" 
                                                                     :alt="subField.label"
                                                                     class="max-w-full h-auto max-h-20 rounded-lg shadow-sm" />
                                                                <span v-else class="text-gray-400 dark:text-gray-500 text-xs">
                                                                    No icon
                                                                </span>
                                                            </div>
                                                            
                                                            <input 
                                                                :id="`${fieldKey}_${index}_${subFieldKey}`"
                                                                type="file"
                                                                :accept="subField.accept"
                                                                @change="e => handleRepeaterFileChange(e, fieldKey, index, subFieldKey)"
                                                                class="mt-1 block w-full text-sm text-gray-500 dark:text-gray-400
                                                                       file:mr-4 file:py-2 file:px-4
                                                                       file:rounded-full file:border-0
                                                                       file:text-sm file:font-semibold
                                                                       file:bg-indigo-50 file:text-indigo-700
                                                                       hover:file:bg-indigo-100
                                                                       dark:file:bg-gray-700 dark:file:text-gray-300"
                                                            />
                                                        </div>
                                                    </template>
                                                </template>
                                            </div>
                                            
                                            <!-- Right column: Image only -->
                                            <div>
                                                <template v-for="(subField, subFieldKey) in field.fields" :key="subFieldKey">
                                                    <div v-if="subFieldKey === 'image' && subField.type === 'file'">
                                                        <label :for="`${fieldKey}_${index}_${subFieldKey}`" 
                                                               class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                                            {{ subField.label }}
                                                        </label>
                                                        
                                                        <!-- Preview container - always present to maintain layout -->
                                                        <div v-if="subField.accept?.includes('image')" 
                                                             class="mt-2 mb-3 h-64 flex items-center justify-center bg-gray-50 dark:bg-gray-800 rounded-lg border-2 border-dashed border-gray-300 dark:border-gray-600 relative">
                                                            <img v-if="form[fieldKey][index][subFieldKey] && typeof form[fieldKey][index][subFieldKey] === 'string'" 
                                                                 :src="`/storage/${form[fieldKey][index][subFieldKey]}`" 
                                                                 :alt="subField.label"
                                                                 class="max-w-full h-auto max-h-64 rounded-lg shadow-sm" />
                                                            <img v-else-if="repeaterFilePreviews[fieldKey]?.[index]?.[subFieldKey]" 
                                                                 :src="repeaterFilePreviews[fieldKey][index][subFieldKey]" 
                                                                 :alt="subField.label"
                                                                 class="max-w-full h-auto max-h-64 rounded-lg shadow-sm" />
                                                            <span v-else class="text-gray-400 dark:text-gray-500 text-sm">
                                                                No image selected
                                                            </span>
                                                            <!-- Delete button for existing images -->
                                                            <button v-if="(form[fieldKey][index][subFieldKey] && typeof form[fieldKey][index][subFieldKey] === 'string') || repeaterFilePreviews[fieldKey]?.[index]?.[subFieldKey]"
                                                                    @click="deleteRepeaterFile(fieldKey, index, subFieldKey)"
                                                                    type="button"
                                                                    class="absolute top-2 right-2 bg-red-500 hover:bg-red-600 text-white rounded-full p-1.5 shadow-lg"
                                                                    title="Delete image">
                                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                                </svg>
                                                            </button>
                                                        </div>
                                                        
                                                        <input 
                                                            :id="`${fieldKey}_${index}_${subFieldKey}`"
                                                            type="file"
                                                            :accept="subField.accept"
                                                            @change="e => handleRepeaterFileChange(e, fieldKey, index, subFieldKey)"
                                                            class="mt-1 block w-full text-sm text-gray-500 dark:text-gray-400
                                                                   file:mr-4 file:py-2 file:px-4
                                                                   file:rounded-full file:border-0
                                                                   file:text-sm file:font-semibold
                                                                   file:bg-indigo-50 file:text-indigo-700
                                                                   hover:file:bg-indigo-100
                                                                   dark:file:bg-gray-700 dark:file:text-gray-300"
                                                        />
                                                        <p v-if="subField.description" class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                                            {{ subField.description }}
                                                        </p>
                                                        <!-- Image processing notices for repeater files -->
                                                        <div v-if="filesNeedingUpscale[`${fieldKey}_${index}_${subFieldKey}`] || filesNeedingCompression[`${fieldKey}_${index}_${subFieldKey}`]" class="mt-2 space-y-2">
                                                            <!-- Upscale notice -->
                                                            <div v-if="filesNeedingUpscale[`${fieldKey}_${index}_${subFieldKey}`]" class="flex items-start text-sm text-blue-600 dark:text-blue-400">
                                                                <svg class="w-4 h-4 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                                                                </svg>
                                                                <span>
                                                                    This image ({{ filesNeedingUpscale[`${fieldKey}_${index}_${subFieldKey}`].currentWidth }}×{{ filesNeedingUpscale[`${fieldKey}_${index}_${subFieldKey}`].currentHeight }}px) 
                                                                    is smaller than minimum ({{ filesNeedingUpscale[`${fieldKey}_${index}_${subFieldKey}`].minWidth }}×{{ filesNeedingUpscale[`${fieldKey}_${index}_${subFieldKey}`].minHeight }}px) 
                                                                    and will be upscaled.
                                                                </span>
                                                            </div>
                                                            <!-- Compression notice -->
                                                            <div v-if="filesNeedingCompression[`${fieldKey}_${index}_${subFieldKey}`]" class="flex items-start text-sm text-amber-600 dark:text-amber-400">
                                                                <svg class="w-4 h-4 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                                                </svg>
                                                                <span>This image is larger than 2MB and will be automatically compressed during upload.</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </template>
                                            </div>
                                        </div>
                                        
                                        <!-- Remove button -->
                                        <div class="mt-3 flex justify-end">
                                            <button 
                                                v-if="(fieldKey === 'room_types' && index > 0) || (fieldKey !== 'room_types' && form[fieldKey].length > 1)"
                                                @click="removeRepeaterItem(fieldKey, index)"
                                                type="button"
                                                class="text-sm text-red-600 hover:text-red-500 dark:text-red-400 dark:hover:text-red-300"
                                            >
                                                Remove
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <!-- Remove button for policy repeater fields -->
                                    <div v-if="['cancellation_policies', 'special_requests', 'deposit_policies', 'payment_methods', 'transfer_types'].includes(fieldKey)" class="mt-3 flex justify-end">
                                        <button 
                                            v-if="form[fieldKey].length > 1"
                                            @click="removeRepeaterItem(fieldKey, index)"
                                            type="button"
                                            class="text-sm text-red-600 hover:text-red-500 dark:text-red-400 dark:hover:text-red-300"
                                        >
                                            Remove
                                        </button>
                                    </div>
                                    
                                    <!-- Add button -->
                                    <button 
                                        @click="addRepeaterItem(fieldKey)"
                                        type="button"
                                        class="mt-2 inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600"
                                    >
                                        <svg class="mr-1.5 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                        </svg>
                                        {{ field.add_button_label || `Add ${field.label || 'Item'}` }}
                                    </button>
                                </div>
                            </div>

                            <!-- Template Preview Field -->
                            <div v-else-if="field.type === 'template_preview'" class="mt-1">
                                <button
                                    type="button"
                                    @click="handleTemplatePreview({ title: field.label, image: field.template_image })"
                                    class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600"
                                >
                                    <svg class="mr-2 h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                    View Template Example
                                </button>
                            </div>
                        </div>

                        <!-- Form Actions -->
                        <div class="flex items-center justify-between pt-6 border-t border-gray-200 dark:border-gray-700">
                            <Link :href="route('client.dashboard')" 
                                  class="text-sm text-gray-600 hover:text-gray-500 dark:text-gray-400 dark:hover:text-gray-300">
                                Cancel
                            </Link>
                            <button 
                                type="submit"
                                :disabled="form.processing"
                                class="ml-3 inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                {{ form.processing ? 'Saving...' : 'Save Changes' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Example Image Modal -->
        <div v-if="showExampleModal" 
             class="fixed inset-0 z-50 overflow-y-auto"
             @click="showExampleModal = false">
            <div class="flex min-h-screen items-center justify-center p-4">
                <div class="fixed inset-0 bg-black bg-opacity-75 transition-opacity"></div>
                
                <div class="relative bg-white dark:bg-gray-800 rounded-lg max-w-4xl w-full mx-auto shadow-xl"
                     @click.stop>
                    <div class="flex items-center justify-between p-4 border-b dark:border-gray-700">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                            {{ exampleModalTitle }} - Example
                        </h3>
                        <button @click="showExampleModal = false"
                                class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    
                    <div class="p-6">
                        <img :src="`/storage/${exampleModalImage}`"
                             :alt="`${exampleModalTitle} example`"
                             class="w-full h-auto rounded-lg shadow-sm" />
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