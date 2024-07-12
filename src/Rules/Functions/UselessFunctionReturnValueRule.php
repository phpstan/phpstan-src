<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\ArgumentsNormalizer;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function count;
use function in_array;
use function sprintf;

/**
 * @implements Rule<Node\Expr\FuncCall>
 */
class UselessFunctionReturnValueRule implements Rule
{

	public function __construct(private ReflectionProvider $reflectionProvider)
	{
	}

	public function getNodeType(): string
	{
		return FuncCall::class;
	}

	public function processNode(Node $funcCall, Scope $scope): array
	{
		if (!($funcCall->name instanceof Node\Name) || $scope->isInFirstLevelStatement()) {
			return [];
		}

		if (!$this->reflectionProvider->hasFunction($funcCall->name, $scope)) {
			return [];
		}

		$functionReflection = $this->reflectionProvider->getFunction($funcCall->name, $scope);

		if (!in_array($functionReflection->getName(), ['var_export', 'print_r', 'highlight_string', 'highlight_file'], true)) {
			return [];
		}
		$parametersAcceptor = ParametersAcceptorSelector::selectFromArgs(
			$scope,
			$funcCall->getArgs(),
			$functionReflection->getVariants(),
			$functionReflection->getNamedArgumentsVariants(),
		);

		$reorderedFuncCall = ArgumentsNormalizer::reorderFuncArguments(
			$parametersAcceptor,
			$funcCall,
		);

		if ($reorderedFuncCall === null) {
			return [];
		}
		$reorderedArgs = $reorderedFuncCall->getArgs();

		if (count($reorderedArgs) === 1 || (count($reorderedArgs) >= 2 && $scope->getType($reorderedArgs[1]->value)->isFalse()->yes())) {
			return [RuleErrorBuilder::message(
				sprintf(
					'Return value of function %s() is always true and the result is printed instead of being returned. Pass in true as parameter #%d $%s to return the output instead.',
					$functionReflection->getName(),
					2,
					$parametersAcceptor->getParameters()[1]->getName(),
				),
			)
				->identifier('function.uselessReturnValue')
				->line($funcCall->getStartLine())
				->build(),
			];
		}

		return [];
	}

}
