<?php
namespace Biloba\ArticleVariantOrderMatrix\Resources\snippet\sv_SE;

use Shopware\Core\System\Snippet\Files\SnippetFileInterface;

/**
 *
 */
class SnippetFile_sv_SE implements SnippetFileInterface
{


  public function getName(): string
  {
    return 'storefront.sv-SE';
  }

  public function getPath(): string
  {
     return __DIR__.'/storefront.sv-SE.json';
  }

  public function getIso(): string
  {
    return 'sv-SE';
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
