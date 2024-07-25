<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\BitwiseFlagHelper;
use PHPStan\Type\DynamicFunctionThrowTypeExtension;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use function in_array;

final class JsonThrowTypeExtension implements DynamicFunctionThrowTypeExtension
{

	private const ARGUMENTS_POSITIONS = [
		'json_encode' => 1,
		'json_decode' => 3,
	];

	public function __construct(
		private ReflectionProvider $reflectionProvider,
		private BitwiseFlagHelper $bitwiseFlagAnalyser,
	)
	{
	}

	public function isFunctionSupported(
		FunctionReflection $functionReflection,
	): bool
	{
		return in_array(
			$functionReflection->getName(),
			[
				'json_encode',
				'json_decode',
			],
			true,
		) && $this->reflectionProvider->hasConstant(new Name\FullyQualified('JSON_THROW_ON_ERROR'), null);
	}

	public function getThrowTypeFromFunctionCall(
		FunctionReflection $functionReflection,
		FuncCall $functionCall,
		Scope $scope,
	): ?Type
	{
		$argumentPosition = self::ARGUMENTS_POSITIONS[$functionReflection->getName()];
		if (!isset($functionCall->getArgs()[$argumentPosition])) {
			return null;
		}

		$optionsExpr = $functionCall->getArgs()[$argumentPosition]->value;
		if (!$this->bitwiseFlagAnalyser->bitwiseOrContainsConstant($optionsExpr, $scope, 'JSON_THROW_ON_ERROR')->no()) {
			return new ObjectType('JsonException');
		}

		return null;
	}

}
