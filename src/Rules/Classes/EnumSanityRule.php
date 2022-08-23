<?php declare(strict_types = 1);

namespace PHPStan\Rules\Classes;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\ShouldNotHappenException;
use function array_key_exists;
use function in_array;
use function sprintf;

/**
 * @implements Rule<Node\Stmt\Enum_>
 */
class EnumSanityRule implements Rule
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

		if ($node->namespacedName === null) {
			throw new ShouldNotHappenException();
		}

		foreach ($node->getMethods() as $methodNode) {
			if ($methodNode->isAbstract()) {
				$errors[] = RuleErrorBuilder::message(sprintf(
					'Enum %s contains abstract method %s().',
					$node->namespacedName->toString(),
					$methodNode->name->name,
				))->line($methodNode->getLine())->nonIgnorable()->build();
			}

			$lowercasedMethodName = $methodNode->name->toLowerString();

			if ($methodNode->isMagic()) {
				if ($lowercasedMethodName === '__construct') {
					$errors[] = RuleErrorBuilder::message(sprintf(
						'Enum %s contains constructor.',
						$node->namespacedName->toString(),
					))->line($methodNode->getLine())->nonIgnorable()->build();
				} elseif ($lowercasedMethodName === '__destruct') {
					$errors[] = RuleErrorBuilder::message(sprintf(
						'Enum %s contains destructor.',
						$node->namespacedName->toString(),
					))->line($methodNode->getLine())->nonIgnorable()->build();
				} elseif (!array_key_exists($lowercasedMethodName, self::ALLOWED_MAGIC_METHODS)) {
					$errors[] = RuleErrorBuilder::message(sprintf(
						'Enum %s contains magic method %s().',
						$node->namespacedName->toString(),
						$methodNode->name->name,
					))->line($methodNode->getLine())->nonIgnorable()->build();
				}
			}

			// serialize/unserialize is not considered a magic method by PhpParser
			if (in_array($lowercasedMethodName, ['__serialize', '__unserialize'], true)) {
				$errors[] = RuleErrorBuilder::message(sprintf(
					'Enum %s contains magic method %s().',
					$node->namespacedName->toString(),
					$methodNode->name->name,
				))->line($methodNode->getLine())->nonIgnorable()->build();
			}

			if ($lowercasedMethodName === 'cases') {
				$errors[] = RuleErrorBuilder::message(sprintf(
					'Enum %s cannot redeclare native method %s().',
					$node->namespacedName->toString(),
					$methodNode->name->name,
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
				$methodNode->name->name,
			))->line($methodNode->getLine())->nonIgnorable()->build();
		}

		if (
			$node->scalarType !== null
			&& $node->scalarType->name !== 'int'
			&& $node->scalarType->name !== 'string'
		) {
			$errors[] = RuleErrorBuilder::message(sprintf(
				'Backed enum %s can have only "int" or "string" type.',
				$node->namespacedName->toString(),
			))->line($node->scalarType->getLine())->nonIgnorable()->build();
		}

		return $errors;
	}

}
