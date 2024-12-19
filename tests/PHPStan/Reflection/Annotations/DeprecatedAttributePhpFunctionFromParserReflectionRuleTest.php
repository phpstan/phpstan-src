<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Annotations;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassMethodNode;
use PHPStan\Node\InFunctionNode;
use PHPStan\Node\InPropertyHookNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Testing\RuleTestCase;
use function sprintf;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<Rule>
 */
class DeprecatedAttributePhpFunctionFromParserReflectionRuleTest extends RuleTestCase
{

	/**
	 * @return Rule<Node>
	 */
	protected function getRule(): Rule
	{
		return new /** @implements Rule<Node> */ class implements Rule {

			public function getNodeType(): string
			{
				return Node::class;
			}

			public function processNode(Node $node, Scope $scope): array
			{
				if ($node instanceof InFunctionNode) {
					$reflection = $node->getFunctionReflection();
				} elseif ($node instanceof InClassMethodNode) {
					$reflection = $node->getMethodReflection();
				} elseif ($node instanceof InPropertyHookNode) {
					$reflection = $node->getHookReflection();
				} else {
					return [];
				}

				if (!$reflection->isDeprecated()->yes()) {
					return [
						RuleErrorBuilder::message('Not deprecated')->identifier('tests.notDeprecated')->build(),
					];
				}

				$description = $reflection->getDeprecatedDescription();
				if ($description === null) {
					return [
						RuleErrorBuilder::message('Deprecated')->identifier('tests.deprecated')->build(),
					];
				}

				return [
					RuleErrorBuilder::message(sprintf('Deprecated: %s', $description))->identifier('tests.deprecated')->build(),
				];
			}

		};
	}

	public function testFunctionRule(): void
	{
		$this->analyse([__DIR__ . '/data/deprecated-attribute-functions.php'], [
			[
				'Not deprecated',
				7,
			],
			[
				'Deprecated',
				12,
			],
			[
				'Deprecated: msg',
				18,
			],
			[
				'Deprecated: msg2',
				24,
			],
			[
				'Deprecated: DeprecatedAttributeFunctions\\fooWithConstantMessage',
				30,
			],
		]);
	}

	public function testMethodRule(): void
	{
		$this->analyse([__DIR__ . '/data/deprecated-attribute-methods.php'], [
			[
				'Not deprecated',
				10,
			],
			[
				'Deprecated',
				15,
			],
			[
				'Deprecated: msg',
				21,
			],
			[
				'Deprecated: msg2',
				27,
			],
		]);
	}

	public function testPropertyHookRule(): void
	{
		if (PHP_VERSION_ID < 80400) {
			$this->markTestSkipped('Test requires PHP 8.4.');
		}

		$this->analyse([__DIR__ . '/data/deprecated-attribute-property-hooks.php'], [
			[
				'Not deprecated',
				11,
			],
			[
				'Deprecated',
				17,
			],
			[
				'Deprecated: msg',
				24,
			],
			[
				'Deprecated: msg2',
				31,
			],
			[
				'Deprecated: $m::get+DeprecatedAttributePropertyHooks\Foo::$m::get+m',
				38,
			],
		]);
	}

}
