<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug14493Nsrt;

use function PHPStan\Testing\assertType;

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

class TypeTest
{
	public function doFoo(OrderEntity $order): void
	{
		$customerType = $order->getOrderCustomer()?->getCustomer()?->getAccountType();
		assertType('string|null', $customerType);

		if ($customerType !== CustomerEntity::ACCOUNT_TYPE_BUSINESS) {
			return;
		}

		assertType("'business'", $customerType);

		// The method still returns nullable - the nullsafe chain narrowing
		// should not leak to the broader scope for subsequent calls
		assertType('Bug14493Nsrt\OrderCustomerEntity|null', $order->getOrderCustomer());
	}

	public function doBar(OrderEntity $order): void
	{
		$customerType = $order::getStaticOrderCustomer()?->getCustomer()?->getAccountType();
		assertType('string|null', $customerType);

		if ($customerType !== CustomerEntity::ACCOUNT_TYPE_BUSINESS) {
			return;
		}

		assertType("'business'", $customerType);
		assertType('Bug14493Nsrt\OrderCustomerEntity|null', $order::getStaticOrderCustomer());
	}
}
