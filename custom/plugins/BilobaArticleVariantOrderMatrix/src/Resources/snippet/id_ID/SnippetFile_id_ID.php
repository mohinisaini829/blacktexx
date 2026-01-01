<?php
namespace Biloba\ArticleVariantOrderMatrix\Resources\snippet\id_ID;

use Shopware\Core\System\Snippet\Files\SnippetFileInterface;

/**
 *
 */
class SnippetFile_id_ID implements SnippetFileInterface
{


  public function getName(): string
  {
    return 'storefront.id-ID';
  }

  public function getPath(): string
  {
     return __DIR__.'/storefront.id-ID.json';
  }

  public function getIso(): string
  {
    return 'id-ID';
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
