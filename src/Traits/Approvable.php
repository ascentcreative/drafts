<?php

namespace AscentCreative\Approval\Traits;

use AscentCreative\Approval\Models\ApprovalItem;

use Illuminate\Http\Request;


/**
 * Allows a Model to be extended with functions to intercept the save of new data
 * and divert to a sandbox record where needed
 */
trait Approvable {

    /**
     * Indicates if the model was approved/rejected during the current request lifecycle.
     *
     * @var bool
     */
    public $wasRecentlyApproved = false;
    public $wasRecentlyRejected = false;
    public $needsApproval = false;
    public $approvalAction = '';

    // public $approval_track = [];
    // public $approval_ignore = [];


    public $_ai;

    public static function bootApprovable() {

        static::addGlobalScope('approved', function($query) {

            $query->where('is_approved', 1)->where('is_rejected', 0);

            $query->whereDoesntHave('approval_items', function($q) {
                $q->where('is_approved', 0)->where('action', 'create');
            });

        });

       


        static::saving(function($model) {
            
            // Need to grab data here, as Extender will steal it otherwise.
            // (Also, changes may have only happened in relations, not on the main model, so other events may not fire at all)
            // Which means we also need to detect if model is new or not:
            
            $pKey = $model->primaryKey;
            if($model->$pKey) {
                $action = 'edit';
            } else {
                $action = 'create';
            }

            $payload = null;

            if(!app()->runningInConsole()) {
                if($action == 'edit' && !request()->user()->can('updateWithoutApproval', get_class($model))) {
                    $payload = $model->approval_detect_changes();
                }

                if($action == 'create' && !request()->user()->can('createWithoutApproval', get_class($model))) {
                    $payload = $model->attributes;
                }
            

        
                if(!is_null($payload) && count($payload) != 0) {
                    $model->needsApproval = true;
                } else {
                    // dd('No changes made');
                }
    
                // set up a new approval item with the incoming data
                // in case we need it. It's not saved yet...
                $model->_ai = new ApprovalItem();
                $model->_ai->fill([
                    'approvable_type' => get_class($model),
                    'approvable_id' => $model->id,
                    'author_id' => auth()->user()->id,
                    'payload' => $payload, 
                    'action' => $action,
                ]);

            }

            if($model->wasRecentlyApproved) {
                $model->is_approved = 1;
            }

            if($model->wasRecentlyRejected) {
                $model->is_rejected = 1;
            }

        });


        static::creating(function($model) {

            if(app()->runningInConsole() || request()->user()->can('createWithoutApproval', get_class($model))) {
                // setting this to indicate the model was approved / allowed, 
                // even though there may not have been an approval loop. 
                // (Might rethink this)
                $model->wasRecentlyApproved = true;
                $model->is_approved = 1;
            } else {
                $model->needsApproval = true;
                $model->approvalAction = 'create';
                // allow the creation to continue as we need the model in place.
            }
            // dd($model);
        });


        // Catch edits. 
        static::updating(function($model) {

            if($model->needsApproval) {
                $model->_ai->save();
                return false;
            }

        });

      
        // Need to act slightly differently based on if we're creating a new model, or updating an existing one
        static::saved(function($model) { 

            if($model->needsApproval) {

                $model->_ai->fill([
                     'approvable_id' => $model->id, // update with the new ID
                //     'action' => $model->approvalAction,
                ]);

                $model->_ai->save();

                return false; // stops event propgating through other traits.
                // is this risky? Should I set a flag which other traits can respond to?

            }

        });
        
    }



    public function approval_items() {
        return $this->morphMany(ApprovalItem::class, 'approvable');
    }

    public function hasUnapprovedEdits() {
        return $this->approval_items()
                    ->where('action', 'edit')
                    ->where('is_approved', 0)
                    ->where('is_rejected', 0)
                    ->exists();
    }

    public function getUnapprovedEditsAttribute() {
        return $this->approval_items()
                    ->where('action', 'edit')
                    ->where('is_approved', 0)
                    ->where('is_rejected', 0)->get();
    }


    public function scopeApprovalQueue($q, $action=null) {

        $q->withoutGlobalScope('approved');

        $q->whereHas('approval_items', function($q) use ($action) {
            $q->where('is_approved', 0);
            if(!is_null($action)) {
                $q->where('action', $action);
            }
        });

    }

    public function scopeWithUnapproved($query) {

        $query->withoutGlobalScope('approved');

    }
  

    public function reject() {

        $this->is_rejected = 1;
        $this->save();

        $this->wasRecentlyRejected = true;

    }


    public function approval_detect_changes($data=null) {

        // Need to detect the changes made.
        // This is ok for straight fields, but will be more complex for relations, which don't get included in getDirty() by default
        // (Or rather, with the extenders traits, ALWAYS get flagged as dirty.)
        // We need to be sure that the only changes we include are legitmately changed
        // 
        // Would the simple option be to directly check specified fields?
        // Possibly use 'fillable' as a starting point, but add "approval_track" and "approval_ignore" 
        // to augment the field list (allowing relations to be checked especially).
        //
        // Then, we just need a means of checking two values are logically equivalent or not.
        // Again, easy for normal string / date / number vals, but relations might be more complex (recursive array checking probably)

        if(is_array($this->approval_track) && count($this->approval_track) > 0) {
            $fields = collect($this->approval_track);
        } else {
            $fields = collect($this->fillable);
        }
        
        // hide any in the ignore:
        if(is_array($this->approval_ignore) && count($this->approval_ignore) > 0) {
            $ignore = collect($this->approval_ignore);
            $fields = $fields->diff($ignore);
        }

        // ok, we've got the set of fields / relations we're working with.
        // now, get the data:
        // get a copy of the saved data:
        $reference = $this->fresh();
       
        $changes = [];
        foreach($fields as $field) {

            // dump($field);
            $thisval = $this->$field;
            $refval = $reference->$field;

             // note - model has been retrieved and then filled from request data
            // so, any valules not in the request will remain unchanged and will
            // be detected as equivalent
            
        
            // if collection, convert to array...
            if($thisval instanceof \Illuminate\Database\Eloquent\Collection) {
                $thisval = $thisval->toArray();
            }
            if($refval instanceof \Illuminate\Database\Eloquent\Collection || $refval instanceof \Illuminate\Database\Eloquent\Model) {
                $refval = $refval->toArray();
            }

            // are both values now arrays?
            if(is_array($thisval) && is_array($refval)) {
                // do a recursive check
                if($this->checkArrayEquivalence($thisval, $refval)) {
                    // dump($field . ' - Arrays are equivalent');
                    continue;
                } else {
                    // dump($field . ' - Arrays are NOT equivalent');
                    $changes[$field] = $thisval;
                    continue;
                }
            } else {
 
                if($this->originalIsEquivalent($field)) {
                    // dump($field . ' - is equiv');
                    continue;
                }
                // dump($field . ' is not equivalent');
            }

            // all equivalence checks have failed. Must be a change.
            if($thisval !== $refval) {
                $changes[$field] = $thisval; 
            }


        }
        return $changes;
        // dd('return..');    
    }


    /**
     * Checks whether two arrays (usually from an eloquent relation) are functionally equivalent
     * 
     * We're not using built in PHP array checking as the arrays may be equivalent, but
     * not identical. For example, $refval may have timestamp fields not in the incoming data.
     * Other fields not in the incoming data can be ignored for checking as the fill/merge will 
     * allow them to be updated, and if not present we can consider them unchanged.
     * @param mixed $new
     * @param mixed $old
     * 
     * @return [type]
     */
    private function checkArrayEquivalence($new, $old) {

        // using the new data as the reference point, loop through (recursivey)
        // checking that values present in $new are the same as $old. 
        foreach($new as $key=>$value) {

            // dump('key: ' . $key); // . "[ " . $value . " | " . $old[$key] . " ]");

            // does this key exist in old?
            if(!array_key_exists($key, $old)) {
                // dump('Key not present in old value');
                return false; // we can just bail out
            }

            // TODO - need to check JSON strings against arrays (i.e. if JSON, parse it).
            
            // TODO - some values might be model IDs on one side, and model data arrays on the other. 
            // - Might need to detect if one is numeric and matches the ID on the other side.

            if(is_array($value) && is_array($old[$key])) {
                // BOTH are arrays
                if(!$this->checkArrayEquivalence($value, $old[$key])) {
                    // dump('Recursive failure');
                    return false; // we can bail if this fails, but need to continue checking if it passes.
                }
            } else if (!is_array($value) && !is_array($old[$key])) {
                // NEITHER are arrays - treat as a simple value we can compare:
                // if(!$this->areValuesEquivalent($value, $old[$key])) {
                if($value != $old[$key]) {
                    // dump($value . ' != ' . $old[$key]);
                    return false; // we can bail if this fails, but need to continue checking if it passes.
                }
            } else {
                // Mismatch - NOT Equivalent
                return false;
            }

        }

        // passes all the tests, and so will go into the west and remain Galadriel.
        return true;

    }


}