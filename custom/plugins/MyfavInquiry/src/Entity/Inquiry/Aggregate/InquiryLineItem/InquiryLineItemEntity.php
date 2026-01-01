<?php
declare(strict_types=1);

namespace Myfav\Inquiry\Entity\Inquiry\Aggregate\InquiryLineItem;

use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;
use Myfav\Inquiry\Entity\Inquiry\InquiryEntity;

class InquiryLineItemEntity extends Entity
{
    use EntityIdTrait;

    protected string $inquiryId;

    protected ?string $productId;

    protected ?string $customIdentifier;

    protected ?string $extendedData;

    protected int $quantity;

    protected ?float $price = null;

    protected ?InquiryEntity $inquiry = null;

    protected ?ProductEntity $product = null;

    public function getInquiryId(): string
    {
        return $this->inquiryId;
    }

    public function setInquiryId(string $inquiryId): InquiryLineItemEntity
    {
        $this->inquiryId = $inquiryId;
        return $this;
    }

    // product id
    public function getProductId(): ?string
    {
        return $this->productId;
    }

    public function setProductId(?string $productId): InquiryLineItemEntity
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


    // quantity
    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): InquiryLineItemEntity
    {
        $this->quantity = $quantity;
        return $this;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(?float $price): InquiryLineItemEntity
    {
        $this->price = $price;
        return $this;
    }

    public function getInquiry(): ?InquiryEntity
    {
        return $this->inquiry;
    }

    public function setInquiry(?InquiryEntity $inquiry): InquiryLineItemEntity
    {
        $this->inquiry = $inquiry;
        return $this;
    }

    public function getProduct(): ?ProductEntity
    {
        return $this->product;
    }

    public function setProduct(?ProductEntity $product): InquiryLineItemEntity
    {
        $this->product = $product;
        return $this;
    }

}
