<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\BooleanType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeUtils;
use PHPStan\Type\UnionType;
use function array_key_exists;
use function array_map;
use function array_merge;
use function array_unique;
use function count;
use function function_exists;
use function in_array;
use function mb_encoding_aliases;
use function mb_list_encodings;
use function strtoupper;

class MbFunctionsReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	/** @var string[] */
	private array $supportedEncodings;

	/** @var int[]  */
	private array $encodingPositionMap = [
		'mb_http_output' => 1,
		'mb_regex_encoding' => 1,
		'mb_internal_encoding' => 1,
		'mb_encoding_aliases' => 1,
		'mb_strlen' => 2,
		'mb_chr' => 2,
		'mb_ord' => 2,
	];

	public function __construct()
	{
		$supportedEncodings = [];
		if (function_exists('mb_list_encodings')) {
			foreach (mb_list_encodings() as $encoding) {
				$aliases = mb_encoding_aliases($encoding);
				if ($aliases === false) {
					throw new ShouldNotHappenException();
				}
				$supportedEncodings = array_merge($supportedEncodings, $aliases, [$encoding]);
			}
		}
		$this->supportedEncodings = array_map('strtoupper', $supportedEncodings);
	}

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return array_key_exists($functionReflection->getName(), $this->encodingPositionMap);
	}

	private function isSupportedEncoding(string $encoding): bool
	{
		return in_array(strtoupper($encoding), $this->supportedEncodings, true);
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): Type
	{
		$returnType = ParametersAcceptorSelector::selectSingle($functionReflection->getVariants())->getReturnType();
		$positionEncodingParam = $this->encodingPositionMap[$functionReflection->getName()];

		// php8-stubs define a regular int return-type for mb_strlen. use a more precise type instead.
		if ($functionReflection->getName() === 'mb_strlen') {
			$returnType = IntegerRangeType::fromInterval(0, null);
		}

		if (count($functionCall->getArgs()) < $positionEncodingParam) {
			return TypeCombinator::remove($returnType, new BooleanType());
		}

		$strings = TypeUtils::getConstantStrings($scope->getType($functionCall->getArgs()[$positionEncodingParam - 1]->value));
		$results = array_unique(array_map(fn (ConstantStringType $encoding): bool => $this->isSupportedEncoding($encoding->getValue()), $strings));

		if ($returnType->equals(new UnionType([new StringType(), new BooleanType()]))) {
			return count($results) === 1 ? new ConstantBooleanType($results[0]) : new BooleanType();
		}

		if (count($results) === 1) {
			return $results[0]
				? TypeCombinator::remove($returnType, new ConstantBooleanType(false))
				: new ConstantBooleanType(false);
		}

		return $returnType;
	}

}
