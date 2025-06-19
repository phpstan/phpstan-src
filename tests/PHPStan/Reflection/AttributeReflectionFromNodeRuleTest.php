<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

use PhpParser\Node;
use PhpParser\NodeAbstract;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassMethodNode;
use PHPStan\Node\InFunctionNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Testing\RuleTestCase;
use PHPStan\Type\VerbosityLevel;
use PHPUnit\Framework\Attributes\RequiresPhp;
use function count;
use function implode;
use function sprintf;

/**
 * @extends RuleTestCase<Rule<NodeAbstract>>
 */
class AttributeReflectionFromNodeRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new /** @implements Rule<NodeAbstract> */ class implements Rule {

			public function getNodeType(): string
			{
				return NodeAbstract::class;
			}

			public function processNode(Node $node, Scope $scope): array
			{
				if ($node instanceof InClassMethodNode) {
					$reflection = $node->getMethodReflection();
				} elseif ($node instanceof InFunctionNode) {
					$reflection = $node->getFunctionReflection();
				} else {
					return [];
				}

				$parts = [];
				foreach ($reflection->getAttributes() as $attribute) {
					$args = [];
					foreach ($attribute->getArgumentTypes() as $argName => $argType) {
						$args[] = sprintf('%s: %s', $argName, $argType->describe(VerbosityLevel::precise()));
					}

					$parts[] = sprintf('#[%s(%s)]', $attribute->getName(), implode(', ', $args));
				}

				foreach ($reflection->getParameters() as $parameter) {
					$parameterAttributes = [];
					foreach ($parameter->getAttributes() as $parameterAttribute) {
						$parameterArgs = [];
						foreach ($parameterAttribute->getArgumentTypes() as $argName => $argType) {
							$parameterArgs[] = sprintf('%s: %s', $argName, $argType->describe(VerbosityLevel::precise()));
						}
						$parameterAttributes[] = sprintf('#[%s(%s)]', $parameterAttribute->getName(), implode(', ', $parameterArgs));
					}

					if (count($parameterAttributes) === 0) {
						continue;
					}

					$parts[] = sprintf('$%s: %s', $parameter->getName(), implode(', ', $parameterAttributes));
				}

				if (count($parts) === 0) {
					return [];
				}

				return [
					RuleErrorBuilder::message(implode(', ', $parts))->identifier('test.attributes')->build(),
				];
			}

		};
	}

	#[RequiresPhp('>= 8.0')]
	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/attribute-reflection.php'], [
			[
				'#[AttributeReflectionTest\MyAttr(one: 7, two: 8)], $test: #[AttributeReflectionTest\MyAttr(one: 9, two: 10)]',
				28,
			],
			[
				'#[AttributeReflectionTest\MyAttr()]',
				39,
			],
			[
				'#[AttributeReflectionTest\Nonexistent()]',
				44,
			],
			[
				'#[AttributeReflectionTest\MyAttr(one: 11, two: 12)]',
				54,
			],
			[
				'#[AttributeReflectionTest\MyAttr(one: 28, two: 29)]',
				59,
			],
		]);
	}

}
