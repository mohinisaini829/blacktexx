<?php
namespace Biloba\ArticleVariantOrderMatrix\Resources\snippet\da_DK;

use Shopware\Core\System\Snippet\Files\SnippetFileInterface;

/**
 *
 */
class SnippetFile_da_DK implements SnippetFileInterface
{


  public function getName(): string
  {
    return 'storefront.da-DK';
  }

  public function getPath(): string
  {
     return __DIR__.'/storefront.da-DK.json';
  }

  public function getIso(): string
  {
    return 'da-DK';
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
