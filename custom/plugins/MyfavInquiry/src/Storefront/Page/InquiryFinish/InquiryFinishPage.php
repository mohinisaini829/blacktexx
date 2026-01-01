<?php
declare(strict_types=1);

namespace Myfav\Inquiry\Storefront\Page\InquiryFinish;

use Shopware\Storefront\Page\Page;
use Myfav\Inquiry\Entity\Inquiry\InquiryEntity;

class InquiryFinishPage extends Page
{
    protected bool $status = true;
    protected InquiryEntity $inquiry;

    public function getInquiry(): InquiryEntity
    {
        return $this->inquiry;
    }

    public function setInquiry(InquiryEntity $inquiry): InquiryFinishPage
    {
        $this->inquiry = $inquiry;
        return $this;
    }

    public function setStatus(bool $status) {
        $this->status = $status;
        return $status;
    }
}
