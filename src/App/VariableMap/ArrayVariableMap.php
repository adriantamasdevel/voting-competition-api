<?php

namespace App\VariableMap;

use App\VariableMap;


class ArrayVariableMap implements VariableMap
{
    /** @var array */
    private $variables;
    
    public function __construct(array $variables)
    {
        $this->variables = $variables;
    }

    public function getVariable($variableName, $default = false, $minimum = false, $maximum = false)
    {
        $value = $default;

        if (array_key_exists($variableName, $this->variables) === true) {
            $value = $this->variables[$variableName];
        }
        if ($minimum !== false) {
            if ($value < $minimum) {
                $value = $minimum;
            }
        }

        if ($maximum !== false) {
            if ($value > $maximum) {
                $value = $maximum;
            }
        }

        return $value;
    }
}
