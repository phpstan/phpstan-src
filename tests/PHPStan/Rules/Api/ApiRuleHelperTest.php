<?php declare(strict_types = 1);

namespace PHPStan\Rules\Api;

use PHPUnit\Framework\TestCase;

class ApiRuleHelperTest extends TestCase
{

	public function dataIsInPhpStanNamespace(): array
	{
		return [
			[
				null,
				false,
			],
			[
				'PHPStan',
				true,
			],
			[
				'PhpStan',
				true,
			],
			[
				'PHPStan\\Foo',
				true,
			],
			[
				'PhpStan\\Foo',
				true,
			],
			[
				'App\\Foo',
				false,
			],
			[
				'PHPStanWorkshop',
				false,
			],
			[
				'PHPStanWorkshop\\',
				false,
			],
			[
				'PHPStan\\PhpDocParser\\',
				false,
			],
		];
	}

	/**
	 * @dataProvider dataIsInPhpStanNamespace
	 * @param string|null $namespace
	 * @param bool $expected
	 */
	public function testIsInPhpStanNamespace(?string $namespace, bool $expected): void
	{
		$rule = new ApiRuleHelper();
		$this->assertSame($expected, $rule->isCalledFromPhpStan($namespace));
	}

}
