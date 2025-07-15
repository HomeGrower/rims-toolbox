<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ProjectSetupTeam extends Model
{
    protected $fillable = [
        'project_id',
        'team',
        'section',
        'fields',
        'completed_fields',
        'progress',
        'status',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'fields' => 'array',
        'completed_fields' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    const TEAMS = [
        'reservation' => 'Reservation Team',
        'marketing' => 'Marketing Team',
        'it' => 'IT Team',
    ];

    const SECTIONS = [
        'reservation' => [
            'hotel_settings' => 'Hotel Settings',
            'user_settings' => 'User Settings',
            'reservation_settings' => 'Reservation Settings',
            'room_types' => 'Room Types',
            'cancellation_policies' => 'Cancellation Policies',
            'special_requests' => 'Special Requests',
            'deposit_policies' => 'Deposit Policies',
            'payment_methods' => 'Payment Methods',
            'transfer_types' => 'Transfer Types',
        ],
        'marketing' => [
            'banner_pictures' => 'Banner Pictures',
            'logos' => 'Logos',
            'colors_fonts' => 'Colors and Fonts',
            'room_details' => 'Room Details',
            'greetings_texts' => 'Greetings Texts',
            'promotions' => 'Promotions',
        ],
        'it' => [
            'it_settings' => 'IT Settings',
        ],
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function calculateProgress(): int
    {
        // Special handling for greetings_texts section
        if ($this->section === 'greetings_texts') {
            // Load the project with modules
            $project = $this->project->load('modules');
            
            // Get only mailing and single_message modules for this project
            $moduleIds = $project->modules
                ->filter(function ($module) {
                    return in_array($module->category, ['mailing', 'single_message']);
                })
                ->pluck('id')
                ->toArray();
            
            if (empty($moduleIds)) {
                // No modules selected, consider it complete
                return 100;
            }
            
            // Check if each module has at least one greeting paragraph
            $greetingParagraphs = \App\Models\GreetingParagraph::where('project_id', $project->id)
                ->where('is_active', true)
                ->whereNotNull('modules')
                ->get();
            
            // Get unique module IDs that have at least one paragraph
            $modulesWithParagraphs = [];
            foreach ($greetingParagraphs as $paragraph) {
                if (is_array($paragraph->modules)) {
                    $modulesWithParagraphs = array_merge($modulesWithParagraphs, $paragraph->modules);
                }
            }
            $modulesWithParagraphs = array_unique($modulesWithParagraphs);
            
            // Calculate percentage based on modules covered
            $coveredModules = array_intersect($moduleIds, $modulesWithParagraphs);
            $coveragePercentage = count($moduleIds) > 0 
                ? (count($coveredModules) / count($moduleIds)) * 100 
                : 0;
            
            return (int) round($coveragePercentage);
        }
        
        // Default calculation for other sections - count only required fields
        $fields = self::generateFieldsForSection($this->team, $this->section, $this->project);
        $totalRequiredFields = 0;
        $completedRequiredFields = 0;
        
        // Count main required fields
        foreach ($fields as $fieldKey => $fieldConfig) {
            // Skip non-input field types
            if (in_array($fieldConfig['type'] ?? '', ['section_header', 'subsection_header', 'separator', 'section_separator', 'info_display', 'image_display', 'info', 'grid'])) {
                continue;
            }
            
            if (isset($fieldConfig['required']) && $fieldConfig['required'] === true) {
                $totalRequiredFields++;
                
                if (isset($this->completed_fields[$fieldKey])) {
                    $fieldValue = $this->completed_fields[$fieldKey];
                    
                    if ($fieldConfig['type'] === 'repeater' && is_array($fieldValue)) {
                        // Special handling for room_types and promotion fields - calculate based on field completion
                        if ($fieldKey === 'room_types' || 
                            strpos($fieldKey, '_items') !== false) {
                            // Count total required fields across all items
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
                                $completedRequiredFields += $completionRatio;
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
                                $completedRequiredFields++;
                            }
                        }
                    } elseif (($fieldConfig['type'] === 'checkbox' || $fieldConfig['type'] === 'boolean')) {
                        // For required checkboxes, only count as complete if checked (true)
                        if ($fieldValue === true || $fieldValue === 1 || $fieldValue === '1' || $fieldValue === 'true') {
                            $completedRequiredFields++;
                        }
                    } elseif ($fieldValue !== null && $fieldValue !== '' && $fieldValue !== []) {
                        $completedRequiredFields++;
                    }
                }
            }
        }
        
        // Count conditional required fields that are currently visible
        foreach ($fields as $fieldKey => $fieldConfig) {
            if (isset($fieldConfig['conditions']) && is_array($fieldConfig['conditions'])) {
                $currentValue = $this->completed_fields[$fieldKey] ?? null;
                
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
                                    
                                    if (isset($this->completed_fields[$conditionalFieldKey])) {
                                        $conditionalFieldValue = $this->completed_fields[$conditionalFieldKey];
                                        
                                        if (($conditionalField['type'] === 'checkbox' || $conditionalField['type'] === 'boolean')) {
                                            // For required checkboxes, only count as complete if checked (true)
                                            if ($conditionalFieldValue === true || $conditionalFieldValue === 1 || $conditionalFieldValue === '1' || $conditionalFieldValue === 'true') {
                                                $completedRequiredFields++;
                                            }
                                        } elseif (!empty($conditionalFieldValue)) {
                                            $completedRequiredFields++;
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
                        
                        if (isset($this->completed_fields[$gridFieldKey])) {
                            $gridFieldValue = $this->completed_fields[$gridFieldKey];
                            
                            if (($gridFieldConfig['type'] === 'checkbox' || $gridFieldConfig['type'] === 'boolean')) {
                                // For required checkboxes, only count as complete if checked (true)
                                if ($gridFieldValue === true || $gridFieldValue === 1 || $gridFieldValue === '1' || $gridFieldValue === 'true') {
                                    $completedRequiredFields++;
                                }
                            } elseif (!empty($gridFieldValue)) {
                                $completedRequiredFields++;
                            }
                        }
                    }
                }
            }
        }
        
        // If no required fields, count all fields for progress
        if ($totalRequiredFields === 0) {
            $totalFields = 0;
            $completedFields = 0;
            
            // Count all fields (including non-required)
            foreach ($fields as $fieldKey => $fieldConfig) {
                if (in_array($fieldConfig['type'] ?? '', ['section_header', 'subsection_header', 'separator', 'section_separator', 'info_display', 'image_display', 'info', 'grid'])) {
                    continue;
                }
                $totalFields++;
                if (isset($this->completed_fields[$fieldKey])) {
                    $fieldValue = $this->completed_fields[$fieldKey];
                    // Consider a field as completed if it has any value (including '0')
                    if ($fieldValue !== null && $fieldValue !== '' && $fieldValue !== []) {
                        $completedFields++;
                    }
                }
            }
            
            // Count grid fields
            foreach ($fields as $fieldKey => $fieldConfig) {
                if ($fieldConfig['type'] === 'grid' && isset($fieldConfig['fields'])) {
                    foreach ($fieldConfig['fields'] as $gridFieldKey => $gridFieldConfig) {
                        $totalFields++;
                        if (isset($this->completed_fields[$gridFieldKey])) {
                            $gridFieldValue = $this->completed_fields[$gridFieldKey];
                            // Consider a field as completed if it has any value (including '0')
                            if ($gridFieldValue !== null && $gridFieldValue !== '' && $gridFieldValue !== []) {
                                $completedFields++;
                            }
                        }
                    }
                }
            }
            
            if ($totalFields === 0) {
                return 0;
            }
            
            return (int) round(($completedFields / $totalFields) * 100);
        }
        
        return (int) round(($completedRequiredFields / $totalRequiredFields) * 100);
    }

    public function updateProgress(): void
    {
        $this->progress = $this->calculateProgress();
        
        // Status ist bereits in SetupController gesetzt, aber wir müssen Timestamps aktualisieren
        if ($this->status === 'in_progress' && !$this->started_at) {
            $this->started_at = now();
        } elseif ($this->status === 'completed' && !$this->completed_at) {
            $this->completed_at = now();
        } elseif ($this->status === 'pending') {
            $this->started_at = null;
            $this->completed_at = null;
        }
        
        $this->save();
    }

    public function getTeamNameAttribute(): string
    {
        return self::TEAMS[$this->team] ?? $this->team;
    }

    public function getSectionNameAttribute(): string
    {
        return self::SECTIONS[$this->team][$this->section] ?? $this->section;
    }

    public static function generateFieldsForSection(string $team, string $section, Project $project): array
    {
        $fields = [];

        switch ($team) {
            case 'reservation':
                $fields = self::getReservationFields($section, $project);
                break;
            case 'marketing':
                $fields = self::getMarketingFields($section, $project);
                break;
            case 'it':
                $fields = self::getITFields($section, $project);
                break;
        }

        return $fields;
    }

    private static function getReservationFields(string $section, Project $project): array
    {
        $fields = [];
        
        // Add example images for this section if available
        if ($project && $project->hotelBrand && $project->hotelBrand->example_images) {
            foreach ($project->hotelBrand->example_images as $index => $example) {
                // Check if this example is for the current section
                $sectionKey = $example['section_key'] ?? '';
                
                // Show example image if it matches the current section
                if ($sectionKey === $section || $sectionKey === 'all') {
                    $fields["example_image_{$sectionKey}"] = [
                        'type' => 'image_display',
                        'label' => ucfirst(str_replace('_', ' ', $sectionKey)) . ' Example',
                        'value' => $example['image'] ?? '',
                        'description' => $example['description'] ?? 'Reference image from brand guidelines',
                    ];
                }
            }
        }
        
        switch ($section) {
            case 'hotel_settings':
                $fields = array_merge($fields, [
                    'hotel_display_name' => [
                        'type' => 'text',
                        'label' => 'Hotel Display Name',
                        'description' => 'The name as it should appear to guests',
                        'required' => false,
                    ],
                    'hotel_code' => [
                        'type' => 'text',
                        'label' => 'Hotel Code',
                        'description' => 'Internal hotel code or identifier',
                        'required' => false,
                    ],
                    'street_address' => [
                        'type' => 'text',
                        'label' => 'Street Address',
                        'description' => 'Street name and number',
                        'required' => false,
                    ],
                    'postal_code' => [
                        'type' => 'text',
                        'label' => 'Postal Code',
                        'description' => 'ZIP or postal code',
                        'required' => false,
                    ],
                    'city' => [
                        'type' => 'text',
                        'label' => 'City',
                        'description' => 'City name',
                        'required' => false,
                    ],
                    'country' => [
                        'type' => 'text',
                        'label' => 'Country',
                        'description' => 'Country name',
                        'required' => false,
                        'placeholder' => 'e.g. Germany',
                    ],
                    'phone_number_main' => [
                        'type' => 'text',
                        'label' => 'Phone Number Main Line',
                        'description' => 'Main hotel phone number with country code',
                        'required' => false,
                        'placeholder' => '+49 89 123456',
                    ],
                    'check_in_time' => [
                        'type' => 'text',
                        'label' => 'Hotel Default Check-in Time',
                        'description' => 'Standard check-in time (e.g., 15:00)',
                        'required' => false,
                        'placeholder' => '15:00',
                    ],
                    'check_out_time' => [
                        'type' => 'text',
                        'label' => 'Hotel Default Check-out Time',
                        'description' => 'Standard check-out time (e.g., 11:00)',
                        'required' => false,
                        'placeholder' => '11:00',
                    ],
                    'hotel_website_url' => [
                        'type' => 'url',
                        'label' => 'Hotel Website URL',
                        'description' => 'Complete URL including https://',
                        'required' => false,
                        'placeholder' => 'https://www.hotel-example.com',
                    ],
                    'currency' => [
                        'type' => 'select',
                        'label' => 'Currency',
                        'description' => 'Primary currency used by the hotel',
                        'required' => false,
                        'options' => \App\Models\Currency::getForSelect(),
                    ],
                    'driving_directions_url' => [
                        'type' => 'url',
                        'label' => 'Driving Directions URL',
                        'description' => 'Link to Google Maps or other navigation service',
                        'required' => false,
                        'placeholder' => 'https://maps.google.com/...',
                    ],
                ]);
                break;
                
            case 'user_settings':
                $fields = array_merge($fields, [
                    'users' => [
                        'type' => 'repeater',
                        'label' => 'RIMS Users',
                        'description' => 'Add users who will have access to the system',
                        'required' => false,
                        'grid_layout' => true,
                        'fields' => [
                            'email' => [
                                'type' => 'email',
                                'label' => 'Email',
                                'required' => false,
                                'placeholder' => 'user@hotel.com',
                                'grid_position' => 'top-left',
                                'grid_width' => 'half',
                            ],
                            'role' => [
                                'type' => 'select',
                                'label' => 'Role',
                                'required' => false,
                                'options' => [
                                    'admin' => 'Admin',
                                    'user' => 'User',
                                ],
                                'default' => 'user',
                                'grid_position' => 'top-right',
                                'grid_width' => 'half',
                            ],
                            'first_name' => [
                                'type' => 'text',
                                'label' => 'First Name',
                                'required' => false,
                                'placeholder' => 'John',
                                'grid_position' => 'bottom-left',
                                'grid_width' => 'half',
                            ],
                            'last_name' => [
                                'type' => 'text',
                                'label' => 'Last Name',
                                'required' => false,
                                'placeholder' => 'Doe',
                                'grid_position' => 'bottom-right',
                                'grid_width' => 'half',
                            ],
                        ],
                    ],
                ]);
                break;
                
            case 'reservation_settings':
                
                // Check PMS configuration to show policy fields
                if ($project && $project->pmsType) {
                    if ($project->pmsType->shouldShowReservationPolicyField('cancellation_policies')) {
                        $fields['cancellation_policies_configured'] = [
                            'type' => 'boolean',
                            'label' => 'Are cancellation policies configured in the PMS?',
                            'description' => 'Confirm if cancellation policies are set up in your PMS system',
                            'required' => true,
                            'example_image' => $project->pmsType->getPolicyExampleImage('cancellation_policies'),
                        ];
                    }
                    
                    if ($project->pmsType->shouldShowReservationPolicyField('special_requests')) {
                        $fields['special_requests_configured'] = [
                            'type' => 'boolean',
                            'label' => 'Are special requests configured in the PMS?',
                            'description' => 'Confirm if special requests are set up in your PMS system',
                            'required' => true,
                            'example_image' => $project->pmsType->getPolicyExampleImage('special_requests'),
                        ];
                    }
                    
                    if ($project->pmsType->shouldShowReservationPolicyField('deposit_policies')) {
                        $fields['deposit_policies_configured'] = [
                            'type' => 'boolean',
                            'label' => 'Are deposit policies configured in the PMS?',
                            'description' => 'Confirm if deposit policies are set up in your PMS system',
                            'required' => true,
                            'example_image' => $project->pmsType->getPolicyExampleImage('deposit_policies'),
                        ];
                    }
                    
                    if ($project->pmsType->shouldShowReservationPolicyField('payment_methods')) {
                        $fields['payment_methods_configured'] = [
                            'type' => 'boolean',
                            'label' => 'Are payment methods configured in the PMS?',
                            'description' => 'Confirm if payment methods are set up in your PMS system',
                            'required' => true,
                            'example_image' => $project->pmsType->getPolicyExampleImage('payment_methods'),
                        ];
                    }
                    
                    if ($project->pmsType->shouldShowReservationPolicyField('transfer_types')) {
                        $fields['transfer_types_configured'] = [
                            'type' => 'boolean',
                            'label' => 'Are transfer types configured in the PMS?',
                            'description' => 'Confirm if transfer types are set up in your PMS system',
                            'required' => true,
                            'example_image' => $project->pmsType->getPolicyExampleImage('transfer_types'),
                        ];
                    }
                }
                
                // Check which modules are selected for the project to show toggle
                if ($project && $project->modules) {
                    $selectedModules = $project->modules;
                    $showToggle = false;
                    
                    foreach ($selectedModules as $module) {
                        if ($module->allow_room_details_toggle) {
                            $showToggle = true;
                            break;
                        }
                    }
                    
                    // Show toggle if any module allows it
                    if ($showToggle) {
                        // Check which of the supported templates are selected
                        $availableTemplates = [];
                        
                        // Check if confirmation module is selected
                        $hasConfirmation = $selectedModules->contains(function ($module) {
                            return $module->code === 'confirmation';
                        });
                        if ($hasConfirmation) {
                            $availableTemplates[] = 'Confirmation';
                        }
                        
                        // Check if pre-arrival module is selected
                        $hasPreArrival = $selectedModules->contains(function ($module) {
                            return $module->code === 'pre_arrival';
                        });
                        if ($hasPreArrival) {
                            $availableTemplates[] = 'Pre-Arrival';
                        }
                        
                        // Create description with available templates
                        $description = 'Display room images and descriptions in emails';
                        if (!empty($availableTemplates)) {
                            $description .= ' (Available for: ' . implode(', ', $availableTemplates) . ')';
                        }
                        
                        // Get brand example image if available
                        $exampleImage = null;
                        if ($project->hotelBrand && isset($project->hotelBrand->example_images['room_details_toggle'])) {
                            $exampleImage = $project->hotelBrand->example_images['room_details_toggle'];
                        }
                        
                        $fields['show_room_details_in_templates'] = [
                            'type' => 'boolean',
                            'label' => 'Show Room Details in Email Templates',
                            'description' => $description,
                            'required' => false,
                        ];
                        
                        if ($exampleImage) {
                            $fields['show_room_details_in_templates']['example_image'] = $exampleImage;
                        }
                        
                        // Add room details example image
                        $roomDetailsExampleImage = null;
                        
                        // Check PMS type for room details example
                        if ($project->pmsType && $project->pmsType->getPolicyExampleImage('room_details')) {
                            $roomDetailsExampleImage = $project->pmsType->getPolicyExampleImage('room_details');
                        }
                        // Fallback to brand example image
                        elseif ($project->hotelBrand && isset($project->hotelBrand->example_images['room_details'])) {
                            $roomDetailsExampleImage = $project->hotelBrand->example_images['room_details'];
                        }
                        
                        if ($roomDetailsExampleImage) {
                            $fields['room_details_example'] = [
                                'type' => 'image_display',
                                'label' => 'Room Details Example',
                                'value' => $roomDetailsExampleImage,
                                'description' => 'Example of how room details appear in email templates',
                            ];
                        }
                    }
                }
                
                break;
                
            case 'room_types':
                $fields['room_types'] = [
                    'type' => 'repeater',
                    'label' => 'Room Types',
                    'description' => 'Configure all room types available in your hotel',
                    'required' => true,
                    'fields' => [
                        'code' => [
                            'type' => 'text',
                            'label' => 'Room Code (PMS)',
                            'required' => true,
                            'placeholder' => 'e.g., STDK',
                            'description' => 'Must match exactly the code from your PMS system',
                        ],
                        'display_name' => [
                            'type' => 'text',
                            'label' => 'Display Name',
                            'required' => true,
                            'placeholder' => 'e.g., Standard Double Room',
                            'description' => 'Name shown to guests',
                        ],
                    ],
                ];
                break;
                
            case 'cancellation_policies':
                // Only show this section if PMS configuration allows it and cancellation policies are configured
                if ($project && $project->pmsType && $project->pmsType->shouldShowReservationPolicyField('cancellation_policies')) {
                        // Check if cancellation policies are configured in reservation_settings
                        $reservationSettings = $project->setupTeams()
                            ->where('team', 'reservation')
                            ->where('section', 'reservation_settings')
                            ->first();
                        
                        if ($reservationSettings && 
                            isset($reservationSettings->completed_fields['cancellation_policies_configured']) && 
                            $reservationSettings->completed_fields['cancellation_policies_configured'] === true) {
                            
                        $fields['cancellation_policies'] = [
                            'type' => 'repeater',
                            'label' => 'Cancellation Policies',
                            'description' => 'Configure all cancellation policies available in your hotel',
                            'required' => false,
                            'fields' => [
                                'code' => [
                                    'type' => 'text',
                                    'label' => 'Policy Code (PMS)',
                                    'required' => true,
                                    'placeholder' => 'e.g., 24H, 48H, NR',
                                    'description' => 'Must match exactly the code from your PMS system',
                                ],
                                'description' => [
                                    'type' => 'textarea',
                                    'label' => 'Description',
                                    'required' => true,
                                    'placeholder' => 'e.g., Free cancellation up to 24 hours before arrival',
                                    'description' => 'Clear description of the cancellation policy',
                                    'rows' => 3,
                                ],
                            ],
                        ];
                    }
                }
                break;
                
            case 'special_requests':
                // Only show this section if PMS configuration allows it and special requests are configured
                if ($project && $project->pmsType && $project->pmsType->shouldShowReservationPolicyField('special_requests')) {
                        // Check if special requests are configured in reservation_settings
                        $reservationSettings = $project->setupTeams()
                            ->where('team', 'reservation')
                            ->where('section', 'reservation_settings')
                            ->first();
                        
                        if ($reservationSettings && 
                            isset($reservationSettings->completed_fields['special_requests_configured']) && 
                            $reservationSettings->completed_fields['special_requests_configured'] === true) {
                            
                        $fields['special_requests'] = [
                            'type' => 'repeater',
                            'label' => 'Special Requests',
                            'description' => 'Configure all special requests available in your hotel',
                            'required' => false,
                            'fields' => [
                                'code' => [
                                    'type' => 'text',
                                    'label' => 'Request Code (PMS)',
                                    'required' => true,
                                    'placeholder' => 'e.g., NOSMO, SEAVW, QUIET',
                                    'description' => 'Must match exactly the code from your PMS system',
                                ],
                                'description' => [
                                    'type' => 'textarea',
                                    'label' => 'Description',
                                    'required' => true,
                                    'placeholder' => 'e.g., Non-smoking room, Sea view room, Quiet room',
                                    'description' => 'Clear description of the special request',
                                    'rows' => 3,
                                ],
                            ],
                        ];
                    }
                }
                break;
                
            case 'deposit_policies':
                // Only show this section if PMS configuration allows it and deposit policies are configured
                if ($project && $project->pmsType && $project->pmsType->shouldShowReservationPolicyField('deposit_policies')) {
                        // Check if deposit policies are configured in reservation_settings
                        $reservationSettings = $project->setupTeams()
                            ->where('team', 'reservation')
                            ->where('section', 'reservation_settings')
                            ->first();
                        
                        if ($reservationSettings && 
                            isset($reservationSettings->completed_fields['deposit_policies_configured']) && 
                            $reservationSettings->completed_fields['deposit_policies_configured'] === true) {
                            
                        $fields['deposit_policies'] = [
                            'type' => 'repeater',
                            'label' => 'Deposit Policies',
                            'description' => 'Configure all deposit policies available in your hotel',
                            'required' => false,
                            'fields' => [
                                'code' => [
                                    'type' => 'text',
                                    'label' => 'Policy Code (PMS)',
                                    'required' => true,
                                    'placeholder' => 'e.g., ADVDEP, FULLDEP, NODEP',
                                    'description' => 'Must match exactly the code from your PMS system',
                                ],
                                'description' => [
                                    'type' => 'textarea',
                                    'label' => 'Description',
                                    'required' => true,
                                    'placeholder' => 'e.g., 50% advance deposit required, Full payment required at booking',
                                    'description' => 'Clear description of the deposit policy',
                                    'rows' => 3,
                                ],
                            ],
                        ];
                    }
                }
                break;
                
            case 'payment_methods':
                // Only show this section if PMS configuration allows it and payment methods are configured
                if ($project && $project->pmsType && $project->pmsType->shouldShowReservationPolicyField('payment_methods')) {
                        // Check if payment methods are configured in reservation_settings
                        $reservationSettings = $project->setupTeams()
                            ->where('team', 'reservation')
                            ->where('section', 'reservation_settings')
                            ->first();
                        
                        if ($reservationSettings && 
                            isset($reservationSettings->completed_fields['payment_methods_configured']) && 
                            $reservationSettings->completed_fields['payment_methods_configured'] === true) {
                            
                        $fields['payment_methods'] = [
                            'type' => 'repeater',
                            'label' => 'Payment Methods',
                            'description' => 'Configure all payment methods available in your hotel',
                            'required' => false,
                            'fields' => [
                                'code' => [
                                    'type' => 'text',
                                    'label' => 'Payment Code (PMS)',
                                    'required' => true,
                                    'placeholder' => 'e.g., VISA, MC, AMEX, CASH',
                                    'description' => 'Must match exactly the code from your PMS system',
                                ],
                                'description' => [
                                    'type' => 'textarea',
                                    'label' => 'Description',
                                    'required' => true,
                                    'placeholder' => 'e.g., Visa Credit Card, MasterCard, American Express, Cash Payment',
                                    'description' => 'Clear description of the payment method',
                                    'rows' => 3,
                                ],
                            ],
                        ];
                    }
                }
                break;
                
            case 'transfer_types':
                // Only show this section if PMS configuration allows it and transfer types are configured
                if ($project && $project->pmsType && $project->pmsType->shouldShowReservationPolicyField('transfer_types')) {
                        // Check if transfer types are configured in reservation_settings
                        $reservationSettings = $project->setupTeams()
                            ->where('team', 'reservation')
                            ->where('section', 'reservation_settings')
                            ->first();
                        
                        if ($reservationSettings && 
                            isset($reservationSettings->completed_fields['transfer_types_configured']) && 
                            $reservationSettings->completed_fields['transfer_types_configured'] === true) {
                            
                        $fields['transfer_types'] = [
                            'type' => 'repeater',
                            'label' => 'Transfer Types',
                            'description' => 'Configure all transfer types available in your hotel',
                            'required' => false,
                            'fields' => [
                                'code' => [
                                    'type' => 'text',
                                    'label' => 'Transfer Code (PMS)',
                                    'required' => true,
                                    'placeholder' => 'e.g., APT, SHU, PVT, LUX',
                                    'description' => 'Must match exactly the code from your PMS system',
                                ],
                                'description' => [
                                    'type' => 'textarea',
                                    'label' => 'Description',
                                    'required' => true,
                                    'placeholder' => 'e.g., Airport Transfer, Shuttle Service, Private Transfer, Luxury Transfer',
                                    'description' => 'Clear description of the transfer type',
                                    'rows' => 3,
                                ],
                            ],
                        ];
                    }
                }
                break;
        }

        return $fields;
    }

    private static function getMarketingFields(string $section, Project $project): array
    {
        $fields = [];
        
        // Add example images for this section if available
        if ($project && $project->hotelBrand && $project->hotelBrand->example_images) {
            foreach ($project->hotelBrand->example_images as $index => $example) {
                // Check if this example is for the current section
                $sectionKey = $example['section_key'] ?? '';
                
                // Show example image if it matches the current section
                if ($sectionKey === $section || $sectionKey === 'all') {
                    $fields["example_image_{$sectionKey}"] = [
                        'type' => 'image_display',
                        'label' => ucfirst(str_replace('_', ' ', $sectionKey)) . ' Example',
                        'value' => $example['image'] ?? '',
                        'description' => $example['description'] ?? 'Reference image from brand guidelines',
                    ];
                }
            }
        }
        
        switch ($section) {
            case 'banner_pictures':
                $fields = [
                    'use_same_banner' => [
                        'type' => 'checkbox',
                        'label' => 'Use the same banner for all modules',
                        'description' => 'Check this to use one banner image across all modules',
                        'required' => false,
                    ],
                    'default_banner' => [
                        'type' => 'file',
                        'label' => 'Default Banner (All Modules)',
                        'description' => 'Main banner image (1920x600px recommended)',
                        'required' => false,
                        'accept' => 'image/*',
                        'depends_on' => 'use_same_banner',
                    ],
                ];
                
                // Get the project's selected modules
                if ($project && $project->modules) {
                    foreach ($project->modules as $module) {
                        $moduleKey = \Str::slug($module->name, '_') . '_banner';
                        $fields[$moduleKey] = [
                            'type' => 'file',
                            'label' => $module->name . ' Banner',
                            'description' => 'Banner for ' . $module->name . ' (1920x600px recommended)',
                            'required' => false,
                            'accept' => 'image/*',
                            'depends_on_not' => 'use_same_banner',
                        ];
                    }
                }
                
                return $fields;
                
            case 'room_details':
                // Get room types from reservation team
                $roomTypesSetup = self::where('project_id', $project->id)
                    ->where('team', 'reservation')
                    ->where('section', 'room_types')
                    ->first();
                
                $roomTypes = [];
                if ($roomTypesSetup && isset($roomTypesSetup->completed_fields['room_types'])) {
                    $roomTypes = $roomTypesSetup->completed_fields['room_types'];
                }
                
                if (empty($roomTypes)) {
                    $fields['no_room_types'] = [
                        'type' => 'info_display',
                        'label' => 'No Room Types Defined',
                        'description' => 'Please ask the Reservation Team to define room types first before adding details.',
                    ];
                    return $fields;
                }
                
                // Check if show_room_details_in_templates is enabled (for confirmation/pre-arrival)
                $reservationSetup = self::where('project_id', $project->id)
                    ->where('team', 'reservation')
                    ->where('section', 'reservation_settings')
                    ->first();
                    
                $showRoomDetailsInTemplates = false;
                if ($reservationSetup && isset($reservationSetup->completed_fields['show_room_details_in_templates'])) {
                    $showRoomDetailsInTemplates = $reservationSetup->completed_fields['show_room_details_in_templates'];
                }
                
                // Check which modules are selected to determine required fields
                $needsBasicDetails = $showRoomDetailsInTemplates; // Start with template toggle
                $needsShortDescription = false;
                $needsLongDescription = false;
                $needsMainImage = false;
                $needsAdditionalImages = false;
                
                if ($project && $project->modules) {
                    foreach ($project->modules as $module) {
                        if ($module->requires_room_details) {
                            $needsBasicDetails = true; // Any module requiring details activates basic details
                            
                            if ($module->requires_room_short_description) {
                                $needsShortDescription = true;
                            }
                            if ($module->requires_room_long_description) {
                                $needsLongDescription = true;
                            }
                            if ($module->requires_room_main_image) {
                                $needsMainImage = true;
                            }
                            if ($module->requires_room_slideshow_images) {
                                $needsAdditionalImages = true;
                            }
                        }
                    }
                }
                
                // If no details are needed at all, show info message
                if (!$needsBasicDetails && !$needsShortDescription && !$needsLongDescription && !$needsMainImage && !$needsAdditionalImages) {
                    $fields['no_room_details_needed'] = [
                        'type' => 'info_display',
                        'label' => 'Room Details Not Required',
                        'description' => 'Room details are not needed based on your current module selection and settings.',
                    ];
                    return $fields;
                }
                
                
                // Create fields for each room type
                foreach ($roomTypes as $index => $roomType) {
                    if (!isset($roomType['code']) || !isset($roomType['display_name'])) {
                        continue;
                    }
                    
                    $roomCode = $roomType['code'];
                    $displayName = $roomType['display_name'];
                    
                    // Add room section wrapper
                    $fields["room_{$index}_section"] = [
                        'type' => 'section_header',
                        'label' => $displayName,
                        'description' => "PMS Code: {$roomCode}",
                    ];
                    
                    // Group images together
                    $hasImages = ($showRoomDetailsInTemplates || $needsMainImage || $needsAdditionalImages);
                    if ($hasImages) {
                        $fields["room_{$index}_images_header"] = [
                            'type' => 'subsection_header',
                            'label' => 'Images',
                        ];
                        
                        if ($showRoomDetailsInTemplates || $needsMainImage) {
                            $fields["room_{$index}_main_image"] = [
                                'type' => 'file',
                                'label' => 'Main Image',
                                'required' => false,
                                'accept' => 'image/*',
                                'description' => 'Primary image (recommended: 1200x800px)',
                            ];
                        }
                        
                        if ($needsAdditionalImages) {
                            $fields["room_{$index}_additional_images_grid"] = [
                                'type' => 'grid',
                                'columns' => 2,
                                'fields' => [
                                    "room_{$index}_additional_image_1" => [
                                        'type' => 'file',
                                        'label' => 'Slideshow Image 1',
                                        'required' => false,
                                        'accept' => 'image/*',
                                        'description' => 'For landing page slideshow',
                                    ],
                                    "room_{$index}_additional_image_2" => [
                                        'type' => 'file',
                                        'label' => 'Slideshow Image 2',
                                        'required' => false,
                                        'accept' => 'image/*',
                                        'description' => 'For landing page slideshow',
                                    ],
                                ],
                            ];
                        }
                    }
                    
                    // Group descriptions together
                    $hasDescriptions = ($showRoomDetailsInTemplates || $needsShortDescription || $needsLongDescription);
                    if ($hasDescriptions) {
                        $fields["room_{$index}_descriptions_header"] = [
                            'type' => 'subsection_header',
                            'label' => 'Descriptions',
                        ];
                        
                        if ($showRoomDetailsInTemplates || $needsShortDescription) {
                            $fields["room_{$index}_short_description"] = [
                                'type' => 'textarea',
                                'label' => 'Short Description',
                                'required' => false,
                                'rows' => 2,
                                'placeholder' => 'Brief description for emails (max. 150 characters)',
                                'description' => 'Used in confirmation emails',
                            ];
                        }
                        
                        if ($needsLongDescription) {
                            $fields["room_{$index}_long_description"] = [
                                'type' => 'textarea',
                                'label' => 'Long Description',
                                'required' => false,
                                'rows' => 4,
                                'placeholder' => 'Detailed description highlighting room features and amenities',
                                'description' => 'Used in offers and upsell campaigns',
                            ];
                        }
                    }
                    
                    // Add separator between rooms (except for the last one)
                    if ($index < count($roomTypes) - 1) {
                        $fields["room_{$index}_separator"] = [
                            'type' => 'section_separator',
                        ];
                    }
                }
                
                return $fields;
                
            case 'logos':
                return [
                    'primary_logo' => [
                        'type' => 'file',
                        'label' => 'Primary Logo',
                        'description' => 'Main hotel logo (PNG format)',
                        'required' => false,
                        'accept' => 'image/png',
                    ],
                    'light_logo' => [
                        'type' => 'file',
                        'label' => 'Light Logo',
                        'description' => 'Light/white version of logo for dark backgrounds (PNG format)',
                        'required' => false,
                        'accept' => 'image/png',
                    ],
                ];
                
            case 'colors_fonts':
                // Get default values from brand if available
                $defaults = [];
                if ($project && $project->hotelBrand) {
                    $brand = $project->hotelBrand;
                    $defaults = [
                        'primary_color' => $brand->primary_color,
                        'secondary_color' => $brand->secondary_color,
                        'font_family' => $brand->font_family,
                        'heading_font_family' => $brand->heading_font_family,
                    ];
                }
                
                return [
                    'primary_color' => [
                        'type' => 'color',
                        'label' => 'Primary Brand Color',
                        'required' => false,
                        'default' => $defaults['primary_color'] ?? null,
                        'description' => $defaults['primary_color'] ? 'Pre-filled from brand settings' : null,
                    ],
                    'secondary_color' => [
                        'type' => 'color',
                        'label' => 'Secondary Brand Color',
                        'required' => false,
                        'default' => $defaults['secondary_color'] ?? null,
                        'description' => $defaults['secondary_color'] ? 'Pre-filled from brand settings' : null,
                    ],
                    'font_family' => [
                        'type' => 'text',
                        'label' => 'Body Font Family',
                        'required' => false,
                        'default' => $defaults['font_family'] ?? null,
                        'placeholder' => 'e.g., Arial, Helvetica, sans-serif',
                        'description' => $defaults['font_family'] ? 'Pre-filled from brand settings' : 'Font stack for body text',
                    ],
                    'heading_font_family' => [
                        'type' => 'text',
                        'label' => 'Heading Font Family',
                        'required' => false,
                        'default' => $defaults['heading_font_family'] ?? null,
                        'placeholder' => 'e.g., Georgia, serif',
                        'description' => $defaults['heading_font_family'] ? 'Pre-filled from brand settings' : 'Font stack for headings',
                    ],
                ];
                
            case 'greetings_texts':
                return [
                    'welcome_message' => [
                        'type' => 'textarea',
                        'label' => 'Welcome Message',
                        'description' => 'Message shown in confirmation emails',
                        'required' => false,
                    ],
                    'thank_you_message' => [
                        'type' => 'textarea',
                        'label' => 'Thank You Message',
                        'description' => 'Post-stay thank you message',
                        'required' => false,
                    ],
                ];
                
            case 'promotions':
                // Get available promotions for this brand
                if ($project && $project->hotelBrand) {
                    $brand = $project->hotelBrand;
                    $availablePromotions = $brand->promotions ?? [];
                    
                    if (empty($availablePromotions)) {
                        $fields['no_promotions'] = [
                            'type' => 'info_display',
                            'label' => 'No Promotions Available',
                            'description' => 'No promotion types have been configured for this brand. Please contact your administrator.',
                        ];
                        return $fields;
                    }
                    
                    // Add brand example images if available
                    if ($brand->example_images) {
                        foreach ($brand->example_images as $index => $example) {
                            if ($example['section'] === 'marketing' || $example['section'] === 'all') {
                                $fields["example_image_{$index}"] = [
                                    'type' => 'image_display',
                                    'label' => $example['label'] ?? 'Example',
                                    'value' => $example['image'] ?? '',
                                    'description' => 'Reference image from brand guidelines',
                                ];
                            }
                        }
                        
                        if (!empty($fields)) {
                            $fields['examples_separator'] = [
                                'type' => 'separator',
                            ];
                        }
                    }
                    
                    // Group promotions by type
                    $promotionsByType = [];
                    foreach ($availablePromotions as $promotion) {
                        $type = $promotion['type'] ?? 'promotion';
                        if (!isset($promotionsByType[$type])) {
                            $promotionsByType[$type] = $promotion;
                        }
                    }
                    
                    // Create repeater fields for each promotion type
                    foreach ($promotionsByType as $type => $promotionConfig) {
                        // Get promotion type label
                        $typeLabels = [
                            'promotion' => 'Promotions',
                            'promotion_tiles' => 'Promotion Tiles',
                            'concierge_perfect_day' => 'Concierge Perfect Day',
                        ];
                        $typeLabel = $typeLabels[$type] ?? $type;
                        
                        // Recommended count
                        $recommendedCount = in_array($type, ['promotion', 'promotion_tiles']) ? 4 : 1;
                        
                        // Add promotion type header
                        $fields["{$type}_header"] = [
                            'type' => 'section_header',
                            'label' => $typeLabel,
                            'description' => $recommendedCount > 1 ? "Recommended: {$recommendedCount} items" : "Configure content for this promotion type",
                        ];
                        
                        // Add example image if available
                        if (!empty($promotionConfig['example_image'])) {
                            $fields["{$type}_example"] = [
                                'type' => 'image_display',
                                'label' => 'Example',
                                'value' => $promotionConfig['example_image'],
                                'description' => 'Reference example for this promotion type',
                            ];
                        }
                        
                        // Build schema for repeater based on promotion configuration
                        $repeaterSchema = [];
                        
                        // Priority field is always shown for all promotions
                        $repeaterSchema['priority'] = [
                            'type' => 'select',
                            'label' => 'Priority',
                            'required' => true,
                            'options' => [
                                '1' => '1 (Top Left)',
                                '2' => '2',
                                '3' => '3',
                                '4' => '4',
                                '5' => '5',
                                '6' => '6',
                                '7' => '7',
                                '8' => '8',
                                '9' => '9',
                                '10' => '10 (Bottom Right)',
                            ],
                            'description' => 'Display position (1 = top left, 10 = bottom right)',
                        ];
                        
                        if (!empty($promotionConfig['show_title'])) {
                            $repeaterSchema['title'] = [
                                'type' => 'text',
                                'label' => 'Title',
                                'required' => false,
                                'placeholder' => 'Enter promotion title',
                            ];
                        }
                        
                        if (!empty($promotionConfig['show_text'])) {
                            $repeaterSchema['text'] = [
                                'type' => 'textarea',
                                'label' => 'Description',
                                'required' => false,
                                'rows' => 3,
                                'placeholder' => 'Enter description',
                            ];
                        }
                        
                        if (!empty($promotionConfig['show_button']) && !empty($promotionConfig['show_url'])) {
                            $repeaterSchema['button_text'] = [
                                'type' => 'text',
                                'label' => 'Button Text',
                                'required' => false,
                                'placeholder' => 'Learn More',
                            ];
                        }
                        
                        if (!empty($promotionConfig['show_url'])) {
                            $repeaterSchema['url'] = [
                                'type' => 'url',
                                'label' => 'Link URL',
                                'required' => false,
                                'placeholder' => 'https://example.com',
                            ];
                        }
                        
                        if (!empty($promotionConfig['show_image'])) {
                            $repeaterSchema['image'] = [
                                'type' => 'file',
                                'label' => 'Image',
                                'required' => true,
                                'accept' => 'image/*',
                                'description' => 'Upload promotion image',
                            ];
                        }
                        
                        if (!empty($promotionConfig['show_icon'])) {
                            $repeaterSchema['icon'] = [
                                'type' => 'file',
                                'label' => 'Icon',
                                'required' => false,
                                'accept' => 'image/*',
                                'description' => 'Upload icon image',
                            ];
                        }
                        
                        
                        // Add the repeater field
                        $fields["{$type}_items"] = [
                            'type' => 'repeater',
                            'label' => false,
                            'required' => true,
                            'fields' => $repeaterSchema,
                            'min_items' => 0,
                            'max_items' => $type === 'concierge_perfect_day' ? 1 : 10,
                            'default_items' => 0,
                            'add_button_label' => "Add {$typeLabel}",
                        ];
                        
                        // Add separator between promotion types
                        $fields["{$type}_separator"] = [
                            'type' => 'section_separator',
                        ];
                    }
                    
                    // Remove last separator
                    array_pop($fields);
                }
                break;
        }

        return $fields;
    }

    private static function getITFields(string $section, Project $project): array
    {
        $fields = [];
        
        // Add brand IT configuration info if available
        if ($project && $project->hotelBrand) {
            // Add IT configuration instructions
            if ($project->hotelBrand->it_configuration && is_array($project->hotelBrand->it_configuration)) {
                foreach ($project->hotelBrand->it_configuration as $index => $config) {
                    $fields["it_info_{$index}"] = [
                        'type' => 'info_display',
                        'label' => $config['label'] ?? 'Information',
                        'description' => $config['description'] ?? '',
                        'example' => $config['example'] ?? null,
                    ];
                }
            }
            
            // Add example images for this section if available
            if ($project->hotelBrand->example_images && is_array($project->hotelBrand->example_images)) {
                foreach ($project->hotelBrand->example_images as $index => $example) {
                    // Check if this example is for the current section
                    $sectionKey = $example['section_key'] ?? '';
                    
                    // Show example image if it matches the current section
                    if ($sectionKey === $section || $sectionKey === 'all') {
                        $fields["example_image_{$sectionKey}"] = [
                            'type' => 'image_display',
                            'label' => ucfirst(str_replace('_', ' ', $sectionKey)) . ' Example',
                            'value' => $example['image'] ?? '',
                            'description' => $example['description'] ?? 'Reference image from brand guidelines',
                        ];
                    }
                }
            }
        }

        switch ($section) {
            case 'it_settings':
                // Get PMS-specific IT settings fields
                if ($project->pms_type_id && !$project->relationLoaded('pmsType')) {
                    $project->load('pmsType');
                }
                
                if ($project->pmsType && $project->pmsType->setup_requirements && is_array($project->pmsType->setup_requirements)) {
                    // Convert the setup_requirements array to proper field format
                    $setupRequirements = $project->pmsType->setup_requirements;
                    
                    
                    foreach ($setupRequirements as $requirement) {
                        // Skip if no label
                        if (!isset($requirement['label'])) {
                            continue;
                        }
                        
                        // Generate field_name if not set
                        $fieldKey = $requirement['field_name'] ?? Str::slug(str_replace(' ', '_', strtolower($requirement['label'])), '_');
                        
                        // Build the field configuration
                        $fields[$fieldKey] = [
                            'type' => $requirement['type'] ?? 'text',
                            'label' => $requirement['label'],
                            'description' => $requirement['description'] ?? '',
                            'required' => $requirement['required'] ?? false,
                            'validation' => $requirement['validation'] ?? '',
                        ];
                        
                        // Add options for select fields
                        if ($requirement['type'] === 'select' && !empty($requirement['options'])) {
                            $fields[$fieldKey]['options'] = array_combine($requirement['options'], $requirement['options']);
                        }
                        
                        // Add conditions if they exist
                        if (!empty($requirement['conditions']) && is_array($requirement['conditions'])) {
                            $fields[$fieldKey]['conditions'] = $requirement['conditions'];
                        }
                    }
                }
                break;
                
                
        }

        return $fields;
    }
}