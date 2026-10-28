<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\OAuth2\Diagnostic;

use Piwik\Http;
use Piwik\Option;
use Piwik\Plugins\Diagnostics\Diagnostic\Diagnostic;
use Piwik\Plugins\Diagnostics\Diagnostic\DiagnosticResult;
use Piwik\Plugins\OAuth2\Service\AuthorizationServerMetadata;
use Piwik\Plugins\OAuth2\Service\DiscoveryFile;
use Piwik\SettingsPiwik;
use Piwik\SettingsServer;

/**
 * Checks that clients can discover the authorization server: requests the RFC 8414 discovery URL
 * of this Matomo and compares the document served there with the current one.
 */
class DiscoveryEndpointCheck implements Diagnostic
{
    private const LABEL = 'OAuth 2.0 discovery endpoint';

    public function __construct(
        private DiscoveryFile $discoveryFile,
        private AuthorizationServerMetadata $metadata
    ) {
    }

    public function execute()
    {
        $issuer = $this->discoveryFile->getIssuer();
        if ($issuer === null) {
            return [DiagnosticResult::singleResult(
                self::LABEL,
                DiagnosticResult::STATUS_INFORMATIONAL,
                'The Matomo URL is not known yet, so the discovery endpoint could not be checked.'
            )];
        }

        $url = self::getDiscoveryUrl($issuer);

        try {
            $response = $this->fetchKeepingMatomoUrl($url);
        } catch (\Exception $e) {
            return [$this->warning($url, $issuer, 'Matomo could not request it: ' . htmlspecialchars($e->getMessage()) . '.')];
        }

        if ($response['status'] !== 200) {
            return [$this->warning($url, $issuer, 'It responded with HTTP status ' . $response['status'] . '.')];
        }

        $document = json_decode($response['data'], true);
        if (!is_array($document)) {
            return [$this->warning($url, $issuer, 'It did not return a JSON document.')];
        }

        if (($document['issuer'] ?? null) !== $issuer) {
            return [$this->warning($url, $issuer, 'It returned the issuer "' . htmlspecialchars(is_string($document['issuer'] ?? null) ? $document['issuer'] : '')
                . '" instead of "' . htmlspecialchars($issuer) . '".')];
        }

        if ($document != $this->metadata->build($issuer)) {
            return [$this->warning(
                $url,
                $issuer,
                'It returned an outdated document. If ' . DiscoveryFile::DIRECTORY . '/' . DiscoveryFile::FILE_NAME
                . ' exists in the Matomo directory, run <code>./console oauth2:write-discovery-file</code> to update it.'
            )];
        }

        return [DiagnosticResult::singleResult(
            self::LABEL,
            DiagnosticResult::STATUS_OK,
            'OAuth 2.0 clients can discover this authorization server at ' . htmlspecialchars($url) . '.'
        )];
    }

    /**
     * Returns the discovery URL for the given issuer. RFC 8414 (section 3.1) inserts the
     * well-known segment between the host and the issuer's path component.
     */
    public static function getDiscoveryUrl(string $issuer): string
    {
        $parts = parse_url($issuer);
        $origin = ($parts['scheme'] ?? 'https') . '://' . ($parts['host'] ?? '')
            . (isset($parts['port']) ? ':' . $parts['port'] : '');

        return $origin . AuthorizationServerMetadata::WELL_KNOWN_PATH . rtrim($parts['path'] ?? '', '/');
    }

    /**
     * Matomo stores the URL of each request it dispatches as its own URL. When a web server rule
     * forwards the discovery URL to Matomo, that is <host>/.well-known/, so the stored URL is put
     * back after the check's own request.
     *
     * @return array{status: int, data: string}
     */
    private function fetchKeepingMatomoUrl(string $url): array
    {
        $matomoUrl = Option::get(SettingsPiwik::OPTION_PIWIK_URL);

        try {
            return $this->fetch($url);
        } finally {
            Option::clearCachedOption(SettingsPiwik::OPTION_PIWIK_URL);
            if ($matomoUrl && Option::get(SettingsPiwik::OPTION_PIWIK_URL) !== $matomoUrl) {
                Option::set(SettingsPiwik::OPTION_PIWIK_URL, $matomoUrl, 1);
            }
        }
    }

    /**
     * @return array{status: int, data: string}
     */
    protected function fetch(string $url): array
    {
        $response = Http::sendHttpRequest($url, 5, null, null, 0, false, false, true);

        return ['status' => (int) $response['status'], 'data' => $response['data'] ?? ''];
    }

    protected function getServerSoftware(): string
    {
        $serverSoftware = $_SERVER['SERVER_SOFTWARE'] ?? '';

        return is_string($serverSoftware) ? strtolower($serverSoftware) : '';
    }

    private function warning(string $url, string $issuer, string $reason): DiagnosticResult
    {
        $path = rtrim((string) parse_url($issuer, PHP_URL_PATH), '/');

        $comment = 'OAuth 2.0 clients such as MCP integrations cannot discover this authorization server at '
            . htmlspecialchars($url) . '. ' . $reason . '<br><br>';

        if ($path === '') {
            $comment .= 'Make sure the Matomo directory is writable and run <code>./console oauth2:write-discovery-file</code>, '
                . 'or forward the discovery URL to Matomo in your web server configuration';
        } else {
            $comment .= 'As Matomo is installed in a subdirectory, the discovery URL is at the domain root. '
                . 'Forward it to Matomo in the web server configuration of the domain';
        }

        $comment .= ':<br><br>' . $this->getWebServerRule($path)
            . '<br><br>If clients can reach the discovery URL but Matomo cannot request itself, you can ignore this warning.';

        return DiagnosticResult::singleResult(self::LABEL, DiagnosticResult::STATUS_WARNING, $comment);
    }

    private function getWebServerRule(string $path): string
    {
        $pattern = '^' . str_replace('.', '\\.', ltrim(AuthorizationServerMetadata::WELL_KNOWN_PATH, '/') . $path) . '$';
        $target = $path . '/index.php?module=OAuth2&action=metadata';
        $server = $this->getServerSoftware();

        if (SettingsServer::isIIS() || strpos($server, 'iis') !== false) {
            $rule = [
                'IIS (URL Rewrite module, inside <rules> of web.config):',
                '<rule name="OAuth2 discovery" stopProcessing="true">',
                '    <match url="' . $pattern . '" />',
                '    <action type="Rewrite" url="' . ltrim(str_replace('&', '&amp;', $target), '/') . '" />',
                '</rule>',
            ];
        } elseif (strpos($server, 'nginx') !== false) {
            $rule = [
                'nginx (inside the server block):',
                'location = ' . AuthorizationServerMetadata::WELL_KNOWN_PATH . $path . ' {',
                '    rewrite ^ ' . $target . ' last;',
                '}',
            ];
        } else {
            $rule = [
                'Apache (.htaccess in the domain root, or the virtual host):',
                'RewriteEngine On',
                'RewriteRule ' . $pattern . ' ' . $target . ' [L,QSA]',
            ];
        }

        return '<code>' . implode('<br>', array_map('htmlspecialchars', $rule)) . '</code>';
    }
}
