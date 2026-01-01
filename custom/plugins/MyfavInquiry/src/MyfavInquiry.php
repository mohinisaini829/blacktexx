<?php

declare(strict_types=1);

namespace Myfav\Inquiry;

use Shopware\Core\Framework\Plugin;

class MyfavInquiry extends Plugin
{
    public const OFFER_TYPE = 'myfav_inquiry_offer';
    public const OFFER_NUMBER_RANGE = 'document_'.self::OFFER_TYPE;
}
