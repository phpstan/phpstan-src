<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\InitializerExprContext;
use PHPStan\Reflection\InitializerExprTypeResolver;
use function count;

#[AutowiredService]
final class DeprecatedAttributeResolver
{

	public function __construct(
		private InitializerExprTypeResolver $initializerExprTypeResolver,
	)
	{
	}

	/**
	 * @return array{bool, string|null}
	 */
	public function getDeprecatedAttribute(Scope $scope, Node\Stmt\Function_|Node\Stmt\ClassMethod|Node\PropertyHook $stmt): array
	{
		$initializerExprContext = InitializerExprContext::fromStubParameter(
			$scope->isInClass() ? $scope->getClassReflection()->getName() : null,
			$scope->getFile(),
			$stmt,
		);
		$isDeprecated = false;
		$deprecatedDescription = null;
		$deprecatedDescriptionType = null;
		foreach ($stmt->attrGroups as $attrGroup) {
			foreach ($attrGroup->attrs as $attr) {
				if ($attr->name->toString() !== 'Deprecated') {
					continue;
				}
				$isDeprecated = true;
				$arguments = $attr->args;
				foreach ($arguments as $i => $arg) {
					$argName = $arg->name;
					if ($argName === null) {
						if ($i !== 0) {
							continue;
						}

						$deprecatedDescriptionType = $this->initializerExprTypeResolver->getType($arg->value, $initializerExprContext);
						break;
					}

					if ($argName->toString() !== 'message') {
						continue;
					}

					$deprecatedDescriptionType = $this->initializerExprTypeResolver->getType($arg->value, $initializerExprContext);
					break;
				}
			}
		}

		if ($deprecatedDescriptionType !== null) {
			$constantStrings = $deprecatedDescriptionType->getConstantStrings();
			if (count($constantStrings) === 1) {
				$deprecatedDescription = $constantStrings[0]->getValue();
			}
		}

		return [$isDeprecated, $deprecatedDescription];
	}

}
