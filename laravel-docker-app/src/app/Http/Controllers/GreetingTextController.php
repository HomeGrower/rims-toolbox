<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Condition;
use App\Models\GreetingParagraph;
use App\Models\ProjectSetupTeam;
use Illuminate\Http\Request;
use Inertia\Inertia;

class GreetingTextController extends Controller
{
    public function index(Request $request)
    {
        $project = $request->attributes->get('project');
        
        // Load necessary relationships
        $project->load(['hotelBrand', 'hotelChain', 'modules']);
        
        // Ensure greetings section exists
        $greetingsSection = $project->setupTeams()
            ->where('team', 'marketing')
            ->where('section', 'greetings_texts')
            ->firstOrCreate([
                'team' => 'marketing',
                'section' => 'greetings_texts',
                'project_id' => $project->id,
            ]);
        
        // Get all conditions (general + chain specific)
        $conditions = Condition::active()
            ->forChain($project->hotel_chain_id)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
        
        // Get all greeting paragraphs for this project
        $paragraphs = GreetingParagraph::where('project_id', $project->id)
            ->active()
            ->orderBy('paragraph_number')
            ->orderBy('priority')
            ->get();
        
        // Get modules for this project - only mailing and single_message modules
        $modules = $project->modules
            ->filter(function ($module) {
                return in_array($module->category, ['mailing', 'single_message']);
            })
            ->map(function ($module) {
                return [
                    'id' => $module->id,
                    'name' => $module->name,
                    'category' => $module->category,
                ];
            })
            ->values();
            
        // If no modules, include all modules as fallback
        if ($modules->isEmpty()) {
            $modules = $project->modules->map(function ($module) {
                return [
                    'id' => $module->id,
                    'name' => $module->name,
                    'category' => $module->category,
                ];
            });
        }
        
        return Inertia::render('Setup/GreetingTexts', [
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
                'hotel_name' => $project->hotel_name,
            ],
            'modules' => $modules,
            'conditions' => $conditions,
            'paragraphs' => $paragraphs,
        ]);
    }
    
    public function store(Request $request)
    {
        $project = $request->attributes->get('project');
        
        $validated = $request->validate([
            'paragraph_number' => 'required|integer|min:1|max:10',
            'priority' => 'required|integer|min:1|max:10',
            'content' => 'required|string',
            'modules' => 'nullable|array',
            'modules.*' => 'exists:modules,id',
            'show_if_conditions' => 'nullable|array',
            'show_if_conditions.*' => 'exists:conditions,id',
            'hide_if_conditions' => 'nullable|array',
            'hide_if_conditions.*' => 'exists:conditions,id',
        ]);
        
        $validated['project_id'] = $project->id;
        
        $paragraph = GreetingParagraph::create($validated);
        
        // Update progress for greetings section
        $this->updateGreetingsProgress($project);
        
        return redirect()->back()->with('success', 'Paragraph created successfully');
    }
    
    public function update(Request $request, $paragraphId)
    {
        $project = $request->attributes->get('project');
        
        $paragraph = GreetingParagraph::where('project_id', $project->id)
            ->findOrFail($paragraphId);
        
        $validated = $request->validate([
            'paragraph_number' => 'required|integer|min:1|max:10',
            'priority' => 'required|integer|min:1|max:10',
            'content' => 'required|string',
            'modules' => 'nullable|array',
            'modules.*' => 'exists:modules,id',
            'show_if_conditions' => 'nullable|array',
            'show_if_conditions.*' => 'exists:conditions,id',
            'hide_if_conditions' => 'nullable|array',
            'hide_if_conditions.*' => 'exists:conditions,id',
            'is_active' => 'boolean',
        ]);
        
        $paragraph->update($validated);
        
        // Update progress for greetings section
        $this->updateGreetingsProgress($project);
        
        return redirect()->back()->with('success', 'Paragraph updated successfully');
    }
    
    public function destroy(Request $request, $paragraphId)
    {
        $project = $request->attributes->get('project');
        
        $paragraph = GreetingParagraph::where('project_id', $project->id)
            ->findOrFail($paragraphId);
        
        $paragraph->delete();
        
        // Update progress for greetings section
        $this->updateGreetingsProgress($project);
        
        return redirect()->back()->with('success', 'Paragraph deleted successfully');
    }
    
    public function preview(Request $request)
    {
        $project = $request->attributes->get('project');
        
        $moduleId = $request->input('module_id');
        $conditionIds = $request->input('conditions', []);
        
        // Build the greeting text based on module and conditions
        $greetingText = $this->buildGreetingText($project->id, $moduleId, $conditionIds);
        
        return response()->json([
            'text' => $greetingText,
            'paragraphs' => $greetingText,
        ]);
    }
    
    private function buildGreetingText($projectId, $moduleId, $conditionIds)
    {
        $compiledParagraphs = [];
        
        // For each paragraph number (1-10)
        for ($num = 1; $num <= 10; $num++) {
            // Get all paragraphs for this number, ordered by priority
            $paragraphs = GreetingParagraph::where('project_id', $projectId)
                ->active()
                ->forParagraph($num)
                ->forModule($moduleId)
                ->orderBy('priority')
                ->get();
            
            // Find the first paragraph that meets conditions
            foreach ($paragraphs as $paragraph) {
                if ($paragraph->meetsConditions($conditionIds)) {
                    $compiledParagraphs[] = [
                        'number' => $num,
                        'content' => $paragraph->content,
                        'id' => $paragraph->id,
                    ];
                    break; // Use first matching paragraph
                }
            }
        }
        
        return $compiledParagraphs;
    }
    
    private function updateGreetingsProgress(Project $project): void
    {
        // Find or create the greetings section record
        $greetingsSection = $project->setupTeams()
            ->where('team', 'marketing')
            ->where('section', 'greetings_texts')
            ->first();
        
        if ($greetingsSection) {
            // Recalculate progress
            $greetingsSection->updateProgress();
            $greetingsSection->save();
        }
    }
}