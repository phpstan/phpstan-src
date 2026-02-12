<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug9534Nsrt;

use function PHPStan\Testing\assertType;

final class FinalCar {}
final class FinalBike {}
final class FinalBoat {}

/**
 * @param class-string<FinalCar|FinalBike|FinalBoat> $string
 */
function narrowing(string $string): void {
	assertType('class-string<Bug9534Nsrt\FinalBike|Bug9534Nsrt\FinalBoat|Bug9534Nsrt\FinalCar>', $string);

	if ($string === FinalCar::class) {
		assertType("'Bug9534Nsrt\\\\FinalCar'", $string);
		return;
	}

	assertType('class-string<Bug9534Nsrt\FinalBike|Bug9534Nsrt\FinalBoat>', $string);

	if ($string === FinalBike::class) {
		assertType("'Bug9534Nsrt\\\\FinalBike'", $string);
		return;
	}

	assertType("class-string<Bug9534Nsrt\\FinalBoat>", $string);
}
