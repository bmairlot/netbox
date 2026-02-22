<?php

namespace Ancalagon\Netbox;

class NetboxClient
{
    private string $baseUrl;
    private string $token;

    public function __construct()
    {
        $key = $_ENV['NETBOX_KEY'] ?? false;
        $tokenValue = $_ENV['NETBOX_TOKEN'] ?? false;
        $baseUrl = $_ENV['NETBOX_URL_PREFIX'] ?? false;

        if ($key === false || $key === '') {
            throw new Exception('Environment variable NETBOX_KEY is not set');
        }
        if ($tokenValue === false || $tokenValue === '') {
            throw new Exception('Environment variable NETBOX_TOKEN is not set');
        }
        if ($baseUrl === false || $baseUrl === '') {
            throw new Exception('Environment variable NETBOX_URL_PREFIX is not set');
        }

        $this->baseUrl = rtrim($baseUrl, '/');
        $this->token = "nbt_{$key}.{$tokenValue}";
    }

    /**
     * @throws Exception
     */
    public function get(string $endpoint, array $queryParams = []): array
    {
        $url = $this->buildUrl($endpoint);
        if (!empty($queryParams)) {
            $url .= '?' . http_build_query($queryParams);
        }
        return $this->request('GET', $url);
    }

    /**
     * @throws Exception
     */
    public function post(string $endpoint, array $data): array
    {
        return $this->request('POST', $this->buildUrl($endpoint), $data);
    }

    /**
     * @throws Exception
     */
    public function put(string $endpoint, array $data): array
    {
        return $this->request('PUT', $this->buildUrl($endpoint), $data);
    }

    /**
     * @throws Exception
     */
    public function patch(string $endpoint, array $data): array
    {
        return $this->request('PATCH', $this->buildUrl($endpoint), $data);
    }

    /**
     * @throws Exception
     */
    public function delete(string $endpoint): array
    {
        return $this->request('DELETE', $this->buildUrl($endpoint));
    }

    private function buildUrl(string $endpoint): string
    {
        return $this->baseUrl . '/' . ltrim($endpoint, '/');
    }

    /**
     * @throws Exception
     */
    private function request(string $method, string $url, ?array $data = null): array
    {
        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->token,
                'Content-Type: application/json',
                'Accept: application/json',
            ],
        ]);

        if ($data !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            throw new Exception("cURL error: {$curlError}");
        }

        $decoded = json_decode($response, true);

        if ($httpCode < 200 || $httpCode >= 300) {
            $body = $decoded !== null ? json_encode($decoded) : $response;
            throw new Exception("HTTP {$httpCode}: {$body}", $httpCode);
        }

        // DELETE with 204 No Content returns empty body
        if ($decoded === null && $httpCode === 204) {
            return [];
        }

        if ($decoded === null) {
            throw new Exception("Failed to decode JSON response: {$response}");
        }

        return $decoded;
    }
}
