<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\Accessory\AccessoryLowercaseStringType;
use PHPStan\Type\Accessory\AccessoryNonEmptyStringType;
use PHPStan\Type\Accessory\AccessoryNonFalsyStringType;
use PHPStan\Type\Accessory\AccessoryUppercaseStringType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function count;
use function in_array;
use function is_bool;
use function mb_substr;
use function strlen;
use function substr;

#[AutowiredService]
final class SubstrDynamicReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function __construct(private PhpVersion $phpVersion)
	{
	}

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return in_array($functionReflection->getName(), ['substr', 'mb_substr'], true);
	}

	public function getTypeFromFunctionCall(
		FunctionReflection $functionReflection,
		FuncCall $functionCall,
		Scope $scope,
	): ?Type
	{
		$args = $functionCall->getArgs();
		if (count($args) < 2) {
			return null;
		}

		$string = $scope->getType($args[0]->value);
		$offset = $scope->getType($args[1]->value);

		$negativeOffset = IntegerRangeType::fromInterval(null, -1)->isSuperTypeOf($offset)->yes();
		$zeroOffset = (new ConstantIntegerType(0))->isSuperTypeOf($offset)->yes();
		$length = null;
		$positiveLength = false;
		$maybeOneLength = false;

		if (count($args) === 3) {
			$length = $scope->getType($args[2]->value);
			$positiveLength = IntegerRangeType::fromInterval(1, null)->isSuperTypeOf($length)->yes();
			$maybeOneLength = !(new ConstantIntegerType(1))->isSuperTypeOf($length)->no();
		}

		$constantStrings = $string->getConstantStrings();
		if (
			count($constantStrings) > 0
			&& $offset instanceof ConstantIntegerType
			&& ($length === null || $length instanceof ConstantIntegerType)
		) {
			$results = [];
			foreach ($constantStrings as $constantString) {
				if ($length !== null) {
					if ($functionReflection->getName() === 'mb_substr') {
						$substr = mb_substr($constantString->getValue(), $offset->getValue(), $length->getValue());
					} elseif ($this->phpVersion->substrReturnFalseInsteadOfEmptyString()) {
						$substr = $this->substrOrFalse($constantString->getValue(), $offset->getValue(), $length->getValue());
					} else {
						$substr = substr($constantString->getValue(), $offset->getValue(), $length->getValue());
					}
				} else {
					if ($functionReflection->getName() === 'mb_substr') {
						$substr = mb_substr($constantString->getValue(), $offset->getValue());
					} elseif ($this->phpVersion->substrReturnFalseInsteadOfEmptyString()) {
						// Simulate substr call on an older PHP version if the runtime one is too new.
						$substr = $this->substrOrFalse($constantString->getValue(), $offset->getValue());
					} else {
						$substr = substr($constantString->getValue(), $offset->getValue());
					}
				}

				if (is_bool($substr)) {
					if ($this->phpVersion->substrReturnFalseInsteadOfEmptyString()) {
						$results[] = new ConstantBooleanType($substr);
					} else {
						// Simulate substr call on a recent PHP version if the runtime one is too old.
						$results[] = new ConstantStringType('');
					}
				} else {
					$results[] = new ConstantStringType($substr);
				}
			}

			return TypeCombinator::union(...$results);
		}

		$accessoryTypes = [];
		$isNotEmpty = false;
		if ($string->isLowercaseString()->yes()) {
			$accessoryTypes[] = new AccessoryLowercaseStringType();
		}
		if ($string->isUppercaseString()->yes()) {
			$accessoryTypes[] = new AccessoryUppercaseStringType();
		}
		if ($string->isNonEmptyString()->yes() && ($negativeOffset || $zeroOffset && $positiveLength)) {
			$isNotEmpty = true;
			if ($string->isNonFalsyString()->yes() && !$maybeOneLength) {
				$accessoryTypes[] = new AccessoryNonFalsyStringType();
			} else {
				$accessoryTypes[] = new AccessoryNonEmptyStringType();
			}
		}
		if (count($accessoryTypes) > 0) {
			$accessoryTypes[] = new StringType();

			if (!$isNotEmpty && $this->phpVersion->substrReturnFalseInsteadOfEmptyString()) {
				return TypeCombinator::union(
					new ConstantBooleanType(false),
					new IntersectionType($accessoryTypes),
				);
			}

			return new IntersectionType($accessoryTypes);
		}

		return null;
	}

	private function substrOrFalse(string $string, int $offset, ?int $length = null): false|string
	{
		$strlen = strlen($string);

		if ($offset > $strlen) {
			return false;
		}

		if ($length !== null && $length < 0) {
			if ($offset < 0 && -$length > $strlen) {
				return false;
			}
			if ($offset >= 0 && -$length > $strlen - $offset) {
				return false;
			}
		}

		if ($length === null) {
			return substr($string, $offset);
		}

		return substr($string, $offset, $length);
	}

}
