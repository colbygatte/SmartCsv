<?php

namespace ColbyGatte\SmartCsv;

class Utilities
{
    /**
     * @param array  $elementsToCheck
     * @param string $message
     *
     * @throws \ColbyGatte\SmartCsv\Exception
     */
    static public function throwElementNotUniqueException($elementsToCheck, $message)
    {
        $notUnique = array_flip(array_filter(
                array_count_values($elementsToCheck),

                function ($count) {
                    return $count > 1;
                })
        );

        $message = sprintf($message, implode(', ', $notUnique));

        throw new Exception($message);
    }
}