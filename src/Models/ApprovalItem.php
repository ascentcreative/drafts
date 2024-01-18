<?php

namespace AscentCreative\Approval\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use AscentCreative\Approval\Events\PreItemApproval;
use AscentCreative\Approval\Events\ItemApproved;
use AscentCreative\Approval\Events\ItemRejected;
// use AscentCreative\Approval\Events\UpdatedSandbox;

use Illuminate\Support\Facades\DB;

use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class ApprovalItem extends Model {

    use LogsActivity;

    public $table = "approval_queue";

    public $fillable = ['approvable_type', 'approvable_id', 'author_id', 'payload', 'action', 'is_approved', 'approved_at', 'approved_by', 'is_rejected', 'rejected_at', 'rejected_by'];

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

        if($val = $this->approvable->$key) {
            return $val;
        }

    }

    public function getActivitylogOptions(): LogOptions {
        return LogOptions::defaults();
    }


    public static function booted() {

        // static::created(function($model) { 
            
        //     NewSandbox::dispatch($model);

        // });

    }

    public function getStatusAttribute() {

    }

    public function author() {
        return $this->belongsTo(\App\Models\User::class, 'author_id');
    }

    public function approvable() {
        return $this->morphTo()->withUnapproved();
    }



    /** 
     * Approve this item
     * @param $data = the data to stamp into the model
     * If null, uses the data stored in the payload, but this parameter allows that 
     * to have been overwritten by the approver in a UI if needed. 
     * Making it optional means that the approval can fire just from non-UI code (if needed...)
     */
    public function approve($data=null) {

        /**
         * TODO - for edits:
         *  - do we need a warning if the target model has been changed since the edit request was created?
         *  - can we use the activity log to check if the edited fields were changed? 
         *  - Or is it enough to show the new value vs the current value?
         */

        DB::transaction( function() use ($data) {

            PreItemApproval::dispatch($this);

            $this->is_approved = 1;
            $this->approved_at = now();
            $this->approved_by = auth()->user()->id;
            $this->save();
    
            // insert the payload data into the model
            // - for a create, this will populate everything
            // - for an edit, we should have only stored the changed fields.
            if(is_null($data)) {
                $data = $this->payload;
            }
            $model = $this->approvable;
            $model->fill($data);
            if($this->action == 'create') {
                $model->wasRecentlyApproved = true;
            }
            $model->save();

            ItemApproved::dispatch($this);

        });

      
    }

    public function reject($reason) {

        $this->is_rejected = 1;
        $this->rejected_at = now();
        $this->rejected_by = auth()->user()->id;
        $this->reject_reason = $reason;
        $this->save();

        // update the model too:
        $model = $this->approvable; //->update(['is_rejected' => 1]);
        if($this->action == 'create') {
            $model->is_rejected = 1;
        }
        $model->save();

        ItemRejected::dispatch($this);
        
    }

    public function scopeApprovalQueue($query, $class, $action=null) {
        $query->where("is_approved", 0)
                    ->where("is_rejected", 0)
                    ->where('approvable_type', $class);
        if(!is_null($action)) {
            $query->where('action', $action);
        }
    }

    public function scopeByAction($q, $action) {
        if(!is_array($action)) {
            $action = [$action];
        }
        $q->whereIn('action', $action);
    }
   
}