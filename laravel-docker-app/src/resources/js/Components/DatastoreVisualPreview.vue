<template>
    <div class="visual-preview">
        <div class="preview-header">
            <h3 class="panel-title">Visual Preview</h3>
            <label class="toggle-label">
                <span>Live Edit</span>
                <div class="toggle-switch">
                    <input type="checkbox" v-model="liveEdit" />
                    <span class="toggle-slider"></span>
                </div>
            </label>
        </div>
        
        <div class="preview-content">
            <template v-for="(table, tableKey) in sortedTables" :key="tableKey">
                <div 
                    v-if="!isTableDisabled(tableKey)" 
                    class="preview-table" 
                    :class="{ 'active-table': selectedTable === tableKey }"
                    :data-table-key="tableKey"
                >
                    <div class="preview-table-header">
                        <span class="preview-table-name">{{ tableKey }}</span>
                        <span v-if="table.label" class="preview-table-label">({{ table.label }})</span>
                    </div>
                    
                    <div class="preview-fields">
                        <div 
                            v-for="(field, fieldKey) in getSortedFields(tableKey)" 
                            :key="fieldKey"
                            class="field-preview"
                            :data-field-key="fieldKey"
                        >
                            <template v-if="!field.disabled">
                                <div class="field-preview-header">
                                    <span class="field-preview-name">{{ fieldKey }}</span>
                                    <div class="field-preview-actions">
                                        <button 
                                            v-if="!isFieldFromDefault(tableKey, fieldKey)" 
                                            @click="$emit('reset-field', tableKey, fieldKey)"
                                            class="btn-icon"
                                            title="Reset field"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                            </svg>
                                        </button>
                                        <span v-if="field.disabled" class="field-badge disabled">Disabled</span>
                                    </div>
                                </div>
                                
                                <div class="field-preview-content">
                                    <label v-if="field.label" class="field-label">
                                        {{ field.label }}
                                        <span v-if="field.required" class="required">*</span>
                                    </label>
                                    
                                    <!-- Field type preview -->
                                    <component 
                                        :is="getFieldComponent(field.type)"
                                        :field="field"
                                        :fieldKey="fieldKey"
                                        disabled
                                    />
                                    
                                    <div class="field-info">
                                        <span class="field-code">Code: {{ fieldKey }}</span>
                                        <span v-if="field.translatable" class="field-badge translatable">Translatable</span>
                                        <span v-if="field.showInList" class="field-badge list">Show in List</span>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>
</template>

<script>
import { computed, h } from 'vue';

export default {
    name: 'DatastoreVisualPreview',
    props: {
        sortedTables: {
            type: Object,
            required: true
        },
        selectedTable: {
            type: String,
            default: ''
        },
        isTableDisabled: {
            type: Function,
            required: true
        },
        getSortedFields: {
            type: Function,
            required: true
        },
        liveEditEnabled: {
            type: Boolean,
            default: false
        },
        defaultStructure: {
            type: Object,
            default: () => ({})
        }
    },
    emits: ['update:liveEditEnabled', 'reset-field'],
    computed: {
        liveEdit: {
            get() {
                return this.liveEditEnabled;
            },
            set(value) {
                this.$emit('update:liveEditEnabled', value);
            }
        }
    },
    methods: {
        isFieldFromDefault(tableKey, fieldKey) {
            return this.defaultStructure?.tables?.[tableKey]?.fields?.[fieldKey] !== undefined;
        },
        
        getFieldComponent(type) {
            // Return a simple render function for each field type
            const fieldComponents = {
                text: () => h('input', { 
                    class: 'form-input', 
                    type: 'text', 
                    disabled: true,
                    placeholder: 'Text input' 
                }),
                
                textarea: () => h('textarea', { 
                    class: 'form-textarea', 
                    rows: 3, 
                    disabled: true,
                    placeholder: 'Textarea input'
                }),
                
                select: (props) => h('div', [
                    h('select', { 
                        class: 'form-select', 
                        disabled: true 
                    }, [
                        h('option', 'Select an option...'),
                        ...Object.entries(props.field.options || {}).map(([value, label]) => 
                            h('option', { value }, `${label} (${value})`)
                        )
                    ]),
                    (!props.field.options || Object.keys(props.field.options).length === 0) && 
                        h('div', { class: 'no-options-hint' }, 'No options defined')
                ]),
                
                boolean: (props) => h('label', { class: 'form-checkbox' }, [
                    h('input', { type: 'checkbox', disabled: true }),
                    h('span', props.field.label || props.fieldKey)
                ]),
                
                date: () => h('input', { 
                    class: 'form-input', 
                    type: 'date', 
                    disabled: true 
                }),
                
                image: () => h('div', { class: 'form-file' }, 
                    h('div', { class: 'file-preview' }, [
                        h('svg', { 
                            class: 'w-12 h-12', 
                            fill: 'none', 
                            stroke: 'currentColor', 
                            viewBox: '0 0 24 24' 
                        }, [
                            h('path', {
                                'stroke-linecap': 'round',
                                'stroke-linejoin': 'round',
                                'stroke-width': '2',
                                d: 'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z'
                            })
                        ]),
                        h('span', 'Click to upload image')
                    ])
                ),
                
                file: () => h('div', { class: 'form-file' }, 
                    h('div', { class: 'file-preview' }, [
                        h('svg', { 
                            class: 'w-12 h-12', 
                            fill: 'none', 
                            stroke: 'currentColor', 
                            viewBox: '0 0 24 24' 
                        }, [
                            h('path', {
                                'stroke-linecap': 'round',
                                'stroke-linejoin': 'round',
                                'stroke-width': '2',
                                d: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'
                            })
                        ]),
                        h('span', 'Click to upload file')
                    ])
                ),
                
                reference: (props) => h('div', [
                    h('select', { 
                        class: 'form-select', 
                        disabled: true 
                    }, [
                        h('option', `Select from ${props.field.table || '[No table selected]'}...`)
                    ]),
                    props.field.table && h('div', { class: 'reference-info' }, [
                        h('span', `References: ${props.field.table}`),
                        props.field.display && h('span', ` (Display: ${props.field.display})`),
                        props.field.multiple && h('span', { class: 'field-badge multiple' }, 'Multiple')
                    ]),
                    !props.field.table && h('div', { class: 'no-options-hint' }, 'No reference table selected')
                ]),
                
                ckeditor: () => h('div', { class: 'form-ckeditor' }, [
                    h('div', { class: 'ckeditor-toolbar' }, 
                        'B I U | H1 H2 | • List | Link Image'
                    ),
                    h('div', { 
                        class: 'ckeditor-content', 
                        contenteditable: false 
                    }, 'Rich text editor content...')
                ]),
                
                summernote: () => h('div', { class: 'form-ckeditor' }, [
                    h('div', { class: 'ckeditor-toolbar' }, 
                        'B I U | H1 H2 | • List | Link Image'
                    ),
                    h('div', { 
                        class: 'ckeditor-content', 
                        contenteditable: false 
                    }, 'Rich text editor content...')
                ]),
                
                subtable: (props) => h('div', { class: 'form-subtable' }, [
                    h('div', { class: 'subtable-header' }, [
                        h('span', `${props.field.label || props.fieldKey} (0 items)`),
                        h('button', { 
                            class: 'btn-add-subtable', 
                            disabled: true 
                        }, '+ Add')
                    ]),
                    h('div', { class: 'subtable-preview' }, 
                        h('p', 'No items added yet')
                    )
                ])
            };
            
            return fieldComponents[type] || (() => h('div', { class: 'form-unknown' }, `${type} field`));
        }
    }
};
</script>

<style scoped>
.form-input,
.form-select,
.form-textarea {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 14px;
    background-color: #f9fafb;
}

.form-textarea {
    resize: vertical;
}

.form-checkbox {
    display: flex;
    align-items: center;
    gap: 8px;
}

.form-file {
    border: 2px dashed #d1d5db;
    border-radius: 6px;
    padding: 24px;
    text-align: center;
}

.file-preview {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    color: #6b7280;
}

.form-ckeditor {
    border: 1px solid #d1d5db;
    border-radius: 6px;
    overflow: hidden;
}

.ckeditor-toolbar {
    background-color: #f3f4f6;
    padding: 8px 12px;
    border-bottom: 1px solid #d1d5db;
    font-size: 13px;
    color: #6b7280;
}

.ckeditor-content {
    padding: 12px;
    min-height: 100px;
    background-color: #f9fafb;
}

.form-subtable {
    border: 1px solid #d1d5db;
    border-radius: 6px;
    overflow: hidden;
}

.subtable-header {
    background-color: #f3f4f6;
    padding: 8px 12px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.btn-add-subtable {
    background-color: #3b82f6;
    color: white;
    border: none;
    padding: 4px 12px;
    border-radius: 4px;
    font-size: 13px;
    cursor: not-allowed;
    opacity: 0.5;
}

.subtable-preview {
    padding: 16px;
    text-align: center;
    color: #9ca3af;
}

.reference-info {
    margin-top: 4px;
    font-size: 13px;
    color: #6b7280;
}

.no-options-hint {
    margin-top: 4px;
    font-size: 13px;
    color: #9ca3af;
    font-style: italic;
}

.field-label {
    display: block;
    margin-bottom: 4px;
    font-size: 14px;
    font-weight: 500;
    color: #374151;
}

.required {
    color: #ef4444;
}

.field-badge {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 500;
}

.field-badge.translatable {
    background-color: #dbeafe;
    color: #1e40af;
}

.field-badge.list {
    background-color: #d1fae5;
    color: #065f46;
}

.field-badge.multiple {
    background-color: #fef3c7;
    color: #92400e;
}

.field-badge.disabled {
    background-color: #f3f4f6;
    color: #6b7280;
}

.toggle-label {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 14px;
    color: #374151;
}

.toggle-switch {
    position: relative;
    display: inline-block;
    width: 44px;
    height: 24px;
}

.toggle-switch input {
    opacity: 0;
    width: 0;
    height: 0;
}

.toggle-slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #cbd5e1;
    transition: .4s;
    border-radius: 24px;
}

.toggle-slider:before {
    position: absolute;
    content: "";
    height: 16px;
    width: 16px;
    left: 4px;
    bottom: 4px;
    background-color: white;
    transition: .4s;
    border-radius: 50%;
}

.toggle-switch input:checked + .toggle-slider {
    background-color: #3b82f6;
}

.toggle-switch input:checked + .toggle-slider:before {
    transform: translateX(20px);
}
</style>