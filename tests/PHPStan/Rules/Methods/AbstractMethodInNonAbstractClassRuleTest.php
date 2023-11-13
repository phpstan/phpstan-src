<?php declare(strict_types = 1);

namespace PHPStan\Rules\Methods;

use Bug3406\AbstractFoo;
use Bug3406\ClassFoo;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

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
		$reflectionProvider = $this->createReflectionProvider();
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

}
