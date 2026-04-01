<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug14411Regression;

use function PHPStan\Testing\assertType;
use function PHPStan\Testing\assertVariableCertainty;
use PHPStan\TrinaryLogic;

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

	assertVariableCertainty(TrinaryLogic::createYes(), $order);

	return $order;
}
