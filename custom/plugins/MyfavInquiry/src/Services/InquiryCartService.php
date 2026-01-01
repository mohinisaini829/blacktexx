<?php declare(strict_types=1);

namespace Myfav\Inquiry\Services;

use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Metric\CountAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\AggregationResult\Metric\CountResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Myfav\Inquiry\Entity\InquiryCartEntry\InquiryCartEntryCollection;

class InquiryCartService
{
    private EntityRepository $inquiryCartEntryRepository;

    public function __construct(EntityRepository $inquiryCartEntryRepository)
    {
        $this->inquiryCartEntryRepository = $inquiryCartEntryRepository;
    }

    public function add(array $lines, SalesChannelContext $salesChannelContext): void
    {
        $lines = array_filter($lines, static fn(array $line) => $line['quantity'] > 0);
        $productIds = array_column($lines, 'productId');

        $criteria = (new Criteria())
            ->addFilter(
                new EqualsFilter('token', $salesChannelContext->getToken()),
                new EqualsAnyFilter('productId', $productIds)
            )
            ->addAssociation('product');

        /** @var InquiryCartEntryCollection $existingLines */
        $existingLines = $this->inquiryCartEntryRepository
            ->search($criteria, $salesChannelContext->getContext())
            ->getEntities();

        $upsertData = [];
        foreach ($lines as $line) {
            $existing = $existingLines->filterByProperty('productId', $line['productId'])->first();
            $data = [
                'productId' => $line['productId'],
                'quantity' => $line['quantity'],
                'token' => $salesChannelContext->getToken()
            ];
            if ($existing) {
                $data['quantity'] += $existing->getQuantity();
                $data['id'] = $existing->getId();
            }
            $upsertData[] = $data;
        }

        $this->inquiryCartEntryRepository->upsert($upsertData, $salesChannelContext->getContext());
    }

    public function addCustomProduct(array $data, SalesChannelContext $salesChannelContext): void
    {
        $customIdentifier = $data['customIdentifier'];
        $criteria = (new Criteria())
            ->addFilter(
                new EqualsFilter('token', $salesChannelContext->getToken()),
                new EqualsFilter('customIdentifier', $customIdentifier)
            );

        /** @var InquiryCartEntryCollection $existingLines */
        $existingLines = $this->inquiryCartEntryRepository
            ->search($criteria, $salesChannelContext->getContext())
            ->getEntities();

        $entryData = [
            'productId' => null,
            'productVersionId' => null,
            'customIdentifier' => $customIdentifier,
            'extendedData' => json_encode($data['extendedData']),
            'token' => $salesChannelContext->getToken(),
            'quantity' => (int) $data['quantity']
        ];

        if ($existing = $existingLines->first()) {
            $entryData['quantity'] += $existing->getQuantity();
            $entryData['id'] = $existing->getId();
        }

        $this->inquiryCartEntryRepository->upsert([$entryData], $salesChannelContext->getContext());
    }

    public function count(SalesChannelContext $salesChannelContext): int
    {
        $criteria = (new Criteria())
            ->addFilter(new EqualsFilter('token', $salesChannelContext->getToken()))
            ->addAggregation(new CountAggregation('count-products', 'productId'));

        /** @var CountResult $aggregation */
        $aggregation = $this->inquiryCartEntryRepository
            ->aggregate($criteria, $salesChannelContext->getContext())
            ->get('count-products');

        return $aggregation->getCount();
    }

    public function delete(string $id, SalesChannelContext $salesChannelContext): void
    {
        if ($this->checkEntryAccessBySalesChannelContext($id, $salesChannelContext)) {
            $this->inquiryCartEntryRepository->delete([['id' => $id]], $salesChannelContext->getContext());
        }
    }

    public function getList(SalesChannelContext $salesChannelContext): InquiryCartEntryCollection
    {
        return $this->inquiryCartEntryRepository
            ->search(
                (new Criteria())->addFilter(
                    new EqualsFilter('token', $salesChannelContext->getToken())
                ),
                $salesChannelContext->getContext()
            )->getEntities();
    }

    public function changeQuantity(string $id, int $quantity, SalesChannelContext $salesChannelContext): void
    {
        if ($this->checkEntryAccessBySalesChannelContext($id, $salesChannelContext)) {
            $quantity = max(1, $quantity);
            $this->inquiryCartEntryRepository->update(
                [['id' => $id, 'quantity' => $quantity]],
                $salesChannelContext->getContext()
            );
        }
    }

    public function clear(SalesChannelContext $salesChannelContext): void
    {
        $ids = $this->inquiryCartEntryRepository->searchIds(
            (new Criteria())->addFilter(
                new EqualsFilter('token', $salesChannelContext->getToken())
            ),
            $salesChannelContext->getContext()
        )->getIds();

        $deleteData = array_map(fn(string $id) => ['id' => $id], $ids);
        $this->inquiryCartEntryRepository->delete($deleteData, $salesChannelContext->getContext());
    }

    protected function checkEntryAccessBySalesChannelContext(string $id, SalesChannelContext $salesChannelContext): bool
    {
        $searchedId = $this->inquiryCartEntryRepository->searchIds(
            (new Criteria([$id]))->addFilter(
                new EqualsFilter('token', $salesChannelContext->getToken())
            ),
            $salesChannelContext->getContext()
        )->firstId();

        return $searchedId === $id;
    }
}
