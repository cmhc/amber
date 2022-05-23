<?php
/**
 * 分词器
 */
namespace amber\modules;

class Tokenizer
{
    public function __construct($ip, $port)
    {
        $this->api = sprintf('http://%s:%d', $ip, $port);
    }

    /**
     * 获取权重词
     * @param  string $word
     * @return  array
     */
    public function weight($word)
    {
        $cutApi = $this->api . '/weight';
        $words = Http::post($cutApi, array('wd' => $word));
        return json_decode($words, true);
    }

    /**
     * 获取权重词
     * @param  string $word
     * @return  array
     */
    public function cut($word)
    {
        $cutApi = $this->api . '/cut';
        $words = Http::post($cutApi, array('wd' => $word));
        return json_decode($words, true);
    }
}