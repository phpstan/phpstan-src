<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\NeverType;
use PHPStan\Type\NullType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\UnionType;
use function array_map;
use function count;
use function is_string;
use function parse_url;
use function str_contains;
use function strcasecmp;
use function strlen;

class CurlInitReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	/** @see https://github.com/curl/curl/blob/curl-8_9_1/lib/urldata.h#L135 */
	private const CURL_MAX_INPUT_LENGTH = 8000000;

	public function __construct(private PhpVersion $phpVersion)
	{
	}

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'curl_init';
	}

	public function getTypeFromFunctionCall(
		FunctionReflection $functionReflection,
		Node\Expr\FuncCall $functionCall,
		Scope $scope,
	): Type
	{
		$args = $functionCall->getArgs();
		$argsCount = count($args);
		$returnType = ParametersAcceptorSelector::selectSingle($functionReflection->getVariants())->getReturnType();
		$notFalseReturnType = TypeCombinator::remove($returnType, new ConstantBooleanType(false));
		if ($argsCount === 0) {
			return $notFalseReturnType;
		}

		$urlArgType = $scope->getType($args[0]->value);
		if ($urlArgType->isConstantScalarValue()->yes() && (new UnionType([new NullType(), new StringType()]))->isSuperTypeOf($urlArgType)->yes()) {
			$urlArgReturnTypes = array_map(
				fn ($value) => $this->getUrlArgValueReturnType($value, $returnType, $notFalseReturnType),
				$urlArgType->getConstantScalarValues(),
			);
			return TypeCombinator::union(...$urlArgReturnTypes);
		}

		return $returnType;
	}

	private function getUrlArgValueReturnType(mixed $urlArgValue, Type $returnType, Type $notFalseReturnType): Type
	{
		if ($urlArgValue === null) {
			return $notFalseReturnType;
		}
		if (!is_string($urlArgValue)) {
			throw new ShouldNotHappenException();
		}
		if (str_contains($urlArgValue, "\0")) {
			if (!$this->phpVersion->throwsValueErrorForInternalFunctions()) {
				// https://github.com/php/php-src/blob/php-7.4.33/ext/curl/interface.c#L112-L115
				return new ConstantBooleanType(false);
			}
			// https://github.com/php/php-src/blob/php-8.0.0/ext/curl/interface.c#L104-L107
			return new NeverType();
		}
		if ($this->phpVersion->isCurloptUrlCheckingFileSchemeWithOpenBasedir()) {
			// Before PHP 8.0 an unparsable URL or a file:// scheme would fail if open_basedir is used
			// Since we can't detect open_basedir properly, we'll always consider a failure possible if these
			//   conditions are given
			// https://github.com/php/php-src/blob/php-7.4.33/ext/curl/interface.c#L139-L158
			$parsedUrlArgValue = parse_url($urlArgValue);
			if ($parsedUrlArgValue === false || (isset($parsedUrlArgValue['scheme']) && strcasecmp($parsedUrlArgValue['scheme'], 'file') === 0)) {
				return $returnType;
			}
		}
		if (strlen($urlArgValue) > self::CURL_MAX_INPUT_LENGTH) {
			// Since libcurl 7.65.0 this would always fail, but no current PHP version requires it at the moment
			// https://github.com/curl/curl/commit/5fc28510a4664f46459d9a40187d81cc08571e60
			return $returnType;
		}
		return $notFalseReturnType;
	}

}
