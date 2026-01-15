<?php

/**
 * Bizfunnel Licensing Client PHP SDK
 * 
 * A PHP SDK for validating and setting up licenses that works in both
 * core PHP and Laravel applications.
 * 
 * @package Bizfunnel\LicenseClient
 * @version 1.0.0
 */
class LicenseClient
{
    /**
     * Base API URL
     */
    private string $baseUrl;

    /**
     * Storage path for local token
     */
    private string $storagePath;

    /**
     * Whether to use Laravel HTTP client if available
     */
    private bool $useLaravelHttp = false;

    /**
     * Initialize the License Client
     * 
     * @param string $baseUrl Base URL of the license management API
     * @param string|null $storagePath Path to store local token file (defaults to current directory)
     */
    public function __construct(string $baseUrl, ?string $storagePath = null)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->storagePath = $storagePath ?: __DIR__;
        
        // Detect if Laravel HTTP client is available
        if (class_exists('\Illuminate\Support\Facades\Http')) {
            $this->useLaravelHttp = true;
        }
    }

    /**
     * Setup or validate a license
     * 
     * This method intelligently checks local token first. If valid, returns immediately.
     * If invalid/expired and auto-refresh is enabled, calls server to get new token.
     * 
     * @param string $licenseKey The license key
     * @param string $domain The domain name
     * @param string $ip The IP address
     * @param string $directory The installation directory
     * @param int $checkInterval Check interval in days (7-90)
     * @param bool $autoRefresh Whether to automatically refresh token if invalid (default: true)
     * @return array Response array with status, message, and data
     * @throws \Exception
     */
    public function setupOrValidateLicense(
        string $licenseKey,
        string $domain,
        string $ip,
        string $directory,
        int $checkInterval = 30,
        bool $autoRefresh = true
    ): array {
        if ($checkInterval < 7 || $checkInterval > 90) {
            throw new \Exception('Check interval must be between 7 and 90 days');
        }

        // First, check if we have a valid local token
        $localToken = $this->loadLocalToken();
        if (!empty($localToken)) {
            $validation = $this->validateLocalToken(
                localToken: $localToken,
                domain: $domain,
                ip: $ip,
                directory: $directory,
                checkInterval: $checkInterval
            );

            // If token is valid, return success without calling server
            if ($validation['valid']) {
                return [
                    'status' => 'success',
                    'message' => 'License is valid (using cached token)',
                    'data' => [
                        'local_token' => $localToken,
                        'cached' => true,
                    ],
                ];
            }

            // Token is invalid/expired
            // If auto-refresh is disabled, return error
            if (!$autoRefresh) {
                return [
                    'status' => 'error',
                    'message' => 'Local token is invalid or expired. Auto-refresh is disabled.',
                    'validation_error' => $validation['message'],
                ];
            }

            // Auto-refresh is enabled, continue to call server below
        }

        // No valid token found or auto-refresh enabled - call server to get/refresh token
        $url = $this->baseUrl . '/api/v1/licenses/setup-or-validate';
        
        $data = [
            'license_key' => $licenseKey,
            'domain' => $domain,
            'ip' => $ip,
            'dir' => $directory,
            'check_interval' => $checkInterval,
        ];

        $response = $this->makeRequest('POST', $url, $data);

        if ($response['status'] === 'success' && isset($response['data']['local_token'])) {
            $this->saveLocalToken($response['data']['local_token']);
            $response['data']['cached'] = false; // Indicate this is a fresh token from server
        }

        return $response;
    }

    /**
     * Publicly validate a license by domain or IP
     * 
     * @param string $domainOrIp Domain name or IP address
     * @return array Response array with status and message
     * @throws \Exception
     */
    public function publicValidateLicense(string $domainOrIp): array
    {
        $url = $this->baseUrl . '/api/v1/licenses/validate';
        
        $data = [
            'domain_or_ip' => $domainOrIp,
        ];

        return $this->makeRequest('POST', $url, $data);
    }

    /**
     * Validate local token
     * 
     * @param string|null $localToken Local token to validate (if null, loads from storage)
     * @param string $domain Current domain
     * @param string $ip Current IP address
     * @param string $directory Current directory
     * @param int $checkInterval Check interval
     * @return array Validation result with status and message
     */
    public function validateLocalToken(
        ?string $localToken = null,
        string $domain = '',
        string $ip = '',
        string $directory = '',
        int $checkInterval = 30
    ): array {
        if ($localToken === null) {
            $localToken = $this->loadLocalToken();
        }

        if (empty($localToken)) {
            return [
                'status' => 'error',
                'message' => 'Local token not found',
                'valid' => false,
            ];
        }

        // Remove random strings (first and last 16 characters)
        $parts = explode('.', $localToken);
        if (count($parts) !== 5) {
            return [
                'status' => 'error',
                'message' => 'Invalid token format',
                'valid' => false,
            ];
        }

        // Extract components: [random1, hash1, encoded_data, hash2, random2]
        $hash1 = $parts[1];
        $encodedData = $parts[2];
        $hash2 = $parts[3];

        // Decode the data
        $decodedData = json_decode(base64_decode($encodedData), true);
        if ($decodedData === null) {
            return [
                'status' => 'error',
                'message' => 'Failed to decode token data',
                'valid' => false,
            ];
        }

        // Verify hash2
        $expectedHash2 = hash('sha256', $hash1 . $encodedData);
        if ($expectedHash2 !== $hash2) {
            return [
                'status' => 'error',
                'message' => 'Token integrity check failed',
                'valid' => false,
            ];
        }

        // Verify hash1 (requires license secret from server, so we'll validate against stored data)
        // For full validation, we need to check with server or have license_secret stored
        // This is a basic validation - for full validation, call setupOrValidateLicense

        // Check if token is expired based on check_interval
        if (isset($decodedData['last_checked_at'])) {
            $lastChecked = strtotime($decodedData['last_checked_at']);
            $now = time();
            $daysSinceCheck = ($now - $lastChecked) / 86400;
            
            if ($daysSinceCheck > $checkInterval) {
                return [
                    'status' => 'warning',
                    'message' => 'Token check interval exceeded. Please re-validate with server.',
                    'valid' => false,
                    'data' => $decodedData,
                ];
            }
        }

        return [
            'status' => 'success',
            'message' => 'Local token is valid',
            'valid' => true,
            'data' => $decodedData,
        ];
    }

    /**
     * Validate local token and auto-refresh if invalid
     * 
     * @param string $licenseKey The license key (required for auto-refresh)
     * @param string $domain Current domain
     * @param string $ip Current IP address
     * @param string $directory Current directory
     * @param int $checkInterval Check interval in days
     * @param bool $autoRefresh Whether to automatically refresh token if invalid
     * @return array Validation result with status and message
     */
    public function validateLocalTokenWithAutoRefresh(
        string $licenseKey,
        string $domain,
        string $ip,
        string $directory,
        int $checkInterval = 30,
        bool $autoRefresh = true
    ): array {
        // First, validate the existing token
        $validation = $this->validateLocalToken(
            localToken: null,
            domain: $domain,
            ip: $ip,
            directory: $directory,
            checkInterval: $checkInterval
        );

        // If token is valid, return it
        if ($validation['valid']) {
            return $validation;
        }

        // If auto-refresh is enabled and token is invalid, try to get a new one
        if ($autoRefresh) {
            try {
                $response = $this->setupOrValidateLicense(
                    licenseKey: $licenseKey,
                    domain: $domain,
                    ip: $ip,
                    directory: $directory,
                    checkInterval: $checkInterval
                );

                if ($response['status'] === 'success') {
                    return [
                        'status' => 'success',
                        'message' => 'Token was invalid, but successfully refreshed',
                        'valid' => true,
                        'refreshed' => true,
                        'data' => $this->validateLocalToken(
                            localToken: $response['data']['local_token'] ?? null,
                            domain: $domain,
                            ip: $ip,
                            directory: $directory,
                            checkInterval: $checkInterval
                        )['data'] ?? null,
                    ];
                } else {
                    return [
                        'status' => 'error',
                        'message' => 'Token validation failed and refresh failed: ' . ($response['message'] ?? 'Unknown error'),
                        'valid' => false,
                        'refreshed' => false,
                    ];
                }
            } catch (\Exception $e) {
                return [
                    'status' => 'error',
                    'message' => 'Token validation failed and refresh exception: ' . $e->getMessage(),
                    'valid' => false,
                    'refreshed' => false,
                ];
            }
        }

        // If auto-refresh is disabled, return the validation result
        return $validation;
    }

    /**
     * Get stored local token
     * 
     * @return string|null
     */
    public function getLocalToken(): ?string
    {
        return $this->loadLocalToken();
    }

    /**
     * Save local token to storage
     * 
     * @param string $token
     * @return bool
     */
    private function saveLocalToken(string $token): bool
    {
        $filePath = $this->getTokenFilePath();
        return file_put_contents($filePath, $token) !== false;
    }

    /**
     * Load local token from storage
     * 
     * @return string|null
     */
    private function loadLocalToken(): ?string
    {
        $filePath = $this->getTokenFilePath();
        if (file_exists($filePath)) {
            return trim(file_get_contents($filePath));
        }
        return null;
    }

    /**
     * Get token file path
     * 
     * @return string
     */
    private function getTokenFilePath(): string
    {
        return rtrim($this->storagePath, '/') . '/.license_token';
    }

    /**
     * Make HTTP request
     * 
     * @param string $method HTTP method
     * @param string $url Request URL
     * @param array $data Request data
     * @return array Response data
     * @throws \Exception
     */
    private function makeRequest(string $method, string $url, array $data = []): array
    {
        if ($this->useLaravelHttp) {
            return $this->makeLaravelRequest($method, $url, $data);
        }

        return $this->makeCurlRequest($method, $url, $data);
    }

    /**
     * Make request using Laravel HTTP client
     * 
     * @param string $method
     * @param string $url
     * @param array $data
     * @return array
     */
    private function makeLaravelRequest(string $method, string $url, array $data = []): array
    {
        if ($method === 'POST') {
            $response = \Illuminate\Support\Facades\Http::post($url, $data);
        } else {
            $response = \Illuminate\Support\Facades\Http::get($url, $data);
        }

        if ($response->failed()) {
            throw new \Exception('API request failed: ' . $response->body());
        }

        return $response->json();
    }

    /**
     * Make request using cURL
     * 
     * @param string $method
     * @param string $url
     * @param array $data
     * @return array
     * @throws \Exception
     */
    private function makeCurlRequest(string $method, string $url, array $data = []): array
    {
        $ch = curl_init();

        $headers = [
            'Content-Type: application/json',
            'Accept: application/json',
        ];

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new \Exception('cURL error: ' . $error);
        }

        $decoded = json_decode($response, true);
        if ($decoded === null) {
            throw new \Exception('Invalid JSON response: ' . $response);
        }

        return $decoded;
    }
}

