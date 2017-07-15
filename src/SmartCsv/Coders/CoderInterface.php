<?php

namespace ColbyGatte\SmartCsv\Coders;

interface CoderInterface
{
    /**
     * @param mixed $data
     *
     * @return mixed
     */
    public function encode($data);
    
    /**
     * @param mixed $data
     *
     * @return mixed
     */
    public function decode($data);
}