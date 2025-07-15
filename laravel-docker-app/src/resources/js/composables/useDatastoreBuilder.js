import { ref, computed, watch, nextTick } from 'vue';

export function useDatastoreBuilder(props) {
    // State
    const selectedTable = ref('');
    const selectedField = ref(null);
    const editingField = ref(null);
    const newFieldName = ref('');
    const showFieldModal = ref(false);
    const fieldToDelete = ref(null);
    const tableToDelete = ref(null);
    const showDeleteModal = ref(false);
    const showTableDeleteModal = ref(false);
    const tableSearchQuery = ref('');
    const showOnlyActiveTables = ref(false);
    const showAddTableModal = ref(false);
    const newTableName = ref('');
    const editingTableName = ref(null);
    const newTableNameValue = ref('');
    const editingSelectedTableName = ref(false);
    const selectedTableNewName = ref('');
    const showTableSettings = ref(false);
    const liveEditEnabled = ref(true); // Default to true for better UX
    const configDiff = ref({ tables: {} });

    // Computed properties
    const currentTables = computed(() => {
        const tables = {};
        
        if (props.defaultStructure?.tables) {
            Object.keys(props.defaultStructure.tables).forEach(tableKey => {
                const defaultTable = props.defaultStructure.tables[tableKey];
                const diffTable = configDiff.value.tables[tableKey] || {};
                
                tables[tableKey] = {
                    ...defaultTable,
                    ...diffTable,
                    disabled: diffTable.disabled !== undefined ? diffTable.disabled : (defaultTable.disabled || false)
                };
            });
        }
        
        if (configDiff.value.tables) {
            Object.keys(configDiff.value.tables).forEach(tableKey => {
                if (!tables[tableKey] && !configDiff.value.tables[tableKey].deleted) {
                    tables[tableKey] = configDiff.value.tables[tableKey];
                }
            });
        }
        
        return tables;
    });

    const sortedCurrentTables = computed(() => {
        const sorted = {};
        Object.keys(currentTables.value).sort().forEach(key => {
            sorted[key] = currentTables.value[key];
        });
        return sorted;
    });

    const filteredTables = computed(() => {
        let tables = sortedCurrentTables.value;
        
        if (tableSearchQuery.value) {
            const query = tableSearchQuery.value.toLowerCase();
            tables = Object.fromEntries(
                Object.entries(tables).filter(([key]) => 
                    key.toLowerCase().includes(query)
                )
            );
        }
        
        if (showOnlyActiveTables.value) {
            tables = Object.fromEntries(
                Object.entries(tables).filter(([key, table]) => 
                    !table.disabled && !table.deleted
                )
            );
        }
        
        return tables;
    });

    // Track which tables have modifications
    const modifiedTables = computed(() => {
        const modified = new Set();
        
        if (configDiff.value.tables) {
            Object.keys(configDiff.value.tables).forEach(tableKey => {
                const tableDiff = configDiff.value.tables[tableKey];
                
                // Check if table has any modifications
                if (tableDiff.fields && Object.keys(tableDiff.fields).length > 0) {
                    modified.add(tableKey);
                } else if (Object.keys(tableDiff).some(key => key !== 'fields' && key !== 'disabled')) {
                    modified.add(tableKey);
                }
            });
        }
        
        return modified;
    });

    // Methods
    const isTableDisabled = (tableKey) => {
        const table = currentTables.value[tableKey];
        return table && (table.disabled || table.deleted);
    };

    const isCustomTable = (tableKey) => {
        return !props.defaultStructure?.tables?.[tableKey];
    };

    const isTableModified = (tableKey) => {
        return modifiedTables.value.has(tableKey);
    };

    const selectTable = (tableKey) => {
        if (!isTableDisabled(tableKey)) {
            selectedTable.value = tableKey;
            selectedField.value = null;
            editingField.value = null;
        }
    };

    const getTableFields = (tableKey) => {
        const table = currentTables.value[tableKey];
        if (!table || !table.fields) return {};
        
        const masterFields = props.masterTemplate?.tables?.[tableKey]?.fields || {};
        const defaultFields = props.defaultStructure?.tables?.[tableKey]?.fields || {};
        const diffFields = configDiff.value.tables?.[tableKey]?.fields || {};
        
        const fields = {};
        
        const allFieldKeys = new Set([
            ...Object.keys(masterFields),
            ...Object.keys(defaultFields),
            ...Object.keys(diffFields)
        ]);
        
        allFieldKeys.forEach(fieldKey => {
            const masterField = masterFields[fieldKey] || {};
            const defaultField = defaultFields[fieldKey] || {};
            const diffField = diffFields[fieldKey] || {};
            
            fields[fieldKey] = {
                ...masterField,
                ...defaultField,
                ...diffField
            };
        });
        
        return fields;
    };

    const getSortedFields = (tableKey) => {
        const fields = getTableFields(tableKey);
        if (!fields || Object.keys(fields).length === 0) return {};
        
        const fieldKeys = Object.keys(fields).filter(key => !fields[key].disabled);
        const sortedKeys = [];
        const visited = new Set();
        
        const addField = (fieldKey) => {
            if (visited.has(fieldKey)) return;
            visited.add(fieldKey);
            
            sortedKeys.push(fieldKey);
            
            fieldKeys.forEach(otherKey => {
                if (fields[otherKey]._insert_after === fieldKey && !visited.has(otherKey)) {
                    addField(otherKey);
                }
            });
        };
        
        fieldKeys.forEach(fieldKey => {
            const insertAfter = fields[fieldKey]._insert_after;
            if (!insertAfter || !fields[insertAfter] || fields[insertAfter].disabled) {
                addField(fieldKey);
            }
        });
        
        const sortedFields = {};
        sortedKeys.forEach(key => {
            sortedFields[key] = fields[key];
        });
        
        return sortedFields;
    };

    const scrollToField = (fieldKey) => {
        nextTick(() => {
            const container = document.querySelector('.visual-preview .preview-content');
            const fieldElement = document.querySelector(`.field-preview[data-field-key="${fieldKey}"]`);
            
            if (container && fieldElement) {
                const containerRect = container.getBoundingClientRect();
                const fieldRect = fieldElement.getBoundingClientRect();
                
                const scrollTop = container.scrollTop + fieldRect.top - containerRect.top - 50;
                
                container.scrollTo({
                    top: scrollTop,
                    behavior: 'smooth'
                });
                
                fieldElement.classList.add('highlight-field');
                setTimeout(() => {
                    fieldElement.classList.remove('highlight-field');
                }, 2000);
            }
        });
    };

    const addTable = () => {
        if (newTableName.value.trim()) {
            const tableKey = newTableName.value.trim();
            if (!configDiff.value.tables) {
                configDiff.value.tables = {};
            }
            configDiff.value.tables[tableKey] = {
                fields: {},
                disabled: false
            };
            selectedTable.value = tableKey;
            newTableName.value = '';
            showAddTableModal.value = false;
        }
    };

    const confirmDeleteTable = (tableKey) => {
        tableToDelete.value = tableKey;
        showTableDeleteModal.value = true;
    };

    const deleteTable = () => {
        if (tableToDelete.value) {
            if (!configDiff.value.tables) {
                configDiff.value.tables = {};
            }
            
            if (isCustomTable(tableToDelete.value)) {
                configDiff.value.tables[tableToDelete.value] = { deleted: true };
            } else {
                if (!configDiff.value.tables[tableToDelete.value]) {
                    configDiff.value.tables[tableToDelete.value] = {};
                }
                configDiff.value.tables[tableToDelete.value].disabled = true;
            }
            
            if (selectedTable.value === tableToDelete.value) {
                selectedTable.value = '';
            }
            
            tableToDelete.value = null;
            showTableDeleteModal.value = false;
        }
    };

    const startEditingTableName = (tableKey) => {
        editingTableName.value = tableKey;
        newTableNameValue.value = tableKey;
        nextTick(() => {
            const input = document.querySelector('.table-name-input');
            if (input) input.focus();
        });
    };

    const saveTableName = () => {
        if (editingTableName.value && newTableNameValue.value.trim() && newTableNameValue.value !== editingTableName.value) {
            const oldKey = editingTableName.value;
            const newKey = newTableNameValue.value.trim();
            
            if (!configDiff.value.tables) {
                configDiff.value.tables = {};
            }
            
            const tableData = configDiff.value.tables[oldKey] || { fields: {} };
            configDiff.value.tables[oldKey] = { deleted: true };
            configDiff.value.tables[newKey] = tableData;
            
            if (selectedTable.value === oldKey) {
                selectedTable.value = newKey;
            }
        }
        editingTableName.value = null;
        newTableNameValue.value = '';
    };

    const startEditingSelectedTableName = () => {
        editingSelectedTableName.value = true;
        selectedTableNewName.value = selectedTable.value;
        nextTick(() => {
            const input = document.querySelector('.editor-title-input');
            if (input) input.focus();
        });
    };

    const saveSelectedTableName = () => {
        if (selectedTableNewName.value.trim() && selectedTableNewName.value !== selectedTable.value) {
            const oldKey = selectedTable.value;
            const newKey = selectedTableNewName.value.trim();
            
            if (!configDiff.value.tables) {
                configDiff.value.tables = {};
            }
            
            const tableData = configDiff.value.tables[oldKey] || { fields: {} };
            configDiff.value.tables[oldKey] = { deleted: true };
            configDiff.value.tables[newKey] = tableData;
            
            selectedTable.value = newKey;
        }
        editingSelectedTableName.value = false;
        selectedTableNewName.value = '';
    };

    const initializeSelectedTable = () => {
        if (!selectedTable.value && Object.keys(currentTables.value).length > 0) {
            const firstEnabledTable = Object.entries(currentTables.value)
                .find(([key, table]) => !isTableDisabled(key));
            if (firstEnabledTable) {
                selectedTable.value = firstEnabledTable[0];
            }
        }
    };

    // Watchers
    watch(() => props.defaultStructure, () => {
        initializeSelectedTable();
    }, { immediate: true });

    watch([selectedTable, selectedField, editingField], () => {
        if (liveEditEnabled.value && selectedTable.value) {
            nextTick(() => {
                const tableElement = document.querySelector(`.preview-table[data-table-key="${selectedTable.value}"]`);
                if (tableElement) {
                    tableElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    
                    document.querySelectorAll('.preview-table').forEach(el => {
                        el.classList.remove('active-table');
                    });
                    tableElement.classList.add('active-table');
                    
                    if (selectedField.value || editingField.value) {
                        const fieldKey = editingField.value || selectedField.value;
                        scrollToField(fieldKey);
                    }
                }
            });
        }
    });

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