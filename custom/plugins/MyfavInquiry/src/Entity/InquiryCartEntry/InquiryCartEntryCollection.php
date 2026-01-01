<?php
declare(strict_types=1);

namespace Myfav\Inquiry\Entity\InquiryCartEntry;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
/**
 * @method void             add(InquiryCartEntryEntity $entity)
 * @method void             set(string $key, InquiryCartEntryEntity $entity)
 * @method InquiryCartEntryEntity[]    getIterator()
 * @method InquiryCartEntryEntity[]    getElements()
 * @method InquiryCartEntryEntity|null get(string $key)
 * @method InquiryCartEntryEntity|null first()
 * @method InquiryCartEntryEntity|null last()
 */
class InquiryCartEntryCollection extends EntityCollection
{
    public function getExpectedClass() : string
    {
        return InquiryCartEntryEntity::class;
    }
}
