<?php declare(strict_types=1);

namespace Shopware\Checkout\Shipping\Event;

use Shopware\Framework\Context;
use Shopware\Checkout\Shipping\Struct\ShippingMethodSearchResult;
use Shopware\Framework\Event\NestedEvent;

class ShippingMethodSearchResultLoadedEvent extends NestedEvent
{
    public const NAME = 'shipping_method.search.result.loaded';

    /**
     * @var ShippingMethodSearchResult
     */
    protected $result;

    public function __construct(ShippingMethodSearchResult $result)
    {
        $this->result = $result;
    }

    public function getName(): string
    {
        return self::NAME;
    }

    public function getContext(): Context
    {
        return $this->result->getContext();
    }
}
