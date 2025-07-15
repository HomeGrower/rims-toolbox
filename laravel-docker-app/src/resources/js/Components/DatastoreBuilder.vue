<template>
    <div class="datastore-builder">
        <div class="builder-layout">
            <!-- Left Panel: Table Editor -->
            <div class="left-panel">
                <div class="panel-header">
                    <h3 class="panel-title">Tables</h3>
                    <button @click="showTableSettings = !showTableSettings" class="btn-icon settings">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                    </button>
                </div>
                
                <div class="table-controls">
                    <div class="search-box">
                        <svg class="search-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                        <input 
                            v-model="tableSearchQuery" 
                            type="text" 
                            placeholder="Search tables..."
                            class="search-input"
                        />
                    </div>
                    <label class="checkbox-label">
                        <input 
                            v-model="showOnlyActiveTables" 
                            type="checkbox"
                        />
                        <span>Show only active tables</span>
                    </label>
                </div>
                
                <div class="tables-list">
                    <div v-if="Object.keys(filteredTables).length === 0" class="no-tables">
                        <p v-if="tableSearchQuery || showOnlyActiveTables">No tables match your search criteria.</p>
                        <p v-else>No tables available. Please check the default structure configuration.</p>
                    </div>
                    <div v-for="(table, tableKey) in filteredTables" :key="tableKey" class="table-item" :class="{ 'disabled': isTableDisabled(tableKey), 'active': selectedTable === tableKey, 'custom': isCustomTable(tableKey), 'modified': isTableModified(tableKey) }" :id="'table-' + tableKey" ref="tableItems">
                        <div class="table-header" @click="selectTable(tableKey)">
                            <span v-if="isTableModified(tableKey)" class="table-modified-indicator" title="Table has modifications"></span>
                            <span class="table-name">{{ tableKey }}</span>
                            <div v-if="isCustomTable(tableKey)" class="table-actions">
                                <button 
                                    @click.stop="startEditingTableName(tableKey)" 
                                    class="btn-edit-table"
                                    title="Edit table name"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                </button>
                                <button 
                                    @click.stop="confirmDeleteTable(tableKey)" 
                                    class="btn-delete-table"
                                    title="Delete table"
                                >
                                    ×
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <button @click="showAddTableModal = true" class="btn-add-table">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                        Add New Table
                    </button>
                </div>
            </div>

            <!-- Middle Panel: Editor -->
            <div class="middle-panel">
                <div v-if="selectedTable" class="editor-content">
                    <div class="editor-header">
                        <div class="editor-title-wrapper">
                            <h3 v-if="!editingSelectedTableName" class="editor-title">{{ selectedTable }}</h3>
                            <input 
                                v-else
                                v-model="selectedTableNewName" 
                                @keyup.enter="saveSelectedTableName"
                                @blur="saveSelectedTableName"
                                class="editor-title-input"
                                ref="tableNameInput"
                            />
                            <button 
                                v-if="isCustomTable(selectedTable) && !editingSelectedTableName" 
                                @click="startEditingSelectedTableName"
                                class="btn-edit-title"
                                title="Edit table name"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Table Properties -->
                    <div class="section">
                        <div class="section-header">
                            <h4 class="section-title">Table Properties</h4>
                            <button 
                                v-if="!isCustomTable(selectedTable)"
                                @click="resetTable(selectedTable)"
                                class="btn-reset-table"
                                title="Reset table to default settings"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                </svg>
                            </button>
                        </div>
                        
                        <!-- Required Table Properties -->
                        <div class="required-properties">
                            <div class="property-item">
                                <label>Label 
                                </label>
                                <input :value="getTableProperty(selectedTable, 'label')" @input="updateTableProperty(selectedTable, 'label', $event.target.value)" type="text" />
                            </div>
                            <div class="property-item">
                                <label>Icon 
                                </label>
                                <input :value="getTableProperty(selectedTable, 'icon')" @input="updateTableProperty(selectedTable, 'icon', $event.target.value)" type="text" />
                            </div>
                            <div class="property-item" data-property="description">
                                <label>Description 
                                </label>
                                <textarea 
                                    :value="getTableProperty(selectedTable, 'description')" 
                                    @input="updateTableProperty(selectedTable, 'description', $event.target.value)"
                                    rows="3"
                                    class="property-textarea"
                                ></textarea>
                            </div>
                        </div>
                        
                        <!-- Boolean Properties as Checkboxes -->
                        <div class="boolean-properties-inline">
                            <div class="boolean-properties-left">
                                <label 
                                    v-if="shouldShowBooleanProperty(selectedTable, 'singleton') || isCustomTable(selectedTable)"
                                    class="checkbox-property-inline"
                                    :title="getBooleanPropertyTooltip('singleton')"
                                >
                                    <input type="checkbox" :checked="getTableProperty(selectedTable, 'singleton')" @change="updateSingletonProperty(selectedTable, $event.target.checked)" />
                                    <span>Singleton</span>
                                </label>
                            </div>
                            <div class="add-property-dropdown">
                                <button @click="showTablePropertyDropdown = !showTablePropertyDropdown" class="btn-add-icon">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                    </svg>
                                </button>
                                <div v-if="showTablePropertyDropdown" class="dropdown-menu">
                                    <button 
                                        v-for="prop in availableNonBooleanTableProperties" 
                                        :key="prop.key" 
                                        @click="addTableProperty(selectedTable, prop.key); showTablePropertyDropdown = false" 
                                        class="dropdown-item"
                                        :class="{ 'disabled': prop.key === 'order' && getTableProperty(selectedTable, 'singleton') }"
                                        :disabled="prop.key === 'order' && getTableProperty(selectedTable, 'singleton')"
                                    >
                                        {{ prop.label }}
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <div class="property-grid">
                            <!-- All Non-Boolean Properties -->
                            <template v-for="prop in allAddedNonBooleanProperties" :key="prop.key">
                                <div class="property-item" :data-property="prop.key">
                                    <label :title="getPropertyTooltip(prop.key)">{{ prop.label }}</label>
                                    <textarea 
                                        v-if="prop.key === 'description'" 
                                        :value="getTableProperty(selectedTable, prop.key)" 
                                        @input="updateTableProperty(selectedTable, prop.key, $event.target.value)"
                                        rows="3"
                                        class="property-textarea"
                                    ></textarea>
                                    <div v-else-if="prop.key === 'order'" class="order-fields" :class="{ 'disabled': getTableProperty(selectedTable, 'singleton') }">
                                        <div v-for="(orderField, index) in getTableOrderFields(selectedTable)" :key="index" class="order-field-item">
                                            <select 
                                                :value="orderField" 
                                                @change="updateOrderField(selectedTable, index, $event.target.value)"
                                                title="Select field for sorting records in list views"
                                                :disabled="getTableProperty(selectedTable, 'singleton')"
                                            >
                                                <option value="">Select field for sorting...</option>
                                                <option v-for="(field, fieldKey) in getTableFields(selectedTable)" :key="fieldKey" :value="fieldKey">
                                                    {{ fieldKey }}
                                                </option>
                                            </select>
                                            <button 
                                                v-if="getTableOrderFields(selectedTable).length > 1"
                                                @click="removeOrderField(selectedTable, index)" 
                                                class="btn-remove"
                                                :disabled="getTableProperty(selectedTable, 'singleton')"
                                            >
                                                ×
                                            </button>
                                        </div>
                                        <button 
                                            v-if="!getTableProperty(selectedTable, 'singleton')"
                                            @click="addOrderField(selectedTable)" 
                                            class="btn-add-option"
                                        >
                                            + Add Sort Field
                                        </button>
                                    </div>
                                    <input 
                                        v-else
                                        :value="getTableProperty(selectedTable, prop.key)" 
                                        @input="updateTableProperty(selectedTable, prop.key, $event.target.value)" 
                                        type="text" 
                                    />
                                    <button @click="removeTableProperty(selectedTable, prop.key)" class="btn-remove">×</button>
                                </div>
                            </template>
                        </div>
                    </div>
                    
                    <!-- Fields -->
                    <div class="section">
                        <h4 class="section-title">Fields</h4>
                        
                        <div class="fields-list">
                            <div v-for="(field, fieldKey) in getSortedFields(selectedTable)" :key="fieldKey" class="field-item" :class="{ 'disabled': field.disabled, 'modified': isFieldModified(selectedTable, fieldKey) }">
                                <div class="field-header" @click="scrollToField(fieldKey)">
                                    <div class="field-name-wrapper">
                                        <span v-if="!editingFieldName[fieldKey]" class="field-name">{{ fieldKey }}</span>
                                        <input 
                                            v-else
                                            v-model="newFieldNames[fieldKey]" 
                                            @keyup.enter="saveFieldName(fieldKey)"
                                            @blur="saveFieldName(fieldKey)"
                                            class="field-name-input"
                                        />
                                        <button 
                                            v-if="!editingFieldName[fieldKey] && isCustomField(selectedTable, fieldKey) && fieldKey !== 'overrides'" 
                                            @click="startEditingFieldName(fieldKey)"
                                            class="btn-edit-field-name"
                                            title="Edit field name"
                                        >
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                            </svg>
                                        </button>
                                    </div>
                                    <div class="field-actions">
                                        <button 
                                            v-if="!isCustomField(selectedTable, fieldKey)"
                                            @click="resetField(selectedTable, fieldKey)"
                                            class="btn-reset-field"
                                            title="Reset field to default settings"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                            </svg>
                                        </button>
                                        <div v-if="!isCustomField(selectedTable, fieldKey)" class="field-toggle">
                                            <label class="toggle-switch">
                                                <input type="checkbox" :checked="!field.disabled" @change="toggleField(selectedTable, fieldKey)">
                                                <span class="toggle-slider"></span>
                                            </label>
                                        </div>
                                        <button 
                                            v-if="isCustomField(selectedTable, fieldKey)" 
                                            @click="confirmDeleteField(selectedTable, fieldKey)"
                                            class="btn-delete-field"
                                            title="Delete field"
                                        >
                                            ×
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="field-properties">
                                    <!-- Special handling for overrides field -->
                                    <div v-if="fieldKey === 'overrides'">
                                        <!-- For overrides field, we force type to be subtable but don't show other properties -->
                                        <span style="display: none;">{{ ensureOverridesFieldType(selectedTable, fieldKey) }}</span>
                                    </div>
                                    <!-- Standard Field Properties for all other fields -->
                                    <div v-else>
                                        <div class="property-item">
                                            <label>Type 
                                            </label>
                                            <select :value="field.type" @change="updateFieldProperty(selectedTable, fieldKey, 'type', $event.target.value)">
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
                                            </select>
                                        </div>
                                        
                                        <div class="property-item">
                                            <label>Label 
                                            </label>
                                            <input :value="field.label" @input="updateFieldProperty(selectedTable, fieldKey, 'label', $event.target.value)" type="text" />
                                        </div>
                                        
                                        <div class="property-item" data-property="description">
                                            <label>Description 
                                            </label>
                                            <textarea 
                                                :value="field.description" 
                                                @input="updateFieldProperty(selectedTable, fieldKey, 'description', $event.target.value)"
                                                rows="3"
                                                class="property-textarea"
                                            ></textarea>
                                            <div></div> <!-- Empty space instead of remove button -->
                                        </div>
                                        
                                        <div class="property-switches">
                                            <label title="Make this field required for form submission">
                                                <input type="checkbox" :checked="field.required" @change="updateFieldProperty(selectedTable, fieldKey, 'required', $event.target.checked)" />
                                                Required
                                            </label>
                                            <label v-if="shouldShowFieldBooleanProperty(selectedTable, fieldKey, 'translate')" title="Make this field translatable in multiple languages">
                                                <input type="checkbox" :checked="field.translate" @change="updateFieldProperty(selectedTable, fieldKey, 'translate', $event.target.checked)" />
                                                Translate
                                            </label>
                                            <label v-if="shouldShowFieldBooleanProperty(selectedTable, fieldKey, 'showInList')" title="Show this field in list/table views">
                                                <input type="checkbox" :checked="field.showInList" @change="updateFieldProperty(selectedTable, fieldKey, 'showInList', $event.target.checked)" />
                                                Show in List
                                            </label>
                                        </div>
                                    </div>
                                    
                                    <!-- Type-specific Properties -->
                                    <div v-if="field.type === 'select'" class="type-specific">
                                        <label>Options</label>
                                        <div v-for="(optionLabel, optionKey) in field.options" :key="optionKey" class="option-item">
                                            <input :value="optionKey" @input="updateOption(selectedTable, fieldKey, optionKey, $event.target.value, optionLabel)" placeholder="Key" />
                                            <input :value="optionLabel" @input="updateOption(selectedTable, fieldKey, optionKey, optionKey, $event.target.value)" placeholder="Label" />
                                            <button @click="removeOption(selectedTable, fieldKey, optionKey)" class="btn-remove">×</button>
                                        </div>
                                        <button @click="addOption(selectedTable, fieldKey)" class="btn-add-option">+ Add Option</button>
                                    </div>
                                    
                                    <div v-if="field.type === 'reference'" class="type-specific">
                                        <div class="property-item">
                                            <label>Table 
                                            </label>
                                            <select :value="field.table" @change="updateFieldProperty(selectedTable, fieldKey, 'table', $event.target.value)">
                                                <option value="">Select table...</option>
                                                <option v-for="(table, key) in getAvailableReferenceTables()" :key="key" :value="key">{{ key }}</option>
                                            </select>
                                        </div>
                                        <div class="property-item">
                                            <label>Display Field 
                                            </label>
                                            <input :value="field.display" @input="updateFieldProperty(selectedTable, fieldKey, 'display', $event.target.value)" type="text" />
                                        </div>
                                        <div class="property-switches">
                                            <label title="Allow multiple referenced records to be selected">
                                                <input type="checkbox" :checked="field.multiple" @change="updateFieldProperty(selectedTable, fieldKey, 'multiple', $event.target.checked)" />
                                                Multiple 
                                            </label>
                                        </div>
                                    </div>
                                    
                                    <div v-if="field.type === 'image'" class="type-specific">
                                        <label>Image Crops</label>
                                        <div v-for="(crop, cropKey) in field.crops" :key="cropKey" class="crop-item">
                                            <input :value="cropKey" placeholder="Crop Name" readonly />
                                            <input :value="crop.width" @input="updateCrop(selectedTable, fieldKey, cropKey, 'width', $event.target.value)" type="number" placeholder="Width" />
                                            <input :value="crop.height" @input="updateCrop(selectedTable, fieldKey, cropKey, 'height', $event.target.value)" type="number" placeholder="Height" />
                                            <button @click="removeCrop(selectedTable, fieldKey, cropKey)" class="btn-remove">×</button>
                                        </div>
                                        <button @click="showAddCropModal(selectedTable, fieldKey)" class="btn-add-option">+ Add Crop</button>
                                    </div>
                                    
                                    <!-- Special handling for fields with subtable fields (override fields) -->
                                    <div v-if="fieldKey === 'overrides' || (field.fields && Object.keys(field.fields).length > 0)" class="type-specific">
                                        <div class="override-preview-toggle">
                                            <button @click="toggleOverridePreview(fieldKey)" class="btn-toggle-preview">
                                                <svg :class="{ 'rotate-90': expandedOverrides[fieldKey] }" class="w-4 h-4 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                                </svg>
                                                <span>Override Fields ({{ Object.keys(field.fields || {}).length }})</span>
                                            </button>
                                        </div>
                                        
                                        <div v-if="expandedOverrides[fieldKey]" class="override-fields-expanded">
                                            <!-- Condition Fields Section -->
                                            <div v-if="Object.keys(getConditionFields(field.fields)).length > 0" class="override-section">
                                                <h5 v-if="fieldKey !== 'overrides'" class="override-section-title">Condition Fields</h5>
                                                <div class="subtable-fields-list">
                                                    <div v-for="(subfield, subfieldKey) in getConditionFields(field.fields)" :key="subfieldKey" class="subtable-field-item condition-field-new" :class="{ 'disabled': subfield.disabled }">
                                                        <div class="field-row">
                                                            <div class="field-col">
                                                                <label class="field-label">{{ subfieldKey }}</label>
                                                                <div class="field-type-display">
                                                                    {{ getConditionFieldType(subfieldKey) }}
                                                                </div>
                                                            </div>
                                                            <div class="field-col">
                                                                <label class="field-label">Label</label>
                                                                <input 
                                                                    :value="subfield.label" 
                                                                    @input="updateSubtableFieldProperty(selectedTable, fieldKey, subfieldKey, 'label', $event.target.value)" 
                                                                    type="text" 
                                                                    class="field-input"
                                                                    placeholder="Field label"
                                                                />
                                                            </div>
                                                            <div v-if="['periodes', 'seasons', 'buildings'].includes(subfieldKey)" class="field-col">
                                                                <div class="field-checkboxes">
                                                                    <label class="checkbox-label">
                                                                        <input 
                                                                            type="checkbox" 
                                                                            :checked="subfield.multiple"
                                                                            @change="updateSubtableFieldProperty(selectedTable, fieldKey, subfieldKey, 'multiple', $event.target.checked)"
                                                                        />
                                                                        Multiple
                                                                    </label>
                                                                    <label class="checkbox-label">
                                                                        <input 
                                                                            type="checkbox" 
                                                                            :checked="subfield.empty"
                                                                            @change="updateSubtableFieldProperty(selectedTable, fieldKey, subfieldKey, 'empty', $event.target.checked)"
                                                                        />
                                                                        Empty
                                                                    </label>
                                                                </div>
                                                            </div>
                                                            <div class="field-col-action">
                                                                <label class="toggle-switch" :title="!isFieldInDefaultStructure(selectedTable, fieldKey) ? 'Remove field' : (isConditionFieldInDefaultStructure(subfieldKey) ? 'Disable/Enable field' : 'Remove field')">
                                                                    <input 
                                                                        type="checkbox" 
                                                                        :checked="!subfield.disabled"
                                                                        @change="toggleConditionField(selectedTable, fieldKey, subfieldKey)"
                                                                    >
                                                                    <span class="toggle-slider"></span>
                                                                </label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <button 
                                                    v-if="getConditionFields(field.fields) && Object.keys(getConditionFields(field.fields)).length < 5"
                                                    @click="showAddConditionFieldModal(selectedTable, fieldKey)" 
                                                    class="btn-add-option"
                                                    style="margin-top: 1rem;"
                                                >
                                                    + Add Condition Field
                                                </button>
                                            </div>
                                            
                                            <!-- Override Fields Section -->
                                            <div v-if="Object.keys(getOverrideFields(field.fields)).length > 0" class="override-section">
                                                <h5 v-if="fieldKey !== 'overrides'" class="override-section-title">Override Fields</h5>
                                                <div class="subtable-fields-list">
                                                    <div v-for="(subfield, subfieldKey) in getOverrideFields(field.fields)" :key="subfieldKey" class="subtable-field-item override-field-new" :class="{ 'disabled': getTableFields(selectedTable)[subfieldKey]?.disabled }">
                                                        <div class="field-row">
                                                            <div class="field-full">
                                                                <span class="field-name">{{ subfieldKey }}</span>
                                                                <span class="field-type-badge">{{ getTableFields(selectedTable)[subfieldKey]?.type || 'unknown' }}</span>
                                                            </div>
                                                            <div class="field-col-action">
                                                                <label class="toggle-switch" :title="isFieldInDefaultStructure(selectedTable, subfieldKey) ? 'Disable/Enable field' : 'Remove field'">
                                                                    <input 
                                                                        type="checkbox" 
                                                                        :checked="true"
                                                                        @change="toggleOverrideField(selectedTable, fieldKey, subfieldKey)"
                                                                    >
                                                                    <span class="toggle-slider"></span>
                                                                </label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <button 
                                                    @click="showAddOverrideFieldModal(selectedTable, fieldKey)" 
                                                    class="btn-add-option"
                                                    style="margin-top: 1rem;"
                                                >
                                                    + Add Field Override
                                                </button>
                                            </div>
                                            
                                            <!-- Add buttons if no fields yet -->
                                            <div v-if="Object.keys(field.fields).length === 0" class="override-empty">
                                                <p>No override fields configured yet</p>
                                                <div class="override-buttons">
                                                    <button 
                                                        @click="showAddConditionFieldModal(selectedTable, fieldKey)" 
                                                        class="btn-add-option"
                                                    >
                                                        + Add Condition Field
                                                    </button>
                                                    <button 
                                                        @click="showAddOverrideFieldModal(selectedTable, fieldKey)" 
                                                        class="btn-add-option"
                                                    >
                                                        + Add Field Override
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Insert After Property -->
                                    <div class="property-item">
                                        <label>Insert After 
                                            <span class="property-tooltip" title="Field after which this field should be displayed">
                                                ?
                                            </span>
                                        </label>
                                        <select 
                                            :value="field._insert_after || ''" 
                                            @change="updateFieldProperty(selectedTable, fieldKey, '_insert_after', $event.target.value)"
                                        >
                                            <option value="">-- At beginning --</option>
                                            <template v-for="(otherField, otherFieldKey) in getTableFields(selectedTable)" :key="otherFieldKey">
                                                <option 
                                                    v-if="otherFieldKey !== fieldKey && !otherField.disabled"
                                                    :value="otherFieldKey"
                                                >
                                                    {{ otherFieldKey }}
                                                </option>
                                            </template>
                                        </select>
                                    </div>
                                    
                                    <!-- Optional Field Properties dropdown removed as requested -->
                                    
                                    <!-- Dynamic Properties -->
                                    <!-- Description removed as it's now a required field above -->
                                </div>
                            </div>
                        </div>
                        
                        <div style="display: flex; gap: 0.5rem; margin-top: 1rem;">
                            <button @click="showAddFieldModal = true" class="btn-add-field">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
                                Add Field
                            </button>
                            <button 
                                v-if="!getTableFields(selectedTable).overrides" 
                                @click="addOverrideField" 
                                class="btn-add-field"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
                                Add Override
                            </button>
                        </div>
                    </div>
                </div>
                
                <div v-else class="no-selection">
                    Select a table from the left to edit its properties
                </div>
            </div>

            <!-- Right Panel: Preview -->
            <div class="right-panel">
                <div class="panel-header">
                    <h3 class="panel-title">Preview</h3>
                    <label v-if="activeJsonTab === 'visual'" class="live-edit-toggle" title="Synchronize preview with editor">
                        <input 
                            type="checkbox" 
                            v-model="liveEditEnabled"
                        />
                        <span>Live Edit</span>
                    </label>
                    <div class="json-tabs">
                        <button 
                            @click="activeJsonTab = 'visual'" 
                            class="tab-button"
                            :class="{ active: activeJsonTab === 'visual' }"
                        >
                            Visual
                        </button>
                        <button 
                            @click="activeJsonTab = 'table'" 
                            class="tab-button"
                            :class="{ active: activeJsonTab === 'table' }"
                        >
                            table diff
                        </button>
                        <button 
                            @click="activeJsonTab = 'diff'" 
                            class="tab-button"
                            :class="{ active: activeJsonTab === 'diff' }"
                        >
                            Full Diff
                        </button>
                    </div>
                </div>
                
                <div class="json-content">
                    <!-- Visual Representation Tab -->
                    <div v-if="activeJsonTab === 'visual'" class="json-tab-content">
                        <div class="visual-preview">
                            <div class="preview-container">
                                <!-- Table Menu -->
                                <div class="table-menu">
                                    <h4 class="menu-title">Tables</h4>
                                    <div class="table-menu-items">
                                        <button 
                                            v-for="(table, tableKey) in sortedCurrentTables" 
                                            :key="tableKey"
                                            @click="visualSelectedTable = tableKey"
                                            class="table-menu-item"
                                            :data-table="tableKey"
                                            :class="{ 
                                                'active': visualSelectedTable === tableKey,
                                                'disabled': isTableDisabled(tableKey),
                                                'singleton': table.singleton,
                                                'editor-active': selectedTable === tableKey
                                            }"
                                        >
                                            <span class="menu-icon">
                                                <svg v-if="table.singleton" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                                </svg>
                                                <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                                                </svg>
                                            </span>
                                            <span class="menu-label">{{ table.label || tableKey }}</span>
                                            <span v-if="table.singleton" class="menu-badge">S</span>
                                        </button>
                                    </div>
                                </div>
                                
                                <!-- Form Preview -->
                                <div class="form-preview">
                                    <div v-if="visualSelectedTable && currentTables[visualSelectedTable]" class="preview-content">
                                        <h3 class="preview-title">{{ currentTables[visualSelectedTable].label || visualSelectedTable }}</h3>
                                        <p v-if="currentTables[visualSelectedTable].description" class="preview-description">
                                            {{ currentTables[visualSelectedTable].description }}
                                        </p>
                                        
                                        <div class="preview-form">
                                            <div 
                                                v-for="(field, fieldKey) in getSortedFields(visualSelectedTable)" 
                                                :key="fieldKey"
                                                v-show="!field.disabled"
                                                class="form-field"
                                                :data-field="fieldKey"
                                            >
                                                <label class="form-label">
                                                    {{ field.label || fieldKey }}
                                                    <span v-if="field.required" class="required-star">*</span>
                                                </label>
                                                
                                                <div v-if="field.description" class="field-description">
                                                    {{ field.description }}
                                                </div>
                                                
                                                <!-- Text Input -->
                                                <input 
                                                    v-if="field.type === 'text' || field.type === 'email' || field.type === 'url'"
                                                    :type="field.type"
                                                    :placeholder="`Enter ${field.label || fieldKey}...`"
                                                    class="form-input"
                                                    disabled
                                                />
                                                
                                                <!-- Number Input -->
                                                <input 
                                                    v-else-if="field.type === 'number'"
                                                    type="number"
                                                    placeholder="0"
                                                    class="form-input"
                                                    disabled
                                                />
                                                
                                                <!-- Textarea -->
                                                <textarea 
                                                    v-else-if="field.type === 'textarea'"
                                                    :placeholder="`Enter ${field.label || fieldKey}...`"
                                                    class="form-textarea"
                                                    rows="3"
                                                    disabled
                                                ></textarea>
                                                
                                                <!-- CKEditor -->
                                                <div v-else-if="field.type === 'ckeditor'" class="form-ckeditor">
                                                    <div class="ckeditor-toolbar">
                                                        <span>B</span> <span>I</span> <span>U</span> | 
                                                        <span>H1</span> <span>H2</span> | 
                                                        <span>• List</span> | 
                                                        <span>Link</span> <span>Image</span>
                                                    </div>
                                                    <div class="ckeditor-content" contenteditable="false">
                                                        Rich text editor content...
                                                    </div>
                                                </div>
                                                
                                                <!-- Select -->
                                                <div v-else-if="field.type === 'select'">
                                                    <select 
                                                        class="form-select"
                                                        disabled
                                                    >
                                                        <option>Select {{ field.label || fieldKey }}...</option>
                                                        <option v-for="(label, value) in (field.options || {})" :key="value">
                                                            {{ label }} ({{ value }})
                                                        </option>
                                                    </select>
                                                    <div v-if="!field.options || Object.keys(field.options).length === 0" class="no-options-hint">
                                                        <span>No options defined</span>
                                                    </div>
                                                </div>
                                                
                                                <!-- Boolean/Checkbox -->
                                                <label v-else-if="field.type === 'boolean'" class="form-checkbox">
                                                    <input type="checkbox" disabled />
                                                    <span>{{ field.label || fieldKey }}</span>
                                                </label>
                                                
                                                <!-- Date -->
                                                <input 
                                                    v-else-if="field.type === 'date'"
                                                    type="date"
                                                    class="form-input"
                                                    disabled
                                                />
                                                
                                                <!-- Image -->
                                                <div v-else-if="field.type === 'image'" class="form-file">
                                                    <div class="file-preview">
                                                        <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                                        </svg>
                                                        <span>Click to upload image</span>
                                                    </div>
                                                </div>
                                                
                                                <!-- File -->
                                                <div v-else-if="field.type === 'file'" class="form-file">
                                                    <div class="file-preview">
                                                        <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                                        </svg>
                                                        <span>Click to upload file</span>
                                                    </div>
                                                </div>
                                                
                                                <!-- Reference -->
                                                <div v-else-if="field.type === 'reference'">
                                                    <select 
                                                        class="form-select"
                                                        disabled
                                                    >
                                                        <option>Select from {{ field.table || '[No table selected]' }}...</option>
                                                    </select>
                                                    <div v-if="field.table" class="reference-info">
                                                        <span>References: {{ field.table }}</span>
                                                        <span v-if="field.display"> (Display: {{ field.display }})</span>
                                                        <span v-if="field.multiple" class="field-badge multiple">Multiple</span>
                                                    </div>
                                                    <div v-else class="no-options-hint">
                                                        <span>No reference table selected</span>
                                                    </div>
                                                </div>
                                                
                                                <!-- Subtable -->
                                                <div v-else-if="field.type === 'subtable'" class="form-subtable">
                                                    <div class="subtable-header">
                                                        <span>{{ field.label || fieldKey }} (0 items)</span>
                                                        <button class="btn-add-subtable" disabled>+ Add</button>
                                                    </div>
                                                    <div class="subtable-preview">
                                                        <p>No items added yet</p>
                                                    </div>
                                                </div>
                                                
                                                <!-- Override Fields Preview -->
                                                <div v-else-if="fieldKey === 'overrides' || (field.fields && Object.keys(field.fields).length > 0)" class="overrides-preview">
                                                    <div v-if="Object.keys(getConditionFields(field.fields || {})).length > 0" class="override-preview-section">
                                                        <h6 class="override-preview-title">Conditions</h6>
                                                        <div class="override-preview-fields compact">
                                                            <div v-for="(subfield, subfieldKey) in getConditionFields(field.fields || {})" :key="subfieldKey" class="override-preview-field compact">
                                                                <span class="field-name">{{ subfieldKey }}</span>
                                                                <span class="field-type">({{ subfield.type }})</span>
                                                                <!-- Show select field items -->
                                                                <div v-if="subfield.type === 'select' && subfield.options && Object.keys(subfield.options).length > 0" class="preview-select-items">
                                                                    <span v-for="(label, value) in subfield.options" :key="value" class="preview-select-item">
                                                                        {{ value }}
                                                                    </span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    
                                                    <div v-if="Object.keys(getOverrideFields(field.fields || {})).length > 0" class="override-preview-section">
                                                        <h6 class="override-preview-title">Overrides</h6>
                                                        <div class="override-preview-fields compact">
                                                            <div v-for="(subfield, subfieldKey) in getOverrideFields(field.fields || {})" :key="subfieldKey" class="override-preview-field compact">
                                                                <span class="field-name">{{ subfieldKey }}</span>
                                                                <span class="field-type">({{ subfield.type || getTableFields(visualSelectedTable)[subfieldKey]?.type || 'unknown' }})</span>
                                                                <!-- Show select field items from override or original field -->
                                                                <div v-if="(subfield.type === 'select' || getTableFields(visualSelectedTable)[subfieldKey]?.type === 'select')" class="preview-select-items">
                                                                    <span 
                                                                        v-for="(label, value) in (subfield.options || getTableFields(visualSelectedTable)[subfieldKey]?.options || {})" 
                                                                        :key="value" 
                                                                        class="preview-select-item"
                                                                    >
                                                                        {{ value }}
                                                                    </span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    
                                                    <div v-if="!field.fields || Object.keys(field.fields).length === 0" class="override-preview-empty">
                                                        <p>No override fields configured</p>
                                                    </div>
                                                </div>
                                                
                                                <!-- Unknown type -->
                                                <div v-else class="form-unknown">
                                                    <span>{{ field.type }} field</span>
                                                </div>
                                                
                                                <div class="field-info">
                                                    <span class="field-code">Code: {{ fieldKey }}</span>
                                                    <span v-if="field.translate" class="field-badge translate">Translatable</span>
                                                    <span v-if="field.showInList" class="field-badge list">Show in List</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div v-else class="preview-empty">
                                        <p>Select a table from the menu to see its form preview</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Table Based JSON Tab -->
                    <div v-if="activeJsonTab === 'table'" class="json-tab-content">
                        <button @click="copyJson('table')" class="btn-copy-json">Copy</button>
                        <div class="json-viewer">
                            <pre>{{ tableBasedJson }}</pre>
                        </div>
                    </div>
                    
                    <!-- Diff Only Tab -->
                    <div v-if="activeJsonTab === 'diff'" class="json-tab-content">
                        <!-- Sub-tabs only for Toolbox mode -->
                        <div v-if="isToolbox" class="diff-sub-tabs">
                            <button 
                                @click="activeDiffSubTab = 'import'" 
                                class="diff-sub-tab"
                                :class="{ active: activeDiffSubTab === 'import' }"
                            >
                                Import JSON
                            </button>
                            <button 
                                @click="activeDiffSubTab = 'current'" 
                                class="diff-sub-tab"
                                :class="{ active: activeDiffSubTab === 'current' }"
                            >
                                Current Configuration
                            </button>
                        </div>
                        
                        <!-- Import Tab Content (Toolbox only) -->
                        <div v-if="isToolbox && activeDiffSubTab === 'import'" class="diff-sub-content">
                            <div class="diff-input-section">
                                <p class="diff-section-description">Paste your JSON configuration below to load tables and fields:</p>
                                <textarea 
                                    v-model="importJsonText"
                                    @input="parseImportedJson"
                                    placeholder="Paste your JSON configuration here..."
                                    class="json-import-textarea"
                                    rows="20"
                                ></textarea>
                                <div v-if="importError" class="import-error">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <span>{{ importError }}</span>
                                </div>
                                <div v-if="importSuccess" class="import-success">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <span>JSON imported successfully!</span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Current Configuration Tab Content (or default when not in toolbox mode) -->
                        <div v-if="!isToolbox || activeDiffSubTab === 'current'" class="diff-sub-content">
                            <div class="diff-output-section">
                                <button @click="copyJson('diff')" class="btn-copy-json">Copy</button>
                                <div class="json-viewer">
                                    <pre>{{ diffJson }}</pre>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Edit Table Name Modal -->
        <div v-if="editingTableKey" class="modal-overlay" @click.self="editingTableKey = null">
            <div class="modal">
                <h3 class="modal-title">Rename Table</h3>
                <input 
                    v-model="newTableNameForEdit" 
                    @keyup.enter="renameTable" 
                    placeholder="New table name" 
                    class="modal-input" 
                />
                <div class="modal-actions">
                    <button @click="editingTableKey = null" class="btn-cancel">Cancel</button>
                    <button @click="renameTable" class="btn-primary">Rename</button>
                </div>
            </div>
        </div>
        
        <!-- Modals -->
        <div v-if="showAddTableModal" class="modal-overlay" @click.self="showAddTableModal = false">
            <div class="modal">
                <h3 class="modal-title">Add New Table</h3>
                <input v-model="newTableName" @keyup.enter="addNewTable" placeholder="Table name (e.g., customTable)" class="modal-input" />
                <div class="modal-actions">
                    <button @click="showAddTableModal = false" class="btn-cancel">Cancel</button>
                    <button @click="addNewTable" class="btn-primary">Add Table</button>
                </div>
            </div>
        </div>
        
        <div v-if="showAddFieldModal" class="modal-overlay" @click.self="showAddFieldModal = false">
            <div class="modal">
                <h3 class="modal-title">Add New Field</h3>
                <input v-model="newFieldName" @keyup.enter="addNewField" placeholder="Field name (e.g., customField)" class="modal-input" />
                <div class="modal-actions">
                    <button @click="showAddFieldModal = false" class="btn-cancel">Cancel</button>
                    <button @click="addNewField" class="btn-primary">Add Field</button>
                </div>
            </div>
        </div>
        
        <div v-if="addCropModalData" class="modal-overlay" @click.self="addCropModalData = null">
            <div class="modal">
                <h3 class="modal-title">Add Image Crop</h3>
                <input v-model="newCropName" placeholder="Crop name (e.g., thumbnail)" class="modal-input" />
                <input v-model.number="newCropWidth" type="number" placeholder="Width" class="modal-input" />
                <input v-model.number="newCropHeight" type="number" placeholder="Height" class="modal-input" />
                <div class="modal-actions">
                    <button @click="addCropModalData = null" class="btn-cancel">Cancel</button>
                    <button @click="addNewCrop" class="btn-primary">Add Crop</button>
                </div>
            </div>
        </div>
        
        <!-- Add Condition Field Modal -->
        <div v-if="addConditionFieldModalData" class="modal-overlay" @click.self="addConditionFieldModalData = null">
            <div class="modal">
                <h3 class="modal-title">Add Condition Field</h3>
                <div v-if="getAvailableConditionFields().length === 0" class="modal-message">
                    All condition fields have already been added.
                </div>
                <template v-else>
                    <select v-model="selectedConditionField" class="modal-input">
                        <option value="">Select a condition field...</option>
                        <option v-for="fieldName in getAvailableConditionFields()" :key="fieldName" :value="fieldName">
                            {{ fieldName === 'start' ? 'Start Date' : 
                               fieldName === 'end' ? 'End Date' : 
                               fieldName.charAt(0).toUpperCase() + fieldName.slice(1) }}
                        </option>
                    </select>
                    <div class="modal-actions">
                        <button @click="addConditionFieldModalData = null" class="btn-cancel">Cancel</button>
                        <button @click="addNewConditionField" class="btn-primary" :disabled="!selectedConditionField">Add Field</button>
                    </div>
                </template>
            </div>
        </div>
        
        <!-- Add Override Field Modal -->
        <div v-if="addOverrideFieldModalData" class="modal-overlay" @click.self="addOverrideFieldModalData = null">
            <div class="modal">
                <h3 class="modal-title">Add Field Override</h3>
                <select v-model="selectedOverrideField" class="modal-input">
                    <option value="">Select a field to override...</option>
                    <option 
                        v-for="(field, fieldKey) in getAvailableOverrideFields()" 
                        :key="fieldKey" 
                        :value="fieldKey"
                    >
                        {{ field.label || fieldKey }} ({{ field.type }})
                    </option>
                </select>
                <div class="modal-actions">
                    <button @click="addOverrideFieldModalData = null" class="btn-cancel">Cancel</button>
                    <button @click="addNewOverrideField" class="btn-primary">Add Override</button>
                </div>
            </div>
        </div>
        
        <!-- Add Subtable Field Modal -->
        <div v-if="addSubtableFieldModalData" class="modal-overlay" @click.self="addSubtableFieldModalData = null">
            <div class="modal">
                <h3 class="modal-title">Add Subtable Field</h3>
                <input 
                    v-model="newSubtableFieldName" 
                    @keyup.enter="addNewSubtableField" 
                    placeholder="Field name (e.g., customField)" 
                    class="modal-input" 
                />
                <div class="modal-actions">
                    <button @click="addSubtableFieldModalData = null" class="btn-cancel">Cancel</button>
                    <button @click="addNewSubtableField" class="btn-primary">Add Field</button>
                </div>
            </div>
        </div>
        
        <!-- Table Settings Modal -->
        <div v-if="showTableSettings" class="modal-overlay" @click.self="showTableSettings = false">
            <div class="modal large">
                <h3 class="modal-title">Table Settings</h3>
                <div class="modal-search-box">
                    <svg class="search-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                    <input 
                        v-model="tableSettingsSearchQuery" 
                        type="text" 
                        placeholder="Search tables..."
                        class="search-input"
                    />
                </div>
                <div class="table-settings-list">
                    <div v-if="Object.keys(filteredSettingsTables).length === 0" class="no-tables">
                        <p>No tables match your search criteria.</p>
                    </div>
                    <div v-for="(table, tableKey) in filteredSettingsTables" :key="tableKey" class="table-setting-item">
                        <label class="table-setting-label">
                            <input 
                                type="checkbox" 
                                :checked="!isTableDisabled(tableKey)"
                                @change="toggleTableManually(tableKey)"
                            >
                            <span>{{ tableKey }}</span>
                            <span class="table-label" v-if="table.label">({{ table.label }})</span>
                        </label>
                    </div>
                </div>
                <div class="modal-actions">
                    <button @click="showTableSettings = false" class="btn-primary">Done</button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { ref, reactive, computed, watch, nextTick } from 'vue';

export default {
    name: 'DatastoreBuilder',
    props: {
        isToolbox: {
            type: Boolean,
            default: false
        },
        projectId: {
            type: Number,
            default: 0
        },
        masterTemplate: {
            type: Object,
            default: () => ({})
        },
        defaultStructure: {
            type: Object,
            default: () => ({})
        },
        configuration: {
            type: Object,
            default: () => ({})
        },
        projectModules: {
            type: Object,
            default: () => ({})
        },
        chainCode: {
            type: String,
            default: null
        },
        allowedTables: {
            type: Array,
            default: () => []
        }
    },
    setup(props) {
        // State
        const selectedTable = ref(null);
        const configDiff = reactive({
            tables: {}
        });
        const showAddTableModal = ref(false);
        const showAddFieldModal = ref(false);
        const newTableName = ref('');
        const newFieldName = ref('');
        const showTablePropertyDropdown = ref(false);
        const fieldPropertyDropdowns = reactive({});
        const addCropModalData = ref(null);
        const newCropName = ref('');
        const newCropWidth = ref(250);
        const newCropHeight = ref(250);
        const topPanelTab = ref('full');
        const bottomPanelTab = ref('tables');
        const showAddModuleOverrideModal = ref(false);
        const showAddConditionOverrideModal = ref(false);
        const selectedModuleForOverride = ref('');
        const selectedConditionForOverride = ref('');
        const showTableSettings = ref(false);
        const editingTableKey = ref(null);
        const newTableNameForEdit = ref('');
        const editingSelectedTableName = ref(false);
        const selectedTableNewName = ref('');
        const editingFieldName = reactive({});
        const newFieldNames = reactive({});
        
        // Search and filter state
        const tableSearchQuery = ref('');
        const tableSettingsSearchQuery = ref('');
        const showOnlyActiveTables = ref(true); // Default to showing only active tables
        
        // JSON Preview state
        const activeJsonTab = ref('visual');
        const visualSelectedTable = ref(null);
        const liveEditEnabled = ref(true);
        
        // Import JSON state
        const importJsonText = ref('');
        const importError = ref('');
        const importSuccess = ref(false);
        
        // Sub-tab state for Full Diff
        const activeDiffSubTab = ref('import');
        
        // Override preview state
        const expandedOverrides = reactive({});
        
        // Condition field names
        const conditionFieldNames = ['start', 'end', 'seasons', 'periodes', 'buildings'];
        
        // Subtable field modals
        const addSubtableFieldModalData = ref(null);
        const newSubtableFieldName = ref('');
        const addConditionFieldModalData = ref(null);
        const selectedConditionField = ref('');
        const addOverrideFieldModalData = ref(null);
        const selectedOverrideField = ref('');
        
        // Use defaultStructure as base
        const baseStructure = computed(() => props.defaultStructure?.tables || {});
        
        // Available modules from project
        const availableModules = computed(() => {
            if (!props.projectModules || typeof props.projectModules !== 'object') {
                return [];
            }
            return Object.values(props.projectModules);
        });
        
        // Determine which tables should be enabled based on allowed tables
        const getDefaultEnabledTables = () => {
            const enabledTables = new Set();
            
            // Use allowedTables if provided, otherwise enable all tables from default structure
            if (props.allowedTables && Array.isArray(props.allowedTables) && props.allowedTables.length > 0) {
                props.allowedTables.forEach(table => {
                    enabledTables.add(table);
                });
            } else if (props.defaultStructure?.tables) {
                // If no allowed tables specified, enable all tables from default structure
                Object.keys(props.defaultStructure.tables).forEach(table => {
                    enabledTables.add(table);
                });
            }
            
            return enabledTables;
        };
        
        const defaultEnabledTables = computed(() => getDefaultEnabledTables());
        
        // Initialize configDiff with existing configuration if provided
        if (props.configuration && Object.keys(props.configuration).length > 0) {
            // Deep merge the configuration
            if (props.configuration.tables) {
                Object.assign(configDiff.tables, props.configuration.tables);
            }
            // Copy other properties
            Object.keys(props.configuration).forEach(key => {
                if (key !== 'tables') {
                    configDiff[key] = props.configuration[key];
                }
            });
        }
        
        // Initialize disabled tables that are not in allowedTables
        if (props.defaultStructure?.tables) {
            Object.keys(props.defaultStructure.tables).forEach(tableKey => {
                const tableInDefaultStructure = props.defaultStructure.tables[tableKey];
                const isDisabledInDefault = tableInDefaultStructure?.disabled === true;
                
                if (!defaultEnabledTables.value.has(tableKey)) {
                    // This table should be disabled
                    // Only add to diff if it's not already disabled in default structure
                    if (!isDisabledInDefault) {
                        if (!configDiff.tables[tableKey]) {
                            configDiff.tables[tableKey] = {};
                        }
                        if (!configDiff.tables[tableKey].hasOwnProperty('disabled')) {
                            configDiff.tables[tableKey].disabled = true;
                        }
                    }
                }
            });
        }
        
        // Force reactivity update
        configDiff.tables = { ...configDiff.tables };
        
        // Debug logging
        console.log('DatastoreBuilder initialized:', {
            defaultStructure: props.defaultStructure,
            baseStructure: baseStructure.value,
            allowedTables: props.allowedTables,
            defaultEnabledTables: Array.from(defaultEnabledTables.value),
            configDiff: configDiff,
            hasBaseStructure: Object.keys(baseStructure.value).length > 0
        });
        
        // Current tables (all tables from base structure)
        const currentTables = computed(() => {
            const tables = {};
            
            // Include all tables from base structure
            Object.keys(baseStructure.value).forEach(tableKey => {
                if (!isTableDisabled(tableKey)) {
                    tables[tableKey] = mergeDeep(
                        baseStructure.value[tableKey] || {}, 
                        configDiff.tables?.[tableKey] || {}
                    );
                }
            });
            
            // Add any new tables from configDiff
            if (configDiff.tables) {
                Object.keys(configDiff.tables).forEach(tableKey => {
                    if (!baseStructure.value[tableKey] && !configDiff.tables[tableKey].disabled) {
                        tables[tableKey] = configDiff.tables[tableKey];
                    }
                });
            }
            
            return tables;
        });
        
        // Sorted current tables for visual preview
        const sortedCurrentTables = computed(() => {
            const sorted = {};
            Object.keys(currentTables.value).sort().forEach(key => {
                sorted[key] = currentTables.value[key];
            });
            return sorted;
        });
        
        // Filtered tables for main list
        const filteredTables = computed(() => {
            const tables = {};
            const searchLower = tableSearchQuery.value.toLowerCase();
            
            // Include tables from base structure
            Object.entries(baseStructure.value).forEach(([tableKey, table]) => {
                // Check if table matches search
                const matchesSearch = !searchLower || 
                    tableKey.toLowerCase().includes(searchLower) ||
                    (table.label && table.label.toLowerCase().includes(searchLower));
                
                // Check if table is disabled
                const isDisabled = isTableDisabled(tableKey);
                
                // Show only active tables by default, unless checkbox is unchecked
                const shouldShow = showOnlyActiveTables.value ? !isDisabled : true;
                
                if (matchesSearch && shouldShow) {
                    tables[tableKey] = table;
                }
            });
            
            // Include custom tables from configDiff
            if (configDiff.tables) {
                Object.entries(configDiff.tables).forEach(([tableKey, table]) => {
                    // Skip if it's already in base structure or if it's disabled
                    if (baseStructure.value[tableKey] || table.disabled) return;
                    
                    // Check if table matches search
                    const matchesSearch = !searchLower || 
                        tableKey.toLowerCase().includes(searchLower) ||
                        (table.label && table.label.toLowerCase().includes(searchLower));
                    
                    // Show only active tables by default (custom tables are active unless explicitly disabled)
                    const shouldShow = showOnlyActiveTables.value ? !table.disabled : true;
                    
                    if (matchesSearch && shouldShow) {
                        tables[tableKey] = table;
                    }
                });
            }
            
            // Sort tables alphabetically
            const sortedTables = {};
            Object.keys(tables).sort().forEach(key => {
                sortedTables[key] = tables[key];
            });
            
            return sortedTables;
        });
        
        // Filtered tables for settings modal
        const filteredSettingsTables = computed(() => {
            const tables = {};
            const searchLower = tableSettingsSearchQuery.value.toLowerCase();
            
            Object.entries(baseStructure.value).forEach(([tableKey, table]) => {
                // Check if table matches search
                const matchesSearch = !searchLower || 
                    tableKey.toLowerCase().includes(searchLower) ||
                    (table.label && table.label.toLowerCase().includes(searchLower));
                
                if (matchesSearch) {
                    tables[tableKey] = table;
                }
            });
            
            return tables;
        });
        
        
        // Sort fields based on _insert_after property
        const getSortedFields = (tableKey) => {
            const fields = getTableFields(tableKey);
            if (!fields || Object.keys(fields).length === 0) return {};
            
            // Build dependency graph
            const fieldKeys = Object.keys(fields).filter(key => !fields[key].disabled);
            const sortedKeys = [];
            const visited = new Set();
            
            // Helper function to add field and its dependencies
            const addField = (fieldKey) => {
                if (visited.has(fieldKey)) return;
                visited.add(fieldKey);
                
                // Find fields that should come before this one
                fieldKeys.forEach(otherKey => {
                    if (fields[otherKey]._insert_after === fieldKey && !visited.has(otherKey)) {
                        // We need to add the other field after this one, so skip for now
                    }
                });
                
                sortedKeys.push(fieldKey);
                
                // Find fields that should come after this one
                fieldKeys.forEach(otherKey => {
                    if (fields[otherKey]._insert_after === fieldKey && !visited.has(otherKey)) {
                        addField(otherKey);
                    }
                });
            };
            
            // First add fields with no _insert_after or invalid _insert_after
            fieldKeys.forEach(fieldKey => {
                const insertAfter = fields[fieldKey]._insert_after;
                if (!insertAfter || !fields[insertAfter] || fields[insertAfter].disabled) {
                    addField(fieldKey);
                }
            });
            
            // Then add any remaining fields (in case of circular dependencies)
            fieldKeys.forEach(fieldKey => {
                if (!visited.has(fieldKey)) {
                    addField(fieldKey);
                }
            });
            
            // Build sorted object
            const sortedFields = {};
            sortedKeys.forEach(key => {
                sortedFields[key] = fields[key];
            });
            
            return sortedFields;
        };
        
        // All table properties
        const allTableProperties = [
            { key: 'singleton', label: 'Singleton', type: 'boolean' },
            { key: 'order', label: 'Order', type: 'text' }
        ];
        
        // Available table properties (not yet added)
        const availableTableProperties = computed(() => {
            if (!selectedTable.value) return [];
            const table = currentTables.value[selectedTable.value] || {};
            return allTableProperties.filter(prop => !table.hasOwnProperty(prop.key));
        });
        
        // Boolean table properties
        const booleanTableProperties = computed(() => {
            return allTableProperties.filter(prop => prop.type === 'boolean');
        });
        
        // Available non-boolean table properties (for dropdown)
        const availableNonBooleanTableProperties = computed(() => {
            if (!selectedTable.value) return [];
            const table = currentTables.value[selectedTable.value] || {};
            return allTableProperties
                .filter(prop => prop.type !== 'boolean')
                .filter(prop => !table.hasOwnProperty(prop.key));
        });
        
        // All added non-boolean properties (already added to the table)
        const allAddedNonBooleanProperties = computed(() => {
            if (!selectedTable.value) return [];
            const table = currentTables.value[selectedTable.value] || {};
            return allTableProperties
                .filter(prop => prop.type !== 'boolean')
                .filter(prop => table.hasOwnProperty(prop.key));
        });
        
        // Helper functions
        const mergeDeep = (target, source) => {
            const output = { ...target };
            Object.keys(source).forEach(key => {
                if (source[key] && typeof source[key] === 'object' && !Array.isArray(source[key])) {
                    output[key] = mergeDeep(target[key] || {}, source[key]);
                } else {
                    output[key] = source[key];
                }
            });
            return output;
        };
        
        const isTableDisabled = (tableKey) => {
            // If there's an explicit override in configDiff
            if (configDiff.tables?.[tableKey]?.disabled !== undefined) {
                return configDiff.tables[tableKey].disabled;
            }
            // If the table is not in baseStructure, it's a custom table - check if it's explicitly disabled
            if (!baseStructure.value[tableKey]) {
                return configDiff.tables?.[tableKey]?.disabled === true;
            }
            // Otherwise, check if it's in the default enabled list
            return !defaultEnabledTables.value.has(tableKey);
        };
        
        const selectTable = (tableKey) => {
            selectedTable.value = tableKey;
        };
        
        const disableTable = (tableKey) => {
            if (!configDiff.tables) configDiff.tables = {};
            if (!configDiff.tables[tableKey]) configDiff.tables[tableKey] = {};
            configDiff.tables[tableKey].disabled = true;
        };
        
        const enableTable = (tableKey) => {
            if (!configDiff.tables) configDiff.tables = {};
            if (!configDiff.tables[tableKey]) configDiff.tables[tableKey] = {};
            
            if (defaultEnabledTables.value.has(tableKey)) {
                // If it's in the default enabled list, just remove the disabled flag
                delete configDiff.tables[tableKey].disabled;
                if (Object.keys(configDiff.tables[tableKey]).length === 0) {
                    delete configDiff.tables[tableKey];
                }
            } else {
                // If it's not in the default enabled list, explicitly enable it
                configDiff.tables[tableKey].disabled = false;
            }
        };
        
        const getTableProperty = (tableKey, property) => {
            const baseValue = baseStructure.value[tableKey]?.[property];
            const diffValue = configDiff.tables?.[tableKey]?.[property];
            return diffValue !== undefined ? diffValue : baseValue;
        };
        
        const updateTableProperty = (tableKey, property, value) => {
            if (!configDiff.tables) configDiff.tables = {};
            if (!configDiff.tables[tableKey]) configDiff.tables[tableKey] = {};
            
            const baseValue = baseStructure.value[tableKey]?.[property];
            if (value !== baseValue) {
                configDiff.tables[tableKey][property] = value;
            } else {
                delete configDiff.tables[tableKey][property];
                // Clean up empty table object
                if (Object.keys(configDiff.tables[tableKey]).length === 0) {
                    delete configDiff.tables[tableKey];
                }
            }
        };
        
        const updateSingletonProperty = (tableKey, value) => {
            // Get the default singleton value
            const defaultSingletonValue = baseStructure.value[tableKey]?.singleton;
            
            // Update the singleton property
            if (!configDiff.tables) configDiff.tables = {};
            if (!configDiff.tables[tableKey]) configDiff.tables[tableKey] = {};
            
            // If the value is different from default, store it explicitly
            // If it's the same as default, remove it from diff (but only if default exists)
            if (defaultSingletonValue !== undefined) {
                if (value !== defaultSingletonValue) {
                    configDiff.tables[tableKey].singleton = value;
                } else {
                    delete configDiff.tables[tableKey].singleton;
                }
            } else {
                // If no default exists, store the value if it's true, remove if false
                if (value) {
                    configDiff.tables[tableKey].singleton = value;
                } else {
                    delete configDiff.tables[tableKey].singleton;
                }
            }
            
            // Clean up empty table object
            if (Object.keys(configDiff.tables[tableKey]).length === 0) {
                delete configDiff.tables[tableKey];
            }
            
            // Handle order property based on singleton state
            if (value) {
                // If singleton is enabled, remove the order property (regardless of where it exists)
                removeTableProperty(tableKey, 'order');
            } else {
                // If singleton is disabled, check if order exists in default structure
                const defaultHasOrder = baseStructure.value[tableKey]?.order !== undefined;
                const currentHasOrder = currentTables.value[tableKey]?.order !== undefined;
                
                // If order exists in default but not in current table, add it
                if (defaultHasOrder && !currentHasOrder) {
                    updateTableProperty(tableKey, 'order', ['']);
                }
            }
        };
        
        const hasTableProperty = (tableKey, property) => {
            return getTableProperty(tableKey, property) !== undefined;
        };
        
        const addTableProperty = (tableKey, property) => {
            if (property === 'order') {
                updateTableProperty(tableKey, property, ['']);
            } else {
                updateTableProperty(tableKey, property, '');
            }
        };
        
        const removeTableProperty = (tableKey, property) => {
            if (configDiff.tables?.[tableKey]) {
                delete configDiff.tables[tableKey][property];
                if (Object.keys(configDiff.tables[tableKey]).length === 0) {
                    delete configDiff.tables[tableKey];
                }
            }
        };
        
        const getTableFields = (tableKey) => {
            const baseFields = baseStructure.value[tableKey]?.fields || {};
            const diffFields = configDiff.tables?.[tableKey]?.fields || {};
            const fields = mergeDeep(baseFields, diffFields);
            
            // Ensure overrides field has proper structure
            if (fields.overrides) {
                // Only set type if not already defined in base structure
                if (!baseFields.overrides && !fields.overrides.type) {
                    fields.overrides.type = 'subtable';
                }
                if (!fields.overrides.fields) {
                    fields.overrides.fields = {};
                }
            }
            
            return fields;
        };
        
        const updateFieldProperty = (tableKey, fieldKey, property, value) => {
            if (!configDiff.tables) configDiff.tables = {};
            if (!configDiff.tables[tableKey]) configDiff.tables[tableKey] = {};
            if (!configDiff.tables[tableKey].fields) configDiff.tables[tableKey].fields = {};
            if (!configDiff.tables[tableKey].fields[fieldKey]) configDiff.tables[tableKey].fields[fieldKey] = {};
            
            const baseValue = baseStructure.value[tableKey]?.fields?.[fieldKey]?.[property];
            if (value !== baseValue) {
                configDiff.tables[tableKey].fields[fieldKey][property] = value;
            } else {
                delete configDiff.tables[tableKey].fields[fieldKey][property];
                if (Object.keys(configDiff.tables[tableKey].fields[fieldKey]).length === 0) {
                    delete configDiff.tables[tableKey].fields[fieldKey];
                }
                // Clean up empty fields object
                if (Object.keys(configDiff.tables[tableKey].fields).length === 0) {
                    delete configDiff.tables[tableKey].fields;
                }
                // Clean up empty table object
                if (Object.keys(configDiff.tables[tableKey]).length === 0) {
                    delete configDiff.tables[tableKey];
                }
            }
        };
        
        const removeField = (tableKey, fieldKey) => {
            if (!configDiff.tables) configDiff.tables = {};
            if (!configDiff.tables[tableKey]) configDiff.tables[tableKey] = {};
            if (!configDiff.tables[tableKey].fields) configDiff.tables[tableKey].fields = {};
            configDiff.tables[tableKey].fields[fieldKey] = { disabled: true };
        };
        
        const addNewTable = () => {
            if (newTableName.value) {
                if (!configDiff.tables) configDiff.tables = {};
                configDiff.tables[newTableName.value] = {
                    label: newTableName.value,
                    fields: {}
                };
                selectedTable.value = newTableName.value;
                newTableName.value = '';
                showAddTableModal.value = false;
            }
        };
        
        const addNewField = () => {
            if (newFieldName.value && selectedTable.value) {
                // Special handling for overrides field
                if (newFieldName.value === 'overrides') {
                    const isInDefault = isFieldInDefaultStructure(selectedTable.value, newFieldName.value);
                    // Only set type if not in default structure
                    if (!isInDefault) {
                        updateFieldProperty(selectedTable.value, newFieldName.value, 'type', 'subtable');
                    }
                    // Always initialize fields as empty object for new overrides
                    updateFieldProperty(selectedTable.value, newFieldName.value, 'fields', {});
                } else {
                    updateFieldProperty(selectedTable.value, newFieldName.value, 'type', 'text');
                    updateFieldProperty(selectedTable.value, newFieldName.value, 'label', newFieldName.value);
                }
                newFieldName.value = '';
                showAddFieldModal.value = false;
            }
        };
        
        const addOverrideField = () => {
            if (selectedTable.value) {
                const isInDefault = isFieldInDefaultStructure(selectedTable.value, 'overrides');
                // Only set type if not in default structure
                if (!isInDefault) {
                    updateFieldProperty(selectedTable.value, 'overrides', 'type', 'subtable');
                }
                // Always initialize fields as empty object for new overrides
                updateFieldProperty(selectedTable.value, 'overrides', 'fields', {});
            }
        };
        
        const toggleFieldPropertyDropdown = (fieldKey) => {
            fieldPropertyDropdowns[fieldKey] = !fieldPropertyDropdowns[fieldKey];
        };
        
        const addFieldProperty = (tableKey, fieldKey, property) => {
            updateFieldProperty(tableKey, fieldKey, property, '');
            fieldPropertyDropdowns[fieldKey] = false;
        };
        
        const removeFieldProperty = (tableKey, fieldKey, property) => {
            if (configDiff.tables?.[tableKey]?.fields?.[fieldKey]) {
                delete configDiff.tables[tableKey].fields[fieldKey][property];
                // Clean up empty field object
                if (Object.keys(configDiff.tables[tableKey].fields[fieldKey]).length === 0) {
                    delete configDiff.tables[tableKey].fields[fieldKey];
                }
                // Clean up empty fields object
                if (Object.keys(configDiff.tables[tableKey].fields).length === 0) {
                    delete configDiff.tables[tableKey].fields;
                }
                // Clean up empty table object
                if (Object.keys(configDiff.tables[tableKey]).length === 0) {
                    delete configDiff.tables[tableKey];
                }
            }
        };
        
        // Select field options
        const updateOption = (tableKey, fieldKey, oldKey, newKey, newLabel) => {
            const field = getTableFields(tableKey)[fieldKey];
            if (!field.options) field.options = {};
            
            if (oldKey !== newKey) {
                delete field.options[oldKey];
            }
            field.options[newKey] = newLabel;
            
            updateFieldProperty(tableKey, fieldKey, 'options', field.options);
        };
        
        const removeOption = (tableKey, fieldKey, optionKey) => {
            const field = getTableFields(tableKey)[fieldKey];
            if (field.options) {
                delete field.options[optionKey];
                updateFieldProperty(tableKey, fieldKey, 'options', field.options);
            }
        };
        
        const addOption = (tableKey, fieldKey) => {
            const field = getTableFields(tableKey)[fieldKey];
            if (!field.options) field.options = {};
            const newKey = `option${Object.keys(field.options).length + 1}`;
            field.options[newKey] = 'New Option';
            updateFieldProperty(tableKey, fieldKey, 'options', field.options);
        };
        
        // Image crops
        const showAddCropModal = (tableKey, fieldKey) => {
            addCropModalData.value = { tableKey, fieldKey };
            newCropName.value = '';
            newCropWidth.value = 250;
            newCropHeight.value = 250;
        };
        
        const addNewCrop = () => {
            if (addCropModalData.value && newCropName.value) {
                const { tableKey, fieldKey } = addCropModalData.value;
                const field = getTableFields(tableKey)[fieldKey];
                if (!field.crops) field.crops = {};
                field.crops[newCropName.value] = {
                    width: newCropWidth.value,
                    height: newCropHeight.value,
                    label: newCropName.value
                };
                updateFieldProperty(tableKey, fieldKey, 'crops', field.crops);
                addCropModalData.value = null;
            }
        };
        
        const updateCrop = (tableKey, fieldKey, cropKey, property, value) => {
            const field = getTableFields(tableKey)[fieldKey];
            if (field.crops?.[cropKey]) {
                field.crops[cropKey][property] = parseInt(value) || 0;
                updateFieldProperty(tableKey, fieldKey, 'crops', field.crops);
            }
        };
        
        const removeCrop = (tableKey, fieldKey, cropKey) => {
            const field = getTableFields(tableKey)[fieldKey];
            if (field.crops?.[cropKey]) {
                delete field.crops[cropKey];
                updateFieldProperty(tableKey, fieldKey, 'crops', field.crops);
            }
        };
        
        // JSON Views
        const tableBasedJson = computed(() => {
            // Show only the diff for the selected table
            if (!selectedTable.value) {
                return '{\n  "message": "Select a table to see its changes"\n}';
            }
            
            // Check if there are any changes for this table
            if (!configDiff.tables?.[selectedTable.value]) {
                return '{\n  "message": "No changes made to this table yet"\n}';
            }
            
            // Clean the table data before displaying
            const cleanTableData = (data) => {
                const cleaned = JSON.parse(JSON.stringify(data));
                // Remove singleton if it's false or undefined
                if (!cleaned.singleton) {
                    delete cleaned.singleton;
                }
                return cleaned;
            };
            
            // Show only the diff for the selected table
            const tableDiff = {
                tables: {
                    [selectedTable.value]: cleanTableData(configDiff.tables[selectedTable.value])
                }
            };
            
            return JSON.stringify(tableDiff, null, 2);
        });
        
        const diffJson = computed(() => {
            // Show only the differences/customizations made
            if (Object.keys(configDiff).length === 0) {
                return '{\n  "message": "No customizations made yet"\n}';
            }
            
            // Clean the diff data before displaying
            const cleanData = (data) => {
                const cleaned = JSON.parse(JSON.stringify(data));
                
                // Clean table data
                if (cleaned.tables) {
                    Object.keys(cleaned.tables).forEach(tableKey => {
                        const table = cleaned.tables[tableKey];
                        // Remove singleton if it's false or undefined
                        if (!table.singleton) {
                            delete table.singleton;
                        }
                    });
                }
                
                return cleaned;
            };
            
            const cleanDiff = cleanData(configDiff);
            return JSON.stringify(cleanDiff, null, 2);
        });
        
        const copyJson = (type) => {
            let jsonToCopy = '';
            if (type === 'diff') {
                jsonToCopy = diffJson.value;
            } else if (type === 'table') {
                jsonToCopy = tableBasedJson.value;
            }
            navigator.clipboard.writeText(jsonToCopy);
        };
        
        const toggleTableManually = (tableKey) => {
            if (!configDiff.tables) configDiff.tables = {};
            if (!configDiff.tables[tableKey]) configDiff.tables[tableKey] = {};
            
            const currentlyDisabled = isTableDisabled(tableKey);
            const isDisabledInDefault = props.defaultStructure?.tables?.[tableKey]?.disabled === true;
            
            if (currentlyDisabled) {
                // Enable the table
                if (isDisabledInDefault) {
                    // If it was disabled in default, we need to explicitly enable it
                    configDiff.tables[tableKey].disabled = false;
                } else if (defaultEnabledTables.value.has(tableKey)) {
                    // If it was in default enabled, just remove the disabled flag
                    delete configDiff.tables[tableKey].disabled;
                    if (Object.keys(configDiff.tables[tableKey]).length === 0) {
                        delete configDiff.tables[tableKey];
                    }
                } else {
                    // If it wasn't in default, explicitly enable it
                    configDiff.tables[tableKey].disabled = false;
                }
            } else {
                // Disable the table
                if (isDisabledInDefault) {
                    // If it's already disabled in default, remove from diff
                    delete configDiff.tables[tableKey].disabled;
                    if (Object.keys(configDiff.tables[tableKey]).length === 0) {
                        delete configDiff.tables[tableKey];
                    }
                } else {
                    // Otherwise add disabled flag
                    configDiff.tables[tableKey].disabled = true;
                }
            }
        };
        
        // Module overrides
        const getTableModuleOverrides = (tableKey) => {
            return configDiff.tables?.[tableKey]?.moduleOverrides || {};
        };
        
        const updateModuleOverride = (tableKey, moduleId, property, value) => {
            if (!configDiff.tables) configDiff.tables = {};
            if (!configDiff.tables[tableKey]) configDiff.tables[tableKey] = {};
            if (!configDiff.tables[tableKey].moduleOverrides) configDiff.tables[tableKey].moduleOverrides = {};
            if (!configDiff.tables[tableKey].moduleOverrides[moduleId]) configDiff.tables[tableKey].moduleOverrides[moduleId] = {};
            
            configDiff.tables[tableKey].moduleOverrides[moduleId][property] = value;
        };
        
        const removeModuleOverride = (tableKey, moduleId) => {
            if (configDiff.tables?.[tableKey]?.moduleOverrides?.[moduleId]) {
                delete configDiff.tables[tableKey].moduleOverrides[moduleId];
                if (Object.keys(configDiff.tables[tableKey].moduleOverrides).length === 0) {
                    delete configDiff.tables[tableKey].moduleOverrides;
                }
            }
        };
        
        const addModuleOverride = () => {
            if (selectedModuleForOverride.value && selectedTable.value) {
                updateModuleOverride(selectedTable.value, selectedModuleForOverride.value, 'disabled', false);
                selectedModuleForOverride.value = '';
                showAddModuleOverrideModal.value = false;
            }
        };
        
        const getModuleName = (moduleId) => {
            const module = availableModules.value.find(m => m.id == moduleId);
            return module ? module.name : `Module ${moduleId}`;
        };
        
        // Condition overrides
        const getTableConditionOverrides = (tableKey) => {
            return configDiff.tables?.[tableKey]?.conditionOverrides || {};
        };
        
        const updateConditionOverride = (tableKey, conditionKey, property, value) => {
            if (!configDiff.tables) configDiff.tables = {};
            if (!configDiff.tables[tableKey]) configDiff.tables[tableKey] = {};
            if (!configDiff.tables[tableKey].conditionOverrides) configDiff.tables[tableKey].conditionOverrides = {};
            if (!configDiff.tables[tableKey].conditionOverrides[conditionKey]) configDiff.tables[tableKey].conditionOverrides[conditionKey] = {};
            
            configDiff.tables[tableKey].conditionOverrides[conditionKey][property] = value;
        };
        
        const removeConditionOverride = (tableKey, conditionKey) => {
            if (configDiff.tables?.[tableKey]?.conditionOverrides?.[conditionKey]) {
                delete configDiff.tables[tableKey].conditionOverrides[conditionKey];
                if (Object.keys(configDiff.tables[tableKey].conditionOverrides).length === 0) {
                    delete configDiff.tables[tableKey].conditionOverrides;
                }
            }
        };
        
        const addConditionOverride = () => {
            if (selectedConditionForOverride.value && selectedTable.value) {
                updateConditionOverride(selectedTable.value, selectedConditionForOverride.value, 'disabled', false);
                selectedConditionForOverride.value = '';
                showAddConditionOverrideModal.value = false;
            }
        };
        
        // Toggle field enabled/disabled state
        const toggleField = (tableKey, fieldKey) => {
            const isInDefault = isFieldInDefaultStructure(tableKey, fieldKey);
            const field = getTableFields(tableKey)[fieldKey];
            const currentlyDisabled = field?.disabled === true;
            
            if (isInDefault) {
                // Field exists in default structure - toggle disabled state
                if (!configDiff.tables) configDiff.tables = {};
                if (!configDiff.tables[tableKey]) configDiff.tables[tableKey] = {};
                if (!configDiff.tables[tableKey].fields) configDiff.tables[tableKey].fields = {};
                if (!configDiff.tables[tableKey].fields[fieldKey]) configDiff.tables[tableKey].fields[fieldKey] = {};
                
                if (currentlyDisabled) {
                    // Enable the field
                    delete configDiff.tables[tableKey].fields[fieldKey].disabled;
                    if (Object.keys(configDiff.tables[tableKey].fields[fieldKey]).length === 0) {
                        delete configDiff.tables[tableKey].fields[fieldKey];
                    }
                } else {
                    // Disable the field
                    configDiff.tables[tableKey].fields[fieldKey].disabled = true;
                }
            } else {
                // Custom field (like override added via Add Field) - remove it completely
                if (currentlyDisabled) {
                    // Re-add the field
                    if (!configDiff.tables) configDiff.tables = {};
                    if (!configDiff.tables[tableKey]) configDiff.tables[tableKey] = {};
                    if (!configDiff.tables[tableKey].fields) configDiff.tables[tableKey].fields = {};
                    if (!configDiff.tables[tableKey].fields[fieldKey]) configDiff.tables[tableKey].fields[fieldKey] = {};
                    
                    // Remove disabled flag to re-enable
                    delete configDiff.tables[tableKey].fields[fieldKey].disabled;
                } else {
                    // Remove the field completely
                    if (configDiff.tables?.[tableKey]?.fields?.[fieldKey]) {
                        delete configDiff.tables[tableKey].fields[fieldKey];
                    }
                }
            }
            
            // Clean up empty objects
            if (configDiff.tables?.[tableKey]?.fields && Object.keys(configDiff.tables[tableKey].fields).length === 0) {
                delete configDiff.tables[tableKey].fields;
            }
            if (configDiff.tables?.[tableKey] && Object.keys(configDiff.tables[tableKey]).length === 0) {
                delete configDiff.tables[tableKey];
            }
        };
        
        // Check if table is a custom table (not in base structure)
        const isCustomTable = (tableKey) => {
            return !baseStructure.value[tableKey];
        };
        
        // Check if table has been modified
        const isTableModified = (tableKey) => {
            if (!configDiff.tables?.[tableKey]) return false;
            
            const tableDiff = configDiff.tables[tableKey];
            
            // Check if table has any field modifications
            if (tableDiff.fields && Object.keys(tableDiff.fields).length > 0) {
                return true;
            }
            
            // Check if table has any property modifications (other than fields and disabled)
            return Object.keys(tableDiff).some(key => key !== 'fields' && key !== 'disabled');
        };
        
        // Check if field has been modified
        const isFieldModified = (tableKey, fieldKey) => {
            // Check if field exists in configDiff
            return configDiff.tables?.[tableKey]?.fields?.[fieldKey] !== undefined;
        };
        
        // Confirm and delete a custom table
        const confirmDeleteTable = (tableKey) => {
            if (confirm(`Are you sure you want to delete the table "${tableKey}"?`)) {
                // Remove from configDiff
                if (configDiff.tables?.[tableKey]) {
                    delete configDiff.tables[tableKey];
                }
                // If this was the selected table, clear selection
                if (selectedTable.value === tableKey) {
                    selectedTable.value = null;
                }
            }
        };
        
        // Start editing table name
        const startEditingTableName = (tableKey) => {
            editingTableKey.value = tableKey;
            newTableNameForEdit.value = tableKey;
        };
        
        // Rename table
        const renameTable = () => {
            const oldKey = editingTableKey.value;
            const newKey = newTableNameForEdit.value.trim();
            
            if (!oldKey || !newKey || oldKey === newKey) {
                editingTableKey.value = null;
                return;
            }
            
            // Check if new name already exists
            if (baseStructure.value[newKey] || configDiff.tables?.[newKey]) {
                alert(`Table "${newKey}" already exists`);
                return;
            }
            
            // Copy the table data to new key
            if (configDiff.tables?.[oldKey]) {
                configDiff.tables[newKey] = configDiff.tables[oldKey];
                delete configDiff.tables[oldKey];
            }
            
            // Update selected table if it was the renamed one
            if (selectedTable.value === oldKey) {
                selectedTable.value = newKey;
            }
            
            editingTableKey.value = null;
            newTableNameForEdit.value = '';
        };
        
        // Start editing selected table name in editor
        const startEditingSelectedTableName = () => {
            selectedTableNewName.value = selectedTable.value;
            editingSelectedTableName.value = true;
            // Focus input after DOM update
            nextTick(() => {
                const input = document.querySelector('.editor-title-input');
                if (input) input.focus();
            });
        };
        
        // Save selected table name
        const saveSelectedTableName = () => {
            const oldKey = selectedTable.value;
            const newKey = selectedTableNewName.value.trim();
            
            if (!newKey || oldKey === newKey) {
                editingSelectedTableName.value = false;
                return;
            }
            
            // Check if new name already exists
            if (baseStructure.value[newKey] || configDiff.tables?.[newKey]) {
                alert(`Table "${newKey}" already exists`);
                editingSelectedTableName.value = false;
                return;
            }
            
            // Copy the table data to new key
            if (configDiff.tables?.[oldKey]) {
                configDiff.tables[newKey] = configDiff.tables[oldKey];
                delete configDiff.tables[oldKey];
            }
            
            selectedTable.value = newKey;
            editingSelectedTableName.value = false;
        };
        
        // Start editing field name
        const startEditingFieldName = (fieldKey) => {
            newFieldNames[fieldKey] = fieldKey;
            editingFieldName[fieldKey] = true;
        };
        
        // Save field name
        const saveFieldName = (oldFieldKey) => {
            const newFieldKey = newFieldNames[oldFieldKey]?.trim();
            
            if (!newFieldKey || oldFieldKey === newFieldKey) {
                editingFieldName[oldFieldKey] = false;
                return;
            }
            
            // Check if field name already exists
            const existingFields = getTableFields(selectedTable.value);
            if (existingFields[newFieldKey]) {
                alert(`Field "${newFieldKey}" already exists`);
                editingFieldName[oldFieldKey] = false;
                return;
            }
            
            // Copy field data to new key
            if (configDiff.tables?.[selectedTable.value]?.fields?.[oldFieldKey]) {
                if (!configDiff.tables[selectedTable.value].fields[newFieldKey]) {
                    configDiff.tables[selectedTable.value].fields[newFieldKey] = {};
                }
                Object.assign(configDiff.tables[selectedTable.value].fields[newFieldKey], configDiff.tables[selectedTable.value].fields[oldFieldKey]);
                delete configDiff.tables[selectedTable.value].fields[oldFieldKey];
            } else {
                // If field doesn't exist in configDiff, create a rename entry
                if (!configDiff.tables) configDiff.tables = {};
                if (!configDiff.tables[selectedTable.value]) configDiff.tables[selectedTable.value] = {};
                if (!configDiff.tables[selectedTable.value].fields) configDiff.tables[selectedTable.value].fields = {};
                
                // Copy base field properties
                const baseField = baseStructure.value[selectedTable.value]?.fields?.[oldFieldKey];
                if (baseField) {
                    configDiff.tables[selectedTable.value].fields[newFieldKey] = { ...baseField };
                    configDiff.tables[selectedTable.value].fields[oldFieldKey] = { disabled: true };
                }
            }
            
            editingFieldName[oldFieldKey] = false;
            delete newFieldNames[oldFieldKey];
        };
        
        // Tooltip functions
        const getPropertyTooltip = (propKey) => {
            const tooltips = {
                'singleton': 'Only one record can exist for this table',
                'order': 'Field used for sorting records in list views',
                'code': 'Internal code identifier for this table',
                'type': 'Type classification of this table',
                'label': 'Display name for this table',
                'icon': 'Icon identifier for UI display',
                'description': 'Detailed description of this table'
            };
            return tooltips[propKey] || '';
        };
        
        const getBooleanPropertyTooltip = (propKey) => {
            const tooltips = {
                'singleton': 'Only one record can exist for this table'
            };
            return tooltips[propKey] || '';
        };
        
        // Check if boolean property should be shown
        const shouldShowBooleanProperty = (tableKey, propKey) => {
            const currentValue = getTableProperty(tableKey, propKey);
            const baseValue = baseStructure.value[tableKey]?.[propKey];
            
            // Show if currently true, or if base structure has it as true
            return currentValue === true || baseValue === true;
        };
        
        // Check if field boolean property should be shown
        const shouldShowFieldBooleanProperty = (tableKey, fieldKey, propKey) => {
            const field = getTableFields(tableKey)[fieldKey];
            const currentValue = field?.[propKey];
            const baseValue = baseStructure.value[tableKey]?.fields?.[fieldKey]?.[propKey];
            
            // Show if currently true, or if base structure has it as true
            return currentValue === true || baseValue === true;
        };
        
        // Check if field is custom (not in base structure)
        const isCustomField = (tableKey, fieldKey) => {
            return !baseStructure.value[tableKey]?.fields?.[fieldKey];
        };
        
        // Get available tables for reference (excluding current table)
        const getAvailableReferenceTables = () => {
            const tables = {};
            Object.entries(currentTables.value).forEach(([tableKey, table]) => {
                // Exclude the current table from reference options
                if (tableKey !== selectedTable.value) {
                    tables[tableKey] = table;
                }
            });
            return tables;
        };
        
        // Get order fields as array
        const getTableOrderFields = (tableKey) => {
            const orderValue = getTableProperty(tableKey, 'order');
            if (!orderValue) return [''];
            if (Array.isArray(orderValue)) return orderValue;
            return [orderValue];
        };
        
        // Update specific order field
        const updateOrderField = (tableKey, index, value) => {
            const orderFields = getTableOrderFields(tableKey);
            orderFields[index] = value;
            // Remove empty fields from the end
            while (orderFields.length > 1 && orderFields[orderFields.length - 1] === '') {
                orderFields.pop();
            }
            // Always store as array, even for single values
            const filteredFields = orderFields.filter(f => f !== '');
            if (filteredFields.length === 0) {
                updateTableProperty(tableKey, 'order', ['']);
            } else {
                updateTableProperty(tableKey, 'order', filteredFields);
            }
        };
        
        // Add new order field
        const addOrderField = (tableKey) => {
            const orderFields = getTableOrderFields(tableKey);
            orderFields.push('');
            updateTableProperty(tableKey, 'order', orderFields);
        };
        
        // Remove order field
        const removeOrderField = (tableKey, index) => {
            const orderFields = getTableOrderFields(tableKey);
            orderFields.splice(index, 1);
            if (orderFields.length === 0) {
                orderFields.push('');
            }
            // Always store as array
            const filteredFields = orderFields.filter(f => f !== '');
            if (filteredFields.length === 0) {
                updateTableProperty(tableKey, 'order', ['']);
            } else {
                updateTableProperty(tableKey, 'order', filteredFields);
            }
        };
        
        // Confirm and delete a custom field
        const confirmDeleteField = (tableKey, fieldKey) => {
            if (confirm(`Are you sure you want to delete the field "${fieldKey}"?`)) {
                // Remove from configDiff
                if (configDiff.tables?.[tableKey]?.fields?.[fieldKey]) {
                    delete configDiff.tables[tableKey].fields[fieldKey];
                    
                    // Clean up empty fields object
                    if (Object.keys(configDiff.tables[tableKey].fields).length === 0) {
                        delete configDiff.tables[tableKey].fields;
                    }
                    // Clean up empty table object
                    if (Object.keys(configDiff.tables[tableKey]).length === 0) {
                        delete configDiff.tables[tableKey];
                    }
                }
            }
        };
        
        // Get field type icon for visual representation
        const getFieldTypeIcon = (type) => {
            const icons = {
                text: 'T',
                textarea: 'T',
                select: '▼',
                boolean: '✓',
                date: '📅',
                image: '🖼',
                file: '📎',
                number: '#',
                reference: '→',
                subtable: '▦',
                ckeditor: 'E'
            };
            return icons[type] || '?';
        };
        
        // Get table references (fields that reference other tables)
        const getTableReferences = (tableKey) => {
            const table = currentTables.value[tableKey];
            if (!table || !table.fields) return [];
            
            const references = [];
            Object.entries(table.fields).forEach(([fieldKey, field]) => {
                if (field.type === 'reference' && field.table && !field.disabled) {
                    references.push({
                        field: fieldKey,
                        table: field.table
                    });
                }
            });
            return references;
        };
        
        // Reset table to default settings
        const resetTable = (tableKey) => {
            if (confirm(`Are you sure you want to reset the table "${tableKey}" to default settings?`)) {
                // Remove entire table from configDiff
                if (configDiff.tables?.[tableKey]) {
                    delete configDiff.tables[tableKey];
                }
                
                // If table should be disabled by default, add disabled flag back
                if (!defaultEnabledTables.value.has(tableKey)) {
                    if (!configDiff.tables) configDiff.tables = {};
                    configDiff.tables[tableKey] = { disabled: true };
                }
            }
        };
        
        // Reset field to default settings
        const resetField = (tableKey, fieldKey) => {
            if (confirm(`Are you sure you want to reset the field "${fieldKey}" to default settings?`)) {
                // Remove field from configDiff
                if (configDiff.tables?.[tableKey]?.fields?.[fieldKey]) {
                    delete configDiff.tables[tableKey].fields[fieldKey];
                    
                    // Clean up empty fields object
                    if (Object.keys(configDiff.tables[tableKey].fields).length === 0) {
                        delete configDiff.tables[tableKey].fields;
                    }
                    // Clean up empty table object
                    if (Object.keys(configDiff.tables[tableKey]).length === 0) {
                        delete configDiff.tables[tableKey];
                    }
                }
            }
        };
        
        // Save configuration
        const saveConfiguration = () => {
            // Dispatch event with configuration data
            const event = new CustomEvent('save-datastore-configuration', {
                detail: { configuration: configDiff }
            });
            window.dispatchEvent(event);
        };
        
        
        // Listen for save event
        window.addEventListener('save-configuration', saveConfiguration);
        
        // Watch for live edit synchronization
        // Function to scroll to table in visual preview
        const scrollToTable = (tableKey) => {
            if (!liveEditEnabled.value || activeJsonTab.value !== 'visual') return;
            
            // Need to wait for the visual preview to update
            nextTick(() => {
                const tableMenu = document.querySelector('.table-menu');
                const tableButton = document.querySelector(`.table-menu-item[data-table="${tableKey}"]`);
                
                if (tableButton && tableMenu) {
                    // Calculate scroll position to center the table button
                    const menuHeight = tableMenu.clientHeight;
                    const buttonHeight = tableButton.clientHeight;
                    const buttonTop = tableButton.offsetTop;
                    
                    // Center the button in the menu viewport
                    const targetScrollTop = buttonTop - (menuHeight / 2) + (buttonHeight / 2);
                    
                    // Ensure we don't scroll past the content boundaries
                    const maxScroll = tableMenu.scrollHeight - menuHeight;
                    const finalScrollTop = Math.max(0, Math.min(targetScrollTop, maxScroll));
                    
                    // Scroll the table menu
                    tableMenu.scrollTo({
                        top: finalScrollTop,
                        behavior: 'smooth'
                    });
                }
            });
        };
        
        // Watch for live edit synchronization
        watch(() => selectedTable.value, (newTable) => {
            if (liveEditEnabled.value && newTable && activeJsonTab.value === 'visual') {
                visualSelectedTable.value = newTable;
                scrollToTable(newTable);
            }
        });
        
        const getConditionFields = (fields) => {
            if (!fields) return {};
            const conditionFields = {};
            Object.entries(fields).forEach(([key, field]) => {
                if (conditionFieldNames.includes(key)) {
                    conditionFields[key] = field;
                }
            });
            return conditionFields;
        };
        
        const getOverrideFields = (fields) => {
            if (!fields) return {};
            const overrideFields = {};
            Object.entries(fields).forEach(([key, field]) => {
                if (!conditionFieldNames.includes(key)) {
                    overrideFields[key] = field;
                }
            });
            return overrideFields;
        };
        
        const getAvailableOverrideFields = () => {
            if (!selectedTable.value || !addOverrideFieldModalData.value) return {};
            
            const tableFields = getTableFields(selectedTable.value);
            const overrideField = getTableFields(selectedTable.value)[addOverrideFieldModalData.value.fieldKey];
            const existingOverrides = overrideField?.fields || {};
            
            // Filter out fields that are already being overridden and disabled fields
            const availableFields = {};
            Object.entries(tableFields).forEach(([key, field]) => {
                if (!existingOverrides[key] && !field.disabled && key !== 'overrides') {
                    availableFields[key] = field;
                }
            });
            
            return availableFields;
        };
        
        // Toggle override preview expansion
        const toggleOverridePreview = (fieldKey) => {
            expandedOverrides[fieldKey] = !expandedOverrides[fieldKey];
        };
        
        // Scroll to field when editing (if live edit is enabled)
        // Parse imported JSON
        const parseImportedJson = () => {
            importError.value = '';
            importSuccess.value = false;
            
            if (!importJsonText.value.trim()) {
                // Clear the configuration if input is empty
                configDiff.tables = {};
                return;
            }
            
            try {
                const parsedJson = JSON.parse(importJsonText.value);
                
                // Clear existing configuration
                configDiff.tables = {};
                
                // Import the configuration
                if (parsedJson.tables) {
                    configDiff.tables = parsedJson.tables;
                } else if (typeof parsedJson === 'object') {
                    // If it's just a tables object without wrapper
                    configDiff.tables = parsedJson;
                }
                
                // Force reactivity update
                configDiff.tables = { ...configDiff.tables };
                
                // Select first table if none selected
                if (!selectedTable.value && Object.keys(currentTables.value).length > 0) {
                    const firstEnabledTable = Object.entries(currentTables.value)
                        .find(([key, table]) => !isTableDisabled(key));
                    if (firstEnabledTable) {
                        selectedTable.value = firstEnabledTable[0];
                    }
                }
                
                importSuccess.value = true;
                setTimeout(() => {
                    importSuccess.value = false;
                }, 3000);
                
            } catch (error) {
                importError.value = 'Invalid JSON format. Please check your input.';
                console.error('JSON parse error:', error);
            }
        };
        
        const scrollToField = (fieldKey) => {
            if (!liveEditEnabled.value || activeJsonTab.value !== 'visual') return;
            
            // Need to wait for the visual preview to update
            setTimeout(() => {
                const previewContainer = document.querySelector('.form-preview');
                const fieldElement = document.querySelector(`.form-field[data-field="${fieldKey}"]`);
                
                if (fieldElement && previewContainer) {
                    // Calculate the position relative to the preview container
                    const containerRect = previewContainer.getBoundingClientRect();
                    const fieldRect = fieldElement.getBoundingClientRect();
                    const scrollTop = fieldElement.offsetTop - previewContainer.offsetTop;
                    
                    // Calculate scroll position to center the field or ensure it's fully visible
                    const containerHeight = previewContainer.clientHeight;
                    const fieldHeight = fieldElement.clientHeight;
                    const currentScrollTop = previewContainer.scrollTop;
                    const fieldTop = fieldElement.offsetTop;
                    const fieldBottom = fieldTop + fieldHeight;
                    const containerScrollBottom = currentScrollTop + containerHeight;
                    
                    let targetScrollTop;
                    
                    // If field is taller than half the container, just make sure it's fully visible
                    if (fieldHeight > containerHeight / 2) {
                        // If field top is above viewport, scroll to show the top
                        if (fieldTop < currentScrollTop) {
                            targetScrollTop = fieldTop - 20; // 20px padding from top
                        }
                        // If field bottom is below viewport, scroll to show the bottom
                        else if (fieldBottom > containerScrollBottom) {
                            targetScrollTop = fieldBottom - containerHeight + 20; // 20px padding from bottom
                        }
                        // Field is already fully visible
                        else {
                            targetScrollTop = currentScrollTop;
                        }
                    } else {
                        // Center the field in the viewport
                        targetScrollTop = fieldTop - (containerHeight / 2) + (fieldHeight / 2);
                    }
                    
                    // Ensure we don't scroll past the content boundaries
                    targetScrollTop = Math.max(0, Math.min(targetScrollTop, previewContainer.scrollHeight - containerHeight));
                    
                    // Scroll the preview container
                    previewContainer.scrollTo({
                        top: targetScrollTop,
                        behavior: 'smooth'
                    });
                    
                    // Add highlight effect
                    fieldElement.classList.add('field-highlight');
                    setTimeout(() => {
                        fieldElement.classList.remove('field-highlight');
                    }, 2000);
                }
            }, 100); // Small delay to ensure DOM is updated
        };
        
        // Get available condition fields that haven't been added yet
        const getAvailableConditionFields = () => {
            if (!addConditionFieldModalData.value) return [];
            
            const { tableKey, fieldKey } = addConditionFieldModalData.value;
            const currentFields = getTableFields(tableKey)[fieldKey]?.fields || {};
            
            // Filter out already added condition fields
            return conditionFieldNames.filter(fieldName => !currentFields[fieldName]);
        };
        
        // Condition field modal methods
        const showAddConditionFieldModal = (tableKey, fieldKey) => {
            addConditionFieldModalData.value = { tableKey, fieldKey };
            selectedConditionField.value = '';
        };
        
        const addNewConditionField = () => {
            if (addConditionFieldModalData.value && selectedConditionField.value) {
                const { tableKey, fieldKey } = addConditionFieldModalData.value;
                
                // Determine field properties
                let fieldType = 'text';
                let fieldLabel = selectedConditionField.value;
                
                switch(selectedConditionField.value) {
                    case 'start':
                    case 'end':
                        fieldType = 'date';
                        fieldLabel = selectedConditionField.value === 'start' ? 'Start Date' : 'End Date';
                        break;
                    case 'seasons':
                    case 'periodes':
                    case 'buildings':
                        fieldType = 'reference';
                        fieldLabel = selectedConditionField.value.charAt(0).toUpperCase() + selectedConditionField.value.slice(1);
                        break;
                }
                
                // Check if these properties differ from base
                const baseField = baseStructure.value[tableKey]?.fields?.[fieldKey]?.fields?.[selectedConditionField.value] || {};
                
                // Add type if different from base
                if (fieldType !== baseField.type) {
                    updateSubtableFieldProperty(tableKey, fieldKey, selectedConditionField.value, 'type', fieldType);
                }
                
                // Add label if different from base
                if (fieldLabel !== baseField.label) {
                    updateSubtableFieldProperty(tableKey, fieldKey, selectedConditionField.value, 'label', fieldLabel);
                }
                
                // For reference fields, add table and multiple properties if different
                if (fieldType === 'reference') {
                    if (selectedConditionField.value !== baseField.table) {
                        updateSubtableFieldProperty(tableKey, fieldKey, selectedConditionField.value, 'table', selectedConditionField.value);
                    }
                    if (true !== baseField.multiple) {
                        updateSubtableFieldProperty(tableKey, fieldKey, selectedConditionField.value, 'multiple', true);
                    }
                }
                
                addConditionFieldModalData.value = null;
            }
        };
        
        // Override field modal methods
        const showAddOverrideFieldModal = (tableKey, fieldKey) => {
            addOverrideFieldModalData.value = { tableKey, fieldKey };
            selectedOverrideField.value = '';
        };
        
        const addNewOverrideField = () => {
            if (addOverrideFieldModalData.value && selectedOverrideField.value) {
                const { tableKey, fieldKey } = addOverrideFieldModalData.value;
                
                // Get current fields
                const currentFields = getTableFields(tableKey)[fieldKey]?.fields || {};
                
                // Create a copy
                const updatedFields = { ...currentFields };
                
                // Get the original field definition
                const originalField = getTableFields(tableKey)[selectedOverrideField.value];
                
                // Copy the field definition (type, label, etc.)
                updatedFields[selectedOverrideField.value] = {
                    type: originalField.type,
                    label: originalField.label || selectedOverrideField.value
                };
                
                // Copy other relevant properties
                if (originalField.options) {
                    updatedFields[selectedOverrideField.value].options = { ...originalField.options };
                }
                if (originalField.table) {
                    updatedFields[selectedOverrideField.value].table = originalField.table;
                }
                if (originalField.multiple !== undefined) {
                    updatedFields[selectedOverrideField.value].multiple = originalField.multiple;
                }
                
                updateFieldProperty(tableKey, fieldKey, 'fields', updatedFields);
                addOverrideFieldModalData.value = null;
            }
        };
        
        // Subtable field methods
        const showAddSubtableFieldModal = (tableKey, fieldKey) => {
            addSubtableFieldModalData.value = { tableKey, fieldKey };
            newSubtableFieldName.value = '';
        };
        
        const addNewSubtableField = () => {
            if (addSubtableFieldModalData.value && newSubtableFieldName.value) {
                const { tableKey, fieldKey } = addSubtableFieldModalData.value;
                
                // Get current fields
                const currentFields = getTableFields(tableKey)[fieldKey]?.fields || {};
                
                // Create a copy
                const updatedFields = { ...currentFields };
                
                // Add new field with default properties
                updatedFields[newSubtableFieldName.value] = {
                    type: 'text',
                    label: newSubtableFieldName.value
                };
                
                updateFieldProperty(tableKey, fieldKey, 'fields', updatedFields);
                addSubtableFieldModalData.value = null;
                newSubtableFieldName.value = '';
            }
        };
        
        const removeSubtableField = (tableKey, fieldKey, subfieldKey) => {
            // Check if field exists in base structure
            const baseFields = baseStructure.value[tableKey]?.fields?.[fieldKey]?.fields || {};
            const existsInBase = baseFields[subfieldKey] !== undefined;
            
            if (existsInBase) {
                // Field exists in base, so we need to mark it as disabled
                updateSubtableFieldProperty(tableKey, fieldKey, subfieldKey, 'disabled', true);
            } else {
                // Field doesn't exist in base, remove it from diff
                if (configDiff.tables?.[tableKey]?.fields?.[fieldKey]?.fields?.[subfieldKey]) {
                    delete configDiff.tables[tableKey].fields[fieldKey].fields[subfieldKey];
                    
                    // Clean up empty objects
                    if (Object.keys(configDiff.tables[tableKey].fields[fieldKey].fields).length === 0) {
                        delete configDiff.tables[tableKey].fields[fieldKey].fields;
                    }
                    if (Object.keys(configDiff.tables[tableKey].fields[fieldKey]).length === 0) {
                        delete configDiff.tables[tableKey].fields[fieldKey];
                    }
                    if (Object.keys(configDiff.tables[tableKey].fields).length === 0) {
                        delete configDiff.tables[tableKey].fields;
                    }
                    if (Object.keys(configDiff.tables[tableKey]).length === 0) {
                        delete configDiff.tables[tableKey];
                    }
                }
            }
        };
        
        const updateSubtableFieldProperty = (tableKey, fieldKey, subfieldKey, property, value) => {
            // Initialize the diff structure if needed
            if (!configDiff.tables) configDiff.tables = {};
            if (!configDiff.tables[tableKey]) configDiff.tables[tableKey] = {};
            if (!configDiff.tables[tableKey].fields) configDiff.tables[tableKey].fields = {};
            if (!configDiff.tables[tableKey].fields[fieldKey]) {
                configDiff.tables[tableKey].fields[fieldKey] = {};
            }
            if (!configDiff.tables[tableKey].fields[fieldKey].fields) {
                configDiff.tables[tableKey].fields[fieldKey].fields = {};
            }
            if (!configDiff.tables[tableKey].fields[fieldKey].fields[subfieldKey]) {
                configDiff.tables[tableKey].fields[fieldKey].fields[subfieldKey] = {};
            }
            
            // Get the base value to compare
            const baseFields = baseStructure.value[tableKey]?.fields?.[fieldKey]?.fields || {};
            const baseValue = baseFields[subfieldKey]?.[property];
            
            // Only add to diff if value is different from base
            if (value !== baseValue) {
                configDiff.tables[tableKey].fields[fieldKey].fields[subfieldKey][property] = value;
            } else {
                // Remove from diff if same as base
                delete configDiff.tables[tableKey].fields[fieldKey].fields[subfieldKey][property];
                
                // Clean up empty objects
                if (Object.keys(configDiff.tables[tableKey].fields[fieldKey].fields[subfieldKey]).length === 0) {
                    delete configDiff.tables[tableKey].fields[fieldKey].fields[subfieldKey];
                }
                if (Object.keys(configDiff.tables[tableKey].fields[fieldKey].fields).length === 0) {
                    delete configDiff.tables[tableKey].fields[fieldKey].fields;
                }
                if (Object.keys(configDiff.tables[tableKey].fields[fieldKey]).length === 0) {
                    delete configDiff.tables[tableKey].fields[fieldKey];
                }
                if (Object.keys(configDiff.tables[tableKey].fields).length === 0) {
                    delete configDiff.tables[tableKey].fields;
                }
                if (Object.keys(configDiff.tables[tableKey]).length === 0) {
                    delete configDiff.tables[tableKey];
                }
            }
        };
        
        // Ensure overrides field has type 'subtable' only if it's not in default structure
        const ensureOverridesFieldType = (tableKey, fieldKey) => {
            if (fieldKey === 'overrides') {
                const field = getTableFields(tableKey)[fieldKey];
                const isInDefault = isFieldInDefaultStructure(tableKey, fieldKey);
                
                // Only set type if overrides field is not in default structure and doesn't have type
                if (!isInDefault && (!field.type || field.type !== 'subtable')) {
                    updateFieldProperty(tableKey, fieldKey, 'type', 'subtable');
                }
                return 'subtable';
            }
            return null;
        };
        
        // Check if field exists in default structure
        const isFieldInDefaultStructure = (tableKey, fieldKey) => {
            return baseStructure.value[tableKey]?.fields?.[fieldKey] !== undefined;
        };
        
        // Toggle override field (disable/enable or remove)
        const toggleOverrideField = (tableKey, overrideFieldKey, subfieldKey) => {
            const isInDefault = isFieldInDefaultStructure(tableKey, subfieldKey);
            
            if (isInDefault) {
                // Field exists in default structure - toggle disabled state
                const currentField = getTableFields(tableKey)[subfieldKey];
                const isCurrentlyDisabled = currentField?.disabled === true;
                
                if (isCurrentlyDisabled) {
                    // Re-enable the field by removing disabled flag
                    if (configDiff.tables?.[tableKey]?.fields?.[subfieldKey]) {
                        delete configDiff.tables[tableKey].fields[subfieldKey].disabled;
                        if (Object.keys(configDiff.tables[tableKey].fields[subfieldKey]).length === 0) {
                            delete configDiff.tables[tableKey].fields[subfieldKey];
                        }
                    }
                } else {
                    // Disable the field
                    updateFieldProperty(tableKey, subfieldKey, 'disabled', true);
                }
            } else {
                // Custom field - remove it completely
                removeSubtableField(tableKey, overrideFieldKey, subfieldKey);
            }
        };
        
        // Check if condition field exists in default list
        const isConditionFieldInDefaultStructure = (fieldKey) => {
            const conditionFieldNames = ['start', 'end', 'seasons', 'periodes', 'buildings'];
            return conditionFieldNames.includes(fieldKey);
        };
        
        // Get the type for a condition field
        const getConditionFieldType = (fieldKey) => {
            switch(fieldKey) {
                case 'start':
                case 'end':
                    return 'date';
                case 'seasons':
                case 'periodes':
                case 'buildings':
                    return 'reference';
                default:
                    return 'text';
            }
        };
        
        // Toggle condition field (disable/enable or remove)
        const toggleConditionField = (tableKey, overrideFieldKey, subfieldKey) => {
            // Check if the parent override field exists in default structure
            const isOverrideInDefault = isFieldInDefaultStructure(tableKey, overrideFieldKey);
            const isConditionInDefault = isConditionFieldInDefaultStructure(subfieldKey);
            
            // If override field is custom (not in default), always remove condition fields
            if (!isOverrideInDefault) {
                // Override is custom, so all its condition fields should be removed, not disabled
                removeSubtableField(tableKey, overrideFieldKey, subfieldKey);
            } else if (isConditionInDefault) {
                // Override is in default and condition field is in default list - toggle disabled state
                // We need to handle this specially to avoid adding all fields to diff
                if (!configDiff.tables) configDiff.tables = {};
                if (!configDiff.tables[tableKey]) configDiff.tables[tableKey] = {};
                if (!configDiff.tables[tableKey].fields) configDiff.tables[tableKey].fields = {};
                if (!configDiff.tables[tableKey].fields[overrideFieldKey]) {
                    configDiff.tables[tableKey].fields[overrideFieldKey] = {};
                }
                if (!configDiff.tables[tableKey].fields[overrideFieldKey].fields) {
                    configDiff.tables[tableKey].fields[overrideFieldKey].fields = {};
                }
                
                const currentConditionField = configDiff.tables[tableKey].fields[overrideFieldKey].fields[subfieldKey];
                const isCurrentlyDisabled = currentConditionField?.disabled === true;
                
                if (isCurrentlyDisabled) {
                    // Re-enable the condition field by removing the disabled property
                    if (configDiff.tables[tableKey].fields[overrideFieldKey].fields[subfieldKey]) {
                        delete configDiff.tables[tableKey].fields[overrideFieldKey].fields[subfieldKey].disabled;
                        // Clean up if empty
                        if (Object.keys(configDiff.tables[tableKey].fields[overrideFieldKey].fields[subfieldKey]).length === 0) {
                            delete configDiff.tables[tableKey].fields[overrideFieldKey].fields[subfieldKey];
                        }
                    }
                } else {
                    // Disable the condition field
                    if (!configDiff.tables[tableKey].fields[overrideFieldKey].fields[subfieldKey]) {
                        configDiff.tables[tableKey].fields[overrideFieldKey].fields[subfieldKey] = {};
                    }
                    configDiff.tables[tableKey].fields[overrideFieldKey].fields[subfieldKey].disabled = true;
                }
                
                // Clean up empty objects
                if (configDiff.tables[tableKey].fields[overrideFieldKey].fields && 
                    Object.keys(configDiff.tables[tableKey].fields[overrideFieldKey].fields).length === 0) {
                    delete configDiff.tables[tableKey].fields[overrideFieldKey].fields;
                }
                if (configDiff.tables[tableKey].fields[overrideFieldKey] && 
                    Object.keys(configDiff.tables[tableKey].fields[overrideFieldKey]).length === 0) {
                    delete configDiff.tables[tableKey].fields[overrideFieldKey];
                }
            } else {
                // Custom condition field - remove it completely
                removeSubtableField(tableKey, overrideFieldKey, subfieldKey);
            }
        };
        
        return {
            selectedTable,
            currentTables,
            baseStructure,
            showAddTableModal,
            showAddFieldModal,
            newTableName,
            newFieldName,
            showTablePropertyDropdown,
            fieldPropertyDropdowns,
            allTableProperties,
            availableTableProperties,
            booleanTableProperties,
            availableNonBooleanTableProperties,
            allAddedNonBooleanProperties,
            addCropModalData,
            newCropName,
            newCropWidth,
            newCropHeight,
            topPanelTab,
            bottomPanelTab,
            diffJson,
            tableBasedJson,
            showAddModuleOverrideModal,
            showAddConditionOverrideModal,
            selectedModuleForOverride,
            selectedConditionForOverride,
            availableModules,
            showTableSettings,
            
            // Search and filter state
            tableSearchQuery,
            tableSettingsSearchQuery,
            showOnlyActiveTables,
            filteredTables,
            filteredSettingsTables,
            
            // JSON Preview state
            activeJsonTab,
            visualSelectedTable,
            liveEditEnabled,
            
            // Import JSON state
            importJsonText,
            importError,
            importSuccess,
            parseImportedJson,
            activeDiffSubTab,
            
            // Methods
            isTableDisabled,
            selectTable,
            disableTable,
            enableTable,
            getTableProperty,
            updateTableProperty,
            updateSingletonProperty,
            hasTableProperty,
            addTableProperty,
            removeTableProperty,
            getTableFields,
            updateFieldProperty,
            removeField,
            addNewTable,
            addNewField,
            addOverrideField,
            toggleFieldPropertyDropdown,
            addFieldProperty,
            removeFieldProperty,
            updateOption,
            removeOption,
            addOption,
            showAddCropModal,
            addNewCrop,
            updateCrop,
            removeCrop,
            copyJson,
            toggleTableManually,
            getTableModuleOverrides,
            updateModuleOverride,
            removeModuleOverride,
            addModuleOverride,
            getModuleName,
            getTableConditionOverrides,
            updateConditionOverride,
            removeConditionOverride,
            addConditionOverride,
            toggleField,
            isCustomTable,
            confirmDeleteTable,
            editingTableKey,
            newTableNameForEdit,
            startEditingTableName,
            renameTable,
            editingSelectedTableName,
            selectedTableNewName,
            startEditingSelectedTableName,
            saveSelectedTableName,
            editingFieldName,
            newFieldNames,
            startEditingFieldName,
            saveFieldName,
            getPropertyTooltip,
            getBooleanPropertyTooltip,
            shouldShowBooleanProperty,
            shouldShowFieldBooleanProperty,
            isCustomField,
            confirmDeleteField,
            resetTable,
            resetField,
            getAvailableReferenceTables,
            getTableOrderFields,
            updateOrderField,
            addOrderField,
            removeOrderField,
            getFieldTypeIcon,
            getTableReferences,
            sortedCurrentTables,
            getSortedFields,
            scrollToField,
            scrollToTable,
            isTableModified,
            isFieldModified,
            getConditionFields,
            getOverrideFields,
            getAvailableOverrideFields,
            toggleOverridePreview,
            expandedOverrides,
            showAddConditionFieldModal,
            addNewConditionField,
            showAddOverrideFieldModal,
            addNewOverrideField,
            showAddSubtableFieldModal,
            addNewSubtableField,
            removeSubtableField,
            updateSubtableFieldProperty,
            ensureOverridesFieldType,
            isFieldInDefaultStructure,
            toggleOverrideField,
            isConditionFieldInDefaultStructure,
            getConditionFieldType,
            toggleConditionField,
            addConditionFieldModalData,
            selectedConditionField,
            getAvailableConditionFields,
            addOverrideFieldModalData,
            selectedOverrideField,
            addSubtableFieldModalData,
            newSubtableFieldName
        };
    }
};
</script>

<style scoped>
.datastore-builder {
    height: 100%; /* Use 100% of parent container */
    display: flex;
    flex-direction: column;
    background: #f3f4f6;
    overflow: hidden;
}

.builder-layout {
    flex: 1;
    display: flex;
    gap: 1rem;
    padding: 1rem;
    overflow: hidden;
    height: 100%;
}

/* Panels */
.left-panel {
    width: 240px;
    background: white;
    border-radius: 0.5rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

.middle-panel {
    flex: 1;
    background: white;
    border-radius: 0.5rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    overflow-y: auto;
    max-height: 100%;
}

.right-panel {
    flex: 1; /* 50% width - same as middle panel */
    background: white;
    border-radius: 0.5rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

/* JSON Preview Tabs */
.json-tabs {
    display: flex;
    gap: 0.5rem;
}

.tab-button {
    padding: 0.5rem 1rem;
    background: #f3f4f6;
    border: none;
    border-radius: 0.375rem;
    font-size: 0.875rem;
    cursor: pointer;
    transition: all 0.2s;
}

.tab-button:hover {
    background: #e5e7eb;
}

.tab-button.active {
    background: #3b82f6;
    color: white;
}

.json-content {
    flex: 1;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

.json-tab-content {
    flex: 1;
    display: flex;
    flex-direction: column;
    position: relative;
    overflow: hidden;
    height: 100%;
}

.btn-copy-json {
    position: absolute;
    top: 1rem;
    right: 1rem;
    padding: 0.5rem 1rem;
    background: #3b82f6;
    color: white;
    border: none;
    border-radius: 0.375rem;
    font-size: 0.875rem;
    cursor: pointer;
    z-index: 10;
}

.btn-copy-json:hover {
    background: #2563eb;
}

/* Headers */
.panel-header {
    padding: 0.75rem;
    border-bottom: 1px solid #e5e7eb;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.panel-title {
    font-size: 0.9375rem;
    font-weight: 600;
    margin: 0;
}

/* Table Controls */
.table-controls {
    padding: 0.75rem;
    border-bottom: 1px solid #e5e7eb;
}

.search-box {
    position: relative;
    margin-bottom: 0.75rem;
}

.search-icon {
    position: absolute;
    left: 0.5rem;
    top: 50%;
    transform: translateY(-50%);
    width: 1rem;
    height: 1rem;
    color: #6b7280;
}

.search-input {
    width: 100%;
    padding: 0.375rem 0.5rem 0.375rem 2rem;
    border: 1px solid #d1d5db;
    border-radius: 0.375rem;
    font-size: 0.75rem;
}

.search-input:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.checkbox-label {
    display: flex;
    align-items: center;
    gap: 0.375rem;
    font-size: 0.75rem;
    color: #6b7280;
    cursor: pointer;
}

.checkbox-label input[type="checkbox"] {
    cursor: pointer;
}

/* Tables List */
.tables-list {
    flex: 1;
    overflow-y: auto;
    padding: 0.75rem;
}

.table-item {
    margin-bottom: 0.375rem;
    position: relative;
}

.table-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.5rem 0.625rem;
    background: #f9fafb;
    border-radius: 0.375rem;
    cursor: pointer;
    transition: all 0.2s;
    border-left: 3px solid transparent;
    font-size: 0.8125rem;
}

.table-header:hover {
    background: #f3f4f6;
}

.table-item.active .table-header {
    background: #eff6ff;
    border-left-color: #3b82f6;
}

.table-item.active .table-name {
    color: #3b82f6;
    font-weight: 600;
}

.table-item.disabled .table-header {
    background: #fee2e2;
    opacity: 0.7;
}

.table-item.disabled .table-header:hover {
    background: #fecaca;
}

.table-name {
    font-weight: 500;
}

.table-actions {
    display: flex;
    gap: 0.5rem;
}

/* Add Table Button */
.btn-add-table {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.375rem;
    width: 100%;
    padding: 0.5rem;
    margin: 0.5rem 0;
    background: #f3f4f6;
    border: 1px solid #e5e7eb;
    border-radius: 0.375rem;
    font-size: 0.75rem;
    color: #4b5563;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-add-table:hover {
    background: #e5e7eb;
    border-color: #d1d5db;
}

.btn-add-table svg {
    width: 0.875rem;
    height: 0.875rem;
}

/* Editor */
.editor-content {
    padding: 1.5rem;
}

.editor-header {
    margin-bottom: 1.5rem;
}

.editor-title {
    font-size: 1.5rem;
    font-weight: 600;
    margin: 0;
}

.no-selection {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 100%;
    color: #6b7280;
}

.no-tables {
    padding: 2rem;
    text-align: center;
    color: #6b7280;
}

/* Sections */
.section {
    margin-bottom: 2rem;
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.section-title {
    font-size: 1.125rem;
    font-weight: 600;
    margin: 0;
}

/* Boolean Properties Inline */
.boolean-properties-inline {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 1rem;
}

.boolean-properties-left {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.checkbox-property-inline {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
    cursor: pointer;
}

.checkbox-property-inline input[type="checkbox"] {
    cursor: pointer;
}

/* Keep old styles for field switches */
.boolean-properties {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    margin-bottom: 1rem;
    padding: 0.75rem;
    background: #f9fafb;
    border-radius: 0.375rem;
}

.checkbox-property {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
    cursor: pointer;
}

.checkbox-property input[type="checkbox"] {
    cursor: pointer;
}

.section-description {
    font-size: 0.875rem;
    color: #6b7280;
    margin-bottom: 1rem;
}

/* Property Tooltip */
.property-tooltip {
    display: inline-block;
    width: 1rem;
    height: 1rem;
    line-height: 1rem;
    text-align: center;
    font-size: 0.75rem;
    background: #e5e7eb;
    color: #6b7280;
    border-radius: 50%;
    cursor: help;
    margin-left: 0.25rem;
}

/* Live Edit Toggle */
.live-edit-toggle {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
    color: #6b7280;
    cursor: pointer;
    margin-left: auto;
    margin-right: 1rem;
}

.live-edit-toggle input[type="checkbox"] {
    cursor: pointer;
}

/* Field Highlight Animation */
.field-highlight {
    animation: highlightPulse 2s ease-out;
}

@keyframes highlightPulse {
    0% {
        background-color: #fef3c7;
        box-shadow: 0 0 0 0 rgba(251, 191, 36, 0.4);
    }
    50% {
        background-color: #fef3c7;
        box-shadow: 0 0 0 10px rgba(251, 191, 36, 0);
    }
    100% {
        background-color: transparent;
        box-shadow: 0 0 0 10px rgba(251, 191, 36, 0);
    }
}

/* Properties */
.property-grid {
    display: grid;
    gap: 1rem;
    margin-bottom: 1rem;
}

.property-item {
    display: grid;
    grid-template-columns: 140px 1fr auto;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 0.75rem;
}

.property-item:last-child {
    margin-bottom: 0;
}

.property-item label {
    font-weight: 500;
    font-size: 0.875rem;
    color: #4b5563;
}

.property-item input[type="text"],
.property-item select {
    padding: 0.5rem;
    border: 1px solid #e5e7eb;
    border-radius: 0.375rem;
    font-size: 0.875rem;
    max-width: 400px;
}

/* Description field gets full width */
.property-item[data-property="description"] {
    grid-template-columns: 140px 1fr auto;
}

.property-item[data-property="description"] .property-textarea {
    max-width: none;
    width: 100%;
}

/* Textarea styling */
.property-textarea {
    padding: 0.5rem;
    border: 1px solid #e5e7eb;
    border-radius: 0.375rem;
    font-size: 0.875rem;
    font-family: inherit;
    resize: vertical;
    min-height: 3.5rem;
    width: 100%;
}

.property-textarea:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.property-switches {
    display: flex;
    gap: 1rem;
    margin: 0.5rem 0;
}

.property-switches label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
}

/* Fields */
.fields-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.field-item {
    border: 1px solid #e5e7eb;
    border-radius: 0.375rem;
    padding: 1rem;
    overflow: hidden;
}

.field-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin: -1rem -1rem 1rem -1rem;
    padding: 0.75rem 1rem;
    background: #f3f4f6;
    border-radius: 0.375rem 0.375rem 0 0;
    cursor: pointer;
    transition: background-color 0.2s;
}

.field-header:hover {
    background: #e5e7eb;
}

.field-item.disabled .field-header {
    background: #f9fafb;
}

/* Modified indicators */
.table-modified-indicator {
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 3px;
    background: #f59e0b;
}

.table-item.modified .table-header {
    border-left: 3px solid #f59e0b;
    padding-left: calc(0.75rem - 3px);
}

.field-item.modified {
    border-left: 3px solid #f59e0b;
}

/* Custom table styles */
.table-item.custom .table-header {
    background: #f0fdf4;
}

.table-item.custom.active .table-header {
    background: #dcfce7;
    border-left-color: #22c55e;
}

.field-name {
    font-weight: 600;
    color: #374151;
    font-size: 0.875rem;
}

.field-properties {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

/* Type-specific */
.type-specific {
    padding: 1rem;
    background: #f9fafb;
    border-radius: 0.375rem;
    margin-top: 0.5rem;
}

.option-item,
.crop-item {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 0.5rem;
    align-items: center;
}

.option-item input,
.crop-item input {
    flex: 1;
    padding: 0.375rem;
    border: 1px solid #e5e7eb;
    border-radius: 0.25rem;
    font-size: 0.875rem;
}

/* Buttons */
.btn-add-table,
.btn-add-field {
    width: 100%;
    padding: 0.75rem;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    background: none;
    border: 2px dashed #e5e7eb;
    border-radius: 0.375rem;
    color: #6b7280;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-add-table:hover,
.btn-add-field:hover {
    border-color: #d1d5db;
    color: #4b5563;
}

.btn-add-property {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    background: #f3f4f6;
    border: none;
    border-radius: 0.375rem;
    color: #374151;
    cursor: pointer;
    font-size: 0.875rem;
}

.btn-add-property-small {
    padding: 0.25rem 0.5rem;
    background: #f3f4f6;
    border: none;
    border-radius: 0.25rem;
    color: #374151;
    cursor: pointer;
    font-size: 0.875rem;
}

.btn-add-option {
    padding: 0.375rem 0.75rem;
    background: #eff6ff;
    border: 1px solid #dbeafe;
    border-radius: 0.25rem;
    color: #3b82f6;
    cursor: pointer;
    font-size: 0.875rem;
}

.btn-icon {
    padding: 0.25rem;
    background: none;
    border: none;
    border-radius: 0.25rem;
    cursor: pointer;
    transition: background-color 0.2s;
}

.btn-icon:hover {
    background: #f3f4f6;
}

.btn-add-icon {
    padding: 0.25rem;
    background: #3b82f6;
    border: none;
    border-radius: 0.375rem;
    color: white;
    cursor: pointer;
    transition: background-color 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
}

.btn-add-icon:hover {
    background: #2563eb;
}

.btn-icon.enable {
    color: #10b981;
}

.btn-icon.disable {
    color: #ef4444;
}

.btn-icon.settings {
    color: #6b7280;
}

.btn-remove {
    padding: 0.25rem 0.5rem;
    background: none;
    border: none;
    color: #ef4444;
    cursor: pointer;
    font-size: 1.25rem;
    line-height: 1;
}

.btn-copy {
    padding: 0.5rem 1rem;
    background: #3b82f6;
    border: none;
    border-radius: 0.375rem;
    color: white;
    cursor: pointer;
    font-size: 0.875rem;
}

/* Dropdowns */
.add-property-dropdown {
    position: relative;
}

.dropdown-menu {
    position: absolute;
    top: 100%;
    right: 0;
    margin-top: 0.25rem;
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 0.375rem;
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
    z-index: 10;
    min-width: 200px;
}

.dropdown-item {
    display: block;
    width: 100%;
    padding: 0.5rem 1rem;
    text-align: left;
    background: none;
    border: none;
    cursor: pointer;
    font-size: 0.875rem;
}

.dropdown-item:hover {
    background: #f3f4f6;
}

/* Tabs */
.tabs {
    display: flex;
    align-items: center;
    border-bottom: 1px solid #e5e7eb;
    position: relative;
}

.tab {
    padding: 0.75rem 1rem;
    background: none;
    border: none;
    border-bottom: 2px solid transparent;
    color: #6b7280;
    cursor: pointer;
    font-size: 0.875rem;
    font-weight: 500;
    transition: all 0.2s;
}

.tab:hover {
    color: #374151;
}

.tab.active {
    color: #3b82f6;
    border-bottom-color: #3b82f6;
}

.tabs .btn-copy {
    position: absolute;
    right: 0.5rem;
    top: 50%;
    transform: translateY(-50%);
    padding: 0.375rem 0.75rem;
    font-size: 0.75rem;
}

.tab-content {
    flex: 1;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

/* JSON Viewer */
.json-viewer-header {
    padding: 0.75rem 1rem;
    border-bottom: 1px solid #e5e7eb;
    display: flex;
    justify-content: flex-end;
}

.json-viewer {
    flex: 1;
    overflow-y: auto;
    padding: 1rem;
    min-height: 0; /* Important for flexbox overflow */
}

.json-viewer pre {
    margin: 0;
    font-family: 'Consolas', 'Monaco', 'Courier New', monospace;
    font-size: 0.875rem;
    line-height: 1.5;
    white-space: pre-wrap;
    word-wrap: break-word;
}

/* Modals */
.modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 50;
}

.modal {
    background: white;
    border-radius: 0.5rem;
    padding: 1.5rem;
    width: 90%;
    max-width: 400px;
}

.modal-title {
    font-size: 1.125rem;
    font-weight: 600;
    margin: 0 0 1rem 0;
}

.modal-input {
    width: 100%;
    padding: 0.5rem;
    border: 1px solid #e5e7eb;
    border-radius: 0.375rem;
    margin-bottom: 0.5rem;
}

.modal-message {
    padding: 1rem;
    text-align: center;
    color: #6b7280;
    font-size: 0.875rem;
}

.modal-actions {
    display: flex;
    justify-content: flex-end;
    gap: 0.5rem;
    margin-top: 1rem;
}

.btn-cancel {
    padding: 0.5rem 1rem;
    background: #e5e7eb;
    border: none;
    border-radius: 0.375rem;
    color: #374151;
    cursor: pointer;
}

.btn-primary {
    padding: 0.5rem 1rem;
    background: #3b82f6;
    border: none;
    border-radius: 0.375rem;
    color: white;
    cursor: pointer;
}

/* Icons */
.w-4 {
    width: 1rem;
}

.h-4 {
    height: 1rem;
}

/* Overrides */
.overrides-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.override-item {
    border: 1px solid #e5e7eb;
    border-radius: 0.375rem;
    padding: 1rem;
}

.override-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.75rem;
}

.override-name {
    font-weight: 600;
    font-size: 0.875rem;
}

.override-content label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
    cursor: pointer;
}

/* Table Settings Modal */
.modal.large {
    max-width: 700px;
    max-height: 80vh;
}

.modal-search-box {
    position: relative;
    margin-bottom: 1rem;
}

.modal-search-box .search-icon {
    position: absolute;
    left: 0.75rem;
    top: 50%;
    transform: translateY(-50%);
    width: 1.25rem;
    height: 1.25rem;
    color: #6b7280;
}

.modal-search-box .search-input {
    width: 100%;
    padding: 0.5rem 0.75rem 0.5rem 2.5rem;
    border: 1px solid #d1d5db;
    border-radius: 0.375rem;
    font-size: 0.875rem;
}

.modal-search-box .search-input:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.table-settings-list {
    height: 400px; /* Fixed height instead of max-height */
    overflow-y: auto;
    border: 1px solid #e5e7eb;
    border-radius: 0.375rem;
    padding: 1rem;
    margin-bottom: 1rem;
}

.table-setting-item {
    margin-bottom: 0.75rem;
}

.table-setting-label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    cursor: pointer;
    font-size: 0.875rem;
}

.table-setting-label input[type="checkbox"] {
    cursor: pointer;
}

.table-label {
    color: #6b7280;
    font-size: 0.75rem;
}

/* Additional icon size */
.w-5 {
    width: 1.25rem;
}

.h-5 {
    height: 1.25rem;
}

/* JSON Viewer */
.json-viewer {
    flex: 1;
    padding: 3rem 1rem 1rem; /* Extra top padding for copy button */
    overflow-y: auto;
    background: #f9fafb;
}

.json-viewer pre {
    margin: 0;
    font-family: 'Courier New', monospace;
    font-size: 0.875rem;
    line-height: 1.5;
    white-space: pre-wrap;
    word-wrap: break-word;
}

/* Toggle Switch Styles */
.field-toggle {
    display: flex;
    align-items: center;
}

.toggle-switch {
    position: relative;
    display: inline-block;
    width: 40px;
    height: 22px;
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
    background-color: #ccc;
    transition: .4s;
    border-radius: 34px;
}

.toggle-slider:before {
    position: absolute;
    content: "";
    height: 16px;
    width: 16px;
    left: 3px;
    bottom: 3px;
    background-color: white;
    transition: .4s;
    border-radius: 50%;
}

.toggle-switch input:checked + .toggle-slider {
    background-color: #3b82f6;
}

.toggle-switch input:focus + .toggle-slider {
    box-shadow: 0 0 1px #3b82f6;
}

.toggle-switch input:checked + .toggle-slider:before {
    transform: translateX(18px);
}

/* Disabled field styles */
.field-item.disabled {
    opacity: 0.6;
}

.field-item.disabled .field-properties {
    pointer-events: none;
    opacity: 0.5;
}

/* Disabled subtable field styles */
.subtable-field-item.disabled {
    opacity: 0.6;
}

.subtable-field-item.disabled .field-col,
.subtable-field-item.disabled .field-full {
    pointer-events: none;
    opacity: 0.7;
}

/* Delete table button */
.btn-delete-table {
    padding: 0.25rem 0.5rem;
    background: none;
    border: none;
    color: #ef4444;
    font-size: 1.25rem;
    line-height: 1;
    cursor: pointer;
    transition: color 0.2s;
}

.btn-delete-table:hover {
    color: #dc2626;
}

/* Edit table button */
.btn-edit-table {
    padding: 0.25rem;
    background: none;
    border: none;
    color: #6b7280;
    cursor: pointer;
    transition: color 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
}

.btn-edit-table:hover {
    color: #3b82f6;
}

.table-actions {
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

/* Editor title editing */
.editor-title-wrapper {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.editor-title-input {
    font-size: 1.5rem;
    font-weight: 600;
    margin: 0;
    padding: 0.25rem 0.5rem;
    border: 1px solid #e5e7eb;
    border-radius: 0.375rem;
    background: white;
}

.btn-edit-title {
    padding: 0.25rem;
    background: none;
    border: none;
    color: #6b7280;
    cursor: pointer;
    transition: color 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
}

.btn-edit-title:hover {
    color: #3b82f6;
}

/* Field name editing */
.field-name-wrapper {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.field-name-input {
    font-weight: 600;
    padding: 0.25rem 0.5rem;
    border: 1px solid #e5e7eb;
    border-radius: 0.25rem;
    background: white;
    font-size: inherit;
}

.btn-edit-field-name {
    padding: 0.125rem;
    background: none;
    border: none;
    color: #6b7280;
    cursor: pointer;
    transition: color 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
}

.btn-edit-field-name:hover {
    color: #3b82f6;
}

.w-3 {
    width: 0.75rem;
}

.h-3 {
    height: 0.75rem;
}

/* Required properties */
.required-properties {
    margin-bottom: 1rem;
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

/* Type specific headers */
.type-specific-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.75rem;
}

.type-specific-header label {
    font-weight: 600;
    font-size: 1rem;
    margin: 0;
}

/* Field actions */
.field-actions {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

/* Delete field button */
.btn-delete-field {
    padding: 0.25rem 0.5rem;
    background: none;
    border: none;
    color: #ef4444;
    font-size: 1.25rem;
    line-height: 1;
    cursor: pointer;
    transition: color 0.2s;
}

.btn-delete-field:hover {
    color: #dc2626;
}

/* Order fields */
.order-fields {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.order-field-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.order-field-item select {
    flex: 1;
    padding: 0.5rem;
    border: 1px solid #e5e7eb;
    border-radius: 0.375rem;
    font-size: 0.875rem;
}

/* Disabled states */
.order-fields.disabled {
    opacity: 0.5;
    pointer-events: none;
}

.dropdown-item.disabled {
    color: #9ca3af;
    background: #f9fafb;
    cursor: not-allowed;
    pointer-events: none;
}

.dropdown-item.disabled:hover {
    background: #f9fafb;
}

/* Reset buttons */
.btn-reset-table {
    padding: 0.25rem;
    background: none;
    border: none;
    color: #6b7280;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 0.25rem;
}

.btn-reset-table:hover {
    color: #3b82f6;
    background: #f3f4f6;
    transform: rotate(180deg);
}

.btn-reset-field {
    padding: 0.25rem;
    background: none;
    border: none;
    color: #6b7280;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 0.25rem;
}

.btn-reset-field:hover {
    color: #3b82f6;
    background: #f3f4f6;
    transform: rotate(180deg);
}

/* Section header with reset button */
.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

/* Visual Preview Styles */
.visual-preview {
    height: 100%;
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

.preview-container {
    display: flex;
    height: 100%;
    flex: 1;
    overflow: hidden;
}

/* Table Menu */
.table-menu {
    width: 250px;
    background: #f9fafb;
    border-right: 1px solid #e5e7eb;
    overflow-y: auto;
}

.menu-title {
    font-size: 0.875rem;
    font-weight: 600;
    padding: 1rem;
    margin: 0;
    border-bottom: 1px solid #e5e7eb;
    color: #374151;
}

.table-menu-items {
    padding: 0.5rem;
}

.table-menu-item {
    width: 100%;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.625rem 0.75rem;
    margin-bottom: 0.25rem;
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 0.375rem;
    text-align: left;
    cursor: pointer;
    transition: all 0.2s;
    font-size: 0.875rem;
    position: relative;
}

.table-menu-item:hover {
    background: #f3f4f6;
    border-color: #d1d5db;
}

.table-menu-item.active {
    background: #eff6ff;
    border-color: #3b82f6;
    color: #1e40af;
}

.table-menu-item.disabled {
    opacity: 0.5;
    background: #fee2e2;
}

.table-menu-item.singleton {
    border-style: dashed;
}

.table-menu-item.editor-active {
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.3);
    border-color: #3b82f6;
}

.table-menu-item.editor-active::before {
    content: '';
    position: absolute;
    left: -0.5rem;
    top: 50%;
    transform: translateY(-50%);
    width: 4px;
    height: 60%;
    background: #3b82f6;
    border-radius: 0 2px 2px 0;
}

.menu-icon {
    color: #6b7280;
    flex-shrink: 0;
}

.menu-label {
    flex: 1;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.menu-badge {
    background: #3b82f6;
    color: white;
    padding: 0.125rem 0.25rem;
    border-radius: 0.25rem;
    font-size: 0.625rem;
    font-weight: 600;
}

/* Form Preview */
.form-preview {
    flex: 1;
    overflow-y: auto;
    height: 100%;
    position: relative;
    scroll-behavior: smooth;
}

.preview-content {
    padding: 2rem;
    padding-bottom: 4rem; /* Extra padding at bottom to ensure last fields are fully visible */
}

.preview-title {
    font-size: 1.5rem;
    font-weight: 600;
    margin: 0 0 0.5rem 0;
    color: #111827;
}

.preview-description {
    color: #6b7280;
    margin: 0 0 2rem 0;
    font-size: 0.875rem;
}

.preview-form {
    max-width: 800px;
}

.form-field {
    margin-bottom: 2rem;
}

.form-label {
    display: block;
    font-size: 0.875rem;
    font-weight: 500;
    color: #374151;
    margin-bottom: 0.5rem;
}

.required-star {
    color: #ef4444;
    margin-left: 0.25rem;
}

.field-description {
    font-size: 0.75rem;
    color: #6b7280;
    margin-bottom: 0.5rem;
}

.form-input,
.form-select,
.form-textarea {
    width: 100%;
    padding: 0.625rem 0.875rem;
    border: 1px solid #e5e7eb;
    border-radius: 0.375rem;
    font-size: 0.875rem;
    background: #f9fafb;
    cursor: not-allowed;
}

.form-textarea {
    resize: vertical;
    min-height: 80px;
}

.form-checkbox {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    cursor: not-allowed;
}

.form-checkbox input {
    cursor: not-allowed;
}

/* CKEditor Preview */
.form-ckeditor {
    border: 1px solid #e5e7eb;
    border-radius: 0.375rem;
    overflow: hidden;
}

.ckeditor-toolbar {
    background: #f3f4f6;
    padding: 0.5rem;
    border-bottom: 1px solid #e5e7eb;
    font-size: 0.75rem;
    color: #6b7280;
}

.ckeditor-toolbar span {
    padding: 0.25rem 0.5rem;
    margin: 0 0.125rem;
    background: white;
    border-radius: 0.25rem;
}

.ckeditor-content {
    padding: 1rem;
    min-height: 100px;
    background: #f9fafb;
    color: #6b7280;
}

/* File Upload Preview */
.form-file {
    border: 2px dashed #e5e7eb;
    border-radius: 0.5rem;
    padding: 2rem;
    text-align: center;
    background: #f9fafb;
}

.file-preview {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
    color: #6b7280;
}

/* Subtable Preview */
.form-subtable {
    border: 1px solid #e5e7eb;
    border-radius: 0.375rem;
    overflow: hidden;
}

.subtable-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem;
    background: #f9fafb;
    border-bottom: 1px solid #e5e7eb;
}

.btn-add-subtable {
    padding: 0.25rem 0.75rem;
    background: #eff6ff;
    border: 1px solid #dbeafe;
    border-radius: 0.25rem;
    font-size: 0.75rem;
    color: #3b82f6;
    cursor: not-allowed;
}

.subtable-preview {
    padding: 2rem;
    text-align: center;
    color: #9ca3af;
    font-size: 0.875rem;
}

/* Unknown Field Type */
.form-unknown {
    padding: 1rem;
    background: #f3f4f6;
    border-radius: 0.375rem;
    text-align: center;
    color: #6b7280;
    font-size: 0.875rem;
}

/* Field Info */
.field-info {
    display: flex;
    gap: 0.75rem;
    margin-top: 0.5rem;
    font-size: 0.75rem;
}

.field-code {
    color: #9ca3af;
}

.field-badge {
    padding: 0.125rem 0.5rem;
    border-radius: 0.25rem;
    font-size: 0.625rem;
    font-weight: 500;
}

.field-badge.translate {
    background: #dbeafe;
    color: #1e40af;
}

.field-badge.list {
    background: #d1fae5;
    color: #047857;
}

.field-badge.multiple {
    background: #e9d5ff;
    color: #7c3aed;
}

/* Reference Info */
.reference-info {
    margin-top: 0.25rem;
    font-size: 0.75rem;
    color: #6b7280;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

/* Empty State */
.preview-empty {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 100%;
    color: #9ca3af;
    font-size: 1rem;
}

/* No Options Hint */
.no-options-hint {
    margin-top: 0.25rem;
    font-size: 0.75rem;
    color: #f59e0b;
    font-style: italic;
}

/* Import JSON Section */
.diff-input-section {
    height: 100%;
}

.diff-output-section {
    height: 100%;
    display: flex;
    flex-direction: column;
}

.diff-section-title {
    font-size: 1rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
    color: #374151;
}

.diff-section-description {
    font-size: 0.875rem;
    color: #6b7280;
    margin-bottom: 1rem;
}

.json-import-textarea {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #e5e7eb;
    border-radius: 0.375rem;
    font-family: 'Consolas', 'Monaco', 'Courier New', monospace;
    font-size: 0.875rem;
    background: #f9fafb;
    resize: vertical;
    min-height: 200px;
}

.json-import-textarea:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    background: white;
}

.import-error,
.import-success {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-top: 0.5rem;
    padding: 0.75rem;
    border-radius: 0.375rem;
    font-size: 0.875rem;
}

.import-error {
    background: #fee2e2;
    color: #b91c1c;
}

.import-success {
    background: #d1fae5;
    color: #065f46;
}

.diff-output-section {
    position: relative;
}

.diff-output-section .btn-copy-json {
    position: absolute;
    top: 0;
    right: 0;
}

/* Sub-tabs for Full Diff */
.diff-sub-tabs {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 1rem;
    border-bottom: 1px solid #e5e7eb;
}

.diff-sub-tab {
    padding: 0.75rem 1.5rem;
    background: none;
    border: none;
    border-bottom: 2px solid transparent;
    color: #6b7280;
    cursor: pointer;
    font-size: 0.875rem;
    font-weight: 500;
    transition: all 0.2s;
}

.diff-sub-tab:hover {
    color: #374151;
}

.diff-sub-tab.active {
    color: #3b82f6;
    border-bottom-color: #3b82f6;
}

.diff-sub-content {
    height: calc(100% - 60px);
    overflow-y: auto;
    padding: 1.5rem;
}

.diff-input-section {
    height: 100%;
    display: flex;
    flex-direction: column;
}

.json-import-textarea {
    flex: 1;
}

/* Condition and Override field styles */
.condition-field {
    background: #f0f9ff;
    border-color: #3b82f6;
}

.override-field .subtable-field-header {
    background: #fef3c7;
    border-bottom-color: #fbbf24;
}

/* Override sections */
.override-section {
    margin-bottom: 1.5rem;
}

.override-section-title {
    font-size: 0.875rem;
    font-weight: 600;
    color: #374151;
    margin-bottom: 0.75rem;
    padding-bottom: 0.5rem;
    border-bottom: 1px solid #e5e7eb;
}

/* Override preview */
.override-preview-section {
    margin-top: 1rem;
}

.override-preview-title {
    font-size: 0.75rem;
    font-weight: 600;
    color: #6b7280;
    margin-bottom: 0.5rem;
}

.override-preview-fields {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.override-preview-field {
    font-size: 0.75rem;
    color: #6b7280;
}

.override-preview-fields.compact {
    gap: 0.125rem;
}

.override-preview-field.compact {
    font-size: 0.7rem;
}

/* Select items in visual preview */
.preview-select-items {
    display: flex;
    flex-wrap: wrap;
    gap: 0.2rem;
    margin-top: 0.25rem;
}

.preview-select-item {
    display: inline-block;
    padding: 0.1rem 0.3rem;
    background: #e5e7eb;
    border-radius: 0.25rem;
    font-size: 0.625rem;
    color: #4b5563;
    line-height: 1;
}

/* Override toggle button */
.btn-toggle-preview {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    background: none;
    border: none;
    padding: 0.5rem;
    cursor: pointer;
    color: #6b7280;
    font-size: 0.875rem;
    width: 100%;
    text-align: left;
}

.btn-toggle-preview:hover {
    color: #374151;
}

.rotate-90 {
    transform: rotate(90deg);
}

.transition-transform {
    transition: transform 0.2s;
}

/* Subtable field styles */
.subtable-fields-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    margin-top: 0.75rem;
}

.subtable-field-item {
    border: 1px solid #e5e7eb;
    border-radius: 0.375rem;
    padding: 0.75rem;
}

.subtable-field-header {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0.5rem;
}

.subtable-field-name {
    font-weight: 500;
    color: #374151;
    flex: 1;
}

.subtable-field-type {
    font-size: 0.75rem;
    color: #6b7280;
    background: #f3f4f6;
    padding: 0.125rem 0.5rem;
    border-radius: 0.25rem;
}

.btn-remove-subtable-field {
    background: none;
    border: none;
    color: #ef4444;
    font-size: 1.25rem;
    line-height: 1;
    cursor: pointer;
    padding: 0;
    width: 24px;
    height: 24px;
}

.subtable-field-properties {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 0.75rem;
}

.override-empty {
    text-align: center;
    padding: 2rem;
    color: #6b7280;
}

.override-buttons {
    display: flex;
    gap: 0.5rem;
    justify-content: center;
    margin-top: 1rem;
}

.override-fields-expanded {
    margin-top: 0.75rem;
    padding: 0;
    background: #f9fafb;
    border-radius: 0.375rem;
}

/* Overrides preview */
.overrides-preview {
    background: #f9fafb;
    padding: 1rem;
    border-radius: 0.375rem;
}

.override-preview-empty {
    text-align: center;
    color: #6b7280;
    font-size: 0.875rem;
}

/* Condition and Override sections */
.condition-section {
    background: #f0f9ff;
    padding: 1rem;
    border-radius: 0.375rem;
    margin-bottom: 1rem;
}

.override-section {
    background: #f9fafb;
    padding: 0.75rem;
    border-radius: 0.375rem;
}

.override-section-title {
    font-size: 0.875rem;
    font-weight: 600;
    color: #374151;
    margin-bottom: 0.5rem;
}

.condition-field {
    background: #e0f2fe;
}

.override-field {
    background: #f3f4f6;
}

/* New condition and override field styles */
.condition-field-new,
.override-field-new {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 0.5rem;
    padding: 0.5rem;
    margin-bottom: 0.5rem;
}

.field-row {
    display: flex;
    gap: 0.75rem;
    align-items: flex-end;
}

.field-col {
    flex: 1;
    display: flex;
    flex-direction: column;
}

.field-col-action {
    display: flex;
    align-items: flex-end;
}

.field-full {
    flex: 1;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.field-label {
    font-size: 0.75rem;
    font-weight: 500;
    color: #6b7280;
    margin-bottom: 0.25rem;
}

.field-select,
.field-input {
    width: 100%;
    padding: 0.5rem 0.75rem;
    border: 1px solid #d1d5db;
    border-radius: 0.375rem;
    font-size: 0.875rem;
    background: white;
}

.field-select:focus,
.field-input:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.field-name {
    font-weight: 500;
    color: #111827;
    font-size: 0.875rem;
}

.field-type-badge {
    font-size: 0.75rem;
    color: #6b7280;
    background: #f3f4f6;
    padding: 0.25rem 0.75rem;
    border-radius: 9999px;
    font-weight: 500;
}

.field-type-display {
    padding: 0.5rem 0.75rem;
    background: #f3f4f6;
    border-radius: 0.375rem;
    font-size: 0.875rem;
    color: #4b5563;
    font-weight: 500;
}

.field-checkboxes {
    display: flex;
    gap: 1rem;
    align-items: center;
    height: 100%;
}

.field-checkboxes .checkbox-label {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    font-size: 0.75rem;
    color: #6b7280;
    margin: 0;
}

.btn-remove-field {
    padding: 0.5rem;
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 0.375rem;
    color: #6b7280;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-remove-field:hover {
    background: #fee2e2;
    border-color: #fecaca;
    color: #dc2626;
}

/* Adjust override sections when inside overrides field */
.override-fields-expanded .override-section {
    background: transparent;
    padding: 0;
    margin-bottom: 0;
}

.override-fields-expanded .override-section:first-child {
    margin-top: 0;
}

.override-fields-expanded .override-section:last-child {
    margin-bottom: 0;
}

.override-fields-expanded .subtable-fields-list {
    margin-top: 0;
}

</style>