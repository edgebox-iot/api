<?php

namespace App\Helper;

class EdgeboxioApiConnector
{
    protected $token;
    public $api_url = 'https://edgebox.io/wp-json';

    public function get_token($username, $password)
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->api_url . '/jwt-auth/v1/token',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => '{
                "username": "' . $username . '",
                "password": "' . $password . '"
            }',
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        $response = json_decode($response, true);

        if (!empty($response['token'])) {
            $this->token = $response['token'];
            return [
                'status' => 'success',
                'value' => $response['token']
            ];
        } else {
            return [
                'status' => 'error',
                'value' => $response['code']
            ];
        }
    }

    public function get_bootnode_info($token = '')
    {

        $token = empty($token) ? $this->token : $token;

        if (empty($token)) {

            return [
                'status' => 'error',
                'value' => 'A token needs to be issued via get_token, or provided as argument.',
            ];
        }

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->api_url . '/myedgeapp/v1/bootnode',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'Authorization: Bearer ' . $token,
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        $response = json_decode($response, true);

        $response_status = 'error';

        if (!empty($response['bootnode_address']) && !empty($response['bootnode_token']) && !empty($response['assigned_address']) && !empty($response['node_name'])) {
            $response_status = 'success';
        }

        return [
            'status' => $response_status,
            'value' => $response,
        ];
    }

    public function register_apps($token, $apps)
    {

        $token = empty($token) ? $this->token : $token;

        if (empty($token)) {

            return [
                'status' => 'error',
                'value' => 'A token needs to be issued via get_token, or provided as argument.',
            ];
        }

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->api_url . '/myedgeapp/v1/apps/register',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'PUT',
            CURLOPT_POSTFIELDS => '{
            "apps": "' . $apps . '"
        }',
            CURLOPT_HTTPHEADER => array(
                'Authorization: Bearer ' . $token,
                'Content-Type: application/json',
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        $response = json_decode($response, true);

        $response_status = 'error';

        if (!empty($response['apps'])) {
            $response_status = 'success';
        }

        return [
            'status' => $response_status,
            'value' => $response,
        ];
    }

    public function unregister_apps($token, $apps)
    {

        $token = empty($token) ? $this->token : $token;

        if (empty($token)) {

            return [
                'status' => 'error',
                'value' => 'A token needs to be issued via get_token, or provided as argument.',
            ];
        }

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->api_url . '/myedgeapp/v1/apps/unregister',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'PUT',
            CURLOPT_POSTFIELDS => '{
            "apps": "' . $apps . '"
        }',
            CURLOPT_HTTPHEADER => array(
                'Authorization: Bearer ' . $token,
                'Content-Type: application/json',
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        $response = json_decode($response, true);

        $response_status = 'success';

        return [
            'status' => $response_status,
            'value' => $response,
        ];
    }
}