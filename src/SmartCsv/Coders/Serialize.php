<?php

namespace ColbyGatte\SmartCsv\Coders;

class Serialize implements CoderInterface
{
    /**
     * @param $data
     *
     * @return string
     */
    public static function encode($data)
    {
        return serialize($data);
    }

    /**
     * @param $data
     *
     * @return mixed
     */
    public static function decode($data)
    {
        return unserialize($data);
    }
}