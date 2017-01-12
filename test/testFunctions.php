<?php

use JsonSchema\Validator as JsonValidator;

//function setExceptionErrorHandler() {
//
//    $errorHandler = function ($errorNumber, $errorMessage, $errorFile, $errorLine) {
//        if (error_reporting() === 0) {
//            // Error reporting has be silenced
//            return true;
//        }
//        if ($errorNumber === E_DEPRECATED) {
//            return true; //Don't care - deprecated warnings are generally not useful
//        }
//
//        if ($errorNumber === E_CORE_ERROR || $errorNumber === E_ERROR) {
//            // For these two types, PHP is shutting down anyway. Return false
//            // to allow shutdown to continue
//            return false;
//        }
//
//        $message = "Error: [$errorNumber] $errorMessage in file $errorFile on line $errorLine<br />\n";
//        throw new \Exception($message);
//    };
//
//    set_error_handler($errorHandler);
//}

date_default_timezone_set('UTC');
error_reporting(E_ALL);
setExceptionErrorHandler();

function json_decode_real($jsonData, $asArray = true)
{
    $data = @json_decode($jsonData, $asArray);
    if (($lastError = json_last_error()) !== JSON_ERROR_NONE) {
        $knownErrors = [
            JSON_ERROR_DEPTH => 'Maximum stack depth exceeded',
            JSON_ERROR_STATE_MISMATCH => 'Underflow or the modes mismatch',
            JSON_ERROR_CTRL_CHAR => 'Unexpected control character found',
            JSON_ERROR_SYNTAX => 'Syntax error, malformed JSON',
            JSON_ERROR_UTF8 => 'Malformed UTF-8 characters, possibly incorrectly encoded',
        ];

        $message = 'Unrecognised error decoding JSON';
        if (array_key_exists($lastError, $knownErrors) == true) {
            $message = $knownErrors[$lastError];
        }

        $message .= "JSON was : " . var_export($jsonData, true);

        throw new \Exception($message);
    }

    return $data;
}

function checkResponseAgainstSchema(\Amp\Artax\Response $response, $schemaName)
{
    $dataObject = json_decode_real($response->getBody(), false);
    checkJsonSchema($dataObject, $schemaName);
}

function checkJsonSchema($data, $schemaName)
{
    $uriResolver = new JsonSchema\Uri\UriResolver();

    $uriRetriever = new JsonSchema\Uri\UriRetriever();
    $refResolver = new JsonSchema\RefResolver($uriRetriever, $uriResolver);

    $path = __DIR__."/data/" . $schemaName;
    $fullPath = realpath($path);
    if ($fullPath === false) {
        throw new \Exception("Failed to read schema file $schemaName, from path $path");
    }

    try {
        $schema = $refResolver->resolve("file://".$fullPath);
    }
    catch (\Exception $e) {
        throw new \Exception(
            "Failed to resolve JSON schema with path $fullPath : " . $e->getMessage(),
            $e->getCode(),
            $e
        );
    }

    try {
        $validator = new JsonValidator();
        $validator->check($data, $schema);

        if ($validator->isValid()) {
            //echo "The supplied JSON validates against the schema.\n";
        }
        else {
            $errors = ["JSON does not validate against schema $schemaName. Violations:"];
            foreach ($validator->getErrors() as $error) {
                $errors[] = sprintf("[%s] %s\n", $error['property'], $error['message']);
            }

            $errors[] = 'Raw json is:';
            $errors[] = json_encode($data);

            throw new \Exception(implode("\n", $errors));
        }
    }
    catch (\Exception $e) {
        $message = "Failed to validate JSON: " . $e->getMessage();
        $message .= "source data is: " . var_export($data, true);
        throw new Exception(
            $message,
            $e->getCode().
            $e
        );
    }

    return true;
}
