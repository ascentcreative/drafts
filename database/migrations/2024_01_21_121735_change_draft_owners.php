<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('drafts', function (Blueprint $table) {
           
            $table->string('owner_type')->index()->after('draftable_id');
            $table->renameColumn('author_id', 'owner_id');
            
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('drafts', function (Blueprint $table) {
           
            $table->dropColumn('owner_type');
            $table->renameColumn('owner_id', 'author_id');
            
        });
    }
};
