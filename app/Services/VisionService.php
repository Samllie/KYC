<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class VisionService
{
    private string $apiKey;
    private string $endpoint = 'https://vision.googleapis.com/v1/images:annotate';

    public function __construct()
    {
        $this->apiKey = (string) config('services.vision.key', '');
    }

    /**
     * Send an uploaded image to Google Vision DOCUMENT_TEXT_DETECTION.
     */
    public function detectDocumentText(UploadedFile $image): array
    {
        if ($this->apiKey === '') {
            throw new RuntimeException('Google Vision API key is not configured.');
        }

        $realPath = $image->getRealPath();
        if ($realPath === false || !is_file($realPath)) {
            throw new RuntimeException('Uploaded image is unreadable.');
        }

        $binary = file_get_contents($realPath);
        if ($binary === false) {
            throw new RuntimeException('Unable to read uploaded image.');
        }

        $payload = [
            'requests' => [
                [
                    'image' => [
                        'content' => base64_encode($binary),
                    ],
                    'features' => [
                        [
                            'type' => 'DOCUMENT_TEXT_DETECTION',
                        ],
                    ],
                ],
            ],
        ];

        $response = Http::acceptJson()
            ->asJson()
            ->timeout(25)
            ->retry(2, 300)
            ->post($this->endpoint . '?key=' . urlencode($this->apiKey), $payload);

        if (!$response->successful()) {
            throw new RuntimeException('Google Vision API request failed (HTTP ' . $response->status() . ').');
        }

        $json = $response->json();
        if (!is_array($json)) {
            throw new RuntimeException('Google Vision API returned an invalid JSON payload.');
        }

        $apiError = data_get($json, 'error.message') ?: data_get($json, 'responses.0.error.message');
        if (is_string($apiError) && trim($apiError) !== '') {
            throw new RuntimeException('Google Vision API error: ' . $apiError);
        }

        return $json;
    }
}
