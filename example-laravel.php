<?php

/**
 * Laravel Integration Example
 * 
 * This example shows how to integrate LicenseClient in a Laravel application.
 * Place this in app/Services/LicenseService.php or similar.
 */

namespace App\Services;

use LicenseClient;
use Illuminate\Support\Facades\Log;

class LicenseService
{
    private LicenseClient $client;

    public function __construct()
    {
        $this->client = new LicenseClient(
            baseUrl: config('app.license_api_url', env('LICENSE_API_URL', 'http://localhost:8000')),
            storagePath: storage_path('app')
        );
    }

    /**
     * Setup or validate a license
     */
    public function setupLicense(
        string $licenseKey,
        string $domain,
        string $ip,
        string $directory,
        int $checkInterval = 30
    ): array {
        try {
            return $this->client->setupOrValidateLicense(
                licenseKey: $licenseKey,
                domain: $domain,
                ip: $ip,
                directory: $directory,
                checkInterval: $checkInterval
            );
        } catch (\Exception $e) {
            Log::error('License setup failed', [
                'error' => $e->getMessage(),
                'license_key' => $licenseKey,
            ]);
            throw $e;
        }
    }

    /**
     * Validate license using local token
     */
    public function validateLicense(): bool
    {
        $result = $this->client->validateLocalToken(
            domain: request()->getHost(),
            ip: request()->ip(),
            directory: base_path(),
            checkInterval: config('app.license_check_interval', 30)
        );

        if (!$result['valid']) {
            Log::warning('License validation failed', [
                'message' => $result['message'],
            ]);
        }

        return $result['valid'];
    }

    /**
     * Public license validation
     */
    public function publicValidate(string $domainOrIp): array
    {
        try {
            return $this->client->publicValidateLicense($domainOrIp);
        } catch (\Exception $e) {
            Log::error('Public license validation failed', [
                'error' => $e->getMessage(),
                'domain_or_ip' => $domainOrIp,
            ]);
            throw $e;
        }
    }

    /**
     * Get license data from local token
     */
    public function getLicenseData(): ?array
    {
        $result = $this->client->validateLocalToken(
            domain: request()->getHost(),
            ip: request()->ip(),
            directory: base_path(),
            checkInterval: config('app.license_check_interval', 30)
        );

        return $result['data'] ?? null;
    }
}

/**
 * Usage in a Controller:
 * 
 * use App\Services\LicenseService;
 * 
 * class LicenseController extends Controller
 * {
 *     public function setup(Request $request, LicenseService $licenseService)
 *     {
 *         $response = $licenseService->setupLicense(
 *             licenseKey: $request->license_key,
 *             domain: $request->domain,
 *             ip: $request->ip(),
 *             directory: base_path()
 *         );
 * 
 *         return response()->json($response);
 *     }
 * 
 *     public function validate(LicenseService $licenseService)
 *     {
 *         if (!$licenseService->validateLicense()) {
 *             abort(403, 'License validation failed');
 *         }
 * 
 *         return response()->json(['status' => 'valid']);
 *     }
 * }
 */

