<?php declare(strict_types=1);

namespace Shopware\Core\Content\Product\DataAbstractionLayer\Indexing;

use Doctrine\DBAL\Connection;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Dbal\Common\IteratorFactory;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenContainerEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Indexing\IndexerInterface;
use Shopware\Core\Framework\Event\ProgressAdvancedEvent;
use Shopware\Core\Framework\Event\ProgressFinishedEvent;
use Shopware\Core\Framework\Event\ProgressStartedEvent;
use Shopware\Core\Framework\Uuid\Uuid;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class AvailableStockIndexer implements IndexerInterface
{
    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var IteratorFactory
     */
    private $iteratorFactory;

    /**
     * @var ProductDefinition
     */
    private $definition;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    public function __construct(
        Connection $connection,
        IteratorFactory $iteratorFactory,
        ProductDefinition $definition,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->connection = $connection;
        $this->iteratorFactory = $iteratorFactory;
        $this->definition = $definition;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function index(\DateTimeInterface $timestamp): void
    {
        $context = Context::createDefaultContext();
        $iterator = $this->iteratorFactory->createIterator($this->definition);

        $this->eventDispatcher->dispatch(
            new ProgressStartedEvent('Start indexing product availability', $iterator->fetchCount()),
            ProgressStartedEvent::NAME
        );

        while ($ids = $iterator->fetch()) {
            $this->update($ids, $context);

            $this->eventDispatcher->dispatch(
                new ProgressAdvancedEvent(\count($ids)),
                ProgressAdvancedEvent::NAME
            );
        }

        $this->eventDispatcher->dispatch(
            new ProgressFinishedEvent('Finished indexing product availability'),
            ProgressFinishedEvent::NAME
        );
    }

    public function refresh(EntityWrittenContainerEvent $event): void
    {
        $products = $event->getEventByDefinition(ProductDefinition::class);

        $ids = [];
        if ($products) {
            $ids = $products->getIds();
        }

        $this->update($ids, $event->getContext());
    }

    private function update(array $ids, Context $context): void
    {
        if (empty($ids)) {
            return;
        }

        $bytes = Uuid::fromHexToBytesList($ids);

        $sql = '
UPDATE product SET available_stock = stock - (
	SELECT IFNULL(SUM(order_delivery_position.quantity), 0)

	FROM order_delivery_position
		INNER JOIN order_line_item 
			ON order_delivery_position.order_line_item_id = order_line_item.id
			AND order_delivery_position.order_line_item_version_id = order_line_item.version_id
			AND order_line_item.type = :type
	        AND order_line_item.version_id = :version

		INNER JOIN order_delivery
			ON order_delivery.id = order_delivery_position.order_delivery_id
			AND order_delivery.version_id = order_delivery_position.order_delivery_version_id

		INNER JOIN state_machine_state
			ON state_machine_state.id = order_delivery.state_id
			AND state_machine_state.technical_name != :shipped

	WHERE UNHEX(order_line_item.referenced_id) = product.id
) 
WHERE product.id IN (:ids);
        ';

        $this->connection->executeUpdate(
            $sql,
            [
                'type' => LineItem::PRODUCT_LINE_ITEM_TYPE,
                'version' => Uuid::fromHexToBytes($context->getVersionId()),
                'shipped' => 'shipped',
                'ids' => $bytes,
            ],
            [
                'ids' => Connection::PARAM_STR_ARRAY,
            ]
        );

        $sql = '
            UPDATE product
            LEFT JOIN product parent 
                ON parent.id = product.parent_id AND parent.version_id = product.version_id
            
            SET product.available = (
                IFNULL(product.is_closeout, parent.is_closeout) * product.available_stock 
                >=
                IFNULL(product.is_closeout, parent.is_closeout) * IFNULL(product.min_purchase, parent.min_purchase)
            )
            WHERE product.id IN (:ids)
            AND product.version_id = :version
        ';

        $this->connection->executeUpdate(
            $sql,
            ['ids' => $bytes, 'version' => Uuid::fromHexToBytes($context->getVersionId())],
            ['ids' => Connection::PARAM_STR_ARRAY]
        );
    }
}