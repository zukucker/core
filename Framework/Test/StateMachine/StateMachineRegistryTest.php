<?php declare(strict_types=1);

namespace Shopware\Core\Framework\Test\StateMachine;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Write\EntityWriter;
use Shopware\Core\Framework\DataAbstractionLayer\Write\WriteContext;
use Shopware\Core\Framework\StateMachine\StateMachineNotFoundException;
use Shopware\Core\Framework\StateMachine\StateMachineRegistry;
use Shopware\Core\Framework\Struct\Uuid;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Shopware\Core\System\StateMachine\StateMachineDefinition;

class StateMachineRegistryTest extends TestCase
{
    use KernelTestBehaviour;

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var string
     */
    private $stateMachineId;

    /**
     * @var string
     */
    private $openId;

    /**
     * @var string
     */
    private $inProgressId;

    /**
     * @var string
     */
    private $closedId;

    /**
     * @var string
     */
    private $stateMachineName;

    /**
     * @var StateMachineRegistry
     */
    private $stateMachineRegistry;

    /**
     * @var EntityWriter
     */
    private $writer;

    public function setUp()
    {
        $this->connection = $this->getContainer()->get(Connection::class);
        $this->stateMachineRegistry = $this->getContainer()->get(StateMachineRegistry::class);
        $this->writer = $this->getContainer()->get(EntityWriter::class);

        $this->stateMachineName = 'test_state_machine';
        $this->stateMachineId = Uuid::uuid4()->getHex();
        $this->openId = Uuid::uuid4()->getHex();
        $this->inProgressId = Uuid::uuid4()->getHex();
        $this->closedId = Uuid::uuid4()->getHex();

        $nullableTable = <<<EOF
DROP TABLE IF EXISTS _test_nullable;
CREATE TABLE `_test_nullable` (
  `id` varbinary(16) NOT NULL,
  `state` varchar(255) NULL,
  PRIMARY KEY `id` (`id`)
);
EOF;
        $this->connection->executeUpdate($nullableTable);
        $this->connection->beginTransaction();
    }

    public function tearDown(): void
    {
        $this->connection->rollBack();
        $this->connection->executeUpdate('DROP TABLE `_test_nullable`');
    }

    public function testNonExistingStateMachine(): void
    {
        $this->expectException(StateMachineNotFoundException::class);

        $context = Context::createDefaultContext();

        $this->stateMachineRegistry->getStateMachine('wusel', $context);
    }

    public function testStateMachineShouldIncludeRelations(): void
    {
        $context = Context::createDefaultContext();
        $this->createStateMachine($context);

        $stateMachine = $this->stateMachineRegistry->getStateMachine($this->stateMachineName, $context);

        $this->assertNotNull($stateMachine);
        $this->assertNotNull($stateMachine->getStates());
        $this->assertEquals(3, $stateMachine->getStates()->count());
        $this->assertNotNull($stateMachine->getTransitions());
        $this->assertEquals(4, $stateMachine->getTransitions()->count());
    }

    private function createStateMachine(Context $context): void
    {
        $writeContext = WriteContext::createFromContext($context);

        $this->writer->insert(StateMachineDefinition::class, [
            [
                'id' => $this->stateMachineId,
                'technicalName' => $this->stateMachineName,
                'translations' => [
                    'en_GB' => ['name' => 'Order state'],
                    'de_DE' => ['name' => 'Bestellungsstatus'],
                ],
                'states' => [
                    ['id' => $this->openId, 'technicalName' => 'open', 'name' => 'Open'],
                    ['id' => $this->inProgressId, 'technicalName' => 'in_progress', 'name' => 'In progress'],
                    ['id' => $this->closedId, 'technicalName' => 'closed', 'name' => 'Closed'],
                ],
                'transitions' => [
                    ['actionName' => 'start', 'fromStateId' => $this->openId, 'toStateId' => $this->inProgressId],

                    ['actionName' => 'reopen', 'fromStateId' => $this->inProgressId, 'toStateId' => $this->openId],
                    ['actionName' => 'close', 'fromStateId' => $this->inProgressId, 'toStateId' => $this->closedId],

                    ['actionName' => 'reopen', 'fromStateId' => $this->closedId, 'toStateId' => $this->openId],
                ],
            ],
        ], $writeContext);
    }
}