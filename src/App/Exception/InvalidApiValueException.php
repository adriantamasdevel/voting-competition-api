<?php

namespace App\Exception;

class InvalidApiValueException extends \Exception
{
    const M_VALUE_MISSING = "Required parameter '%s' not found.";
    const M_VALUE_ONLY_DIGITS = "Value for '%s' must only contain digits";
    const M_VALUE_TOO_MANY_DIGITS = "Value for '%s' too large, max number of digits is %d.";
    const M_VALUE_TOO_LARGE = "Value for '%s' too large, max value is %d.";
    const M_VALUE_TOO_LONG = "Value for '%s' too long, max length is %d.";
    const M_VALUE_TOO_SHORT = "Value for '%s' too short, min length is %d.";
    const M_INVALID_CHARS = 'Valid characters for %s are: %s.';
    const M_INVALID_UUID = 'Valid characters for %s are: [a-zA-Z0-9-].';
    const M_INVALID_BOOLEAN = "Boolean values must be a string of 'true' or 'false'";

    public static function onlyDigits($paramName)
    {
        $string = sprintf(self::M_VALUE_ONLY_DIGITS, $paramName);
        return new self($string);
    }

    public static function tooManyDigits($paramName, $maxDigits)
    {
        $string = sprintf(self::M_VALUE_TOO_MANY_DIGITS, $paramName, $maxDigits);
        return new self($string);
    }

    public static function tooLarge($paramName, $maxValue)
    {
        $string = sprintf(self::M_VALUE_TOO_LARGE, $paramName, $maxValue);
        return new self($string);
    }

    public static function tooLong($paramName, $maxLength)
    {
        $string = sprintf(self::M_VALUE_TOO_LONG, $paramName, $maxLength);
        return new self($string);
    }

    public static function invalidBoolean()
    {
        return new self(self::M_INVALID_BOOLEAN);
    }

    public static function tooShort($paramName, $minLength)
    {
        $string = sprintf(self::M_VALUE_TOO_SHORT, $paramName, $minLength);
        return new self($string);
    }

    public static function missing($paramName)
    {
        $string = sprintf(self::M_VALUE_MISSING, $paramName);
        return new self($string);
    }

    public static function invalidUuid($paramName)
    {
        $string = sprintf(self::M_INVALID_UUID, $paramName);
        return new self($string);
    }

    public static function validChars($paramName, $allowedValues)
    {
        $string = sprintf(self::M_INVALID_CHARS, $paramName, $allowedValues);

        return new self($string);
    }
}
