<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;
use function array_filter;
use function array_values;
use function str_starts_with;

/**
 * @extends RuleTestCase<Rule<Node>>
 */
class Bug15292Test extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new class implements Rule {

			public function getNodeType(): string
			{
				return Node::class;
			}

			public function processNode(Node $node, Scope $scope): array
			{
				return [];
			}

		};
	}

	#[RequiresPhp('>= 8.1')]
	public function testCallableLikeValuesAreNotReflected(): void
	{
		Bug15292MethodsClassReflectionExtension::$askedMethods = [];
		$this->analyse([__DIR__ . '/data/bug-15292.php'], []);
		$this->assertSame([], $this->getAskedMethods('Bug15292\\'));
	}

	public function testCallableLikeGenericArgumentsAreNotReflected(): void
	{
		Bug15292MethodsClassReflectionExtension::$askedMethods = [];
		$this->analyse([__DIR__ . '/data/bug-15292-generic.php'], []);
		$this->assertSame([], $this->getAskedMethods('Bug15292Generic\\'));
	}

	public function testCallableIsReflected(): void
	{
		Bug15292MethodsClassReflectionExtension::$askedMethods = [];
		$this->analyse([__DIR__ . '/data/bug-15292-callable.php'], []);
		$this->assertContains('Bug15292Callable\BigContainer::callable_ident', $this->getAskedMethods('Bug15292Callable\\'));
	}

	/**
	 * @return list<string>
	 */
	private function getAskedMethods(string $prefix): array
	{
		return array_values(array_filter(
			Bug15292MethodsClassReflectionExtension::$askedMethods,
			static fn (string $method): bool => str_starts_with($method, $prefix),
		));
	}

	public static function getAdditionalConfigFiles(): array
	{
		return [
			__DIR__ . '/bug-15292.neon',
		];
	}

}
