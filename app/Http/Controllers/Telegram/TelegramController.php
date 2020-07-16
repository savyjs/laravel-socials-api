<?php

namespace App\Http\Controllers\Telegram;
ini_set('memory_limit', '3G');

use App\Http\Controllers\Controller;
use App\Http\Requests\TelegramApiRequest;
use App\Token;
use Illuminate\Support\Facades\Request;
use Telegram\Bot\Api;
use Telegram\Bot\FileUpload\InputFile;
use Telegram\Bot\Traits\Telegram;

class TelegramController extends Controller
{
    public $telegram = null;

    /**
     * @return \Illuminate\Http\JsonResponse
     * /*
     * 'uid',
     * 'secret',
     * 'targets',
     * 'text',
     * 'attachment'
     */
    public function sendMessage()
    {
        try {
            $data = Request::all();

            if (!isset($data['targets']) || !is_array($data['targets'])) {
                return response()->json([
                    'status' => 400,
                    'error' => 'please set target identifiers as array likne ["@username",..]',
                    'data' => $data,
                    'targets' => $data['targets']
                ]);
            }
            $targets = ($data['targets']);
            $title = $data['title'];
            $text = $data['text'];
            $attachment = isset($data['attachment']) ? $data['attachment'] : null;
            $token = $data['secret'];
            if (!$token) {
                return response()->json(
                    ['status' => 400, 'error' => 'need token']
                );
            }
            $this->telegram = new Api($token);
            $response = [];

            if (!$attachment || empty($attachment) || is_null($attachment) || (is_array($attachment) && !count($attachment))) {
                if ($data['text']) {
                    foreach ($targets as $target) {
                        try {
                            $response[] = $this->telegram->sendMessage(
                                ['chat_id' => $target, 'text' => $text]
                            );
                        } catch (\Exception $er) {
                            return response()->json([-1, $er->getMessage(), $er]);
                        }
                    }
                }
            } elseif (is_string($attachment)) {
                try {
                    $attachmentContent = file_get_contents($attachment);

                    $finfo = new \finfo(FILEINFO_MIME_TYPE);
                    $type = $finfo->buffer($attachmentContent);
                    //response()->json($type);
                    foreach ($targets as $target) {
                        $telegramResponse ['chat_id'] = $target;
                        //response()->json($telegramResponse);
                        if ($text) {
                            $telegramResponse['caption'] = $text;
                        }
                        $fileName = $title ?? basename($attachment);
                        if (substr($type, 0, 6) === 'image/') {

                            $telegramResponse['photo'] = InputFile::create($attachment, $fileName ?? time());
                            try {
                                $response[] = $this->telegram->sendPhoto($telegramResponse);
                                //return response()->json([0, 1, $telegramResponse, $type]);
                            } catch (\Exception $errorSend) {
                                //response()->json([$errorSend->getMessage(), $errorSend]);
                            }
                        } else if (substr($type, 0, 6) === 'video/') {
                            $telegramResponse['video'] = InputFile::create($attachment, $fileName ?? time());
                            try {
                                $response[] = $this->telegram->sendVideo($telegramResponse);
                                //return response()->json([0, 2, $telegramResponse, $type]);
                            } catch (\Exception $errorSend) {
                                //response()->json([$errorSend->getMessage(), $errorSend]);
                            }
                        } else if (substr($type, 0, 6) === 'audio/') {
                            $telegramResponse['audio'] = InputFile::create($attachment, $fileName ?? time());
                            try {
                                $response[] = $this->telegram->sendAudio($telegramResponse);
                                //return response()->json([0, 3, $telegramResponse, $type]);
                            } catch (\Exception $errorSend) {
                                //response()->json([$errorSend->getMessage(), $errorSend]);
                            }
                        } else {
                            $telegramResponse['document'] = InputFile::create($attachment, $fileName ?? time());
                            try {
                                $response[] = $this->telegram->sendDocument($telegramResponse);
                                return response()->json([0, 4, $telegramResponse, $type]);
                            } catch (\Exception $errorSend) {
                                //response()->json([$errorSend->getMessage(), $errorSend]);
                            }
                        }
                    }
                } catch (\Exception $er) {
                    return response()->json([1, $er->getMessage(), $er]);
                }
            }


            return response()->json($response);
        } catch (\Exception $e) {
            return response()->json([3, $e->getMessage(), $e]);
        }
    }

}
