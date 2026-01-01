<?php
namespace Biloba\ArticleVariantOrderMatrix\Resources\snippet\tr_TR;

use Shopware\Core\System\Snippet\Files\SnippetFileInterface;

/**
 *
 */
class SnippetFile_tr_TR implements SnippetFileInterface
{


  public function getName(): string
  {
    return 'storefront.tr-TR';
  }

  public function getPath(): string
  {
     return __DIR__.'/storefront.tr-TR.json';
  }

  public function getIso(): string
  {
    return 'tr-TR';
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
