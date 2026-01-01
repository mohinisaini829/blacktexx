<?php
namespace Biloba\ArticleVariantOrderMatrix\Resources\snippet\nb_NO;

use Shopware\Core\System\Snippet\Files\SnippetFileInterface;

/**
 *
 */
class SnippetFile_nb_NO implements SnippetFileInterface
{


  public function getName(): string
  {
    return 'storefront.nb-NO';
  }

  public function getPath(): string
  {
     return __DIR__.'/storefront.nb-NO.json';
  }

  public function getIso(): string
  {
    return 'nb-NO';
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
