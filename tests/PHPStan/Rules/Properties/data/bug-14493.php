<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug14493NullsafeProperty;

class OrderEntity {
	public function getOrderCustomer(): ?OrderCustomerEntity
	{
		return new OrderCustomerEntity();
	}
}

class OrderCustomerEntity {
	public ?CustomerEntity $customer = null;
	/** @var array<string>|null */
	public ?array $vatIds = null;
}

class CustomerEntity {
	public const ACCOUNT_TYPE_BUSINESS = 'business';

	public string $accountType = '';
}


abstract class AbstractDocumentRenderer
{
    protected function doFoo(OrderEntity $order): bool
    {
        $customerType = $order->getOrderCustomer()?->customer?->accountType;
        if ($customerType !== CustomerEntity::ACCOUNT_TYPE_BUSINESS) {
            return false;
        }

        $vatIds = $order->getOrderCustomer()?->vatIds;
        if (!is_array($vatIds)) {
            return false;
        }

        return true;
    }
}
