<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\Type;
use Random\Randomizer;
use function count;
use function in_array;

#[AutowiredService]
final class RandomizerMethodReturnTypeExtension implements DynamicMethodReturnTypeExtension
{

	public function __construct(
		private RandomArrayKeysReturnTypeHelper $randomArrayKeysReturnTypeHelper,
		private StringBytesReturnTypeHelper $stringBytesReturnTypeHelper,
		private RandomIntRangeHelper $randomIntRangeHelper,
	)
	{
	}

	public function getClass(): string
	{
		return Randomizer::class;
	}

	public function isMethodSupported(MethodReflection $methodReflection): bool
	{
		return in_array($methodReflection->getName(), [
			'shuffleArray',
			'pickArrayKeys',
			'shuffleBytes',
			'getBytesFromString',
			'getInt',
		], true);
	}

	public function getTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, Scope $scope): ?Type
	{
		$args = $methodCall->getArgs();
		if (count($args) < 1) {
			return null;
		}

		$firstArgType = $scope->getType($args[0]->value);

		switch ($methodReflection->getName()) {
			case 'shuffleArray':
				return $firstArgType->shuffleArray();
			case 'pickArrayKeys':
				// $num is validated to be between 1 and the size of the array,
				// so a successful call always returns at least one key.
				return $this->randomArrayKeysReturnTypeHelper->getPickedKeysListType($firstArgType);
			case 'shuffleBytes':
				return $this->stringBytesReturnTypeHelper->getReorderedStringType($firstArgType);
			case 'getBytesFromString':
				return $this->stringBytesReturnTypeHelper->getNonEmptySelectionStringType($firstArgType);
			case 'getInt':
				if (count($args) < 2) {
					return null;
				}

				return $this->randomIntRangeHelper->createRange(
					$firstArgType->toInteger(),
					$scope->getType($args[1]->value)->toInteger(),
				);
		}

		return null;
	}

}
