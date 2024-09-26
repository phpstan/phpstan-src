<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\BooleanType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\NeverType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\UnionType;
use function array_key_exists;
use function array_map;
use function array_unique;
use function count;

final class MbFunctionsReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	use MbFunctionsReturnTypeExtensionTrait;

	/** @var int[]  */
	private array $encodingPositionMap = [
		'mb_http_output' => 1,
		'mb_regex_encoding' => 1,
		'mb_internal_encoding' => 1,
		'mb_encoding_aliases' => 1,
		'mb_chr' => 2,
		'mb_ord' => 2,
	];

	public function __construct(private PhpVersion $phpVersion)
	{
	}

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return array_key_exists($functionReflection->getName(), $this->encodingPositionMap);
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): Type
	{
		$returnType = ParametersAcceptorSelector::selectFromArgs(
			$scope,
			$functionCall->getArgs(),
			$functionReflection->getVariants(),
		)->getReturnType();
		$positionEncodingParam = $this->encodingPositionMap[$functionReflection->getName()];

		if (count($functionCall->getArgs()) < $positionEncodingParam) {
			return TypeCombinator::remove($returnType, new BooleanType());
		}

		$strings = $scope->getType($functionCall->getArgs()[$positionEncodingParam - 1]->value)->getConstantStrings();
		$results = array_unique(array_map(fn (ConstantStringType $encoding): bool => $this->isSupportedEncoding($encoding->getValue()), $strings));

		if ($returnType->equals(new UnionType([new StringType(), new BooleanType()]))) {
			return count($results) === 1 ? new ConstantBooleanType($results[0]) : new BooleanType();
		}

		if (count($results) === 1) {
			$invalidEncodingReturn = new ConstantBooleanType(false);
			if ($this->phpVersion->throwsOnInvalidMbStringEncoding()) {
				$invalidEncodingReturn = new NeverType();
			}

			return $results[0]
				? TypeCombinator::remove($returnType, new ConstantBooleanType(false))
				: $invalidEncodingReturn;
		}

		return $returnType;
	}

}
