<?php declare(strict_types = 1);

namespace PHPStan\Rules\Methods;

use PhpParser\Node;
use PhpParser\Node\Stmt\Return_;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Reflection\Php\PhpMethodFromParserNodeReflection;
use PHPStan\Rules\FunctionReturnTypeCheck;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\LineRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Rules\TipRuleError;
use PHPStan\Type\Accessory\AccessoryArrayListType;
use PHPStan\Type\ArrayType;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\ObjectType;
use function count;
use function sprintf;
use function strtolower;

/**
 * @implements Rule<Node\Stmt\Return_>
 */
class ReturnTypeRule implements Rule
{

	public function __construct(private FunctionReturnTypeCheck $returnTypeCheck)
	{
	}

	public function getNodeType(): string
	{
		return Return_::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if ($scope->getFunction() === null) {
			return [];
		}

		if ($scope->isInAnonymousFunction()) {
			return [];
		}

		$method = $scope->getFunction();
		if (!$method instanceof PhpMethodFromParserNodeReflection) {
			return [];
		}

		$returnType = ParametersAcceptorSelector::selectSingle($method->getVariants())->getReturnType();
		$errors = $this->returnTypeCheck->checkReturnType(
			$scope,
			$returnType,
			$node->expr,
			$node,
			sprintf(
				'Method %s::%s() should return %%s but empty return statement found.',
				$method->getDeclaringClass()->getDisplayName(),
				$method->getName(),
			),
			sprintf(
				'Method %s::%s() with return type void returns %%s but should not return anything.',
				$method->getDeclaringClass()->getDisplayName(),
				$method->getName(),
			),
			sprintf(
				'Method %s::%s() should return %%s but returns %%s.',
				$method->getDeclaringClass()->getDisplayName(),
				$method->getName(),
			),
			sprintf(
				'Method %s::%s() should never return but return statement found.',
				$method->getDeclaringClass()->getDisplayName(),
				$method->getName(),
			),
			$method->isGenerator(),
		);

		if (
			count($errors) === 1
			&& $errors[0]->getIdentifier() === 'return.type'
			&& !$errors[0] instanceof TipRuleError
			&& $errors[0] instanceof LineRuleError
			&& $method->getDeclaringClass()->isSubclassOf(Rule::class)
			&& strtolower($method->getName()) === 'processnode'
			&& $node->expr !== null
		) {
			$ruleErrorType = new ObjectType(RuleError::class);
			$identifierRuleErrorType = new ObjectType(IdentifierRuleError::class);
			$listOfIdentifierRuleErrors = new IntersectionType([
				new ArrayType(IntegerRangeType::fromInterval(0, null), $identifierRuleErrorType),
				new AccessoryArrayListType(),
			]);
			if (!$listOfIdentifierRuleErrors->isSuperTypeOf($returnType)->yes()) {
				return $errors;
			}

			$returnValueType = $scope->getType($node->expr)->getIterableValueType();
			$builder = RuleErrorBuilder::message($errors[0]->getMessage())
				->line($errors[0]->getLine())
				->identifier($errors[0]->getIdentifier());
			if (!$returnValueType->isString()->no()) {
				$builder->tip('Rules can no longer return plain strings. See: https://phpstan.org/blog/using-rule-error-builder');
			} elseif (
				$ruleErrorType->isSuperTypeOf($returnValueType)->yes()
				&& !$identifierRuleErrorType->isSuperTypeOf($returnValueType)->yes()
			) {
				$builder->tip('Error is missing an identifier. See: https://phpstan.org/blog/using-rule-error-builder');
			}

			$errors = [$builder->build()];
		}

		return $errors;
	}

}
