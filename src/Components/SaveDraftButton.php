<?php

namespace AscentCreative\Drafts\Components;

use Illuminate\View\Component;

class SaveDraftButton extends Component
{ 
    
    public $label;
    public $icon;
    public $class;


    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct($label, $icon='', $class='')
    {
        
        $this->label = $label;
        $this->icon = $icon;
        $this->class = $class;

    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|string
     */
    public function render()
    {
        return view('drafts::components.savedraftbutton');
    }
}
