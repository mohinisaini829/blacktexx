<?php
namespace Biloba\ArticleVariantOrderMatrix\Resources\snippet\ru_RU;

use Shopware\Core\System\Snippet\Files\SnippetFileInterface;

/**
 *
 */
class SnippetFile_ru_RU implements SnippetFileInterface
{


  public function getName(): string
  {
    return 'storefront.ru-RU';
  }

  public function getPath(): string
  {
     return __DIR__.'/storefront.ru-RU.json';
  }

  public function getIso(): string
  {
    return 'ru-RU';
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
