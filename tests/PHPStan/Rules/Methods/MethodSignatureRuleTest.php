<?php declare(strict_types = 1);

namespace PHPStan\Rules\Methods;

use PHPStan\Php\PhpVersion;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<OverridingMethodRule>
 */
class MethodSignatureRuleTest extends RuleTestCase
{

	private bool $reportMaybes;

	private bool $reportStatic;

	protected function getRule(): Rule
	{
		$phpVersion = new PhpVersion(PHP_VERSION_ID);

		return new OverridingMethodRule(
			$phpVersion,
			new MethodSignatureRule($this->reportMaybes, $this->reportStatic),
			true,
			new MethodParameterComparisonHelper($phpVersion, true),
			true,
		);
	}

	public function testReturnTypeRule(): void
	{
		$this->reportMaybes = true;
		$this->reportStatic = true;
		$this->analyse(
			[
				__DIR__ . '/data/method-signature.php',
			],
			[
				[
					'Parameter #1 $animal (MethodSignature\Dog) of method MethodSignature\SubClass::parameterTypeTest4() should be contravariant with parameter $animal (MethodSignature\Animal) of method MethodSignature\BaseClass::parameterTypeTest4()',
					298,
				],
				[
					'Parameter #1 $animal (MethodSignature\Dog) of method MethodSignature\SubClass::parameterTypeTest4() should be contravariant with parameter $animal (MethodSignature\Animal) of method MethodSignature\BaseInterface::parameterTypeTest4()',
					298,
				],
				[
					'Parameter #1 $animal (MethodSignature\Dog) of method MethodSignature\SubClass::parameterTypeTest5() should be compatible with parameter $animal (MethodSignature\Cat) of method MethodSignature\BaseClass::parameterTypeTest5()',
					305,
				],
				[
					'Parameter #1 $animal (MethodSignature\Dog) of method MethodSignature\SubClass::parameterTypeTest5() should be compatible with parameter $animal (MethodSignature\Cat) of method MethodSignature\BaseInterface::parameterTypeTest5()',
					305,
				],
				[
					'Return type (MethodSignature\Animal) of method MethodSignature\SubClass::returnTypeTest4() should be covariant with return type (MethodSignature\Dog) of method MethodSignature\BaseClass::returnTypeTest4()',
					351,
				],
				[
					'Return type (MethodSignature\Animal) of method MethodSignature\SubClass::returnTypeTest4() should be covariant with return type (MethodSignature\Dog) of method MethodSignature\BaseInterface::returnTypeTest4()',
					351,
				],
				[
					'Return type (MethodSignature\Cat) of method MethodSignature\SubClass::returnTypeTest5() should be compatible with return type (MethodSignature\Dog) of method MethodSignature\BaseClass::returnTypeTest5()',
					358,
				],
				[
					'Return type (MethodSignature\Cat) of method MethodSignature\SubClass::returnTypeTest5() should be compatible with return type (MethodSignature\Dog) of method MethodSignature\BaseInterface::returnTypeTest5()',
					358,
				],
				[
					'Parameter #1 $node (PhpParser\Node\Expr\StaticCall) of method MethodSignature\Rule::processNode() should be contravariant with parameter $node (PhpParser\Node) of method MethodSignature\GenericRule<PhpParser\Node>::processNode()',
					454,
				],
			],
		);
	}

	public function testReturnTypeRuleTrait(): void
	{
		$this->reportMaybes = true;
		$this->reportStatic = true;
		$this->analyse(
			[
				__DIR__ . '/data/method-signature-trait.php',
			],
			[
				[
					'Parameter #1 $animal (MethodSignature\Dog) of method MethodSignature\SubClassUsingTrait::parameterTypeTest4() should be contravariant with parameter $animal (MethodSignature\Animal) of method MethodSignature\BaseClass::parameterTypeTest4()',
					43,
				],
				[
					'Parameter #1 $animal (MethodSignature\Dog) of method MethodSignature\SubClassUsingTrait::parameterTypeTest4() should be contravariant with parameter $animal (MethodSignature\Animal) of method MethodSignature\BaseInterface::parameterTypeTest4()',
					43,
				],
				[
					'Parameter #1 $animal (MethodSignature\Dog) of method MethodSignature\SubClassUsingTrait::parameterTypeTest5() should be compatible with parameter $animal (MethodSignature\Cat) of method MethodSignature\BaseClass::parameterTypeTest5()',
					50,
				],
				[
					'Parameter #1 $animal (MethodSignature\Dog) of method MethodSignature\SubClassUsingTrait::parameterTypeTest5() should be compatible with parameter $animal (MethodSignature\Cat) of method MethodSignature\BaseInterface::parameterTypeTest5()',
					50,
				],
				[
					'Return type (MethodSignature\Animal) of method MethodSignature\SubClassUsingTrait::returnTypeTest4() should be covariant with return type (MethodSignature\Dog) of method MethodSignature\BaseClass::returnTypeTest4()',
					96,
				],
				[
					'Return type (MethodSignature\Animal) of method MethodSignature\SubClassUsingTrait::returnTypeTest4() should be covariant with return type (MethodSignature\Dog) of method MethodSignature\BaseInterface::returnTypeTest4()',
					96,
				],
				[
					'Return type (MethodSignature\Cat) of method MethodSignature\SubClassUsingTrait::returnTypeTest5() should be compatible with return type (MethodSignature\Dog) of method MethodSignature\BaseClass::returnTypeTest5()',
					103,
				],
				[
					'Return type (MethodSignature\Cat) of method MethodSignature\SubClassUsingTrait::returnTypeTest5() should be compatible with return type (MethodSignature\Dog) of method MethodSignature\BaseInterface::returnTypeTest5()',
					103,
				],
			],
		);
	}

	public function testReturnTypeRuleTraitWithoutMaybes(): void
	{
		$this->reportMaybes = false;
		$this->reportStatic = true;
		$this->analyse(
			[
				__DIR__ . '/data/method-signature-trait.php',
			],
			[
				[
					'Parameter #1 $animal (MethodSignature\Dog) of method MethodSignature\SubClassUsingTrait::parameterTypeTest5() should be compatible with parameter $animal (MethodSignature\Cat) of method MethodSignature\BaseClass::parameterTypeTest5()',
					50,
				],
				[
					'Parameter #1 $animal (MethodSignature\Dog) of method MethodSignature\SubClassUsingTrait::parameterTypeTest5() should be compatible with parameter $animal (MethodSignature\Cat) of method MethodSignature\BaseInterface::parameterTypeTest5()',
					50,
				],
				[
					'Return type (MethodSignature\Cat) of method MethodSignature\SubClassUsingTrait::returnTypeTest5() should be compatible with return type (MethodSignature\Dog) of method MethodSignature\BaseClass::returnTypeTest5()',
					103,
				],
				[
					'Return type (MethodSignature\Cat) of method MethodSignature\SubClassUsingTrait::returnTypeTest5() should be compatible with return type (MethodSignature\Dog) of method MethodSignature\BaseInterface::returnTypeTest5()',
					103,
				],
			],
		);
	}

	public function testReturnTypeRuleWithoutMaybes(): void
	{
		$this->reportMaybes = false;
		$this->reportStatic = true;
		$this->analyse(
			[
				__DIR__ . '/data/method-signature.php',
			],
			[
				[
					'Parameter #1 $animal (MethodSignature\Dog) of method MethodSignature\SubClass::parameterTypeTest5() should be compatible with parameter $animal (MethodSignature\Cat) of method MethodSignature\BaseClass::parameterTypeTest5()',
					305,
				],
				[
					'Parameter #1 $animal (MethodSignature\Dog) of method MethodSignature\SubClass::parameterTypeTest5() should be compatible with parameter $animal (MethodSignature\Cat) of method MethodSignature\BaseInterface::parameterTypeTest5()',
					305,
				],
				[
					'Return type (MethodSignature\Cat) of method MethodSignature\SubClass::returnTypeTest5() should be compatible with return type (MethodSignature\Dog) of method MethodSignature\BaseClass::returnTypeTest5()',
					358,
				],
				[
					'Return type (MethodSignature\Cat) of method MethodSignature\SubClass::returnTypeTest5() should be compatible with return type (MethodSignature\Dog) of method MethodSignature\BaseInterface::returnTypeTest5()',
					358,
				],
			],
		);
	}

	public function testRuleWithoutStaticMethods(): void
	{
		$this->reportMaybes = true;
		$this->reportStatic = false;
		$this->analyse([__DIR__ . '/data/method-signature-static.php'], []);
	}

	public function testRuleWithStaticMethods(): void
	{
		$this->reportMaybes = true;
		$this->reportStatic = true;
		$this->analyse([__DIR__ . '/data/method-signature-static.php'], [
			[
				'Parameter #1 $value (string) of method MethodSignature\Bar::doFoo() should be compatible with parameter $value (int) of method MethodSignature\Foo::doFoo()',
				24,
			],
		]);
	}

	public function testBug2950(): void
	{
		$this->reportMaybes = true;
		$this->reportStatic = true;
		$this->analyse([__DIR__ . '/data/bug-2950.php'], []);
	}

	public function testBug3997(): void
	{
		$this->reportMaybes = true;
		$this->reportStatic = true;
		$this->analyse([__DIR__ . '/data/bug-3997.php'], [
			[
				'Return type (int) of method Bug3997\Baz::count() should be covariant with return type (int<0, max>) of method Countable::count()',
				35,
			],
			[
				'Return type (int) of method Bug3997\Lorem::count() should be covariant with return type (int<0, max>) of method Countable::count()',
				49,
			],
			[
				'Return type (string) of method Bug3997\Ipsum::count() should be compatible with return type (int<0, max>) of method Countable::count()',
				63,
			],
		]);
	}

	public function testBug4003(): void
	{
		$this->reportMaybes = true;
		$this->reportStatic = true;
		$this->analyse([__DIR__ . '/data/bug-4003.php'], [
			[
				'Return type (string) of method Bug4003\Baz::foo() should be compatible with return type (int) of method Bug4003\Boo::foo()',
				15,
			],
			[
				'Parameter #1 $test (string) of method Bug4003\Ipsum::doFoo() should be compatible with parameter $test (int) of method Bug4003\Lorem::doFoo()',
				38,
			],
		]);
	}

	public function testBug4017(): void
	{
		if (PHP_VERSION_ID < 70400) {
			$this->markTestSkipped('Test requires PHP 7.4.');
		}
		$this->reportMaybes = true;
		$this->reportStatic = true;
		$this->analyse([__DIR__ . '/data/bug-4017.php'], []);
	}

	public function testBug4017Two(): void
	{
		$this->reportMaybes = true;
		$this->reportStatic = true;
		$this->analyse([__DIR__ . '/data/bug-4017_2.php'], [
			[
				'Parameter #1 $a (Bug4017_2\Foo) of method Bug4017_2\Lorem::doFoo() should be compatible with parameter $a (stdClass) of method Bug4017_2\Bar<stdClass>::doFoo()',
				51,
			],
		]);
	}

	public function testBug4017Three(): void
	{
		$this->reportMaybes = true;
		$this->reportStatic = true;
		$this->analyse([__DIR__ . '/data/bug-4017_3.php'], [
			[
				'Parameter #1 $a (T of stdClass) of method Bug4017_3\Lorem::doFoo() should be compatible with parameter $a (Bug4017_3\Foo) of method Bug4017_3\Bar::doFoo()',
				45,
			],
		]);
	}

	public function testBug4023(): void
	{
		$this->reportMaybes = true;
		$this->reportStatic = true;
		$this->analyse([__DIR__ . '/data/bug-4023.php'], []);
	}

	public function testBug4006(): void
	{
		$this->reportMaybes = true;
		$this->reportStatic = true;
		$this->analyse([__DIR__ . '/data/bug-4006.php'], []);
	}

	public function testBug4084(): void
	{
		$this->reportMaybes = true;
		$this->reportStatic = true;
		$this->analyse([__DIR__ . '/data/bug-4084.php'], []);
	}

	public function testBug3523(): void
	{
		$this->reportMaybes = true;
		$this->reportStatic = true;
		$this->analyse([__DIR__ . '/data/bug-3523.php'], [
			[
				'Return type (Bug3523\Baz) of method Bug3523\Baz::deserialize() should be covariant with return type (static(Bug3523\FooInterface)) of method Bug3523\FooInterface::deserialize()',
				40,
			],
		]);
	}

	public function testBug4707(): void
	{
		$this->reportMaybes = true;
		$this->reportStatic = true;
		$this->analyse([__DIR__ . '/data/bug-4707.php'], [
			[
				'Return type (list<Bug4707\Row2>) of method Bug4707\Block2::getChildren() should be compatible with return type (list<Bug4707\ChildNodeInterface<static(Bug4707\ParentNodeInterface)>>) of method Bug4707\ParentNodeInterface::getChildren()',
				38,
			],
		]);
	}

	public function testBug4707Covariant(): void
	{
		$this->reportMaybes = true;
		$this->reportStatic = true;
		$this->analyse([__DIR__ . '/data/bug-4707-covariant.php'], [
			[
				'Return type (list<Bug4707Covariant\Row2>) of method Bug4707Covariant\Block2::getChildren() should be covariant with return type (list<Bug4707Covariant\ChildNodeInterface<static(Bug4707Covariant\ParentNodeInterface)>>) of method Bug4707Covariant\ParentNodeInterface::getChildren()',
				38,
			],
		]);
	}

	public function testBug4707Two(): void
	{
		$this->reportMaybes = true;
		$this->reportStatic = true;
		$this->analyse([__DIR__ . '/data/bug-4707-two.php'], []);
	}

	public function testBug4729(): void
	{
		$this->reportMaybes = true;
		$this->reportStatic = true;
		$this->analyse([__DIR__ . '/data/bug-4729.php'], []);
	}

	public function testBug4854(): void
	{
		$this->reportMaybes = true;
		$this->reportStatic = true;
		$this->analyse([__DIR__ . '/data/bug-4854.php'], []);
	}

	public function testMemcachePoolGet(): void
	{
		$this->reportMaybes = true;
		$this->reportStatic = true;
		$this->analyse([__DIR__ . '/data/memcache-pool-get.php'], []);
	}

	public function testOverridenMethodWithConditionalReturnType(): void
	{
		$this->reportMaybes = true;
		$this->reportStatic = true;
		$this->analyse([__DIR__ . '/data/overriden-method-with-conditional-return-type.php'], [
			[
				'Return type (($p is int ? stdClass : string)) of method OverridenMethodWithConditionalReturnType\Bar2::doFoo() should be compatible with return type (($p is int ? int : string)) of method OverridenMethodWithConditionalReturnType\Foo::doFoo()',
				37,
			],
		]);
	}

	public function testBug7652(): void
	{
		if (PHP_VERSION_ID < 80100) {
			$this->markTestSkipped('Test requires PHP 8.1.');
		}

		$this->reportMaybes = true;
		$this->reportStatic = true;
		$this->analyse([__DIR__ . '/data/bug-7652.php'], [
			[
				'Return type mixed of method Bug7652\Options::offsetGet() is not covariant with tentative return type mixed of method ArrayAccess<key-of<TArray of array>,value-of<TArray of array>>::offsetGet().',
				23,
				'Make it covariant, or use the #[\ReturnTypeWillChange] attribute to temporarily suppress the error.',
			],
			[
				'Parameter #1 $offset (TOffset of key-of<TArray of array>) of method Bug7652\Options::offsetSet() should be contravariant with parameter $offset (key-of<array>|null) of method ArrayAccess<key-of<TArray of array>,value-of<TArray of array>>::offsetSet()',
				30,
			],
		]);
	}

	public function testBug7103(): void
	{
		$this->reportMaybes = true;
		$this->reportStatic = true;
		$this->analyse([__DIR__ . '/data/bug-7103.php'], []);
	}

	public function testListReturnTypeCovariance(): void
	{
		$this->reportMaybes = true;
		$this->reportStatic = true;
		$this->analyse([__DIR__ . '/data/list-return-type-covariance.php'], [
			[
				'Return type (array<int, string>) of method ListReturnTypeCovariance\ListChild::returnsList() should be covariant with return type (list<string>) of method ListReturnTypeCovariance\ListParent::returnsList()',
				17,
			],
		]);
	}

	public function testRuleError(): void
	{
		$this->reportMaybes = true;
		$this->reportStatic = true;
		$this->analyse([__DIR__ . '/data/rule-error-signature.php'], [
			[
				'Return type (array<PHPStan\Rules\RuleError|string>) of method RuleErrorSignature\Baz::processNode() should be covariant with return type (list<PHPStan\Rules\IdentifierRuleError>) of method PHPStan\Rules\Rule<PhpParser\Node>::processNode()',
				64,
				'Rules can no longer return plain strings. See: https://phpstan.org/blog/using-rule-error-builder',
			],
			[
				'Return type (array<string>) of method RuleErrorSignature\Lorem::processNode() should be compatible with return type (list<PHPStan\Rules\IdentifierRuleError>) of method PHPStan\Rules\Rule<PhpParser\Node>::processNode()',
				85,
				'Rules can no longer return plain strings. See: https://phpstan.org/blog/using-rule-error-builder',
			],
			[
				'Return type (array<PHPStan\Rules\RuleError>) of method RuleErrorSignature\Ipsum::processNode() should be covariant with return type (list<PHPStan\Rules\IdentifierRuleError>) of method PHPStan\Rules\Rule<PhpParser\Node>::processNode()',
				106,
				'Errors are missing identifiers. See: https://phpstan.org/blog/using-rule-error-builder',
			],
			[
				'Return type (array<PHPStan\Rules\IdentifierRuleError>) of method RuleErrorSignature\Dolor::processNode() should be covariant with return type (list<PHPStan\Rules\IdentifierRuleError>) of method PHPStan\Rules\Rule<PhpParser\Node>::processNode()',
				127,
				'Return type must be a list. See: https://phpstan.org/blog/using-rule-error-builder',
			],
		]);
	}

	public function testBug9905(): void
	{
		$this->reportMaybes = true;
		$this->reportStatic = true;
		$this->analyse([__DIR__ . '/data/bug-9905.php'], []);
	}

}
