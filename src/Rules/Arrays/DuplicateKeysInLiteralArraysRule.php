<?php declare(strict_types = 1);

namespace PHPStan\Rules\Arrays;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\LiteralArrayNode;
use PHPStan\Node\Printer\ExprPrinter;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\ConstantScalarType;
use function array_keys;
use function count;
use function implode;
use function max;
use function sprintf;
use function var_export;

/**
 * @implements Rule<LiteralArrayNode>
 */
class DuplicateKeysInLiteralArraysRule implements Rule
{

	public function __construct(
		private ExprPrinter $exprPrinter,
	)
	{
	}

	public function getNodeType(): string
	{
		return LiteralArrayNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$values = [];
		$duplicateKeys = [];
		$printedValues = [];
		$valueLines = [];

		/**
		 * @var int|false|null $autoGeneratedIndex
		 * - An int value represent the biggest integer used as array key.
		 *   When no key is provided this value + 1 will be used.
		 * - Null is used as initializer instead of 0 to avoid issue with negative keys.
		 * - False means a non-scalar value was encountered and we cannot be sure of the next keys.
		 */
		$autoGeneratedIndex = null;
		foreach ($node->getItemNodes() as $itemNode) {
			$item = $itemNode->getArrayItem();
			if ($item === null) {
				$autoGeneratedIndex = false;
				continue;
			}

			$key = $item->key;
			if ($key === null) {
				if ($autoGeneratedIndex === false) {
					continue;
				}

				if ($autoGeneratedIndex === null) {
					$autoGeneratedIndex = 0;
					$keyType = new ConstantIntegerType(0);
				} else {
					$keyType = new ConstantIntegerType(++$autoGeneratedIndex);
				}
			} else {
				$keyType = $itemNode->getScope()->getType($key);

				$arrayKeyValue = $keyType->toArrayKey();
				if ($arrayKeyValue instanceof ConstantIntegerType) {
					$autoGeneratedIndex = $autoGeneratedIndex === null
						? $arrayKeyValue->getValue()
						: max($autoGeneratedIndex, $arrayKeyValue->getValue());
				}
			}

			if (!$keyType instanceof ConstantScalarType) {
				$autoGeneratedIndex = false;
				continue;
			}

			$value = $keyType->getValue();
			$index = (string) $value;
			$printedValue = $key !== null
				? $this->exprPrinter->printExpr($key)
				: $value;

			$printedValues[$index][] = $printedValue;

			if (!isset($valueLines[$index])) {
				$valueLines[$index] = $item->getStartLine();
			}

			$previousCount = count($values);
			$values[$index] = $printedValue;
			if ($previousCount !== count($values)) {
				continue;
			}

			$duplicateKeys[$index] = true;
		}

		$messages = [];
		foreach (array_keys($duplicateKeys) as $value) {
			$messages[] = RuleErrorBuilder::message(sprintf(
				'Array has %d %s with value %s (%s).',
				count($printedValues[$value]),
				count($printedValues[$value]) === 1 ? 'duplicate key' : 'duplicate keys',
				var_export($value, true),
				implode(', ', $printedValues[$value]),
			))->identifier('array.duplicateKey')->line($valueLines[$value])->build();
		}

		return $messages;
	}

}
