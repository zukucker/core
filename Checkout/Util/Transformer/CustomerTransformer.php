<?php declare(strict_types=1);

namespace Shopware\Core\Checkout\Util\Transformer;

use Shopware\Core\Checkout\Customer\CustomerCollection;
use Shopware\Core\Checkout\Customer\CustomerEntity;

class CustomerTransformer
{
    public function transformCollection(CustomerCollection $customers, bool $useIdAsKey = false): array
    {
        $output = [];
        /** @var CustomerEntity $customer */
        foreach ($customers as $customer) {
            $output[$customer->getId()] = self::transform($customer);
        }

        if (!$useIdAsKey) {
            $output = array_values($output);
        }

        return $output;
    }

    public function transform(CustomerEntity $customer): array
    {
        return [
            'customerId' => $customer->getId(),
            'email' => $customer->getEmail(),
            'firstName' => $customer->getFirstName(),
            'lastName' => $customer->getLastName(),
            'salutation' => $customer->getSalutation(),
            'title' => $customer->getTitle(),
            'customerNumber' => $customer->getCustomerNumber(),
        ];
    }
}