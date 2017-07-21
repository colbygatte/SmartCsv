<?php

namespace ColbyGatte\SmartCsv;

class Utilities
{
    static public function throwElementNotUniqueException($elementsToCheck, $message)
    {
        $counts = [];
    
        foreach ($elementsToCheck as $element) {
            if (! isset($counts[$element])) {
                $counts[$element] = 0;
            }
        
            $counts[$element]++;
        }
    
        $more = array_flip(array_filter($counts, function ($value) {
            return $value > 1;
        }));
    
        throw new Exception(sprintf($message, implode(', ', $more)));
    }
}