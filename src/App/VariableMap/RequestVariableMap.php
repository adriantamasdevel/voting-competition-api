<?php

namespace App\VariableMap;

use App\VariableMap;
use Symfony\Component\HttpFoundation\Request;


class RequestVariableMap implements VariableMap
{
    /** @var Request  */
    private $request;

    private $formData = [];
    
    public function __construct(Request $serverRequest)
    {
        $this->request = $serverRequest;

        // This reads in data from the form body
        parse_str($serverRequest->getContent(), $this->formData);
    }

    public function getVariable($variableName, $default = false, $minimum = false, $maximum = false)
    {
        $value = $default;

        if (array_key_exists($variableName, $this->formData) === true) {
            $value = $this->formData[$variableName];
        }
        else if ($this->request->query->has($variableName) === true) {
            $value = $this->request->query->get($variableName);
        }
        else if ($this->request->request->has($variableName) === true) {
            $value = $this->request->request->get($variableName);
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
