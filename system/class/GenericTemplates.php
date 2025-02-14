<?php

namespace Sunlight;

use Kuria\Debug\Exception;
use Sunlight\Util\StringManipulator;
use Sunlight\Util\UrlHelper;

abstract class GenericTemplates
{
    /**
     * Render a number
     */
    static function renderNumber($number, int $decimals = 2): string
    {
        if (is_int($number) || $decimals <= 0 || abs(fmod($number, 1)) < 0.1 ** $decimals) {
            // an integer value
            return number_format($number, 0, '', _lang('numbers.thousands_sep'));
        }

        // a float value
        return number_format($number, $decimals, _lang('numbers.dec_point'), _lang('numbers.thousands_sep'));
    }

    /**
     * Render a date value
     *
     * @param int $timestamp UNIX timestamp
     * @param string|null $type short description of usage type for plugins
     */
    static function renderDate(int $timestamp, ?string $type = null): string
    {
        return self::renderTimestamp('date_format', $timestamp, $type);
    }

    /**
     * Render a date-time value
     *
     * @param int $timestamp UNIX timestamp
     * @param string|null $type short description of usage type for plugins
     */
    static function renderTime(int $timestamp, ?string $type = null): string
    {
        return self::renderTimestamp('time_format', $timestamp, $type);
    }

    /**
     * Render a file size
     */
    static function renderFilesize(int $bytes): string
    {
        if ($bytes >= 1000000) {
            return self::renderNumber($bytes / 1000000) . ' MB';
        }

        return self::renderNumber(ceil($bytes / 1000)) . ' kB';
    }

    /**
     * Render an IP address
     */
    static function renderIp(string $ip): string
    {
        if (User::$group['id'] != User::ADMIN_GROUP_ID) {
            // only admins see the actual IP, anonymize IP for anyone else
            $ip = substr(hash_hmac('sha256', $ip, Core::$secret), 0, 16);
        }

        return _lang('posts.anonym') . '@' . _e($ip);
    }

    /**
     * Render the beginning of an HTML document
     */
    static function renderHead(): string
    {
        $lang = _e(_lang('code.iso639-1'));

        return <<<HTML
<!DOCTYPE html>
<html lang="{$lang}">
<head>
<meta charset="UTF-8">
<meta name="generator" content="SunLight CMS">

HTML;
    }

    /**
     * Render HTML to insert CSS and JS into <head>
     *
     * Parameters supported in$assets:
     * ---------------------------------------------------
     * meta             HTML inserted at the beginning
     * css              array with paths to CSS files
     * js               array with paths to JS files
     * css_before       HTML inserted before <link> tags
     * css_after        HTML inserted after <link> tags
     * js_before        HTML inserted before <script> tags
     * js_after         HTML inserted after <script> tags
     * extend_event     extend event name
     * favicon          true = link to favicon, false = no favicon, null = no output
     */
    static function renderHeadAssets(array $assets): string
    {
        $html = '';
        $cacheParam = '_' . Settings::get('cacheid');

        $assets += [
            'meta' => '',
            'css' => [],
            'js' => [],
            'css_before' => '',
            'css_after' => '',
            'js_before' => '',
            'js_after' => '',
            'favicon' => null,
        ];

        // extend
        if (isset($assets['extend_event'])) {
            Extend::call($assets['extend_event'], [
                'meta' => &$assets['meta'],
                'css' => &$assets['css'],
                'js' => &$assets['js'],
                'css_before' => &$assets['css_before'],
                'css_after' => &$assets['css_after'],
                'js_before' => &$assets['js_before'],
                'js_after' => &$assets['js_after'],
                'favicon' => &$assets['favicon'],
            ]);
        }

        // meta
        $html .= $assets['meta'];

        // css
        $html .= $assets['css_before'];

        foreach ($assets['css'] as $item) {
            $html .= "\n<link rel=\"stylesheet\" href=\"" . _e(UrlHelper::appendParams($item, $cacheParam)) . '" type="text/css">';
        }

        $html .= $assets['css_after'];

        // favicon
        if ($assets['favicon'] !== null) {
            $faviconPath = $assets['favicon']
                ? Router::path('favicon.ico') . '?' . $cacheParam
                : 'data:,';

            $html .= "\n<link rel=\"icon\" href=\"" . _e($faviconPath) . '">';
        }

        // javascript
        $html .= $assets['js_before'];

        foreach ($assets['js'] as $item) {
            $html .= "\n<script src=\"" . _e(UrlHelper::appendParams($item, $cacheParam)) . '"></script>';
        }

        $html .= $assets['js_after'];

        return $html;
    }

    /**
     * Render a list of information
     *
     * Each item must have 1 or 2 elements: array(content) or array(label, content)
     *
     * @param array[] $infos
     */
    static function renderInfos(array $infos, string $class = 'list-info'): string
    {
        if (!empty($infos)) {
            $output = '<ul class="' . _e($class) . "\"\n>";

            foreach ($infos as $info) {
                if (isset($info[1])) {
                    $output .= "<li><strong>{$info[0]}:</strong> {$info[1]}</li>\n";
                } else {
                    $output .= "<li>{$info[0]}</li>\n";
                }
            }

            $output .= "</ul>\n";

            return $output;
        }

        return '';
    }

    /**
     * Render a list of messages
     *
     * Supported $options:
     * -------------------------------------------------------
     * lcfirst (1)      lowercase first letter of each message
     * escape (1)       escape HTML in messages
     * show_keys (0)    render array keys
     */
    static function renderMessageList(array $messages, array $options = []): string
    {
        $output = '';

        if (!empty($messages)) {
            $output .= "<ul class=\"message-list\">\n";

            foreach ($messages as $key => $item) {
                if ($options['lcfirst'] ?? true) {
                    $item = StringManipulator::lcfirst($item);
                }

                $output .= '<li>'
                    . (($options['show_keys'] ?? false) ? '<strong>' . _e($key) . '</strong>: ' : '')
                    . (($options['escape'] ?? true) ? _e($item) : $item)
                    . "</li>\n";
            }

            $output .= "</ul>\n";
        }

        return $output;
    }

    /**
     * Render an exception
     */
    public static function renderException(\Throwable $e, bool $showTrace = true, bool $showPrevious = true): string
    {
        return '<pre class="exception">' . _e(Exception::render($e, $showTrace, $showPrevious)) . "</pre>\n";
    }

    /**
     * Render a script to limit a length of a textarea
     *
     * @param int $maxlength maximum length
     * @param string $form form name
     * @param string $name textarea name
     */
    static function jsLimitLength(int $maxlength, string $form, string $name): string
    {
        return <<<HTML
<script>
$(document).ready(function() {
    var events = ['keyup', 'mouseup', 'mousedown'];
    for (var i = 0; i < events.length; ++i) $(document)[events[i]](function() {
        Sunlight.limitTextarea(document.{$form}.{$name}, {$maxlength});
    });
});
</script>
HTML;
    }

    private static function renderTimestamp(string $settingVar, int $timestamp, ?string $type = null): string
    {
        $extend = Extend::buffer('render.timestamp', [
            'settings_var' => $settingVar,
            'timestamp' => $timestamp,
            'type' => $type,
        ]);

        if ($extend !== '') {
            return $extend;
        }

        return date(Settings::get($settingVar), $timestamp);
    }
}
