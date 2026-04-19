<?php // lint >= 8.0

declare(strict_types=1);

namespace Bug14493;

class OrderEntity {
	static public function getStaticOrderCustomer(): ?OrderCustomerEntity
	{
		return new OrderCustomerEntity();
	}

	public function getOrderCustomer(): ?OrderCustomerEntity
	{
		return new OrderCustomerEntity();
	}
}

class OrderCustomerEntity {
	public function getCustomer(): ?CustomerEntity { return null; }
	/** @return array<string>|null */
	public function getVatIds(): ?array { return null; }
}

class CustomerEntity {
	final public const ACCOUNT_TYPE_BUSINESS = 'business';

	public function getAccountType(): string { return ''; }
}


abstract class AbstractDocumentRenderer
{
    protected function doFoo(OrderEntity $order): bool
    {
        $customerType = $order->getOrderCustomer()?->getCustomer()?->getAccountType();
        if ($customerType !== CustomerEntity::ACCOUNT_TYPE_BUSINESS) {
            return false;
        }

        $vatIds = $order->getOrderCustomer()?->getVatIds();
        if (!is_array($vatIds)) {
            return false;
        }

        return true;
    }

	protected function doBar(OrderEntity $order): bool
	{
		$customerType = $order::getStaticOrderCustomer()?->getCustomer()?->getAccountType();
		if ($customerType !== CustomerEntity::ACCOUNT_TYPE_BUSINESS) {
			return false;
		}

		$vatIds = $order::getStaticOrderCustomer()?->getVatIds();
		if (!is_array($vatIds)) {
			return false;
		}

		return true;
	}
}
