<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Eloquent;

class Projects extends Eloquent
{
	/**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'projects';

    protected $fillable = ['id','title'];

    //Add extra attribute
	protected $attributes = ['type'];

	//Make it available in the json response
	protected $appends = ['type'];

	//implement the attribute
	public function getTypeAttribute()
	{
	    return 'project';
	}

    /**
     * Defines an inverse one-to-many relationship.
     */
    public function children()
    {
        return $this->hasMany('App\Models\Costs', 'project_id');
    }

}
