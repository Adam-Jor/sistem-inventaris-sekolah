<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class AppLayout extends Component
{
    /**
     * @param  string  $title  Judul halaman (tab browser & navbar).
     * @param  array<string, string|null>  $breadcrumbs  ['Label' => url]; item terakhir (atau url null) = halaman aktif.
     */
    public function __construct(
        public string $title = '',
        public array $breadcrumbs = [],
    ) {
    }

    /**
     * Get the view / contents that represents the component.
     */
    public function render(): View
    {
        return view('layouts.app');
    }
}
