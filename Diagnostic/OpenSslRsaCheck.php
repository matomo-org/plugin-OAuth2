<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\OAuth2\Diagnostic;

use Piwik\Plugins\Diagnostics\Diagnostic\Diagnostic;
use Piwik\Plugins\Diagnostics\Diagnostic\DiagnosticResult;

class OpenSslRsaCheck implements Diagnostic
{
    public function execute()
    {
        $missing = [];

        if (!defined('OPENSSL_KEYTYPE_RSA')) {
            $missing[] = 'OPENSSL_KEYTYPE_RSA';
        }
        if (!function_exists('openssl_pkey_new')) {
            $missing[] = 'openssl_pkey_new()';
        }
        if (!function_exists('openssl_pkey_export')) {
            $missing[] = 'openssl_pkey_export()';
        }
        if (!function_exists('openssl_pkey_get_details')) {
            $missing[] = 'openssl_pkey_get_details()';
        }

        $label = 'OAuth2 OpenSSL RSA support';

        if (!empty($missing)) {
            $comment = 'Missing OpenSSL RSA constant/function(s): ' . implode(', ', $missing)
                . '. Enable the PHP OpenSSL extension to generate OAuth2 RSA keys.';
            return [DiagnosticResult::singleResult($label, DiagnosticResult::STATUS_ERROR, $comment)];
        }

        $comment = 'Required OpenSSL RSA constant and functions are available.';
        return [DiagnosticResult::singleResult($label, DiagnosticResult::STATUS_OK, $comment)];
    }
}
