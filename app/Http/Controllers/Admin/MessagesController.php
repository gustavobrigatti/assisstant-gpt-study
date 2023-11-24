<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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
            $user = Auth::user();
            if ($user->threadId == null){
                $response = $this->createThread();
                if (!$response['success']){
                    return response()->json($response);
                }
                $user->update([
                    'threadId' => $response['threadId']
                ]);
            }
            $response = $this->attachMessage($request->message, $user->threadId);
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

    public function createThread(){
        $apiKey = getenv('OPEN_AI_API_KEY');
        $curl = $this->createCurl($apiKey, 'threads', 'POST');
        $response = curl_exec($curl);
        $err = curl_error($curl);
        $info = curl_getinfo($curl);
        curl_close($curl);

        if ($err) {
            return ['success' => false, 'message' => $err];
        }else{
            if ($info["http_code"] <= 201){
                return ['success' => true, 'threadId' => json_decode($response)->id];
            }else{
                return ['success' => false, 'message' => 'Falha ao criar thread, tente novamente'];
            }
        }
    }

    public function attachMessage($message, $threadId){
        $apiKey = getenv('OPEN_AI_API_KEY');
        $data = ['role' => 'user', 'content' => $message];
        $curl = $this->createCurl($apiKey, 'threads/'.$threadId.'/messages', 'POST', $data);
        curl_exec($curl);
        $err = curl_error($curl);
        $info = curl_getinfo($curl);
        curl_close($curl);
        if ($err) {
            return ['success' => false, 'message' => $err];
        }else{
            if ($info["http_code"] <= 201){
                return ['success' => true];
            }else{
                return ['success' => false, 'message' => 'Falha ao enviar mensagem, tente novamente'];
            }
        }
    }

    public function runAssistant(){
        $user = Auth::user();
        if ($user->threadId == null){
            $response = $this->createThread();
            if (!$response['success']){
                return response()->json($response);
            }
            $user->update([
                'threadId' => $response['threadId']
            ]);
        }
        $apiKey = getenv('OPEN_AI_API_KEY');
        $data = ['assistant_id' => 'asst_HjpMl7HCJkhKtyVkxN8eVMyc'];
        $curl = $this->createCurl($apiKey, 'threads/'.$user->threadId.'/runs', 'POST', $data);
        $response = curl_exec($curl);
        $err = curl_error($curl);
        $info = curl_getinfo($curl);
        curl_close($curl);

        if ($err) {
            return ['success' => false, 'message' => $err];
        }else{
            if ($info["http_code"] <= 201){
                do{
                    sleep(2);
                    $responseCheckStatusRun = $this->checkRunStatus($user->threadId, json_decode($response)->id);
                }while(!$responseCheckStatusRun['success']);
                $responseGetThreadMessages = $this->getThreadMessages($user->threadId);
                $message = '';
                foreach ($responseGetThreadMessages['messages'] as $responseGetThreadMessage){
                    if ($responseGetThreadMessage->run_id == json_decode($response)->id){
                        $message .= view('admin.components.messages.receive', ['message' => $responseGetThreadMessage->content[0]->text->value])->render();
                    }
                }
                return ['success' => true, 'messageReceive' => $message];
            }else{
                return ['success' => false, 'message' => 'Falha ao executar resposta do assistente, tente novamente'];
            }
        }
    }

    public function checkRunStatus($threadId, $runId){
        $apiKey = getenv('OPEN_AI_API_KEY');
        $curl = $this->createCurl($apiKey, 'threads/'.$threadId.'/runs/'.$runId, 'GET');
        $response = curl_exec($curl);
        $err = curl_error($curl);
        $info = curl_getinfo($curl);
        curl_close($curl);

        if ($err) {
            return ['success' => false, 'message' => $err];
        }else{
            if ($info["http_code"] <= 201){
                if (json_decode($response)->status == 'completed'){
                    return ['success' => true];
                }elseif(json_decode($response)->status == 'requires_action'){
                    if (json_decode($response)->required_action->submit_tool_outputs->tool_calls[0]->function->name == 'get_flight_info'){
                        $callId = json_decode($response)->required_action->submit_tool_outputs->tool_calls[0]->id;
                        $arguments = json_decode(json_decode($response)->required_action->submit_tool_outputs->tool_calls[0]->function->arguments);
                        $data = [
                            'loc_origin' => $arguments->loc_origin,
                            'loc_destination' => $arguments->loc_destination,
                            'datetime' => "25/11/2023 12:00",
                            'airline' => "GOL",
                            'flight' => "GOL23",
                        ];
                        $this->submitToolOutputs($data, $threadId, $runId, $callId);
                    }
                    return ['success' => false];
                }else{
                    return ['success' => false];
                }
            }else{
                return ['success' => false, 'message' => 'Falha ao checar status do assistente, tente novamente'];
            }
        }
    }

    public  function submitToolOutputs($toolOutputs, $threadId, $runId, $callId){
        $apiKey = getenv('OPEN_AI_API_KEY');
        $data = [
            'threadId' => $threadId,
            'runId' => $runId,
            'functionResponses' => [
                'tool_outputs' => [
                    [
                        'tool_call_id' => $callId,
                        'output' => json_encode($toolOutputs)
                    ]
                ]
            ]
        ];
        $curl = $this->createCurl($apiKey, 'threads/'.$threadId.'/runs/'.$runId.'/submit_tool_outputs', 'POST', $data);
        $response = curl_exec($curl);
        $err = curl_error($curl);
        $info = curl_getinfo($curl);
        curl_close($curl);

        if ($err) {
            return ['success' => false, 'message' => $err];
        }else{
            if ($info["http_code"] <= 201){
                return ['success' => true];
            }else{
                dd($response);
                return ['success' => false, 'message' => 'Falha ao responder função, tente novamente'];
            }
        }
    }

    public function getThreadMessages($threadId){
        $apiKey = getenv('OPEN_AI_API_KEY');
        $curl = $this->createCurl($apiKey, 'threads/'.$threadId.'/messages', 'GET');
        $response = curl_exec($curl);
        $err = curl_error($curl);
        $info = curl_getinfo($curl);
        curl_close($curl);

        if ($err) {
            return ['success' => false, 'message' => $err];
        }else{
            if ($info["http_code"] <= 201){
                return ['success' => true, 'messages' => json_decode($response)->data];
            }else{
                return ['success' => false, 'message' => 'Falha ao checar status do assistente, tente novamente'];
            }
        }
    }

    public function createCurl($apiKey, $url, $method, $data = null){
        $header = [
            'OpenAI-Beta: assistants=v1',
            "Content-Type: application/json",
            'Authorization: Bearer '.$apiKey
        ];
        $curl = curl_init();
        $options = [
            CURLOPT_URL => 'https://api.openai.com/v1/' . $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $header,
        ];
        if ($data != null) {
            $options[CURLOPT_POSTFIELDS] = json_encode($data);
        }
        curl_setopt_array($curl, $options);
        return $curl;
    }
}
