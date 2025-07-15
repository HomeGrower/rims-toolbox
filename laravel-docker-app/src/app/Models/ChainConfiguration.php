<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChainConfiguration extends Model
{
    protected $fillable = [
        'hotel_chain_id',
        'configuration_type',
        'team',
        'settings',
        'instructions',
        'additional_fields',
        'field_overrides',
        'is_active',
    ];

    protected $casts = [
        'settings' => 'array',
        'instructions' => 'array',
        'additional_fields' => 'array',
        'field_overrides' => 'array',
        'is_active' => 'boolean',
    ];

    const CONFIGURATION_TYPES = [
        'email' => 'Email Configuration',
        'it_setup' => 'IT Setup Requirements',
        'reservation' => 'Reservation Settings',
        'marketing' => 'Marketing Requirements',
    ];

    const TEAMS = [
        'it' => 'IT Team',
        'reservation' => 'Reservation Team',
        'marketing' => 'Marketing Team',
    ];

    public function hotelChain(): BelongsTo
    {
        return $this->belongsTo(HotelChain::class);
    }

    /**
     * Get merged fields for a specific section
     */
    public function getMergedFields(array $baseFields): array
    {
        $mergedFields = $baseFields;

        // Add additional fields
        if (!empty($this->additional_fields)) {
            foreach ($this->additional_fields as $fieldKey => $fieldConfig) {
                $mergedFields[$fieldKey] = $fieldConfig;
            }
        }

        // Apply field overrides
        if (!empty($this->field_overrides)) {
            foreach ($this->field_overrides as $fieldKey => $overrides) {
                if (isset($mergedFields[$fieldKey])) {
                    $mergedFields[$fieldKey] = array_merge($mergedFields[$fieldKey], $overrides);
                }
            }
        }

        return $mergedFields;
    }

    /**
     * Example Anantara email configuration
     */
    public static function getAnantaraEmailConfig(): array
    {
        return [
            'settings' => [
                'no_internet_access' => true,
                'cannot_send_from_domain' => '@anantara.com',
                'must_use_subdomain' => 'res.anantara.com',
                'spf_configured' => true,
                'dkim_configured' => true,
            ],
            'instructions' => [
                'it' => [
                    'steps' => [
                        '1. Use <abcd>@res.anantara.com for sending emails',
                        '2. Contact Minor IT to create email forwarding',
                        '3. Forward from res.anantara.com to actual email address',
                        '4. SPF and DKIM are already configured',
                    ],
                    'warnings' => [
                        'Opera does not have internet access',
                        'Cannot send emails from @anantara.com domain',
                    ],
                ],
            ],
            'additional_fields' => [
                'forwarding_email' => [
                    'type' => 'email',
                    'label' => 'Forwarding Target Email',
                    'description' => 'Email address where res.anantara.com emails should be forwarded',
                    'required' => true,
                ],
                'minor_it_ticket' => [
                    'type' => 'text',
                    'label' => 'Minor IT Ticket Number',
                    'description' => 'Ticket number for forwarding setup request',
                    'required' => false,
                ],
            ],
        ];
    }
}