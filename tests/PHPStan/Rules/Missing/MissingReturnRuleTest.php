<?php declare(strict_types = 1);

namespace PHPStan\Rules\Missing;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<MissingReturnRule>
 */
class MissingReturnRuleTest extends RuleTestCase
{

	private bool $checkExplicitMixedMissingReturn;

	private bool $checkPhpDocMissingReturn = true;

	protected function getRule(): Rule
	{
		return new MissingReturnRule($this->checkExplicitMixedMissingReturn, $this->checkPhpDocMissingReturn);
	}

	public function testRule(): void
	{
		$this->checkExplicitMixedMissingReturn = true;

		$this->analyse([__DIR__ . '/data/missing-return.php'], [
			[
				'Method MissingReturn\Foo::doFoo() should return int but return statement is missing.',
				8,
			],
			[
				'Method MissingReturn\Foo::doBar() should return int but return statement is missing.',
				15,
			],
			[
				'Method MissingReturn\Foo::doBaz() should return int but return statement is missing.',
				21,
			],
			[
				'Method MissingReturn\Foo::doLorem() should return int but return statement is missing.',
				39,
			],
			[
				'Method MissingReturn\Foo::doLorem() should return int but return statement is missing.',
				47,
			],
			[
				'Anonymous function should return int but return statement is missing.',
				105,
			],
			[
				'Function MissingReturn\doFoo() should return int but return statement is missing.',
				112,
			],
			[
				'Method MissingReturn\SwitchBranches::doBar() should return int but return statement is missing.',
				146,
			],
			[
				'Method MissingReturn\SwitchBranches::doLorem() should return int but return statement is missing.',
				172,
			],
			[
				'Method MissingReturn\SwitchBranches::doIpsum() should return int but return statement is missing.',
				182,
			],
			[
				'Method MissingReturn\SwitchBranches::doDolor() should return int but return statement is missing.',
				193,
			],
			[
				'Method MissingReturn\TryCatchFinally::doBaz() should return int but return statement is missing.',
				234,
			],
			[
				'Method MissingReturn\TryCatchFinally::doDolor() should return int but return statement is missing.',
				263,
			],
			[
				'Method MissingReturn\ReturnInPhpDoc::doFoo() should return int but return statement is missing.',
				290,
			],
			[
				'Method MissingReturn\FooTemplateMixedType::doFoo() should return T but return statement is missing.',
				321,
			],
			[
				'Method MissingReturn\MissingReturnGenerators::emptyBodyUnspecifiedTReturn() should return Generator but return statement is missing.',
				331,
			],
			[
				'Method MissingReturn\MissingReturnGenerators::emptyBodyUnspecifiedTReturn2() should return Generator<int, int, mixed, mixed> but return statement is missing.',
				344,
			],
			[
				'Method MissingReturn\MissingReturnGenerators::emptyBodySpecifiedTReturn() should return Generator<int, int, int, string> but return statement is missing.',
				360,
			],
			[
				'Method MissingReturn\MissingReturnGenerators::bodySpecifiedTReturn() should return string but return statement is missing.',
				370,
			],
			[
				'Method MissingReturn\NeverReturn::doBaz() should always throw an exception or terminate script execution but doesn\'t do that.',
				473,
			],
			[
				'Method MissingReturn\NeverReturn::doBaz2() should always throw an exception or terminate script execution but doesn\'t do that.',
				481,
			],
			[
				'Method MissingReturn\MorePreciseMissingReturnLines::doFoo() should return int but return statement is missing.',
				514,
			],
			[
				'Method MissingReturn\MorePreciseMissingReturnLines::doFoo() should return int but return statement is missing.',
				515,
			],
			[
				'Method MissingReturn\MorePreciseMissingReturnLines::doFoo2() should return int but return statement is missing.',
				524,
			],
		]);
	}

	public function testCheckMissingReturnWithTemplateMixedType(): void
	{
		$this->checkExplicitMixedMissingReturn = false;
		$this->analyse([__DIR__ . '/data/missing-return-template-mixed-type.php'], [
			[
				'Method MissingReturnTemplateMixedType\Foo::doFoo() should return T but return statement is missing.',
				13,
			],
		]);
	}

	public function testBug2875(): void
	{
		$this->checkExplicitMixedMissingReturn = true;
		$this->analyse([__DIR__ . '/data/bug-2875.php'], []);
	}

	public function testMissingMixedReturnInEmptyBody(): void
	{
		$this->checkExplicitMixedMissingReturn = true;
		$this->analyse([__DIR__ . '/data/missing-mixed-return-empty-body.php'], [
			[
				'Method MissingMixedReturnEmptyBody\HelloWorld::doFoo() should return mixed but return statement is missing.',
				11,
			],
		]);
	}

	public function testBug3488(): void
	{
		if (PHP_VERSION_ID < 80100) {
			$this->markTestSkipped('Test requires PHP 8.1.');
		}

		$this->checkExplicitMixedMissingReturn = true;
		$this->analyse([__DIR__ . '/data/bug-3488.php'], []);
	}

	public function testBug3669(): void
	{
		$this->checkExplicitMixedMissingReturn = true;

		require_once __DIR__ . '/data/bug-3669.php';
		$this->analyse([__DIR__ . '/data/bug-3669.php'], []);
	}

	public function dataCheckPhpDocMissingReturn(): array
	{
		return [
			[
				true,
				[
					[
						'Method CheckPhpDocMissingReturn\Foo::doFoo() should return int|string but return statement is missing.',
						11,
					],
					[
						'Method CheckPhpDocMissingReturn\Foo::doFoo2() should return string|null but return statement is missing.',
						19,
					],
					[
						'Method CheckPhpDocMissingReturn\Foo::doFoo3() should return int|string but return statement is missing.',
						29,
					],
					[
						'Method CheckPhpDocMissingReturn\Foo::doFoo4() should return string|null but return statement is missing.',
						39,
					],
					[
						'Method CheckPhpDocMissingReturn\Foo::doFoo5() should return mixed but return statement is missing.',
						49,
					],
					[
						'Method CheckPhpDocMissingReturn\Bar::doFoo() should return int|string but return statement is missing.',
						59,
					],
					[
						'Method CheckPhpDocMissingReturn\Bar::doFoo2() should return string|null but return statement is missing.',
						64,
					],
					[
						'Method CheckPhpDocMissingReturn\Bar::doFoo3() should return int|string but return statement is missing.',
						71,
					],
					[
						'Method CheckPhpDocMissingReturn\Bar::doFoo4() should return string|null but return statement is missing.',
						78,
					],
					[
						'Method CheckPhpDocMissingReturn\Bar::doFoo5() should return mixed but return statement is missing.',
						85,
					],
				],
			],
			[
				false,
				[
					[
						'Method CheckPhpDocMissingReturn\Foo::doFoo() should return int|string but return statement is missing.',
						11,
					],
					[
						'Method CheckPhpDocMissingReturn\Foo::doFoo3() should return int|string but return statement is missing.',
						29,
					],
					[
						'Method CheckPhpDocMissingReturn\Foo::doFoo5() should return mixed but return statement is missing.',
						49,
					],
					[
						'Method CheckPhpDocMissingReturn\Bar::doFoo() should return int|string but return statement is missing.',
						59,
					],
					[
						'Method CheckPhpDocMissingReturn\Bar::doFoo2() should return string|null but return statement is missing.',
						64,
					],
					[
						'Method CheckPhpDocMissingReturn\Bar::doFoo3() should return int|string but return statement is missing.',
						71,
					],
					[
						'Method CheckPhpDocMissingReturn\Bar::doFoo4() should return string|null but return statement is missing.',
						78,
					],
					[
						'Method CheckPhpDocMissingReturn\Bar::doFoo5() should return mixed but return statement is missing.',
						85,
					],
				],
			],
		];
	}

	/**
	 * @dataProvider dataCheckPhpDocMissingReturn
	 * @param list<array{0: string, 1: int, 2?: string}> $errors
	 */
	public function testCheckPhpDocMissingReturn(bool $checkPhpDocMissingReturn, array $errors): void
	{
		if (PHP_VERSION_ID < 80000) {
			self::markTestSkipped('Test requires PHP 8.0.');
		}

		$this->checkExplicitMixedMissingReturn = true;
		$this->checkPhpDocMissingReturn = $checkPhpDocMissingReturn;
		$this->analyse([__DIR__ . '/data/check-phpdoc-missing-return.php'], $errors);
	}

	public function dataModelMixin(): array
	{
		return [
			[
				true,
			],
			[
				false,
			],
		];
	}

	/**
	 * @dataProvider dataModelMixin
	 */
	public function testModelMixin(bool $checkExplicitMixedMissingReturn): void
	{
		if (PHP_VERSION_ID < 80000) {
			self::markTestSkipped('Test requires PHP 8.0.');
		}

		$this->checkExplicitMixedMissingReturn = $checkExplicitMixedMissingReturn;
		$this->checkPhpDocMissingReturn = true;
		$this->analyse([__DIR__ . '/../../Analyser/nsrt/model-mixin.php'], [
			[
				'Method ModelMixin\Model::__callStatic() should return mixed but return statement is missing.',
				13,
			],
		]);
	}

	public function testBug6257(): void
	{
		if (PHP_VERSION_ID < 80000) {
			$this->markTestSkipped('Test requires PHP 8.0.');
		}
		$this->checkExplicitMixedMissingReturn = true;
		$this->checkPhpDocMissingReturn = true;
		$this->analyse([__DIR__ . '/data/bug-6257.php'], [
			[
				'Function ReturnTypes\sometimesThrows() should always throw an exception or terminate script execution but doesn\'t do that.',
				27,
			],
		]);
	}

	public function testBug7384(): void
	{
		$this->checkExplicitMixedMissingReturn = true;
		$this->checkPhpDocMissingReturn = true;
		$this->analyse([__DIR__ . '/data/bug-7384.php'], []);
	}

	public function testBug9309(): void
	{
		$this->checkExplicitMixedMissingReturn = true;
		$this->analyse([__DIR__ . '/data/bug-9309.php'], []);
	}

	public function testBug6807(): void
	{
		$this->checkExplicitMixedMissingReturn = true;
		$this->analyse([__DIR__ . '/data/bug-6807.php'], []);
	}

	public function testBug8463(): void
	{
		$this->checkExplicitMixedMissingReturn = true;
		$this->analyse([__DIR__ . '/data/bug-8463.php'], []);
	}

	public function testBug9374(): void
	{
		$this->checkExplicitMixedMissingReturn = true;
		$this->analyse([__DIR__ . '/data/bug-9374.php'], []);
	}

	public function testPropertyHooks(): void
	{
		if (PHP_VERSION_ID < 80400) {
			$this->markTestSkipped('Test requires PHP 8.4.');
		}

		$this->checkExplicitMixedMissingReturn = true;
		$this->analyse([__DIR__ . '/data/property-hooks-missing-return.php'], [
			[
				'Get hook for property PropertyHooksMissingReturn\Foo::$i should return int but return statement is missing.',
				10,
			],
			[
				'Get hook for property PropertyHooksMissingReturn\Foo::$j should return int but return statement is missing.',
				23,
			],
		]);
	}

}
