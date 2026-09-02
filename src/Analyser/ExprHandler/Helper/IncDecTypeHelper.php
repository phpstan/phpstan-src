<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler\Helper;

use Closure;
use PhpParser\Node\Expr;
use PhpParser\Node\Scalar\Int_;
use PHPStan\Analyser\ExpressionResult;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\InitializerExprTypeResolver;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\Accessory\AccessoryLiteralStringType;
use PHPStan\Type\BenevolentUnionType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\ConstantTypeHelper;
use PHPStan\Type\FloatType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\NeverType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use ValueError;
use function count;
use function is_bool;
use function is_numeric;
use function is_string;
use function str_decrement;
use function str_increment;

#[AutowiredService]
final class IncDecTypeHelper
{

	public function __construct(
		private InitializerExprTypeResolver $initializerExprTypeResolver,
	)
	{
	}

	/**
	 * The written type of ++$var / $var++ ($increment: false: --$var / $var--),
	 * read lazily from the variable's result. Shared by the pre handlers (their
	 * own type) and the post handlers (the value their virtual assign writes).
	 *
	 * @return Closure(bool): Type
	 */
	public function getTypeCallback(Expr $varExpr, ExpressionResult $varResult, bool $increment): Closure
	{
		return function (bool $nativeTypesPromoted) use ($varExpr, $varResult, $increment): Type {
			$varType = ($nativeTypesPromoted ? $varResult->getNativeType() : $varResult->getType());
			$varScalars = $varType->getConstantScalarValues();

			if (count($varScalars) > 0) {
				$newTypes = [];

				foreach ($varScalars as $varValue) {
					if ($increment) {
						if ($varValue === '') {
							$varValue = '1';
						} elseif (is_string($varValue) && !is_numeric($varValue)) {
							try {
								$varValue = str_increment($varValue);
							} catch (ValueError) {
								return new NeverType();
							}
						} elseif (!is_bool($varValue)) {
							++$varValue;
						}
					} else {
						if ($varValue === '') {
							$varValue = -1;
						} elseif (is_string($varValue) && !is_numeric($varValue)) {
							try {
								$varValue = str_decrement($varValue);
							} catch (ValueError) {
								return new NeverType();
							}
						} elseif (is_numeric($varValue)) {
							--$varValue;
						}
					}

					$newTypes[] = ConstantTypeHelper::getTypeFromValue($varValue);
				}
				return TypeCombinator::union(...$newTypes);
			} elseif ($varType->isString()->yes()) {
				if ($varType->isLiteralString()->yes()) {
					return new IntersectionType([
						new StringType(),
						new AccessoryLiteralStringType(),
					]);
				}

				if ($varType->isNumericString()->yes()) {
					return new BenevolentUnionType([
						new IntegerType(),
						new FloatType(),
					]);
				}

				return new BenevolentUnionType([
					new StringType(),
					new IntegerType(),
					new FloatType(),
				]);
			}

			$one = new Int_(1);
			$getType = static function (Expr $e) use ($nativeTypesPromoted, $varExpr, $varResult, $one): Type {
				if ($e === $varExpr) {
					return $nativeTypesPromoted ? $varResult->getNativeType() : $varResult->getType();
				}
				if ($e === $one) {
					return new ConstantIntegerType(1);
				}

				throw new ShouldNotHappenException();
			};

			return $increment
				? $this->initializerExprTypeResolver->getPlusType($varExpr, $one, $getType)
				: $this->initializerExprTypeResolver->getMinusType($varExpr, $one, $getType);
		};
	}

}
