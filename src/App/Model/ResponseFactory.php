<?php

namespace App\Model;

use App\Auth\Auth;
use App\Exception\AuthenticationRequiredException;
use App\Model\Entity\Competition;
use App\Model\Entity\ImageEntry;
use App\Model\Entity\ImageEntryWithScore;
use App\Pagination\StandardPagination;
use Symfony\Component\HttpFoundation\JsonResponse;

class ResponseFactory
{
    private $includeRestrictedData;

    private $imageBaseUrl;

    private $imageWidth;

    public function __construct(Auth $auth, $imageBaseUrl, $imageWidth)
    {
        $this->includeRestrictedData = $auth->isAllowed(Auth::IMAGE_ENTRY_VIEW_USER_INFO);
        $this->imageBaseUrl = $imageBaseUrl;
        $this->imageWidth = $imageWidth;

    }

    public function create($data, StandardPagination $pagination = null, $imageWidth = null)
    {
        if($imageWidth != null) {
            $this->imageWidth = $imageWidth;
        }

        $formattedData = [];

        foreach ($data as $key => $value) {
            $formattedData['data'][$key] = $this->formatValue($value);
        }

        if ($pagination !== null) {
            $formattedData['pagination'] = $pagination->toArray();
        }

        return new JsonResponse($formattedData, 200);
    }

    private function formatObject($value)
    {
        if ($value instanceof Competition) {
            return $value->toArray();
        }
        else if ($value instanceof \App\Model\CompetitionStats) {

            if ($this->includeRestrictedData == false) {
                throw new AuthenticationRequiredException("Access to CompetitionStats restricted.");
            }

            return $value->toArray();
        }
        else if ($value instanceof ImageEntry) {
            if ($this->includeRestrictedData == false) {
                if ($value->getStatus() !== ImageEntry::STATUS_VERIFIED) {
                    throw new AuthenticationRequiredException("Access to image restricted.");
                }
            }

            return $value->toArray($this->includeRestrictedData, $this->imageBaseUrl, $this->imageWidth);
        }
        else if ($value instanceof ImageEntryWithScore) {
            return $value->toArray($this->includeRestrictedData, $this->imageBaseUrl, $this->imageWidth);
        }
        else {
            throw new \Exception("Don't know how to format object of type ".get_class($value));
        }
    }

    private function formatValue($value)
    {
        if (is_object($value)) {
            return $this->formatObject($value);
        }
        else if (is_array($value)) {
            $formatted = [];
            foreach ($value as $valueEntry) {
                $formatted[] = $this->formatValue($valueEntry);
            }
            return $formatted;
        }
        else {
            return $value;  //Must be scalar
        }
    }
}
