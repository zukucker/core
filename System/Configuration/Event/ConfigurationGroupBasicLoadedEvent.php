<?php declare(strict_types=1);

namespace Shopware\System\Configuration\Event;

use Shopware\Framework\Context;
use Shopware\Framework\Event\NestedEvent;
use Shopware\System\Configuration\Collection\ConfigurationGroupBasicCollection;

class ConfigurationGroupBasicLoadedEvent extends NestedEvent
{
    public const NAME = 'configuration_group.basic.loaded';

    /**
     * @var \Shopware\Framework\Context
     */
    protected $context;

    /**
     * @var ConfigurationGroupBasicCollection
     */
    protected $configurationGroups;

    public function __construct(ConfigurationGroupBasicCollection $configurationGroups, Context $context)
    {
        $this->context = $context;
        $this->configurationGroups = $configurationGroups;
    }

    public function getName(): string
    {
        return self::NAME;
    }

    public function getContext(): Context
    {
        return $this->context;
    }

    public function getConfigurationGroups(): ConfigurationGroupBasicCollection
    {
        return $this->configurationGroups;
    }
}
