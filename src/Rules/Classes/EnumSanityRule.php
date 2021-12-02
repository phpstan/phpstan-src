<?php declare(strict_types = 1);

namespace PHPStan\Rules\Classes;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\RuleErrorBuilder;
use function array_key_exists;

/**
 * @implements \PHPStan\Rules\Rule<Node\Stmt\Enum_>
 */
class EnumSanityRule implements \PHPStan\Rules\Rule
{

	private const ALLOWED_MAGIC_METHODS = [
		'__call' => true,
		'__callstatic' => true,
		'__invoke' => true,
	];

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

			if ($methodNode->isMagic()) {
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
				} elseif (!array_key_exists($lowercasedMethodName, self::ALLOWED_MAGIC_METHODS)) {
					$errors[] = RuleErrorBuilder::message(sprintf(
						'Enum %s contains magic method %s().',
						$node->namespacedName->toString(),
						$methodNode->name->name
					))->line($methodNode->getLine())->nonIgnorable()->build();
				}
			}

			if ($lowercasedMethodName === 'cases') {
				$errors[] = RuleErrorBuilder::message(sprintf(
					'Enum %s cannot redeclare native method %s().',
					$node->namespacedName->toString(),
					$methodNode->name->name
				))->line($methodNode->getLine())->nonIgnorable()->build();
			}

			if ($node->scalarType === null) {
				continue;
			}

			if ($lowercasedMethodName !== 'from' && $lowercasedMethodName !== 'tryfrom') {
				continue;
			}

			$errors[] = RuleErrorBuilder::message(sprintf(
				'Enum %s cannot redeclare native method %s().',
				$node->namespacedName->toString(),
				$methodNode->name->name
			))->line($methodNode->getLine())->nonIgnorable()->build();
		}

		return $errors;
	}

}
