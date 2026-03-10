<?php declare(strict_types = 1);

namespace PHPStan\Rules\Classes;

use PhpParser\Node;
use PhpParser\Node\Stmt\ClassConst;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Node\InTraitNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\ShouldNotHappenException;
use function array_key_exists;
use function is_string;
use function sprintf;
use function strtolower;

/**
 * @implements Rule<InTraitNode>
 */
#[RegisteredRule(level: 0)]
final class DuplicateTraitDeclarationRule implements Rule
{

	public function getNodeType(): string
	{
		return InTraitNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$traitReflection = $node->getTraitReflection();

		$errors = [];

		$declaredClassConstants = [];
		foreach ($node->getOriginalNode()->stmts as $stmtNode) {
			if (!($stmtNode instanceof ClassConst)) {
				continue;
			}
			foreach ($stmtNode->consts as $classConstNode) {
				if (array_key_exists($classConstNode->name->name, $declaredClassConstants)) {
					$errors[] = RuleErrorBuilder::message(sprintf(
						'Cannot redeclare constant %s::%s.',
						$traitReflection->getDisplayName(),
						$classConstNode->name->name,
					))->identifier('trait.duplicateConstant')
						->line($classConstNode->getStartLine())
						->nonIgnorable()
						->build();
				} else {
					$declaredClassConstants[$classConstNode->name->name] = true;
				}
			}
		}

		$declaredProperties = [];
		foreach ($node->getOriginalNode()->getProperties() as $propertyDecl) {
			foreach ($propertyDecl->props as $property) {
				if (array_key_exists($property->name->name, $declaredProperties)) {
					$errors[] = RuleErrorBuilder::message(sprintf(
						'Cannot redeclare property %s::$%s.',
						$traitReflection->getDisplayName(),
						$property->name->name,
					))->identifier('trait.duplicateProperty')
						->line($property->getStartLine())
						->nonIgnorable()
						->build();
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
						throw new ShouldNotHappenException();
					}

					$propertyName = $param->var->name;

					if (array_key_exists($propertyName, $declaredProperties)) {
						$errors[] = RuleErrorBuilder::message(sprintf(
							'Cannot redeclare property %s::$%s.',
							$traitReflection->getDisplayName(),
							$propertyName,
						))->identifier('trait.duplicateProperty')
							->line($param->getStartLine())
							->nonIgnorable()
							->build();
					} else {
						$declaredProperties[$propertyName] = true;
					}
				}
			}
			if (array_key_exists(strtolower($method->name->name), $declaredFunctions)) {
				$errors[] = RuleErrorBuilder::message(sprintf(
					'Cannot redeclare method %s::%s().',
					$traitReflection->getDisplayName(),
					$method->name->name,
				))->identifier('trait.duplicateMethod')
					->line($method->getStartLine())
					->nonIgnorable()
					->build();
			} else {
				$declaredFunctions[strtolower($method->name->name)] = true;
			}
		}

		return $errors;
	}

}
