<?php

// ai
class Zy_Helper_Ai_Speakxf extends Zy_Core_Rpc{  

    private $service = "https://spark-api-open.xf-yun.com/v1/chat/completions";

    /** 
     * @文本生成, 单次问答生成结果
     * @param
     * @return array
     */
    public function getReqParam ($arrParam) {
        // header 固定
        $header = array(
            'Content-Type: application/json',
            'Authorization: Bearer ' . $arrParam["appKey"],
        );

        $input =  array(
            "model" => "generalv3.5",
            "messages" => array(
                array(
                    "role" => "system",
                    "content" => $arrParam["prompt"],                
                ),
                array(
                    "role" => "user",
                    "content" => $arrParam["content"],
                ),
            ),
        );

        return array(
            'method'    => 'post',
            'payload'   => json_encode($input),
            'header'    => $header,
            "url"       => $this->service,
            "time_out"  => 10,
        );
    }

    /**
     * @param $response
     * @return array|mixed
     * @throws Exception
     */
    public function handleResponse($response)
    {
        if ($response !== false) {
            if (isset($response["errno"]) && $response["errno"] == 200 && !empty($response["data"])) {
                $responseData = json_decode($response["data"], true);
                if (!empty($responseData["choices"]) && is_array($responseData["choices"])) {
                    $retContent = "";
                    foreach ($responseData["choices"] as $v) {
                        if (!empty($v["message"]["content"])) {
                            $v["message"]["content"] = trim($v["message"]["content"], "```json");
                            $v["message"]["content"] = trim($v["message"]["content"], "```");
                            $v["message"]["content"] = json_decode($v["message"]["content"], true);
                            if (!empty($v["message"]['content'])) {
                                $retContent = $v["message"]['content'];
                            }
                            break;
                        }
                    }
                    return $retContent;
                }
            }
            throw new Exception(" deepseek response failed, ret " .json_encode($response));
        }
        throw new Exception("talk to deepseek failed, ret ");
    }
}
