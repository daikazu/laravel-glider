<?php

namespace Daikazu\LaravelGlider\View\Components;


use Illuminate\Support\Str;
use Illuminate\View\Component;

class Img extends Component
{
    public $src;

    public bool $responsive;

    public $ext;

    public $mime_type;


    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct($src, $responsive = false)
    {
        $this->src = $src;
        $this->responsive = $responsive;

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
        return view('glider::components.img');
    }

    private function getMimetypeByUrl()
    {

        $this->mime_type = match ($this->ext) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'webp' => 'image/webp',
            'gif' => 'image/gif',
            default => '',
        };

    }

}
