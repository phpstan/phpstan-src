<?php declare(strict_types = 1);

namespace PHPStan\Rules\Classes;

use PhpParser\Node;
use PhpParser\Node\Stmt\ClassConst;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\EnumCase;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\ShouldNotHappenException;
use function array_key_exists;
use function is_string;
use function sprintf;
use function strtolower;

final class DuplicateDeclarationHelper
{

	/**
	 * @return list<IdentifierRuleError>
	 */
	public static function checkClassLike(ClassLike $classLike, string $displayName, string $identifierType): array
	{
		$errors = [];

		$declaredClassConstantsOrEnumCases = [];
		foreach ($classLike->stmts as $stmtNode) {
			if ($stmtNode instanceof EnumCase) {
				if (array_key_exists($stmtNode->name->name, $declaredClassConstantsOrEnumCases)) {
					$errors[] = RuleErrorBuilder::message(sprintf(
						'Cannot redeclare enum case %s::%s.',
						$displayName,
						$stmtNode->name->name,
					))->identifier(sprintf('%s.duplicateEnumCase', $identifierType))
						->line($stmtNode->getStartLine())
						->nonIgnorable()
						->build();
				} else {
					$declaredClassConstantsOrEnumCases[$stmtNode->name->name] = true;
				}
			} elseif ($stmtNode instanceof ClassConst) {
				foreach ($stmtNode->consts as $classConstNode) {
					if (array_key_exists($classConstNode->name->name, $declaredClassConstantsOrEnumCases)) {
						$errors[] = RuleErrorBuilder::message(sprintf(
							'Cannot redeclare constant %s::%s.',
							$displayName,
							$classConstNode->name->name,
						))->identifier(sprintf('%s.duplicateConstant', $identifierType))
							->line($classConstNode->getStartLine())
							->nonIgnorable()
							->build();
					} else {
						$declaredClassConstantsOrEnumCases[$classConstNode->name->name] = true;
					}
				}
			}
		}

		$declaredProperties = [];
		foreach ($classLike->getProperties() as $propertyDecl) {
			foreach ($propertyDecl->props as $property) {
				if (array_key_exists($property->name->name, $declaredProperties)) {
					$errors[] = RuleErrorBuilder::message(sprintf(
						'Cannot redeclare property %s::$%s.',
						$displayName,
						$property->name->name,
					))->identifier(sprintf('%s.duplicateProperty', $identifierType))
						->line($property->getStartLine())
						->nonIgnorable()
						->build();
				} else {
					$declaredProperties[$property->name->name] = true;
				}
			}
		}

		$declaredFunctions = [];
		foreach ($classLike->getMethods() as $method) {
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
							$displayName,
							$propertyName,
						))->identifier(sprintf('%s.duplicateProperty', $identifierType))
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
					$displayName,
					$method->name->name,
				))->identifier(sprintf('%s.duplicateMethod', $identifierType))
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
