<?php declare(strict_types = 1);

namespace PHPStan\Rules\InternalTag;

use PHPStan\Rules\RestrictedUsage\RestrictedStaticMethodUsageRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<RestrictedStaticMethodUsageRule>
 */
class RestrictedInternalStaticMethodUsageExtensionTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return self::getContainer()->getByType(RestrictedStaticMethodUsageRule::class);
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/static-method-internal-tag.php'], [
			[
				'Call to internal static method StaticMethodInternalTagOne\Foo::doInternal() from outside its root namespace StaticMethodInternalTagOne.',
				58,
			],
			[
				'Call to static method doFoo() of internal class StaticMethodInternalTagOne\FooInternal from outside its root namespace StaticMethodInternalTagOne.',
				63,
			],
			[
				'Call to internal static method StaticMethodInternalTagOne\Foo::doInternal() from outside its root namespace StaticMethodInternalTagOne.',
				71,
			],

			[
				'Call to static method doFoo() of internal class StaticMethodInternalTagOne\FooInternal from outside its root namespace StaticMethodInternalTagOne.',
				76,
			],
			[
				'Call to internal static method FooWithInternalStaticMethodWithoutNamespace::doInternal().',
				107,
			],
			[
				'Call to static method doFoo() of internal class FooInternalStaticWithoutNamespace.',
				112,
			],
			[
				'Call to internal static method FooWithInternalStaticMethodWithoutNamespace::doInternal().',
				120,
			],
			[
				'Call to static method doFoo() of internal class FooInternalStaticWithoutNamespace.',
				125,
			],
		]);
	}

	public function testStaticMethodCallOnInternalSubclass(): void
	{
		$this->analyse([__DIR__ . '/data/static-method-call-on-internal-subclass.php'], [
			[
				'Call to static method doBar() of internal class StaticMethodCallOnInternalSubclassOne\Bar from outside its root namespace StaticMethodCallOnInternalSubclassOne.',
				34,
			],
		]);
	}

	public function testNoNamespace(): void
	{
		$this->analyse([__DIR__ . '/data/no-namespace.php'], [
			[
				'Call to internal static method ClassInternal::internalStaticMethod().',
				39,
			],
		]);
	}

	public function testBug13210(): void
	{
		$this->analyse([__DIR__ . '/data/bug-13210.php'], []);
	}

}
