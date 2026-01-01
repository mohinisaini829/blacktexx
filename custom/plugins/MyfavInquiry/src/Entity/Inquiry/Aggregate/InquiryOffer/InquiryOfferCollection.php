<?php
declare(strict_types=1);

namespace Myfav\Inquiry\Entity\Inquiry\Aggregate\InquiryOffer;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
/**
 * @method void             add(InquiryOfferEntity $entity)
 * @method void             set(string $key, InquiryOfferEntity $entity)
 * @method InquiryOfferEntity[]    getIterator()
 * @method InquiryOfferEntity[]    getElements()
 * @method InquiryOfferEntity|null get(string $key)
 * @method InquiryOfferEntity|null first()
 * @method InquiryOfferEntity|null last()
 */
class InquiryOfferCollection extends EntityCollection
{
    public function getExpectedClass() : string
    {
        return InquiryOfferEntity::class;
    }
}
