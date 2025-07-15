<template>
    <DatastoreBuilderRefactored v-bind="$props" @update:configuration="$emit('update:configuration', $event)">
        <!-- Middle Panel slot -->
        <template #middle-panel="slotProps">
            <div class="middle-panel">
                <div class="tabs-container">
                    <div class="tabs-nav">
                        <button 
                            v-for="tab in middlePanelTabs" 
                            :key="tab.key"
                            @click="activeMiddleTab = tab.key"
                            :class="['tab-button', { active: activeMiddleTab === tab.key }]"
                        >
                            {{ tab.label }}
                        </button>
                    </div>
                    
                    <div class="tab-content">
                        <!-- Implementation specific content -->
                        <component 
                            :is="getMiddlePanelComponent(activeMiddleTab)"
                            v-bind="slotProps"
                            :configDiff="slotProps.configDiff"
                            @update-field="updateField"
                            @add-field="addField"
                            @delete-field="deleteField"
                        />
                    </div>
                </div>
            </div>
        </template>
        
        <!-- Right Panel slot -->
        <template #right-panel="slotProps">
            <div class="right-panel">
                <div class="tabs-container">
                    <div class="tabs-nav">
                        <button 
                            v-for="tab in rightPanelTabs" 
                            :key="tab.key"
                            @click="activeRightTab = tab.key"
                            :class="['tab-button', { active: activeRightTab === tab.key }]"
                        >
                            {{ tab.label }}
                        </button>
                    </div>
                    
                    <div class="tab-content">
                        <!-- Visual Preview -->
                        <DatastoreVisualPreview
                            v-if="activeRightTab === 'visual'"
                            :sortedTables="slotProps.sortedCurrentTables"
                            :selectedTable="slotProps.selectedTable"
                            :isTableDisabled="slotProps.isTableDisabled"
                            :getSortedFields="slotProps.getSortedFields"
                            :liveEditEnabled="slotProps.liveEditEnabled"
                            :defaultStructure="defaultStructure"
                            @update:liveEditEnabled="slotProps.liveEditEnabled = $event"
                            @reset-field="resetField"
                        />
                        
                        <!-- JSON tabs -->
                        <component
                            v-else
                            :is="getRightPanelComponent(activeRightTab)"
                            :configDiff="slotProps.configDiff"
                            :currentTables="slotProps.sortedCurrentTables"
                        />
                    </div>
                </div>
            </div>
        </template>
    </DatastoreBuilderRefactored>
</template>

<script>
import { ref } from 'vue';
import DatastoreBuilderRefactored from './DatastoreBuilderRefactored.vue';
import DatastoreVisualPreview from './DatastoreVisualPreview.vue';
import DatastoreFieldEditor from './DatastoreFieldEditor.vue';

export default {
    name: 'DatastoreBuilderShared',
    components: {
        DatastoreBuilderRefactored,
        DatastoreVisualPreview,
        DatastoreFieldEditor
    },
    props: {
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
        },
        isToolBox: {
            type: Boolean,
            default: false
        }
    },
    emits: ['update:configuration'],
    setup(props) {
        const activeMiddleTab = ref('editor');
        const activeRightTab = ref('visual');
        
        const middlePanelTabs = props.isToolBox ? [
            { key: 'editor', label: 'Editor' },
            { key: 'properties', label: 'Properties' }
        ] : [
            { key: 'editor', label: 'Editor' },
            { key: 'fields', label: 'Fields' }
        ];
        
        const rightPanelTabs = props.isToolBox ? [
            { key: 'visual', label: 'Visual' },
            { key: 'diff', label: 'Full Diff' }
        ] : [
            { key: 'visual', label: 'Visual' },
            { key: 'table', label: 'Table Diff' },
            { key: 'diff', label: 'Diff Only' }
        ];
        
        const updateField = (data) => {
            // Implementation for updating fields
            console.log('Update field:', data);
        };
        
        const addField = (tableKey) => {
            // Implementation for adding fields
            console.log('Add field to table:', tableKey);
        };
        
        const deleteField = (tableKey, fieldKey) => {
            // Implementation for deleting fields
            console.log('Delete field:', tableKey, fieldKey);
        };
        
        const resetField = (tableKey, fieldKey) => {
            // Implementation for resetting fields
            console.log('Reset field:', tableKey, fieldKey);
        };
        
        const getMiddlePanelComponent = (tab) => {
            // Return appropriate component based on tab
            // This would be implementation-specific
            return 'div'; // Placeholder
        };
        
        const getRightPanelComponent = (tab) => {
            // Return appropriate component based on tab
            // This would be implementation-specific
            return 'div'; // Placeholder
        };
        
        return {
            activeMiddleTab,
            activeRightTab,
            middlePanelTabs,
            rightPanelTabs,
            updateField,
            addField,
            deleteField,
            resetField,
            getMiddlePanelComponent,
            getRightPanelComponent
        };
    }
};
</script>

<style scoped>
@import '../../css/datastore-builder-shared.css';
</style>