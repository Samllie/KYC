<?php

namespace App\Http\Controllers;

use App\Services\KycParserService;
use App\Services\VisionService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Throwable;

class KycController extends Controller
{
    public function showScanForm(): View
    {
        return view('kyc.scan', [
            'data' => $this->emptyData(),
        ]);
    }

    public function scanId(Request $request, VisionService $visionService, KycParserService $kycParserService): View|RedirectResponse
    {
        $validated = $request->validate([
            'id_image' => ['required', 'file', 'mimes:jpg,jpeg,png', 'max:5120'],
        ]);

        /** @var UploadedFile $image */
        $image = $validated['id_image'];

        try {
            $ocrResponse = $visionService->detectDocumentText($image);
            $fullText = trim((string) data_get($ocrResponse, 'responses.0.fullTextAnnotation.text', ''));

            if ($fullText === '') {
                return back()->withErrors([
                    'id_image' => 'Unable to scan ID',
                ])->withInput();
            }

            $parsed = $this->validateParsedData($kycParserService->parse($fullText));
            if ($this->isParsedDataEmpty($parsed)) {
                return back()->withErrors([
                    'id_image' => 'Unable to scan ID',
                ])->withInput();
            }

            return view('kyc.scan', [
                'data' => $parsed,
                'uploadedPreview' => $this->buildPreviewDataUri($image),
                'scanSuccess' => true,
            ]);
        } catch (Throwable $exception) {
            report($exception);

            return back()->withErrors([
                'id_image' => 'ID scanning failed. Please try again.',
            ])->withInput();
        }
    }

    /**
     * @param array<string, string> $parsed
     * @return array<string, string>
     */
    private function validateParsedData(array $parsed): array
    {
        $data = [
            'name' => $this->cleanField((string) ($parsed['name'] ?? ''), 120),
            'dob' => $this->cleanField((string) ($parsed['dob'] ?? ''), 20),
            'address' => $this->cleanField((string) ($parsed['address'] ?? ''), 255),
            'id_number' => $this->cleanField((string) ($parsed['id_number'] ?? ''), 40),
        ];

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data['dob'])) {
            $data['dob'] = '';
        }

        if (strlen($data['name']) < 3) {
            $data['name'] = '';
        }

        if (strlen($data['address']) < 6) {
            $data['address'] = '';
        }

        $digitsOnly = (string) preg_replace('/\D+/', '', $data['id_number']);
        if (strlen($digitsOnly) < 6) {
            $data['id_number'] = '';
        }

        return $data;
    }

    /**
     * @param array<string, string> $data
     */
    private function isParsedDataEmpty(array $data): bool
    {
        foreach ($data as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    private function buildPreviewDataUri(UploadedFile $image): string
    {
        $path = $image->getRealPath();
        if ($path === false || !is_file($path)) {
            return '';
        }

        $binary = file_get_contents($path);
        if ($binary === false) {
            return '';
        }

        $mime = $image->getMimeType() ?: 'image/jpeg';
        return 'data:' . $mime . ';base64,' . base64_encode($binary);
    }

    private function cleanField(string $value, int $maxLength): string
    {
        $value = preg_replace('/[\x00-\x1F\x7F]/', '', trim($value)) ?? trim($value);
        if ($value === '') {
            return '';
        }

        if (strlen($value) > $maxLength) {
            $value = substr($value, 0, $maxLength);
        }

        return trim($value);
    }

    /**
     * @return array<string, string>
     */
    private function emptyData(): array
    {
        return [
            'name' => '',
            'dob' => '',
            'address' => '',
            'id_number' => '',
        ];
    }
}
