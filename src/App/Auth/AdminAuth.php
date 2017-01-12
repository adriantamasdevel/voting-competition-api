<?php

namespace App\Auth;

use App\Exception\AuthenticationRequiredException;

class AdminAuth implements Auth
{
    public function isAllowed($resourceAction)
    {
        if ($resourceAction === Auth::COMPETITION_CREATE ||
            $resourceAction === Auth::COMPETITION_UPDATE ||
            $resourceAction === Auth::IMAGE_ENTRY_UPDATE ||
            $resourceAction === Auth::IMAGE_ENTRY_VIEW_UNMODERATED ||
            $resourceAction === Auth::IMAGE_ENTRY_VIEW_USER_INFO ||
            $resourceAction === Auth::IMAGE_ENTRY_RANDOMIZE_ORDER) {
            return true;
        }

        return false;
    }

    public function checkAllowed($resourceAction)
    {
        if ($this->isAllowed($resourceAction) == false) {
            throw new AuthenticationRequiredException("Access to $resourceAction is restricted");
        }
    }
}
