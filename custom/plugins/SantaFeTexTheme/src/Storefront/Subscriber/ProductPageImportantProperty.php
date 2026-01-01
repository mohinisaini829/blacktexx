<?php declare(strict_types=1);

namespace SantaFeTexTheme\Storefront\Subscriber;

use SantaFeTexTheme\SantaFeTexTheme;
use Shopware\Core\Content\Property\PropertyGroupEntity;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\Framework\Struct;
use Shopware\Storefront\Page\Product\ProductPageCriteriaEvent;
use Shopware\Storefront\Page\Product\ProductPageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @RouteScope(scopes={"storefront"})
 */
class ProductPageImportantProperty implements EventSubscriberInterface
{
    /** @noinspection PhpArrayShapeAttributeCanBeAddedInspection */
    public static function getSubscribedEvents(): array
    {
        return [
            ProductPageLoadedEvent::class => 'onProductLoaded',
            ProductPageCriteriaEvent::class => 'onProductPageCriteria'
        ];
    }

    public function onProductLoaded(ProductPageLoadedEvent $event): void
    {
        $product = $event->getPage()->getProduct();
        $this->productLoad($product);
    }

    public function onProductPageCriteria(ProductPageCriteriaEvent $event): void
    {
        $event
            ->getCriteria()
            ->addAssociation('properties.media')
        ;
    }

    private function productLoad($product): void
    {
        $productProperties = $product->getSortedProperties();
        foreach ($productProperties as $property) {
            $customFields = $property->getCustomFields();
            $propImportantProperty = null;

            if(!empty($customFields)) {
                if(array_key_exists(SantaFeTexTheme::CUSTOM_FIELD_IMPORTANT_PROPERTY, $customFields)) {
                    $propImportantProperty = $customFields[SantaFeTexTheme::CUSTOM_FIELD_IMPORTANT_PROPERTY];
                }

                if($propImportantProperty) {
                    $product->addExtension('VioImportantProperty', new Struct\ArrayEntity(['vio-extension' => $this->getProperty($property, $property->getName())]));
                }
            }
        }
    }

    private function getProperty(PropertyGroupEntity $property,  $propName = null): array
    {
        $prop = [];
        if($propName) {
            $prop[$propName] = [];
        }

        if($property->getOptions() !== null) {
            foreach ($property->getOptions()->getElements() as $option) {
                if($propName) {
                    $prop[$property->getName()][] = $option->getName();
                }
                else {
                    $prop[] = $option->getName();
                }

            }
        }
        return $prop;
    }
}
