<?php

namespace App\Auth;

use App\Exception\AuthenticationRequiredException;

class AnonAuth implements Auth
{
    public function isAllowed($resourceAction)
    {
        return false;
    }

    public function checkAllowed($resourceAction)
    {
        if ($this->isAllowed($resourceAction) == false) {
            throw new AuthenticationRequiredException("Access to $resourceAction is restricted");
        }
    }
}
