<?php

declare(strict_types=1);

namespace ResultCacheE2E\Dependency;

use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Scalar\String_;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\IntegerType;
use PHPStan\Type\MixedType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;

final class ConfigValueDynamicReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{
	public function __construct(
		private ConfigTypeRegistry $configTypeRegistry,
		private TenantConfigTypeRegistry $tenantConfigTypeRegistry,
	)
	{
	}

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'configValue'
			|| $functionReflection->getName() === 'configuredConnectionValue'
			|| $functionReflection->getName() === 'tenantConfigValue';
	}

	public function getTypeFromFunctionCall(
		FunctionReflection $functionReflection,
		FuncCall $functionCall,
		Scope $scope,
	): Type
	{
		$keyArgument = $functionCall->getArgs()[0] ?? null;
		if ($keyArgument === null || !$keyArgument->value instanceof String_) {
			return new MixedType();
		}

		$key = $keyArgument->value->value;
		$configTypeRegistry = $functionReflection->getName() === 'tenantConfigValue'
			? $this->tenantConfigTypeRegistry
			: $this->configTypeRegistry;
		if ($functionReflection->getName() === 'configuredConnectionValue') {
			$key = $this->configTypeRegistry->getSelectedConnectionKey($key);
		}

		return match ($configTypeRegistry->get($key)) {
			'string' => new StringType(),
			'int' => new IntegerType(),
			default => new MixedType(),
		};
	}
}
