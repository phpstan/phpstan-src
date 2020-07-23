<?php declare(strict_types = 1);

namespace PHPStan\PhpDoc;

use PHPStan\Analyser\FileAnalyser;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Broker\AnonymousClassNameHelper;
use PHPStan\Broker\Broker;
use PHPStan\DependencyInjection\Container;
use PHPStan\DependencyInjection\DerivativeContainerFactory;
use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\PhpDocParser;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\ClassCaseSensitivityCheck;
use PHPStan\Rules\Classes\ExistingClassesInClassImplementsRule;
use PHPStan\Rules\Classes\ExistingClassesInInterfaceExtendsRule;
use PHPStan\Rules\Classes\ExistingClassInClassExtendsRule;
use PHPStan\Rules\Classes\ExistingClassInTraitUseRule;
use PHPStan\Rules\FunctionDefinitionCheck;
use PHPStan\Rules\Functions\MissingFunctionParameterTypehintRule;
use PHPStan\Rules\Functions\MissingFunctionReturnTypehintRule;
use PHPStan\Rules\Generics\ClassAncestorsRule;
use PHPStan\Rules\Generics\ClassTemplateTypeRule;
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
use PHPStan\Rules\Methods\MissingMethodParameterTypehintRule;
use PHPStan\Rules\Methods\MissingMethodReturnTypehintRule;
use PHPStan\Rules\MissingTypehintCheck;
use PHPStan\Rules\PhpDoc\IncompatiblePhpDocTypeRule;
use PHPStan\Rules\PhpDoc\IncompatiblePropertyPhpDocTypeRule;
use PHPStan\Rules\PhpDoc\InvalidPhpDocTagValueRule;
use PHPStan\Rules\PhpDoc\InvalidThrowsPhpDocValueRule;
use PHPStan\Rules\Properties\ExistingClassesInPropertiesRule;
use PHPStan\Rules\Properties\MissingPropertyTypehintRule;
use PHPStan\Rules\Registry;
use PHPStan\Type\FileTypeMapper;

class StubValidator
{

	/** @var string[] */
	private array $stubFiles;

	private \PHPStan\DependencyInjection\DerivativeContainerFactory $derivativeContainerFactory;

	/**
	 * @param string[] $stubFiles
	 */
	public function __construct(
		array $stubFiles,
		DerivativeContainerFactory $derivativeContainerFactory
	)
	{
		$this->stubFiles = $stubFiles;
		$this->derivativeContainerFactory = $derivativeContainerFactory;
	}

	/**
	 * @return \PHPStan\Analyser\Error[]
	 */
	public function validate(): array
	{
		$originalBroker = Broker::getInstance();
		$container = $this->derivativeContainerFactory->create([
			__DIR__ . '/../../conf/config.stubValidator.neon',
		]);

		$ruleRegistry = $this->getRuleRegistry($container);

		/** @var FileAnalyser $fileAnalyser */
		$fileAnalyser = $container->getByType(FileAnalyser::class);

		/** @var NodeScopeResolver $nodeScopeResolver */
		$nodeScopeResolver = $container->getByType(NodeScopeResolver::class);
		$nodeScopeResolver->setAnalysedFiles($this->stubFiles);

		$analysedFiles = array_fill_keys($this->stubFiles, true);

		$errors = [];
		foreach ($this->stubFiles as $stubFile) {
			$tmpErrors = $fileAnalyser->analyseFile(
				$stubFile,
				$analysedFiles,
				$ruleRegistry,
				static function (): void {
				}
			)->getErrors();
			foreach ($tmpErrors as $tmpError) {
				$errors[] = $tmpError->withoutTip();
			}
		}

		Broker::registerInstance($originalBroker);

		return $errors;
	}

	private function getRuleRegistry(Container $container): Registry
	{
		$fileTypeMapper = $container->getByType(FileTypeMapper::class);
		$genericObjectTypeCheck = $container->getByType(GenericObjectTypeCheck::class);
		$genericAncestorsCheck = $container->getByType(GenericAncestorsCheck::class);
		$templateTypeCheck = $container->getByType(TemplateTypeCheck::class);
		$varianceCheck = $container->getByType(VarianceCheck::class);
		$reflectionProvider = $container->getByType(ReflectionProvider::class);
		$classCaseSensitivityCheck = $container->getByType(ClassCaseSensitivityCheck::class);
		$functionDefinitionCheck = $container->getByType(FunctionDefinitionCheck::class);
		$missingTypehintCheck = $container->getByType(MissingTypehintCheck::class);
		$anonymousClassNameHelper = $container->getByType(AnonymousClassNameHelper::class);

		return new Registry([
			// level 0
			new ExistingClassesInClassImplementsRule($classCaseSensitivityCheck, $reflectionProvider),
			new ExistingClassesInInterfaceExtendsRule($classCaseSensitivityCheck, $reflectionProvider),
			new ExistingClassInClassExtendsRule($classCaseSensitivityCheck, $reflectionProvider),
			new ExistingClassInTraitUseRule($classCaseSensitivityCheck, $reflectionProvider),
			new ExistingClassesInTypehintsRule($functionDefinitionCheck),
			new \PHPStan\Rules\Functions\ExistingClassesInTypehintsRule($functionDefinitionCheck),
			new ExistingClassesInPropertiesRule($reflectionProvider, $classCaseSensitivityCheck, true, false),

			// level 2
			new ClassAncestorsRule($fileTypeMapper, $genericAncestorsCheck),
			new ClassTemplateTypeRule($fileTypeMapper, $templateTypeCheck, $anonymousClassNameHelper),
			new FunctionTemplateTypeRule($fileTypeMapper, $templateTypeCheck),
			new FunctionSignatureVarianceRule($varianceCheck),
			new InterfaceAncestorsRule($fileTypeMapper, $genericAncestorsCheck),
			new InterfaceTemplateTypeRule($fileTypeMapper, $templateTypeCheck),
			new MethodTemplateTypeRule($fileTypeMapper, $templateTypeCheck),
			new MethodSignatureVarianceRule($varianceCheck),
			new TraitTemplateTypeRule($fileTypeMapper, $templateTypeCheck),
			new IncompatiblePhpDocTypeRule(
				$fileTypeMapper,
				$genericObjectTypeCheck
			),
			new IncompatiblePropertyPhpDocTypeRule($genericObjectTypeCheck),
			new InvalidPhpDocTagValueRule(
				$container->getByType(Lexer::class),
				$container->getByType(PhpDocParser::class)
			),
			new InvalidThrowsPhpDocValueRule($fileTypeMapper),

			// level 6
			new MissingFunctionParameterTypehintRule($missingTypehintCheck),
			new MissingFunctionReturnTypehintRule($missingTypehintCheck),
			new MissingMethodParameterTypehintRule($missingTypehintCheck),
			new MissingMethodReturnTypehintRule($missingTypehintCheck),
			new MissingPropertyTypehintRule($missingTypehintCheck),
		]);
	}

}
