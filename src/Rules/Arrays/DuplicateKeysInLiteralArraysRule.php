<?php declare(strict_types = 1);

namespace PHPStan\Rules\Arrays;

use PhpParser\Node;
use PhpParser\PrettyPrinter\Standard;
use PHPStan\Analyser\Scope;
use PHPStan\Node\LiteralArrayNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ConstantScalarType;
use function array_keys;
use function count;
use function implode;
use function sprintf;
use function var_export;

/**
 * @implements Rule<LiteralArrayNode>
 */
class DuplicateKeysInLiteralArraysRule implements Rule
{

	private Standard $printer;

	public function __construct(
		Standard $printer
	)
	{
		$this->printer = $printer;
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
		foreach ($node->getItemNodes() as $itemNode) {
			$item = $itemNode->getArrayItem();
			if ($item === null) {
				continue;
			}
			if ($item->key === null) {
				continue;
			}

			$key = $item->key;
			$keyType = $itemNode->getScope()->getType($key);
			if (
				!$keyType instanceof ConstantScalarType
			) {
				continue;
			}

			$printedValue = $this->printer->prettyPrintExpr($key);
			$value = $keyType->getValue();
			$printedValues[$value][] = $printedValue;

			if (!isset($valueLines[$value])) {
				$valueLines[$value] = $item->getLine();
			}

			$previousCount = count($values);
			$values[$value] = $printedValue;
			if ($previousCount !== count($values)) {
				continue;
			}

			$duplicateKeys[$value] = true;
		}

		$messages = [];
		foreach (array_keys($duplicateKeys) as $value) {
			$messages[] = RuleErrorBuilder::message(sprintf(
				'Array has %d %s with value %s (%s).',
				count($printedValues[$value]),
				count($printedValues[$value]) === 1 ? 'duplicate key' : 'duplicate keys',
				var_export($value, true),
				implode(', ', $printedValues[$value])
			))->line($valueLines[$value])->build();
		}

		return $messages;
	}

}
