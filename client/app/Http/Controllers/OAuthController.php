<?php

namespace App\Http\Controllers;

use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class OAuthController extends Controller
{
    public function redirect()
    {
        $queries = http_build_query([
            'client_id' => config('services.oauth_server.client_id'),
            'redirect_uri' => config('services.oauth_server.redirect'),
            'response_type' => 'code',
            'scope' => 'view-posts',
        ]);

        return redirect(config('services.oauth_server.uri') . '/oauth/authorize?' . $queries);
    }

    public function callback(Request $request)
    {

        // if (!$request->user()) {
        //     return response()->json(['error' => 'User not authenticated'], 401);
        // }

        // $response = Http::post(config('services.oauth_server.uri') . '/oauth/token', [
        //     'grant_type' => 'authorization_code',
        //     'client_id' => config('services.oauth_server.client_id'),
        //     'client_secret' => config('services.oauth_server.client_secret'),
        //     'redirect_uri' => config('services.oauth_server.redirect'),
        //     'code' => $request->code,
        // ]);

        // $response = $response->json();
        // // $request->user()->token()->delete();

        // $request->user()->token()->create([
        //     'access_token' => $response['access_token'],
        //     'expires_in' => $response['expires_in'],
        //     'refresh_token' => $response['refresh_token'],
        // ]);

        // return redirect('/home');

        // Check if the user is authenticated
        $user = $request->user();

        if (!$user) {
            return response()->json(['error' => 'User not authenticated'], 401);
        }

        $response = Http::post(config('services.oauth_server.uri') . '/oauth/token', [
            'grant_type' => 'authorization_code',
            'client_id' => config('services.oauth_server.client_id'),
            'client_secret' => config('services.oauth_server.client_secret'),
            'redirect_uri' => config('services.oauth_server.redirect'),
            'code' => $request->code,
        ]);

        // Check if the response is successful
        if ($response->failed()) {
            return response()->json(['error' => 'Failed to obtain tokens from OAuth server'], 500);
        }

        $response = $response->json();

        // Delete existing tokens for this user, if any
        $user->token()->delete();

        // Store the new tokens
        $user->token()->create([
            'access_token' => $response['access_token'],
            'expires_in' => $response['expires_in'],
            'refresh_token' => $response['refresh_token'],
        ]);

        return redirect('/home');

    }

    public function refresh(Request $request)
    {
        $response = Http::post(config('services.oauth_server.uri') . '/oauth/token', [
            'grant_type' => 'refresh_token',
            'refresh_token' => $request->user()->token->refresh_token,
            'client_id' => config('services.oauth_server.client_id'),
            'client_secret' => config('services.oauth_server.client_secret'),
            'redirect_uri' => config('services.oauth_server.redirect'),
            'scope' => 'view-posts',
        ]);

        if ($response->status() !== 200) {
            $request->user()->token()->delete();

            return redirect('/home')
                ->withStatus('Authorization failed from OAuth server.');
        }

        $response = $response->json();
        $request->user()->token()->update([
            'access_token' => $response['access_token'],
            'expires_in' => $response['expires_in'],
            'refresh_token' => $response['refresh_token'],
        ]);

        return redirect('/home');
    }
}
