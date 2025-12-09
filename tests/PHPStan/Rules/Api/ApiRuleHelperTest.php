<?php declare(strict_types = 1);

namespace PHPStan\Rules\Api;

use PHPStan\Analyser\Scope;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ApiRuleHelperTest extends TestCase
{

	public static function dataIsPhpStanCode(): array
	{
		return [
			[
				null,
				'/usr/var/foo.php',
				'App\\Foo',
				null,
				false,
			],
			[
				null,
				'/usr/var/foo.php',
				'PHPStan\\Foo',
				null,
				true,
			],
			[
				null,
				'/usr/var/foo.php',
				'PHPStan\\BetterReflection\\Foo',
				null,
				false,
			],
			[
				'App\\Foo',
				'/usr/var/foo.php',
				'PHPStan',
				null,
				true,
			],
			[
				'PHPStan\\Foo',
				'/usr/var/foo.php',
				'App\\Foo',
				null,
				false,
			],
			[
				'PHPStan\\Foo',
				'/usr/var/foo.php',
				'PHPStan\\Foo',
				null,
				false,
			],
			[
				'App\\Foo',
				'/usr/var/foo.php',
				'PhpStan',
				null,
				true,
			],
			[
				'App\\Foo',
				'/usr/var/foo.php',
				'PHPStan\\Foo',
				null,
				true,
			],
			[
				'App\\Foo',
				'/usr/var/foo.php',
				'PhpStan\\Foo',
				null,
				true,
			],
			[
				'App\\Foo',
				'/usr/var/foo.php',
				'App\\Foo',
				null,
				false,
			],
			[
				'App\\Foo',
				'/usr/var/foo.php',
				'PHPStanWorkshop',
				null,
				false,
			],
			[
				'App\\Foo',
				'/usr/var/foo.php',
				'PHPStanWorkshop\\',
				null,
				false,
			],
			[
				'App\\Foo',
				'/usr/var/foo.php',
				'PHPStan\\PhpDocParser\\',
				null,
				false,
			],
			[
				'PHPStan\\Doctrine',
				'/usr/var/phpstan-doctrine/src/Foo.php',
				'PHPStan\\TrinaryLogic',
				'/usr/var/phpstan-doctrine/vendor/phpstan/phpstan.phar/src/TrinaryLogic.php',
				true,
			],
			[
				'PHPStan\\Doctrine',
				'/usr/var/phpstan-doctrine/src/Foo.php',
				'PHPStan\\TrinaryLogic',
				'phar:///usr/var/phpstan-doctrine/vendor/phpstan/phpstan.phar/src/TrinaryLogic.php',
				true,
			],
			[
				'PHPStan\\Doctrine',
				'/usr/var/phpstan-doctrine/src/Foo.php',
				'PHPStan\\Doctrine\\Bar',
				'/usr/var/phpstan-doctrine/src/Bar.php',
				false,
			],
			[
				'PHPStan\\Doctrine',
				'/usr/var/phpstan-doctrine/tests/FooTest.php',
				'PHPStan\\Doctrine\\Bar',
				'/usr/var/phpstan-doctrine/src/Bar.php',
				false,
			],
		];
	}

	#[DataProvider('dataIsPhpStanCode')]
	public function testIsPhpStanCode(
		?string $scopeNamespace,
		string $scopeFile,
		string $nameToCheck,
		?string $declaringFileNameToCheck,
		bool $expected,
	): void
	{
		$rule = new ApiRuleHelper();
		$scope = $this->createStub(Scope::class);
		$scope->method('getNamespace')->willReturn($scopeNamespace);
		$scope->method('getFile')->willReturn($scopeFile);
		$this->assertSame($expected, $rule->isPhpStanCode($scope, $nameToCheck, $declaringFileNameToCheck));
	}

}
