<?php

namespace AscentCreative\Drafts;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;
use Illuminate\Routing\Router;

use Illuminate\Support\Facades\Route;

use Illuminate\Support\Facades\Schema;


class DraftsServiceProvider extends ServiceProvider
{

    public function register() {
        //
    
        $this->mergeConfigFrom(
            __DIR__.'/../config/drafts.php', 'drafts'
        );    

    }

    public function boot() {
        $this->bootComponents();

        $this->loadViewsFrom(__DIR__.'/../resources/views', 'drafts');

        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

    }


    // register the components
    public function bootComponents() {
      
        Blade::component('drafts-savedraftbutton', 'AscentCreative\Drafts\Components\SaveDraftButton');

    }


    public function bootPublishes() {

      $this->publishes([
        __DIR__.'/Assets' => public_path('vendor/ascentcreative/drafts'),
    
      ], 'public');

      $this->publishes([
        __DIR__.'/config/drafts.php' => config_path('drafts.php'),
      ]);

    }



}