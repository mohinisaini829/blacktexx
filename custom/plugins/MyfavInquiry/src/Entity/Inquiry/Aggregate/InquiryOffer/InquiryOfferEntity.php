<?php
/** @noinspection PhpUnused */
declare(strict_types=1);

namespace Myfav\Inquiry\Entity\Inquiry\Aggregate\InquiryOffer;

use DateTimeInterface;
use Shopware\Core\Content\Media\MediaEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;
use Myfav\Inquiry\Entity\Inquiry\InquiryEntity;

class InquiryOfferEntity extends Entity
{
    use EntityIdTrait;

    protected string $mediaId;
    protected ?MediaEntity $media = null;
    protected string $inquiryId;
    protected ?InquiryEntity $inquiry = null;
    protected string $offerNumber;
    protected ?bool $send = null;
    protected ?DateTimeInterface $createdAt = null;
    protected ?DateTimeInterface $updatedAt = null;

    public function getMediaId(): string
    {
        return $this->mediaId;
    }

    public function setMediaId(string $mediaId): InquiryOfferEntity
    {
        $this->mediaId = $mediaId;
        return $this;
    }

    public function getMedia(): ?MediaEntity
    {
        return $this->media;
    }

    public function setMedia(?MediaEntity $media): InquiryOfferEntity
    {
        $this->media = $media;
        return $this;
    }

    public function getInquiryId(): string
    {
        return $this->inquiryId;
    }

    public function setInquiryId(string $inquiryId): InquiryOfferEntity
    {
        $this->inquiryId = $inquiryId;
        return $this;
    }

    public function getInquiry(): ?InquiryEntity
    {
        return $this->inquiry;
    }

    public function setInquiry(?InquiryEntity $inquiry): InquiryOfferEntity
    {
        $this->inquiry = $inquiry;
        return $this;
    }

    public function getOfferNumber(): string
    {
        return $this->offerNumber;
    }

    public function setOfferNumber(string $offerNumber): InquiryOfferEntity
    {
        $this->offerNumber = $offerNumber;
        return $this;
    }

    public function getSend(): ?bool
    {
        return $this->send;
    }

    public function setSend(?bool $send): InquiryOfferEntity
    {
        $this->send = $send;
        return $this;
    }

    public function getCreatedAt(): ?DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTimeInterface $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function getUpdatedAt(): ?DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(DateTimeInterface $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }

}
