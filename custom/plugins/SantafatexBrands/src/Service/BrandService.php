<?php declare(strict_types=1);

namespace Santafatex\Brands\Service;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\Uuid\Uuid;

class BrandService
{
    private EntityRepository $brandRepository;

    public function __construct(EntityRepository $brandRepository)
    {
        $this->brandRepository = $brandRepository;
    }

    public function createBrand(array $data, Context $context): string
    {
        $data['id'] = Uuid::randomHex();

        $this->brandRepository->create([$data], $context);

        return $data['id'];
    }

    public function updateBrand(string $brandId, array $data, Context $context): void
    {
        $this->brandRepository->update([array_merge(['id' => $brandId], $data)], $context);
    }

    public function deleteBrand(string $brandId, Context $context): void
    {
        $this->brandRepository->delete([['id' => $brandId]], $context);
    }

    public function getBrand(string $brandId, Context $context)
    {
        $criteria = new Criteria([$brandId]);
        return $this->brandRepository->search($criteria, $context)->first();
    }

    public function getAllBrands(Context $context, ?int $limit = null, ?int $offset = null)
    {
        $criteria = new Criteria();
        $criteria->addSorting(new FieldSorting('displayOrder', FieldSorting::ASCENDING));
        $criteria->addSorting(new FieldSorting('name', FieldSorting::ASCENDING));

        if ($limit) {
            $criteria->setLimit($limit);
        }
        if ($offset) {
            $criteria->setOffset($offset);
        }

        return $this->brandRepository->search($criteria, $context);
    }

    public function getActiveBrands(Context $context)
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('active', true));
        $criteria->addSorting(new FieldSorting('displayOrder', FieldSorting::ASCENDING));

        return $this->brandRepository->search($criteria, $context);
    }
}
