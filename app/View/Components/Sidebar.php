<?php

namespace App\View\Components;

use Illuminate\View\Component;

class Sidebar extends Component
{
    public $menuItems;

    public function __construct()
    {
        $this->menuItems = config('menu.items', []);
    }

    public function render()
    {
        return view('components.sidebar');
    }
}