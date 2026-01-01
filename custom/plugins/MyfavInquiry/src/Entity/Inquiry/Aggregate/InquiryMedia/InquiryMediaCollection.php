<?php
declare(strict_types=1);

namespace Myfav\Inquiry\Entity\Inquiry\Aggregate\InquiryMedia;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
/**
 * @method void             add(InquiryMediaEntity $entity)
 * @method void             set(string $key, InquiryMediaEntity $entity)
 * @method InquiryMediaEntity[]    getIterator()
 * @method InquiryMediaEntity[]    getElements()
 * @method InquiryMediaEntity|null get(string $key)
 * @method InquiryMediaEntity|null first()
 * @method InquiryMediaEntity|null last()
 */
class InquiryMediaCollection extends EntityCollection
{
    public function getExpectedClass() : string
    {
        return InquiryMediaEntity::class;
    }
}
