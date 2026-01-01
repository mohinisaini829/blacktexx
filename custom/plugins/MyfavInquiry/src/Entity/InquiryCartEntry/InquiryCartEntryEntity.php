<?php
declare(strict_types=1);

namespace Myfav\Inquiry\Entity\InquiryCartEntry;

use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

class InquiryCartEntryEntity extends Entity
{
    use EntityIdTrait;

    protected int $quantity;

    protected ?ProductEntity $product;

    protected ?string $productId;

    protected ?string $customIdentifier;

    protected ?string $extendedData;


    protected string $token;

    public function getQuantity() : int
    {
        return $this->quantity;
    }

    public function setQuantity(int $value) : InquiryCartEntryEntity
    {
        $this->quantity = $value;
        return $this;
    }

    // product
    public function getProduct(): ?ProductEntity
    {
        return $this->product;
    }

    public function setProduct(?ProductEntity $product): InquiryCartEntryEntity
    {
        $this->product = $product;
        return $this;
    }

    public function getProductId(): ?string
    {
        return $this->productId;
    }

    public function setProductId(?string $productId): InquiryCartEntryEntity
    {
        $this->productId = $productId;
        return $this;
    }

    // customIdentifier
    public function getCustomIdentifier(): ?string
    {
        return $this->customIdentifier;
    }

    public function setCustomIdentifier(?string $customIdentifier): InquiryLineItemEntity
    {
        $this->customIdentifier = $customIdentifier;
        return $this;
    }

    // extendedData
    public function getExtendedData(): ?string
    {
        return $this->extendedData;
    }

    public function setExtendedData(?string $extendedData): InquiryLineItemEntity
    {
        $this->extendedData = $extendedData;
        return $this;
    }


    // token
    public function getToken(): string
    {
        return $this->token;
    }

    public function setToken(string $token): InquiryCartEntryEntity
    {
        $this->token = $token;
        return $this;
    }

}
