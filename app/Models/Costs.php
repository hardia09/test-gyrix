<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Eloquent;

class Costs extends Eloquent
{
	/**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'costs';

    //Add extra attribute
	protected $attributes = ['type', 'amount'];

	//Make it available in the json response
	protected $appends = ['type', 'amount'];

	//implement the attribute
	public function getTypeAttribute()
	{
	    return 'cost';
	}

	//implement the attribute
	public function getAmountAttribute()
	{
	    return 'amount';
	}

    /**
     * Defines an inverse one-to-many relationship
     */
    public function project()
    {
        return $this->belongsTo('App\Models\Projects', 'project_id');
    }
    
    /**
     * Defines an inverse many-to-many relationship
     */
    public function children()
    {
        return $this->belongsTo('App\Models\CostTypes', 'cost_type_id');
    }
    
}
