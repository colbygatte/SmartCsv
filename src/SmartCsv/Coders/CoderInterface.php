<?php

namespace ColbyGatte\SmartCsv\Coders;

interface CoderInterface
{
    /**
     * @param mixed $data
     *
     * @return mixed
     */
    public static function encode($data);
    
    /**
     * @param mixed $data
     *
     * @return mixed
     */
    public static function decode($data);
}