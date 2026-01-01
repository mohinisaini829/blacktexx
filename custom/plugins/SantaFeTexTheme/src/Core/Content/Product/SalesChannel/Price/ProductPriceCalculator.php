<?php
declare(strict_types=1);

namespace SantaFeTexTheme\Core\Content\Product\SalesChannel\Price;

use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\Content\Product\SalesChannel\Price\AbstractProductPriceCalculator;
use Shopware\Core\Content\Product\SalesChannel\SalesChannelProductEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Contracts\Service\ResetInterface;

class ProductPriceCalculator extends AbstractProductPriceCalculator implements ResetInterface
{

    protected AbstractProductPriceCalculator $decorated;

    public function __construct(
        AbstractProductPriceCalculator $decorated
    )
    {
        $this->decorated = $decorated;
    }

    public function getDecorated(): AbstractProductPriceCalculator
    {
        return $this->decorated;
    }

    public function calculate(iterable $products, SalesChannelContext $context): void
    {
        $this->decorated->calculate($products, $context);
        foreach ($products as $product) {
            // set net price on calculated prices
            if ($product instanceof SalesChannelProductEntity) {
                foreach ($product->getCalculatedPrices() as $price) {
                    $netTotalPrice = $price->getTotalPrice() - $price->getCalculatedTaxes()->getAmount();
                    $netPrice = new CalculatedPrice(
                        $netTotalPrice / $price->getQuantity(),
                        $netTotalPrice,
                        $price->getCalculatedTaxes(),
                        $price->getTaxRules(),
                        $price->getQuantity()
                    );
                    $price->addExtension('netPrice', $netPrice);
                }
            }
        }
    }

    public function reset(): void
    {
        if($this->decorated instanceof ResetInterface) {
            $this->decorated->reset();
        }
    }
}
