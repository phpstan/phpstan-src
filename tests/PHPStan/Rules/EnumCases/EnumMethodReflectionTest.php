<?php declare(strict_types = 1);

namespace PHPStan\Rules\EnumCases;

use Nette\Utils\Strings;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassNode;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<Rule>
 */
class EnumMethodReflectionTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new class implements Rule {

			public function getNodeType(): string
			{
				return InClassNode::class;
			}

			public function processNode(Node $node, Scope $scope): array
			{
				foreach ($node->getClassReflection()->getNativeReflection()->getMethods() as $method) {
					$method->getStartLine();
				}

				return [];
			}
		};
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/enum-reflection.php'], []);
	}

}
