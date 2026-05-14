<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Artisan;

class ZohoAuthController extends Controller
{
    public function connect()
    {
        $clientId = config('services.zoho.client_id');
        $redirectUri = config('services.zoho.redirect_uri');

        $dc = config('services.zoho.data_center', '.com');
        $dc = str_starts_with($dc, '.') ? $dc : '.' . $dc;

        $url = "https://accounts.zoho{$dc}/oauth/v2/auth?" . http_build_query([
            'response_type' => 'code',
            'client_id' => $clientId,
            'scope' => 'ZohoBooks.fullaccess.all',
            'redirect_uri' => $redirectUri,
            'access_type' => 'offline',
            'prompt' => 'consent'
        ]);

        return redirect($url);
    }

    public function callback(Request $request)
    {
        if (!$request->has('code')) {
            return response()->json(['error' => 'No authorization code provided by Zoho.', 'details' => $request->all()]);
        }

        $dc = config('services.zoho.data_center', '.com');
        $dc = str_starts_with($dc, '.') ? $dc : '.' . $dc;

        // Use the exact accounts server provided by Zoho in the callback, or fallback to config
        $accountsServer = $request->query('accounts-server', "https://accounts.zoho{$dc}");

        $response = Http::asForm()->post("{$accountsServer}/oauth/v2/token", [
            'code' => $request->code,
            'client_id' => config('services.zoho.client_id'),
            'client_secret' => config('services.zoho.client_secret'),
            'redirect_uri' => config('services.zoho.redirect_uri'),
            'grant_type' => 'authorization_code'
        ]);

        $data = $response->json();

        if (isset($data['refresh_token'])) {
            $location = $request->query('location');
            $refreshToken = $data['refresh_token'];

            // Defer the .env update so 'php artisan serve' doesn't restart and kill this HTTP response
            register_shutdown_function(function () use ($location, $refreshToken) {
                if ($location) {
                    $this->setEnvValue('ZOHO_DATA_CENTER', $location);
                }
                $this->setEnvValue('ZOHO_REFRESH_TOKEN', $refreshToken);
                Artisan::call('config:clear');
                Artisan::call('cache:clear');
            });

            return redirect()->route('report.index')->with('success', 'Zoho Books Connected Successfully!');
        }

        return response()->json([
            'error' => 'Failed to generate refresh token. The code might be expired or already used. Please visit /zoho/connect again to generate a new code.',
            'hint' => 'Authorization codes expire in 60 seconds and can only be used once. Do not refresh this page.',
            'response' => $data
        ]);
    }

    private function setEnvValue($key, $value)
    {
        $path = base_path('.env');

        if (file_exists($path)) {
            $env = file_get_contents($path);

            $oldValue = env($key);

            if (str_contains($env, "{$key}=")) {
                $env = preg_replace("/^{$key}=.*/m", "{$key}=\"{$value}\"", $env);
            } else {
                $env .= "\n{$key}=\"{$value}\"\n";
            }

            file_put_contents($path, $env);
        }
    }
}
