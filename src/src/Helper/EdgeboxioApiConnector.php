<?php

namespace App\Helper;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\RequestOptions;

class EdgeboxioApiConnector
{
    private string $api_url = 'https://edgebox.io/wp-json';
    private Client $client;
    private string $token;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function get_token(string $username, string $password)
    {
        $url = $this->api_url.'/jwt-auth/v1/token';
        $response = $this->client->post($url, [
            RequestOptions::JSON => [
                'username' => $username,
                'password' => $password,
            ],
        ]);

        $response = json_decode($response->getBody(), true);
        if (!empty($response['token'])) {
            $this->token = $response['token'];

            return [
                'status' => 'success',
                'value' => $response['token'],
            ];
        }

        return [
            'status' => 'error',
            'value' => $response['code'],
        ];
    }

    public function get_bootnode_info(string $token = '')
    {
        $token = empty($token) ? $this->token : $token;

        if (empty($token)) {
            return [
                'status' => 'error',
                'value' => 'A token needs to be issued via get_token, or provided as argument.',
            ];
        }

        $url = $this->api_url.'/myedgeapp/v1/bootnode';

        try {
            $response = $this->client->get($url, [
                RequestOptions::HEADERS => [
                    'Authorization' => sprintf('Bearer %s', $token),
                ],
            ]);
        } catch (ClientException|RequestException $e) {
            return [
                'status' => 'error',
                'value' => json_decode($e->getResponse()->getBody(), true),
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'value' => ['message' => 'An unexpected error occured.'],
            ];
        }

        $response = json_decode($response->getBody(), true);

        $response_status = 'error';

        if (!empty($response['bootnode_address']) && !empty($response['bootnode_token']) && !empty($response['assigned_address']) && !empty($response['node_name'])) {
            $response_status = 'success';
        }

        return [
            'status' => $response_status,
            'value' => $response,
        ];
    }

    public function register_apps(string $token, string $apps, string $ip = '')
    {
        $token = empty($token) ? $this->token : $token;

        if (empty($token)) {
            return [
                'status' => 'error',
                'value' => 'A token needs to be issued via get_token, or provided as argument.',
            ];
        }

        $request_options = [
            'apps' => $apps,
            'ip' => $ip,
        ];

        $url = $this->api_url.'/myedgeapp/v1/apps/register';
        $response = $this->client->put($url, [
            RequestOptions::JSON => $request_options,
            RequestOptions::HEADERS => [
                'Authorization' => sprintf('Bearer %s', $token),
            ],
        ]);

        $response = json_decode($response->getBody(), true);

        $response_status = 'error';

        if (!empty($response['apps'])) {
            $response_status = 'success';
        }

        return [
            'status' => $response_status,
            'value' => $response,
        ];
    }

    public function unregister_apps(string $token, string $apps)
    {
        $token = empty($token) ? $this->token : $token;

        if (empty($token)) {
            return [
                'status' => 'error',
                'value' => 'A token needs to be issued via get_token, or provided as argument.',
            ];
        }

        $url = $this->api_url.'/myedgeapp/v1/apps/unregister';
        $response = $this->client->put($url, [
            RequestOptions::JSON => [
                'apps' => $apps,
            ],
            RequestOptions::HEADERS => [
                'Authorization' => sprintf('Bearer %s', $token),
            ],
        ]);

        $response = json_decode($response->getBody(), true);

        $response_status = 'success';

        return [
            'status' => $response_status,
            'value' => $response,
        ];
    }
}
