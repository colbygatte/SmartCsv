<?php

namespace ColbyGatte\SmartCsv\Coders;

class Trimmer implements CoderInterface
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