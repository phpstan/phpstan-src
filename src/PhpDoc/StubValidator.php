<?php declare(strict_types = 1);

namespace PHPStan\PhpDoc;

use PHPStan\Analyser\Error;
use PHPStan\Analyser\FileAnalyser;
use PHPStan\Analyser\InternalError;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Broker\Broker;
use PHPStan\Collectors\Registry as CollectorRegistry;
use PHPStan\DependencyInjection\Container;
use PHPStan\DependencyInjection\DerivativeContainerFactory;
use PHPStan\Php\PhpVersion;
use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\PhpDocParser;
use PHPStan\Reflection\Php\PhpClassReflectionExtension;
use PHPStan\Reflection\PhpVersionStaticAccessor;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Reflection\ReflectionProviderStaticAccessor;
use PHPStan\Rules\Classes\DuplicateClassDeclarationRule;
use PHPStan\Rules\Classes\DuplicateDeclarationRule;
use PHPStan\Rules\Classes\ExistingClassesInClassImplementsRule;
use PHPStan\Rules\Classes\ExistingClassesInInterfaceExtendsRule;
use PHPStan\Rules\Classes\ExistingClassInClassExtendsRule;
use PHPStan\Rules\Classes\ExistingClassInTraitUseRule;
use PHPStan\Rules\Classes\LocalTypeAliasesCheck;
use PHPStan\Rules\Classes\LocalTypeAliasesRule;
use PHPStan\Rules\Classes\LocalTypeTraitAliasesRule;
use PHPStan\Rules\ClassNameCheck;
use PHPStan\Rules\DirectRegistry as DirectRuleRegistry;
use PHPStan\Rules\FunctionDefinitionCheck;
use PHPStan\Rules\Functions\DuplicateFunctionDeclarationRule;
use PHPStan\Rules\Functions\MissingFunctionParameterTypehintRule;
use PHPStan\Rules\Functions\MissingFunctionReturnTypehintRule;
use PHPStan\Rules\Generics\ClassAncestorsRule;
use PHPStan\Rules\Generics\ClassTemplateTypeRule;
use PHPStan\Rules\Generics\CrossCheckInterfacesHelper;
use PHPStan\Rules\Generics\FunctionSignatureVarianceRule;
use PHPStan\Rules\Generics\FunctionTemplateTypeRule;
use PHPStan\Rules\Generics\GenericAncestorsCheck;
use PHPStan\Rules\Generics\GenericObjectTypeCheck;
use PHPStan\Rules\Generics\InterfaceAncestorsRule;
use PHPStan\Rules\Generics\InterfaceTemplateTypeRule;
use PHPStan\Rules\Generics\MethodSignatureVarianceRule;
use PHPStan\Rules\Generics\MethodTemplateTypeRule;
use PHPStan\Rules\Generics\TemplateTypeCheck;
use PHPStan\Rules\Generics\TraitTemplateTypeRule;
use PHPStan\Rules\Generics\VarianceCheck;
use PHPStan\Rules\Methods\ExistingClassesInTypehintsRule;
use PHPStan\Rules\Methods\MethodParameterComparisonHelper;
use PHPStan\Rules\Methods\MethodSignatureRule;
use PHPStan\Rules\Methods\MissingMethodParameterTypehintRule;
use PHPStan\Rules\Methods\MissingMethodReturnTypehintRule;
use PHPStan\Rules\Methods\OverridingMethodRule;
use PHPStan\Rules\MissingTypehintCheck;
use PHPStan\Rules\PhpDoc\GenericCallableRuleHelper;
use PHPStan\Rules\PhpDoc\IncompatiblePhpDocTypeRule;
use PHPStan\Rules\PhpDoc\IncompatiblePropertyPhpDocTypeRule;
use PHPStan\Rules\PhpDoc\InvalidPhpDocTagValueRule;
use PHPStan\Rules\PhpDoc\InvalidPHPStanDocTagRule;
use PHPStan\Rules\PhpDoc\InvalidThrowsPhpDocValueRule;
use PHPStan\Rules\PhpDoc\UnresolvableTypeHelper;
use PHPStan\Rules\Properties\ExistingClassesInPropertiesRule;
use PHPStan\Rules\Properties\MissingPropertyTypehintRule;
use PHPStan\Rules\Registry as RuleRegistry;
use PHPStan\Type\FileTypeMapper;
use PHPStan\Type\ObjectType;
use Throwable;
use function array_fill_keys;
use function count;
use function sprintf;

class StubValidator
{

	public function __construct(
		private DerivativeContainerFactory $derivativeContainerFactory,
		private bool $duplicateStubs,
	)
	{
	}

	/**
	 * @param string[] $stubFiles
	 * @return list<Error>
	 */
	public function validate(array $stubFiles, bool $debug): array
	{
		if (count($stubFiles) === 0) {
			return [];
		}

		$originalBroker = Broker::getInstance();
		$originalReflectionProvider = ReflectionProviderStaticAccessor::getInstance();
		$originalPhpVerison = PhpVersionStaticAccessor::getInstance();
		$container = $this->derivativeContainerFactory->create([
			__DIR__ . '/../../conf/config.stubValidator.neon',
		]);

		$ruleRegistry = $this->getRuleRegistry($container);
		$collectorRegistry = $this->getCollectorRegistry($container);

		$fileAnalyser = $container->getByType(FileAnalyser::class);

		$nodeScopeResolver = $container->getByType(NodeScopeResolver::class);
		$nodeScopeResolver->setAnalysedFiles($stubFiles);

		$pathRoutingParser = $container->getService('pathRoutingParser');
		$pathRoutingParser->setAnalysedFiles($stubFiles);

		$analysedFiles = array_fill_keys($stubFiles, true);

		$errors = [];
		foreach ($stubFiles as $stubFile) {
			try {
				$tmpErrors = $fileAnalyser->analyseFile(
					$stubFile,
					$analysedFiles,
					$ruleRegistry,
					$collectorRegistry,
					static function (): void {
					},
				)->getErrors();
				foreach ($tmpErrors as $tmpError) {
					$errors[] = $tmpError->withoutTip()->doNotIgnore();
				}
			} catch (Throwable $e) {
				if ($debug) {
					throw $e;
				}

				$internalErrorMessage = sprintf('Internal error: %s', $e->getMessage());
				$errors[] = (new Error($internalErrorMessage, $stubFile, null, $e))
					->withIdentifier('phpstan.internal')
					->withMetadata([
						InternalError::STACK_TRACE_METADATA_KEY => InternalError::prepareTrace($e),
					]);
			}
		}

		Broker::registerInstance($originalBroker);
		ReflectionProviderStaticAccessor::registerInstance($originalReflectionProvider);
		PhpVersionStaticAccessor::registerInstance($originalPhpVerison);
		ObjectType::resetCaches();

		return $errors;
	}

	private function getRuleRegistry(Container $container): RuleRegistry
	{
		$fileTypeMapper = $container->getByType(FileTypeMapper::class);
		$genericObjectTypeCheck = $container->getByType(GenericObjectTypeCheck::class);
		$genericAncestorsCheck = $container->getByType(GenericAncestorsCheck::class);
		$templateTypeCheck = $container->getByType(TemplateTypeCheck::class);
		$varianceCheck = $container->getByType(VarianceCheck::class);
		$reflectionProvider = $container->getByType(ReflectionProvider::class);
		$classNameCheck = $container->getByType(ClassNameCheck::class);
		$functionDefinitionCheck = $container->getByType(FunctionDefinitionCheck::class);
		$missingTypehintCheck = $container->getByType(MissingTypehintCheck::class);
		$unresolvableTypeHelper = $container->getByType(UnresolvableTypeHelper::class);
		$crossCheckInterfacesHelper = $container->getByType(CrossCheckInterfacesHelper::class);
		$phpVersion = $container->getByType(PhpVersion::class);
		$localTypeAliasesCheck = $container->getByType(LocalTypeAliasesCheck::class);
		$phpClassReflectionExtension = $container->getByType(PhpClassReflectionExtension::class);
		$genericCallableRuleHelper = $container->getByType(GenericCallableRuleHelper::class);

		$rules = [
			// level 0
			new ExistingClassesInClassImplementsRule($classNameCheck, $reflectionProvider),
			new ExistingClassesInInterfaceExtendsRule($classNameCheck, $reflectionProvider),
			new ExistingClassInClassExtendsRule($classNameCheck, $reflectionProvider),
			new ExistingClassInTraitUseRule($classNameCheck, $reflectionProvider),
			new ExistingClassesInTypehintsRule($functionDefinitionCheck),
			new \PHPStan\Rules\Functions\ExistingClassesInTypehintsRule($functionDefinitionCheck),
			new ExistingClassesInPropertiesRule($reflectionProvider, $classNameCheck, $unresolvableTypeHelper, $phpVersion, true, false),
			new OverridingMethodRule($phpVersion, new MethodSignatureRule($phpClassReflectionExtension, true, true, $container->getParameter('featureToggles')['abstractTraitMethod']), true, new MethodParameterComparisonHelper($phpVersion, $container->getParameter('featureToggles')['genericPrototypeMessage']), $phpClassReflectionExtension, $container->getParameter('featureToggles')['genericPrototypeMessage'], $container->getParameter('featureToggles')['finalByPhpDoc'], $container->getParameter('checkMissingOverrideMethodAttribute')),
			new DuplicateDeclarationRule(),
			new LocalTypeAliasesRule($localTypeAliasesCheck),
			new LocalTypeTraitAliasesRule($localTypeAliasesCheck, $reflectionProvider),

			// level 2
			new ClassAncestorsRule($genericAncestorsCheck, $crossCheckInterfacesHelper),
			new ClassTemplateTypeRule($templateTypeCheck),
			new FunctionTemplateTypeRule($fileTypeMapper, $templateTypeCheck),
			new FunctionSignatureVarianceRule($varianceCheck),
			new InterfaceAncestorsRule($genericAncestorsCheck, $crossCheckInterfacesHelper),
			new InterfaceTemplateTypeRule($templateTypeCheck),
			new MethodTemplateTypeRule($fileTypeMapper, $templateTypeCheck),
			new MethodSignatureVarianceRule($varianceCheck),
			new TraitTemplateTypeRule($fileTypeMapper, $templateTypeCheck),
			new IncompatiblePhpDocTypeRule($fileTypeMapper, $genericObjectTypeCheck, $unresolvableTypeHelper, $genericCallableRuleHelper),
			new IncompatiblePropertyPhpDocTypeRule($genericObjectTypeCheck, $unresolvableTypeHelper, $genericCallableRuleHelper),
			new InvalidPhpDocTagValueRule(
				$container->getByType(Lexer::class),
				$container->getByType(PhpDocParser::class),
				$container->getParameter('featureToggles')['allInvalidPhpDocs'],
				$container->getParameter('featureToggles')['invalidPhpDocTagLine'],
			),
			new InvalidThrowsPhpDocValueRule($fileTypeMapper),

			// level 6
			new MissingFunctionParameterTypehintRule($missingTypehintCheck, $container->getParameter('featureToggles')['paramOutType']),
			new MissingFunctionReturnTypehintRule($missingTypehintCheck),
			new MissingMethodParameterTypehintRule($missingTypehintCheck, $container->getParameter('featureToggles')['paramOutType']),
			new MissingMethodReturnTypehintRule($missingTypehintCheck),
			new MissingPropertyTypehintRule($missingTypehintCheck),
		];

		if ($this->duplicateStubs) {
			$reflector = $container->getService('stubReflector');
			$relativePathHelper = $container->getService('simpleRelativePathHelper');
			$rules[] = new DuplicateClassDeclarationRule($reflector, $relativePathHelper);
			$rules[] = new DuplicateFunctionDeclarationRule($reflector, $relativePathHelper);
		}

		if ((bool) $container->getParameter('featureToggles')['allInvalidPhpDocs']) {
			$rules[] = new InvalidPHPStanDocTagRule(
				$container->getByType(Lexer::class),
				$container->getByType(PhpDocParser::class),
				true,
			);
		}

		return new DirectRuleRegistry($rules);
	}

	private function getCollectorRegistry(Container $container): CollectorRegistry
	{
		return new CollectorRegistry([]);
	}

}
