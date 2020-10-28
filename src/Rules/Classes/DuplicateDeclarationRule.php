<?php declare(strict_types = 1);

namespace PHPStan\Rules\Classes;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassNode;
use PHPStan\Rules\RuleErrorBuilder;
use function array_key_exists;
use function sprintf;
use function strtolower;

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
		$classReflection = $scope->getClassReflection();
		if ($classReflection === null) {
			throw new \PHPStan\ShouldNotHappenException();
		}

		$errors = [];

		$declaredClassConstants = [];
		foreach ($node->getOriginalNode()->getConstants() as $constDecl) {
			foreach ($constDecl->consts as $const) {
				if (array_key_exists($const->name->name, $declaredClassConstants)) {
					$errors[] = RuleErrorBuilder::message(sprintf(
						'Cannot redeclare constant %s::%s.',
						$classReflection->getDisplayName(),
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
						$classReflection->getDisplayName(),
						$property->name->name
					))->line($property->getLine())->nonIgnorable()->build();
				} else {
					$declaredProperties[$property->name->name] = true;
				}
			}
		}

		$declaredFunctions = [];
		foreach ($node->getOriginalNode()->getMethods() as $method) {
			if ($method->name->toLowerString() === '__construct') {
				foreach ($method->params as $param) {
					if ($param->flags === 0) {
						continue;
					}

					if (!$param->var instanceof Node\Expr\Variable || !is_string($param->var->name)) {
						throw new \PHPStan\ShouldNotHappenException();
					}

					$propertyName = $param->var->name;

					if (array_key_exists($propertyName, $declaredProperties)) {
						$errors[] = RuleErrorBuilder::message(sprintf(
							'Cannot redeclare property %s::$%s.',
							$classReflection->getDisplayName(),
							$propertyName
						))->line($param->getLine())->nonIgnorable()->build();
					} else {
						$declaredProperties[$propertyName] = true;
					}
				}
			}
			if (array_key_exists(strtolower($method->name->name), $declaredFunctions)) {
				$errors[] = RuleErrorBuilder::message(sprintf(
					'Cannot redeclare method %s::%s().',
					$classReflection->getDisplayName(),
					$method->name->name
				))->line($method->getStartLine())->nonIgnorable()->build();
			} else {
				$declaredFunctions[strtolower($method->name->name)] = true;
			}
		}

		return $errors;
	}

}
