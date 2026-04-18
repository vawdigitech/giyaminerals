<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Site;
use App\Models\Factory;
use Illuminate\Http\Request;

class WorkLocationController extends Controller
{
    /**
     * Get work location selection options for the app
     * This is called when the app opens to determine what to show
     */
    public function getOptions(Request $request)
    {
        $user = $request->user();

        // For supervisors - they are assigned to a specific site
        if ($user->isSupervisor() && $user->site_id) {
            $site = Site::with('projects')->find($user->site_id);

            return response()->json([
                'success' => true,
                'data' => [
                    'selection_type' => 'site', // Fixed to site for supervisors
                    'require_selection' => true,
                    'site' => $site,
                    'projects' => $site->projects ?? [],
                    'message' => 'Please select a project to continue',
                ],
            ]);
        }

        // For admins/managers - show both factory and site options
        $sites = Site::with('projects')->orderBy('name')->get();
        $factories = Factory::with('projects')->orderBy('name')->get();

        return response()->json([
            'success' => true,
            'data' => [
                'selection_type' => 'both', // User can choose factory or site
                'require_selection' => true,
                'sites' => $sites,
                'factories' => $factories,
                'message' => 'Please select work location type',
            ],
        ]);
    }

    /**
     * Get details for a specific site including projects
     */
    public function getSiteDetails(Request $request, $siteId)
    {
        $site = Site::with(['projects' => function ($query) {
            $query->with(['sites', 'factories']);
        }])->findOrFail($siteId);

        // Add location information to each project
        $projects = $site->projects->map(function ($project) {
            return [
                'id' => $project->id,
                'code' => $project->code,
                'name' => $project->name,
                'description' => $project->description,
                'status' => $project->status,
                'quoted_amount' => $project->quoted_amount,
                'locations' => $project->locations()->toArray(),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'site' => $site,
                'projects' => $projects,
                'message' => 'Please select a project',
            ],
        ]);
    }

    /**
     * Get details for a specific factory including projects
     */
    public function getFactoryDetails(Request $request, $factoryId)
    {
        $factory = Factory::with(['projects' => function ($query) {
            $query->with(['sites', 'factories']);
        }])->findOrFail($factoryId);

        // Add location information to each project
        $projects = $factory->projects->map(function ($project) {
            return [
                'id' => $project->id,
                'code' => $project->code,
                'name' => $project->name,
                'description' => $project->description,
                'status' => $project->status,
                'quoted_amount' => $project->quoted_amount,
                'locations' => $project->locations()->toArray(),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'factory' => $factory,
                'projects' => $projects,
                'message' => 'Please select a project',
            ],
        ]);
    }
}
