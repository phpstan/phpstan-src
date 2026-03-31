<?php // lint >= 8.0

declare(strict_types = 1);

namespace SyliusOrderIpListenerIntegration;

interface OrderInterface {}

class Event
{
	/** @return mixed */
	public function getSubject()
	{
		return new \stdClass();
	}
}

function getOrder(Event|OrderInterface $event): OrderInterface
{
	if ($event instanceof Event) {
		$order = $event->getSubject();
		assert($order instanceof OrderInterface);
	}

	if ($event instanceof OrderInterface) {
		$order = $event;
	}

	return $order;
}
