<?php
namespace Biloba\ArticleVariantOrderMatrix\Resources\snippet\lv_LV;

use Shopware\Core\System\Snippet\Files\SnippetFileInterface;

/**
 *
 */
class SnippetFile_lv_LV implements SnippetFileInterface
{


  public function getName(): string
  {
    return 'storefront.lv-LV';
  }

  public function getPath(): string
  {
     return __DIR__.'/storefront.lv-LV.json';
  }

  public function getIso(): string
  {
    return 'lv-LV';
  }

  public function getAuthor(): string
  {
    return 'Biloba';
  }

  public function isBase(): bool
  {
    return false;
  }

}
