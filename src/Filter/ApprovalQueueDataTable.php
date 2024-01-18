<?php
namespace AscentCreative\Approval\Filter;

use AscentCreative\Filter\DataTableBuilder;
use AscentCreative\Filter\DataTable\Column;

use AscentCreative\Approval\Models\ApprovalItem;


class ApprovalQueueDataTable extends DataTableBuilder {

    public $defaults = [
        'sort' => ['created_at'=>'asc']
    ];

    public $default_sort = 'created_at asc';

    public function boot() {
        parent::boot();

        $this->setFilterWrapper('');

        

        $this->registerFilter('action', 'byAction');
    
    }


    public function columns() : array {

        return [

            Column::make("Submitted")
                ->width('150px')
                ->valueProperty('created_at'),

            Column::make('Action')
                ->width('100px')
                ->valueBlade('approval::queue.action-badge')
                ->filterScope('byAction')
                ->filterBlade("filter::ui.filters.checkboxes", ['create'=>'Create', 'edit'=>'Edit']),


        //     Column::make('Name')
        //         ->width('minmax(200px, 1fr)')
        //         ->valueProperty('lastFirst')
        //         ->filterScope('byName')
        //         ->filterBlade('filter::ui.filters.text')
        //         ->link(function($item) {
        //             return route('portal.contacts.show', ['contact'=>$item]);
        //         })
        //         ->sortScope('sortByName'),

        //     Column::make('Email')
        //         // ->width('2fr')
        //         ->valueProperty('email')
        //         ->filterable(true)
        //         ->copyable()
        //         ->sortable(),

        //     Column::make("Catalogues")
        //         ->width('175px')
        //         ->valueBlade('portal.contacts.index.catalogues')
        //         ->filterScope('byActiveCatalogue')
        //         ->filterBlade('filter::ui.filters.checkboxes', \App\Models\Catalogue::earnsIncome()->get()->keyBy('id')->transform(function ($item) { return $item->title; })),

        //     Column::make('Works Owned')
        //         ->width('minmax(10px, 150px)')
        //         ->value(function($item) { 
        //             return $item->works_count;
        //         })
        //         ->align('center'),

        ];


    }

    public function buildQuery() {
        return ApprovalItem::approvalQueue($this->modelClass)
                    ->with("approvable")
                    ->select('*');
    }




}