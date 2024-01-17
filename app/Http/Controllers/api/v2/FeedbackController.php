<?php

namespace App\Http\Controllers\api\v2;

use Http;
use App\Http\Controllers\base\ApiController;
use App\Models\Feedback;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class FeedbackController extends ApiController
{
    public function create(Request $request)
    {
        if (($user = $this->userExists($request)) === null) {
            return $this->response(false);
        }

        $validator = Validator::make($request->all(), [
            'type' => ['required', Rule::in(["other", "error", "suggestion"])],
            'content' => 'required',
        ]);
        if ($validator->fails()) {
            $this->setErrorData($validator->errors());
            return $this->response(false);
        }

        $feedback = Feedback::create($request->all());

        // send to telegram group
        try {
            $type = $user->isManager() ? 'Manager App' : 'Client App';
            $text = "📱 {$user->phone}\n";
            $text .= "🗒 {$type}\n";
            $text .= "✍️ {$feedback->type}\n";
            $text .= "🗣 {$feedback->content} \n";

            $url = "https://api.telegram.org/bot6628296302:AAFWQ5ooQbHZpu93s6K5wD6_3RoVluOPqmc/sendMessage";
            $response = Http::post($url, [
                'chat_id' => -1002022100528,
                'text' => $text
            ]);
        } catch (\Throwable $th) {
            error_log($th->getMessage());
        }

        return $this->response(true, [
            "user_id" => $user->id,
            "feedback_id" => $feedback->id
        ]);
    }

    public function list(Request $request)
    {
        if (is_null($user = $this->userExists($request))) {
            return $this->response(false);
        }

        $domains = Feedback::where([
            "user_id" => $user->id,
        ])->select("type", "content")->get();
        return $this->response(true, $domains);
    }
}
