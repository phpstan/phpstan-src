<?php declare(strict_types = 1);

namespace PHPStan\Rules\Api;

use PHPStan\Analyser\Scope;
use PHPUnit\Framework\TestCase;

class ApiRuleHelperTest extends TestCase
{

	public function dataIsPhpStanCode(): array
	{
		return [
			[
				null,
				'App\\Foo',
				false,
			],
			[
				null,
				'PHPStan\\Foo',
				true,
			],
			[
				'App\\Foo',
				'PHPStan',
				true,
			],
			[
				'PHPStan\\Foo',
				'App\\Foo',
				false,
			],
			[
				'PHPStan\\Foo',
				'PHPStan\\Foo',
				false,
			],
			[
				'App\\Foo',
				'PhpStan',
				true,
			],
			[
				'App\\Foo',
				'PHPStan\\Foo',
				true,
			],
			[
				'App\\Foo',
				'PhpStan\\Foo',
				true,
			],
			[
				'App\\Foo',
				'App\\Foo',
				false,
			],
			[
				'App\\Foo',
				'PHPStanWorkshop',
				false,
			],
			[
				'App\\Foo',
				'PHPStanWorkshop\\',
				false,
			],
			[
				'App\\Foo',
				'PHPStan\\PhpDocParser\\',
				false,
			],
		];
	}

	/**
	 * @dataProvider dataIsPhpStanCode
	 * @param string|null $scopeNamespace
	 * @param string $nameToCheck
	 * @param bool $expected
	 */
	public function testIsPhpStanCode(?string $scopeNamespace, string $nameToCheck, bool $expected): void
	{
		$rule = new ApiRuleHelper();
		$scope = $this->createMock(Scope::class);
		$scope->method('getNamespace')->willReturn($scopeNamespace);
		$this->assertSame($expected, $rule->isPhpStanCode($scope, $nameToCheck));
	}

}
