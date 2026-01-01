<?php
namespace Biloba\ArticleVariantOrderMatrix\Resources\snippet\de_AT;

use Shopware\Core\System\Snippet\Files\SnippetFileInterface;

/**
 *
 */
class SnippetFile_de_AT implements SnippetFileInterface
{


  public function getName(): string
  {
    return 'storefront.de-AT';
  }

  public function getPath(): string
  {
     return __DIR__.'/storefront.de-AT.json';
  }

  public function getIso(): string
  {
    return 'de-AT';
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
