<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\Constant\ConstantIntegerType;
use function array_key_exists;
use function count;
use function sprintf;
use function strtolower;

/**
 * @implements Rule<Node\Expr\FuncCall>
 */
class PrintfArrayParametersRule implements Rule
{
	private const FORMAT_ARGUMENT_POSITIONS = [
		'vprintf' => 0,
		'vsprintf' => 0,
	];
	private const MINIMUM_NUMBER_OF_ARGUMENTS = [
		'vprintf' => 1,
		'vsprintf' => 1,
	];

	public function __construct(private PrintfHelper $printfHelper)
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

		$name = strtolower((string) $node->name);
		if (!array_key_exists($name, self::FORMAT_ARGUMENT_POSITIONS)) {
			return [];
		}

		$formatArgumentPosition = self::FORMAT_ARGUMENT_POSITIONS[$name];

		$args = $node->getArgs();
		$argsCount = count($args);
		if ($argsCount < self::MINIMUM_NUMBER_OF_ARGUMENTS[$name]) {
			return []; // caught by CallToFunctionParametersRule
		}

		$formatArgType = $scope->getType($args[$formatArgumentPosition]->value);
		$maxPlaceHoldersCount = null;
		foreach ($formatArgType->getConstantStrings() as $formatString) {
			$format = $formatString->getValue();
			$tempPlaceHoldersCount = $this->printfHelper->getPrintfPlaceholdersCount($format);
			if ($maxPlaceHoldersCount === null) {
				$maxPlaceHoldersCount = $tempPlaceHoldersCount;
			} elseif ($tempPlaceHoldersCount > $maxPlaceHoldersCount) {
				$maxPlaceHoldersCount = $tempPlaceHoldersCount;
			}
		}

		if ($maxPlaceHoldersCount === null) {
			return [];
		}

		$formatArgsCount = 0;
		if (isset($args[1])) {
			$formatArgs = $scope->getType($args[1]->value);

			foreach ($formatArgs->getConstantArrays() as $constantArray) {
				$size = $constantArray->getArraySize();
				if (!$size instanceof ConstantIntegerType) {
					return [];
				}
				$formatArgsCount = $size->getValue();
			}
		}

		if ($formatArgsCount !== $maxPlaceHoldersCount) {
			return [
				RuleErrorBuilder::message(sprintf(
					sprintf(
						'%s, %s.',
						$maxPlaceHoldersCount === 1 ? 'Call to %s contains %d placeholder' : 'Call to %s contains %d placeholders',
						$formatArgsCount === 1 ? '%d value given' : '%d values given',
					),
					$name,
					$maxPlaceHoldersCount,
					$formatArgsCount,
				))->identifier(sprintf('argument.%s', $name))->build(),
			];
		}

		return [];
	}

}
