<?php
namespace Biloba\ArticleVariantOrderMatrix\Resources\snippet\de_CH;

use Shopware\Core\System\Snippet\Files\SnippetFileInterface;

/**
 *
 */
class SnippetFile_de_CH implements SnippetFileInterface
{


  public function getName(): string
  {
    return 'storefront.de-CH';
  }

  public function getPath(): string
  {
     return __DIR__.'/storefront.de-CH.json';
  }

  public function getIso(): string
  {
    return 'de-CH';
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
