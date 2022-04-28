<?php declare(strict_types = 1);

namespace PHPStan\Rules\Methods;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<MissingMethodImplementationRule>
 */
class MissingMethodImplementationRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new MissingMethodImplementationRule();
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/missing-method-impl.php'], [
			[
				'Non-abstract class MissingMethodImpl\Baz contains abstract method doBaz() from class MissingMethodImpl\Baz.',
				24,
			],
			[
				'Non-abstract class MissingMethodImpl\Baz contains abstract method doFoo() from interface MissingMethodImpl\Foo.',
				24,
			],
			[
				'Non-abstract class class@anonymous/tests/PHPStan/Rules/Methods/data/missing-method-impl.php:41 contains abstract method doFoo() from interface MissingMethodImpl\Foo.',
				41,
			],
		]);
	}

	public function testBug3469(): void
	{
		$this->analyse([__DIR__ . '/data/bug-3469.php'], []);
	}

	public function testBug3958(): void
	{
		$this->analyse([__DIR__ . '/data/bug-3958.php'], []);
	}

	public function testEnums(): void
	{
		$this->analyse([__DIR__ . '/data/missing-method-impl-enum.php'], [
			[
				'Enum MissingMethodImplEnum\Bar contains abstract method doFoo() from interface MissingMethodImplEnum\FooInterface.',
				21,
			],
		]);
	}

}
