<?php

class Zy_Core_Rpc
{
    /**
     * 发送一个curl请求
     * @param string $url
     * @param array $queryParams
     * @param array $headerParams
     * @return mixed
     */
    public function call($params = array()){
        $params = $this->getReqParam($params);
        
        if (empty($params["url"])) {
            return false ;
        }
        $header = array(
            'Content-type: application/json'
        );
        if (!empty($params['header'])) {
            $header = $params['header'];
        }

        $isPost = 0;
        if (!empty($params['method']) && $params["method"] == "post") {
            $isPost = 1;
        }

        $timeOut = 2;
        if (isset($params['time_out']) && $params["time_out"] > 0) {
            $timeOut = intval($params["time_out"]);
        }

        $curl = curl_init ();
        curl_setopt ( $curl, CURLOPT_HTTPHEADER, $header);
        curl_setopt ( $curl, CURLOPT_URL, $params["url"]);
        curl_setopt ( $curl, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt ( $curl,CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt ( $curl,CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt ( $curl, CURLOPT_HEADER, false );
        curl_setopt ( $curl, CURLOPT_POST, $isPost );
        curl_setopt ( $curl, CURLOPT_TIMEOUT, $timeOut);
        curl_setopt ( $curl, CURLOPT_CONNECTTIMEOUT,$timeOut);

        if ($isPost) {
            curl_setopt ( $curl, CURLOPT_POSTFIELDS, $params['payload']);
        }

        $result = curl_exec ( $curl );
        if (false === $result) {
            $result = array(
                'errno' => curl_errno($curl),
                "errmsg" => curl_error($curl)
            );
        } else {
            $info = curl_getinfo($curl);
            $result = array(
                "errno" => $info["http_code"],
                "errmsg" => json_encode($info),
                "data" => $result,
            );
        }
        curl_close($curl);
        return $this->handleResponse($result);
    }
    public function getReqParam($params){}
    public function handleResponse($response){}
}