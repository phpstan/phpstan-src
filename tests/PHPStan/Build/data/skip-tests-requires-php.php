<?php declare(strict_types = 1);

namespace SkipTestsWithRequiresPhpAttributeRule;

use PHPUnit\Framework\TestCase;
use const PHP_VERSION_ID;

class FooTest extends TestCase
{

	public function testVersionCheck(): void
	{
		if (PHP_VERSION_ID < 80000) {
			$this->markTestSkipped();
		}
	}

	public function testVersionCheckWithoutSkip(): void
	{
		if (PHP_VERSION_ID < 80000) {
			return;
		}
	}

	public function testUnrelatedCondition(bool $a, bool $b): void
	{
		if ($a || $b) {
			return;
		}
	}

	public function testUnrelatedComparison(int $a): void
	{
		if ($a < 80000) {
			return;
		}
	}

}
