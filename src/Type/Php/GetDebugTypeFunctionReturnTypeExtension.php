<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\UnionType;
use function array_key_first;
use function array_map;
use function count;

#[AutowiredService]
final class GetDebugTypeFunctionReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'get_debug_type';
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): ?Type
	{
		if (count($functionCall->getArgs()) < 1) {
			return null;
		}

		$argType = $scope->getType($functionCall->getArgs()[0]->value);
		if ($argType instanceof UnionType) {
			return TypeCombinator::union(...array_map(static fn (Type $type) => self::resolveOneType($type), $argType->getTypes()));
		}
		return self::resolveOneType($argType);
	}

	/**
	 * @see https://www.php.net/manual/en/function.get-debug-type.php#refsect1-function.get-debug-type-returnvalues
	 * @see https://github.com/php/php-src/commit/ef0e4478c51540510b67f7781ad240f5e0592ee4
	 */
	private static function resolveOneType(Type $type): Type
	{
		if ($type->isNull()->yes()) {
			return new ConstantStringType('null');
		}
		if ($type->isBoolean()->yes()) {
			return new ConstantStringType('bool');
		}
		if ($type->isInteger()->yes()) {
			return new ConstantStringType('int');
		}
		if ($type->isFloat()->yes()) {
			return new ConstantStringType('float');
		}
		if ($type->isString()->yes()) {
			return new ConstantStringType('string');
		}
		if ($type->isArray()->yes()) {
			return new ConstantStringType('array');
		}

		// "resources" type+state is skipped since we cannot infer the state

		if ($type->isObject()->yes()) {
			$reflections = $type->getObjectClassReflections();
			$types = [];
			foreach ($reflections as $reflection) {
				// if the class is not final, the actual returned string might be of a child class
				if ($reflection->isFinal() && !$reflection->isAnonymous()) {
					$types[] = new ConstantStringType($reflection->getName());
				}

				if ($reflection->isAnonymous()) { // phpcs:ignore
					$parentClass = $reflection->getParentClass();
					$implementedInterfaces = $reflection->getImmediateInterfaces();
					if ($parentClass !== null) {
						$types[] = new ConstantStringType($parentClass->getName() . '@anonymous');
					} elseif ($implementedInterfaces !== []) {
						$firstInterface = $implementedInterfaces[array_key_first($implementedInterfaces)];
						$types[] = new ConstantStringType($firstInterface->getName() . '@anonymous');
					} else {
						$types[] = new ConstantStringType('class@anonymous');
					}
				}
			}

			switch (count($types)) {
				case 0:
					return new StringType();
				case 1:
					return $types[0];
				default:
					return TypeCombinator::union(...$types);
			}
		}

		return new StringType();
	}

}
