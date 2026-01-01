<?php
declare(strict_types=1);

namespace Myfav\Inquiry\Entity\Inquiry\Aggregate\InquiryLineItem;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void             add(InquiryLineItemEntity $entity)
 * @method void             set(string $key, InquiryLineItemEntity $entity)
 * @method InquiryLineItemEntity[]    getIterator()
 * @method InquiryLineItemEntity[]    getElements()
 * @method InquiryLineItemEntity|null get(string $key)
 * @method InquiryLineItemEntity|null first()
 * @method InquiryLineItemEntity|null last()
 */
class InquiryLineItemCollection extends EntityCollection
{
    public function getExpectedClass() : string
    {
        return InquiryLineItemEntity::class;
    }
}
