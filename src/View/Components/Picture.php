<?php

namespace Daikazu\LaravelGlider\View\Components;

use Illuminate\Support\Str;
use Illuminate\View\Component;

class Picture extends Component
{
    public $src;
    public $mime_type;
    public $ext;


    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct($src)
    {

        $this->src = $src;

        $this->ext = (string) Str::of($src)->afterLast('.')->before('?');

        $this->getMimetypeByUrl();
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('glider::components.picture');
    }

    private function getMimetypeByUrl()
    {

        $this->mime_type = match ($this->ext) {
            'jpg', 'jpeg', => 'image/jpeg',
            'png' => 'image/png',
            'webp' => 'image/webp',
            'gif' => 'image/gif',
            default => '',
        };

    }


}
