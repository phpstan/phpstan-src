<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use ArrayAccess;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\StaticPropertyFetch;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Node\PropertyAssignNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ObjectType;
use function sprintf;

/**
 * @implements Rule<PropertyAssignNode>
 */
#[RegisteredRule(level: 3)]
final class ReadOnlyPropertyIndirectModificationRule implements Rule
{

	public function __construct(
		private PropertyReflectionFinder $propertyReflectionFinder,
	)
	{
	}

	public function getNodeType(): string
	{
		return PropertyAssignNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$propertyFetch = $node->getPropertyFetch();
		if (!$propertyFetch instanceof PropertyFetch) {
			return [];
		}

		return $this->checkVarChain($propertyFetch->var, $scope);
	}

	/**
	 * @return list<IdentifierRuleError>
	 */
	private function checkVarChain(Expr $expr, Scope $scope): array
	{
		$errors = [];

		while (true) {
			if ($expr instanceof ArrayDimFetch) {
				while ($expr instanceof ArrayDimFetch) {
					$expr = $expr->var;
				}

				if ($expr instanceof PropertyFetch && $expr->name instanceof Node\Identifier) {
					$propertyType = $scope->getType($expr);
					if (!(new ObjectType(ArrayAccess::class))->isSuperTypeOf($propertyType)->yes()) {
						$reflections = $this->propertyReflectionFinder->findPropertyReflectionsFromNode($expr, $scope);
						foreach ($reflections as $reflection) {
							$nativeReflection = $reflection->getNativeReflection();
							if ($nativeReflection === null) {
								continue;
							}
							if ($nativeReflection->isReadOnly()) {
								$declaringClass = $nativeReflection->getDeclaringClass();
								$errors[] = RuleErrorBuilder::message(sprintf(
									'Readonly property %s::$%s is indirectly modified.',
									$declaringClass->getDisplayName(),
									$reflection->getName(),
								))
									->line($expr->name->getStartLine())
									->identifier('property.readOnlyIndirectModification')
									->build();
							} elseif ($nativeReflection->isReadOnlyByPhpDoc()) {
								if ($nativeReflection->isAllowedPrivateMutation()) {
									continue;
								}
								$declaringClass = $nativeReflection->getDeclaringClass();
								$errors[] = RuleErrorBuilder::message(sprintf(
									'@readonly property %s::$%s is indirectly modified.',
									$declaringClass->getDisplayName(),
									$reflection->getName(),
								))
									->line($expr->name->getStartLine())
									->identifier('property.readOnlyByPhpDocIndirectModification')
									->build();
							}
						}
					}

					$expr = $expr->var;
					continue;
				}

				if ($expr instanceof StaticPropertyFetch) {
					$expr = $expr->class instanceof Node\Name ? null : $expr->class;
					if ($expr === null) {
						break;
					}
					continue;
				}

				break;
			}

			if ($expr instanceof PropertyFetch) {
				$expr = $expr->var;
				continue;
			}

			break;
		}

		return $errors;
	}

}
