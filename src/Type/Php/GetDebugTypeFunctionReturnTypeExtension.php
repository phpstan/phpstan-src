<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use Closure;
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\UnionType;
use function array_map;
use function count;

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
			return new UnionType(array_map(Closure::fromCallable([self::class, 'resolveOneType']), $argType->getTypes()));
		}
		return self::resolveOneType($argType);
	}

	/**
	 * @see https://www.php.net/manual/en/function.get-debug-type.php#refsect1-function.get-debug-type-returnvalues
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
					$types[] = new ConstantStringType('class@anonymous');
				}
			}

			switch (count($types)) {
				case 0:
					return new StringType();
				case 1:
					return $types[0];
				default:
					return new UnionType($types);
			}
		}

		return new StringType();
	}

}
