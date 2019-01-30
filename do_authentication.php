<?php
    include('http.php');

    function authenticate($username, $password, $authen_base_url){
        $authen_headers = [
            'Content-Type: application/json',
            'Accept: application/json'
        ];
        $credential = [
            'username' => $username,
            'password' => $password
        ];
        $authen_url = $authen_base_url . '/api/authenticate';
        $response = post($authen_url, json_encode($credential), $authen_headers);
        $status_code = $response['info']['http_code'];

        switch($status_code){
            case 200:
                $response_json = json_decode($response['result'],true);
                $access_token = $response_json['accessToken'];
                echo now().': Received access_token: ' . $access_token.PHP_EOL;
                return $access_token;
            break;
            case 404:
                echo "Invalid url $authen_url".PHP_EOL;
                exit(1);
            break;
            case 401:
                echo "Invalid username/password. Please reset your password via web".PHP_EOL;
                exit(1);
            break;
            case 500:
                echo "Internal server error. Please try again later".PHP_EOL;
                exit(1);
            break;
            default:
                echo "Failed to authenticate user $username with error code $status_code".PHP_EOL;
                exit(1);
            break;
        }
    }
?>