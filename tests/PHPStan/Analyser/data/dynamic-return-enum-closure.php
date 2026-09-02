<?php // lint >= 8.1

declare(strict_types = 1);

namespace DynamicReturnEnumClosure;

use function array_map;
use function PHPStan\Testing\assertType;

interface EnumInterface
{

	public function getValue(): string|int;

}

trait LabeledEnum
{

	/**
	 * @return value-of<static>
	 */
	public function getValue(): string|int
	{
		return $this->value;
	}

}

enum OfferType: string implements EnumInterface
{

	use LabeledEnum;

	case OFFER_FLIGHT_HOTEL = 'OfferFlightHotel';
	case OFFER_BUS_HOTEL = 'OfferBusHotel';
	case OFFER_HOTEL = 'OfferHotel';

}

class Options
{

	/**
	 * @param list<OfferType> $enums
	 */
	public function direct(array $enums): void
	{
		assertType('string', $enums[0]->getValue());
	}

	/**
	 * @param list<OfferType> $enums
	 * @return list<string>
	 */
	public function mapped(array $enums): array
	{
		$result = array_map(
			static fn (OfferType $enum) => $enum->getValue(),
			$enums,
		);
		// the registered extension resolves getValue() to the backing type -
		// it must also be consulted when the arrow function's return is priced
		// for array_map()
		assertType('list<string>', $result);

		return $result;
	}

}
