<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class IncludePlugins extends Component
{
    public $plugins;

    /**
     * Create a new component instance.
     */
    public function __construct(array $plugins = [])
    {
        $this->plugins = $plugins;
    }

    public function hasPlugin($pluginName)
    {
        return in_array($pluginName, $this->plugins);
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.include-plugins');
    }
}
