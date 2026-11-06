<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\OAuth2\Diagnostic;

use Piwik\Common;
use Piwik\Http;
use Piwik\Option;
use Piwik\Plugins\Diagnostics\Diagnostic\Diagnostic;
use Piwik\Plugins\Diagnostics\Diagnostic\DiagnosticResult;
use Piwik\Plugins\OAuth2\Service\AuthorizationServerMetadata;
use Piwik\Plugins\OAuth2\Service\DiscoveryFile;
use Piwik\SettingsPiwik;
use Piwik\SettingsServer;
use Piwik\Translation\Translator;

/**
 * Checks that clients can discover the authorization server: requests the RFC 8414 discovery URL
 * of this Matomo and compares the document served there with the current one.
 */
class DiscoveryEndpointCheck implements Diagnostic
{
    public function __construct(
        private DiscoveryFile $discoveryFile,
        private AuthorizationServerMetadata $metadata,
        private Translator $translator
    ) {
    }

    public function execute()
    {
        $issuer = $this->discoveryFile->getIssuer();
        if ($issuer === null) {
            return [DiagnosticResult::singleResult(
                $this->getLabel(),
                DiagnosticResult::STATUS_INFORMATIONAL,
                $this->translator->translate('OAuth2_DiscoveryCheckUrlUnknown')
            )];
        }

        if (!DiscoveryFile::isHttps($issuer)) {
            // Matomo stores the URL of the last request it dispatched, so this is also the case
            // after a single request over http on an install that is reachable over https.
            return [DiagnosticResult::singleResult(
                $this->getLabel(),
                DiagnosticResult::STATUS_WARNING,
                $this->translator->translate('OAuth2_DiscoveryCheckUrlNotHttps', [
                    htmlspecialchars($issuer),
                    '<code>force_ssl = 1</code>',
                    '<code>[General]</code>',
                    'config/config.ini.php',
                ])
            )];
        }

        $url = self::getDiscoveryUrl($issuer);

        try {
            $response = $this->fetchKeepingMatomoUrl($url);
        } catch (\Exception $e) {
            return [$this->warning($url, $issuer, $this->translator->translate('OAuth2_DiscoveryCheckRequestFailed', htmlspecialchars($e->getMessage())))];
        }

        if ($response['status'] !== 200) {
            return [$this->warning($url, $issuer, $this->translator->translate('OAuth2_DiscoveryCheckHttpStatus', (string) $response['status']))];
        }

        $document = json_decode($response['data'], true);
        if (!is_array($document)) {
            return [$this->warning($url, $issuer, $this->translator->translate('OAuth2_DiscoveryCheckNotJson'))];
        }

        if (($document['issuer'] ?? null) !== $issuer) {
            return [$this->warning($url, $issuer, $this->translator->translate('OAuth2_DiscoveryCheckIssuerMismatch', [
                htmlspecialchars(is_string($document['issuer'] ?? null) ? $document['issuer'] : ''),
                htmlspecialchars($issuer),
            ]))];
        }

        try {
            // Compared as written to the file, where e.g. 1.0 becomes 1 and an empty object an array.
            $currentDocument = json_decode(DiscoveryFile::encode($this->metadata->build($issuer)), true);
        } catch (\Throwable $e) {
            // E.g. a listener of OAuth2.authorizationServerMetadata fails, which also keeps the
            // file from being written.
            return [$this->warning($url, $issuer, $this->translator->translate('OAuth2_DiscoveryCheckBuildFailed', htmlspecialchars($e->getMessage())))];
        }

        if ($document !== $currentDocument) {
            return [$this->warning(
                $url,
                $issuer,
                $this->translator->translate('OAuth2_DiscoveryCheckOutdated', [
                    DiscoveryFile::DIRECTORY . '/' . DiscoveryFile::FILE_NAME,
                    '<code>./console oauth2:write-discovery-file</code>',
                    '<code>--force</code>',
                ])
            )];
        }

        // Some clients reject the document without a JSON content type. Only the static file can be
        // served with another one, as Matomo always sends JSON. Subdirectory installs have no static
        // file, so their document comes from elsewhere, and forwarding the path to Matomo fixes it.
        $contentType = strtolower(trim(explode(';', $response['contentType'])[0]));
        if ($contentType !== 'application/json') {
            $path = rtrim((string) parse_url($issuer, PHP_URL_PATH), '/');

            return [DiagnosticResult::singleResult(
                $this->getLabel(),
                DiagnosticResult::STATUS_WARNING,
                $this->translator->translate('OAuth2_DiscoveryCheckNotSentAsJson', [
                    htmlspecialchars($url),
                    htmlspecialchars($contentType),
                    'application/json',
                ]) . '<br><br>' . ($path === '' ? $this->getContentTypeRule() : $this->getWebServerRule($path))
            )];
        }

        return [DiagnosticResult::singleResult(
            $this->getLabel(),
            DiagnosticResult::STATUS_OK,
            $this->translator->translate('OAuth2_DiscoveryCheckOk', htmlspecialchars($url))
        )];
    }

    /**
     * Returns the discovery URL for the given issuer. RFC 8414 (section 3.1) inserts the
     * well-known segment between the host and the issuer's path component.
     */
    public static function getDiscoveryUrl(string $issuer): string
    {
        return self::getOrigin($issuer) . AuthorizationServerMetadata::WELL_KNOWN_PATH
            . rtrim((string) parse_url($issuer, PHP_URL_PATH), '/');
    }

    private static function getOrigin(string $issuer): string
    {
        $parts = parse_url($issuer);

        return ($parts['scheme'] ?? 'https') . '://' . ($parts['host'] ?? '')
            . (isset($parts['port']) ? ':' . $parts['port'] : '');
    }

    /**
     * Matomo stores the URL of each request it dispatches as its own URL. When a web server rule
     * forwards the discovery URL to Matomo, that is <host>/.well-known/, so the stored URL is put
     * back after the check's own request.
     *
     * @return array{status: int, data: string, contentType: string}
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
     * @return array{status: int, data: string, contentType: string}
     */
    protected function fetch(string $url): array
    {
        // With $getExtendedInfo the response is always this array, which Matomo 5 doesn't declare yet.
        /** @var array{status: int|null, headers?: array|null, data?: string|null} $response */
        $response = Http::sendHttpRequest($url, 5, null, null, 0, false, false, true);
        $contentType = $response['headers']['Content-Type'] ?? '';

        return [
            'status' => (int) $response['status'],
            'data' => $response['data'] ?? '',
            'contentType' => is_string($contentType) ? $contentType : '',
        ];
    }

    protected function isConsole(): bool
    {
        return Common::isPhpCliMode();
    }

    protected function getServerSoftware(): string
    {
        $serverSoftware = $_SERVER['SERVER_SOFTWARE'] ?? '';

        return is_string($serverSoftware) ? strtolower($serverSoftware) : '';
    }

    private function warning(string $url, string $issuer, string $reason): DiagnosticResult
    {
        $path = rtrim((string) parse_url($issuer, PHP_URL_PATH), '/');

        $comment = $this->translator->translate('OAuth2_DiscoveryCheckNotDiscoverable', htmlspecialchars($url))
            . ' ' . $reason . '<br><br>';

        if ($path === '' && !$this->discoveryFile->isEnabled()) {
            $comment .= $this->translator->translate(
                'OAuth2_DiscoveryCheckFileTurnedOff',
                '<code>[OAuth2] write_discovery_file = 0</code>'
            );
        } elseif ($path === '') {
            $comment .= $this->translator->translate('OAuth2_DiscoveryCheckWriteFileOrForward', [
                '<code>./console oauth2:write-discovery-file</code>',
                '<code>--force</code>',
            ]);
        } else {
            $comment .= $this->translator->translate('OAuth2_DiscoveryCheckSubdirectory');
        }

        $comment .= '<br><br>' . $this->getWebServerRule($path)
            . '<br><br>' . $this->translator->translate(
                'OAuth2_DiscoveryCheckForwardingChangesMatomoUrl',
                htmlspecialchars(self::getOrigin($issuer) . '/' . DiscoveryFile::DIRECTORY . '/')
            )
            . '<br><br>' . $this->translator->translate('OAuth2_DiscoveryCheckIgnoreIfReachable');

        return DiagnosticResult::singleResult($this->getLabel(), DiagnosticResult::STATUS_WARNING, $comment);
    }

    private function getLabel(): string
    {
        return $this->translator->translate('OAuth2_DiscoveryEndpointCheck');
    }

    private function getWebServerRule(string $path): string
    {
        $pattern = str_replace('.', '\\.', ltrim(AuthorizationServerMetadata::WELL_KNOWN_PATH, '/')) . preg_quote($path) . '$';
        $target = $path . '/index.php?module=OAuth2&action=metadata';
        $server = $this->getServerSoftware();

        if (SettingsServer::isIIS() || strpos($server, 'iis') !== false) {
            $rule = [
                $this->translator->translate('OAuth2_DiscoveryCheckIisRewriteRule', ['<rules>', 'web.config']),
                '<rule name="OAuth2 discovery" stopProcessing="true">',
                '    <match url="^' . $pattern . '" />',
                '    <action type="Rewrite" url="' . ltrim(str_replace('&', '&amp;', $target), '/') . '" />',
                '</rule>',
            ];
        } elseif (strpos($server, 'nginx') !== false) {
            $rule = [
                // nginx refuses to load two blocks for the same path, so this one has to replace the
                // block from getContentTypeRule() if that was added before. That is only ever
                // suggested for root installs.
                $this->translator->translate($path === '' ? 'OAuth2_DiscoveryCheckNginxRewriteRule' : 'OAuth2_DiscoveryCheckNginxServerBlock'),
                'location = ' . AuthorizationServerMetadata::WELL_KNOWN_PATH . $path . ' {',
                '    rewrite ^ ' . $target . ' last;',
                '}',
            ];
        } else {
            $rule = [
                $this->translator->translate('OAuth2_DiscoveryCheckApacheRewriteRule', '.htaccess'),
                'RewriteEngine On',
                // In a virtual host the path keeps its leading slash, in .htaccess it doesn't.
                'RewriteRule ^/?' . $pattern . ' ' . $target . ' [L,QSA]',
            ];
        }

        return $this->formatRule($rule);
    }

    /**
     * The generated .htaccess and web.config set the content type, but nginx ignores both, and
     * Apache ignores the .htaccess with AllowOverride None. The virtual host rule works regardless.
     */
    private function getContentTypeRule(): string
    {
        $server = $this->getServerSoftware();
        $path = AuthorizationServerMetadata::WELL_KNOWN_PATH;

        if (SettingsServer::isIIS() || strpos($server, 'iis') !== false) {
            $rule = [
                $this->translator->translate('OAuth2_DiscoveryCheckIisContentType', 'web.config'),
                '<location path="' . ltrim($path, '/') . '">',
                '    <system.webServer>',
                '        <staticContent>',
                '            <remove fileExtension="." />',
                '            <mimeMap fileExtension="." mimeType="application/json" />',
                '        </staticContent>',
                '    </system.webServer>',
                '</location>',
            ];
        } elseif (strpos($server, 'nginx') !== false) {
            $rule = [
                $this->translator->translate('OAuth2_DiscoveryCheckNginxServerBlock'),
                'location = ' . $path . ' {',
                '    default_type application/json;',
                '}',
            ];
        } else {
            $rule = [
                $this->translator->translate('OAuth2_DiscoveryCheckApacheVirtualHost'),
                '<Location "' . $path . '">',
                '    ForceType application/json',
                '</Location>',
            ];
        }

        return $this->formatRule($rule);
    }

    /**
     * The System Check strips <pre>, so the indentation is kept with non-breaking spaces.
     *
     * The console (diagnostics:run) strips tags without decoding entities, which would break the
     * rule when copied, and cannot tell the web server, so it points to the System Check instead.
     *
     * @param string[] $lines
     */
    private function formatRule(array $lines): string
    {
        if ($this->isConsole()) {
            return $this->translator->translate('OAuth2_DiscoveryCheckSeeSystemCheck');
        }

        $lines = array_map(static function (string $line): string {
            $indentation = strlen($line) - strlen(ltrim($line, ' '));

            return str_repeat('&nbsp;', $indentation) . htmlspecialchars(ltrim($line, ' '));
        }, $lines);

        return '<code>' . implode('<br>', $lines) . '</code>';
    }
}
