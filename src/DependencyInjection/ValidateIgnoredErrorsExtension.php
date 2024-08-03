<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection;

use Hoa\Compiler\Llk\Llk;
use Hoa\File\Read;
use Nette\DI\CompilerExtension;
use Nette\Utils\RegexpException;
use Nette\Utils\Strings;
use PHPStan\Analyser\ConstantResolver;
use PHPStan\Analyser\NameScope;
use PHPStan\Command\IgnoredRegexValidator;
use PHPStan\DependencyInjection\Type\OperatorTypeSpecifyingExtensionRegistryProvider;
use PHPStan\File\FileExcluder;
use PHPStan\Php\PhpVersion;
use PHPStan\PhpDoc\DirectTypeNodeResolverExtensionRegistryProvider;
use PHPStan\PhpDoc\TypeNodeResolver;
use PHPStan\PhpDoc\TypeNodeResolverExtensionRegistry;
use PHPStan\PhpDoc\TypeStringResolver;
use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\ConstExprParser;
use PHPStan\PhpDocParser\Parser\TypeParser;
use PHPStan\Reflection\InitializerExprTypeResolver;
use PHPStan\Reflection\PhpVersionStaticAccessor;
use PHPStan\Reflection\ReflectionProvider\DirectReflectionProviderProvider;
use PHPStan\Reflection\ReflectionProvider\DummyReflectionProvider;
use PHPStan\Reflection\ReflectionProviderStaticAccessor;
use PHPStan\Type\Constant\OversizedArrayBuilder;
use PHPStan\Type\DirectTypeAliasResolverProvider;
use PHPStan\Type\OperatorTypeSpecifyingExtensionRegistry;
use PHPStan\Type\Type;
use PHPStan\Type\TypeAliasResolver;
use function array_keys;
use function array_map;
use function count;
use function implode;
use function is_array;
use function is_dir;
use function is_file;
use function sprintf;
use const PHP_VERSION_ID;

final class ValidateIgnoredErrorsExtension extends CompilerExtension
{

	/**
	 * @throws InvalidIgnoredErrorPatternsException
	 */
	public function loadConfiguration(): void
	{
		$builder = $this->getContainerBuilder();
		if (!$builder->parameters['__validate']) {
			return;
		}

		$ignoreErrors = $builder->parameters['ignoreErrors'];
		if (count($ignoreErrors) === 0) {
			return;
		}

		$noImplicitWildcard = $builder->parameters['featureToggles']['noImplicitWildcard'];

		/** @throws void */
		$parser = Llk::load(new Read(__DIR__ . '/../../resources/RegexGrammar.pp'));
		$reflectionProvider = new DummyReflectionProvider();
		$reflectionProviderProvider = new DirectReflectionProviderProvider($reflectionProvider);
		ReflectionProviderStaticAccessor::registerInstance($reflectionProvider);
		PhpVersionStaticAccessor::registerInstance(new PhpVersion(PHP_VERSION_ID));
		$constantResolver = new ConstantResolver($reflectionProviderProvider, []);
		$ignoredRegexValidator = new IgnoredRegexValidator(
			$parser,
			new TypeStringResolver(
				new Lexer(),
				new TypeParser(new ConstExprParser($builder->parameters['featureToggles']['unescapeStrings'])),
				new TypeNodeResolver(
					new DirectTypeNodeResolverExtensionRegistryProvider(
						new class implements TypeNodeResolverExtensionRegistry {

							public function getExtensions(): array
							{
								return [];
							}

						},
					),
					$reflectionProviderProvider,
					new DirectTypeAliasResolverProvider(new class implements TypeAliasResolver {

						public function hasTypeAlias(string $aliasName, ?string $classNameScope): bool
						{
							return false;
						}

						public function resolveTypeAlias(string $aliasName, NameScope $nameScope): ?Type
						{
							return null;
						}

					}),
					$constantResolver,
					new InitializerExprTypeResolver($constantResolver, $reflectionProviderProvider, new PhpVersion(PHP_VERSION_ID), new class implements OperatorTypeSpecifyingExtensionRegistryProvider {

						public function getRegistry(): OperatorTypeSpecifyingExtensionRegistry
						{
							return new OperatorTypeSpecifyingExtensionRegistry(null, []);
						}

					}, new OversizedArrayBuilder()),
				),
			),
		);

		$errors = [];
		foreach ($ignoreErrors as $ignoreError) {
			if (is_array($ignoreError)) {
				if (isset($ignoreError['count'])) {
					continue; // ignoreError coming from baseline will be correct
				}
				if (isset($ignoreError['messages'])) {
					$ignoreMessages = $ignoreError['messages'];
				} elseif (isset($ignoreError['message'])) {
					$ignoreMessages = [$ignoreError['message']];
				} else {
					continue;
				}
			} else {
				$ignoreMessages = [$ignoreError];
			}

			foreach ($ignoreMessages as $ignoreMessage) {
				$error = $this->validateMessage($ignoredRegexValidator, $ignoreMessage);
				if ($error === null) {
					continue;
				}
				$errors[] = $error;
			}
		}

		if ($noImplicitWildcard) {
			foreach ($ignoreErrors as $ignoreError) {
				if (!is_array($ignoreError)) {
					continue;
				}

				if (isset($ignoreError['path'])) {
					$ignorePaths = [$ignoreError['path']];
				} elseif (isset($ignoreError['paths'])) {
					$ignorePaths = $ignoreError['paths'];
				} else {
					continue;
				}

				foreach ($ignorePaths as $ignorePath) {
					if (FileExcluder::isAbsolutePath($ignorePath)) {
						if (is_dir($ignorePath)) {
							continue;
						}
						if (is_file($ignorePath)) {
							continue;
						}
					}
					if (FileExcluder::isFnmatchPattern($ignorePath)) {
						continue;
					}

					$errors[] = sprintf('Path %s is neither a directory, nor a file path, nor a fnmatch pattern.', $ignorePath);
				}
			}
		}

		if (count($errors) === 0) {
			return;
		}

		throw new InvalidIgnoredErrorPatternsException($errors);
	}

	private function validateMessage(IgnoredRegexValidator $ignoredRegexValidator, string $ignoreMessage): ?string
	{
		try {
			Strings::match('', $ignoreMessage);
			$validationResult = $ignoredRegexValidator->validate($ignoreMessage);
			$ignoredTypes = $validationResult->getIgnoredTypes();
			if (count($ignoredTypes) > 0) {
				return $this->createIgnoredTypesError($ignoreMessage, $ignoredTypes);
			}

			if ($validationResult->hasAnchorsInTheMiddle()) {
				return $this->createAnchorInTheMiddleError($ignoreMessage);
			}

			if ($validationResult->areAllErrorsIgnored()) {
				return sprintf("Ignored error %s has an unescaped '%s' which leads to ignoring all errors. Use '%s' instead.", $ignoreMessage, $validationResult->getWrongSequence(), $validationResult->getEscapedWrongSequence());
			}
		} catch (RegexpException $e) {
			return $e->getMessage();
		}
		return null;
	}

	/**
	 * @param array<string, string> $ignoredTypes
	 */
	private function createIgnoredTypesError(string $regex, array $ignoredTypes): string
	{
		return sprintf(
			"Ignored error %s has an unescaped '|' which leads to ignoring more errors than intended. Use '\\|' instead.\n%s",
			$regex,
			sprintf(
				"It ignores all errors containing the following types:\n%s",
				implode("\n", array_map(static fn (string $typeDescription): string => sprintf('* %s', $typeDescription), array_keys($ignoredTypes))),
			),
		);
	}

	private function createAnchorInTheMiddleError(string $regex): string
	{
		return sprintf("Ignored error %s has an unescaped anchor '$' in the middle. This leads to unintended behavior. Use '\\$' instead.", $regex);
	}

}
