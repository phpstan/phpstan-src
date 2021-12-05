<?php declare(strict_types = 1);

namespace PHPStan\Rules;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\Constant\ConstantStringType;

class UnusedFunctionParametersCheck
{

	private ReflectionProvider $reflectionProvider;

	public function __construct(ReflectionProvider $reflectionProvider)
	{
		$this->reflectionProvider = $reflectionProvider;
	}

	/**
	 * @param string[] $parameterNames
	 * @param \PhpParser\Node[] $statements
	 * @param mixed[] $additionalMetadata
	 * @return RuleError[]
	 */
	public function getUnusedParameters(
		Scope $scope,
		array $parameterNames,
		array $statements,
		string $unusedParameterMessage,
		string $identifier,
		array $additionalMetadata
	): array
	{
		$unusedParameters = array_fill_keys($parameterNames, true);
		foreach ($this->getUsedVariables($scope, $statements) as $variableName) {
			if (!isset($unusedParameters[$variableName])) {
				continue;
			}

			unset($unusedParameters[$variableName]);
		}
		$errors = [];
		foreach (array_keys($unusedParameters) as $name) {
			$errors[] = RuleErrorBuilder::message(
				sprintf($unusedParameterMessage, $name)
			)->identifier($identifier)->metadata($additionalMetadata + ['variableName' => $name])->build();
		}

		return $errors;
	}

	/**
	 * @param \PhpParser\Node[]|\PhpParser\Node|scalar $node
	 * @return string[]
	 */
	private function getUsedVariables(Scope $scope, $node): array
	{
		$variableNames = [];
		if ($node instanceof Node) {
			if ($node instanceof Node\Expr\FuncCall && $node->name instanceof Node\Name) {
				$functionName = $this->reflectionProvider->resolveFunctionName($node->name, $scope);
				if ($functionName === 'func_get_args') {
					return $scope->getDefinedVariables();
				}
			}
			if ($node instanceof Node\Expr\Variable && is_string($node->name) && $node->name !== 'this') {
				return [$node->name];
			}
			if ($node instanceof Node\Expr\ClosureUse && is_string($node->var->name)) {
				return [$node->var->name];
			}
			if (
				$node instanceof Node\Expr\FuncCall
				&& $node->name instanceof Node\Name
				&& (string) $node->name === 'compact'
			) {
				foreach ($node->getArgs() as $arg) {
					$argType = $scope->getType($arg->value);
					if (!($argType instanceof ConstantStringType)) {
						continue;
					}

					$variableNames[] = $argType->getValue();
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
