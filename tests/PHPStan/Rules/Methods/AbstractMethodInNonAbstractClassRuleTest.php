<?php declare(strict_types = 1);

namespace PHPStan\Rules\Methods;

use Bug3406\AbstractFoo;
use Bug3406\ClassFoo;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;

/**
 * @extends RuleTestCase<AbstractMethodInNonAbstractClassRule>
 */
class AbstractMethodInNonAbstractClassRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new AbstractMethodInNonAbstractClassRule();
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/abstract-method.php'], [
			[
				'Non-abstract class AbstractMethod\Bar contains abstract method doBar().',
				15,
			],
			[
				'Interface AbstractMethod\Baz contains abstract method doBar().',
				22,
			],
		]);
	}

	public function testTraitProblem(): void
	{
		$this->analyse([__DIR__ . '/data/trait-method-problem.php'], []);
	}

	public function testBug3406(): void
	{
		$this->analyse([__DIR__ . '/data/bug-3406.php'], []);
	}

	public function testBug3406ReflectionCheck(): void
	{
		$reflectionProvider = self::createReflectionProvider();
		$reflection = $reflectionProvider->getClass(ClassFoo::class);
		$this->assertSame(AbstractFoo::class, $reflection->getNativeMethod('myFoo')->getDeclaringClass()->getName());
		$this->assertSame(ClassFoo::class, $reflection->getNativeMethod('myBar')->getDeclaringClass()->getName());
	}

	public function testbug3406AnotherCase(): void
	{
		$this->analyse([__DIR__ . '/data/bug-3406_2.php'], []);
	}

	public function testBug4214(): void
	{
		$this->analyse([__DIR__ . '/data/bug-4214.php'], []);
	}

	public function testNonAbstractMethodWithNoBody(): void
	{
		$this->analyse([__DIR__ . '/data/bug-4244.php'], [
			[
				'Non-abstract method HelloWorld::sayHello() must contain a body.',
				5,
			],
		]);
	}

	#[RequiresPhp('>= 8.1')]
	public function testEnum(): void
	{
		$this->analyse([__DIR__ . '/data/method-in-enum-without-body.php'], [
			[
				'Non-abstract method MethodInEnumWithoutBody\Foo::doFoo() must contain a body.',
				8,
			],
			[
				'Enum MethodInEnumWithoutBody\Foo contains abstract method doBar().',
				10,
			],
		]);
	}

	#[RequiresPhp('>= 8.1')]
	public function testBug11592(): void
	{
		$this->analyse([__DIR__ . '/../Classes/data/bug-11592.php'], [
			[
				'Enum Bug11592\Test contains abstract method from().',
				9,
			],
			[
				'Enum Bug11592\Test contains abstract method tryFrom().',
				11,
			],
			[
				'Enum Bug11592\Test2 contains abstract method from().',
				24,
			],
			[
				'Enum Bug11592\Test2 contains abstract method tryFrom().',
				26,
			],
			[
				'Enum Bug11592\EnumWithAbstractMethod contains abstract method foo().',
				46,
			],
		]);
	}

}
