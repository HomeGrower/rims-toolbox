<script setup>
import { ref } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';

const logoutForm = useForm({});

const logout = () => {
    logoutForm.post(route('code.logout'));
};

const page = usePage();
const brandLogo = page.props.app?.brand_logo;
const rimsLogo = page.props.app?.rims_logo;
const logoToDisplay = brandLogo || rimsLogo;

// Check if we're on a page that should show the sidebar
const showSidebar = ref(false);
const currentRoute = page.url;
if (currentRoute.includes('/greeting-texts')) {
    showSidebar.value = true;
}
</script>

<template>
    <div>
        <div class="min-h-screen bg-gray-100 dark:bg-gray-900">
            <nav class="border-b border-gray-100 bg-white dark:border-gray-700 dark:bg-gray-800">
                <!-- Primary Navigation Menu -->
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="flex h-16 justify-between">
                        <div class="flex">
                            <!-- Logo -->
                            <div class="flex shrink-0 items-center">
                                <Link :href="route('client.dashboard')">
                                    <img 
                                        v-if="logoToDisplay" 
                                        :src="logoToDisplay" 
                                        alt="Logo"
                                        class="block h-9 w-auto"
                                    />
                                    <ApplicationLogo
                                        v-else
                                        class="block h-9 w-auto fill-current text-gray-800 dark:text-gray-200"
                                    />
                                </Link>
                            </div>

                            
                            <!-- Page Title Slot -->
                            <div v-if="$slots.pageTitle" class="hidden sm:flex sm:items-center sm:ms-6">
                                <slot name="pageTitle" />
                            </div>
                        </div>

                        <div class="hidden sm:ms-6 sm:flex sm:items-center space-x-8">
                            <!-- Page Actions Slot -->
                            <div v-if="$slots.pageActions" class="flex items-center space-x-2">
                                <slot name="pageActions" />
                            </div>

                            <!-- Logout Button -->
                            <button
                                @click="logout"
                                class="inline-flex items-center px-4 py-2 bg-gray-800 dark:bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-white dark:text-gray-800 uppercase tracking-widest hover:bg-gray-700 dark:hover:bg-white focus:bg-gray-700 dark:focus:bg-white active:bg-gray-900 dark:active:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150"
                            >
                                Logout
                            </button>
                        </div>

                        <!-- Mobile Logout Button -->
                        <div class="-me-2 flex items-center sm:hidden">
                            <button
                                @click="logout"
                                class="inline-flex items-center justify-center rounded-md p-2 text-gray-400 transition duration-150 ease-in-out hover:bg-gray-100 hover:text-gray-500 focus:bg-gray-100 focus:text-gray-500 focus:outline-none dark:text-gray-500 dark:hover:bg-gray-900 dark:hover:text-gray-400 dark:focus:bg-gray-900 dark:focus:text-gray-400"
                            >
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

            </nav>


            <!-- Page Content -->
            <div class="flex">
                <!-- Sidebar -->
                <aside v-if="showSidebar" class="w-64 bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700 min-h-screen">
                    <nav class="p-4">
                        <div class="space-y-2">
                            <Link
                                :href="route('client.dashboard')"
                                class="block px-4 py-2 text-sm font-medium rounded-md"
                                :class="route().current('client.dashboard') ? 'bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white'"
                            >
                                Dashboard
                            </Link>
                            <Link
                                :href="route('client.greeting-texts.index')"
                                class="block px-4 py-2 text-sm font-medium rounded-md"
                                :class="route().current('client.greeting-texts.*') ? 'bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white'"
                            >
                                Greeting Texts
                            </Link>
                        </div>
                    </nav>
                </aside>
                
                <!-- Main Content -->
                <main class="flex-1">
                    <slot />
                </main>
            </div>
        </div>
    </div>
</template>