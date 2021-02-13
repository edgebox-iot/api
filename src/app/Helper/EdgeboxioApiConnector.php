<?php
namespace App\Helper;

class EdgeboxioApiConnector
{
    protected $f3;
    protected $token;
    public $api_url = 'https://edgebox.io/wp-json';

    public function __construct()
    {
        $this->f3 = \Base::instance();
    }

    public function get_token($username, $password) {

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
            CURLOPT_POSTFIELDS =>'{
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

        if(!empty($response['token'])) {
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

    public function get_bootnode_info($token = '') {
        
        $token = empty($token) ? $this->token : $token;

        if(empty($token)) {

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

        if(!empty($response['bootnode_address']) && !empty($response['bootnode_token']) && !empty($response['assigned_address'])) {
            $response_status = 'success';
        }

        return [
            'status' => $response_status,
            'value' => $response,
        ];

    }

    public function register_apps($apps) {


    
    }

    public function unregister_apps($apps) {



    }

}