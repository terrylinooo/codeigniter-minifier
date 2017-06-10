# CodeIgniter Minifier - A HTML / CSS / Javascript Minification Library

![Screenshot](http://i.imgur.com/L5Cps84.png)

Compress and minify output for your CodeIgniter framework websites. This library supports CodeIgniter 3 only, it is able to not just minify HTML, but also CSS and Javascript.

It also works with `$this->output->cache($n);` to save minified content into the cache files.

------------------------------------
### Change Logs
* ver 1.0 - first release
* ver 1.1 - Add javascript obfuscator (Dean Edwards' version)
* ver 1.2 - Add PHP Simple Dom parser to parse "script" and "style" tags. It is an alternative if the default parser (DOMDocument) causes your Javasctipt to not work.

--------------------------------------

### Step 1: Load CI_Minifier library

Copy CI_Minifier.php to libraries folder, and then load CI_Minifier library by one of the following ways.


(1) Load CI_Minifier library in Controller.
```php
$this->load->library('CI_Minifier');
```
(2) Load CI_Minifier library in config/autoload.php (recommeded)
```php
$autoload['libraries'] = array('CI_Minifier');
```

### Step 2: Enable Hooks in config/config.php
```php
$config['enable_hooks'] = TRUE;
```
### Step 3: Define a 'display_override' to config/hooks.php
```php
$hook['display_override'][] = array(
    'class' => '',
    'function' => 'CI_Minifier_Hook_Loader',
    'filename' => '',
    'filepath' => ''
);
```
Keep "class", "filename" and "filepath" fields blank. 

----------------------------------------
## Options

CodeIgniter Minifier has the following options, you can set the "option number" or "option string" to init() to minify HTML, CSS, Javascript as your choice. 

------------------------

For setting option string, you can put them together and separated by commas, for example: 'html,js'

| option number  | option string | HTML | CSS | Javascript |
| ------------- | ------------- | ------------- | ------------- | ------------- |
| 0 | off | x | x | x |
| 1 | html,css,js | o | o | o |
| 2 | html,css | o | o | x |
| 3 | html,js | o | x | o |
| 4 | html | o | x | x |
| 5 | css,js | x | o | o |
| 6 | css | x | o | x |
| 7 | js | x | x | o |

Notice that setting option to '1' or 'html,css,js' is totally unnecessary, because it is default.

Here is the examples:
```php
// Keep original output, nothing changes. You can use it on some pages you won't minify.
$this->ci_minifier->init(0);
// same as
$this->ci_minifier->init('off');
```
```php
// Minify html only
$this->ci_minifier->init(4); 
// same as
$this->ci_minifier->init('html'); 
```
```php
// Minify html and css, except jaascript
$this->ci_minifier->init(2);
// same as
$this->ci_minifier->init('html,css'); 
```
#### Enable Javascript obfuscator

![Screenshot](http://i.imgur.com/PRGEKHj.png)

enable_obfuscator($level = 2)
```php
/**
 * @param int $level - default: 2
 * @return bool
 */
$this->ci_minifier->enable_obfuscator();
```
| option level  | obfuscation type | 
| ------------- | ------------- | 
| 0 | None |
| 1 | Numeric |
| 2 | Normal |
| 3 | High ASCII |


Javascript obfuscator is off by default, if you would like to use this feature, copy `JSPacker.php` to `/application/third_party/` folder, then put `$this->ci_minifier->enable_obfuscator();` in Controller.

--------------------------------------

#### Use PHP Simple Dom parser to parse "script" and "style" tags

1. put Simple_html_dom.php at /third_party folder
2. use $this->ci_minifier->set_domparser(2); in Controller.

option value: 1 (default, PHP bulti-in Dom parser - DOMDocument)
option value: 2 (PHP Simple Dom parser)

--------------------------------------------------
## API

#### html()
Minify HTML string
```php
/**
 * @param $input
 * @return string
 */
$this->ci_minifier->html($input);
```

#### css()
Minify CSS string
```php
/**
 * @param $input
 * @return string
 */
$this->ci_minifier->css($input);
```

#### js()
Minify Javascript string
```php
/**
 * @param $input
 * @return string
 */
$this->ci_minifier->js($input);
```
***Be careful: This method doesn't support "javascript automatic semicolon insertion", you must add semicolon by yourself, otherwise your javascript code will not work and generate error messages***.

### js_packer()

Minify Javascript string by use JSPacker (Dean Edwards' version)
```php
/**
 * @param $input
 * @param $level
 * @return string
 */
$this->ci_minifier->js($input, $level = 2);
```
### Success example

Original code:
```javascript
<script>
var d = new Date();
d.setTime(d.getTime()+(7*24*60*60*1000));
var expires = "expires="+d.toUTCString();
document.cookie = "ssjd=1;domain=.dictpedia.com;"+expires;
</script>
```
After minifying
```javascript
<script>var d=new Date();d.setTime(d.getTime()+(7*24*60*60*1000));var expires="expires="+d.toUTCString();document.cookie="ssjd=1;domain=.dictpedia.com;"+expires;</script>
```
### Failure example

Original code is working with popular browsers because that browsers support "javascript automatic semicolon insertion".
```javascript
<script>
var d = new Date()
d.setTime(d.getTime()+(7*24*60*60*1000))
var expires = "expires="+d.toUTCString()
document.cookie = "ssjd=1;domain=.dictpedia.com;"+expires;
</script>
```
After minifying, this code will generate error because of semicolon issue.
```javascript
<script>var d=new Date()d.setTime(d.getTime()+(7*24*60*60*1000))var expires="expires="+d.toUTCString()document.cookie="ssjd=1;domain=.dictpedia.com;"+expires;</script>
```
### Ideas

Minifying all Javascript snippets is good but it breaks Google AdSense's TOS, so how to minify all of them excepts Google AdSense?
```
<script async src="//pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"></script>
<ins class="adsbygoogle"
     style="display:inline-block;width:160px;height:600px"
     data-ad-client="ca-pub-xxxxxxxxxx"
     data-ad-slot="4179732317"></ins>
<script data-minify-level="0">
(adsbygoogle = window.adsbygoogle || []).push({});
</script>

```
CI Minifier will skip script tags contain `data-minify-level="0"`, this option can also control Javascript obfuscator encoding level, the default value is 2, you set the value 1-3 whatever you like.

---------------------------------------------

## License

GPL version 3

