<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectSetupTeam;
use Illuminate\Http\Request;

class RoomDataController extends Controller
{
    /**
     * Check if a room type has associated marketing data
     */
    public function checkRoomMarketingData(Request $request, Project $project, int $roomIndex)
    {
        // Ensure the user has access to this project
        if ($request->attributes->get('project')->id !== $project->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Get the room details setup
        $roomDetailsSetup = ProjectSetupTeam::where('project_id', $project->id)
            ->where('team', 'marketing')
            ->where('section', 'room_details')
            ->first();

        if (!$roomDetailsSetup || !$roomDetailsSetup->completed_fields) {
            return response()->json(['hasMarketingData' => false]);
        }

        // Check for any fields related to this room index
        $hasMarketingData = false;
        $marketingFields = [];
        
        foreach ($roomDetailsSetup->completed_fields as $key => $value) {
            // Check if this field belongs to the room at the specified index
            if (preg_match("/^room_{$roomIndex}_/", $key)) {
                // Check if the field has actual content
                if (!empty($value) && $value !== '' && $value !== null) {
                    $hasMarketingData = true;
                    $marketingFields[] = $key;
                }
            }
        }

        return response()->json([
            'hasMarketingData' => $hasMarketingData,
            'fields' => $marketingFields
        ]);
    }
}