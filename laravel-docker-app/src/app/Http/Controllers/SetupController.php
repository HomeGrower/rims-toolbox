<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectSetupTeam;
use App\Models\ProjectData;
use App\Services\ConfigurationService;
use App\Services\ImageCompressionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class SetupController extends Controller
{
    protected ConfigurationService $configService;
    protected ImageCompressionService $imageService;

    public function __construct(ConfigurationService $configService, ImageCompressionService $imageService)
    {
        $this->configService = $configService;
        $this->imageService = $imageService;
    }

    public function show(Request $request, string $team, string $section)
    {
        $project = $request->attributes->get('project');
        
        // Load relationships for marketing banner pictures and brand defaults
        $project->load(['modules', 'hotelBrand', 'pmsType']);

        // Validate team and section
        $validTeams = ['reservation', 'marketing', 'it'];
        $validSections = [
            'reservation' => ['hotel_settings', 'user_settings', 'reservation_settings', 'room_types', 'cancellation_policies', 'special_requests', 'deposit_policies', 'payment_methods', 'transfer_types'],
            'marketing' => ['banner_pictures', 'logos', 'colors_fonts', 'room_details', 'greetings_texts', 'promotions'],
            'it' => ['it_settings'],
        ];

        if (!in_array($team, $validTeams) || !in_array($section, $validSections[$team])) {
            abort(404);
        }

        // Get or create setup record
        $setupTeam = ProjectSetupTeam::firstOrCreate(
            [
                'project_id' => $project->id,
                'team' => $team,
                'section' => $section,
            ],
            [
                'fields' => [],
                'completed_fields' => [],
                'progress' => 0,
                'status' => 'pending',
            ]
        );

        // Get fields from configuration service (with chain/brand overrides)
        $fields = $this->configService->getFieldsForSetupSection($project, $team, $section);
        
        // Get instructions for this team/section
        $instructions = $this->configService->getInstructionsForTeam($project, $team, $section);


        // Ensure we have the fresh data
        $setupTeam->refresh();
        
        return Inertia::render('Setup/Form', [
            'project' => [
                'id' => $project->id,
                'hotel_name' => $project->hotel_name,
                'access_code' => $project->access_code,
            ],
            'team' => $team,
            'section' => $section,
            'fields' => $fields,
            'currentData' => $setupTeam->completed_fields ?: new \stdClass(),
            'instructions' => $instructions,
        ]);
    }

    public function save(Request $request, string $team, string $section)
    {
        \Log::info('SetupController::save called', [
            'team' => $team,
            'section' => $section,
            'method' => $request->method(),
            'has_data' => $request->has('data'),
            'request_all' => $request->all(),
        ]);
        
        $project = $request->attributes->get('project');
        
        // Load relationships for marketing banner pictures and brand defaults
        $project->load(['modules', 'hotelBrand', 'pmsType']);
        

        $setupTeam = ProjectSetupTeam::where('project_id', $project->id)
            ->where('team', $team)
            ->where('section', $section)
            ->firstOrFail();

        // Get fields configuration
        $fields = $this->configService->getFieldsForSetupSection($project, $team, $section);

        // Build validation rules
        $rules = [];
        $customMessages = [];
        $allFields = $fields; // Keep track of all fields including conditional ones
        
        // Extract grid fields and add them to allFields
        foreach ($fields as $fieldKey => $fieldConfig) {
            if ($fieldConfig['type'] === 'grid' && isset($fieldConfig['fields'])) {
                foreach ($fieldConfig['fields'] as $gridFieldKey => $gridFieldConfig) {
                    $allFields[$gridFieldKey] = $gridFieldConfig;
                    
                    // Add validation rules for grid fields
                    $gridRules = ['nullable'];
                    
                    switch ($gridFieldConfig['type'] ?? 'text') {
                        case 'file':
                        case 'files':
                            // File fields can be either a file upload OR a string (existing path)
                            if ($request->hasFile("data.{$gridFieldKey}")) {
                                $gridRules[] = 'file';
                                if (isset($gridFieldConfig['accept'])) {
                                    $mimes = str_replace(['image/*', 'application/pdf'], ['mimes:jpg,jpeg,png,gif,svg', 'mimes:pdf'], $gridFieldConfig['accept']);
                                    $gridRules[] = $mimes;
                                }
                            } else {
                                $gridRules[] = 'string';
                            }
                            break;
                        case 'email':
                            $gridRules[] = 'email';
                            break;
                        case 'number':
                            $gridRules[] = 'numeric';
                            break;
                        case 'url':
                            $gridRules[] = 'url';
                            break;
                        case 'checkbox':
                        case 'boolean':
                            $gridRules[] = 'boolean';
                            break;
                    }
                    
                    $rules["data.{$gridFieldKey}"] = implode('|', $gridRules);
                }
            }
        }
        
        // Process main fields and extract conditional fields
        foreach ($fields as $fieldKey => $fieldConfig) {
            $fieldRules = [];
            
            // IMPORTANT: All fields are nullable - nothing is mandatory in setup
            $fieldRules[] = 'nullable';
            
            // Add type-specific validation
            switch ($fieldConfig['type'] ?? 'text') {
                case 'email':
                    $fieldRules[] = 'email';
                    break;
                case 'number':
                    $fieldRules[] = 'numeric';
                    if (isset($fieldConfig['min'])) {
                        $fieldRules[] = 'min:' . $fieldConfig['min'];
                    }
                    if (isset($fieldConfig['max'])) {
                        $fieldRules[] = 'max:' . $fieldConfig['max'];
                    }
                    break;
                case 'url':
                    $fieldRules[] = 'url';
                    break;
                case 'file':
                case 'files':
                    // File fields can be either a file upload OR a string (existing path)
                    if ($request->hasFile("data.{$fieldKey}")) {
                        $fieldRules[] = 'file';
                        if (isset($fieldConfig['accept'])) {
                            // Convert accept to mimes validation
                            $mimes = str_replace(['image/*', 'application/pdf'], ['mimes:jpg,jpeg,png,gif,svg', 'mimes:pdf'], $fieldConfig['accept']);
                            $fieldRules[] = $mimes;
                        }
                        if (isset($fieldConfig['max_size'])) {
                            $fieldRules[] = 'max:' . $fieldConfig['max_size'];
                        }
                    } else {
                        // If not a file upload, allow string (existing file path) or null
                        $fieldRules[] = 'string';
                    }
                    break;
                case 'repeater':
                    $fieldRules = ['nullable', 'array'];
                    // Add validation for sub-fields
                    if (isset($fieldConfig['fields'])) {
                        foreach ($fieldConfig['fields'] as $subFieldKey => $subFieldConfig) {
                            $subFieldRules = ['nullable'];
                            switch ($subFieldConfig['type'] ?? 'text') {
                                case 'email':
                                    $subFieldRules[] = 'email';
                                    break;
                                case 'url':
                                    $subFieldRules[] = 'url';
                                    break;
                                case 'number':
                                    $subFieldRules[] = 'numeric';
                                    break;
                                case 'file':
                                case 'files':
                                    // Dynamic validation - check each item individually
                                    if ($request->has("data.{$fieldKey}") && is_array($request->input("data.{$fieldKey}"))) {
                                        foreach ($request->input("data.{$fieldKey}") as $index => $item) {
                                            if ($request->hasFile("data.{$fieldKey}.{$index}.{$subFieldKey}")) {
                                                $fileRules = ['nullable', 'file'];
                                                if (isset($subFieldConfig['accept'])) {
                                                    $mimes = str_replace(['image/*', 'application/pdf'], ['mimes:jpg,jpeg,png,gif,svg', 'mimes:pdf'], $subFieldConfig['accept']);
                                                    $fileRules[] = $mimes;
                                                }
                                                $rules["data.{$fieldKey}.{$index}.{$subFieldKey}"] = implode('|', $fileRules);
                                            } else {
                                                $rules["data.{$fieldKey}.{$index}.{$subFieldKey}"] = 'nullable|string';
                                            }
                                        }
                                        continue 2; // Skip the general rule for this field
                                    }
                                    break;
                            }
                            $rules["data.{$fieldKey}.*.{$subFieldKey}"] = implode('|', $subFieldRules);
                        }
                    }
                    break;
                case 'grid':
                    // Skip grid validation - fields inside grid are validated separately
                    continue 2;
            }
            
            // Add custom validation if specified
            if (!empty($fieldConfig['validation'])) {
                $fieldRules[] = $fieldConfig['validation'];
            }
            
            $rules["data.{$fieldKey}"] = implode('|', $fieldRules);
            
            // Custom messages
            if (isset($fieldConfig['validation_message'])) {
                $customMessages["data.{$fieldKey}.required"] = $fieldConfig['validation_message'];
            }
            
            // Process conditional fields
            if (isset($fieldConfig['conditions']) && is_array($fieldConfig['conditions'])) {
                foreach ($fieldConfig['conditions'] as $condition) {
                    if (isset($condition['fields']) && is_array($condition['fields'])) {
                        foreach ($condition['fields'] as $conditionalField) {
                            $conditionalFieldKey = $conditionalField['field_name'] ?? '';
                            if ($conditionalFieldKey) {
                                // Add conditional field to allFields for later processing
                                $allFields[$conditionalFieldKey] = $conditionalField;
                                
                                // Add validation rules for conditional fields
                                $conditionalRules = ['nullable'];
                                
                                switch ($conditionalField['type'] ?? 'text') {
                                    case 'email':
                                        $conditionalRules[] = 'email';
                                        break;
                                    case 'number':
                                        $conditionalRules[] = 'numeric';
                                        break;
                                    case 'url':
                                        $conditionalRules[] = 'url';
                                        break;
                                    case 'checkbox':
                                    case 'boolean':
                                        $conditionalRules[] = 'boolean';
                                        break;
                                }
                                
                                $rules["data.{$conditionalFieldKey}"] = implode('|', $conditionalRules);
                            }
                        }
                    }
                }
            }
        }

        
        $validated = $request->validate($rules, $customMessages);

        // Debug logging
        \Log::info('SetupController save - Request data:', [
            'team' => $team,
            'section' => $section,
            'all_data' => $request->all(),
            'files' => $request->allFiles(),
            'promotions_data' => $section === 'promotions' ? ($request->input('data') ?? 'No data') : 'Not promotions section'
        ]);

        // Process file uploads and boolean conversions before saving
        $dataToSave = $validated['data'] ?? [];
        foreach ($allFields as $fieldKey => $fieldConfig) {
            if (($fieldConfig['type'] === 'file' || $fieldConfig['type'] === 'files') && $request->hasFile("data.{$fieldKey}")) {
                $file = $request->file("data.{$fieldKey}");
                // Check if it's an image file
                $mimeType = $file->getMimeType();
                if (str_starts_with($mimeType, 'image/')) {
                    // Use compression service for images
                    $path = $this->imageService->processImage($file, "projects/{$project->id}/{$team}/{$section}");
                } else {
                    // Regular file storage for non-images
                    $path = $file->store("projects/{$project->id}/{$team}/{$section}", 'public');
                }
                $dataToSave[$fieldKey] = $path;
            } elseif (($fieldConfig['type'] === 'checkbox' || $fieldConfig['type'] === 'boolean') && isset($dataToSave[$fieldKey])) {
                // Ensure boolean fields are properly converted
                $dataToSave[$fieldKey] = filter_var($dataToSave[$fieldKey], FILTER_VALIDATE_BOOLEAN);
            } elseif ($fieldConfig['type'] === 'repeater' && isset($dataToSave[$fieldKey]) && is_array($dataToSave[$fieldKey])) {
                // Handle file uploads within repeater fields
                $repeaterData = $dataToSave[$fieldKey];
                $processedRepeaterData = [];
                
                foreach ($repeaterData as $index => $item) {
                    $processedItem = $item;
                    
                    // Check if this repeater has any file fields
                    if (isset($fieldConfig['fields']) && is_array($fieldConfig['fields'])) {
                        foreach ($fieldConfig['fields'] as $subFieldKey => $subFieldConfig) {
                            if (($subFieldConfig['type'] === 'file' || $subFieldConfig['type'] === 'files') && 
                                $request->hasFile("data.{$fieldKey}.{$index}.{$subFieldKey}")) {
                                $file = $request->file("data.{$fieldKey}.{$index}.{$subFieldKey}");
                                // Check if it's an image file
                                $mimeType = $file->getMimeType();
                                if (str_starts_with($mimeType, 'image/')) {
                                    // Use compression service for images
                                    $path = $this->imageService->processImage($file, "projects/{$project->id}/{$team}/{$section}/{$fieldKey}");
                                } else {
                                    // Regular file storage for non-images
                                    $path = $file->store("projects/{$project->id}/{$team}/{$section}/{$fieldKey}", 'public');
                                }
                                $processedItem[$subFieldKey] = $path;
                            } elseif (isset($item[$subFieldKey]) && is_string($item[$subFieldKey]) && 
                                     Storage::disk('public')->exists($item[$subFieldKey])) {
                                // Keep existing file path if it's a valid storage path
                                $processedItem[$subFieldKey] = $item[$subFieldKey];
                            }
                        }
                    }
                    
                    $processedRepeaterData[] = $processedItem;
                }
                
                $dataToSave[$fieldKey] = $processedRepeaterData;
            }
        }

        // Update completed fields - only save fields that have actual values
        $completedFieldsToSave = [];
        foreach ($dataToSave as $key => $value) {
            // Check if this is a boolean field
            $isBooleanField = false;
            if (isset($allFields[$key]) && in_array($allFields[$key]['type'], ['checkbox', 'boolean'])) {
                $isBooleanField = true;
            }
            
            // Check if this is a repeater field
            $isRepeaterField = false;
            if (isset($allFields[$key]) && $allFields[$key]['type'] === 'repeater') {
                $isRepeaterField = true;
            }
            
            // For boolean fields, save both true and false values
            // For repeater fields, save even if empty array to properly update when all items are deleted
            // For other fields, only save if they have actual values (not null, empty string, or empty array)
            if ($isBooleanField && $value !== null) {
                $completedFieldsToSave[$key] = $value;
            } elseif ($isRepeaterField && is_array($value)) {
                $completedFieldsToSave[$key] = $value;
            } elseif (!$isBooleanField && !$isRepeaterField && $value !== null && $value !== '' && $value !== []) {
                $completedFieldsToSave[$key] = $value;
            }
        }
        
        // Merge with existing completed fields to not lose data
        $existingCompleted = $setupTeam->completed_fields ?: [];
        foreach ($existingCompleted as $key => $value) {
            // Keep existing value if not in current save
            if (!array_key_exists($key, $dataToSave)) {
                $completedFieldsToSave[$key] = $value;
            }
        }
        $setupTeam->completed_fields = $completedFieldsToSave;
        
        // Debug what we're saving
        \Log::info('SetupController - Saving completed fields:', [
            'team' => $team,
            'section' => $section,
            'completed_fields' => $completedFieldsToSave,
        ]);
        
        // Determine status based on completion - count all required fields including conditional ones
        $totalRequiredFields = 0;
        $completedFields = 0;
        
        // Count main fields
        foreach ($fields as $fieldKey => $fieldConfig) {
            // Skip non-input field types
            if (in_array($fieldConfig['type'] ?? '', ['section_header', 'subsection_header', 'separator', 'section_separator', 'info_display', 'image_display', 'info', 'grid'])) {
                continue;
            }
            
            // Count required fields
            if (isset($fieldConfig['required']) && $fieldConfig['required'] === true) {
                $totalRequiredFields++;
                
                // Check if field is completed
                if (isset($validated['data'][$fieldKey])) {
                    $fieldValue = $validated['data'][$fieldKey];
                    
                    if ($fieldConfig['type'] === 'repeater' && is_array($fieldValue)) {
                        // Special handling for room_types - calculate based on field completion
                        if ($fieldKey === 'room_types') {
                            // Count total required fields across all room types
                            $totalRepeaterFields = 0;
                            $completedRepeaterFields = 0;
                            
                            // Get the repeater field schema
                            $repeaterSchema = $fieldConfig['fields'] ?? [];
                            
                            foreach ($fieldValue as $item) {
                                if (is_array($item)) {
                                    // Count required fields in schema
                                    foreach ($repeaterSchema as $subFieldKey => $subFieldConfig) {
                                        if (isset($subFieldConfig['required']) && $subFieldConfig['required'] === true) {
                                            $totalRepeaterFields++;
                                            
                                            // Check if this field is completed
                                            if (isset($item[$subFieldKey]) && 
                                                $item[$subFieldKey] !== null && 
                                                $item[$subFieldKey] !== '' && 
                                                $item[$subFieldKey] !== []) {
                                                $completedRepeaterFields++;
                                            }
                                        }
                                    }
                                }
                            }
                            
                            // If there are required fields, count as complete based on percentage
                            if ($totalRepeaterFields > 0) {
                                // Give partial credit based on completion percentage
                                $completionRatio = $completedRepeaterFields / $totalRepeaterFields;
                                $completedFields += $completionRatio;
                            }
                        } else {
                            // For other repeater fields, check if at least one item has data
                            $hasData = false;
                            foreach ($fieldValue as $item) {
                                if (is_array($item) && count(array_filter($item)) > 0) {
                                    $hasData = true;
                                    break;
                                }
                            }
                            if ($hasData) {
                                $completedFields++;
                            }
                        }
                    } elseif (($fieldConfig['type'] === 'checkbox' || $fieldConfig['type'] === 'boolean')) {
                        // For required checkboxes, only count as complete if checked (true)
                        if ($fieldValue === true || $fieldValue === 1 || $fieldValue === '1' || $fieldValue === 'true') {
                            $completedFields++;
                        }
                    } elseif (!empty($fieldValue)) {
                        $completedFields++;
                    }
                }
            }
        }
        
        // Count conditional fields that are currently visible
        foreach ($fields as $fieldKey => $fieldConfig) {
            if (isset($fieldConfig['conditions']) && is_array($fieldConfig['conditions'])) {
                $currentValue = $validated['data'][$fieldKey] ?? null;
                
                foreach ($fieldConfig['conditions'] as $condition) {
                    // Check if this condition is met
                    $conditionMet = false;
                    if (($fieldConfig['type'] === 'checkbox' || $fieldConfig['type'] === 'boolean')) {
                        $conditionMet = ($condition['value'] === 'true' && $currentValue === true) ||
                                      ($condition['value'] === 'false' && $currentValue === false);
                    } else {
                        $conditionMet = ($condition['value'] == $currentValue);
                    }
                    
                    if ($conditionMet && isset($condition['fields']) && is_array($condition['fields'])) {
                        // Count required conditional fields
                        foreach ($condition['fields'] as $conditionalField) {
                            if (isset($conditionalField['required']) && $conditionalField['required'] === true) {
                                $conditionalFieldKey = $conditionalField['field_name'] ?? '';
                                if ($conditionalFieldKey) {
                                    $totalRequiredFields++;
                                    
                                    // Check if conditional field is completed
                                    if (isset($validated['data'][$conditionalFieldKey])) {
                                        $conditionalFieldValue = $validated['data'][$conditionalFieldKey];
                                        
                                        if (($conditionalField['type'] === 'checkbox' || $conditionalField['type'] === 'boolean')) {
                                            // For required checkboxes, only count as complete if checked (true)
                                            if ($conditionalFieldValue === true || $conditionalFieldValue === 1 || $conditionalFieldValue === '1' || $conditionalFieldValue === 'true') {
                                                $completedFields++;
                                            }
                                        } elseif (!empty($conditionalFieldValue)) {
                                            $completedFields++;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        
        // Count grid fields
        foreach ($fields as $fieldKey => $fieldConfig) {
            if ($fieldConfig['type'] === 'grid' && isset($fieldConfig['fields'])) {
                foreach ($fieldConfig['fields'] as $gridFieldKey => $gridFieldConfig) {
                    if (isset($gridFieldConfig['required']) && $gridFieldConfig['required'] === true) {
                        $totalRequiredFields++;
                        
                        if (isset($validated['data'][$gridFieldKey])) {
                            $gridFieldValue = $validated['data'][$gridFieldKey];
                            
                            if (($gridFieldConfig['type'] === 'checkbox' || $gridFieldConfig['type'] === 'boolean')) {
                                // For required checkboxes, only count as complete if checked (true)
                                if ($gridFieldValue === true || $gridFieldValue === 1 || $gridFieldValue === '1' || $gridFieldValue === 'true') {
                                    $completedFields++;
                                }
                            } elseif (!empty($gridFieldValue)) {
                                $completedFields++;
                            }
                        }
                    }
                }
            }
        }
        
        // For sections with no required fields, use progress-based status
        if ($totalRequiredFields === 0) {
            // Count all fields for sections without required fields
            $totalAllFields = 0;
            $completedAllFields = 0;
            
            // Count regular fields
            foreach ($fields as $fieldKey => $fieldConfig) {
                if (in_array($fieldConfig['type'] ?? '', ['section_header', 'subsection_header', 'separator', 'section_separator', 'info_display', 'image_display', 'info', 'grid'])) {
                    continue;
                }
                $totalAllFields++;
                
                // Special handling for repeater fields
                if ($fieldConfig['type'] === 'repeater') {
                    // For repeater fields, check if there's at least one item with all required subfields filled
                    if (isset($completedFieldsToSave[$fieldKey]) && is_array($completedFieldsToSave[$fieldKey]) && !empty($completedFieldsToSave[$fieldKey])) {
                        $repeaterSchema = $fieldConfig['fields'] ?? [];
                        $hasCompleteItem = false;
                        
                        foreach ($completedFieldsToSave[$fieldKey] as $item) {
                            if (is_array($item)) {
                                $itemIsComplete = true;
                                // Check if all required subfields are filled
                                foreach ($repeaterSchema as $subFieldKey => $subFieldConfig) {
                                    if (isset($subFieldConfig['required']) && $subFieldConfig['required'] === true) {
                                        if (!isset($item[$subFieldKey]) || 
                                            $item[$subFieldKey] === null || 
                                            $item[$subFieldKey] === '' || 
                                            $item[$subFieldKey] === []) {
                                            $itemIsComplete = false;
                                            break;
                                        }
                                    }
                                }
                                if ($itemIsComplete) {
                                    $hasCompleteItem = true;
                                    break;
                                }
                            }
                        }
                        
                        if ($hasCompleteItem) {
                            $completedAllFields++;
                        }
                    }
                } else {
                    // For non-repeater fields
                    if (isset($completedFieldsToSave[$fieldKey]) && $completedFieldsToSave[$fieldKey] !== null && $completedFieldsToSave[$fieldKey] !== '' && $completedFieldsToSave[$fieldKey] !== []) {
                        $completedAllFields++;
                    }
                }
            }
            
            // Count grid fields
            foreach ($fields as $fieldKey => $fieldConfig) {
                if ($fieldConfig['type'] === 'grid' && isset($fieldConfig['fields'])) {
                    foreach ($fieldConfig['fields'] as $gridFieldKey => $gridFieldConfig) {
                        $totalAllFields++;
                        if (isset($completedFieldsToSave[$gridFieldKey]) && $completedFieldsToSave[$gridFieldKey] !== null && $completedFieldsToSave[$gridFieldKey] !== '' && $completedFieldsToSave[$gridFieldKey] !== []) {
                            $completedAllFields++;
                        }
                    }
                }
            }
            
            // Special handling for sections with only repeater fields (like promotions)
            $hasOnlyRepeaterFields = true;
            $hasAnyFilledRepeater = false;
            
            foreach ($fields as $fieldKey => $fieldConfig) {
                if (in_array($fieldConfig['type'] ?? '', ['section_header', 'subsection_header', 'separator', 'section_separator', 'info_display', 'image_display', 'info', 'grid'])) {
                    continue;
                }
                
                if ($fieldConfig['type'] !== 'repeater') {
                    $hasOnlyRepeaterFields = false;
                }
                
                if ($fieldConfig['type'] === 'repeater' && 
                    isset($completedFieldsToSave[$fieldKey]) && 
                    is_array($completedFieldsToSave[$fieldKey]) && 
                    !empty($completedFieldsToSave[$fieldKey])) {
                    // Check if any item in the repeater has data
                    foreach ($completedFieldsToSave[$fieldKey] as $item) {
                        if (is_array($item) && !empty(array_filter($item))) {
                            $hasAnyFilledRepeater = true;
                            break;
                        }
                    }
                }
            }
            
            // Set status based on actual progress
            if ($hasOnlyRepeaterFields && !$hasAnyFilledRepeater) {
                // If section only has repeater fields and none have data, it's pending
                $setupTeam->status = 'pending';
            } elseif ($completedAllFields === 0) {
                $setupTeam->status = 'pending';
            } elseif ($completedAllFields >= $totalAllFields) {
                // Also check for empty repeater fields
                $hasEmptyRepeater = false;
                foreach ($completedFieldsToSave as $key => $value) {
                    if (isset($allFields[$key]) && $allFields[$key]['type'] === 'repeater' && 
                        is_array($value) && empty($value)) {
                        $hasEmptyRepeater = true;
                        break;
                    }
                }
                $setupTeam->status = $hasEmptyRepeater ? 'in_progress' : 'completed';
            } else {
                $setupTeam->status = 'in_progress';
            }
        } elseif ($completedFields === 0) {
            $setupTeam->status = 'pending';
        } elseif ($completedFields >= $totalRequiredFields) {
            // Special check for sections with repeater fields that need full completion
            $repeaterFieldsToCheck = ['room_types'];
            
            // Add all promotion field types dynamically
            foreach ($fields as $fieldKey => $fieldConfig) {
                if ($fieldConfig['type'] === 'repeater' && strpos($fieldKey, '_items') !== false) {
                    $repeaterFieldsToCheck[] = $fieldKey;
                }
            }
            $hasIncompleteRepeater = false;
            
            foreach ($repeaterFieldsToCheck as $repeaterFieldKey) {
                if (isset($fields[$repeaterFieldKey]) && $fields[$repeaterFieldKey]['type'] === 'repeater') {
                    $repeaterData = $validated['data'][$repeaterFieldKey] ?? [];
                    $repeaterConfig = $fields[$repeaterFieldKey];
                    
                    // If the repeater field exists but is empty, it's incomplete
                    if (empty($repeaterData)) {
                        if (isset($completedFieldsToSave[$repeaterFieldKey])) {
                            $hasIncompleteRepeater = true;
                            break;
                        }
                    } elseif (is_array($repeaterData)) {
                        // Check if all required fields are filled in all items
                        $repeaterSchema = $repeaterConfig['fields'] ?? [];
                        foreach ($repeaterData as $item) {
                            if (is_array($item)) {
                                foreach ($repeaterSchema as $subFieldKey => $subFieldConfig) {
                                    if (isset($subFieldConfig['required']) && $subFieldConfig['required'] === true) {
                                        if (!isset($item[$subFieldKey]) || 
                                            $item[$subFieldKey] === null || 
                                            $item[$subFieldKey] === '' || 
                                            $item[$subFieldKey] === []) {
                                            $hasIncompleteRepeater = true;
                                            break 3;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
            
            if ($hasIncompleteRepeater) {
                $setupTeam->status = 'in_progress';
            } else {
                $setupTeam->status = 'completed';
            }
        } else {
            $setupTeam->status = 'in_progress';
        }
        
        $setupTeam->save();
        $setupTeam->updateProgress();
        
        // If room_types were updated, also update room_details progress
        if ($team === 'reservation' && $section === 'room_types') {
            $this->updateRoomDetailsProgress($project);
            $this->cleanupDeletedRoomDetails($project, $completedFieldsToSave['room_types'] ?? []);
        }

        // Handle project_data table updates - including deletions
        foreach ($allFields as $fieldKey => $fieldConfig) {
            $fieldValue = $validated['data'][$fieldKey] ?? null;
            
            // Handle file uploads
            if (($fieldConfig['type'] === 'file' || $fieldConfig['type'] === 'files') && $request->hasFile("data.{$fieldKey}")) {
                $file = $request->file("data.{$fieldKey}");
                $path = $file->store("projects/{$project->id}/{$team}/{$section}", 'public');
                $fieldValue = $path;
            }
            
            if ($fieldValue !== null && $fieldValue !== '') {
                // Save or update the field
                ProjectData::updateOrCreate(
                    [
                        'project_id' => $project->id,
                        'team' => $team,
                        'section' => $section,
                        'field_key' => $fieldKey,
                    ],
                    [
                        'field_label' => $fieldConfig['label'] ?? $fieldKey,
                        'field_value' => is_array($fieldValue) ? json_encode($fieldValue) : $fieldValue,
                        'field_type' => $fieldConfig['type'] ?? 'text',
                    ]
                );
            } else {
                // Delete the field if it's empty
                ProjectData::where('project_id', $project->id)
                    ->where('team', $team)
                    ->where('section', $section)
                    ->where('field_key', $fieldKey)
                    ->delete();
            }
        }

        // Check if all sections for this team are completed
        $teamSections = ProjectSetupTeam::where('project_id', $project->id)
            ->where('team', $team)
            ->get();
        
        $allCompleted = $teamSections->every(fn($s) => $s->status === 'completed');
        
        if ($allCompleted) {
            // You could trigger team completion notifications here
        }

        return redirect()->route('client.dashboard')->with('success', 'Settings saved successfully!');
    }
    
    private function updateRoomDetailsProgress(Project $project): void
    {
        // Get room_details setup team
        $roomDetailsSetup = ProjectSetupTeam::where('project_id', $project->id)
            ->where('team', 'marketing')
            ->where('section', 'room_details')
            ->first();
            
        if (!$roomDetailsSetup) {
            return;
        }
        
        // Regenerate fields based on current room types
        $fields = ProjectSetupTeam::generateFieldsForSection('marketing', 'room_details', $project);
        
        // Count total fields (excluding headers and non-input fields)
        $totalFields = 0;
        $completedFields = 0;
        $existingData = $roomDetailsSetup->completed_fields ?: [];
        
        foreach ($fields as $fieldKey => $fieldConfig) {
            if (in_array($fieldConfig['type'] ?? '', ['section_header', 'subsection_header', 'separator', 'section_separator', 'info_display', 'image_display', 'info', 'grid'])) {
                continue;
            }
            $totalFields++;
            if (isset($existingData[$fieldKey]) && $existingData[$fieldKey] !== null && $existingData[$fieldKey] !== '' && $existingData[$fieldKey] !== []) {
                $completedFields++;
            }
        }
        
        // Count grid fields
        foreach ($fields as $fieldKey => $fieldConfig) {
            if ($fieldConfig['type'] === 'grid' && isset($fieldConfig['fields'])) {
                foreach ($fieldConfig['fields'] as $gridFieldKey => $gridFieldConfig) {
                    $totalFields++;
                    if (isset($existingData[$gridFieldKey]) && $existingData[$gridFieldKey] !== null && $existingData[$gridFieldKey] !== '' && $existingData[$gridFieldKey] !== []) {
                        $completedFields++;
                    }
                }
            }
        }
        
        // Update status based on progress
        if ($totalFields === 0) {
            $roomDetailsSetup->status = 'pending';
        } elseif ($completedFields === 0) {
            $roomDetailsSetup->status = 'pending';
        } elseif ($completedFields >= $totalFields) {
            $roomDetailsSetup->status = 'completed';
        } else {
            $roomDetailsSetup->status = 'in_progress';
        }
        
        // Clean up completed_fields - remove fields for rooms that no longer exist
        $cleanedCompletedFields = [];
        foreach ($existingData as $key => $value) {
            // Check if this field still exists in the new field structure
            if (isset($fields[$key]) || $this->isGridFieldKey($key, $fields)) {
                $cleanedCompletedFields[$key] = $value;
            }
        }
        
        $roomDetailsSetup->completed_fields = $cleanedCompletedFields;
        $roomDetailsSetup->save();
        $roomDetailsSetup->updateProgress();
    }
    
    private function cleanupDeletedRoomDetails(Project $project, array $currentRoomTypes): void
    {
        // Get room_details setup team
        $roomDetailsSetup = ProjectSetupTeam::where('project_id', $project->id)
            ->where('team', 'marketing')
            ->where('section', 'room_details')
            ->first();
            
        if (!$roomDetailsSetup || !$roomDetailsSetup->completed_fields) {
            return;
        }
        
        // Get the count of current room types
        $currentRoomCount = count($currentRoomTypes);
        
        // Clean up any room details for indices that no longer exist
        $cleanedFields = [];
        foreach ($roomDetailsSetup->completed_fields as $key => $value) {
            // Check if this is a room detail field
            if (preg_match('/^room_(\d+)_/', $key, $matches)) {
                $roomIndex = (int) $matches[1];
                // Only keep if the room index is still valid
                if ($roomIndex < $currentRoomCount) {
                    $cleanedFields[$key] = $value;
                }
            } else {
                // Keep all non-room fields
                $cleanedFields[$key] = $value;
            }
        }
        
        // Update if changes were made
        if (count($cleanedFields) !== count($roomDetailsSetup->completed_fields)) {
            $roomDetailsSetup->completed_fields = $cleanedFields;
            $roomDetailsSetup->save();
            $roomDetailsSetup->updateProgress();
        }
    }
    
    private function isGridFieldKey(string $key, array $fields): bool
    {
        foreach ($fields as $fieldConfig) {
            if ($fieldConfig['type'] === 'grid' && isset($fieldConfig['fields'])) {
                if (array_key_exists($key, $fieldConfig['fields'])) {
                    return true;
                }
            }
        }
        return false;
    }
}