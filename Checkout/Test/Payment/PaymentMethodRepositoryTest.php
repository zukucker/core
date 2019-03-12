<?php declare(strict_types=1);

namespace Shopware\Core\Checkout\Test\Payment;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Payment\PaymentMethodCollection;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Write\FieldException\WriteStackException;
use Shopware\Core\Framework\Struct\Uuid;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;

class PaymentMethodRepositoryTest extends TestCase
{
    use IntegrationTestBehaviour;

    /**
     * @var EntityRepositoryInterface
     */
    private $paymentRepository;

    /**
     * @var string
     */
    private $paymentMethodId;

    public function setUp(): void
    {
        $this->paymentRepository = $this->getContainer()->get('payment_method.repository');
        $this->paymentMethodId = Uuid::uuid4()->getHex();
    }

    public function testCreatePaymentMethod(): void
    {
        $defaultContext = Context::createDefaultContext();

        $paymentMethod = $this->createPaymentMethodDummyArray();

        $this->paymentRepository->create($paymentMethod, $defaultContext);

        $criteria = new Criteria([$this->paymentMethodId]);
        $criteria->addAssociation('availabilityRules');

        /** @var PaymentMethodCollection $resultSet */
        $resultSet = $this->paymentRepository->search($criteria, $defaultContext);

        static::assertSame($this->paymentMethodId, $resultSet->first()->getId());
        static::assertSame(
            $paymentMethod[0]['availabilityRules'][0]['id'],
            $resultSet->first()->getAvailabilityRules()->first()->getId()
        );
    }

    public function testUpdatePaymentMethod(): void
    {
        $defaultContext = Context::createDefaultContext();

        $paymentMethod = $this->createPaymentMethodDummyArray();

        $this->paymentRepository->create($paymentMethod, $defaultContext);

        $updateParameter = [
            'id' => $this->paymentMethodId,
            'availabilityRules' => [
                [
                    'id' => Uuid::uuid4()->getHex(),
                    'name' => 'test update',
                    'priority' => 5,
                    'created_at' => new \DateTime(),
                ],
            ],
        ];

        $this->paymentRepository->update([$updateParameter], $defaultContext);

        $criteria = new Criteria([$this->paymentMethodId]);
        $criteria->addAssociation('availabilityRules');

        /** @var PaymentMethodCollection $resultSet */
        $resultSet = $this->paymentRepository->search($criteria, $defaultContext);

        static::assertCount(2, $resultSet->first()->getAvailabilityRules());
    }

    public function testPaymentMethodCanBeDeleted(): void
    {
        $defaultContext = Context::createDefaultContext();

        $paymentMethod = $this->createPaymentMethodDummyArray();

        $this->paymentRepository->create($paymentMethod, $defaultContext);

        $primaryKey = [
            'id' => $this->paymentMethodId,
        ];

        $this->paymentRepository->delete([$primaryKey], $defaultContext);

        $criteria = new Criteria([$this->paymentMethodId]);

        /** @var PaymentMethodCollection $resultSet */
        $resultSet = $this->paymentRepository->search($criteria, $defaultContext);

        static::assertCount(0, $resultSet);
    }

    public function testThrowsExceptionIfNotAllRequiredValuesAreGiven(): void
    {
        $defaultContext = Context::createDefaultContext();
        $paymentMethod = $this->createPaymentMethodDummyArray();

        unset($paymentMethod[0]['name']);

        try {
            $this->paymentRepository->create($paymentMethod, $defaultContext);

            static::fail('The name should always be required!');
        } catch (WriteStackException $e) {
            static::assertStringContainsString('[propertyPath] => name', $e->getMessage());
            static::assertStringContainsString('[message] => This value should not be blank.', $e->getMessage());
        }
    }

    private function createPaymentMethodDummyArray(): array
    {
        return [
            [
                'id' => $this->paymentMethodId,
                'name' => 'test',
                'technicalName' => 'techTest',
                'availabilityRules' => [
                    [
                        'id' => Uuid::uuid4()->getHex(),
                        'name' => 'asd',
                        'priority' => 2,
                    ],
                ],
            ],
        ];
    }
}
