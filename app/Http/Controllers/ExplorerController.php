<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\Clients;

class ExplorerController extends Controller
{
    public function __construct()
    {
       // Init Clients model object
        $this->clients = new Clients();
    }

    // Cost data of all the clients and their projects 
    public function indexAction(Request $request) {
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
