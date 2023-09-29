<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

use PhpParser\Node\Name;
use PHPStan\Testing\PHPStanTestCase;
use PHPStan\Type\VerbosityLevel;
use function array_keys;
use function count;
use function explode;
use function file_get_contents;
use function floor;
use function implode;
use function trim;
use const PHP_VERSION_ID;

class ReflectionProviderGoldenTest extends PHPStanTestCase
{

	/** @return iterable<string, array<string>> */
	public static function data(): iterable
	{
		$first = (int) floor(PHP_VERSION_ID / 10000);
		$second = (int) (floor(PHP_VERSION_ID % 10000) / 100);
		$currentVersion = $first . '.' . $second;
		$contents = file_get_contents(__DIR__ . '/data/golden/reflection-' . $currentVersion . '.test');

		if ($contents === false) {
			self::fail('Reflection test data is missing for PHP ' . $currentVersion);
		}

		$parts = explode('-----', $contents);

		for ($i = 1; $i + 1 < count($parts); $i += 2) {
			$input = trim($parts[$i]);
			$output = trim($parts[$i + 1]);

			yield $input => [
				$input,
				$output,
			];
		}
	}

	/** @dataProvider data */
	public function test(string $input, string $expectedOutput): void
	{
		[$type, $name] = explode(' ', $input);

		$output = match ($type) {
			'FUNCTION' => self::generateFunctionDescription($name),
			'CLASS' => self::generateClassDescription($name),
			'METHOD' => self::generateClassMethodDescription($name),
			'PROPERTY' => self::generateClassPropertyDescription($name),
			default => $this->fail('Unknown type ' . $type),
		};
		$output = trim($output);

		$this->assertSame($expectedOutput, $output);
	}

	public static function generateFunctionDescription(string $functionName): string
	{
		$nameNode = new Name($functionName);
		$reflectionProvider = self::getContainer()->getByType(ReflectionProvider::class);

		if (! $reflectionProvider->hasFunction($nameNode, null)) {
			return "MISSING\n";
		}

		$functionReflection = $reflectionProvider->getFunction($nameNode, null);
		$result = self::generateFunctionMethodBaseDescription($functionReflection);

		if (! $functionReflection->isBuiltin()) {
			$result .= "NOT BUILTIN\n";
		}

		$result .= self::generateVariantsDescription($functionReflection->getName(), $functionReflection->getVariants());

		return $result;
	}

	public static function generateClassDescription(string $className): string
	{
		$reflectionProvider = self::getContainer()->getByType(ReflectionProvider::class);

		if (! $reflectionProvider->hasClass($className)) {
			return "MISSING\n";
		}

		$result = '';
		$classReflection = $reflectionProvider->getClass($className);

		if ($classReflection->isDeprecated()) {
			$result .= "Deprecated\n";
		}

		if (! $classReflection->isBuiltin()) {
			$result .= "Not builtin\n";
		}

		if ($classReflection->isInternal()) {
			$result .= "Internal\n";
		}

		if ($classReflection->isImmutable()) {
			$result .= "Immutable\n";
		}

		if ($classReflection->hasConsistentConstructor()) {
			$result .= "Consistent constructor\n";
		}

		$parentReflection = $classReflection->getParentClass();
		$extends = '';

		if ($parentReflection !== null) {
			$extends = ' extends ' . $parentReflection->getName();
		}

		$attributes = [];

		if ($classReflection->allowsDynamicProperties()) {
			$attributes[] = "#[AllowDynamicProperties]\n";
		}

		$attributesTxt = implode('', $attributes);
		$abstractTxt = $classReflection->isAbstract()
			? 'abstract '
			: '';
		$keyword = match (true) {
			$classReflection->isEnum() => 'enum',
			$classReflection->isInterface() => 'interface',
			$classReflection->isTrait() => 'trait',
			$classReflection->isClass() => 'class',
			default => self::fail(),
		};
		$verbosityLevel = VerbosityLevel::precise();
		$backedEnumType = $classReflection->getBackedEnumType();
		$backedEnumTypeTxt = $backedEnumType !== null
			? ': ' . $backedEnumType->describe($verbosityLevel)
			: '';
		$readonlyTxt = $classReflection->isReadOnly()
			? 'readonly '
			: '';
		$interfaceNames = array_keys($classReflection->getImmediateInterfaces());
		$implementsTxt = $interfaceNames !== []
			? ($classReflection->isInterface() ? ' extends ' : ' implements ') . implode(', ', $interfaceNames)
			: '';
		$finalTxt = $classReflection->isFinal()
			? 'final '
			: '';
		$result .= $attributesTxt . $finalTxt . $readonlyTxt . $abstractTxt . $keyword . ' '
			. $classReflection->getName() . $extends . $implementsTxt . $backedEnumTypeTxt . "\n";
		$result .= "{\n";
		$ident = '    ';

		foreach (array_keys($classReflection->getTraits()) as $trait) {
			$result .= $ident . 'use ' . $trait . ";\n";
		}

		$result .= "}\n";

		return $result;
	}

	public static function generateClassMethodDescription(string $classMethodName): string
	{
		[$className, $methodName] = explode('::', $classMethodName);

		$reflectionProvider = self::getContainer()->getByType(ReflectionProvider::class);

		if (! $reflectionProvider->hasClass($className)) {
			return "MISSING\n";
		}

		$classReflection = $reflectionProvider->getClass($className);

		if (! $classReflection->hasNativeMethod($methodName)) {
			return "MISSING\n";
		}

		$methodReflection = $classReflection->getNativeMethod($methodName);
		$result = self::generateFunctionMethodBaseDescription($methodReflection);
		$verbosityLevel = VerbosityLevel::precise();

		if ($methodReflection->getSelfOutType() !== null) {
			$result .= 'Self out type: ' . $methodReflection->getSelfOutType()->describe($verbosityLevel) . "\n";
		}

		if ($methodReflection->isStatic()) {
			$result .= "Static\n";
		}

		switch (true) {
			case $methodReflection->isPublic():
				$visibility = 'public';
				break;
			case $methodReflection->isPrivate():
				$visibility = 'private';
				break;
			default:
				$visibility = 'protected';
				break;
		}

		$result .= 'Visibility: ' . $visibility . "\n";
		$result .= self::generateVariantsDescription($methodReflection->getName(), $methodReflection->getVariants());

		return $result;
	}

	/** @param FunctionReflection|ExtendedMethodReflection $reflection */
	private static function generateFunctionMethodBaseDescription($reflection): string
	{
		$result = '';

		if (! $reflection->isDeprecated()->no()) {
			$result .= 'Is deprecated: ' . $reflection->isDeprecated()->describe() . "\n";
		}

		if (! $reflection->isFinal()->no()) {
			$result .= 'Is final: ' . $reflection->isFinal()->describe() . "\n";
		}

		if (! $reflection->isInternal()->no()) {
			$result .= 'Is internal: ' . $reflection->isInternal()->describe() . "\n";
		}

		if (! $reflection->returnsByReference()->no()) {
			$result .= 'Returns by reference: ' . $reflection->returnsByReference()->describe() . "\n";
		}

		if (! $reflection->hasSideEffects()->no()) {
			$result .= 'Has side-effects: ' . $reflection->hasSideEffects()->describe() . "\n";
		}

		if ($reflection->getThrowType() !== null) {
			$result .= 'Throw type: ' . $reflection->getThrowType()->describe(VerbosityLevel::precise()) . "\n";
		}

		return $result;
	}

	/** @param ParametersAcceptorWithPhpDocs[] $variants */
	private static function generateVariantsDescription(string $name, array $variants): string
	{
		$variantCount = count($variants);
		$result = 'Variants: ' . $variantCount . "\n";
		$variantIdent = '    ';
		$verbosityLevel = VerbosityLevel::precise();

		foreach ($variants as $variant) {
			$paramsNative = [];
			$paramsPhpDoc = [];

			foreach ($variant->getParameters() as $param) {
				$paramsPhpDoc[] = $variantIdent . ' * @param ' . $param->getType()->describe($verbosityLevel) . ' $' . $param->getName() . "\n";

				if ($param->getOutType() !== null) {
					$paramsPhpDoc[] = $variantIdent . ' * @param-out ' . $param->getOutType()->describe($verbosityLevel) . ' $' . $param->getName() . "\n";
				}

				$passedByRef = $param->passedByReference();

				if ($passedByRef->no()) {
					$refDes = '';
				} elseif ($passedByRef->createsNewVariable()) {
					$refDes = '&rw';
				} else {
					$refDes = '&r';
				}

				$variadicDesc = $param->isVariadic() ? '...' : '';
				$defValueDesc = $param->getDefaultValue() !== null
					? ' = ' . $param->getDefaultValue()->describe($verbosityLevel)
					: '';

				$paramsNative[] = $param->getNativeType()->describe($verbosityLevel) . ' ' . $variadicDesc . $refDes . '$' . $param->getName() . $defValueDesc;
			}

			$result .= $variantIdent . "/**\n";
			$result .= implode('', $paramsPhpDoc);
			$result .= $variantIdent . ' * @return ' . $variant->getReturnType()->describe($verbosityLevel) . "\n";
			$result .= $variantIdent . " */\n";
			$paramsTxt = implode(', ', $paramsNative);
			$result .= $variantIdent . 'function ' . $name . '(' . $paramsTxt . '): ' . $variant->getNativeReturnType()->describe($verbosityLevel) . "\n";
		}

		return $result;
	}

	public static function generateClassPropertyDescription(string $propertyName): string
	{
		[$className, $propertyName] = explode('::', $propertyName);

		$reflectionProvider = self::getContainer()->getByType(ReflectionProvider::class);

		if (! $reflectionProvider->hasClass($className)) {
			return "MISSING\n";
		}

		$classReflection = $reflectionProvider->getClass($className);

		if (! $classReflection->hasNativeProperty($propertyName)) {
			return "MISSING\n";
		}

		$result = '';
		$propertyReflection = $classReflection->getNativeProperty($propertyName);

		if (! $propertyReflection->isDeprecated()->no()) {
			$result .= 'Is deprecated: ' . $propertyReflection->isDeprecated()->describe() . "\n";
		}

		if (! $propertyReflection->isInternal()->no()) {
			$result .= 'Is internal: ' . $propertyReflection->isDeprecated()->describe() . "\n";
		}

		if ($propertyReflection->isStatic()) {
			$result .= "Static\n";
		}

		if ($propertyReflection->isReadOnly()) {
			$result .= "Readonly\n";
		}

		switch (true) {
			case $propertyReflection->isPublic():
				$visibility = 'public';
				break;
			case $propertyReflection->isPrivate():
				$visibility = 'private';
				break;
			default:
				$visibility = 'protected';
				break;
		}

		$result .= 'Visibility: ' . $visibility . "\n";
		$verbosityLevel = VerbosityLevel::precise();

		if ($propertyReflection->isReadable()) {
			$result .= 'Read type: ' . $propertyReflection->getReadableType()->describe($verbosityLevel) . "\n";
		}

		if ($propertyReflection->isWritable()) {
			$result .= 'Write type: ' . $propertyReflection->getWritableType()->describe($verbosityLevel) . "\n";
		}

		return $result;
	}

}
