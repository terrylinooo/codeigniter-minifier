# CodeIgniter Minifier - A HTML / CSS / Javascript Minification Library

![Screenshot](http://i.imgur.com/L5Cps84.png)


Compress and minify output for your CodeIgniter framework websites. This library supports CodeIgniter 3 only, it is able to not just minify HTML, but also CSS and Javascript.

------------------------------------

###Step 1: Load CI_Minifier library###

Copy CI_Minifier.php to libraries folder, and then load CI_Minifier library by one of the following ways.

```php
$this->load->library('CI_Minifier');
```

#####(1) Load CI_Minifier library in Controller.#####
```php
class MY_Controller extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->load->library('CI_Minifier');
```
#####(2) Load CI_Minifier library in config/autoload.php#####
```php
$autoload['libraries'] = array('CI_Minifier');
```

###Step 2: Enable Hooks in config/config.php###
```php
$config['enable_hooks'] = TRUE;
```
###Step 3: Define a 'display_override' to config/hooks.php###
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
##Options##

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
--------------------------------------------------
##API##

####html()####
Minify HTML string
```php
/**
 * @param $input
 * @return string
 */
$this->ci_minifier->html($input);
```

####css()####
Minify CSS string
```php
/**
 * @param $input
 * @return string
 */
$this->ci_minifier->css($input);
```

####js()####
Minify Javascript string

***Be careful: This method doesn't support "javascript automatic semicolon insertion", you must add semicolon by yourself, otherwise your javascript code will not work and generate error messages***.
```php
/**
 * @param $input
 * @return string
 */
$this->ci_minifier->js($input);
```
---------------------------------------------
##License##
GPL version 3

