<?php declare(strict_types = 1);

namespace PHPStan\Rules\InternalTag;

use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Rules\RestrictedUsage\RestrictedFunctionUsageExtension;
use PHPStan\Rules\RestrictedUsage\RestrictedUsage;
use function array_slice;
use function explode;
use function sprintf;

final class RestrictedInternalFunctionUsageExtension implements RestrictedFunctionUsageExtension
{

	public function __construct(private RestrictedInternalUsageHelper $helper)
	{
	}

	public function isRestrictedFunctionUsage(FunctionReflection $functionReflection, Scope $scope): ?RestrictedUsage
	{
		if (!$functionReflection->isInternal()->yes()) {
			return null;
		}

		if (!$this->helper->shouldFunctionBeReported($scope, $functionReflection)) {
			return null;
		}

		$namespace = array_slice(explode('\\', $functionReflection->getName()), 0, -1)[0] ?? null;
		if ($namespace === null) {
			return RestrictedUsage::create(
				sprintf(
					'Call to internal function %s().',
					$functionReflection->getName(),
				),
				'function.internal',
			);
		}

		return RestrictedUsage::create(
			sprintf(
				'Call to internal function %s() from outside its root namespace %s.',
				$functionReflection->getName(),
				$namespace,
			),
			'function.internal',
		);
	}

}
