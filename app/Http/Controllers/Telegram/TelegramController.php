<?php

namespace App\Http\Controllers\Telegram;
ini_set('memory_limit', '2G');

use App\Http\Controllers\Controller;
use App\Http\Requests\TelegramApiRequest;
use App\Token;
use http\Client\Request;
use Telegram\Bot\Api;
use Telegram\Bot\FileUpload\InputFile;
use Telegram\Bot\Traits\Telegram;

class TelegramController extends Controller
{
    public $telegram = null;

    public function sendMessage()
    {
        try {
            $request = \Illuminate\Support\Facades\Request::class;
            $data = $request::only([
                'uid',
                'secret',
                'targets',
                'text',
                'attachment'
            ]);
            if (!isset($data['targets']) || !is_array($data['targets'])) {
                return response()->json([
                    'status' => 400,
                    'error' => 'please set target identifiers as array likne ["@username",..]',
                    'data' => $data,
                ]);
            }
            $targets = ($data['targets']);
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
                        }
                    }
                }
            } elseif (is_string($attachment)) {
                try {
                    $attachmentContent = file_get_contents($attachment);

                    $finfo = new \finfo(FILEINFO_MIME_TYPE);
                    $type = $finfo->buffer($attachmentContent);
                    //dd($type);
                    foreach ($targets as $target) {
                        $telegramResponse ['chat_id'] = $target;
                        //dd($telegramResponse);
                        if ($text) {
                            $telegramResponse['caption'] = $text;
                        }
                        if (substr($type, 0, 6) === 'image/') {
                            $telegramResponse['photo'] = InputFile::create($attachment, time());
                            try {
                                $response[] = $this->telegram->sendPhoto($telegramResponse);
                                dd($telegramResponse, $type);
                            } catch (\Exception $errorSend) {
                                //dd([$errorSend->getMessage(), $errorSend]);
                            }
                        } else if (substr($type, 0, 6) === 'video/') {
                            $telegramResponse['video'] = InputFile::create($attachment, time());
                            try {
                                $response[] = $this->telegram->sendVideo($telegramResponse);
                                dd($telegramResponse, $type);
                            } catch (\Exception $errorSend) {
                                //dd([$errorSend->getMessage(), $errorSend]);
                            }
                        }else if (substr($type, 0, 6) === 'audio/') {
                            $telegramResponse['audio'] = InputFile::create($attachment, time());
                            try {
                                $response[] = $this->telegram->sendAudio($telegramResponse);
                                dd($telegramResponse, $type);
                            } catch (\Exception $errorSend) {
                                //dd([$errorSend->getMessage(), $errorSend]);
                            }
                        } else {
                            $telegramResponse['document'] = InputFile::create($attachment, time());
                            try {
                                $response[] = $this->telegram->sendDocument($telegramResponse);
                                dd($telegramResponse, $type);
                            } catch (\Exception $errorSend) {
                                //dd([$errorSend->getMessage(), $errorSend]);
                            }
                        }
                    }
                } catch (\Exception $er) {
                    dd([$er->getMessage(), $er]);
                }
            }


            dd($response);
        } catch (\Exception $e) {
            dd($e->getMessage(), $e);
        }
    }

}
