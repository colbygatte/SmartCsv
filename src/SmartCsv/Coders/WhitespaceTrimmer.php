<?php

namespace ColbyGatte\SmartCsv\Coders;

class WhitespaceTrimmer implements CoderInterface
{
    /**
     * @param $data
     *
     * @return string
     */
    public static function encode($data)
    {
        return $data;
    }
    
    /**
     * @param string $data
     *
     * @return mixed
     */
    public static function decode($data)
    {
        return trim($data);
    }
}