<?php
/**
 * Created by PhpStorm.
 * User: selwyn.polit
 * Date: 4/15/16
 * Time: 12:12 PM
 */


//Test reading for normal small json files with json_decode.


$testfile = dirname(__FILE__) . '/WFProducts.json';

$string = file_get_contents($testfile);
$json_a = json_decode($string, true);
echo '<pre>' . print_r($json_a, true) . '</pre>';

//var_dump($json_a);
