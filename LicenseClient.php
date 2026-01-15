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
     * @param string $licenseKey The license key
     * @param string $domain The domain name
     * @param string $ip The IP address
     * @param string $directory The installation directory
     * @param int $checkInterval Check interval in days (7-90)
     * @return array Response array with status, message, and data
     * @throws \Exception
     */
    public function setupOrValidateLicense(
        string $licenseKey,
        string $domain,
        string $ip,
        string $directory,
        int $checkInterval = 30
    ): array {
        if ($checkInterval < 7 || $checkInterval > 90) {
            throw new \Exception('Check interval must be between 7 and 90 days');
        }

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

