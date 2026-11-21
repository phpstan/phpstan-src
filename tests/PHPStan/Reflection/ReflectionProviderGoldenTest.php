<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

use JetBrains\PHPStormStub\PhpStormStubsMap;
use Override;
use PhpParser\Node;
use PhpParser\Node\Name;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use PHPStan\Parser\Parser;
use PHPStan\Php8StubsMap;
use PHPStan\PhpDoc\Tag\AssertTag;
use PHPStan\ShouldNotHappenException;
use PHPStan\Testing\PHPStanTestCase;
use PHPStan\Type\Generic\TemplateType;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\VerbosityLevel;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionClass;
use Symfony\Component\Finder\Finder;
use Throwable;
use function array_keys;
use function array_merge;
use function count;
use function dirname;
use function explode;
use function file_get_contents;
use function file_put_contents;
use function floor;
use function get_class;
use function get_declared_classes;
use function get_defined_functions;
use function getenv;
use function implode;
use function is_bool;
use function mkdir;
use function sort;
use function strpos;
use function substr;
use function trim;
use const PHP_INT_MAX;
use const PHP_VERSION_ID;

class ReflectionProviderGoldenTest extends PHPStanTestCase
{

	/** @return iterable<string, array<string>> */
	public static function data(): iterable
	{
		$inputFile = self::getTestInputFile();
		$contents = file_get_contents($inputFile);

		if ($contents === false) {
			self::fail('Input file \'' . $inputFile . '\' is missing.');
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

	#[DataProvider('data')]
	public function test(string $input, string $expectedOutput): void
	{
		$output = self::generateSymbolDescription($input);
		$output = trim($output);
		$this->assertSame($expectedOutput, $output);
	}

	private static function generateSymbolDescription(string $symbol): string
	{
		[$type, $name] = explode(' ', $symbol);

		if ($name === '') {
			throw new ShouldNotHappenException();
		}

		try {
			switch ($type) {
				case 'FUNCTION':
					return self::generateFunctionDescription($name);
				case 'CLASS':
					return self::generateClassDescription($name);
				case 'METHOD':
					return self::generateClassMethodDescription($name);
				case 'PROPERTY':
					return self::generateClassPropertyDescription($name);
				default:
					self::fail('Unknown symbol type ' . $type);
			}
		} catch (Throwable $e) {
			// phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly
			if ($e instanceof \PHPUnit\Exception) {
				throw $e;
			}

			// Skip stack trace - it's not fully consistent between dump and test.
			return "Generating symbol description failed:\n"
				. get_class($e) . ': ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine() . "\n";
		}
	}

	public static function dumpOutput(): void
	{
		$symbolsTxt = file_get_contents(self::getPhpSymbolsFile());

		if ($symbolsTxt === false) {
			throw new ShouldNotHappenException('Cannot read phpSymbols.txt');
		}

		$symbols = explode("\n", $symbolsTxt);
		$separator = '-----';
		$contents = '';

		foreach ($symbols as $line) {
			$contents .= $separator . "\n";
			$contents .= $line . "\n";
			$contents .= $separator . "\n";
			$contents .= self::generateSymbolDescription($line);
		}

		$result = file_put_contents(self::getTestInputFile(), $contents);

		if ($result !== false) {
			return;
		}

		throw new ShouldNotHappenException('Failed write dump for reflection golden test.');
	}

	private static function getTestInputFile(): string
	{
		$fileFromEnv = getenv('REFLECTION_GOLDEN_TEST_FILE');

		if ($fileFromEnv !== false) {
			return $fileFromEnv;
		}

		$first = (int) floor(PHP_VERSION_ID / 10000);
		$second = (int) (floor(PHP_VERSION_ID % 10000) / 100);
		$currentVersion = $first . '.' . $second;

		return __DIR__ . '/data/golden/reflection-' . $currentVersion . '.test';
	}

	private static function getPhpSymbolsFile(): string
	{
		$fileFromEnv = getenv('REFLECTION_GOLDEN_SYMBOLS_FILE');

		if ($fileFromEnv !== false) {
			return $fileFromEnv;
		}

		return __DIR__ . '/data/golden/phpSymbols.txt';
	}

	/**
	 * @param non-empty-string $functionName
	 */
	private static function generateFunctionDescription(string $functionName): string
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

		$result .= self::generateVariantsDescription($functionReflection->getName(), $functionReflection->getVariants(), false);
		$namedArgumentsVariants = $functionReflection->getNamedArgumentsVariants();

		if ($namedArgumentsVariants !== null) {
			$result .= self::generateVariantsDescription($functionReflection->getName(), $namedArgumentsVariants, true);
		}

		return $result;
	}

	private static function generateClassDescription(string $className): string
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

		if ($classReflection->getDeprecatedDescription() !== null) {
			$result .= 'Deprecated description: ' . $classReflection->getDeprecatedDescription() . "\n";
		}

		if ($classReflection->isAttributeClass()) {
			$result .= 'Attribute class flags: ' . $classReflection->getAttributeClassFlags() . "\n";
		}

		foreach (self::describeTemplateTypeMap($classReflection->getTemplateTypeMap(), '') as $templateLine) {
			$result .= $templateLine;
		}

		$parentReflection = $classReflection->getParentClass();
		if ($parentReflection !== null && $parentReflection->getDisplayName() !== $parentReflection->getName()) {
			$result .= '@extends ' . $parentReflection->getDisplayName() . "\n";
		}

		foreach ($classReflection->getImmediateInterfaces() as $interfaceReflection) {
			if ($interfaceReflection->getDisplayName() === $interfaceReflection->getName()) {
				continue;
			}

			$result .= ($classReflection->isInterface() ? '@extends ' : '@implements ') . $interfaceReflection->getDisplayName() . "\n";
		}

		foreach ($classReflection->getResolvedMixinTypes() as $mixinType) {
			$result .= '@mixin ' . $mixinType->describe(VerbosityLevel::precise()) . "\n";
		}

		$extends = '';

		if ($parentReflection !== null) {
			$extends = ' extends ' . $parentReflection->getName();
		}

		$attributes = [];

		if ($classReflection->allowsDynamicProperties()) {
			$attributes[] = "#[AllowDynamicProperties]\n";
		}

		$attributesTxt = implode('', $attributes) . self::describeAttributes($classReflection->getAttributes(), "\n");
		$abstractTxt = $classReflection->isAbstract()
			? 'abstract '
			: '';

		switch (true) {
			case $classReflection->isEnum():
				$keyword = 'enum';
				break;
			case $classReflection->isInterface():
				$keyword = 'interface';
				break;
			case $classReflection->isTrait():
				$keyword = 'trait';
				break;
			case $classReflection->isClass():
				$keyword = 'class';
				break;
			default:
				self::fail();
		}

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

		if ($classReflection->isEnum()) {
			foreach ($classReflection->getEnumCases() as $enumCase) {
				$backingValueType = $enumCase->getBackingValueType();
				$result .= $ident . 'case ' . $enumCase->getName()
					. ($backingValueType !== null ? ' = ' . $backingValueType->describe($verbosityLevel) : '')
					. ";\n";
			}
		}

		return $result . "}\n";
	}

	private static function generateClassMethodDescription(string $classMethodName): string
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

		$isAbstract = $methodReflection->isAbstract();
		if (is_bool($isAbstract) ? $isAbstract : ! $isAbstract->no()) {
			$result .= 'Abstract' . (is_bool($isAbstract) ? '' : ': ' . $isAbstract->describe()) . "\n";
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
		$result .= self::generateVariantsDescription($methodReflection->getName(), $methodReflection->getVariants(), false);
		$namedArgumentsVariants = $methodReflection->getNamedArgumentsVariants();

		if ($namedArgumentsVariants !== null) {
			$result .= self::generateVariantsDescription($methodReflection->getName(), $namedArgumentsVariants, true);
		}

		return $result;
	}

	/** @param FunctionReflection|ExtendedMethodReflection $reflection */
	private static function generateFunctionMethodBaseDescription($reflection): string
	{
		$result = '';

		if (! $reflection->isDeprecated()->no()) {
			$result .= 'Is deprecated: ' . $reflection->isDeprecated()->describe() . "\n";
		}

		if ($reflection->getDeprecatedDescription() !== null) {
			$result .= 'Deprecated description: ' . $reflection->getDeprecatedDescription() . "\n";
		}

		if ($reflection instanceof MethodReflection && ! $reflection->isFinal()->no()) {
			$result .= 'Is final: ' . $reflection->isFinal()->describe() . "\n";
		}

		if (! $reflection->isInternal()->no()) {
			$result .= 'Is internal: ' . $reflection->isInternal()->describe() . "\n";
		}

		if (is_bool($reflection->isBuiltin()) && $reflection->isBuiltin()) {
			$result .= 'Is built-in' . "\n";
		}

		if (!is_bool($reflection->isBuiltin()) && !$reflection->isBuiltin()->no()) {
			$result .= 'Is built-in: ' . $reflection->isBuiltin()->describe() . "\n";
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

		if (! $reflection->acceptsNamedArguments()->yes()) {
			$result .= 'Accepts named arguments: ' . $reflection->acceptsNamedArguments()->describe() . "\n";
		}

		if (! $reflection->mustUseReturnValue()->no()) {
			$result .= 'Must use return value: ' . $reflection->mustUseReturnValue()->describe() . "\n";
		}

		foreach ($reflection->getAsserts()->getAll() as $assert) {
			$result .= self::describeAssert($assert) . "\n";
		}

		$result .= self::describeAttributes($reflection->getAttributes(), "\n");

		return $result;
	}

	/** @param ExtendedParametersAcceptor[] $variants */
	private static function generateVariantsDescription(string $name, array $variants, bool $isNamedArguments): string
	{
		$variantCount = count($variants);
		$result = $isNamedArguments
			? 'Named arguments variants: '
			: 'Variants: ';
		$result .= $variantCount . "\n";
		$variantIdent = '    ';
		$verbosityLevel = VerbosityLevel::precise();

		foreach ($variants as $variant) {
			$paramsNative = [];
			$paramsPhpDoc = self::describeTemplateTypeMap($variant->getTemplateTypeMap(), $variantIdent . ' * ');

			foreach ($variant->getParameters() as $param) {
				$paramsPhpDoc[] = $variantIdent . ' * @param ' . $param->getType()->describe($verbosityLevel) . ' $' . $param->getName() . "\n";

				if ($param->getOutType() !== null) {
					$paramsPhpDoc[] = $variantIdent . ' * @param-out ' . $param->getOutType()->describe($verbosityLevel) . ' $' . $param->getName() . "\n";
				}

				if ($param->isImmediatelyInvokedCallable()->yes()) {
					$paramsPhpDoc[] = $variantIdent . ' * @param-immediately-invoked-callable $' . $param->getName() . "\n";
				} elseif ($param->isImmediatelyInvokedCallable()->no()) {
					$paramsPhpDoc[] = $variantIdent . ' * @param-later-invoked-callable $' . $param->getName() . "\n";
				}

				if ($param->getClosureThisType() !== null) {
					$paramsPhpDoc[] = $variantIdent . ' * @param-closure-this ' . $param->getClosureThisType()->describe($verbosityLevel) . ' $' . $param->getName() . "\n";
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

				$paramsNative[] = self::describeAttributes($param->getAttributes(), ' ') . $param->getNativeType()->describe($verbosityLevel) . ' ' . $variadicDesc . $refDes . '$' . $param->getName() . $defValueDesc;
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

	/**
	 * @return list<string>
	 */
	private static function describeTemplateTypeMap(TemplateTypeMap $templateTypeMap, string $prefix): array
	{
		$verbosityLevel = VerbosityLevel::precise();
		$lines = [];
		foreach ($templateTypeMap->getTypes() as $name => $type) {
			if (! $type instanceof TemplateType) {
				$lines[] = $prefix . '@template ' . $name . ' = ' . $type->describe($verbosityLevel) . "\n";
				continue;
			}

			$variance = $type->getVariance()->invariant() ? '' : '-' . $type->getVariance()->describe();
			$default = $type->getDefault() !== null ? ' = ' . $type->getDefault()->describe($verbosityLevel) : '';
			$lines[] = $prefix . '@template' . $variance . ' ' . $name . ' of ' . $type->getBound()->describe($verbosityLevel) . $default . "\n";
		}

		return $lines;
	}

	private static function describeAssert(AssertTag $assert): string
	{
		$tag = '@phpstan-assert';
		if ($assert->getIf() !== AssertTag::NULL) {
			$tag .= '-if-' . $assert->getIf();
		}

		return $tag . ' ' . ($assert->isNegated() ? '!' : '') . ($assert->isEquality() ? '=' : '')
			. $assert->getType()->describe(VerbosityLevel::precise()) . ' ' . $assert->getParameter()->describe();
	}

	/**
	 * @param list<AttributeReflection> $attributes
	 */
	private static function describeAttributes(array $attributes, string $separator): string
	{
		$result = '';
		foreach ($attributes as $attribute) {
			$arguments = [];
			foreach ($attribute->getArgumentTypes() as $argumentName => $argumentType) {
				$arguments[] = $argumentName . ': ' . $argumentType->describe(VerbosityLevel::precise());
			}

			$result .= '#[' . $attribute->getName() . ($arguments !== [] ? '(' . implode(', ', $arguments) . ')' : '') . ']' . $separator;
		}

		return $result;
	}

	private static function generateClassPropertyDescription(string $propertyName): string
	{
		[$className, $propertyName] = explode('::', $propertyName);
		// remove $
		$propertyName = substr($propertyName, 1);

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

		if ($propertyReflection->getDeprecatedDescription() !== null) {
			$result .= 'Deprecated description: ' . $propertyReflection->getDeprecatedDescription() . "\n";
		}

		if (! $propertyReflection->isInternal()->no()) {
			$result .= 'Is internal: ' . $propertyReflection->isInternal()->describe() . "\n";
		}

		if (! $propertyReflection->isAbstract()->no()) {
			$result .= 'Is abstract: ' . $propertyReflection->isAbstract()->describe() . "\n";
		}

		if (! $propertyReflection->isFinal()->no()) {
			$result .= 'Is final: ' . $propertyReflection->isFinal()->describe() . "\n";
		}

		if (! $propertyReflection->isVirtual()->no()) {
			$result .= 'Is virtual: ' . $propertyReflection->isVirtual()->describe() . "\n";
		}

		foreach (['get', 'set'] as $hookType) {
			if (! $propertyReflection->hasHook($hookType)) {
				continue;
			}

			$result .= 'Hook: ' . $hookType . "\n";
		}

		if ($propertyReflection->isPrivateSet()) {
			$result .= "Set visibility: private\n";
		} elseif ($propertyReflection->isProtectedSet()) {
			$result .= "Set visibility: protected\n";
		}

		$result .= self::describeAttributes($propertyReflection->getAttributes(), "\n");

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

	public static function dumpInputSymbols(): void
	{
		$symbols = self::scrapeInputSymbols();
		$symbolsFile = self::getPhpSymbolsFile();
		@mkdir(dirname($symbolsFile), recursive: true);
		$result = file_put_contents($symbolsFile, implode("\n", $symbols));

		if ($result !== false) {
			return;
		}

		throw new ShouldNotHappenException('Failed write dump for reflection golden test.');
	}

	/** @return list<string> */
	public static function scrapeInputSymbols(): array
	{
		$result = array_keys(
			self::scrapeInputSymbolsFromFunctionMap()
			+ self::scrapeInputSymbolsFromPhp8Stubs()
			+ self::scrapeInputSymbolsFromPhpStormStubs()
			+ self::scrapeInputSymbolsFromReflection(),
		);
		sort($result);

		return $result;
	}

	/** @return array<string, true> */
	private static function scrapeInputSymbolsFromFunctionMap(): array
	{
		$finder = new Finder();
		$files = $finder->files()->name('functionMap*.php')->in(__DIR__ . '/../../../resources');
		$combinedMap = [];

		foreach ($files as $file) {
			if ($file->getBasename() === 'functionMap.php') {
				$combinedMap += require $file->getPathname();
				continue;
			}

			$deltaMap = require $file->getPathname();

			// Deltas have new/old sections which contain the same format as the base functionMap.php
			foreach ($deltaMap as $functionMap) {
				$combinedMap += $functionMap;
			}
		}

		$result = [];

		foreach (array_keys($combinedMap) as $symbol) {
			// skip duplicated variants
			if (strpos($symbol, "'") !== false) {
				continue;
			}

			$parts = explode('::', $symbol);

			switch (count($parts)) {
				case 1:
					$result['FUNCTION ' . $symbol] = true;
					break;
				case 2:
					$result['CLASS ' . $parts[0]] = true;
					$result['METHOD ' . $symbol] = true;
					break;
				default:
					throw new ShouldNotHappenException('Invalid symbol ' . $symbol);
			}
		}

		return $result;
	}

	/** @return array<string, true> */
	private static function scrapeInputSymbolsFromPhp8Stubs(): array
	{
		// Currently the Php8StubsMap only adds symbols for later versions, so let's max it.
		$map = new Php8StubsMap(PHP_INT_MAX);
		$files = [];

		foreach (array_merge($map->classes, $map->functions) as $file) {
			$files[] = __DIR__ . '/../../../vendor/phpstan/php-8-stubs/' . $file;
		}

		return self::scrapeSymbolsFromStubs($files);
	}

	/** @return array<string, true> */
	private static function scrapeInputSymbolsFromPhpStormStubs(): array
	{
		$files = [];

		foreach (PhpStormStubsMap::CLASSES as $file) {
			$files[] = PhpStormStubsMap::DIR . '/' . $file;
		}

		return self::scrapeSymbolsFromStubs($files);
	}

	/** @return array<string, true> */
	private static function scrapeInputSymbolsFromReflection(): array
	{
		$result = [];

		foreach (get_defined_functions()['internal'] as $function) {
			$result['FUNCTION ' . $function] = true;
		}

		foreach (get_declared_classes() as $class) {
			$reflection = new ReflectionClass($class);

			if ($reflection->getFileName() !== false) {
				continue;
			}

			$className = $reflection->getName();
			$result['CLASS ' . $className] = true;

			foreach ($reflection->getMethods() as $method) {
				$result['METHOD ' . $className . '::' . $method->getName()] = true;
			}

			foreach ($reflection->getProperties() as $property) {
				$result['PROPERTY ' . $className . '::$' . $property->getName()] = true;
			}
		}

		return $result;
	}

	/**
	 * @param array<string> $stubFiles
	 * @return array<string, true>
	 */
	private static function scrapeSymbolsFromStubs(array $stubFiles): array
	{
		$parser = self::getContainer()->getService('defaultAnalysisParser');
		self::assertInstanceOf(Parser::class, $parser);
		$visitor = new class () extends NodeVisitorAbstract {

			/** @var array<string, true> */
			public array $symbols = [];

			private Node\Stmt\ClassLike $classLike;

			#[Override]
			public function enterNode(Node $node)
			{
				if ($node instanceof Node\Stmt\ClassLike && $node->namespacedName !== null) {
					$this->symbols['CLASS ' . $node->namespacedName->toString()] = true;
					$this->classLike = $node;
				}

				if ($node instanceof Node\Stmt\ClassMethod && isset($this->classLike->namespacedName)) {
					$this->symbols['METHOD ' . $this->classLike->namespacedName->toString() . '::' . $node->name->name] = true;
				}

				if ($node instanceof Node\Stmt\PropertyProperty && isset($this->classLike->namespacedName)) {
					$this->symbols['PROPERTY ' . $this->classLike->namespacedName->toString() . '::$' . $node->name->toString()] = true;
				}

				if ($node instanceof Node\Stmt\Function_) {
					$this->symbols['FUNCTION ' . $node->name->name] = true;
				}

				return null;
			}

			#[Override]
			public function leaveNode(Node $node)
			{
				if ($node instanceof Node\Stmt\ClassLike) {
					unset($this->classLike);
				}

				return null;
			}

		};
		$traverser = new NodeTraverser();
		$traverser->addVisitor($visitor);

		foreach ($stubFiles as $file) {
			$ast = $parser->parseFile($file);
			$traverser->traverse($ast);
		}

		return $visitor->symbols;
	}

}
