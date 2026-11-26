<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Minimal WATI (WhatsApp Business API) client. Only sends approved template
 * messages, the one kind WhatsApp allows for a first, business-started message.
 */
class Wati
{
    public static function enabled(): bool
    {
        return filled(config('services.wati.endpoint')) && filled(config('services.wati.token'));
    }

    /** 10 digit Indian mobile -> 91XXXXXXXXXX, the format WATI expects. */
    public static function number(string $mobile): string
    {
        $digits = preg_replace('/\D/', '', $mobile);

        return strlen($digits) === 10 ? config('services.wati.country_code').$digits : $digits;
    }

    /**
     * @param  array<string, string>  $parameters  template variable name => value
     *
     * @throws RuntimeException when WATI refuses the message
     */
    public function sendTemplate(string $mobile, string $template, array $parameters): void
    {
        $params = collect($parameters)
            ->map(fn ($value, $name) => ['name' => $name, 'value' => (string) $value])
            ->values()
            ->all();

        self::isV3() ? $this->sendV3($mobile, $template, $params) : $this->sendV1($mobile, $template, $params);
    }

    /** New API: endpoint is https://live-mt-server.wati.io/api/ext/v3 (no account id). */
    public static function isV3(): bool
    {
        return str_contains((string) config('services.wati.endpoint'), '/api/ext/v3');
    }

    private function sendV3(string $mobile, string $template, array $params): void
    {
        $response = $this->http()->post($this->url('messageTemplates/send'), [
            'template_name' => $template,
            'broadcast_name' => config('services.wati.broadcast_name'),
            'recipients' => [[
                'phone_number' => self::number($mobile),
                'custom_params' => $params,
            ]],
        ]);

        // 200 = accepted; per-number problems come back in recipients[].errors.
        $errors = collect($response->json('recipients', []))->pluck('errors')->flatten(1)->filter();
        if ($response->failed() || $response->json('success') === false || $errors->isNotEmpty()) {
            throw new RuntimeException('WATI '.$response->status().': '.$response->body());
        }
    }

    /** Classic API: endpoint is https://live-mt-server.wati.io/<account id>. */
    private function sendV1(string $mobile, string $template, array $params): void
    {
        $response = $this->http()->post($this->url('api/v1/sendTemplateMessage?'.http_build_query([
            'whatsappNumber' => self::number($mobile),
        ])), [
            'template_name' => $template,
            'broadcast_name' => config('services.wati.broadcast_name'),
            'parameters' => $params,
        ]);

        // WATI can answer 200 with "result": false (e.g. template not approved, invalid number).
        if ($response->failed() || $response->json('result') === false) {
            throw new RuntimeException('WATI '.$response->status().': '.$response->body());
        }
    }

    private function http(): PendingRequest
    {
        // WATI shows the token with a "Bearer " prefix; accept it with or without.
        $token = preg_replace('/^Bearer\s+/i', '', (string) config('services.wati.token'));

        return Http::withToken($token)->acceptJson()->timeout(15);
    }

    private function url(string $path): string
    {
        return rtrim((string) config('services.wati.endpoint'), '/').'/'.$path;
    }
}
