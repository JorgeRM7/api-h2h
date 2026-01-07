<?php

namespace App\Services;

use Eher\OAuth\Consumer;
use Eher\OAuth\OAuthException;
use Eher\OAuth\Request;
use Eher\OAuth\Token;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use App\Utils\HmacSha256;

class NetSuiteRestService
{
    protected Client $client;
    protected string $baseUrl;
    protected int $account;

    public function __construct()
    {
        $this->client  = new Client(['timeout' => 30]);
        $this->baseUrl = config('services.netsuite.base_url');
        $this->account = (int) config('services.netsuite.account');
    }

    /**
     * @throws GuzzleException
     */
    public function request(string $path, string $method = 'GET', array $data = [])
    {
        $url     = $this->baseUrl . $path;
        $headers = $this->buildHeaders($method, $url);

        $options = [
            RequestOptions::HEADERS => $headers,
        ];

        if (!empty($data)) {
            $options[RequestOptions::JSON] = $data;
        }

        $response = $this->client->request($method, $url, $options);

        if ($response->getStatusCode() >= 500) {
            return null;
        }

        return json_decode($response->getBody()->getContents(), true);
    }

    protected function buildHeaders(string $method, string $url): array
    {
        $signatureMethod = new HmacSha256();

        $consumer = new Consumer(
            config('services.netsuite.consumer_key'),
            config('services.netsuite.consumer_secret')
        );

        $token = new Token(
            config('services.netsuite.token_key'),
            config('services.netsuite.token_secret')
        );

        $request = new Request($method, $url, [
            'oauth_nonce'            => md5(uniqid('', true)),
            'oauth_timestamp'        => time(),
            'oauth_version'          => '1.0',
            'oauth_token'            => $token->key,
            'oauth_consumer_key'     => $consumer->key,
            'oauth_signature_method' => $signatureMethod->get_name(),
        ]);

        $signature = $request->build_signature($signatureMethod, $consumer, $token);
        $request->set_parameter('oauth_signature', $signature);
        $request->set_parameter('realm', $this->account);

        try {
            return [
                'Authorization' => substr($request->to_header($this->account), 15),
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
            ];
        } catch (OAuthException $e) {
            return [];
        }
    }
}
