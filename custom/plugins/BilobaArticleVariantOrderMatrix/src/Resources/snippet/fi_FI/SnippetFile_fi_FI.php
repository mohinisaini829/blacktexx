<?php
namespace Biloba\ArticleVariantOrderMatrix\Resources\snippet\fi_FI;

use Shopware\Core\System\Snippet\Files\SnippetFileInterface;

/**
 *
 */
class SnippetFile_fi_FI implements SnippetFileInterface
{


  public function getName(): string
  {
    return 'storefront.fi-FI';
  }

  public function getPath(): string
  {
     return __DIR__.'/storefront.fi-FI.json';
  }

  public function getIso(): string
  {
    return 'fi-FI';
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
