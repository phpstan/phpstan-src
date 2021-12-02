<?php declare(strict_types = 1);

namespace PHPStan\Rules\Classes;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements \PHPStan\Rules\Rule<Node\Stmt\Enum_>
 */
class EnumSanityRule implements \PHPStan\Rules\Rule
{

	public function getNodeType(): string
	{
		return Node\Stmt\Enum_::class;
	}

	/**
	 * @param Node\Stmt\Enum_ $node
	 */
	public function processNode(Node $node, Scope $scope): array
	{
		$errors = [];

		foreach ($node->getMethods() as $methodNode) {
			if ($methodNode->isAbstract()) {
				$errors[] = RuleErrorBuilder::message(sprintf(
					'Enum %s contains abstract method %s().',
					$node->namespacedName->toString(),
					$methodNode->name->name
				))->line($methodNode->getLine())->nonIgnorable()->build();
			}

			$lowercasedMethodName = $methodNode->name->toLowerString();

			if ($lowercasedMethodName === '__construct') {
				$errors[] = RuleErrorBuilder::message(sprintf(
					'Enum %s contains constructor.',
					$node->namespacedName->toString()
				))->line($methodNode->getLine())->nonIgnorable()->build();
			} elseif ($lowercasedMethodName === '__destruct') {
				$errors[] = RuleErrorBuilder::message(sprintf(
					'Enum %s contains destructor.',
					$node->namespacedName->toString()
				))->line($methodNode->getLine())->nonIgnorable()->build();
			}
		}

		return $errors;
	}

}
