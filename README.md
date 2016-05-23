# CodeIgniter Minifier - A HTML / CSS / Javascript Minification Library

![Screenshot](http://i.imgur.com/L5Cps84.png)


Compress and minify output for your CodeIgniter framework websites. This library supports CodeIgniter 3 only, it is able to not just minify HTML, but also CSS and Javascript.

###Step 1: Load CI_Minifier library###

Copy CI_Minifier.php to libraries folder, and then load CI_Minifier library by one of the following ways.

(1) Load CI_Minifier library in Controller.
```php
class MY_Controller extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->load->library('CI_Minifier');
```
(2) Load CI_Minifier library in config/autoload.php
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
    'function' => 'CI_Minifier',
    'filename' => '',
    'filepath' => ''
);
```
Keep "class", "filename" and "filepath" fields blank. 

###Options###
