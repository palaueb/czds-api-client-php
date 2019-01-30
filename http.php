<?php
    function now(){
        return date("Y-m-d H:i:s");
    }
    function post($url, $post_data = [], $headers = []){
        $ch = curl_init();
        ## DEBUG
        //curl_setopt($ch, CURLOPT_VERBOSE, true);
        //curl_setopt($ch, CURLINFO_HEADER_OUT, true); // enable tracking

        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        if(count($headers) > 0){
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        $result = curl_exec($ch);
        $information = curl_getinfo($ch);

        $parts = process_curl_response($result);
        $head = $parts[0];
        $body = $parts[1];

        $return = [
            'info'   => $information,
            'head'   => $head,
            'result' => $body
        ];
        return $return;
    }
    function do_get($url, $access_token){
        $ch = curl_init();
        ## DEBUG
        //curl_setopt($ch, CURLOPT_VERBOSE, true);
        //curl_setopt($ch, CURLINFO_HEADER_OUT, true); // enable tracking
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Content-Type: application/json",
            "Accept: application/json",
            "Authorization: Bearer ".$access_token
        ]);

        $result = curl_exec($ch);
        $information = curl_getinfo($ch);

        $parts = process_curl_response($result);
        $head = $parts[0];
        $body = $parts[1];

        $return = [
            'info'   => $information,
            'head'   => $head,
            'result' => $body
        ];
        
        return $return;
    }
    function process_curl_response($response){
        $headers = array();
        $body = "";
    
        $header_text = substr($response, 0, strpos($response, "\r\n\r\n"));
        foreach (explode("\r\n", $header_text) as $i => $line){
            if ($i === 0){
                $headers['http_code'] = $line;
            }else{
                list ($key, $value) = explode(': ', $line);
                $headers[$key] = $value;
            }
        }

        $body = substr($response,strlen($header_text) + 4);
        return [$headers,$body];
    }
?>