<?php

/**
 * @name        CI_Minifier - Minify Library for CodeIgniter 3
 * @author      Terry Lin
 * @link        https://github.com/terrylinooo/CodeIgniter-Minifier
 * @license     GPL version 3 License Copyright
 *
 */

class CI_Minifier
{


    private static $enable_html = true;
    private static $enable_js   = true;
    private static $enable_css  = true;


    /**
     * Set a level type to handle the output.
     *
     * @param number $level  0: original output, nothing changes.
     *                       1: minify html, css and javascript.
     *                       2: minify html, css
     *                       3: minidy html, javascript
     *                       4: minify html
     *                       5: minify css and javascript
     *                       6: minify css
     *                       7: minify javascript
     *
     *        string $level  accept string 'js', 'css, 'html'
     *                       you can put them together and separated by commas, for example: 'html,js'
     */
    public function init($level)
    {
        self::$enable_html = false;
        self::$enable_css  = false;
        self::$enable_js   = false;

        if (is_numeric($level)) {
            switch ($level) {
                case 7:
                    self::$enable_js   = true;
                    break;
                case 6:
                    self::$enable_css  = true;
                    break;
                case 5:
                    self::$enable_css  = true;
                    self::$enable_js   = true;
                    break;
                case 4:
                    self::$enable_html = true;
                    break;
                case 3:
                    self::$enable_html = true;
                    self::$enable_js   = true;
                    break;
                case 2:
                    self::$enable_html = true;
                    self::$enable_css  = true;
                    break;
                case 1:
                default:
                    self::$enable_html = true;
                    self::$enable_css  = true;
                    self::$enable_js   = true;
                    break;
                case 0:
                    break;
            }
        }

        if (is_string($level)) {
            $level = str_replace(' ', '', $level);
            $types = explode(',', $level);

            foreach ($types as $type) {
                if ($type == 'html') {
                    self::$enable_html = true;
                }
                if ($type == 'css') {
                    self::$enable_css = true;
                }
                if ($type == 'js') {
                    self::$enable_js = true;
                }
            }
        }
    }


    /**
     *
     */
    public static function output()
    {
        ini_set("pcre.recursion_limit", "16777");

        $CI =& get_instance();

        $buffer = $CI->output->get_output();
        $new_buffer = null;

        if (!(!self::$enable_html and !self::$enable_css and !self::$enable_js)) {
            if (self::$enable_js or self::$enable_css) {
                // You're facing "Fatal error: Class 'DOMDocument' not found" error
                // you need to install php-xml to support PHP DOM
                // For CentOS, run "yum install php-xml"
                $dom = new DOMDocument;

                // prevent DOMDocument::loadHTML error
                libxml_use_internal_errors(true);
                $dom->loadHTML($buffer);
            }

            if (self::$enable_js) {
                // Get all script Tags and minify them
                $scripts = $dom->getElementsByTagName('script');
                foreach ($scripts as $script) {
                    if (!empty($script->nodeValue)) {
                        $script->nodeValue = self::minifyJS($script->nodeValue);
                    }
                }
            }

            if (self::$enable_css) {
                // Get all style Tags and minify them
                $styles = $dom->getElementsByTagName('style');
                foreach ($styles as $style) {
                    if (!empty($style->nodeValue)) {
                        $style->nodeValue = self::minifyCSS($style->nodeValue);
                    }
                }
            }

            if (self::$enable_js or self::$enable_css) {
                if (self::$enable_html) {
                    $new_buffer = self::minifyHTML($dom->saveHTML());
                } else {
                    $new_buffer = $dom->saveHTML();
                }
                libxml_use_internal_errors(false);
                unset($dom);
            } else {
                if (self::$enable_html) {
                    $new_buffer = self::minifyHTML($buffer);
                }
            }
        }

        if ($new_buffer === null) {
            $new_buffer = $buffer;
        }
        $CI->output->set_output($new_buffer);
        $CI->output->_display();
    }


    /**
     * Minify the HTML text
     *
     * @param string $input
     * @return mixed
     *
     * @link    https://github.com/mecha-cms/mecha-cms/blob/master/engine/kernel/converter.php
     * @author  Taufik Nurrohman
     * @license GPL version 3 License Copyright
     *
     */
    private static function minifyHTML($input)
    {
        if (trim($input) === "") {
            return $input;
        }

        // Remove extra white-space(s) between HTML attribute(s)
        $input = preg_replace_callback(
            '#<([^\/\s<>!]+)(?:\s+([^<>]*?)\s*|\s*)(\/?)>#s',
            function ($matches) {
                return '<' . $matches[1] . preg_replace('#([^\s=]+)(\=([\'"]?)(.*?)\3)?(\s+|$)#s', ' $1$2', $matches[2]) . $matches[3] . '>';
            },
            str_replace("\r", "", $input)
        );

        // Minify inline CSS declaration(s)
        if (strpos($input, ' style=') !== false) {
            $input = preg_replace_callback(
                '#<([^<]+?)\s+style=([\'"])(.*?)\2(?=[\/\s>])#s',
                function ($matches) {
                    return '<' . $matches[1] . ' style=' . $matches[2] . self::minifyCSS($matches[3]) . $matches[2];
                },
                $input
            );
        }
        return preg_replace(
            array(
                // t = text
                // o = tag open
                // c = tag close
                // Keep important white-space(s) after self-closing HTML tag(s)
                '#<(img|input)(>| .*?>)#s',

                // Remove a line break and two or more white-space(s) between tag(s)
                '#(<!--.*?-->)|(>)(?:\n*|\s{2,})(<)|^\s*|\s*$#s',
                '#(<!--.*?-->)|(?<!\>)\s+(<\/.*?>)|(<[^\/]*?>)\s+(?!\<)#s', // t+c || o+t
                '#(<!--.*?-->)|(<[^\/]*?>)\s+(<[^\/]*?>)|(<\/.*?>)\s+(<\/.*?>)#s', // o+o || c+c
                '#(<!--.*?-->)|(<\/.*?>)\s+(\s)(?!\<)|(?<!\>)\s+(\s)(<[^\/]*?\/?>)|(<[^\/]*?\/?>)\s+(\s)(?!\<)#s', // c+t || t+o || o+t -- separated by long white-space(s)
                '#(<!--.*?-->)|(<[^\/]*?>)\s+(<\/.*?>)#s', // empty tag
                '#<(img|input)(>| .*?>)<\/\1\x1A>#s', // reset previous fix
                '#(&nbsp;)&nbsp;(?![<\s])#', // clean up ...

                // Force line-break with `&#10;` or `&#xa;`
                '#&\#(?:10|xa);#',

                // Force white-space with `&#32;` or `&#x20;`
                '#&\#(?:32|x20);#',

                // Remove HTML comment(s) except IE comment(s)
                '#\s*<!--(?!\[if\s).*?-->\s*|(?<!\>)\n+(?=\<[^!])#s'
            ),
            array(
                "<$1$2</$1\x1A>",
                '$1$2$3',
                '$1$2$3',
                '$1$2$3$4$5',
                '$1$2$3$4$5$6$7',
                '$1$2$3',
                '<$1$2',
                '$1 ',
                "\n",
                ' ',
                ""
            ),
            $input);
    }
    /**
     * Minify the CSS text
     *
     * @param string $input
     * @return mixed
     *
     * @link    http://ideone.com/Q5USEF + improvement(s)
     * @author  Unknown, improved by Taufik Nurrohman
     * @license GPL version 3 License Copyright
     */
    private static function minifyCSS($input)
    {
        if (trim($input) === "") {
            return $input;
        }

        // Force white-space(s) in `calc()`
        if (strpos($input, 'calc(') !== false) {
            $input = preg_replace_callback('#(?<=[\s:])calc\(\s*(.*?)\s*\)#',
                function ($matches) {
                    return 'calc(' . preg_replace('#\s+#', "\x1A", $matches[1]) . ')';
                },
                $input
            );
        }

        return preg_replace(
            array(
                // Remove comment(s)
                '#("(?:[^"\\\]++|\\\.)*+"|\'(?:[^\'\\\\]++|\\\.)*+\')|\/\*(?!\!)(?>.*?\*\/)|^\s*|\s*$#s',

                // Remove unused white-space(s)
                '#("(?:[^"\\\]++|\\\.)*+"|\'(?:[^\'\\\\]++|\\\.)*+\'|\/\*(?>.*?\*\/))|\s*+;\s*+(})\s*+|\s*+([*$~^|]?+=|[{};,>~+]|\s*+-(?![0-9\.])|!important\b)\s*+|([[(:])\s++|\s++([])])|\s++(:)\s*+(?!(?>[^{}"\']++|"(?:[^"\\\]++|\\\.)*+"|\'(?:[^\'\\\\]++|\\\.)*+\')*+{)|^\s++|\s++\z|(\s)\s+#si',

                // Replace `0(cm|em|ex|in|mm|pc|pt|px|vh|vw|%)` with `0`
                '#(?<=[\s:])(0)(cm|em|ex|in|mm|pc|pt|px|vh|vw|%)#si',

                // Replace `:0 0 0 0` with `:0`
                '#:(0\s+0|0\s+0\s+0\s+0)(?=[;\}]|\!important)#i',

                // Replace `background-position:0` with `background-position:0 0`
                '#(background-position):0(?=[;\}])#si',

                // Replace `0.6` with `.6`, but only when preceded by a white-space or `=`, `:`, `,`, `(`, `-`
                '#(?<=[\s=:,\(\-]|&\#32;)0+\.(\d+)#s',

                // Minify string value
                '#(\/\*(?>.*?\*\/))|(?<!content\:)([\'"])([a-z_][-\w]*?)\2(?=[\s\{\}\];,])#si',
                '#(\/\*(?>.*?\*\/))|(\burl\()([\'"])([^\s]+?)\3(\))#si',

                // Minify HEX color code
                '#(?<=[\s=:,\(]\#)([a-f0-6]+)\1([a-f0-6]+)\2([a-f0-6]+)\3#i',

                // Replace `(border|outline):none` with `(border|outline):0`
                '#(?<=[\{;])(border|outline):none(?=[;\}\!])#',

                // Remove empty selector(s)
                '#(\/\*(?>.*?\*\/))|(^|[\{\}])(?:[^\s\{\}]+)\{\}#s',
                '#\x1A#'
            ),
            array(
                '$1',
                '$1$2$3$4$5$6$7',
                '$1',
                ':0',
                '$1:0 0',
                '.$1',
                '$1$3',
                '$1$2$4$5',
                '$1$2$3',
                '$1:0',
                '$1$2',
                ' '
            ),
            $input
        );
    }

    /**
     * Minify the Javascript text
     *
     * Be careful:
     * This method doesn't support "javascript automatic semicolon insertion", you must add semicolon by yourself,
     * otherwise your javascript code will not work and generate error messages.
     *
     * @param string $input
     * @return mixed
     *
     * @link    https://github.com/mecha-cms/mecha-cms/blob/master/engine/kernel/converter.php
     * @author  Taufik Nurrohman
     * @license GPL version 3 License Copyright
     */
    private static function minifyJS($input)
    {
        if (trim($input) === "") {
            return $input;
        }

        return preg_replace(
            array(
                // Remove comment(s)
                '#\s*("(?:[^"\\\]++|\\\.)*+"|\'(?:[^\'\\\\]++|\\\.)*+\')\s*|\s*\/\*(?!\!|@cc_on)(?>[\s\S]*?\*\/)\s*|\s*(?<![\:\=])\/\/.*(?=[\n\r]|$)|^\s*|\s*$#',

                // Remove white-space(s) outside the string and regex
                '#("(?:[^"\\\]++|\\\.)*+"|\'(?:[^\'\\\\]++|\\\.)*+\'|\/\*(?>.*?\*\/)|\/(?!\/)[^\n\r]*?\/(?=[\s.,;]|[gimuy]|$))|\s*([!%&*\(\)\-=+\[\]\{\}|;:,.<>?\/])\s*#s',

                // Remove the last semicolon
                '#;+\}#',

                // Minify object attribute(s) except JSON attribute(s). From `{'foo':'bar'}` to `{foo:'bar'}`
                '#([\{,])([\'])(\d+|[a-z_]\w*)\2(?=\:)#i',

                // --ibid. From `foo['bar']` to `foo.bar`
                '#([\w\)\]])\[([\'"])([a-z_]\w*)\2\]#i',

                // Replace `true` with `!0`
                '#(?<=return |[=:,\(\[])true\b#',

                // Replace `false` with `!1`
                '#(?<=return |[=:,\(\[])false\b#',

                // Clean up ...
                '#\s*(\/\*|\*\/)\s*#'
            ),
            array(
                '$1',
                '$1$2',
                '}',
                '$1$3',
                '$1.$3',
                '!0',
                '!1',
                '$1'
            ),
            $input
        );
    }

    /**
     * Alias for static minifyHTML()
     *
     * @param $input
     * @return mixed
     */
    public function html($input)
    {
        return self::minifyHTML($input);
    }

    /**
     * Alias for static minifyCSS()
     *
     * @param $input
     * @return mixed
     */
    public function css($input)
    {
        return self::minifyCSS($input);
    }

    /**
     * Alias for static minifyJS()
     *
     * @param $input
     * @return mixed
     */
    public function js($input)
    {
        return self::minifyJS($input);
    }
}


// This global function is only used hook "display_override" in /config/hooks.php
// Please add the following setting in /config/hooks.php

 # $hook['display_override'][] = array(
 #     'class' => '',
 #     'function' => 'CI_Minifier_Hook_Loader',
 #     'filename' => '',
 #     'filepath' => ''
 # );

 # For getting more control of output, but there is no way to pass varibles to hook function and class..
 # Finally I decide to use class instead of function, but it is still need a function can be called by
 # /system/core/Hook.php (line:259)

function CI_Minifier_Hook_Loader()
{
    return CI_Minifier::output();
}
