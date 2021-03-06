<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Eloquent;

class Clients extends Eloquent
{
    // @var string $table
    // This protected member contains table name
    protected $table = 'clients';

    public function getCostDataForAllClients($clientIds = [])
    {
        $clientProjects = [];
        $clientDetails = Clients::select('clients.id', 'clients.name', DB::raw('"client" as type'), DB::raw('SUM(costs.amount) AS amount'), DB::raw('"[]" as children'))
                        ->join('projects', 'projects.client_id', '=', 'clients.id')
                        ->join('costs', 'projects.id', '=', 'costs.project_id')
                        ->join('cost_types', 'costs.cost_type_id', '=', 'cost_types.id');
        if (count($clientIds) > 0) {
            $clientDetails->whereIn('clients.id', $clientIds);
        }

        $clientDetails = $clientDetails->groupBy('clients.id')->get();
        if($clientDetails){
            return $clientDetails;
        }else{
            return false;
        }
    }

    public function getClientProjects($clientIds = [], $projectIds = [])
    {
        $projectDetails = Projects::select('clients.id as client_id', 'projects.id', 'projects.title', DB::raw('"project" as type'), DB::raw('SUM(costs.amount) AS amount'), DB::raw('"[]" as children'))
                        ->join('clients', 'clients.id', '=', 'projects.client_id')
                        ->join('costs', 'projects.id', '=', 'costs.project_id')
                        ->join('cost_types', 'costs.cost_type_id', '=', 'cost_types.id');
        if (count($clientIds) > 0) {
            $projectDetails->whereIn('clients.id', $clientIds);
        }
        if (count($projectIds) > 0) {
            $projectDetails->whereIn('projects.id', $projectIds);
        }
        $projectDetails = $projectDetails->groupBy('projects.id')->get();
        if($projectDetails){
            return $projectDetails;
        }else{
            return false;
        }
    }

    public function getProjectCost($clientIds = [], $projectIds = [])
    {
        $projectCostDetails = DB::table('costs')
                        ->select('clients.id as client_id', 'projects.id', DB::raw('SUM(costs.amount) AS amount'), 'projects.title', DB::raw('"cost" as type'))
                        ->join('projects', 'projects.id', '=', 'costs.project_id')
                        ->join('clients', 'clients.id', '=', 'projects.client_id')
                        ->join('cost_types', 'costs.cost_type_id', '=', 'cost_types.id');
        if (count($clientIds)) {
            $projectCostDetails->whereIn('projects.client_id', $clientIds);
        }
        if (count($projectIds)) {
            $projectCostDetails->whereIn('projects.id', $projectIds);
        }
        $projectCostDetails = $projectCostDetails
                                ->groupBy('projects.id')
                                ->get();
        if($projectCostDetails){
            return $projectCostDetails;
        }else{
            return false;
        }
    }

    public function getProjectDetailsByCostType($clientIds = [], $projectIds = [], $costTypeId = [])
    {
        $projectCostDetails = DB::table('cost_types')
                        ->select('cost_types.id', DB::raw('"cost" as type'), 'cost_types.name', DB::raw('SUM(costs.amount) AS amount'), DB::raw('projects.id AS project_id'))
                        ->join('costs', 'costs.cost_type_id', '=', 'cost_types.id')
                        ->join('projects', 'projects.id', '=', 'costs.project_id')
                        ->join('clients', 'clients.id', '=', 'projects.client_id');
        if (count($clientIds)) {
            $projectCostDetails->whereIn('projects.client_id', $clientIds);
        }
        if (count($projectIds)) {
            $projectCostDetails->whereIn('projects.id', $projectIds);
        }

        if (count($costTypeId)) {
            $projectCostDetails->whereIn('cost_types.id', $costTypeId);
            $projectCostDetails->orWhereIn('cost_types.parent_id', $costTypeId);

        }
        $projectCostDetails = $projectCostDetails
                                ->groupBy('cost_types.id')
                                ->groupBy('costs.project_id')
                                ->groupBy('projects.client_id')
                                ->get();
        if($projectCostDetails){
            return $projectCostDetails;
        }else{
            return false;
        }
    }

    /**
     * Get the value of title.
     *
     * @return string
     */
    public function getType()
    {
        return 'client';
    }

    public function getProjects() { 
        return $this->hasMany('App\Models\Projects', 'client_id'); 
    }

    /**
     * Get all distinct tags attached to all posts by author.
     * Can't use hasManyThrough on ManyToMany relationships, so we do this instead.
     *
     * @return array
     */
    public function projects()
    {
        $projects = Projects::select('clients.id as client_id', 'projects.id', 'projects.title', DB::raw('"project" as type'), DB::raw('SUM(costs.amount) AS amount'))
            ->join('clients', 'clients.id', '=', 'projects.client_id')
            ->join('costs', 'projects.id', '=', 'costs.project_id')
            ->join('cost_types', 'costs.cost_type_id', '=', 'cost_types.id');

        $projects = $projects->groupBy('projects.id')->get();

        if (count($clientIds) > 0) {
            $projectDetails->whereIn('clients.id', $clientIds);
        }
        if (count($projectIds) > 0) {
            $projectDetails->whereIn('projects.id', $projectIds);
        }

        return $projects;
    }

    // results in a "problem", se examples below
    public function children() {
        return $this->getProjects();
    }


}
