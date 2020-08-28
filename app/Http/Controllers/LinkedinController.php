<?php

namespace App\Http\Controllers;

use App\Linkedin;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LinkedinController extends Controller
{
    public function auth(Request $request)
    {
        try {
            $token = $this->getAccessToken($request->code);
            $linkedin = new Linkedin;
            if ($token != null) {
                $linkedin->user_id = $request->userId;
                $linkedin->auth_code = $request->code;
                $linkedin->auth_token = $token;

                $linkedin_id = $this->getID($token);

                $linkedin->linkedin_id = $linkedin_id;

                $linkedin->save();
            }

            return response()->json(['msj' => 'Token guardado exitosamente!', 'linkedin' => $linkedin]);
        } catch (Exception $e) {
            return response()->json($e->getMessage(), 500);
        }
    }

    public function infoAccess(Request $request)
    {

        $infoAccess = DB::table('linkedin_api')
            ->whereRaw('user_id = ? AND created_at >= NOW() - INTERVAL \'60 days\'', [$request->userId])
            ->limit(1)->get();

        return response()->json(['linkedin' => $infoAccess[0]]);
    }


    private function getAccessToken($code)
    {
        try {
            $client = new Client(['base_uri' => 'https://www.linkedin.com']);

            $response = $client->request('POST', '/oauth/v2/accessToken', [
                'form_params' => [
                    "grant_type" => "authorization_code",
                    "code" => trim($code),
                    "redirect_uri" => env('LINKEDIN_REDIRECT_URI_LOGIN'),
                    "client_id" => env('LINKEDIN_CLIENT_ID'),
                    "client_secret" => env('LINKEDIN_CLIENT_SECRET'),
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            return $data['access_token'];
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return null;
        }
    }

    private function getID($token)
    {
        try {
            $client = new Client(['base_uri' => 'https://api.linkedin.com']);

            $response = $client->request('GET', '/v2/me', [
                'headers' => [
                    "Authorization" => "Bearer " . $token,
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            Log::info($data);

            return $data['id'];
        } catch (Exception $e) {
            Log::error($e->getMessage());

            return null;
        }
    }
}
