<?php

namespace AscentCreative\Drafts\Traits;

use AscentCreative\Approval\Models\Draft;

use Illuminate\Http\Request;

/**
 * Allows a Model to be extended with functions to
 * store a draft record, and retrieve on demand
 */
trait Draftable {

    public function drafts() {
        return $this->morphMany(Draft::class, 'draftable');
    }

}