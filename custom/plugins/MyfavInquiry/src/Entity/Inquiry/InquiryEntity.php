<?php
declare(strict_types=1);

namespace Myfav\Inquiry\Entity\Inquiry;

use DateTime;
use DateTimeInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Shopware\Core\System\Salutation\SalutationEntity;
use Myfav\Inquiry\Entity\Inquiry\Aggregate\InquiryLineItem\InquiryLineItemCollection;
use Myfav\Inquiry\Entity\Inquiry\Aggregate\InquiryMedia\InquiryMediaCollection;
use Myfav\Inquiry\Entity\Inquiry\Aggregate\InquiryOffer\InquiryOfferCollection;

class InquiryEntity extends Entity
{
    use EntityIdTrait;

    protected ?string $salutationId = null;

    protected ?string $customerId = null;

    protected ?string $salesChannelId = null;

    protected ?string $firstName = null;

    protected ?string $lastName = null;

    protected ?string $email = null;

    protected ?string $company = null;

    protected ?string $phoneNumber = null;

    protected ?DateTimeInterface $deliveryDate = null;

    protected ?string $comment = null;

    protected ?SalutationEntity $salutation = null;

    protected ?CustomerEntity $customer = null;

    protected ?SalesChannelEntity $salesChannel = null;

    protected ?InquiryLineItemCollection $lineItems = null;

    protected ?InquiryOfferCollection $offers = null;

    protected ?InquiryMediaCollection $medias = null;
    protected ?string $status = 'open';  // Add this property
    protected ?string $adminUser = null;

    // salutationId
    public function getSalutationId(): ?string
    {
        return $this->salutationId;
    }

    public function setSalutationId(?string $salutationId): InquiryEntity
    {
        $this->salutationId = $salutationId;
        return $this;
    }
    // Getter and Setter for status
    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): InquiryEntity
    {
        $this->status = $status;
        return $this;
    }
     // Getter and Setter for adminUser
    public function getAdminUser(): ?string
    {
        return $this->adminUser;
    }

    public function setAdminUser(?string $adminUser): InquiryEntity
    {
        $this->adminUser = $adminUser;
        return $this;
    }
    // Constructor to ensure 'status' is set to 'open' by default
    public function __construct()
    {
        $this->status = 'open'; // Default value for status when a new InquiryEntity is created
    }
    // customerId
    public function getCustomerId(): ?string
    {
        return $this->customerId;
    }

    public function setCustomerId(?string $customerId): InquiryEntity
    {
        $this->customerId = $customerId;
        return $this;
    }

    // salesChannelId
    public function getSalesChannelId(): ?string
    {
        return $this->salesChannelId;
    }

    public function setSalesChannelId(?string $salesChannelId): InquiryEntity
    {
        $this->salesChannelId = $salesChannelId;
        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(?string $firstName): InquiryEntity
    {
        $this->firstName = $firstName;
        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(?string $lastName): InquiryEntity
    {
        $this->lastName = $lastName;
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): InquiryEntity
    {
        $this->email = $email;
        return $this;
    }

    public function getCompany(): ?string
    {
        return $this->company;
    }

    public function setCompany(?string $company): InquiryEntity
    {
        $this->company = $company;
        return $this;
    }

    public function getPhoneNumber(): ?string
    {
        return $this->phoneNumber;
    }

    public function setPhoneNumber(?string $phoneNumber): InquiryEntity
    {
        $this->phoneNumber = $phoneNumber;
        return $this;
    }

    public function getDeliveryDate(): ?DateTimeInterface
    {
        return $this->deliveryDate;
    }

    public function setDeliveryDate(?DateTimeInterface $deliveryDate): InquiryEntity
    {
        $this->deliveryDate = $deliveryDate;
        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): InquiryEntity
    {
        $this->comment = $comment;
        return $this;
    }

    // salutation
    public function getSalutation(): ?SalutationEntity
    {
        return $this->salutation;
    }

    public function setSalutation(?SalutationEntity $salutation): InquiryEntity
    {
        $this->salutation = $salutation;
        return $this;
    }

    // customer
    public function getCustomer(): ?CustomerEntity
    {
        return $this->salutation;
    }

    public function setCustomer(?CustomerEntity $customer): InquiryEntity
    {
        $this->customer = $customer;
        return $this;
    }

    // salesChannel
    public function getSalesChannel(): ?SalesChannelEntity
    {
        return $this->salesChannel;
    }

    public function setSalesChannel(?SalesChannelEntity $salesChannel): InquiryEntity
    {
        $this->salesChannel = $salesChannel;
        return $this;
    }

    public function getLineItems(): ?InquiryLineItemCollection
    {
        return $this->lineItems;
    }

    public function setLineItems(?InquiryLineItemCollection $lineItems): InquiryEntity
    {
        $this->lineItems = $lineItems;
        return $this;
    }

    public function getMedias(): ?InquiryMediaCollection
    {
        return $this->medias;
    }

    public function setMedias(?InquiryMediaCollection $medias): InquiryEntity
    {
        $this->medias = $medias;
        return $this;
    }

    public function getOffers(): ?InquiryOfferCollection
    {
        return $this->offers;
    }

}
