<?php

class mailjet{
    private $endpointUrl;
    private $onErrorFunc; // variáveis de funções
    private $mjApiVersion = 'v3', $mjPublicKey, $mjPrivateKey; // variaveis de autenticacao
    private $apiVersionEndpoints = Array(
        'v3' => 'https://api.mailjet.com/v3/REST/',
        'v3.1' => 'https://api.mailjet.com/v3.1/',
    );
    function __construct($mjPublicKey = false, $mjPrivateKey = false, $mjApiVersion = 'v3'){
        if(isset($this->apiVersionEndpoints[$mjApiVersion])){
            $this->endpointUrl = $this->apiVersionEndpoints[$mjApiVersion];
        }else{
            $this->throwError('api-version', 'Invalid API VERSION ('.$mjApiVersion.')');
            return false;
        }
        if($mjPublicKey && $mjPrivateKey){
            return $this->auth($mjPublicKey, $mjPrivateKey);
        }
        return true;
    }
    function auth($mjPublicKey, $mjPrivateKey){
        $this->mjPublicKey = $mjPublicKey;
        $this->mjPrivateKey = $mjPrivateKey;
        return true;
    }
    function get($funcName, $parameters = null){
        $callUrl = $this->endpointUrl.'/'.$funcName;
        if($retorno = $this->curlConnect($callUrl, $parameters, null, null)){
            if(isset($retorno['success']) && isset($retorno[$funcName])){
                return $retorno[$funcName];
            }
        }
        return $retorno;
    }
    function post($funcName, $parameters = null){
        $callUrl = $this->endpointUrl.'/'.$funcName;
        if($retorno = $this->curlConnect($callUrl, null, $parameters, null)){
            if(isset($retorno['success']) && isset($retorno[$funcName])){
                return $retorno[$funcName];
            }
        }
        return $retorno;
    }
    function put($funcName, $parameters = null){
        $callUrl = $this->endpointUrl.'/'.$funcName;
        $headers = Array('PUT');
        if($retorno = $this->curlConnect($callUrl, null, $parameters, $headers)){
            if(isset($retorno['success']) && isset($retorno[$funcName])){
                return $retorno[$funcName];
            }
        }
        return $retorno;
    }
    private function curlConnect($url, $get = null, $post = null, $headers = null, $timeout = 5){
        $headers[] = 'Content-type:application/json';
        if($get && !empty($get)){
            $url = trim($url, ' /') . '?' . http_build_query($get);
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        if($post){
            curl_setopt($ch, CURLOPT_POST, 1);
        }
        if (!empty($post)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post));
        }
        if ($headers && !empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        curl_setopt($ch, CURLOPT_USERPWD, $this->mjPublicKey . ":" . $this->mjPrivateKey);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        $returnData = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if($jsonDecoded = @json_decode($returnData, true)){
            $this->analyseReturnErrors($jsonDecoded);
            return $jsonDecoded;
        }else{
            $this->throwError('invalid-return ('.$httpcode.')', $returnData);
        }
        return $returnData;
    }
    private function analyseReturnErrors($returnData){
        if(isset($returnData['StatusCode']) && ($returnData['StatusCode'] >= 400 && $returnData['StatusCode'] <= 500)){
            $errorCode = (isset($returnData['StatusCode']))? $returnData['StatusCode'] : 0;
            $this->throwError($errorCode, $returnData['ErrorMessage']);
            return false;
        }
        return true;
    }
    private function throwError($code, $description){
        if($this->onErrorFunc && is_callable($this->onErrorFunc)){
            if(call_user_func($this->onErrorFunc, $code, $description)){
                return true;
            }
        }
        throw new Exception('Mailjet API Error: ['.$code.'], '.$description);
        return true;
    }
    function onError($errorFunc){
        if(is_callable($errorFunc)){
            $this->onErrorFunc = $errorFunc;
            return true;
        }
        return false;
    }
}

