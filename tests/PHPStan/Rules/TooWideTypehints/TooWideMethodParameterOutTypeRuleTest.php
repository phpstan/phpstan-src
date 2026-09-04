<?php declare(strict_types = 1);

namespace PHPStan\Rules\TooWideTypehints;

use PHPStan\Rules\Properties\PropertyReflectionFinder;
use PHPStan\Rules\Rule as TRule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;

/**
 * @extends RuleTestCase<TooWideMethodParameterOutTypeRule>
 */
class TooWideMethodParameterOutTypeRuleTest extends RuleTestCase
{

	private bool $checkProtectedAndPublicMethods = true;

	protected function getRule(): TRule
	{
		return new TooWideMethodParameterOutTypeRule(
			new TooWideParameterOutTypeCheck(
				new TooWideTypeCheck(new PropertyReflectionFinder(), true, true),
			),
			$this->checkProtectedAndPublicMethods,
		);
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/too-wide-method-parameter-out.php'], [
			[
				'Method TooWideMethodParameterOut\Foo::doBar() never assigns null to &$p so it can be removed from the by-ref type.',
				13,
				'You can narrow the parameter out type with @param-out PHPDoc tag.',
			],
			[
				'Method TooWideMethodParameterOut\Foo::doBaz() never assigns null to &$p so it can be removed from the @param-out type.',
				21,
			],
			[
				'Method TooWideMethodParameterOut\Foo::doLorem() never assigns null to &$p so it can be removed from the by-ref type.',
				26,
				'You can narrow the parameter out type with @param-out PHPDoc tag.',
			],
			[
				'Method TooWideMethodParameterOut\Foo::finalDoBaz() never assigns null to &$p so it can be removed from the @param-out type.',
				45,
			],
			[
				'Method TooWideMethodParameterOut\Foo::doBazProtected() never assigns null to &$p so it can be removed from the @param-out type.',
				53,
			],
			[
				'Method TooWideMethodParameterOut\Foo::doBazPrivate() never assigns null to &$p so it can be removed from the @param-out type.',
				61,
			],
			[
				'Method TooWideMethodParameterOut\FinalFoo::doBar() never assigns null to &$p so it can be removed from the by-ref type.',
				76,
				'You can narrow the parameter out type with @param-out PHPDoc tag.',
			],
			[
				'Method TooWideMethodParameterOut\FinalFoo::doBaz() never assigns null to &$p so it can be removed from the @param-out type.',
				84,
			],
			[
				'Method TooWideMethodParameterOut\FinalFoo::doLorem() never assigns null to &$p so it can be removed from the by-ref type.',
				89,
				'You can narrow the parameter out type with @param-out PHPDoc tag.',
			],
			[
				'Method TooWideMethodParameterOut\FinalFoo::doBool() never assigns false to &$b so the by-ref type can be changed to true.',
				105,
				'You can narrow the parameter out type with @param-out PHPDoc tag.',
			],
			[
				'Method TooWideMethodParameterOut\FinalFoo::doBool2() never assigns false to &$b so the @param-out type can be changed to true.',
				113,
			],
		]);
	}

	public function testRuleWithoutProtectedAndPublic(): void
	{
		$this->checkProtectedAndPublicMethods = false;
		$this->analyse([__DIR__ . '/data/too-wide-method-parameter-out.php'], [
			[
				'Method TooWideMethodParameterOut\Foo::finalDoBaz() never assigns null to &$p so it can be removed from the @param-out type.',
				45,
			],
			[
				'Method TooWideMethodParameterOut\Foo::doBazPrivate() never assigns null to &$p so it can be removed from the @param-out type.',
				61,
			],
			[
				'Method TooWideMethodParameterOut\FinalFoo::doBar() never assigns null to &$p so it can be removed from the by-ref type.',
				76,
				'You can narrow the parameter out type with @param-out PHPDoc tag.',
			],
			[
				'Method TooWideMethodParameterOut\FinalFoo::doBaz() never assigns null to &$p so it can be removed from the @param-out type.',
				84,
			],
			[
				'Method TooWideMethodParameterOut\FinalFoo::doLorem() never assigns null to &$p so it can be removed from the by-ref type.',
				89,
				'You can narrow the parameter out type with @param-out PHPDoc tag.',
			],
			[
				'Method TooWideMethodParameterOut\FinalFoo::doBool() never assigns false to &$b so the by-ref type can be changed to true.',
				105,
				'You can narrow the parameter out type with @param-out PHPDoc tag.',
			],
			[
				'Method TooWideMethodParameterOut\FinalFoo::doBool2() never assigns false to &$b so the @param-out type can be changed to true.',
				113,
			],
		]);
	}

	public function testBug10684(): void
	{
		$this->analyse([__DIR__ . '/data/bug-10684.php'], []);
	}

	public function testBug10687(): void
	{
		$this->analyse([__DIR__ . '/data/bug-10687.php'], []);
	}

	public function testBug12080(): void
	{
		$this->checkProtectedAndPublicMethods = false;
		$this->analyse([__DIR__ . '/data/bug-12080.php'], []);
	}

	public function testNestedTooWideType(): void
	{
		$this->analyse([__DIR__ . '/data/nested-too-wide-method-parameter-out-type.php'], [
			[
				'PHPDoc tag @param-out type array<array{int, bool}> of method NestedTooWideMethodParameterOutType\Foo::doFoo() can be narrowed to array<array{int, false}>.',
				12,
				'Offset 1 (false) does not accept type bool.',
			],
		]);
	}

	#[RequiresPhp('>= 8.0.0')]
	public function testBug15066(): void
	{
		$this->analyse([__DIR__ . '/data/bug-15066.php'], [
			[
				'Method Bug15066\\Foo::variadicNeverNull() never assigns null to &$refs so it can be removed from the by-ref type.',
				45,
				'You can narrow the parameter out type with @param-out PHPDoc tag.',
			],
		]);
	}

}
