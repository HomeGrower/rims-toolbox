<template>
    <ClientLayout>
        <Head title="Greetings" />

        <template #pageTitle>
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                Greetings Builder
            </h2>
        </template>

        <template #pageActions>
            <button @click="saveAllChanges"
                    :disabled="isSaving || !hasUnsavedChanges"
                    class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 active:bg-green-900 focus:outline-none focus:border-green-900 focus:ring ring-green-300 disabled:opacity-25 transition ease-in-out duration-150">
                <svg v-if="!isSaving" class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <svg v-else class="animate-spin h-4 w-4 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                {{ isSaving ? 'Saving...' : 'Save' }}
            </button>
            <button @click="handleBack"
                    class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 active:bg-gray-900 focus:outline-none focus:border-gray-900 focus:ring ring-gray-300 disabled:opacity-25 transition ease-in-out duration-150">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Back
            </button>
        </template>

        <div class="fixed inset-0 top-16 flex overflow-hidden">
            <!-- Left Panel - Paragraph Builder -->
            <div class="w-1/2 bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700 flex flex-col">
                <!-- Sticky Header -->
                <div class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 px-6 py-4 sticky top-0 z-20">
                    <div class="flex justify-between items-center">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Paragraphs</h3>
                        <div class="flex items-center space-x-2">
                            <!-- Filter Dropdown -->
                            <div class="relative" @click.stop>
                                <button @click="showFilterDropdown = !showFilterDropdown"
                                        class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-sm font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                                    </svg>
                                    {{ filterMode === 'all' ? 'All' : props.modules.find(m => m.id == filterMode)?.name || 'Filter' }}
                                </button>
                                
                                <!-- Dropdown Menu -->
                                <div v-if="showFilterDropdown" 
                                     class="absolute right-0 mt-2 w-48 rounded-md shadow-lg bg-white dark:bg-gray-700 ring-1 ring-black ring-opacity-5 z-10">
                                    <div class="py-1">
                                        <button @click="filterMode = 'all'; showFilterDropdown = false"
                                                :class="[
                                                    'block w-full text-left px-4 py-2 text-sm',
                                                    filterMode === 'all' 
                                                        ? 'bg-gray-100 dark:bg-gray-600 text-gray-900 dark:text-gray-100' 
                                                        : 'text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-600'
                                                ]">
                                            All Paragraphs
                                        </button>
                                        <div class="border-t border-gray-100 dark:border-gray-600"></div>
                                        <button v-for="module in props.modules"
                                                :key="module.id"
                                                @click="filterMode = module.id; showFilterDropdown = false"
                                                :class="[
                                                    'block w-full text-left px-4 py-2 text-sm',
                                                    filterMode == module.id 
                                                        ? 'bg-gray-100 dark:bg-gray-600 text-gray-900 dark:text-gray-100' 
                                                        : 'text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-600'
                                                ]">
                                            {{ module.name }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Add New Paragraph Dropdown -->
                            <div class="relative" @click.stop>
                                <button @click="showAddParagraphDropdown = !showAddParagraphDropdown"
                                        class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:border-indigo-900 focus:ring ring-indigo-300 disabled:opacity-25 transition ease-in-out duration-150">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                    </svg>
                                    Add New Paragraph
                                    <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </button>
                                
                                <!-- Dropdown Menu -->
                                <div v-if="showAddParagraphDropdown" 
                                     class="absolute right-0 mt-2 w-64 rounded-md shadow-lg bg-white dark:bg-gray-700 ring-1 ring-black ring-opacity-5 z-10">
                                    <div class="p-4">
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">
                                            Select Paragraph Number
                                        </label>
                                        <div class="grid grid-cols-2 gap-2">
                                            <button v-for="num in 10"
                                                    :key="num"
                                                    @click="addNewParagraph(num)"
                                                    class="px-3 py-2 text-sm text-gray-700 dark:text-gray-200 bg-gray-100 dark:bg-gray-600 hover:bg-gray-200 dark:hover:bg-gray-500 rounded-md transition-colors">
                                                Paragraph {{ num }}
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Scrollable Content -->
                <div class="flex-1 overflow-y-auto p-6">
                    <!-- Paragraph Groups -->
                    <div class="space-y-4">
                        <div v-for="group in groupedParagraphs" 
                             :key="`group-${group.number}`"
                             class="border rounded-lg dark:border-gray-700 bg-gray-50 dark:bg-gray-900">
                            <!-- Group Header -->
                            <div class="p-4 bg-white dark:bg-gray-800 border-b dark:border-gray-700">
                                <h4 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                    Paragraph {{ group.number }}
                                </h4>
                            </div>
                            
                            <!-- Paragraphs within this group -->
                            <div v-for="(paragraph, pIndex) in group.paragraphs" 
                                 :key="paragraph.id || `new-${localParagraphs.indexOf(paragraph)}`"
                                 :class="{ 'border-t dark:border-gray-700': pIndex > 0 }">
                                 
                                <!-- Individual Paragraph Header -->
                                <div @click="toggleParagraph(paragraph.id || `new-${localParagraphs.indexOf(paragraph)}`)"
                                     class="p-4 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors bg-white dark:bg-gray-800">
                                    <div class="flex justify-between items-start">
                                        <div class="flex items-start space-x-3 flex-1">
                                            <svg :class="{ 'rotate-90': expandedParagraphs.includes(paragraph.id || `new-${localParagraphs.indexOf(paragraph)}`) }"
                                                 class="w-4 h-4 mt-1 transition-transform text-gray-600 dark:text-gray-400 flex-shrink-0"
                                                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                            </svg>
                                            <div class="flex-1">
                                                <span class="font-medium text-gray-900 dark:text-gray-100">
                                                    Priority {{ paragraph.priority || '?' }}
                                                </span>
                                                <div class="flex items-center flex-wrap gap-2 mt-1">
                                                <span v-if="(!paragraph.modules || paragraph.modules.length === 0) && paragraph.content" 
                                                      class="text-xs text-yellow-600 dark:text-yellow-400 flex items-center">
                                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                                                    </svg>
                                                    No module
                                                </span>
                                                
                                                <!-- Show modules as badges -->
                                                <span v-for="moduleId in (paragraph.modules || [])" 
                                                      :key="`module-${moduleId}`"
                                                      :class="getModuleBadgeClass(moduleId)"
                                                      class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium">
                                                    {{ props.modules.find(m => m.id == moduleId)?.name || 'Unknown' }}
                                                </span>
                                                
                                                <!-- Show "Show if" conditions as badges -->
                                                <span v-for="conditionId in (paragraph.show_if_conditions || [])" 
                                                      :key="`show-${conditionId}`"
                                                      class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                                    </svg>
                                                    {{ conditions.find(c => c.id == conditionId)?.name || 'Unknown' }}
                                                </span>
                                                
                                                <!-- Show "Hide if" conditions as badges -->
                                                <span v-for="conditionId in (paragraph.hide_if_conditions || [])" 
                                                      :key="`hide-${conditionId}`"
                                                      class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                                    </svg>
                                                    {{ conditions.find(c => c.id == conditionId)?.name || 'Unknown' }}
                                                </span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="flex space-x-2 flex-shrink-0 ml-4">
                                            <button @click.stop="deleteParagraph(paragraph.id || `new-${localParagraphs.indexOf(paragraph)}`, localParagraphs.indexOf(paragraph))" 
                                                    class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Paragraph Content (Collapsible) -->
                                <div v-show="expandedParagraphs.includes(paragraph.id || `new-${localParagraphs.indexOf(paragraph)}`)"
                                     class="p-4 bg-gray-50 dark:bg-gray-900 space-y-4">
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                            Paragraph Number
                                        </label>
                                        <select v-model="paragraph.paragraph_number"
                                                @change="handleParagraphChange(paragraph, localParagraphs.indexOf(paragraph))"
                                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200">
                                            <option v-for="num in 10" :key="num" :value="num">{{ num }}</option>
                                        </select>
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                            Priority (1 = highest)
                                        </label>
                                        <select v-model="paragraph.priority"
                                                @change="handleParagraphChange(paragraph, localParagraphs.indexOf(paragraph))"
                                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200">
                                            <option v-for="num in 10" :key="num" :value="num">{{ num }}</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Content
                                    </label>
                                    <textarea v-model="paragraph.content"
                                              @input="handleParagraphChange(paragraph, localParagraphs.indexOf(paragraph))"
                                              rows="3"
                                              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200 dark:placeholder-gray-400"
                                              placeholder="Enter paragraph content..."></textarea>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Apply to Modules
                                        <span class="text-xs text-gray-500 dark:text-gray-400 ml-2">(Email templates only)</span>
                                    </label>
                                    
                                    <!-- Warning if no modules selected -->
                                    <div v-if="(!paragraph.modules || paragraph.modules.length === 0) && paragraph.content" 
                                         class="mb-2 text-sm text-yellow-600 dark:text-yellow-400 flex items-center">
                                        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                                        </svg>
                                        No modules selected - will appear in no templates
                                    </div>
                                    
                                    <!-- Selected modules as badges -->
                                    <div v-if="paragraph.modules && paragraph.modules.length > 0" class="flex flex-wrap gap-2 mb-2">
                                        <span v-for="moduleId in paragraph.modules" 
                                              :key="moduleId"
                                              :class="getModuleBadgeClass(moduleId)"
                                              class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium">
                                            {{ props.modules.find(m => m.id == moduleId)?.name || 'Unknown' }}
                                            <button @click="removeModule(paragraph, moduleId, localParagraphs.indexOf(paragraph))"
                                                    type="button"
                                                    class="ml-1 inline-flex items-center justify-center w-4 h-4 opacity-60 hover:opacity-100">
                                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                                                </svg>
                                            </button>
                                        </span>
                                    </div>
                                    
                                    <!-- Dropdown to add modules -->
                                    <select @change="addModule($event, paragraph, localParagraphs.indexOf(paragraph))"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200">
                                        <option value="">Add a module...</option>
                                        <option v-for="module in availableModules(paragraph.modules)" 
                                                :key="module.id" 
                                                :value="module.id">
                                            {{ module.name }}
                                        </option>
                                    </select>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Show If Conditions
                                    </label>
                                    
                                    <!-- Selected conditions as badges -->
                                    <div v-if="paragraph.show_if_conditions && paragraph.show_if_conditions.length > 0" class="flex flex-wrap gap-2 mb-2">
                                        <span v-for="conditionId in paragraph.show_if_conditions" 
                                              :key="conditionId"
                                              class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                            {{ conditions.find(c => c.id == conditionId)?.name || 'Unknown' }}
                                            <button @click="removeShowCondition(paragraph, conditionId, localParagraphs.indexOf(paragraph))"
                                                    type="button"
                                                    class="ml-1 inline-flex items-center justify-center w-4 h-4 text-green-400 hover:text-green-600 dark:text-green-300 dark:hover:text-green-100">
                                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                                                </svg>
                                            </button>
                                        </span>
                                    </div>
                                    
                                    <!-- Dropdown to add conditions -->
                                    <select @change="addShowCondition($event, paragraph, localParagraphs.indexOf(paragraph))"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200">
                                        <option value="">Add a condition...</option>
                                        <option v-for="condition in availableShowConditions(paragraph.show_if_conditions)" 
                                                :key="condition.id" 
                                                :value="condition.id">
                                            {{ condition.name }}
                                        </option>
                                    </select>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Hide If Conditions
                                    </label>
                                    
                                    <!-- Selected conditions as badges -->
                                    <div v-if="paragraph.hide_if_conditions && paragraph.hide_if_conditions.length > 0" class="flex flex-wrap gap-2 mb-2">
                                        <span v-for="conditionId in paragraph.hide_if_conditions" 
                                              :key="conditionId"
                                              class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                                            {{ conditions.find(c => c.id == conditionId)?.name || 'Unknown' }}
                                            <button @click="removeHideCondition(paragraph, conditionId, localParagraphs.indexOf(paragraph))"
                                                    type="button"
                                                    class="ml-1 inline-flex items-center justify-center w-4 h-4 text-red-400 hover:text-red-600 dark:text-red-300 dark:hover:text-red-100">
                                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                                                </svg>
                                            </button>
                                        </span>
                                    </div>
                                    
                                    <!-- Dropdown to add conditions -->
                                    <select @change="addHideCondition($event, paragraph, localParagraphs.indexOf(paragraph))"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200">
                                        <option value="">Add a condition...</option>
                                        <option v-for="condition in availableHideConditions(paragraph.hide_if_conditions)" 
                                                :key="condition.id" 
                                                :value="condition.id">
                                            {{ condition.name }}
                                        </option>
                                    </select>
                                </div>
                                
                                <div class="flex justify-end">
                                    <button @click="deleteParagraph(paragraph.id || `new-${localParagraphs.indexOf(paragraph)}`, localParagraphs.indexOf(paragraph))"
                                            class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300 font-medium text-sm">
                                        Delete Paragraph
                                    </button>
                                </div>
                                </div> <!-- End collapsible content -->
                            </div> <!-- End individual paragraph -->
                        </div> <!-- End group -->
                    </div> <!-- End groups container -->
                </div>
            </div>
            
            <!-- Right Panel - Preview -->
            <div class="w-1/2 bg-gray-50 dark:bg-gray-900 overflow-hidden flex flex-col">
                <div class="p-6 h-full flex flex-col overflow-hidden">
                    <!-- Preview Controls -->
                    <div class="mb-6 space-y-4">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Preview</h3>
                        
                        <!-- Module Selection -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Select Template/Module
                            </label>
                            <div class="flex flex-wrap gap-2">
                                <button v-for="module in props.modules" 
                                        :key="module.id"
                                        @click="selectedModule = module.id; updatePreview()"
                                        :class="[
                                            'px-3 py-1 rounded-full text-xs font-medium transition-colors cursor-pointer',
                                            selectedModule === module.id
                                                ? 'bg-blue-600 text-white hover:bg-blue-700' 
                                                : 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200 hover:bg-blue-200 dark:hover:bg-blue-800'
                                        ]">
                                    {{ module.name }}
                                </button>
                            </div>
                        </div>
                        
                        <!-- Conditions Selection -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Active Conditions
                            </label>
                            <div class="flex flex-wrap gap-2">
                                <button v-for="condition in conditions" 
                                        :key="condition.id"
                                        @click="toggleCondition(condition.id)"
                                        :class="[
                                            'px-3 py-1 rounded-full text-xs font-medium transition-colors cursor-pointer',
                                            selectedConditions.includes(condition.id)
                                                ? 'bg-green-600 text-white hover:bg-green-700' 
                                                : 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200 hover:bg-green-200 dark:hover:bg-green-800'
                                        ]">
                                    {{ condition.name }}
                                    <span v-if="condition.type === 'chain_specific'" class="text-xs opacity-75">(Chain)</span>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Preview Content -->
                    <div class="flex-1 bg-white dark:bg-gray-800 rounded-lg shadow p-6 overflow-y-auto">
                        <div v-if="previewText.length > 0" class="space-y-4">
                            <div v-for="paragraph in previewText" :key="`preview-${paragraph.number}`" class="relative">
                                <p class="text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ paragraph.content }}</p>
                                <span class="absolute -left-6 top-0 text-xs text-gray-400">{{ paragraph.number }}</span>
                            </div>
                        </div>
                        <div v-else class="text-gray-500 dark:text-gray-400 italic">
                            Select a module to preview the greeting text
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </ClientLayout>
</template>

<script setup>
import { ref, computed, watch, onUnmounted, nextTick } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import ClientLayout from '@/Layouts/ClientLayout.vue';

const props = defineProps({
    project: Object,
    modules: Array,
    conditions: Array,
    paragraphs: Array,
});

// Local paragraphs state - ensure arrays are properly initialized
const localParagraphs = ref(props.paragraphs.map(p => ({
    ...p,
    modules: p.modules || [],
    show_if_conditions: p.show_if_conditions || [],
    hide_if_conditions: p.hide_if_conditions || [],
})));
const expandedParagraphs = ref([]);

// Filter state
const filterMode = ref('all'); // 'all' or module ID
const showFilterDropdown = ref(false);

// Add paragraph state
const showAddParagraphDropdown = ref(false);
const selectedParagraphNumber = ref(1);

// Preview state - default to first module
const selectedModule = ref(props.modules.length > 0 ? props.modules[0].id : '');
const selectedConditions = ref([]);
const previewText = ref([]);

// Save status tracking
const saveStatus = ref({});
const saveTimers = ref({});

// Track changes
const hasUnsavedChanges = ref(false);
const changedParagraphs = ref(new Set());
const isSaving = ref(false);

// Group and filter paragraphs by number
const groupedParagraphs = computed(() => {
    let filtered = [...localParagraphs.value];
    
    // Apply filter
    if (filterMode.value !== 'all') {
        const moduleId = parseInt(filterMode.value);
        filtered = filtered.filter(p => {
            // Show paragraphs that have no modules (apply to all) or include the selected module
            return !p.modules || p.modules.length === 0 || p.modules.some(id => parseInt(id) === moduleId);
        });
    }
    
    // Group by paragraph number
    const groups = {};
    filtered.forEach((paragraph) => {
        const num = paragraph.paragraph_number || 1;
        if (!groups[num]) {
            groups[num] = [];
        }
        // Find original index in localParagraphs
        const originalIndex = localParagraphs.value.findIndex(p => p === paragraph);
        // Don't create a copy, reference the original
        groups[num].push(paragraph);
    });
    
    // Sort each group by priority
    Object.keys(groups).forEach(num => {
        groups[num].sort((a, b) => a.priority - b.priority);
    });
    
    // Convert to array and sort by paragraph number
    return Object.entries(groups)
        .map(([num, paragraphs]) => ({
            number: parseInt(num),
            paragraphs: paragraphs
        }))
        .sort((a, b) => a.number - b.number);
});

// Add new paragraph
const addNewParagraph = (paragraphNumber = null) => {
    const num = paragraphNumber || selectedParagraphNumber.value;
    
    // Find the highest priority for this paragraph number
    const existingParagraphs = localParagraphs.value.filter(p => p.paragraph_number === num);
    const highestPriority = existingParagraphs.reduce((max, p) => Math.max(max, p.priority || 0), 0);
    
    const newParagraph = {
        paragraph_number: num,
        priority: highestPriority + 1, // Next priority
        content: '',
        modules: [],
        show_if_conditions: [],
        hide_if_conditions: [],
        is_active: true,
    };
    const newIndex = localParagraphs.value.length;
    localParagraphs.value.push(newParagraph);
    const newId = `new-${newIndex}`;
    
    // Auto-expand only the new paragraph
    expandedParagraphs.value = [newId];
    
    // Mark as changed
    hasUnsavedChanges.value = true;
    changedParagraphs.value.add(newId);
    
    // Hide dropdown
    showAddParagraphDropdown.value = false;
    
    updatePreview();
};

// Toggle paragraph expansion - only one at a time
const toggleParagraph = (id) => {
    const index = expandedParagraphs.value.indexOf(id);
    if (index > -1) {
        // If clicking on already expanded, collapse it
        expandedParagraphs.value = [];
    } else {
        // Otherwise, expand only this one
        expandedParagraphs.value = [id];
    }
};

// Handle paragraph changes (no auto-save)
const handleParagraphChange = (paragraph, index) => {
    updatePreview();
    hasUnsavedChanges.value = true;
    const key = paragraph.id || `new-${index}`;
    changedParagraphs.value.add(key);
    
    // If paragraph number or priority changed, keep it expanded
    if (expandedParagraphs.value.includes(key)) {
        // Force Vue to re-compute the groupedParagraphs by triggering a change
        localParagraphs.value = [...localParagraphs.value];
        // Re-expand after DOM update
        nextTick(() => {
            expandedParagraphs.value = [key];
        });
    }
};

// Save new paragraph
const saveParagraph = (index, isAutoSave = false) => {
    const paragraph = localParagraphs.value[index];
    const key = `new-${index}`;
    
    if (!paragraph.content) {
        if (!isAutoSave) {
            alert('Please enter paragraph content');
        }
        saveStatus.value[key] = '';
        return;
    }
    
    const form = useForm({
        paragraph_number: paragraph.paragraph_number,
        priority: paragraph.priority,
        content: paragraph.content,
        modules: (paragraph.modules || []).map(id => parseInt(id)),
        show_if_conditions: (paragraph.show_if_conditions || []).map(id => parseInt(id)),
        hide_if_conditions: (paragraph.hide_if_conditions || []).map(id => parseInt(id)),
    });
    
    form.post(route('client.greeting-texts.store'), {
        preserveScroll: true,
        onSuccess: (page) => {
            // Update the local paragraph with the saved version
            const savedParagraph = page.props.paragraphs.find(p => 
                p.paragraph_number === paragraph.paragraph_number && 
                p.priority === paragraph.priority &&
                p.content === paragraph.content
            );
            if (savedParagraph) {
                localParagraphs.value[index] = savedParagraph;
                // Update save status with new ID
                saveStatus.value[savedParagraph.id] = 'saved';
                delete saveStatus.value[key];
                // Show saved status for 2 seconds
                setTimeout(() => {
                    saveStatus.value[savedParagraph.id] = '';
                }, 2000);
            }
            updatePreview();
        },
        onError: () => {
            saveStatus.value[key] = '';
        },
    });
};

// Update existing paragraph
const updateParagraph = (paragraph, isAutoSave = false) => {
    const key = paragraph.id;
    
    if (!paragraph.content && isAutoSave) {
        saveStatus.value[key] = '';
        return;
    }
    
    const form = useForm({
        paragraph_number: paragraph.paragraph_number,
        priority: paragraph.priority,
        content: paragraph.content,
        modules: (paragraph.modules || []).map(id => parseInt(id)),
        show_if_conditions: (paragraph.show_if_conditions || []).map(id => parseInt(id)),
        hide_if_conditions: (paragraph.hide_if_conditions || []).map(id => parseInt(id)),
        is_active: paragraph.is_active,
    });
    
    form.put(route('client.greeting-texts.update', paragraph.id), {
        preserveScroll: true,
        onSuccess: () => {
            saveStatus.value[key] = 'saved';
            // Show saved status for 2 seconds
            setTimeout(() => {
                saveStatus.value[key] = '';
            }, 2000);
            updatePreview();
        },
        onError: () => {
            saveStatus.value[key] = '';
        },
    });
};

// Delete paragraph
const deleteParagraph = (id, filteredIndex) => {
    if (confirm('Are you sure you want to delete this paragraph?')) {
        // If it's a new paragraph (not saved yet), just remove it from local state
        if (typeof id === 'string' && id.startsWith('new-')) {
            // Find the actual index in the original array
            const actualIndex = localParagraphs.value.findIndex((p, idx) => 
                (!p.id && `new-${idx}` === id) || p.id === id
            );
            if (actualIndex !== -1) {
                localParagraphs.value.splice(actualIndex, 1);
                changedParagraphs.value.delete(id);
            }
            updatePreview();
        } else {
            // It's a saved paragraph, delete from database
            router.delete(route('client.greeting-texts.destroy', id), {
                preserveScroll: true,
                onSuccess: () => {
                    localParagraphs.value = localParagraphs.value.filter(p => p.id !== id);
                    changedParagraphs.value.delete(id);
                    updatePreview();
                },
            });
        }
    }
};

// Helper functions for dropdown badge system
const availableModules = (selectedModules) => {
    const selected = selectedModules || [];
    return props.modules.filter(m => !selected.includes(m.id) && !selected.includes(String(m.id)));
};

const availableShowConditions = (selectedConditions) => {
    const selected = selectedConditions || [];
    return props.conditions.filter(c => !selected.includes(c.id) && !selected.includes(String(c.id)));
};

const availableHideConditions = (selectedConditions) => {
    const selected = selectedConditions || [];
    return props.conditions.filter(c => !selected.includes(c.id) && !selected.includes(String(c.id)));
};

const addModule = (event, paragraph, index) => {
    const moduleId = parseInt(event.target.value);
    if (moduleId) {
        if (!paragraph.modules) {
            paragraph.modules = [];
        }
        paragraph.modules.push(moduleId);
        event.target.value = ''; // Reset dropdown
        handleParagraphChange(paragraph, index);
    }
};

const removeModule = (paragraph, moduleId, index) => {
    const idx = paragraph.modules.indexOf(moduleId);
    if (idx > -1) {
        paragraph.modules.splice(idx, 1);
    }
    handleParagraphChange(paragraph, index);
};

const addShowCondition = (event, paragraph, index) => {
    const conditionId = parseInt(event.target.value);
    if (conditionId) {
        if (!paragraph.show_if_conditions) {
            paragraph.show_if_conditions = [];
        }
        paragraph.show_if_conditions.push(conditionId);
        event.target.value = ''; // Reset dropdown
        handleParagraphChange(paragraph, index);
    }
};

const removeShowCondition = (paragraph, conditionId, index) => {
    const idx = paragraph.show_if_conditions.indexOf(conditionId);
    if (idx > -1) {
        paragraph.show_if_conditions.splice(idx, 1);
    }
    handleParagraphChange(paragraph, index);
};

const addHideCondition = (event, paragraph, index) => {
    const conditionId = parseInt(event.target.value);
    if (conditionId) {
        if (!paragraph.hide_if_conditions) {
            paragraph.hide_if_conditions = [];
        }
        paragraph.hide_if_conditions.push(conditionId);
        event.target.value = ''; // Reset dropdown
        handleParagraphChange(paragraph, index);
    }
};

const removeHideCondition = (paragraph, conditionId, index) => {
    const idx = paragraph.hide_if_conditions.indexOf(conditionId);
    if (idx > -1) {
        paragraph.hide_if_conditions.splice(idx, 1);
    }
    handleParagraphChange(paragraph, index);
};

// Toggle condition in preview
const toggleCondition = (conditionId) => {
    const index = selectedConditions.value.indexOf(conditionId);
    if (index > -1) {
        selectedConditions.value.splice(index, 1);
    } else {
        selectedConditions.value.push(conditionId);
    }
    updatePreview();
};

// Update preview
const updatePreview = () => {
    if (!selectedModule.value) {
        previewText.value = [];
        return;
    }
    
    const compiledParagraphs = [];
    
    // For each paragraph number (1-10)
    for (let num = 1; num <= 10; num++) {
        // Get all paragraphs for this number, ordered by priority
        const paragraphsForNumber = localParagraphs.value
            .filter(p => p.paragraph_number === num && p.content)
            .filter(p => {
                // Only show paragraphs that have no modules OR include the selected module
                if (!p.modules || p.modules.length === 0) {
                    // No modules assigned - don't show in any template
                    return false;
                }
                
                const moduleId = parseInt(selectedModule.value);
                // Check if module ID exists in the array, handling both string and number types
                const hasModule = p.modules.some(id => parseInt(id) === moduleId);
                return hasModule;
            })
            .sort((a, b) => a.priority - b.priority); // Sort by priority
        
        // Find the first paragraph that meets conditions
        for (const paragraph of paragraphsForNumber) {
            if (meetsConditions(paragraph)) {
                compiledParagraphs.push({
                    number: num,
                    content: paragraph.content,
                });
                break; // Use first matching paragraph
            }
        }
    }
    
    previewText.value = compiledParagraphs;
};

// Check if paragraph meets conditions
const meetsConditions = (paragraph) => {
    // Check show_if conditions
    if (paragraph.show_if_conditions && paragraph.show_if_conditions.length > 0) {
        const hasRequiredCondition = paragraph.show_if_conditions.some(condId => 
            selectedConditions.value.includes(condId)
        );
        if (!hasRequiredCondition) {
            return false;
        }
    }
    
    // Check hide_if conditions
    if (paragraph.hide_if_conditions && paragraph.hide_if_conditions.length > 0) {
        const hasBlockingCondition = paragraph.hide_if_conditions.some(condId => 
            selectedConditions.value.includes(condId)
        );
        if (hasBlockingCondition) {
            return false;
        }
    }
    
    return true;
};

// Watch for changes in props.paragraphs
watch(() => props.paragraphs, (newParagraphs) => {
    localParagraphs.value = newParagraphs.map(p => ({
        ...p,
        modules: p.modules || [],
        show_if_conditions: p.show_if_conditions || [],
        hide_if_conditions: p.hide_if_conditions || [],
    }));
    updatePreview();
}, { deep: true });

// Initial preview update
updatePreview();

// Close dropdowns when clicking outside
const handleClickOutside = () => {
    showFilterDropdown.value = false;
    showAddParagraphDropdown.value = false;
};

// Add event listener on mount
if (typeof window !== 'undefined') {
    window.addEventListener('click', handleClickOutside);
}

// Save all changes
const saveAllChanges = async () => {
    if (isSaving.value) return;
    
    const promises = [];
    
    // Go through all changed paragraphs
    localParagraphs.value.forEach((paragraph, index) => {
        const key = paragraph.id || `new-${index}`;
        if (changedParagraphs.value.has(key)) {
            if (paragraph.id) {
                // Update existing paragraph
                promises.push(updateParagraphAsync(paragraph));
            } else if (paragraph.content) {
                // Save new paragraph (only if it has content)
                promises.push(saveParagraphAsync(paragraph));
            }
        }
    });
    
    if (promises.length === 0) {
        return;
    }
    
    isSaving.value = true;
    try {
        await Promise.all(promises);
        hasUnsavedChanges.value = false;
        changedParagraphs.value.clear();
        
        // Reload the page to update progress
        router.reload({ preserveState: false, preserveScroll: true });
    } catch (error) {
        console.error('Error saving changes:', error);
    } finally {
        isSaving.value = false;
    }
};

// Async version of saveParagraph for batch saving
const saveParagraphAsync = (paragraph) => {
    return new Promise((resolve, reject) => {
        const form = useForm({
            paragraph_number: paragraph.paragraph_number,
            priority: paragraph.priority,
            content: paragraph.content,
            modules: (paragraph.modules || []).map(id => parseInt(id)),
            show_if_conditions: (paragraph.show_if_conditions || []).map(id => parseInt(id)),
            hide_if_conditions: (paragraph.hide_if_conditions || []).map(id => parseInt(id)),
        });
        
        form.post(route('client.greeting-texts.store'), {
            preserveScroll: true,
            onSuccess: (page) => {
                // Update the local paragraph with the saved version
                const savedParagraph = page.props.paragraphs.find(p => 
                    p.paragraph_number === paragraph.paragraph_number && 
                    p.priority === paragraph.priority &&
                    p.content === paragraph.content
                );
                if (savedParagraph) {
                    const index = localParagraphs.value.findIndex(p => p === paragraph);
                    if (index !== -1) {
                        localParagraphs.value[index] = savedParagraph;
                    }
                }
                resolve();
            },
            onError: () => reject(),
        });
    });
};

// Async version of updateParagraph for batch saving
const updateParagraphAsync = (paragraph) => {
    return new Promise((resolve, reject) => {
        const form = useForm({
            paragraph_number: paragraph.paragraph_number,
            priority: paragraph.priority,
            content: paragraph.content,
            modules: (paragraph.modules || []).map(id => parseInt(id)),
            show_if_conditions: (paragraph.show_if_conditions || []).map(id => parseInt(id)),
            hide_if_conditions: (paragraph.hide_if_conditions || []).map(id => parseInt(id)),
            is_active: paragraph.is_active,
        });
        
        form.put(route('client.greeting-texts.update', paragraph.id), {
            preserveScroll: true,
            onSuccess: () => resolve(),
            onError: () => reject(),
        });
    });
};

// Handle back button
const handleBack = () => {
    if (hasUnsavedChanges.value) {
        if (confirm('You have unsaved changes. Are you sure you want to leave?')) {
            router.get(route('client.dashboard'));
        }
    } else {
        router.get(route('client.dashboard'));
    }
};

// Get module badge class based on category
const getModuleBadgeClass = (moduleId) => {
    const module = props.modules.find(m => m.id == moduleId);
    if (!module) return 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200';
    
    switch (module.category) {
        case 'mailing':
            return 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200';
        case 'single_message':
            return 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200';
        default:
            return 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200';
    }
};

// Clean up on unmount
onUnmounted(() => {
    if (typeof window !== 'undefined') {
        window.removeEventListener('click', handleClickOutside);
    }
});
</script>