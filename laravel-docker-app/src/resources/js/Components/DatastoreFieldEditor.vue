<template>
    <div class="field-editor">
        <div class="field-properties">
            <!-- Standard Field Properties -->
            <div class="property-item">
                <label>Type</label>
                <select :value="field.type" @change="updateProperty('type', $event.target.value)">
                    <option value="text">Text</option>
                    <option value="textarea">Textarea</option>
                    <option value="select">Select</option>
                    <option value="boolean">Boolean</option>
                    <option value="date">Date</option>
                    <option value="image">Image</option>
                    <option value="file">File</option>
                    <option value="number">Number</option>
                    <option value="reference">Reference</option>
                    <option value="subtable">Subtable</option>
                    <option value="ckeditor">CKEditor</option>
                    <!-- Summernote is legacy - show if already selected but don't allow new selection -->
                    <option v-if="field.type === 'summernote'" value="summernote" disabled>Summernote (Legacy)</option>
                </select>
            </div>
            
            <div class="property-item">
                <label>Label</label>
                <input 
                    :value="field.label" 
                    @input="updateProperty('label', $event.target.value)" 
                    type="text" 
                />
            </div>
            
            <div class="property-item">
                <label>Icon</label>
                <input 
                    :value="field.icon" 
                    @input="updateProperty('icon', $event.target.value)" 
                    type="text" 
                />
            </div>
            
            <div class="property-item">
                <label>Placeholder</label>
                <input 
                    :value="field.placeholder" 
                    @input="updateProperty('placeholder', $event.target.value)" 
                    type="text" 
                />
            </div>
            
            <div class="property-item">
                <label>Tooltip</label>
                <input 
                    :value="field.tooltip" 
                    @input="updateProperty('tooltip', $event.target.value)" 
                    type="text" 
                />
            </div>
            
            <!-- Boolean Properties -->
            <div class="boolean-properties">
                <label class="checkbox-property">
                    <input 
                        type="checkbox" 
                        :checked="field.required" 
                        @change="updateProperty('required', $event.target.checked)" 
                    />
                    <span>Required</span>
                </label>
                
                <label class="checkbox-property">
                    <input 
                        type="checkbox" 
                        :checked="field.translatable" 
                        @change="updateProperty('translatable', $event.target.checked)" 
                    />
                    <span>Translatable</span>
                </label>
                
                <label class="checkbox-property">
                    <input 
                        type="checkbox" 
                        :checked="field.showInList" 
                        @change="updateProperty('showInList', $event.target.checked)" 
                    />
                    <span>Show in List</span>
                </label>
            </div>
            
            <!-- Type-specific properties -->
            <template v-if="field.type === 'select'">
                <div class="property-item">
                    <label>Options</label>
                    <div class="options-editor">
                        <div v-for="(label, value) in field.options" :key="value" class="option-item">
                            <input 
                                :value="value" 
                                @blur="updateOptionKey(value, $event.target.value)" 
                                placeholder="Value" 
                            />
                            <input 
                                :value="label" 
                                @input="updateOptionValue(value, $event.target.value)" 
                                placeholder="Label" 
                            />
                            <button @click="removeOption(value)" class="btn-remove">×</button>
                        </div>
                        <button @click="addOption" class="btn-add-option">+ Add Option</button>
                    </div>
                </div>
            </template>
            
            <template v-if="field.type === 'reference'">
                <div class="property-item">
                    <label>Reference Table</label>
                    <select 
                        :value="field.table" 
                        @change="updateProperty('table', $event.target.value)"
                    >
                        <option value="">Select table...</option>
                        <option v-for="(table, tableKey) in availableTables" :key="tableKey" :value="tableKey">
                            {{ tableKey }}
                        </option>
                    </select>
                </div>
                
                <div v-if="field.table" class="property-item">
                    <label>Display Field</label>
                    <select 
                        :value="field.display" 
                        @change="updateProperty('display', $event.target.value)"
                    >
                        <option value="">Select field...</option>
                        <option v-for="(field, fieldKey) in getReferenceTableFields()" :key="fieldKey" :value="fieldKey">
                            {{ fieldKey }}
                        </option>
                    </select>
                </div>
                
                <label class="checkbox-property">
                    <input 
                        type="checkbox" 
                        :checked="field.multiple" 
                        @change="updateProperty('multiple', $event.target.checked)" 
                    />
                    <span>Multiple Selection</span>
                </label>
            </template>
            
            <template v-if="field.type === 'image'">
                <div class="property-item">
                    <label>Image Crops</label>
                    <div class="crops-editor">
                        <div v-for="(crop, cropName) in field.crops" :key="cropName" class="crop-item">
                            <span class="crop-name">{{ cropName }}</span>
                            <span class="crop-size">{{ crop.width }}x{{ crop.height }}</span>
                            <button @click="removeCrop(cropName)" class="btn-remove">×</button>
                        </div>
                        <button @click="$emit('add-crop')" class="btn-add-option">+ Add Crop</button>
                    </div>
                </div>
            </template>
        </div>
    </div>
</template>

<script>
export default {
    name: 'DatastoreFieldEditor',
    props: {
        field: {
            type: Object,
            required: true
        },
        fieldKey: {
            type: String,
            required: true
        },
        tableKey: {
            type: String,
            required: true
        },
        availableTables: {
            type: Object,
            default: () => ({})
        }
    },
    emits: ['update-property', 'add-crop'],
    methods: {
        updateProperty(property, value) {
            this.$emit('update-property', {
                tableKey: this.tableKey,
                fieldKey: this.fieldKey,
                property,
                value
            });
        },
        
        addOption() {
            const options = { ...(this.field.options || {}) };
            const newKey = `option${Object.keys(options).length + 1}`;
            options[newKey] = `Option ${Object.keys(options).length + 1}`;
            this.updateProperty('options', options);
        },
        
        updateOptionKey(oldKey, newKey) {
            if (oldKey === newKey) return;
            const options = { ...(this.field.options || {}) };
            const value = options[oldKey];
            delete options[oldKey];
            options[newKey] = value;
            this.updateProperty('options', options);
        },
        
        updateOptionValue(key, value) {
            const options = { ...(this.field.options || {}) };
            options[key] = value;
            this.updateProperty('options', options);
        },
        
        removeOption(key) {
            const options = { ...(this.field.options || {}) };
            delete options[key];
            this.updateProperty('options', options);
        },
        
        removeCrop(cropName) {
            const crops = { ...(this.field.crops || {}) };
            delete crops[cropName];
            this.updateProperty('crops', crops);
        },
        
        getReferenceTableFields() {
            if (!this.field.table || !this.availableTables[this.field.table]) {
                return {};
            }
            return this.availableTables[this.field.table].fields || {};
        }
    }
};
</script>

<style scoped>
.field-editor {
    padding: 16px;
}

.property-item {
    margin-bottom: 16px;
}

.property-item label {
    display: block;
    margin-bottom: 4px;
    font-size: 14px;
    font-weight: 500;
    color: #374151;
}

.property-item input[type="text"],
.property-item select {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 14px;
}

.boolean-properties {
    display: flex;
    flex-wrap: wrap;
    gap: 16px;
    margin-bottom: 16px;
}

.checkbox-property {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 14px;
    color: #4b5563;
    cursor: pointer;
}

.checkbox-property input[type="checkbox"] {
    cursor: pointer;
}

.options-editor,
.crops-editor {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.option-item,
.crop-item {
    display: flex;
    align-items: center;
    gap: 8px;
}

.option-item input {
    flex: 1;
    padding: 6px 8px;
    border: 1px solid #d1d5db;
    border-radius: 4px;
    font-size: 14px;
}

.crop-item {
    background-color: #f3f4f6;
    padding: 8px 12px;
    border-radius: 4px;
}

.crop-name {
    font-weight: 500;
    margin-right: auto;
}

.crop-size {
    color: #6b7280;
    font-size: 13px;
}

.btn-remove {
    background: none;
    border: none;
    color: #ef4444;
    font-size: 20px;
    cursor: pointer;
    padding: 0 4px;
    line-height: 1;
}

.btn-remove:hover {
    color: #dc2626;
}

.btn-add-option {
    background-color: #f3f4f6;
    border: 1px dashed #d1d5db;
    border-radius: 4px;
    padding: 8px 12px;
    font-size: 14px;
    color: #6b7280;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-add-option:hover {
    background-color: #e5e7eb;
    color: #374151;
}
</style>