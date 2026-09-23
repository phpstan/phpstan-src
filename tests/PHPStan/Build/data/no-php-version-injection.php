<?php declare(strict_types = 1);

namespace NoPhpVersionInjection;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;

final class InjectsPhpVersion implements DynamicFunctionReturnTypeExtension
{

	/** @var string */
	private $name;

	/** @var PhpVersion */
	private $phpVersion;

	public function __construct(
		string $name,
		PhpVersion $phpVersion
	)
	{
		$this->name = $name;
		$this->phpVersion = $phpVersion;
	}

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === $this->name;
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): ?Type
	{
		return new MixedType();
	}

}

final class UsesScope implements DynamicFunctionReturnTypeExtension
{

	/** @var string */
	private $name;

	public function __construct(string $name)
	{
		$this->name = $name;
	}

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === $this->name;
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): ?Type
	{
		$scope->getPhpVersion();
		return new MixedType();
	}

}

final class NotAnExtension
{

	/** @var PhpVersion|null */
	private $phpVersion;

	public function __construct(?PhpVersion $phpVersion)
	{
		$this->phpVersion = $phpVersion;
	}

}

final class InjectsNullablePhpVersion implements DynamicFunctionReturnTypeExtension
{

	/** @var PhpVersion|null */
	private $phpVersion;

	public function __construct(?PhpVersion $phpVersion)
	{
		$this->phpVersion = $phpVersion;
	}

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return false;
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): ?Type
	{
		return new MixedType();
	}

}
