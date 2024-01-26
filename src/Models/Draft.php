<?php

namespace AscentCreative\Drafts\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Support\Facades\DB;

use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Draft extends Model {

    use LogsActivity;

    public $table = "drafts";

    public $fillable = ['draftable_type', 'draftable_id', 'owner_type', 'owner_id', 'payload'];

    public $casts = [
        'payload' => 'array',
    ];


    public function __get($key) {
        
        if($val = parent::__get($key)) {
            return $val;
        } 

        if(isset($this->payload[$key])) {
            return $this->payload[$key];
        }

        // if($this->draftable->$key) {
        //     return $val;
        // }

    }

    public function getActivitylogOptions(): LogOptions {
        return LogOptions::defaults();
    }


    public function owner() {
        return $this->morphTo();
    }

    public function draftable() {
        return $this->morphTo(); //->withUnapproved();
    }


    public function scopeByOwner($q, $owner) {
        $q->where('owner_type', get_class($owner))
                ->where('owner_id', $owner->id);
    }


    public function updateDraft($data) {

        if($parent = $this->draftable) {
            $parent->fill($data);
            $this->payload = $parent->attributes;
            $this->save();
        } else {
            $cls = $this->draftable_type;
            $instance = new $cls();
            $instance->fill($data);
            $this->payload = $instance->attributes;
            $this->save();
        }

    }



    // turns the draft back into the relevant model
    // - Should remove the draft once converted as no longer needed. Maybe just archive it? Soft Delete?
    // - If draft was an edit, maybe we need to check for clashes on fields 
    //      (or at least check draft is newer than the model we're overwriting?)
    public function commitDraft() {

    }

   
}