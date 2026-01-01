<?php
namespace Biloba\ArticleVariantOrderMatrix\Resources\snippet\el_GR;

use Shopware\Core\System\Snippet\Files\SnippetFileInterface;

/**
 *
 */
class SnippetFile_el_GR implements SnippetFileInterface
{


  public function getName(): string
  {
    return 'storefront.el-GR';
  }

  public function getPath(): string
  {
     return __DIR__.'/storefront.el-GR.json';
  }

  public function getIso(): string
  {
    return 'el-GR';
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
