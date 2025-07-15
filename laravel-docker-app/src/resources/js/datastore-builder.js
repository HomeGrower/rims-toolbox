import { createApp } from 'vue';
import DatastoreBuilder from './Components/DatastoreBuilder.vue';

// Wait for DOM to be ready
document.addEventListener('DOMContentLoaded', () => {
    // Create Vue app if the element exists
    const element = document.getElementById('datastore-builder-app');
    
    if (element) {
        // Get props from the data attributes
        const props = {
            projectId: parseInt(element.dataset.projectId || '0'),
            masterTemplate: JSON.parse(element.dataset.masterTemplate || '{}'),
            defaultStructure: JSON.parse(element.dataset.defaultStructure || '{}'),
            configuration: JSON.parse(element.dataset.configuration || '{}'),
            projectModules: JSON.parse(element.dataset.projectModules || '{}'),
            chainCode: JSON.parse(element.dataset.chainCode || 'null'),
            allowedTables: JSON.parse(element.dataset.allowedTables || '[]'),
        };
        
        const app = createApp(DatastoreBuilder, props);
        app.mount('#datastore-builder-app');
    }
});