<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\ArgumentsNormalizer;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ParametersAcceptor;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\VerbosityLevel;
use function array_key_exists;
use function in_array;
use function sprintf;
use const SORT_NUMERIC;
use const SORT_REGULAR;

/**
 * Reports calls to in-place sort functions that cannot change the array they're given.
 *
 * @implements Rule<Node\Expr\FuncCall>
 */
final class SortWithoutEffectRule implements Rule
{

	/**
	 * Sort functions that keep the original keys. They cannot change an array
	 * with at most one element.
	 */
	private const KEY_PRESERVING_SORT_FUNCTIONS = ['arsort', 'asort', 'krsort', 'ksort', 'natcasesort', 'natsort', 'uasort', 'uksort'];

	/**
	 * Sort functions that reindex the array. They can still turn a single-element
	 * array with a non-zero key into a list, so they're only a no-op on lists.
	 */
	private const REINDEXING_SORT_FUNCTIONS = ['rsort', 'shuffle', 'sort', 'usort'];

	/**
	 * Sort flags under which the keys of a list are already in ascending order.
	 */
	private const KSORT_LIST_SAFE_FLAGS = [SORT_REGULAR, SORT_NUMERIC];

	private const REASON_MESSAGES = [
		'empty' => 'Parameter #1 $array (%s) of function %s is empty, call has no effect.',
		'list' => 'Parameter #1 $array (%s) of function %s is a list, call has no effect.',
		'singleElement' => 'Parameter #1 $array (%s) of function %s has at most 1 element, call has no effect.',
	];

	public function __construct(
		private ReflectionProvider $reflectionProvider,
		private bool $treatPhpDocTypesAsCertain,
		private bool $treatPhpDocTypesAsCertainTip,
	)
	{
	}

	public function getNodeType(): string
	{
		return FuncCall::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!($node->name instanceof Node\Name)) {
			return [];
		}

		if (!$this->reflectionProvider->hasFunction($node->name, $scope)) {
			return [];
		}

		$functionReflection = $this->reflectionProvider->getFunction($node->name, $scope);
		$functionName = $functionReflection->getName();

		$keyPreserving = in_array($functionName, self::KEY_PRESERVING_SORT_FUNCTIONS, true);
		if (!$keyPreserving && !in_array($functionName, self::REINDEXING_SORT_FUNCTIONS, true)) {
			return [];
		}

		$parametersAcceptor = ParametersAcceptorSelector::selectFromArgs(
			$scope,
			$node->getArgs(),
			$functionReflection->getVariants(),
			$functionReflection->getNamedArgumentsVariants(),
		);

		$normalizedFuncCall = ArgumentsNormalizer::reorderFuncArguments($parametersAcceptor, $node);
		if ($normalizedFuncCall === null) {
			return [];
		}

		$args = $normalizedFuncCall->getArgs();
		if (!array_key_exists(0, $args)) {
			return [];
		}

		if ($this->treatPhpDocTypesAsCertain) {
			$arrayType = $scope->getType($args[0]->value);
		} else {
			$arrayType = $scope->getNativeType($args[0]->value);
		}

		$keysAlreadySorted = $functionName === 'ksort' && $this->hasFlagsKeepingListOrder($scope, $parametersAcceptor, $args);

		$reason = $this->findNoEffectReason($arrayType, $keyPreserving, $keysAlreadySorted);
		if ($reason === null) {
			return [];
		}

		$errorBuilder = RuleErrorBuilder::message(sprintf(
			self::REASON_MESSAGES[$reason],
			$arrayType->describe(VerbosityLevel::value()),
			$functionName,
		))->identifier(sprintf('sortArray.%s', $reason));

		if ($this->treatPhpDocTypesAsCertain && $this->treatPhpDocTypesAsCertainTip) {
			$nativeArrayType = $scope->getNativeType($args[0]->value);
			if ($this->findNoEffectReason($nativeArrayType, $keyPreserving, $keysAlreadySorted) !== $reason) {
				$errorBuilder->treatPhpDocTypesAsCertainTip();
			}
		}

		return [
			$errorBuilder->build(),
		];
	}

	/**
	 * @return key-of<self::REASON_MESSAGES>|null
	 */
	private function findNoEffectReason(Type $arrayType, bool $keyPreserving, bool $keysAlreadySorted): ?string
	{
		if (!$arrayType->isArray()->yes()) {
			return null;
		}

		if ($arrayType->isIterableAtLeastOnce()->no()) {
			return 'empty';
		}

		$isList = $arrayType->isList()->yes();

		if ($keysAlreadySorted && $isList) {
			return 'list';
		}

		if (!$keyPreserving && !$isList) {
			return null;
		}

		if (IntegerRangeType::fromInterval(0, 1)->isSuperTypeOf($arrayType->getArraySize())->yes()) {
			return 'noop';
		}

		return null;
	}

	/**
	 * @param Node\Arg[] $args
	 */
	private function hasFlagsKeepingListOrder(Scope $scope, ParametersAcceptor $parametersAcceptor, array $args): bool
	{
		if (array_key_exists(1, $args)) {
			$flagsType = $scope->getType($args[1]->value);
		} else {
			$parameters = $parametersAcceptor->getParameters();
			if (!array_key_exists(1, $parameters)) {
				return true;
			}

			$flagsType = $parameters[1]->getDefaultValue();
			if ($flagsType === null) {
				return true;
			}
		}

		$safeFlagsTypes = [];
		foreach (self::KSORT_LIST_SAFE_FLAGS as $safeFlag) {
			$safeFlagsTypes[] = new ConstantIntegerType($safeFlag);
		}

		return TypeCombinator::union(...$safeFlagsTypes)->isSuperTypeOf($flagsType)->yes();
	}

}
