<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Scalar\Int_;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\Type;
use Random\Randomizer;
use function array_map;
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

		switch ($methodReflection->getName()) {
			case 'shuffleArray':
				return $scope->getType($args[0]->value)->shuffleArray();
			case 'pickArrayKeys':
				// $num is validated to be between 1 and the size of the array, so unlike
				// array_rand() a successful call always returns a list of keys - which is
				// what array_rand() returns when asked for more than one key.
				return $scope->getType($this->createFuncCall('array_rand', [
					$args[0]->value,
					new Int_(2),
				]));
			case 'shuffleBytes':
				return $scope->getType($this->createFuncCall('str_shuffle', [$args[0]->value]));
			case 'getInt':
				if (count($args) < 2) {
					return null;
				}

				return $scope->getType($this->createFuncCall('random_int', [
					$args[0]->value,
					$args[1]->value,
				]));
		}

		return null;
	}

	/**
	 * @param non-empty-string $functionName
	 * @param list<Expr> $argValues
	 */
	private function createFuncCall(string $functionName, array $argValues): FuncCall
	{
		return new FuncCall(
			new FullyQualified($functionName),
			array_map(static fn (Expr $argValue): Arg => new Arg($argValue), $argValues),
		);
	}

}
