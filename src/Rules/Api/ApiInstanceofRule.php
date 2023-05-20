<?php declare(strict_types = 1);

namespace PHPStan\Rules\Api;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\UnionType;
use function count;
use function sprintf;
use function strtolower;

/**
 * @implements Rule<Node\Expr\Instanceof_>
 */
class ApiInstanceofRule implements Rule
{

	public function __construct(
		private ApiRuleHelper $apiRuleHelper,
		private ReflectionProvider $reflectionProvider,
	)
	{
	}

	public function getNodeType(): string
	{
		return Node\Expr\Instanceof_::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!$node->class instanceof Node\Name) {
			return [];
		}

		$className = $scope->resolveName($node->class);
		if (!$this->reflectionProvider->hasClass($className)) {
			return [];
		}

		$classReflection = $this->reflectionProvider->getClass($className);
		if (!$this->apiRuleHelper->isPhpStanCode($scope, $classReflection->getName(), $classReflection->getFileName())) {
			return [];
		}

		$ruleError = RuleErrorBuilder::message(sprintf(
			'Asking about instanceof %s is not covered by backward compatibility promise. The %s might change in a minor PHPStan version.',
			$classReflection->getDisplayName(),
			strtolower($classReflection->getClassTypeDescription()),
		))
			->identifier(sprintf('phpstanApi.%s', strtolower($classReflection->getClassTypeDescription())))
			->tip(sprintf(
				"If you think it should be covered by backward compatibility promise, open a discussion:\n   %s\n\n   See also:\n   https://phpstan.org/developing-extensions/backward-compatibility-promise",
				'https://github.com/phpstan/phpstan/discussions',
			))->build();

		$docBlock = $classReflection->getResolvedPhpDoc();
		if ($docBlock === null) {
			return [$ruleError];
		}

		foreach ($docBlock->getPhpDocNodes() as $phpDocNode) {
			$apiTags = $phpDocNode->getTagsByName('@api');
			if (count($apiTags) > 0) {
				return $this->processCoveredClass($node, $scope, $classReflection);
			}
		}

		return [$ruleError];
	}

	/**
	 * @return list<IdentifierRuleError>
	 */
	private function processCoveredClass(Node\Expr\Instanceof_ $node, Scope $scope, ClassReflection $classReflection): array
	{
		if ($classReflection->getName() === Type::class || $classReflection->isSubclassOf(Type::class)) {
			return [];
		}

		$instanceofType = $scope->getType($node);
		if ($instanceofType->isTrue()->or($instanceofType->isFalse())->yes()) {
			return [];
		}

		$classType = new ObjectType($classReflection->getName(), null, $classReflection);

		$exprType = $scope->getType($node->expr);
		if ($exprType instanceof UnionType) {
			foreach ($exprType->getTypes() as $innerType) {
				if ($innerType->getObjectClassNames() !== [] && $classType->isSuperTypeOf($innerType)->yes()) {
					return [];
				}
			}
		}

		return [
			RuleErrorBuilder::message(sprintf(
				'Although %s is covered by backward compatibility promise, this instanceof assumption might break because it\'s not guaranteed to always stay the same.',
				$classReflection->getDisplayName(),
			))->identifier('phpstanApi.instanceofAssumption')->tip(sprintf(
				"In case of questions how to solve this correctly, open a discussion:\n   %s\n\n   See also:\n   https://phpstan.org/developing-extensions/backward-compatibility-promise",
				'https://github.com/phpstan/phpstan/discussions',
			))->build(),
		];
	}

}
