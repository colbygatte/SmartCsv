<?php

namespace ColbyGatte\SmartCsv;

class Utilities
{
    /**
     * @param array $elementsToCheck
     * @param string $message
     *
     * @throws \ColbyGatte\SmartCsv\Exception
     */
    static public function throwElementNotUniqueException($elementsToCheck, $message)
    {
        $message = sprintf($message, implode(', ', array_flip(array_filter(
                array_count_values($elementsToCheck),
                function ($value) {
                    return $value > 1;
                })
        )));
        
        throw new Exception($message);
    }
}