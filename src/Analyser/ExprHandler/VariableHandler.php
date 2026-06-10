<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\BinaryOp\Identical;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt;
use PHPStan\Analyser\ExpressionContext;
use PHPStan\Analyser\ExpressionResult;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\ExprHandler;
use PHPStan\Analyser\ExprHandler\Helper\DefaultNarrowingHelper;
use PHPStan\Analyser\ImpurePoint;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\TypeSpecifier;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\DependencyInjection\Type\ExpressionTypeResolverExtensionRegistryProvider;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\ErrorType;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function count;
use function in_array;
use function is_string;

/**
 * @implements ExprHandler<Variable>
 */
#[AutowiredService]
final class VariableHandler implements ExprHandler
{

	public function __construct(
		private ExpressionTypeResolverExtensionRegistryProvider $expressionTypeResolverExtensionRegistryProvider,
		private DefaultNarrowingHelper $defaultNarrowingHelper,
	)
	{
	}

	public function supports(Expr $expr): bool
	{
		return $expr instanceof Variable;
	}

	public function resolveType(MutatingScope $scope, Expr $expr): Type
	{
		if (is_string($expr->name)) {
			if ($scope->hasVariableType($expr->name)->no()) {
				return new ErrorType();
			}

			return $scope->getVariableType($expr->name);
		}

		$nameType = $scope->getType($expr->name);
		if (count($nameType->getConstantStrings()) > 0) {
			$types = [];
			foreach ($nameType->getConstantStrings() as $constantString) {
				$variableScope = $scope
					->filterByTruthyValue(
						new Identical($expr->name, new String_($constantString->getValue())),
					);
				if ($variableScope->hasVariableType($constantString->getValue())->no()) {
					$types[] = new ErrorType();
					continue;
				}

				$types[] = $variableScope->getVariableType($constantString->getValue());
			}

			return TypeCombinator::union(...$types);
		}

		return new MixedType();
	}

	public function processExpr(NodeScopeResolver $nodeScopeResolver, Stmt $stmt, Expr $expr, MutatingScope $scope, ExpressionResultStorage $storage, callable $nodeCallback, ExpressionContext $context): ExpressionResult
	{
		$hasYield = false;
		$throwPoints = [];
		$impurePoints = [];
		$isAlwaysTerminating = false;
		if (is_string($expr->name)) {
			if (in_array($expr->name, Scope::SUPERGLOBAL_VARIABLES, true)) {
				$impurePoints[] = new ImpurePoint($scope, $expr, 'superglobal', 'access to superglobal variable', true);
			}
		} else {
			$nameResult = $nodeScopeResolver->processExprNode($stmt, $expr->name, $scope, $storage, $nodeCallback, $context->enterDeep());
			$hasYield = $nameResult->hasYield();
			$throwPoints = $nameResult->getThrowPoints();
			$impurePoints = $nameResult->getImpurePoints();
			$isAlwaysTerminating = $nameResult->isAlwaysTerminating();
			$scope = $nameResult->getScope();
		}
		$typeCallback = static function (Expr $e, MutatingScope $s): Type {
			if (!$e instanceof Variable) {
				throw new ShouldNotHappenException();
			}

			if (is_string($e->name)) {
				if ($s->hasVariableType($e->name)->no()) {
					return new ErrorType();
				}

				return $s->getVariableType($e->name);
			}

			// dynamic variable names need per-constant-string equality narrowing,
			// which requires the BinaryOp equality migration first — guarded
			// legacy bridge until then (works under PHPSTAN_FNSR=0)
			return $s->getType($e);
		};

		return new ExpressionResult(
			$scope,
			$hasYield,
			$isAlwaysTerminating,
			$throwPoints,
			$impurePoints,
			static fn (): MutatingScope => $scope->filterByTruthyValue($expr),
			static fn (): MutatingScope => $scope->filterByFalseyValue($expr),
			expr: $expr,
			typeCallback: $typeCallback,
			specifyTypesCallback: fn (Expr $e, MutatingScope $s, TypeSpecifierContext $ctx): SpecifiedTypes => $this->defaultNarrowingHelper->specifyDefaultTypes($e, $ctx),
			expressionTypeResolverExtensionRegistryProvider: $this->expressionTypeResolverExtensionRegistryProvider,
		);
	}

	public function specifyTypes(TypeSpecifier $typeSpecifier, Scope $scope, Expr $expr, TypeSpecifierContext $context): SpecifiedTypes
	{
		return $typeSpecifier->specifyDefaultTypes($scope, $expr, $context);
	}

}
