<?php

namespace ColbyGatte\SmartCsv\Coders;

class WhitespaceTrimmer implements CoderInterface
{
    /**
     * @param $data
     *
     * @return string
     */
    public function encode($data)
    {
        return $data;
    }
    
    /**
     * @param string $data
     *
     * @return mixed
     */
    public function decode($data)
    {
        return trim($data);
    }
}