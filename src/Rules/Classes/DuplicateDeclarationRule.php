<?php declare(strict_types = 1);

namespace PHPStan\Rules\Classes;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassNode;
use PHPStan\Rules\RuleErrorBuilder;
use function array_key_exists;
use function sprintf;

/**
 * @implements \PHPStan\Rules\Rule<\PHPStan\Node\InClassNode>
 */
class DuplicateDeclarationRule implements \PHPStan\Rules\Rule
{

	public function getNodeType(): string
	{
		return InClassNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$classScope = $scope->getClassReflection();
		if ($classScope === null) {
			throw new \PHPStan\ShouldNotHappenException();
		}

		/** @var InClassNode $node */

		$errors = [];

		$declaredClassConstants = [];
		foreach ($node->getOriginalNode()->getConstants() as $constDecl) {
			foreach ($constDecl->consts as $const) {
				if (array_key_exists($const->name->name, $declaredClassConstants)) {
					$errors[] = RuleErrorBuilder::message(sprintf(
						'Cannot redeclare constant %s::%s.',
						$classScope->getName(),
						$const->name->name
					))->line($const->getLine())->nonIgnorable()->build();
				} else {
					$declaredClassConstants[$const->name->name] = true;
				}
			}
		}

		$declaredProperties = [];
		foreach ($node->getOriginalNode()->getProperties() as $propertyDecl) {
			foreach ($propertyDecl->props as $property) {
				if (array_key_exists($property->name->name, $declaredProperties)) {
					$errors[] = RuleErrorBuilder::message(sprintf(
						'Cannot redeclare property %s::$%s.',
						$classScope->getName(),
						$property->name->name
					))->line($property->getLine())->nonIgnorable()->build();
				} else {
					$declaredProperties[$property->name->name] = true;
				}
			}
		}

		$declaredFunctions = [];
		foreach ($node->getOriginalNode()->getMethods() as $method) {
			if (array_key_exists($method->name->name, $declaredFunctions)) {
				$errors[] = RuleErrorBuilder::message(sprintf(
					'Cannot redeclare method %s::%s().',
					$classScope->getName(),
					$method->name->name
				))->line($method->getStartLine())->nonIgnorable()->build();
			} else {
				$declaredFunctions[$method->name->name] = true;
			}
		}

		return $errors;
	}

}
