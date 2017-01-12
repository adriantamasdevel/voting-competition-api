<?php

namespace App\Model\Entity;

// For reference
// http://williamdurand.fr/2014/02/14/please-do-not-patch-like-an-idiot/
//
// https://tools.ietf.org/html/rfc5789
// https://tools.ietf.org/html/rfc6902

class ImageEntryPatch
{
    public $firstName;
    public $lastName;
    public $email;
    public $description;
    public $status;

    public function containsUpdate()
    {
        if ($this->firstName == null &&
            $this->lastName == null &&
            $this->email == null &&
            $this->description == null &&
            $this->status == null) {
            return false;
        }
        return true;
    }
}
