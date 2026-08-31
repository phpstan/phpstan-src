<?php declare(strict_types = 1);

namespace MatchTruePropertyNarrowing;

use function PHPStan\Testing\assertType;

abstract class BaseShipment {}
class Shipment extends BaseShipment {}
class ReturnShipment extends BaseShipment {}

class Package
{

	private BaseShipment $shipment;

	public function __construct(BaseShipment $shipment)
	{
		$this->shipment = $shipment;
	}

	public function throwArm(): void
	{
		$result = match (true) {
			$this->shipment instanceof Shipment => $this->shipment,
			$this->shipment instanceof ReturnShipment => throw new \LogicException(),
		};
		assertType('MatchTruePropertyNarrowing\Shipment', $result);
	}

	public function valueArm(): void
	{
		$result = match (true) {
			$this->shipment instanceof Shipment => $this->shipment,
			$this->shipment instanceof ReturnShipment => new Shipment(),
		};
		assertType('MatchTruePropertyNarrowing\Shipment', $result);
	}

	public function defaultArm(): void
	{
		$result = match (true) {
			$this->shipment instanceof Shipment => $this->shipment,
			default => throw new \LogicException(),
		};
		assertType('MatchTruePropertyNarrowing\Shipment', $result);
	}

}
