<?php declare(strict_types=1);

namespace Shopware\Checkout\Order\Aggregate\OrderTransactionStateTranslation;

use Shopware\Framework\Context;
use Shopware\Checkout\Order\Aggregate\OrderTransactionStateTranslation\Collection\OrderTransactionStateTranslationBasicCollection;
use Shopware\Checkout\Order\Aggregate\OrderTransactionStateTranslation\Event\OrderTransactionStateTranslationAggregationResultLoadedEvent;
use Shopware\Checkout\Order\Aggregate\OrderTransactionStateTranslation\Event\OrderTransactionStateTranslationBasicLoadedEvent;
use Shopware\Checkout\Order\Aggregate\OrderTransactionStateTranslation\Event\OrderTransactionStateTranslationIdSearchResultLoadedEvent;
use Shopware\Checkout\Order\Aggregate\OrderTransactionStateTranslation\Event\OrderTransactionStateTranslationSearchResultLoadedEvent;
use Shopware\Checkout\Order\Aggregate\OrderTransactionStateTranslation\Struct\OrderTransactionStateTranslationSearchResult;
use Shopware\Framework\ORM\Read\EntityReaderInterface;
use Shopware\Framework\ORM\RepositoryInterface;
use Shopware\Framework\ORM\Search\AggregatorResult;
use Shopware\Framework\ORM\Search\Criteria;
use Shopware\Framework\ORM\Search\EntityAggregatorInterface;
use Shopware\Framework\ORM\Search\EntitySearcherInterface;
use Shopware\Framework\ORM\Search\IdSearchResult;
use Shopware\Framework\ORM\Version\Service\VersionManager;
use Shopware\Framework\ORM\Write\GenericWrittenEvent;
use Shopware\Framework\ORM\Write\WriteContext;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class OrderTransactionStateTranslationRepository implements RepositoryInterface
{
    /**
     * @var EntityReaderInterface
     */
    private $reader;

    /**
     * @var EntitySearcherInterface
     */
    private $searcher;

    /**
     * @var EntityAggregatorInterface
     */
    private $aggregator;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var VersionManager
     */
    private $versionManager;

    public function __construct(
        EntityReaderInterface $reader,
        VersionManager $versionManager,
        EntitySearcherInterface $searcher,
        EntityAggregatorInterface $aggregator,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->reader = $reader;
        $this->searcher = $searcher;
        $this->aggregator = $aggregator;
        $this->eventDispatcher = $eventDispatcher;
        $this->versionManager = $versionManager;
    }

    public function search(Criteria $criteria, Context $context): OrderTransactionStateTranslationSearchResult
    {
        $ids = $this->searchIds($criteria, $context);

        $entities = $this->readBasic($ids->getIds(), $context);

        $aggregations = null;
        if ($criteria->getAggregations()) {
            $aggregations = $this->aggregate($criteria, $context);
        }

        $result = OrderTransactionStateTranslationSearchResult::createFromResults($ids, $entities, $aggregations);

        $event = new OrderTransactionStateTranslationSearchResultLoadedEvent($result);
        $this->eventDispatcher->dispatch($event->getName(), $event);

        return $result;
    }

    public function aggregate(Criteria $criteria, Context $context): AggregatorResult
    {
        $result = $this->aggregator->aggregate(OrderTransactionStateTranslationDefinition::class, $criteria, $context);

        $event = new OrderTransactionStateTranslationAggregationResultLoadedEvent($result);
        $this->eventDispatcher->dispatch($event->getName(), $event);

        return $result;
    }

    public function searchIds(Criteria $criteria, Context $context): IdSearchResult
    {
        $result = $this->searcher->search(OrderTransactionStateTranslationDefinition::class, $criteria, $context);

        $event = new OrderTransactionStateTranslationIdSearchResultLoadedEvent($result);
        $this->eventDispatcher->dispatch($event->getName(), $event);

        return $result;
    }

    public function readBasic(array $ids, Context $context): OrderTransactionStateTranslationBasicCollection
    {
        /** @var \Shopware\Checkout\Order\Aggregate\OrderTransactionStateTranslation\Collection\OrderTransactionStateTranslationBasicCollection $entities */
        $entities = $this->reader->readBasic(OrderTransactionStateTranslationDefinition::class, $ids, $context);

        $event = new OrderTransactionStateTranslationBasicLoadedEvent($entities, $context);
        $this->eventDispatcher->dispatch($event->getName(), $event);

        return $entities;
    }

    public function readDetail(array $ids, Context $context): OrderTransactionStateTranslationBasicCollection
    {
        return $this->readBasic($ids, $context);
    }

    public function update(array $data, Context $context): GenericWrittenEvent
    {
        $affected = $this->versionManager->update(OrderTransactionStateTranslationDefinition::class, $data, WriteContext::createFromContext($context));
        $event = GenericWrittenEvent::createWithWrittenEvents($affected, $context, []);
        $this->eventDispatcher->dispatch(GenericWrittenEvent::NAME, $event);

        return $event;
    }

    public function upsert(array $data, Context $context): GenericWrittenEvent
    {
        $affected = $this->versionManager->upsert(OrderTransactionStateTranslationDefinition::class, $data, WriteContext::createFromContext($context));
        $event = GenericWrittenEvent::createWithWrittenEvents($affected, $context, []);
        $this->eventDispatcher->dispatch(GenericWrittenEvent::NAME, $event);

        return $event;
    }

    public function create(array $data, Context $context): GenericWrittenEvent
    {
        $affected = $this->versionManager->insert(OrderTransactionStateTranslationDefinition::class, $data, WriteContext::createFromContext($context));
        $event = GenericWrittenEvent::createWithWrittenEvents($affected, $context, []);
        $this->eventDispatcher->dispatch(GenericWrittenEvent::NAME, $event);

        return $event;
    }

    public function delete(array $ids, Context $context): GenericWrittenEvent
    {
        $affected = $this->versionManager->delete(OrderTransactionStateTranslationDefinition::class, $ids, WriteContext::createFromContext($context));
        $event = GenericWrittenEvent::createWithDeletedEvents($affected, $context, []);
        $this->eventDispatcher->dispatch(GenericWrittenEvent::NAME, $event);

        return $event;
    }

    public function createVersion(string $id, Context $context, ?string $name = null, ?string $versionId = null): string
    {
        return $this->versionManager->createVersion(OrderTransactionStateTranslationDefinition::class, $id, WriteContext::createFromContext($context), $name, $versionId);
    }

    public function merge(string $versionId, Context $context): void
    {
        $this->versionManager->merge($versionId, WriteContext::createFromContext($context));
    }
}
