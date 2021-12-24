<?php

namespace Daikazu\LaravelGlider\View\Components;


use Illuminate\Support\Str;
use Illuminate\View\Component;

class Img extends Component
{
    public $src;

    public bool $responsive;


    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct($src, $responsive = false)
    {
        $this->src = $src;
        $this->responsive = $responsive;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('glider::components.img');
    }

}
