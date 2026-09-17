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

/**
 * Randomizer methods mirror global functions PHPStan already describes:
 * shuffleArray() is shuffle(), pickArrayKeys() is array_rand(),
 * shuffleBytes() is str_shuffle() and getInt() is random_int().
 */
#[AutowiredService]
final class RandomizerMethodReturnTypeExtension implements DynamicMethodReturnTypeExtension
{

	public function __construct(
		private ArrayRandFunctionReturnTypeExtension $arrayRandExtension,
		private StrShuffleFunctionReturnTypeExtension $strShuffleExtension,
		private RandomIntFunctionReturnTypeExtension $randomIntExtension,
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
				// so unlike array_rand() a successful call always returns a list.
				return $this->arrayRandExtension->getPickedKeysListType($firstArgType);
			case 'shuffleBytes':
				return $this->strShuffleExtension->getShuffledStringType($firstArgType);
			case 'getInt':
				if (count($args) < 2) {
					return null;
				}

				return $this->randomIntExtension->createRange(
					$firstArgType->toInteger(),
					$scope->getType($args[1]->value)->toInteger(),
				);
		}

		return null;
	}

}
