<?php declare(strict_types = 1);

namespace PHPStan\PhpStormMeta;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\Type;
use function array_map;
use function count;
use function in_array;
use function strtolower;

class FunctionReturnTypeResolver implements DynamicFunctionReturnTypeExtension
{

	/** @var list<string> */
	private readonly array $functionNames;

	/**
	 * @param list<string> $functionNames
	 */
	public function __construct(
		private readonly TypeFromMetaResolver $metaResolver,
		array $functionNames,
	)
	{
		$this->functionNames = array_map(strtolower(...), $functionNames);
	}

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		$functionName = strtolower($functionReflection->getName());

		return in_array($functionName, $this->functionNames, true);
	}

	public function getTypeFromFunctionCall(
		FunctionReflection $functionReflection,
		FuncCall $functionCall,
		Scope $scope,
	): ?Type
	{
		$functionName = strtolower($functionReflection->getName());
		$args = $functionCall->getArgs();

		if (count($args) > 0) {
			return $this->metaResolver->resolveReferencedType($functionName, ...$args);
		}

		return null;
	}

}
