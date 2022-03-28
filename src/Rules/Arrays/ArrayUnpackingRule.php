<?php declare(strict_types = 1);

namespace PHPStan\Rules\Arrays;

use PhpParser\Node;
use PhpParser\Node\Expr\ArrayItem;
use PHPStan\Analyser\Scope;
use PHPStan\Php\PhpVersion;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\StringType;
use PHPStan\Type\VerbosityLevel;

/**
 * @implements Rule<ArrayItem>
 */
class ArrayUnpackingRule implements Rule
{

	public function __construct(private PhpVersion $phpVersion)
	{
	}

	public function getNodeType(): string
	{
		return ArrayItem::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if ($node->unpack === false || $this->phpVersion->supportsArrayUnpackingWithStringKeys()) {
			return [];
		}

		$valueType = $scope->getType($node->value);
		$isString = (new StringType())->isSuperTypeOf($valueType->getIterableKeyType());

		if ($isString->no()) {
			return [];
		}

		return [
			RuleErrorBuilder::message(sprintf(
				'Array unpacking cannot be used on an array with %sstring keys: %s',
				$isString->yes() ? '' : 'potential ',
				$valueType->describe(VerbosityLevel::value()),
			))->build()
		];
	}

}
