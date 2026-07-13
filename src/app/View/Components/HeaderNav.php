<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class HeaderNav extends Component
{
    /**
     * Create a new component instance.
     *
     * @return void
     */
    public string $type;
    public function __construct(string $type = 'user')
    {
        $this->type = $type;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render(): View|Closure|string
    {
        return view('components.header-nav');
    }
}
