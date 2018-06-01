<?php declare(strict_types=1);

namespace Shopware\Framework\ORM\Version\Event\VersionCommit;

use Shopware\Framework\Context;
use Shopware\Framework\Event\NestedEvent;
use Shopware\Framework\ORM\Version\Collection\VersionCommitBasicCollection;

class VersionCommitBasicLoadedEvent extends NestedEvent
{
    public const NAME = 'version_commit.basic.loaded';

    /**
     * @var Context
     */
    protected $context;

    /**
     * @var VersionCommitBasicCollection
     */
    protected $versionCommits;

    public function __construct(VersionCommitBasicCollection $versionCommits, Context $context)
    {
        $this->context = $context;
        $this->versionCommits = $versionCommits;
    }

    public function getName(): string
    {
        return self::NAME;
    }

    public function getContext(): Context
    {
        return $this->context;
    }

    public function getVersionCommits(): VersionCommitBasicCollection
    {
        return $this->versionCommits;
    }
}
