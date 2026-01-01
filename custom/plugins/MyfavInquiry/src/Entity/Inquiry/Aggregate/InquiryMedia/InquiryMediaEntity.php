<?php
declare(strict_types=1);

namespace Myfav\Inquiry\Entity\Inquiry\Aggregate\InquiryMedia;

use Shopware\Core\Content\Media\MediaEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;
use Myfav\Inquiry\Entity\Inquiry\InquiryEntity;

class InquiryMediaEntity extends Entity
{
    use EntityIdTrait;

    protected string $mediaId;

    protected string $inquiryId;

    protected ?MediaEntity $media;

    protected ?InquiryEntity $inquiry;

    public function getMediaId(): string
    {
        return $this->mediaId;
    }

    public function setMediaId(string $mediaId): InquiryMediaEntity
    {
        $this->mediaId = $mediaId;
        return $this;
    }

    public function getInquiryId(): string
    {
        return $this->inquiryId;
    }

    public function setInquiryId(string $inquiryId): InquiryMediaEntity
    {
        $this->inquiryId = $inquiryId;
        return $this;
    }

    public function getMedia(): ?MediaEntity
    {
        return $this->media;
    }

    public function setMedia(?MediaEntity $media): InquiryMediaEntity
    {
        $this->media = $media;
        return $this;
    }

    public function getInquiry(): ?InquiryEntity
    {
        return $this->inquiry;
    }

    public function setInquiry(?InquiryEntity $inquiry): InquiryMediaEntity
    {
        $this->inquiry = $inquiry;
        return $this;
    }

}
