<?php declare(strict_types = 1);

namespace PHPStan\Rules;

use Generator;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Variable;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Type\ErrorType;
use PHPStan\Type\Generic\TemplateType;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\NeverType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeTraverser;
use PHPStan\Type\TypeUtils;
use PHPStan\Type\VerbosityLevel;
use function sprintf;

#[AutowiredService]
final class FunctionReturnTypeCheck
{

	public function __construct(private RuleLevelHelper $ruleLevelHelper)
	{
	}

	/**
	 * @return list<IdentifierRuleError>
	 */
	public function checkReturnType(
		Scope $scope,
		Type $returnType,
		?Expr $returnValue,
		Node $returnNode,
		string $emptyReturnStatementMessage,
		string $voidMessage,
		string $typeMismatchMessage,
		string $neverMessage,
		bool $isGenerator,
	): array
	{
		$returnType = TypeUtils::resolveLateResolvableTypes($returnType);
		$returnType = $this->specifyTemplateTypesFromScope($scope, $returnType);

		if ($returnType instanceof NeverType && $returnType->isExplicit()) {
			return [
				RuleErrorBuilder::message($neverMessage)
					->line($returnNode->getStartLine())
					->identifier('return.never')
					->build(),
			];
		}

		if ($isGenerator) {
			$returnType = $returnType->getTemplateType(Generator::class, 'TReturn');
			if ($returnType instanceof ErrorType) {
				return [];
			}
		}

		$isVoidSuperType = $returnType->isVoid();
		$verbosityLevel = VerbosityLevel::getRecommendedLevelByType($returnType, null);
		if ($returnValue === null) {
			if (!$isVoidSuperType->no()) {
				return [];
			}

			return [
				RuleErrorBuilder::message(sprintf(
					$emptyReturnStatementMessage,
					$returnType->describe($verbosityLevel),
				))
					->line($returnNode->getStartLine())
					->identifier('return.empty')
					->build(),
			];
		}

		if ($returnNode instanceof Expr\Yield_ || $returnNode instanceof Expr\YieldFrom) {
			return [];
		}

		$returnValueType = $scope->getType($returnValue);
		$verbosityLevel = VerbosityLevel::getRecommendedLevelByType($returnType, $returnValueType);

		if ($isVoidSuperType->yes()) {
			return [
				RuleErrorBuilder::message(sprintf(
					$voidMessage,
					$returnValueType->describe($verbosityLevel),
				))
					->line($returnNode->getStartLine())
					->identifier('return.void')
					->build(),
			];
		}

		$accepts = $this->ruleLevelHelper->accepts($returnType, $returnValueType, $scope->isDeclareStrictTypes());
		if (!$accepts->result) {
			return [
				RuleErrorBuilder::message(sprintf(
					$typeMismatchMessage,
					$returnType->describe($verbosityLevel),
					$returnValueType->describe($verbosityLevel),
				))
					->line($returnNode->getStartLine())
					->identifier('return.type')
					->acceptsReasonsTip($accepts->reasons)
					->build(),
			];
		}

		return [];
	}

	/**
	 * Resolves template types in the declared return type that have been pinned
	 * to an exact class by narrowing a `class-string<T>` parameter to a constant
	 * class-string (e.g. `if ($className === Foo::class)`). In such a branch the
	 * caller's `T` is known to be exactly that class, so returning a value of that
	 * class satisfies `@return T`.
	 */
	private function specifyTemplateTypesFromScope(Scope $scope, Type $returnType): Type
	{
		if (!$returnType->hasTemplateOrLateResolvableType()) {
			return $returnType;
		}

		$function = $scope->getFunction();
		if ($function === null || $scope->isInAnonymousFunction()) {
			return $returnType;
		}

		$map = TemplateTypeMap::createEmpty();
		foreach ($function->getParameters() as $parameter) {
			$parameterType = $parameter->getType();
			if (!$parameterType->isClassString()->yes()) {
				continue;
			}

			$scopeType = $scope->getType(new Variable($parameter->getName()));
			$constantStrings = $scopeType->getConstantStrings();
			if ($constantStrings === [] || !TypeCombinator::union(...$constantStrings)->equals($scopeType)) {
				continue;
			}

			$map = $map->union($parameterType->inferTemplateTypes($scopeType));
		}

		if ($map->isEmpty()) {
			return $returnType;
		}

		return TypeTraverser::map($returnType, static function (Type $type, callable $traverse) use ($map): Type {
			if ($type instanceof TemplateType) {
				$specifiedType = $map->getType($type->getName());
				if ($specifiedType !== null && !$specifiedType instanceof ErrorType) {
					return $specifiedType;
				}
			}

			return $traverse($type);
		});
	}

}
