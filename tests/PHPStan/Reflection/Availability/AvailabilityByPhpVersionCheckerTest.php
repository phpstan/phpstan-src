<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Availability;

use PHPStan\Php\PhpVersion;
use PHPUnit\Framework\TestCase;

class AvailabilityByPhpVersionCheckerTest extends TestCase
{

	public function dataFunction(): array
	{
		return [
			[
				'htmlspecialchars',
				70100,
				null,
			],
			[
				'array_key_first',
				70100,
				false,
			],
			[
				'array_key_first',
				70300,
				true,
			],
			[
				'convert_cyr_string',
				70100,
				true,
			],
			[
				'convert_cyr_string',
				80000,
				false,
			],
			[
				'datefmt_set_timezone_id',
				70100,
				false,
			],
			[
				'openssl_cms_read',
				70400,
				false,
			],
			[
				'openssl_cms_read',
				80000,
				true,
			],
		];
	}

	/**
	 * @dataProvider dataFunction
	 * @param string $functionName
	 * @param int $phpVersionId
	 */
	public function testFunction(string $functionName, int $phpVersionId, ?bool $expected): void
	{
		$checker = new AvailabilityByPhpVersionChecker(new PhpVersion($phpVersionId));
		$this->assertSame($expected, $checker->isFunctionAvailable($functionName));
	}

	public function dataClass(): array
	{
		return [
			[
				'Attribute',
				70400,
				false,
			],
			[
				'Attribute',
				80000,
				true,
			],
			[
				'ReflectionClass',
				70100,
				null,
			],
			[
				'DOMErrorHandler',
				70100,
				true,
			],
			[
				'DOMErrorHandler',
				80000,
				false,
			],
		];
	}

	/**
	 * @dataProvider dataClass
	 * @param string $className
	 * @param int $phpVersionId
	 */
	public function testClass(string $className, int $phpVersionId, ?bool $expected): void
	{
		$checker = new AvailabilityByPhpVersionChecker(new PhpVersion($phpVersionId));
		$this->assertSame($expected, $checker->isClassAvailable($className));
	}

}
