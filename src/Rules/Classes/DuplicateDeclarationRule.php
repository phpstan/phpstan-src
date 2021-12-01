<?php declare(strict_types = 1);

namespace PHPStan\Rules\Classes;

use PhpParser\Node;
use PhpParser\Node\Stmt\ClassConst;
use PhpParser\Node\Stmt\EnumCase;
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

		$declaredClassConstantsOrEnumCases = [];
		foreach ($node->getOriginalNode()->stmts as $stmtNode) {
			if ($stmtNode instanceof EnumCase) {
				if (array_key_exists($stmtNode->name->name, $declaredClassConstantsOrEnumCases)) {
					$errors[] = RuleErrorBuilder::message(sprintf(
						'Cannot redeclare enum case %s::%s.',
						$classReflection->getDisplayName(),
						$stmtNode->name->name
					))->line($stmtNode->getLine())->nonIgnorable()->build();
				} else {
					$declaredClassConstantsOrEnumCases[$stmtNode->name->name] = true;
				}
			} elseif ($stmtNode instanceof ClassConst) {
				foreach ($stmtNode->consts as $classConstNode) {
					if (array_key_exists($classConstNode->name->name, $declaredClassConstantsOrEnumCases)) {
						$errors[] = RuleErrorBuilder::message(sprintf(
							'Cannot redeclare constant %s::%s.',
							$classReflection->getDisplayName(),
							$classConstNode->name->name
						))->line($classConstNode->getLine())->nonIgnorable()->build();
					} else {
						$declaredClassConstantsOrEnumCases[$classConstNode->name->name] = true;
					}
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
