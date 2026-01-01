<?php
declare(strict_types=1);

namespace Myfav\Inquiry\Storefront\Page\InquiryConfirm;

use Shopware\Core\System\Salutation\SalutationCollection;
use Shopware\Storefront\Page\Page;
use Myfav\Inquiry\Entity\InquiryCartEntry\InquiryCartEntryCollection;

class InquiryConfirmPage extends Page
{
    protected InquiryCartEntryCollection $inquiryCartEntryCollection;
    protected SalutationCollection $salutations;

    public function getInquiryCartEntryCollection(): InquiryCartEntryCollection
    {
        return $this->inquiryCartEntryCollection;
    }

    public function setInquiryCartEntryCollection(InquiryCartEntryCollection $inquiryCartEntryCollection): InquiryConfirmPage
    {
        $this->inquiryCartEntryCollection = $inquiryCartEntryCollection;
        return $this;
    }

    public function getSalutations(): SalutationCollection
    {
        return $this->salutations;
    }

    public function setSalutations(SalutationCollection $salutations): InquiryConfirmPage
    {
        $this->salutations = $salutations;
        return $this;
    }

}
