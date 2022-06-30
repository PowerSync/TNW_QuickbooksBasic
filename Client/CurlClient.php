<?php
/**
 * Copyright © 2016 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\QuickbooksBasic\Client;

use OAuth\Common\Http\Client\CurlClient as BaseClient;

/**
 * Class CurlClient
 */
class CurlClient extends BaseClient
{
    /**
     * @param array $headers
     */
    public function normalizeHeaders(&$headers)
    {
        $normalizedHeaders = [];
        foreach ($headers as $key => $value) {
            $normalizedHeaders[ucfirst(strtolower($key))] = ucfirst(strtolower($key)) . ': ' . $value;
        }
        $headers = $normalizedHeaders;
    }
}
