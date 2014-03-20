<?php

class Ecube_JcubeConnect_Model_Sync_Transport_Api {
    const ENTER = "\r\n";
    const DOUBLE_ENTER = "\r\n\r\n";

    public function sendRequest($url, $headerData = array(), $postData = array(), $timeout = 1) {
        // Get the curl session object
        $session = curl_init($url);

        // Set the POST options.
        curl_setopt($session, CURLOPT_POST, true);
        curl_setopt($session, CURLOPT_HTTPHEADER, $headerData);
        curl_setopt($session, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($session, CURLOPT_HEADER, true);
        curl_setopt($session, CURLOPT_RETURNTRANSFER, true);

        if ($timeout != false) {
            curl_setopt($session, CURLOPT_TIMEOUT, $timeout);
        }

        // Do the POST and then close the session
        $response = curl_exec($session);

        if (curl_errno($session))
            return array('CURL_ERR', curl_error($session));
        else
            curl_close($session);

        $heads = $this->parseHeaders($response);
        $body = $this->getBody($response);

        // Get HTTP Status code from the response
        $status_code = array();
        preg_match('/\d\d\d/', $heads[0], $status_code);

        return array($status_code[0], $body);
    }

    private function parseHeaders($message) {
        $head_end = strpos($message, self::DOUBLE_ENTER);
        $headers = $this->getHeaders(substr($message, 0, $head_end + strlen(self::DOUBLE_ENTER)));
        if (!is_array($headers) || empty($headers)) {
            return null;
        }
        if (!preg_match('%[HTTP/\d\.\d] (\d\d\d)%', $headers[0], $status_code)) {
            return null;
        }

        switch ($status_code[1]) {
            case '200':
                $parsed = $this->parseHeaders(substr($message, $head_end + strlen(self::DOUBLE_ENTER)));
                return is_null($parsed) ? $headers : $parsed;
            break;

            default:
                return $headers;
            break;
        }
    }

    private function getHeaders($heads, $format = 0) {
        $fp = explode(self::ENTER, $heads);
        foreach($fp as $header) {
            if ($header == '') {
                $eoheader = true;
                break;
            }
            else {
                $header = trim($header);
            }

            if ($format == 1) {
                $key = array_shift(explode(':', $header));
                if ($key == $header)
                    $headers[] = $header;
                else
                    $headers[$key] = substr($header, strlen($key) + 2);
                unset($key);
            }
            else {
                $headers[] = $header;
            }
        }
        return $headers;
    }

    private function getBody($heads) {
        $fp = explode(self::DOUBLE_ENTER, $heads, 2);
        return $fp[1];
    }
}
