<?php
declare(strict_types=1);

namespace Myfav\Inquiry\Exception;

use Shopware\Core\Framework\ShopwareHttpException;
use Symfony\Component\HttpFoundation\Response;

class InquiryNotFoundException extends ShopwareHttpException
{

    /**
     * @var string
     */
    private $inquiryId;

    public function __construct(string $inquiryId)
    {
        parent::__construct(
            'Inquiry with id "{{ inquiryId }}" not found.',
            ['inquiryId' => $inquiryId]
        );

        $this->inquiryId = $inquiryId;
    }

    public function getErrorCode(): string
    {
        return 'CHECKOUT__INQUIRY_NOT_FOUND';
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_BAD_REQUEST;
    }

    public function getInquiryId(): string
    {
        return $this->inquiryId;
    }
}
