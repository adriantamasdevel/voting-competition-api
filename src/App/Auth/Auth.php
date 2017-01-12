<?php

namespace App\Auth;

interface Auth
{
    const COMPETITION_CREATE = 'COMPETITION_CREATE';

    const COMPETITION_UPDATE = 'COMPETITION_UPDATE';

    const IMAGE_ENTRY_UPDATE = 'IMAGE_ENTRY_UPDATE';

    const IMAGE_ENTRY_RANDOMIZE_ORDER = 'IMAGE_ENTRY_RANDOMIZE_ORDER';

    const IMAGE_ENTRY_VIEW_UNMODERATED = 'IMAGE_ENTRY_VIEW_UNMODERATED';

    const IMAGE_ENTRY_VIEW_USER_INFO = 'IMAGE_ENTRY_VIEW_USER_INFO';

    public function checkAllowed($resourceAction);

    public function isAllowed($resourceAction);
}
