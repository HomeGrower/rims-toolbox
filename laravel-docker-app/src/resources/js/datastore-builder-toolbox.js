import { createApp } from 'vue';
import DatastoreBuilder from './Components/DatastoreBuilder.vue';

// Wait for DOM to be ready
document.addEventListener('DOMContentLoaded', () => {
    // Create Vue app if the element exists
    const element = document.getElementById('datastore-builder-toolbox-app');
    
    if (element) {
        // Get props from the data attributes
        const props = {
            isToolbox: true, // Enable toolbox-specific features
            masterTemplate: JSON.parse(element.dataset.masterTemplate || '{}'),
            defaultStructure: JSON.parse(element.dataset.defaultStructure || '{}'),
        };
        
        const app = createApp(DatastoreBuilder, props);
        app.mount('#datastore-builder-toolbox-app');
    }
});