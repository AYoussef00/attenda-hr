<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ChatController extends Controller
{
    /**
     * Display the chat page.
     */
    public function index(Request $request)
    {
        return Inertia::render('Company/Chat/Index');
    }
}
