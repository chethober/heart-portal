<?php

namespace App\Http\Controllers\Clinical;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class HelpController extends Controller
{
    public function __invoke(): View
    {
        return view('clinical.help');
    }
}
