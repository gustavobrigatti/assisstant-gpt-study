<?php

namespace App\Http\Controllers\Admin;

use App\Services\GPTService;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class AdminController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function dashboard()
    {
        $title = "Dashboard";
        $data = [
            'title' => $title
        ];
        $breadcrumbs = [
            ['title' => 'Dashboard', 'url' => route('admin.index')]
        ];
        $messages = [];
        if (Auth::user()->threadId != null){
            $gptService = new GPTService();
            $responseGetThreadMessages = $gptService->getThreadMessages(Auth::user()->threadId);
            foreach ($responseGetThreadMessages['messages'] as $responseGetThreadMessage){
                if ($responseGetThreadMessage->role == 'assistant'){
                    $messages[] = view('admin.components.messages.receive', ['message' => $responseGetThreadMessage->content[0]->text->value])->render();
                }else{
                    $messages[] = view('admin.components.messages.send', ['message' => $responseGetThreadMessage->content[0]->text->value])->render();
                }
            }
        }
        return view('admin.pages.dashboard', compact('title', 'data', 'breadcrumbs', 'messages'));
    }
}
