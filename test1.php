<?php
/**
 * Created by PhpStorm.
 * User: selwyn.polit
 * Date: 4/12/16
 * Time: 2:23 PM
 */

//require_once "JSONParser/package/JSONParser.php";
require_once "jsonstreamingparser/vendor/autoload.php";
error_reporting(E_ALL);
$testfile = dirname(__FILE__) . '/WFProducts.json';


class ProductListener implements \JsonStreamingParser\Listener
{
  private $product = array();
  private $store_specific = array();
  private $image = array();
  private $products = array();

  private $just_started_object = false;
  private $key_store;
  private $in_initial_array = false;
  private $identifier = null;
  private $current_object = "";
  private $counter_only = false;
  private $numRecords = 0;

  public function __construct($setting = false) {
    $this->counter_only = (bool) $counter_only;
  }
  public function setCounterOnly($setting) {
    $this->counter_only = (bool) $setting;
  }
  public function getProducts() {
    return $this->products;
  }
  public function getProductCount() {
    return $this->numRecords;
  }
  public function startDocument() {
    // TODO: Implement startDocument() method.
  }

  public function endDocument() {
    // TODO: Implement endDocument() method.
  }

  public function startObject() {
    //Flag to remind us to check what type object we are looking at.
    $this->just_started_object = true;
  }

  public function endObject() {
    switch($this->current_object) {
      case "stores";
        //Check if we already added this product so we don't repeat it.
        $new_product = array_merge($this->product, $this->store_specific);
        $prev_product = $this->products[sizeof($this->products)-1]; //could I use end($this->products) ?
        $diff = array_diff($new_product, $prev_product);
        if (!empty($diff) || is_null($prev_product)) {
          //add this product to the products array.
          $this->products[] = $new_product;
          //Clear the store specific array so it can get used for the next run
          $this->store_specific = array();
        }
        //Finished with all variants of a product
        if (empty($diff)) {
            $this->numRecords += sizeof($this->products);
          //For counter-only code - throw away the products.
          if ($this->counter_only == true) {
            $this->products = array();
          }
        }
        break;
      case "products";
        $products[] = $this->product;
        $this->product = array();
        break;
      case "images";
        $this->product['images'][] = $this->image;
        $this->image = array();
        break;
    }
  }

  public function startArray() {
    if ($this->in_initial_array == false) {
      $this->in_initial_array = true;
      return;
    }
  }

  public function endArray() {
    $this->store_info = false;
  }

  public function key($key) {
    $this->key_store = $key;
  }

  public function value($value) {
//    if (is_null($value)) {
//      $this->key_store = "";
//      return;
//    }
    //Decide type of object if we are just starting
    if ($this->just_started_object) {
      switch($this->key_store) {
        case "identifier";
          $this->current_object = "products";
          break;
        case "url";
          $this->current_object = "images";
          break;
        case "tlc";
          $this->current_object = "stores";
          break;
      }
      $this->just_started_object = false;
    }

    switch($this->current_object) {
      case "products";
        //Initialize store specific and images if new product
        if ($this->key_store == "identifier") {
          if (!is_null($this->identifier)) {
            if ($this->identifier !== $value) {
              // Time for a new product altogether.
              $this->product = array();
              $this->images = array();
              $this->store_specific = array();
            }
          }
        }
        //Generic product info.
        $this->product[$this->key_store] = $value;
        if ($this->key_store == "identifier") {
          $this->identifier = $value;
        }
      break;
      case "stores";
        //Store specific product info
        $this->store_specific[$this->key_store] = $value;
        break;
      case "images";
        $this->image[$this->key_store] = $value;
        break;
    }
  }

  public function whitespace($whitespace) {
    // TODO: Implement whitespace() method.
  }

}

//$listener = new \JsonStreamingParser\Listener\InMemoryListener();
$listener = new ProductListener();
$stream = fopen($testfile,'r');

try {
  $listener->setCounterOnly(true);
  $parser = new \JsonStreamingParser\Parser($stream, $listener);
  $parser->parse();
  var_dump($listener->getProductCount());

  fseek($stream,0);
  $listener->setCounterOnly(false);
  $parser = new \JsonStreamingParser\Parser($stream, $listener);
  $parser->parse();
  fclose($stream);


} catch (Exception $e) {
  fclose($stream);
  throw $e;
}

var_dump($listener->getProducts());