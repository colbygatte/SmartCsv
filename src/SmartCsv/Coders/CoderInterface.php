<?php

namespace ColbyGatte\SmartCsv\Coders;

interface CoderInterface
{
    public static function encode($data);

    public static function decode($data);
}