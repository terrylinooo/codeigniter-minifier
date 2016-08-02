<?php

/**
 * @name        CI_Minifier - Minify Library for CodeIgniter 3
 * @author      Terry Lin
 * @link        https://github.com/terrylinooo/CodeIgniter-Minifier
 * @license     GPL version 3 License Copyright
 * @code_style  PSR-2
 * @change_log  1.0 - first release.
 *
 *              1.1 - Add javascript obfuscator (Dean Edwards' version)
 *                    To enable this function
 *                    Step 1. put JSPacker.php at /third_party folder
 *                    Step 2. use $this->ci_minifier->enable_obfuscator() in Controller.
 *              1.2   Add PHP Simple Dom parser (If you met problem with DOMDocument, it is a solution)
 *                    Step 1. put Simple_html_dom.php at /third_party folder
 *                    Step 2. use $this->ci_minifier->set_domparser(2); in Controller.
 */

class CI_Minifier
{

    private static $enable_html = true;
    private static $enable_js   = true;
    private static $enable_css  = true;
    /**
     * @var bool
     */
    private static $enable_obfuscator = false;
    private static $obfuscator;

    /*
     *  1: DOMDocument
     *  2: simplehtmldom
     */

    public static $dom_parser = 2;

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
                if ($type == 'off') {
                    // nothing changes
                }
            }
        }
    }

    public function set_domparser($type = 1)
    {
        self::$dom_parser = $type;

        if ($type == 2) {
            if (!class_exists('Simple_html_dom')) {
                try {
                    include APPPATH . 'third_party/Simple_html_dom.php';

                } catch (Exception $e) {
                    self::$dom_parser = 1;
                    return false;
                }
            }
        }
    }


    /**
     * @param int $level
     * @return bool
     */
    public function enable_obfuscator($level = 2)
    {
        self::$enable_obfuscator = true;

        if (!class_exists('JSPacker')) {
            try {
                include APPPATH . 'third_party/JSPacker.php';

            } catch (Exception $e) {
                self::$enable_obfuscator = false;
                return false;
            }
        }

        switch ($level) {
            case 0:
                $packed_level = 'None';
                break;
            case 1:
                $packed_level = 'Numeric';
                break;
            case 2:
            default:
                $packed_level = 'Normal';
                break;
            case 3:
                $packed_level = 'High ASCII';
                break;
        }

        self::$obfuscator = new JSPacker('', $packed_level, true, false);
    }


    /**
     * CI Minifier - Output handler
     *
     * @return mixed
     */
    public static function output()
    {
        ini_set("pcre.recursion_limit", "16777");

        $CI =& get_instance();

        $buffer = $CI->output->get_output();
        $new_buffer = null;

        if (!(!self::$enable_html and !self::$enable_css and !self::$enable_js)) {
            if (self::$enable_js or self::$enable_css) {

                if (self::$dom_parser == 1) {
                    // You're facing "Fatal error: Class 'DOMDocument' not found" error
                    // you need to install php-xml to support PHP DOM
                    // For CentOS, run "yum install php-xml"
                    $dom = new DOMDocument;

                    // prevent DOMDocument::loadHTML error
                    libxml_use_internal_errors(true);
                    $dom->loadHTML($buffer, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
                }
                if (self::$dom_parser == 2) {
                    $dom = new \SimpleDom\Simple_html_dom();
                    $dom->load($buffer, true, false);
                }
            }

            if (self::$enable_js) {
                // Get all script Tags and minify them
                if (self::$dom_parser == 1) {
                    $scripts = $dom->getElementsByTagName('script');
                    foreach ($scripts as $script) {
                        $data_minify_level = $script->getAttribute('data-minify-level');
                        if ($data_minify_level != '0') {
                            if (!empty($script->nodeValue)) {
                                if (self::$enable_obfuscator) {
                                    $script->nodeValue = self::packerJS($script->nodeValue, $data_minify_level);
                                } else {
                                    $script->nodeValue = self::minifyJS($script->nodeValue);
                                }
                            }
                        }
                    }
                }
                if (self::$dom_parser == 2) {
                    $scripts = $dom->find('script');
                    foreach ($scripts as $script) {
                        $data_minify_level = $script->{'data-minify-level'};
                        if ($data_minify_level !== '0') {
                            if (!empty($script->innertext)) {
                                if (self::$enable_obfuscator) {
                                    $script->innertext = self::packerJS($script->innertext, $data_minify_level);
                                } else {
                                    $script->innertext = self::minifyJS($script->innertext);
                                }
                            }
                        }
                    }
                }

            }

            if (self::$enable_css) {
                // Get all style Tags and minify them
                if (self::$dom_parser == 1) {
                    $styles = $dom->getElementsByTagName('style');
                    foreach ($styles as $style) {
                        if (!empty($style->nodeValue)) {
                            $style->nodeValue = self::minifyCSS($style->nodeValue);
                        }
                    }
                }
                if (self::$dom_parser == 2) {
                    $styles = $dom->find('style');
                    foreach ($styles as $style) {
                        if (!empty($style->innertext)) {
                            $style->innertext = self::minifyCSS($style->innertext);
                        }
                    }
                }

            }

            if (self::$enable_js or self::$enable_css) {
                if (self::$dom_parser == 1) {
                    if (self::$enable_html) {
                        $new_buffer = self::minifyHTML($dom->saveHTML());
                    } else {
                        $new_buffer = $dom->saveHTML();
                    }
                    libxml_use_internal_errors(false);
                    unset($dom);
                }
                if (self::$dom_parser == 2) {
                    if (self::$enable_html) {
                        $new_buffer = self::minifyHTML($dom->save());
                    } else {
                        $new_buffer = $dom->save();
                    }
                }
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
     * Another minification engine by Dean Edwards.
     */
    private static function packerJS($input, $level = 2)
    {
        switch ($level) {
            case 1:
                $data_minify_level = 10;
                break;
            case 2:
            default:
                $data_minify_level = 62;
                break;
            case 3:
                $data_minify_level = 95;
                break;
        }
        self::$obfuscator->set_encoding($data_minify_level);
        self::$obfuscator->load_script($input);
        return self::$obfuscator->pack();
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
    /**
     * Alias for static packJS()
     *
     * @param $input
     * @return mixed
     */
    public function js_packer($input, $level = 2)
    {
        return self::packerJS($input, $level);
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
