<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

use Isolated\Symfony\Component\Finder\Finder;

$dependenciesToPrefix = json_decode(getenv('MATOMO_DEPENDENCIES_TO_PREFIX'), true);
$namespacesToPrefix = json_decode(getenv('MATOMO_NAMESPACES_TO_PREFIX'), true);
$isRenamingReferences = getenv('MATOMO_RENAME_REFERENCES') == 1;
$pluginName = getenv('MATOMO_PLUGIN');

$namespacesToExclude = [];
$forceNoGlobalAlias = false;

if ($isRenamingReferences) {
    $finders = [
        Finder::create()
            ->files()
            ->in(__DIR__)
            ->exclude('vendor')
            ->exclude('node_modules')
            ->exclude('lang')
            ->exclude('javascripts')
            ->exclude('vue')
            ->notName('scoper.inc.php')
            ->filter(function (\SplFileInfo $file) {
                return !($file->isLink() && $file->isDir());
            })
            ->filter(function (\SplFileInfo $file) {
                return !($file->isLink() && !$file->getRealPath());
            }),
    ];
} else {
    $finders = array_map(function ($dependency) {
        return Finder::create()
            ->files()
            ->in($dependency);
    }, $dependenciesToPrefix);
}

$namespacesToIncludeRegexes = array_map(function ($n) {
    $n = rtrim($n, '\\');
    return '/^' . preg_quote($n) . '(?:\\\\|$)/';
}, $namespacesToPrefix);

return [
    'expose-global-constants' => false,
    'expose-global-classes' => false,
    'expose-global-functions' => false,
    'force-no-global-alias' => $forceNoGlobalAlias,
    'prefix' => 'Matomo\\Dependencies\\' . $pluginName,
    'finders' => $finders,
    'patchers' => [
        static function (string $filePath, string $prefix, string $content): string {
            if (!str_ends_with(str_replace('\\', '/', $filePath), '/league/uri/Uri.php')) {
                return $content;
            }

            // Keep these return types as `self` before Rector runs. Otherwise
            // the downgrade step can remove the original `static` return types
            // here while the scoped interfaces still require a compatible type.
            return str_replace(
                [
                    'public function withScheme($scheme) : static',
                    'public function withUserInfo($user, #[SensitiveParameter] $password = null) : static',
                    'public function withUsername($user) : static',
                    'public function withPassword(#[SensitiveParameter] $password) : static',
                    'public function withHost($host) : static',
                    'public function withPort(?int $port) : static',
                    'public function withPath($path) : static',
                    'public function withQuery($query) : static',
                    'public function withFragment($fragment) : static',
                ],
                [
                    'public function withScheme($scheme) : self',
                    'public function withUserInfo($user, #[SensitiveParameter] $password = null) : self',
                    'public function withUsername($user) : self',
                    'public function withPassword(#[SensitiveParameter] $password) : self',
                    'public function withHost($host) : self',
                    'public function withPort(?int $port) : self',
                    'public function withPath($path) : self',
                    'public function withQuery($query) : self',
                    'public function withFragment($fragment) : self',
                ],
                $content
            );
        },
    ],
    'include-namespaces' => $namespacesToIncludeRegexes,
    'exclude-namespaces' => $namespacesToExclude,
    'exclude-constants' => [
        'PIWIK_TEST_MODE',
        '/^self::/', // work around php-scoper bug
    ],
    'exclude-functions' => ['Piwik_ShouldPrintBackTraceWithMessage'],
];
