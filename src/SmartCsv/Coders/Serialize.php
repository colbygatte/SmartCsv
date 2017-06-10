<?php

namespace ColbyGatte\SmartCsv\Coders;

class Serialize implements CoderInterface
{
    public static function encode($data)
    {
        return serialize($data);
    }

    public static function decode($data)
    {
        return unserialize($data);
    }
}