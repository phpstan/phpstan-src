<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\DynamicFunctionThrowTypeExtension;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\VoidType;
use TypeError;
use function count;
use function preg_match;

/**
 * unserialize() throws TypeError and ValueError for invalid $options, and
 * TypeError when the data does not fit a typed property. Objects of classes
 * that are not allowed are unserialized as __PHP_Incomplete_Class.
 */
#[AutowiredService]
final class UnserializeFunctionThrowTypeExtension implements DynamicFunctionThrowTypeExtension
{

	public function __construct(private PhpVersion $phpVersion)
	{
	}

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'unserialize';
	}

	public function getThrowTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $funcCall, Scope $scope): ?Type
	{
		if (!$this->phpVersion->throwsValueErrorForInternalFunctions()) {
			return new VoidType();
		}

		$args = $funcCall->getArgs();
		foreach ($args as $arg) {
			if ($arg->unpack || $arg->name !== null) {
				return $functionReflection->getThrowType();
			}
		}

		if (count($args) < 2) {
			return new ObjectType(TypeError::class);
		}

		$optionsType = $scope->getNativeType($args[1]->value);
		$constantArrays = $optionsType->getConstantArrays();
		if (!$optionsType->isArray()->yes() || count($constantArrays) === 0) {
			return $functionReflection->getThrowType();
		}

		$allowsNoClasses = true;
		foreach ($constantArrays as $constantArray) {
			if (!$this->areOptionsValid($constantArray)) {
				return $functionReflection->getThrowType();
			}

			if ($this->allowsNoClasses($constantArray)) {
				continue;
			}

			$allowsNoClasses = false;
		}

		if ($allowsNoClasses) {
			return new VoidType();
		}

		return new ObjectType(TypeError::class);
	}

	private function areOptionsValid(ConstantArrayType $options): bool
	{
		$allowedClassesKey = new ConstantStringType('allowed_classes');
		if (!$options->hasOffsetValueType($allowedClassesKey)->no()) {
			$allowedClasses = $options->getOffsetValueType($allowedClassesKey);
			if (!$allowedClasses->isBoolean()->yes() && !$this->isListOfClassNames($allowedClasses)) {
				return false;
			}
		}

		$maxDepthKey = new ConstantStringType('max_depth');
		if (!$options->hasOffsetValueType($maxDepthKey)->no()) {
			return IntegerRangeType::fromInterval(0, null)->isSuperTypeOf($options->getOffsetValueType($maxDepthKey))->yes();
		}

		return true;
	}

	private function isListOfClassNames(Type $type): bool
	{
		if (!$type->isArray()->yes()) {
			return false;
		}

		if ($type->isIterableAtLeastOnce()->no()) {
			return true;
		}

		$classNames = $type->getIterableValueType()->getConstantStrings();
		if (count($classNames) === 0 || !$type->getIterableValueType()->isString()->yes()) {
			return false;
		}

		foreach ($classNames as $className) {
			if (preg_match('~^[0-9A-Za-z_\\\\\x80-\xff]*$~', $className->getValue()) !== 1) {
				return false;
			}
		}

		return true;
	}

	/**
	 * With no allowed classes, objects become __PHP_Incomplete_Class and no
	 * typed property can reject the data.
	 */
	private function allowsNoClasses(ConstantArrayType $options): bool
	{
		$allowedClassesKey = new ConstantStringType('allowed_classes');
		if (!$options->hasOffsetValueType($allowedClassesKey)->yes()) {
			return false;
		}

		$allowedClasses = $options->getOffsetValueType($allowedClassesKey);

		return $allowedClasses->isFalse()->yes()
			|| ($allowedClasses->isArray()->yes() && $allowedClasses->isIterableAtLeastOnce()->no());
	}

}
