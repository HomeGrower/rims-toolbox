<template>
    <div class="datastore-builder">
        <div class="builder-layout">
            <!-- Left Panel: Table Editor -->
            <div class="left-panel">
                <div class="panel-header">
                    <h3 class="panel-title">Tables</h3>
                    <button @click="showTableSettings = !showTableSettings" class="btn-icon settings">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                    <div v-for="(table, tableKey) in filteredTables" :key="tableKey" class="table-item" :class="{ 'disabled': isTableDisabled(tableKey), 'active': selectedTable === tableKey, 'custom': isCustomTable(tableKey) }">
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

            <!-- Import implementation-specific middle and right panels -->
            <slot name="middle-panel" 
                :selectedTable="selectedTable"
                :editingSelectedTableName="editingSelectedTableName"
                :selectedTableNewName="selectedTableNewName"
                :startEditingSelectedTableName="startEditingSelectedTableName"
                :saveSelectedTableName="saveSelectedTableName"
                :isCustomTable="isCustomTable"
                :getTableFields="getTableFields"
                :getSortedFields="getSortedFields"
                :scrollToField="scrollToField"
                :configDiff="configDiff"
            />
            
            <slot name="right-panel"
                :selectedTable="selectedTable"
                :sortedCurrentTables="sortedCurrentTables"
                :isTableDisabled="isTableDisabled"
                :getSortedFields="getSortedFields"
                :configDiff="configDiff"
                :liveEditEnabled="liveEditEnabled"
            />
        </div>
        
        <!-- Modals -->
        <div v-if="showAddTableModal" class="modal-overlay" @click.self="showAddTableModal = false">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 class="modal-title">Add New Table</h3>
                    <button @click="showAddTableModal = false" class="btn-close">&times;</button>
                </div>
                <div class="form-group">
                    <input 
                        v-model="newTableName" 
                        @keyup.enter="addTable"
                        placeholder="Table name (e.g., customTable)" 
                        class="form-input"
                        ref="newTableInput"
                    />
                </div>
                <div class="modal-actions">
                    <button @click="showAddTableModal = false" class="btn btn-secondary">Cancel</button>
                    <button @click="addTable" class="btn btn-primary">Add Table</button>
                </div>
            </div>
        </div>
        
        <div v-if="showTableDeleteModal" class="modal-overlay" @click.self="showTableDeleteModal = false">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 class="modal-title">Delete Table</h3>
                    <button @click="showTableDeleteModal = false" class="btn-close">&times;</button>
                </div>
                <p>Are you sure you want to delete the table "{{ tableToDelete }}"?</p>
                <div class="modal-actions">
                    <button @click="showTableDeleteModal = false" class="btn btn-secondary">Cancel</button>
                    <button @click="deleteTable" class="btn btn-danger">Delete</button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { useDatastoreBuilder } from '../composables/useDatastoreBuilder';
import { nextTick, watch } from 'vue';

export default {
    name: 'DatastoreBuilderRefactored',
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
        }
    },
    setup(props, { emit }) {
        const {
            // State
            selectedTable,
            selectedField,
            editingField,
            newFieldName,
            showFieldModal,
            fieldToDelete,
            tableToDelete,
            showDeleteModal,
            showTableDeleteModal,
            tableSearchQuery,
            showOnlyActiveTables,
            showAddTableModal,
            newTableName,
            editingTableName,
            newTableNameValue,
            editingSelectedTableName,
            selectedTableNewName,
            showTableSettings,
            liveEditEnabled,
            configDiff,
            
            // Computed
            currentTables,
            sortedCurrentTables,
            filteredTables,
            modifiedTables,
            
            // Methods
            isTableDisabled,
            isCustomTable,
            isTableModified,
            selectTable,
            getTableFields,
            getSortedFields,
            scrollToField,
            addTable,
            confirmDeleteTable,
            deleteTable,
            startEditingTableName,
            saveTableName,
            startEditingSelectedTableName,
            saveSelectedTableName,
            initializeSelectedTable
        } = useDatastoreBuilder(props);

        // Initialize configuration if provided
        if (props.configuration && Object.keys(props.configuration).length > 0) {
            // Deep merge the configuration
            if (props.configuration.tables) {
                Object.assign(configDiff.value.tables, props.configuration.tables);
            }
            // Copy other properties
            Object.keys(props.configuration).forEach(key => {
                if (key !== 'tables') {
                    configDiff.value[key] = props.configuration[key];
                }
            });
        }

        // Initialize disabled tables that are not in allowedTables
        if (props.allowedTables && Array.isArray(props.allowedTables) && props.allowedTables.length > 0) {
            const allowedSet = new Set(props.allowedTables);
            if (props.defaultStructure?.tables) {
                Object.keys(props.defaultStructure.tables).forEach(tableKey => {
                    if (!allowedSet.has(tableKey)) {
                        if (!configDiff.value.tables[tableKey]) {
                            configDiff.value.tables[tableKey] = {};
                        }
                        configDiff.value.tables[tableKey].disabled = true;
                    }
                });
            }
        }

        // Watch for modal opening to focus input
        watch(showAddTableModal, (newVal) => {
            if (newVal) {
                nextTick(() => {
                    const input = document.querySelector('input[ref="newTableInput"]');
                    if (input) input.focus();
                });
            }
        });

        // Emit configuration changes
        watch(configDiff, (newVal) => {
            emit('update:configuration', newVal);
        }, { deep: true });

        return {
            // State
            selectedTable,
            selectedField,
            editingField,
            newFieldName,
            showFieldModal,
            fieldToDelete,
            tableToDelete,
            showDeleteModal,
            showTableDeleteModal,
            tableSearchQuery,
            showOnlyActiveTables,
            showAddTableModal,
            newTableName,
            editingTableName,
            newTableNameValue,
            editingSelectedTableName,
            selectedTableNewName,
            showTableSettings,
            liveEditEnabled,
            configDiff,
            
            // Computed
            currentTables,
            sortedCurrentTables,
            filteredTables,
            modifiedTables,
            
            // Methods
            isTableDisabled,
            isCustomTable,
            isTableModified,
            selectTable,
            getTableFields,
            getSortedFields,
            scrollToField,
            addTable,
            confirmDeleteTable,
            deleteTable,
            startEditingTableName,
            saveTableName,
            startEditingSelectedTableName,
            saveSelectedTableName,
            initializeSelectedTable
        };
    }
};
</script>

<style scoped>
@import '../../css/datastore-builder-shared.css';
</style>