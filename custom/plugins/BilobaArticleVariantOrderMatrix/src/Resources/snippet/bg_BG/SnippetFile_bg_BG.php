<?php
namespace Biloba\ArticleVariantOrderMatrix\Resources\snippet\bg_BG;

use Shopware\Core\System\Snippet\Files\SnippetFileInterface;

/**
 *
 */
class SnippetFile_bg_BG implements SnippetFileInterface
{


  public function getName(): string
  {
    return 'storefront.bg-BG';
  }

  public function getPath(): string
  {
     return __DIR__.'/storefront.bg-BG.json';
  }

  public function getIso(): string
  {
    return 'bg-BG';
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
