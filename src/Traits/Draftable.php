<?php

namespace AscentCreative\Drafts\Traits;

use AscentCreative\Drafts\Models\Draft;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Http\Request;

/**
 * Allows a Model to be extended with functions to
 * store a draft record, and retrieve on demand
 */
trait Draftable {

    // get all the drafts for a model
    public function drafts() {
        return $this->morphMany(Draft::class, 'draftable');
    }

    // get the drafts for the model for the user
    public function userDrafts(\App\Models\User $user=null) {
        if(!$user) {
            $user = auth()->user();
        }
        return $this->drafts()
                ->where('author_id', $user->id);
    }

    // saves a new draft of a model (i.e. no existing instance)
    static function saveNewDraft($data) {

        // write the incoming model data as a draft
        $cls = __CLASS__;
        $inst = new $cls();
        // use fill() ... attributes to strip out extraneous data (like if a request()->all() was provided as the data)
        $inst->fill($data); 
        $payload = $inst->attributes;

        $owner = $inst->resolveDraftOwner();

        // - the approvals module also stores a stub Model record when new items are created. Should we do that here?
        //  Or can drafts be saved totally independently?
        //  Try the latter first and see where we get to!
        Draft::create([
            'draftable_type' => $cls,
            'draftable_id' => null,
            'owner_type' => get_class($owner),
            'owner_id' => $owner->id,
            'payload' => $payload,
        ]);

    }

    // Saves a draft edit on an existing model record.
    public function saveAsDraft($data) {

        $this->fill($data);

        //  @TODO- when storing a draft edit, use similar code to approvall module to detect changes
        //  - only store the changes
        $payload = $this->attributes;

        // resolve the owner:
        $owner = $this->resolveDraftOwner();

        // dd($payload);

        Draft::updateOrCreate([
            'draftable_type' => get_class($this),
            'draftable_id' => $this->id,
        ], [
            'owner_type' => get_class($owner),
            'owner_id' => $owner->id,
            'payload' => $payload,
        ]);

    }


    // delete the drafts stored for a model:
    public function clearDrafts() {
        $this->drafts()->delete();
    }



    private function resolveDraftOwner() {

         // resolve the owner from the model
         $owner_relation = $this->owner_relation ?? 'owner';
         $owner = $this->$owner_relation;
         
         if(is_null($owner)) {
             // should we just fall back to the user?
             $owner = auth()->user();            
         }
 
         if(is_null($owner)) {
             // still null? bomb out.
             throw new Exception('Unable to resolve an owner for the draft');
         }

         return $owner;

    }

}