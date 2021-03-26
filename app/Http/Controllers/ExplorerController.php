<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\Clients;
use App\Models\Costs;
use App\Models\CostTypes;
use App\Models\Projects;
use DB;

class ExplorerController extends Controller
{
    public function __construct()
    {
       // Init Clients model object
        $this->clients = new Clients();
    }

    // Cost data of all the clients and their projects 
    public function indexOldAction(Request $request) {
        $clientIds = $request->query('client_id') ? $request->query('client_id') : [];
        $costTypeId = $request->query('cost_type_id') ? $request->query('cost_type_id') : [];
        $projectId = $request->query('project_id') ? $request->query('project_id') : [];
        
        // Get all client's details
        $clientDetails = $this->clients->getCostDataForAllClients($clientIds, $projectId, $costTypeId);
        
        // Get Project Details with cost
        $projectCost = $this->clients->getProjectCost($clientIds, $projectId, $costTypeId);
        $projectCostByCustomKey = $this->assignValueAsKey(json_decode(json_encode($projectCost)), 'id');
        
        // Get Project Cost
        $projectByCostType = $this->clients->getProjectDetailsByCostType($clientIds, $projectId, $costTypeId);
        $validProjectByCostType = $this->assignValueAsKey(json_decode(json_encode($projectByCostType)), 'project_id');

        // Assign project children to project array
        $projectChildren = $this->assignChildren($projectCostByCustomKey, $validProjectByCostType, 'id');

        $clientProjects = $this->clients->getClientProjects($clientIds, $costTypeId, $projectId);
        $projectWithCostChildren = $this->assignChildren(json_decode(json_encode($clientProjects), true), json_decode(json_encode($projectChildren), true), 'id');
        // Valid project of client
        $validProject = $this->assignValueAsKey(json_decode(json_encode($clientProjects)), 'client_id');
        
        // Assign projects as children to client's object
        $clientWithProjectChildren = $this->assignChildren(json_decode(json_encode($clientDetails), true), $validProject, 'id');
        if(count($clientWithProjectChildren) > 0) {
            foreach ($clientWithProjectChildren as $key => $clientData) {
                if(is_array($clientData['children']) && count($clientData['children']) > 0){
                    foreach ($clientData['children'] as $childrenKey => $projectChildrenValue) {
                        
                        if(array_key_exists($projectChildrenValue['id'], $projectChildren)){
                            $projectChildrenValue['children'] = $projectChildren[$projectChildrenValue['id']][0]['children'];
                            $clientWithProjectChildren[$key]['children'][$childrenKey] = $projectChildrenValue;
                        }
                    }
                }
            }
        }
        $finalResult = json_encode($clientWithProjectChildren, JSON_PRETTY_PRINT);
        print_r($finalResult);
    }

    /**
     * Cost data of all the clients and their projects 
     * Using eloquent method for data fetching
     * @request array
     * @return json
    **/
    public function indexAction(Request $request) {
        $clientIds = $request->query('client_id') ? $request->query('client_id') : [];
        $costTypeId = $request->query('cost_type_id') ? $request->query('cost_type_id') : [];
        $projectIds = $request->query('project_id') ? $request->query('project_id') : [];

        // Clients LIST
        $clients = Clients::select('clients.id', 'clients.name', DB::raw('"client" as type'), DB::raw('SUM(costs.amount) AS amount'), DB::raw('"[]" as children'))
            ->join('projects', 'projects.client_id', '=', 'clients.id')
            ->join('costs', 'projects.id', '=', 'costs.project_id')
            ->join('cost_types', 'costs.cost_type_id', '=', 'cost_types.id');
        if (count($clientIds) > 0) {
            $clients->whereIn('clients.id', $clientIds);
        }
        $clients = $clients->groupBy('clients.id')->get()->toArray();

        // Client's Project LIST
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
        $clientProjects = $projectDetails->groupBy('projects.id')->get();
        $clientProjects = $clientProjects->toArray();

        // Get Project Details with cost
        $projectCost = Costs::select('clients.id as client_id', 'projects.id', DB::raw('SUM(costs.amount) AS amount'), 'projects.title', DB::raw('"cost" as type'), DB::raw('"[]" as children'))
                        ->join('projects', 'projects.id', '=', 'costs.project_id')
                        ->join('clients', 'clients.id', '=', 'projects.client_id')
                        ->join('cost_types', 'costs.cost_type_id', '=', 'cost_types.id');
        if (count($clientIds)) {
            $projectCost->whereIn('projects.client_id', $clientIds);
        }
        if (count($projectIds)) {
            $projectCost->whereIn('projects.id', $projectIds);
        }
        $projectCost = $projectCost
                                ->groupBy('projects.id')
                                ->get();
        $projectCost = $projectCost->toArray();
        $projectCostByCustomKey = $this->assignValueAsKey($projectCost, 'id');

        // Get Project Cost
        $projectByCostType = DB::table('cost_types')
                        ->select('cost_types.id', DB::raw('"cost" as type'), 'cost_types.name', DB::raw('SUM(costs.amount) AS amount'), DB::raw('projects.id AS project_id'), DB::raw('"[]" as children'))
                        ->join('costs', 'costs.cost_type_id', '=', 'cost_types.id')
                        ->join('projects', 'projects.id', '=', 'costs.project_id')
                        ->join('clients', 'clients.id', '=', 'projects.client_id');
        if (count($clientIds)) {
            $projectByCostType->whereIn('projects.client_id', $clientIds);
        }
        if (count($projectIds)) {
            $projectByCostType->whereIn('projects.id', $projectIds);
        }

        if (count($costTypeId)) {
            $projectByCostType->whereIn('cost_types.id', $costTypeId);
            $projectByCostType->orWhereIn('cost_types.parent_id', $costTypeId);

        }
        $projectByCostType = $projectByCostType
                                ->groupBy('cost_types.id')
                                ->groupBy('costs.project_id')
                                ->groupBy('projects.client_id')
                                ->get()
                                ->toArray();

        $validProjectByCostType = $this->assignValueAsKey($projectByCostType, 'project_id');

        // Assign project children to project array
        $projectChildren = $this->assignChildren($projectCostByCustomKey, $validProjectByCostType, 'id');

        $projectWithCostChildren = $this->assignChildren($clientProjects, $projectChildren, 'id');
        echo '<pre>';print_r($projectWithCostChildren);die;
        
        // Valid project of client
        $validProject = $this->assignValueAsKey($clientProjects, 'client_id');
        
        // Assign projects as children to client's object
        $clientWithProjectChildren = $this->assignChildren($clients, $validProject, 'id');
        if(count($clientWithProjectChildren) > 0) {
            foreach ($clientWithProjectChildren as $key => $clientData) {
                if(is_array($clientData['children']) && count($clientData['children']) > 0){
                    foreach ($clientData['children'] as $childrenKey => $projectChildrenValue) {
                        
                        if(array_key_exists($projectChildrenValue['id'], $projectChildren)){
                            $projectChildrenValue['children'] = $projectChildren[$projectChildrenValue['id']][0]['children'];
                            $clientWithProjectChildren[$key]['children'][$childrenKey] = $projectChildrenValue;
                        }
                    }
                }
            }
        }
        $finalResult = json_encode($clientWithProjectChildren, JSON_PRETTY_PRINT);
        print_r($finalResult);
    }

    /**
     * Cost data of all the clients and their projects 
     * Using eloquent relation method for data fetching
     * @request array
     * @return json
    **/
    public function allChildrenWithSingleQueryAndRelationAction(Request $request) {
        $clientIds = $request->query('client_id') ? $request->query('client_id') : [];
        $costTypeId = $request->query('cost_type_id') ? $request->query('cost_type_id') : [];
        $projectIds = $request->query('project_id') ? $request->query('project_id') : [];

        // Clients LIST
        $clients = Clients::select('clients.id', 'clients.name', DB::raw('"client" as type'), DB::raw('SUM(costs.amount) AS amount'), DB::raw('"[]" as children'))
            ->join('projects', 'projects.client_id', '=', 'clients.id')
            ->join('costs', 'projects.id', '=', 'costs.project_id')
            ->join('cost_types', 'costs.cost_type_id', '=', 'cost_types.id')
            ->with(['children.children.children.parent.children']);
        if (count($clientIds) > 0) {
            $clients->whereIn('clients.id', $clientIds);
        }
        $clients = $clients->groupBy('clients.id')->get()->toArray();

        $finalResult = json_encode($clients, JSON_PRETTY_PRINT);
        print_r($finalResult);
    }

    // Make array value as key
    private function assignValueAsKey($dataArray, $keyName) {
        $array = [];
        foreach ($dataArray as $key => $data) {
            $data = (array) $data;
            if (in_array($data[$keyName], $array)) {
               $array[$data[$keyName]][] = $data;
            } else {
                $array[$data[$keyName]][] = $data;
            }
        }
        return $array;
    }

    // Assign children by key
    private function assignChildren($parentArray, $childrenArray, $keyName) {
        foreach ($parentArray as $key => $data) {
            $data = (array) $data;
            if(isset($data[0])) {
                $data = $data[0];
                if (isset($childrenArray[$data[$keyName]])) {
                    $parentArray[$key][0]['children'] = $childrenArray[$data[$keyName]];
                }
            } else {
                if (isset($childrenArray[$data[$keyName]])) {
                    if(isset($data[0]))
                        $parentArray[$key][0]['children'] = $childrenArray[$data[$keyName]];
                    else
                        $parentArray[$key]['children'] = $childrenArray[$data[$keyName]];
                }
            }
        }
        return $parentArray;
    }
}
