<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;

class GPTService
{
    protected $apiKey;
    protected $response, $err, $info;

    public function __construct()
    {
        $this->apiKey = getenv('OPEN_AI_API_KEY');
    }

    public function getThreadMessages($threadId){
        $this->curl('threads/'.$threadId.'/messages', 'GET');
        if ($this->err) {
            return ['success' => false, 'message' => $this->err];
        }else{
            if ($this->info["http_code"] <= 201){
                return ['success' => true, 'messages' => json_decode($this->response)->data];
            }else{
                return ['success' => false, 'message' => 'Falha ao recuperar mensagens, tente novamente'];
            }
        }
    }

    public function createThread(){
        $this->curl('threads', 'POST');
        if ($this->err) {
            return ['success' => false, 'message' => $this->err];
        }else{
            if ($this->info["http_code"] <= 201){
                return ['success' => true, 'threadId' => json_decode($this->response)->id];
            }else{
                return ['success' => false, 'message' => 'Falha ao criar thread, tente novamente'];
            }
        }
    }

    public function attachThreadMessage($message, $threadId){
        $data = ['role' => 'user', 'content' => $message];
        $this->curl('threads/'.$threadId.'/messages', 'POST', $data);
        if ($this->err) {
            return ['success' => false, 'message' => $this->err];
        }else{
            if ($this->info["http_code"] <= 201){
                return ['success' => true];
            }else{
                return ['success' => false, 'message' => 'Falha ao enviar mensagem, tente novamente'];
            }
        }
    }

    public function runAssistant($assistantId){
        $user = Auth::user();
        if ($user->threadId == null){
            $this->createThread();
            if (!$this->response['success']){
                return response()->json($this->response);
            }
            $user->update([
                'threadId' => $this->response['threadId']
            ]);
        }
        $data = ['assistant_id' => $assistantId];
        $this->curl('threads/'.$user->threadId.'/runs', 'POST', $data);
        if ($this->err) {
            return ['success' => false, 'message' => $this->err];
        }else{
            if ($this->info["http_code"] <= 201){
                $i = 0;
                $runId = json_decode($this->response)->id;
                do{
                    sleep(2);
                    $responseCheckStatusRun = $this->checkRunStatus($user->threadId, $runId);
                    if ($i == 10 && !$responseCheckStatusRun['success']){
                        $responseCheckStatusRun['success'] = true;
                    }else{
                        $i++;
                    }
                }while(!$responseCheckStatusRun['success']);
                if ($i == 10){
                    return ['success' => false, 'message' => 'Falha ao executar resposta do assistente, tente novamente'];
                }
                $responseGetThreadMessages = $this->getThreadMessages($user->threadId);
                $message = '';
                foreach ($responseGetThreadMessages['messages'] as $responseGetThreadMessage){
                    if ($responseGetThreadMessage->run_id == $runId){
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
        $this->curl('threads/'.$threadId.'/runs/'.$runId, 'GET');
        if ($this->err) {
            return ['success' => false, 'message' => $this->err];
        }else{
            if ($this->info["http_code"] <= 201){
                if (json_decode($this->response)->status == 'completed'){
                    return ['success' => true];
                }elseif(json_decode($this->response)->status == 'requires_action'){
                    if (json_decode($this->response)->required_action->submit_tool_outputs->tool_calls[0]->function->name == 'get_flight_info'){
                        $callId = json_decode($this->response)->required_action->submit_tool_outputs->tool_calls[0]->id;
                        $arguments = json_decode(json_decode($this->response)->required_action->submit_tool_outputs->tool_calls[0]->function->arguments);
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
        $data = [
            'tool_outputs' => [
                [
                    'tool_call_id' => $callId,
                    'output' => json_encode($toolOutputs)
                ]
            ]
        ];
        $this->curl('threads/'.$threadId.'/runs/'.$runId.'/submit_tool_outputs', 'POST', $data);;
        if ($this->err) {
            return ['success' => false, 'message' => $this->err];
        }else{
            if ($this->info["http_code"] <= 201){
                return ['success' => true];
            }else{
                return ['success' => false, 'message' => 'Falha ao responder função, tente novamente'];
            }
        }
    }

    public function curl($url, $method, $data = null){
        $header = [
            'OpenAI-Beta: assistants=v1',
            "Content-Type: application/json",
            'Authorization: Bearer '.$this->apiKey
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

        $this->response = curl_exec($curl);
        $this->err = curl_error($curl);
        $this->info = curl_getinfo($curl);
        curl_close($curl);
    }
}
