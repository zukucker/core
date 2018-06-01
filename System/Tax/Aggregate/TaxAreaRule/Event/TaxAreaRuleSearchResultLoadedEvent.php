<?php declare(strict_types=1);

namespace Shopware\System\Tax\Aggregate\TaxAreaRule\Event;

use Shopware\Framework\Context;
use Shopware\Framework\Event\NestedEvent;
use Shopware\System\Tax\Aggregate\TaxAreaRule\Struct\TaxAreaRuleSearchResult;

class TaxAreaRuleSearchResultLoadedEvent extends NestedEvent
{
    public const NAME = 'tax_area_rule.search.result.loaded';

    /**
     * @var TaxAreaRuleSearchResult
     */
    protected $result;

    public function __construct(TaxAreaRuleSearchResult $result)
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
