<?php

namespace ColbyGatte\SmartCsv\Coders;

class Serialize implements CoderInterface
{
    /**
     * @param $data
     *
     * @return string
     */
    public function encode($data)
    {
        return serialize($data);
    }
    
    /**
     * @param $data
     *
     * @return mixed
     */
    public function decode($data)
    {
        return unserialize($data);
    }
}