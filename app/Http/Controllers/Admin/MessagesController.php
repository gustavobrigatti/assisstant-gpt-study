<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\GPTService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use OpenAI;
use function Symfony\Component\Translation\t;

class MessagesController extends Controller
{

    public function store(Request $request){
        DB::beginTransaction();
        try {
            $gptService = new GPTService();
            $user = Auth::user();
            if ($user->threadId == null){
                $response = $gptService->createThread();
                if (!$response['success']){
                    return response()->json($response);
                }
                $user->update([
                    'threadId' => $response['threadId']
                ]);
            }
            $response = $gptService->attachThreadMessage($request->message, $user->threadId);
            if (!$response['success']){
                return response()->json(['success' => false, 'message' => $response['message']]);
            }
            DB::commit();
            return response()->json([
                'success' => true,
                'messageSend' => view('admin.components.messages.send', ['message' => $request->message])->render(),
                //'messageReceive' => view('admin.components.messages.receive', ['message' => $result->choices[0]->message->content])->render(),
            ]);
        }catch (\Exception $e){
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function runAssistant(){
        try {
            $gptService = new GPTService();
            $response = $gptService->runAssistant('asst_HjpMl7HCJkhKtyVkxN8eVMyc');
            return response()->json($response);
        }catch (\Exception $e){
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}
