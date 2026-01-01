<?php declare(strict_types=1);

namespace Myfav\Zweideh\Services;

use Doctrine\DBAL\Connection;
use Shopware\Core\Checkout\Customer\CustomerEntity;

/**
 * The tmp_cart is used through the order.
 * It is the design, that is already used from a not logged in state,
 * and then handed over to a logged in state automatically,
 * as soon, as the user logs in.
 */
class ShopwareDesignsService
{
    private Connection $connection;

    /**
     * __construct
     * @param  Connection $connection
     *
     * @return void
     */
    public function __construct(
        Connection $connection
    ) {
        $this->connection = $connection;
    }
        
    /**
     * Load entry from database.
     *
     * @param  string $key
     * @param  string $tmp_cart_id
     * @return array
     */
    public function load(string $key, string $tmp_cart_id): array|null 
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->select('*');
        $queryBuilder->from('lumise_shopware_designs', 'l');
        $queryBuilder->where('l.key = ? AND l.tmp_cart_id = ?');
        $queryBuilder->setParameter(0, $key);
        $queryBuilder->setParameter(1, $tmp_cart_id);
        $result = $queryBuilder->execute()->fetchAll();

        if (!is_array($result) || count($result) == 0) {
            return null;
        }

        $result = $result[0];
        return $result;
    }
    
    /**
     * loadDesignsByCustomerId
     *
     * @param  mixed $shopwareUserId
     * @return ?array
     */
    public function loadDesignsByCustomerId($index, $limit, $shopwareUserId): ?array
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->select('*');
        $queryBuilder->from('lumise_shopware_designs', 'l');
        $queryBuilder->where('shopware_user_id = :shopwareUserId');
        $queryBuilder->setParameter('shopwareUserId', hex2bin($shopwareUserId));
        $queryBuilder->setFirstResult($index);
        $queryBuilder->setMaxResults($limit);
        $queryBuilder->orderBy('l.created', 'DESC');
        $result = $queryBuilder->executeQuery()->fetchAllAssociative();

        return $result;
    }
    
    /**
     * verifyCustomer
     *
     * @param  array $tmpCart
     * @param  CustomerEntity $customer
     * @return void
     */
    public function verifyCustomer(array $tmpCart, CustomerEntity $customer): void
    {
        $customerId = $customer->getId();
        $tmpCustomerId = $tmpCart['shopware_user_id'];

        if($tmpCustomerId === null) {
            $this->saveCustomerIdOnTmpCart($tmpCart, $customerId);
            return;
        }

        $tmpCustomerId = bin2hex($tmpCustomerId);

        if($customerId === null) {
            throw new \Exception('No customer given');
        }

        if($customerId !== $tmpCustomerId) {
            throw new \Exception('This cart does not belong to this user.');
        }
    }
    
    /**
     * saveCustomerIdOnTmpCart
     *
     * @param  array $tmpCart
     * @param  string $customerId
     * @return void
     */
    public function saveCustomerIdOnTmpCart(array $tmpCart, $customerId): void
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->update('lumise_shopware_designs', 'l');
        $queryBuilder->set('shopware_user_id', ':shopwareUserId');
        $queryBuilder->where('l.id = :id');
        $queryBuilder->setParameter('shopwareUserId', hex2bin($customerId));
        $queryBuilder->setParameter('id', $tmpCart['id']);
        $result = $queryBuilder->execute();
    }
}