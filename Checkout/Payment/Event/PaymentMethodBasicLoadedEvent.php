<?php declare(strict_types=1);

namespace Shopware\Checkout\Payment\Event;

use Shopware\Framework\Context;
use Shopware\Checkout\Payment\Collection\PaymentMethodBasicCollection;
use Shopware\Framework\Event\NestedEvent;

class PaymentMethodBasicLoadedEvent extends NestedEvent
{
    public const NAME = 'payment_method.basic.loaded';

    /**
     * @var \Shopware\Framework\Context
     */
    protected $context;

    /**
     * @var PaymentMethodBasicCollection
     */
    protected $paymentMethods;

    public function __construct(PaymentMethodBasicCollection $paymentMethods, Context $context)
    {
        $this->context = $context;
        $this->paymentMethods = $paymentMethods;
    }

    public function getName(): string
    {
        return self::NAME;
    }

    public function getContext(): Context
    {
        return $this->context;
    }

    public function getPaymentMethods(): PaymentMethodBasicCollection
    {
        return $this->paymentMethods;
    }
}
