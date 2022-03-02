<?php

namespace Bug3892;

class OrderEntity
{
	public function isLoaded(): bool
	{
		return rand(0,1) === 0;
	}
}

abstract class OrderSaved
{
	public const TYP = [PickingOrder::class => '200', ReceiptOrder::class => '300'];

	/** @var Order[] */
	private $dtos;

	public function __construct(OrderEntity $order)
	{
		$this->dtos = \array_filter([ReceiptOrder::fromOrder($order), PickingOrder::fromOrder($order)]);
	}


	/**
	 * @return Order[]
	 */
	public function getDTOs(): array
	{
		return $this->dtos;
	}
}

abstract class Order
{
	public const TYP = [PickingOrder::class => '200', ReceiptOrder::class => '300'];
}

class PickingOrder extends Order
{
	public static function fromOrder(OrderEntity $order): ?self
	{
		return $order->isLoaded() ? new self() : null;
	}
}

class ReceiptOrder extends Order
{
	public static function fromOrder(OrderEntity $order): ?self
	{
		return $order->isLoaded() ? new self() : null;
	}
}

class Foo
{

	public function doFoo(OrderSaved $event)
	{
		$DTOs = $event->getDTOs();

		$DTOClasses = \array_map('\get_class', $DTOs);
		$missingClasses = \array_diff(\array_keys(Order::TYP), $DTOClasses);

		if (\in_array(ReceiptOrder::class, $missingClasses, true)) {

		}

	}

}
