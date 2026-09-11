<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\ArgumentsNormalizer;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\Type;
use function array_key_exists;

/**
 * Locates the values carrying filter flags in a call to one of the filter_*()
 * functions, taking named arguments into account.
 */
#[AutowiredService]
final class FilterFunctionFlagsHelper
{

	/** The $options parameter doubles as the flags argument. */
	private const OPTIONS_PARAMETER_POSITIONS = [
		'filter_var' => 2,
		'filter_input' => 3,
		'filter_var_array' => 1,
		'filter_input_array' => 1,
	];

	/**
	 * The array variants take a filter specification per input key instead of a
	 * single flags argument. An integer $options is the filter id there, so it
	 * cannot carry any flags.
	 */
	private const PER_KEY_SPECIFICATION_FUNCTIONS = [
		'filter_var_array' => true,
		'filter_input_array' => true,
	];

	public function isSupported(FunctionReflection $functionReflection): bool
	{
		return array_key_exists($functionReflection->getName(), self::OPTIONS_PARAMETER_POSITIONS);
	}

	/**
	 * @return list<Type> types of all the values that may carry filter flags
	 */
	public function getFlagsTypes(FunctionReflection $functionReflection, FuncCall $funcCall, Scope $scope): array
	{
		$functionName = $functionReflection->getName();
		if (!array_key_exists($functionName, self::OPTIONS_PARAMETER_POSITIONS)) {
			return [];
		}

		$parametersAcceptor = ParametersAcceptorSelector::selectFromArgs(
			$scope,
			$funcCall->getArgs(),
			$functionReflection->getVariants(),
			$functionReflection->getNamedArgumentsVariants(),
		);
		$normalizedFuncCall = ArgumentsNormalizer::reorderFuncArguments($parametersAcceptor, $funcCall);
		if ($normalizedFuncCall === null) {
			return [];
		}

		$args = $normalizedFuncCall->getArgs();
		$optionsPosition = self::OPTIONS_PARAMETER_POSITIONS[$functionName];
		if (!isset($args[$optionsPosition])) {
			return [];
		}

		$optionsType = $scope->getType($args[$optionsPosition]->value);
		if (!array_key_exists($functionName, self::PER_KEY_SPECIFICATION_FUNCTIONS)) {
			return [$optionsType];
		}

		if ($optionsType->isArray()->no()) {
			return [];
		}

		$constantArrays = $optionsType->getConstantArrays();
		if ($constantArrays === []) {
			return [$optionsType];
		}

		$flagsTypes = [];
		foreach ($constantArrays as $constantArray) {
			foreach ($constantArray->getValueTypes() as $valueType) {
				$flagsTypes[] = $valueType;
			}
		}

		return $flagsTypes;
	}

}
