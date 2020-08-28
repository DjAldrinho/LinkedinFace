<?php

namespace App\Http\Controllers;

use App\Linkedin;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LinkedinController extends Controller
{

    public function all()
    {
        return Linkedin::all();
    }

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

            return response()->json(['msg' => 'Token guardado exitosamente!', 'show' => true]);
        } catch (Exception $e) {
            return response()->json($e->getMessage());
        }
    }

    public function sharePost(Request $request)
    {
        $link = env('APP_URL');
        $access_token = $request->linkedin['authToken'];
        $linkedin_id = $request->linkedin['linkedinID'];
        $body = new \stdClass();
        $body->content = new \stdClass();
        $body->content->contentEntities[0] = new \stdClass();
        $body->text = new \stdClass();
        $body->content->contentEntities[0]->thumbnails[0] = new \stdClass();
        $body->content->contentEntities[0]->entityLocation = $link;
        $body->content->contentEntities[0]->thumbnails[0]->resolvedUrl = 'https://www.gettyimages.es/gi-resources/images/500px/983801190.jpg';
        $body->content->title = 'Prueba';
        $body->owner = 'urn:li:person:' . $linkedin_id;
        $body->text->text = 'Resumen del Post. Prueba desde API';
        $body_json = json_encode($body, true);

        try {
            $client = new Client(['base_uri' => 'https://api.linkedin.com']);
            $response = $client->request('POST', '/v2/shares', [
                'headers' => [
                    "Authorization" => "Bearer " . $access_token,
                    "Content-Type" => "application/json",
                    "x-li-format" => "json"
                ],
                'body' => $body_json,
            ]);

            if ($response->getStatusCode() !== 201) {
                LOG::error('Error: ' . $response->getLastBody()->errors[0]->message);
                return response()->json('Error: ' . $response->getLastBody()->errors[0]->message);
            }

            return response()->json('Post is shared on LinkedIn successfully.', 201);
        } catch (Exception $e) {
            return response()->json($e->getMessage(), 500);
        }
    }

    public function infoAccess(Request $request)
    {

        $infoAccess = DB::table('linkedin_api')
            ->whereRaw('user_id = ? AND created_at >= NOW() - INTERVAL \'60 days\'', [$request->userId])
            ->limit(1)->get();

        if (count($infoAccess) > 0) {
            $infoAccess = $infoAccess[0];
        }

        return response()->json(['linkedin' => $infoAccess]);
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
