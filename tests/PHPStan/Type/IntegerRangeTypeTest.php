<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\Testing\PHPStanTestCase;
use const PHP_INT_MAX;

class IntegerRangeTypeTest extends PHPStanTestCase
{

	public function testCreateFromFloatPastTheIntRange(): void
	{
		// PHP_INT_MAX is not representable as a float: (float) PHP_INT_MAX is
		// the first float past the int range
		$pastMax = (float) PHP_INT_MAX;

		$this->assertSame('int', IntegerRangeType::createAllSmallerThan($pastMax)->describe(VerbosityLevel::precise()));
		$this->assertSame('int', IntegerRangeType::createAllSmallerThanOrEqualTo($pastMax)->describe(VerbosityLevel::precise()));
		$this->assertSame('*NEVER*', IntegerRangeType::createAllGreaterThan($pastMax)->describe(VerbosityLevel::precise()));
		$this->assertSame('*NEVER*', IntegerRangeType::createAllGreaterThanOrEqualTo($pastMax)->describe(VerbosityLevel::precise()));
	}

}
