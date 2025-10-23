<?php

namespace Crater\Http\Controllers\V1\Admin\ExchangeRate;

use Crater\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class BcvRateController extends Controller
{
    /**
     * Obtiene la tasa de cambio USD -> VES actual del BCV.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getRate(Request $request)
    {
        $cacheKey = 'bcv_usd_ves_rate';
        $cacheDuration = now()->addHour(); // Cache por 1 hora

        try {
            $rate = Cache::remember($cacheKey, $cacheDuration, function () {

                //$response = Http::timeout(10)->get('https://www.bcv.org.ve/');
                $response = Http::timeout(10)->withoutVerifying()->get('https://www.bcv.org.ve/');

                if ($response->failed()) {
                    throw new \Exception('No se pudo conectar al sitio web del BCV.');
                }

                $html = $response->body();

                $pattern = '#<div id="dolar".*?<strong>\s*([\d,\.]+)\s*</strong>#is';

                if (preg_match($pattern, $html, $matches)) {
                    $rate_usd_in_ves = (float) str_replace(',', '.', str_replace('.', '', $matches[1]));

                    if ($rate_usd_in_ves <= 0) {
                        throw new \Exception('La tasa extraída del BCV es inválida.');
                    }
                    return $rate_usd_in_ves;
                } else {
                    throw new \Exception('No se pudo encontrar la tasa de Dólar en el HTML del BCV.');
                }
            });

            // Éxito
            return response()->json([
                'success' => true,
                'source' => 'BCV',
                'rate_usd_ves' => $rate,
            ]);

        } catch (\Exception $e) {
            Log::error('Error en BcvRateController: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'No se pudo obtener la tasa del BCV.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
