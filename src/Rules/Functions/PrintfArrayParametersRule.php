<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\TypeCombinator;
use function count;
use function in_array;
use function max;
use function min;
use function sprintf;

/**
 * @implements Rule<Node\Expr\FuncCall>
 */
class PrintfArrayParametersRule implements Rule
{

	public function __construct(
		private PrintfHelper $printfHelper,
		private ReflectionProvider $reflectionProvider,
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
		$name = $functionReflection->getName();
		if (!in_array($name, ['vprintf', 'vsprintf'], true)) {
			return [];
		}

		$args = $node->getArgs();
		$argsCount = count($args);
		if ($argsCount < 1) {
			return []; // caught by CallToFunctionParametersRule
		}

		$formatArgType = $scope->getType($args[0]->value);
		$placeHoldersCounts = [];
		foreach ($formatArgType->getConstantStrings() as $formatString) {
			$format = $formatString->getValue();

			$placeHoldersCounts[] = $this->printfHelper->getPrintfPlaceholdersCount($format);
		}

		if ($placeHoldersCounts === []) {
			return [];
		}

		$minCount = min($placeHoldersCounts);
		$maxCount = max($placeHoldersCounts);
		if ($minCount === $maxCount) {
			$placeHoldersCount = new ConstantIntegerType($minCount);
		} else {
			$placeHoldersCount = IntegerRangeType::fromInterval($minCount, $maxCount);

			if (!$placeHoldersCount instanceof IntegerRangeType && !$placeHoldersCount instanceof ConstantIntegerType) {
				return [];
			}
		}

		$formatArgsCounts = [];
		if (isset($args[1])) {
			$formatArgsType = $scope->getType($args[1]->value);

			$constantArrays = $formatArgsType->getConstantArrays();
			foreach ($constantArrays as $constantArray) {
				$formatArgsCounts[] = $constantArray->getArraySize();
			}

			if ($constantArrays === []) {
				$formatArgsCounts[] = $formatArgsType->getArraySize();
			}
		}

		if ($formatArgsCounts === []) {
			$formatArgsCount = new ConstantIntegerType(0);
		} else {
			$formatArgsCount = TypeCombinator::union(...$formatArgsCounts);

			if (!$formatArgsCount instanceof IntegerRangeType && !$formatArgsCount instanceof ConstantIntegerType) {
				return [];
			}
		}

		if (!$this->placeholdersMatchesArgsCount($placeHoldersCount, $formatArgsCount)) {

			if ($placeHoldersCount instanceof IntegerRangeType) {
				$placeholders = $this->getIntegerRangeAsString($placeHoldersCount);
				$singlePlaceholder = false;
			} else {
				$placeholders = $placeHoldersCount->getValue();
				$singlePlaceholder = $placeholders === 1;
			}

			if ($formatArgsCount instanceof IntegerRangeType) {
				$values = $this->getIntegerRangeAsString($formatArgsCount);
				$singleValue = false;
			} else {
				$values = $formatArgsCount->getValue();
				$singleValue = $values === 1;
			}

			return [
				RuleErrorBuilder::message(sprintf(
					sprintf(
						'%s, %s.',
						$singlePlaceholder ? 'Call to %s contains %d placeholder' : 'Call to %s contains %s placeholders',
						$singleValue ? '%d value given' : '%s values given',
					),
					$name,
					$placeholders,
					$values,
				))->identifier(sprintf('argument.%s', $name))->build(),
			];
		}

		return [];
	}

	private function placeholdersMatchesArgsCount(ConstantIntegerType|IntegerRangeType $placeHoldersCount, ConstantIntegerType|IntegerRangeType $formatArgsCount): bool
	{
		if ($placeHoldersCount instanceof ConstantIntegerType) {
			if ($formatArgsCount instanceof ConstantIntegerType) {
				return $placeHoldersCount->getValue() === $formatArgsCount->getValue();
			}

			// Zero placeholders + array
			if ($placeHoldersCount->getValue() === 0) {
				return true;
			}

			return false;
		}

		if (
			$formatArgsCount instanceof IntegerRangeType
			&& IntegerRangeType::fromInterval(1, null)->isSuperTypeOf($placeHoldersCount)->yes()
		) {
			if ($formatArgsCount->getMin() !== null && $formatArgsCount->getMax() !== null) {
				// constant array
				return $placeHoldersCount->isSuperTypeOf($formatArgsCount)->yes();
			}

			// general array
			return IntegerRangeType::fromInterval(1, null)->isSuperTypeOf($formatArgsCount)->yes();
		}

		return false;
	}

	private function getIntegerRangeAsString(IntegerRangeType $range): string
	{
		if ($range->getMin() !== null && $range->getMax() !== null) {
			return $range->getMin() . '-' . $range->getMax();
		} elseif ($range->getMin() !== null) {
			return $range->getMin() . ' or more';
		} elseif ($range->getMax() !== null) {
			return $range->getMax() . ' or less';
		}

		throw new ShouldNotHappenException();
	}

}
