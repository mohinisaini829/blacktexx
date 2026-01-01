<?php

namespace Biloba\ArticleVariantOrderMatrix\Structs;

use Shopware\Core\Framework\Struct\Struct;

/**
 * data storage to write data to storefront
 */
class StoreFrontPageCollection extends Struct
{

  public $elements = [];

  public function __construct()
  {
  }

  public function setValue($key, $value):void
  {
    $this->elements[$key] = $value;
  }

  public function getValue($key)
  {
    if(array_key_exists($key, $this->elements))
    {
      return $this->elements[$key];
    }

    return null;
  }
}
