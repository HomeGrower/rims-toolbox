import { createApp } from 'vue';

// Make Vue createApp available globally for Filament pages if needed
window.Vue = { createApp };

// Note: DatastoreBuilder is now loaded separately via datastore-builder.js
// This prevents double mounting and allows proper HMR