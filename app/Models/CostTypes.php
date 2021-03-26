<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Eloquent;

class CostTypes extends Eloquent
{
	/**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'cost_types';

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

	public function parent()
	{
	     return $this->belongsTo('App\Models\CostTypes','parent_id')->where('parent_id', NULL)->with('parent') ;
	}

	 public function children()
	 {
	   return $this->hasMany('App\Models\CostTypes','parent_id')->with('children');
	}
}
