<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug9534;

class Car {}
class Bike {}
class Boat {}

final class FinalCar {}
final class FinalBike {}
final class FinalBoat {}

/**
 * Non-final classes: match can't be exhaustive because subclasses may exist
 *
 * @param class-string<Car|Bike|Boat> $string
 */
function accept_class(string $string): void {
	match ($string) { // error on this line
		Car::class => 'car',
		Bike::class => 'bike',
		Boat::class => 'boat',
	};
}

/**
 * Final classes: match IS exhaustive because no subclasses can exist
 *
 * @param class-string<FinalCar|FinalBike|FinalBoat> $string
 */
function accept_final_class(string $string): void {
	match ($string) { // no error
		FinalCar::class => 'car',
		FinalBike::class => 'bike',
		FinalBoat::class => 'boat',
	};
}

/**
 * Partial match with final classes: should report remaining value
 *
 * @param class-string<FinalCar|FinalBike|FinalBoat> $string
 */
function partial_final_match(string $string): void {
	match ($string) { // error on this line
		FinalCar::class => 'car',
	};
}

/**
 * Partial match with non-final classes
 *
 * @param class-string<Car|Bike> $string
 */
function partial_match(string $string): void {
	match ($string) { // error on this line
		Car::class => 'car',
	};
}
