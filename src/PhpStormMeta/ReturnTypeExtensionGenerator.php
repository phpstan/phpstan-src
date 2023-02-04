<?php declare(strict_types = 1);

namespace PHPStan\PhpStormMeta;

use PHPStan\PhpStormMeta\TypeMapping\CallReturnOverrideCollection;
use PHPStan\PhpStormMeta\TypeMapping\FunctionCallTypeOverride;
use PHPStan\PhpStormMeta\TypeMapping\MethodCallTypeOverride;
use function array_key_exists;

class ReturnTypeExtensionGenerator
{

	public function __construct()
	{
	}

	public function generateExtensionsBasedOnMeta(CallReturnOverrideCollection $overrides): ReturnTypeExtensionCollection
	{
		$resolver = new TypeFromMetaResolver($overrides);

		$result = new ReturnTypeExtensionCollection();

		/** @var array<string, list<string>> $classMethodMap */
		$classMethodMap = [];
		/** @var list<string> $functionList */
		$functionList = [];

		foreach ($overrides->getAllOverrides() as $override) {
			if ($override instanceof MethodCallTypeOverride) {
				if (!array_key_exists($override->classlikeName, $classMethodMap)) {
					$classMethodMap[$override->classlikeName] = [];
				}
				$classMethodMap[$override->classlikeName][] = $override->methodName;
			} elseif ($override instanceof FunctionCallTypeOverride) {
				$functionList [] = $override->functionName;
			}
		}

		foreach ($classMethodMap as $className => $methodList) {
			$result->nonStaticMethodExtensions [] = new NonStaticMethodReturnTypeResolver($resolver, $className, $methodList);
			$result->staticMethodExtensions [] = new StaticMethodReturnTypeResolver($resolver, $className, $methodList);
		}

		$result->functionExtensions [] = new FunctionReturnTypeResolver($resolver, $functionList);

		return $result;
	}

}
