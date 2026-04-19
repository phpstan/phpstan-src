<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug5207b;

class OrderEntity
{
	public function getOrderCustomer(): ?OrderCustomerEntity
	{
		return new OrderCustomerEntity();
	}
}

class OrderCustomerEntity
{
	public function getCustomer(): ?CustomerEntity
	{
		return null;
	}

	public function getEmail(): string
	{
		return '';
	}
}

class CustomerEntity
{
	public function getGuest(): bool
	{
		return true;
	}
}

class GuestAuthenticator
{
	public function validate(OrderEntity $order, string $s): void
	{
		$isOrderByGuest = $order->getOrderCustomer()?->getCustomer()?->getGuest();

		if (!$isOrderByGuest) {
			throw new \Exception();
		}


		if (mb_strtolower($s) !== mb_strtolower($order->getOrderCustomer()?->getEmail() ?: '')) {
			throw new \Exception();
		}
	}
}

