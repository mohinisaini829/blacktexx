<?php
namespace Biloba\ArticleVariantOrderMatrix\Resources\snippet\cs_CZ;

use Shopware\Core\System\Snippet\Files\SnippetFileInterface;

/**
 *
 */
class SnippetFile_cs_CZ implements SnippetFileInterface
{


  public function getName(): string
  {
    return 'storefront.cs-CZ';
  }

  public function getPath(): string
  {
     return __DIR__.'/storefront.cs-CZ.json';
  }

  public function getIso(): string
  {
    return 'cs-CZ';
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
