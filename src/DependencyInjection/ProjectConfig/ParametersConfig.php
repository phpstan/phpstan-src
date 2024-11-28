<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection\ProjectConfig;

final class ParametersConfig implements Arrayable
{

	use ArrayableTrait;

	public function __construct(
		/** @var list<string>|Undefined */
		private readonly array|Undefined $bootstrapFiles = Undefined::Undefined,
		private readonly ExcludePathsConfig|Undefined $excludePaths = Undefined::Undefined,
		private readonly int|string|Undefined $level = Undefined::Undefined,
		/** @var list<string>|Undefined */
		private readonly array|Undefined $paths = Undefined::Undefined,
		private readonly ExceptionsConfig|Undefined $exceptions = Undefined::Undefined,
		private readonly FeatureTogglesConfig|Undefined $featureToggles = Undefined::Undefined,
		/** @var list<string>|Undefined */
		private readonly array|Undefined $fileExtensions = Undefined::Undefined,
		private readonly bool|Undefined $checkAdvancedIsset = Undefined::Undefined,
		private readonly bool|Undefined $reportAlwaysTrueInLastCondition = Undefined::Undefined,
		private readonly bool|Undefined $checkClassCaseSensitivity = Undefined::Undefined,
		private readonly bool|Undefined $checkExplicitMixed = Undefined::Undefined,
		private readonly bool|Undefined $checkImplicitMixed = Undefined::Undefined,
		private readonly bool|Undefined $checkFunctionArgumentTypes = Undefined::Undefined,
		private readonly bool|Undefined $checkFunctionNameCase = Undefined::Undefined,
		private readonly bool|Undefined $checkInternalClassCaseSensitivity = Undefined::Undefined,
		private readonly bool|Undefined $checkMissingCallableSignature = Undefined::Undefined,
		private readonly bool|Undefined $checkMissingVarTagTypehint = Undefined::Undefined,
		private readonly bool|Undefined $checkArgumentsPassedByReference = Undefined::Undefined,
		private readonly bool|Undefined $checkMaybeUndefinedVariables = Undefined::Undefined,
		private readonly bool|Undefined $checkNullables = Undefined::Undefined,
		private readonly bool|Undefined $checkThisOnly = Undefined::Undefined,
		private readonly bool|Undefined $checkUnionTypes = Undefined::Undefined,
		private readonly bool|Undefined $checkBenevolentUnionTypes = Undefined::Undefined,
		private readonly bool|Undefined $checkExplicitMixedMissingReturn = Undefined::Undefined,
		private readonly bool|Undefined $checkPhpDocMissingReturn = Undefined::Undefined,
		private readonly bool|Undefined $checkPhpDocMethodSignatures = Undefined::Undefined,
		private readonly bool|Undefined $checkExtraArguments = Undefined::Undefined,
		private readonly bool|Undefined $checkMissingTypehints = Undefined::Undefined,
		private readonly bool|Undefined $checkTooWideReturnTypesInProtectedAndPublicMethods = Undefined::Undefined,
		private readonly bool|Undefined $checkUninitializedProperties = Undefined::Undefined,
		private readonly bool|Undefined $checkDynamicProperties = Undefined::Undefined,
		private readonly bool|Undefined $deprecationRulesInstalled = Undefined::Undefined,
		private readonly bool|Undefined $inferPrivatePropertyTypeFromConstructor = Undefined::Undefined,
		private readonly TipsConfig|Undefined $tips = Undefined::Undefined,
		private readonly bool|Undefined $tipsOfTheDay = Undefined::Undefined,
		private readonly bool|Undefined $reportMaybes = Undefined::Undefined,
		private readonly bool|Undefined $reportMaybesInMethodSignatures = Undefined::Undefined,
		private readonly bool|Undefined $reportMaybesInPropertyPhpDocTypes = Undefined::Undefined,
		private readonly bool|Undefined $reportStaticMethodSignatures = Undefined::Undefined,
		private readonly bool|Undefined $reportWrongPhpDocTypeInVarTag = Undefined::Undefined,
		private readonly bool|Undefined $reportAnyTypeWideningInVarTag = Undefined::Undefined,
		private readonly bool|Undefined $reportPossiblyNonexistentGeneralArrayOffset = Undefined::Undefined,
		private readonly bool|Undefined $reportPossiblyNonexistentConstantArrayOffset = Undefined::Undefined,
		private readonly bool|Undefined $checkMissingOverrideMethodAttribute = Undefined::Undefined,
		private readonly ParallelConfig|Undefined $parallelConfig = Undefined::Undefined,
		private readonly int|PhpVersionConfig|Undefined|null $phpVersion = Undefined::Undefined,
		private readonly bool|Undefined $polluteScopeWithLoopInitialAssignments = Undefined::Undefined,
		private readonly bool|Undefined $polluteScopeWithAlwaysIterableForeach = Undefined::Undefined,
		private readonly bool|Undefined $polluteScopeWithBlock = Undefined::Undefined,
		/** @var list<string>|Undefined */
		private readonly array|Undefined $propertyAlwaysWrittenTags = Undefined::Undefined,
		/** @var list<string>|Undefined */
		private readonly array|Undefined $propertyAlwaysReadTags = Undefined::Undefined,
		/** @var list<string>|Undefined */
		private readonly array|Undefined $additionalConstructors = Undefined::Undefined,
		private readonly bool|Undefined $treatPhpDocTypesAsCertain = Undefined::Undefined,
		private readonly bool|Undefined $usePathConstantsAsConstantString = Undefined::Undefined,
		private readonly bool|Undefined $rememberPossiblyImpureFunctionValues = Undefined::Undefined,
		private readonly bool|Undefined $reportMagicMethods = Undefined::Undefined,
		private readonly bool|Undefined $reportMagicProperties = Undefined::Undefined,
		/** @var list<string|IgnoreErrorsConfig> */
		private readonly array|Undefined $ignoreErrors = Undefined::Undefined,
		private readonly int|Undefined $internalErrorsCountLimit = Undefined::Undefined,
		private readonly CacheConfig|Undefined $cache = Undefined::Undefined,
		private readonly bool|Undefined $reportUnmatchedIgnoredErrors = Undefined::Undefined,
		/** @var array<string, string>|Undefined */
		private readonly array|Undefined $typeAliases = Undefined::Undefined,
		/** @var list<string>|Undefined */
		private readonly array|Undefined $universalObjectCratesClasses = Undefined::Undefined,
		/** @var list<string>|Undefined */
		private readonly array|Undefined $stubFiles = Undefined::Undefined,
		/** @var array<string, list<string>>|Undefined */
		private readonly array|Undefined $earlyTerminatingMethodCalls = Undefined::Undefined,
		/** @var list<string>|Undefined */
		private readonly array|Undefined $earlyTerminatingFunctionCalls = Undefined::Undefined,
		private readonly string|Undefined $resultCachePath = Undefined::Undefined,
		private readonly bool|Undefined $resultCacheChecksProjectExtensionFilesDependencies = Undefined::Undefined,
		/** @var list<string>|Undefined */
		private readonly array|Undefined $dynamicConstantNames = Undefined::Undefined,
		private readonly bool|Undefined|null $customRulesetUsed = Undefined::Undefined,
		private readonly string|Undefined $rootDir = Undefined::Undefined,
		private readonly string|Undefined $tmpDir = Undefined::Undefined,
		private readonly string|Undefined $currentWorkingDirectory = Undefined::Undefined,
		private readonly bool|Undefined $cliArgumentsVariablesRegistered = Undefined::Undefined,
		/** @var list<string>|Undefined */
		private readonly array|Undefined $mixinExcludeClasses = Undefined::Undefined,
		/** @var list<string>|Undefined */
		private readonly array|Undefined $scanFiles = Undefined::Undefined,
		/** @var list<string>|Undefined */
		private readonly array|Undefined $scanDirectories = Undefined::Undefined,
		private readonly string|Undefined|null $editorUrl = Undefined::Undefined,
		private readonly string|Undefined|null $editorUrlTitle = Undefined::Undefined,
		private readonly string|Undefined|null $errorFormat = Undefined::Undefined,
		private readonly ProConfig|Undefined $pro = Undefined::Undefined,
		/** @var array<int|string, string>|Undefined */
		private readonly array|Undefined $env = Undefined::Undefined,
		private readonly string|Undefined $sysGetTempDir = Undefined::Undefined,
		private readonly bool|Undefined $sourceLocatorPlaygroundMode = Undefined::Undefined,
		/** @var array<array-key, mixed> */
		// phpcs:ignore Consistence.NamingConventions.ValidVariableName.NotCamelCaps
		private readonly array|Undefined $_extra = Undefined::Undefined,
	)
	{
	}

}
