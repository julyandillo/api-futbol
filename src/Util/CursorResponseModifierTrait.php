<?php

namespace App\Util;

use App\ApiCursor\ApiCursor;
use App\ApiCursor\ApiCursorBuilder;

trait CursorResponseModifierTrait
{
    public function addNextPageToResponse(array &$response, ApiCursor $cursor): void
    {
        if (($nextPage = $cursor->getNextPage()) !== ApiCursor::LAST_PAGE) {
            $response[ApiCursorBuilder::CURSOR_PARAMETER_NAME] = $nextPage;
        }
    }
}