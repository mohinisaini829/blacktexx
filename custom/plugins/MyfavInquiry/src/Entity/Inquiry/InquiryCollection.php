<?php
declare(strict_types=1);

namespace Myfav\Inquiry\Entity\Inquiry;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
/**
 * @method void             add(InquiryEntity $entity)
 * @method void             set(string $key, InquiryEntity $entity)
 * @method InquiryEntity[]    getIterator()
 * @method InquiryEntity[]    getElements()
 * @method InquiryEntity|null get(string $key)
 * @method InquiryEntity|null first()
 * @method InquiryEntity|null last()
 */
class InquiryCollection extends EntityCollection
{
    public function getExpectedClass() : string
    {
        return InquiryEntity::class;
    }
}
