<?php declare(strict_types = 1);

namespace PHPStan\Rules;

use PhpParser\Node;
use PhpParser\Node\Expr\Variable;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\InitializerExprContext;
use PHPStan\Reflection\InitializerExprTypeResolver;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\ErrorType;
use function array_merge;
use function in_array;
use function is_array;
use function is_string;
use function sprintf;

#[AutowiredService]
final class UnusedFunctionParametersCheck
{

	public function __construct(
		private ReflectionProvider $reflectionProvider,
		private InitializerExprTypeResolver $initializerExprTypeResolver,
		#[AutowiredParameter(ref: '%featureToggles.reportPreciseLineForUnusedFunctionParameter%')]
		private bool $reportExactLine,
	)
	{
	}

	/**
	 * @param Variable[] $parameterVars
	 * @param Node[] $statements
	 * @param 'constructor.unusedParameter'|'closure.unusedUse' $identifier
	 * @return list<IdentifierRuleError>
	 */
	public function getUnusedParameters(
		Scope $scope,
		array $parameterVars,
		array $statements,
		string $unusedParameterMessage,
		string $identifier,
	): array
	{
		$unusedParameters = [];
		foreach ($parameterVars as $variable) {
			if (!is_string($variable->name)) {
				throw new ShouldNotHappenException();
			}

			$unusedParameters[$variable->name] = $variable;
		}
		foreach ($this->getUsedVariables($scope, $statements) as $variableName) {
			unset($unusedParameters[$variableName]);
		}

		$errors = [];
		foreach ($unusedParameters as $name => $variable) {
			$errorBuilder = RuleErrorBuilder::message(sprintf($unusedParameterMessage, $name))->identifier($identifier);
			if ($this->reportExactLine) {
				$errorBuilder->line($variable->getStartLine());
			}
			$errors[] = $errorBuilder->build();
		}

		return $errors;
	}

	/**
	 * @param Node[]|Node|scalar|null $node
	 * @return string[]
	 */
	private function getUsedVariables(Scope $scope, $node): array
	{
		$variableNames = [];
		if ($node instanceof Node) {
			if ($node instanceof Node\Expr\FuncCall && $node->name instanceof Node\Name && !$node->isFirstClassCallable()) {
				$functionName = $this->reflectionProvider->resolveFunctionName($node->name, $scope);
				if (in_array($functionName, ['func_get_args', 'get_defined_vars'], true)) {
					return $scope->getDefinedVariables();
				}
			}
			if ($node instanceof Node\Expr\Include_ || $node instanceof Node\Expr\Eval_) {
				return $scope->getDefinedVariables();
			}
			if ($node instanceof Variable) {
				if (is_string($node->name)) {
					if ($node->name !== 'this') {
						return [$node->name];
					}
				} else {
					// a variable-variable's name expression: a literal is priced
					// without the scope, a variable read is scope state - neither
					// asks the scope about a node this function body's walk has
					// not stored yet
					if ($node->name instanceof Node\Scalar\String_) {
						$nameType = $this->initializerExprTypeResolver->getType($node->name, InitializerExprContext::fromScope($scope));
					} elseif ($node->name instanceof Variable && is_string($node->name->name)) {
						$nameType = $scope->hasVariableType($node->name->name)->no() ? new ErrorType() : $scope->getVariableType($node->name->name);
					} else {
						$nameType = $scope->getType($node->name);
					}
					if ($nameType->getConstantStrings() === []) {
						return $scope->getDefinedVariables();
					}

					foreach ($nameType->getConstantStrings() as $constantString) {
						$variableNames[] = $constantString->getValue();
					}
				}
			}
			if ($node instanceof Node\ClosureUse && is_string($node->var->name)) {
				return [$node->var->name];
			}
			if (
				$node instanceof Node\Expr\FuncCall
				&& !$node->isFirstClassCallable()
				&& $node->name instanceof Node\Name
				&& (string) $node->name === 'compact'
			) {
				foreach ($node->getArgs() as $arg) {
					// compact('name') takes literal names - price a constant
					// argument without asking the scope about a node the walk of
					// this very function body has not stored yet
					$argType = $arg->value instanceof Node\Scalar\String_
						? $this->initializerExprTypeResolver->getType($arg->value, InitializerExprContext::fromScope($scope))
						: $scope->getType($arg->value);
					foreach ($argType->getConstantStrings() as $constantStringType) {
						$variableNames[] = $constantStringType->getValue();
					}
				}
			}
			foreach ($node->getSubNodeNames() as $subNodeName) {
				if ($node instanceof Node\Expr\Closure && $subNodeName !== 'uses') {
					continue;
				}
				$subNode = $node->{$subNodeName};
				$variableNames = array_merge($variableNames, $this->getUsedVariables($scope, $subNode));
			}
		} elseif (is_array($node)) {
			foreach ($node as $subNode) {
				$variableNames = array_merge($variableNames, $this->getUsedVariables($scope, $subNode));
			}
		}

		return $variableNames;
	}

}
