<?php declare(strict_types = 1);

namespace PHPStan\Type;

use Closure;
use PhpParser\Comment\Doc;
use PhpParser\Node;
use PHPStan\Analyser\EditorModeFileHelper;
use PHPStan\Analyser\IntermediaryNameScope;
use PHPStan\Analyser\NameScope;
use PHPStan\BetterReflection\Util\GetLastDocComment;
use PHPStan\Broker\AnonymousClassNameHelper;
use PHPStan\Cache\Cache;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\File\FileContentHasher;
use PHPStan\File\FileHelper;
use PHPStan\Internal\ComposerHelper;
use PHPStan\Parser\Parser;
use PHPStan\PhpDoc\NameScopeAlreadyBeingCreatedException;
use PHPStan\PhpDoc\PhpDocNodeResolver;
use PHPStan\PhpDoc\PhpDocStringResolver;
use PHPStan\PhpDoc\ResolvedPhpDocBlock;
use PHPStan\PhpDoc\Tag\TemplateTag;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTagNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\TemplateTagValueNode;
use PHPStan\Reflection\ReflectionProvider\ReflectionProviderProvider;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\Generic\TemplateTypeFactory;
use PHPStan\Type\Generic\TemplateTypeHelper;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\Generic\TemplateTypeVariance;
use PHPStan\Type\Generic\TemplateTypeVarianceMap;
use function array_key_exists;
use function array_key_first;
use function array_keys;
use function array_last;
use function array_map;
use function array_merge;
use function array_pop;
use function array_reverse;
use function count;
use function in_array;
use function is_array;
use function is_file;
use function ltrim;
use function md5;
use function sprintf;
use function str_contains;
use function str_starts_with;
use function strtolower;

#[AutowiredService]
final class FileTypeMapper
{

	private const SKIP_NODE = 1;
	private const POP_TYPE_MAP_STACK = 2;

	/** @var array<string, array{array<string, IntermediaryNameScope>}> */
	private array $memoryCache = [];

	private int $memoryCacheCount = 0;

	/** @var array<string, true> */
	private array $inProcess = [];

	/** @var array<string, NameScope> */
	private array $inProcessNameScopes = [];

	/** @var array<string, ResolvedPhpDocBlock> */
	private array $resolvedPhpDocBlockCache = [];

	private int $resolvedPhpDocBlockCacheCount = 0;

	public function __construct(
		private ReflectionProviderProvider $reflectionProviderProvider,
		#[AutowiredParameter(ref: '@defaultAnalysisParser')]
		private Parser $phpParser,
		private PhpDocStringResolver $phpDocStringResolver,
		private PhpDocNodeResolver $phpDocNodeResolver,
		private AnonymousClassNameHelper $anonymousClassNameHelper,
		private FileHelper $fileHelper,
		private EditorModeFileHelper $editorModeFileHelper,
		private Cache $cache,
		private FileContentHasher $fileContentHasher,
		#[AutowiredParameter(ref: '%cache.resolvedPhpDocBlockCacheCountMax%')]
		private int $resolvedPhpDocBlockCacheCountMax,
		#[AutowiredParameter(ref: '%cache.nameScopeMapMemoryCacheCountMax%')]
		private int $nameScopeMapMemoryCacheCountMax,
	)
	{
	}

	/** @api */
	public function getResolvedPhpDoc(
		?string $fileName,
		?string $className,
		?string $traitName,
		?string $functionName,
		?string $docComment,
	): ResolvedPhpDocBlock
	{
		if ($className === null && $traitName !== null) {
			throw new ShouldNotHappenException();
		}

		if (in_array($docComment, [null, ''], true)) {
			return ResolvedPhpDocBlock::createEmpty();
		}

		if ($fileName !== null) {
			$fileName = $this->editorModeFileHelper->getAnalysedFile($this->fileHelper->normalizePath($fileName));
		}

		$nameScopeKey = $this->getNameScopeKey($fileName, $className, $traitName, $functionName);
		$phpDocKey = $this->getPhpDocKey($nameScopeKey, $docComment);
		if (isset($this->resolvedPhpDocBlockCache[$phpDocKey])) {
			return $this->resolvedPhpDocBlockCache[$phpDocKey];
		}

		while ($this->resolvedPhpDocBlockCacheCount >= $this->resolvedPhpDocBlockCacheCountMax) {
			$oldestKey = array_key_first($this->resolvedPhpDocBlockCache);
			if ($oldestKey === null) {
				break;
			}
			unset($this->resolvedPhpDocBlockCache[$oldestKey]);
			$this->resolvedPhpDocBlockCacheCount--;
		}

		$this->resolvedPhpDocBlockCacheCount++;

		if ($fileName === null) {
			return $this->resolvedPhpDocBlockCache[$phpDocKey] = $this->createResolvedPhpDocBlock($this->phpDocStringResolver->resolve($docComment), new NameScope(null, []), $docComment, null);
		}

		try {
			$nameScope = $this->getNameScope($fileName, $className, $traitName, $functionName);
		} catch (NameScopeAlreadyBeingCreatedException) {
			return $this->resolvedPhpDocBlockCache[$phpDocKey] = ResolvedPhpDocBlock::createEmpty();
		}

		return $this->resolvedPhpDocBlockCache[$phpDocKey] = $this->createResolvedPhpDocBlock(
			$this->phpDocStringResolver->resolve($docComment),
			$nameScope,
			$docComment,
			$fileName,
		);
	}

	private function createResolvedPhpDocBlock(
		PhpDocNode $phpDocNode,
		NameScope $nameScope,
		string $phpDocString,
		?string $fileName,
	): ResolvedPhpDocBlock
	{
		$docBlockTemplateTypes = [];
		$templateTypeMap = $nameScope->getTemplateTypeMap();
		$templateTags = [];
		$phpDocNodeTemplateTagsByName = [];
		foreach ($phpDocNode->getTags() as $tagNode) {
			$valueNode = $tagNode->value;
			if (!$valueNode instanceof TemplateTagValueNode) {
				continue;
			}

			$phpDocNodeTemplateTagsByName[$valueNode->name] = true;
		}
		foreach ($nameScope->getTemplateTags() as $templateTagName => $templateTag) {
			if (!array_key_exists($templateTagName, $phpDocNodeTemplateTagsByName)) {
				continue;
			}
			$templateTags[$templateTagName] = $templateTag;
			$templateType = $templateTypeMap->getType($templateTagName);
			if ($templateType === null) {
				continue;
			}
			$docBlockTemplateTypes[$templateTagName] = $templateType;
		}

		return ResolvedPhpDocBlock::create(
			$phpDocNode,
			$phpDocString,
			$fileName,
			$nameScope,
			new TemplateTypeMap($docBlockTemplateTypes),
			$templateTags,
			$this->phpDocNodeResolver,
			$this->reflectionProviderProvider->getReflectionProvider(),
		);
	}

	/**
	 * @throws NameScopeAlreadyBeingCreatedException
	 */
	public function getNameScope(
		string $fileName,
		?string $className,
		?string $traitName,
		?string $functionName,
	): NameScope
	{
		$fileName = $this->editorModeFileHelper->getAnalysedFile($fileName);
		$nameScopeKey = $this->getNameScopeKey($fileName, $className, $traitName, $functionName);
		if (isset($this->inProcess[$nameScopeKey])) {
			if (isset($this->inProcessNameScopes[$nameScopeKey])) {
				return $this->inProcessNameScopes[$nameScopeKey];
			}
			throw new NameScopeAlreadyBeingCreatedException();
		}

		[$nameScopeMap] = $this->getNameScopeMap($fileName);
		if (!isset($nameScopeMap[$nameScopeKey])) {
			throw new NameScopeAlreadyBeingCreatedException();
		}

		$intermediaryNameScope = $nameScopeMap[$nameScopeKey];

		$this->inProcess[$nameScopeKey] = true;

		try {
			$parents = [$intermediaryNameScope];
			$i = $intermediaryNameScope;
			while ($i->getParent() !== null) {
				$parents[] = $i->getParent();
				$i = $i->getParent();
			}

			$phpDocTemplateTypes = [];
			$templateTags = [];
			$reflectionProvider = $this->reflectionProviderProvider->getReflectionProvider();
			foreach (array_reverse($parents) as $parent) {
				$nameScope = new NameScope(
					$parent->getNamespace(),
					$parent->getUses(),
					$parent->getClassName(),
					$parent->getFunctionName(),
					new TemplateTypeMap($phpDocTemplateTypes),
					$templateTags,
					$parent->getTypeAliasesMap(),
					$parent->shouldBypassTypeAliases(),
					$parent->getConstUses(),
					$parent->getClassNameForTypeAlias(),
				);
				if ($parent->getTraitData() !== null) {
					[$traitFileName, $traitClassName, $traitName, $lookForTraitName, $traitDocComment] = $parent->getTraitData();
					if (!$reflectionProvider->hasClass($traitName)) {
						continue;
					}
					$traitReflection = $reflectionProvider->getClass($traitName);
					$useTags = $this->getResolvedPhpDoc(
						$traitFileName,
						$traitClassName,
						$lookForTraitName,
						null,
						$traitDocComment,
					)->getUsesTags();
					$useType = null;
					foreach ($useTags as $useTag) {
						$useTagType = $useTag->getType();
						if (!$useTagType instanceof GenericObjectType) {
							continue;
						}

						if ($useTagType->getClassName() !== $traitReflection->getName()) {
							continue;
						}

						$useType = $useTagType;
						break;
					}
					$traitTemplateTypeMap = $traitReflection->getTemplateTypeMap();
					$namesToUnset = [];
					if ($useType === null) {
						foreach ($traitTemplateTypeMap->resolveToBounds()->getTypes() as $name => $templateType) {
							$phpDocTemplateTypes[$name] = $templateType;
							$namesToUnset[] = $name;
						}
					} else {
						$transformedTraitTypeMap = $traitReflection->typeMapFromList($useType->getTypes());
						$nameScopeTemplateTypeMap = $traitTemplateTypeMap->map(
							static fn (string $name, Type $type): Type => TemplateTypeHelper::resolveTemplateTypes($type, $transformedTraitTypeMap, TemplateTypeVarianceMap::createEmpty(), TemplateTypeVariance::createStatic()),
						);
						foreach ($nameScopeTemplateTypeMap->getTypes() as $name => $templateType) {
							$phpDocTemplateTypes[$name] = $templateType;
							$namesToUnset[] = $name;
						}
					}
					$parent = $parent->unsetTemplatePhpDocNodes($namesToUnset);
				}

				$templateTypeScope = $nameScope->getTemplateTypeScope();
				if ($templateTypeScope === null) {
					continue;
				}

				$this->inProcessNameScopes[$nameScopeKey] = $nameScope;

				$templateTags = $this->phpDocNodeResolver->resolveTemplateTags($parent->getTemplatePhpDocNodes(), $nameScope);
				$templateTypeMap = new TemplateTypeMap(array_map(static fn (TemplateTag $tag): Type => TemplateTypeFactory::fromTemplateTag($templateTypeScope, $tag), $templateTags));
				$nameScope = $nameScope->withTemplateTypeMap($templateTypeMap, $templateTags);
				$templateTags = $this->phpDocNodeResolver->resolveTemplateTags($parent->getTemplatePhpDocNodes(), $nameScope);
				$templateTypeMap = new TemplateTypeMap(array_map(static fn (TemplateTag $tag): Type => TemplateTypeFactory::fromTemplateTag($templateTypeScope, $tag), $templateTags));
				$nameScope = $nameScope->withTemplateTypeMap($templateTypeMap, $templateTags);
				$templateTags = $this->phpDocNodeResolver->resolveTemplateTags($parent->getTemplatePhpDocNodes(), $nameScope);
				$templateTypeMap = new TemplateTypeMap(array_map(static fn (TemplateTag $tag): Type => TemplateTypeFactory::fromTemplateTag($templateTypeScope, $tag), $templateTags));
				foreach (array_keys($templateTags) as $name) {
					$templateType = $templateTypeMap->getType($name);
					if ($templateType === null) {
						continue;
					}
					$phpDocTemplateTypes[$name] = $templateType;
				}
			}

			return new NameScope(
				$intermediaryNameScope->getNamespace(),
				$intermediaryNameScope->getUses(),
				$intermediaryNameScope->getClassName(),
				$intermediaryNameScope->getFunctionName(),
				new TemplateTypeMap($phpDocTemplateTypes),
				$templateTags,
				$intermediaryNameScope->getTypeAliasesMap(),
				$intermediaryNameScope->shouldBypassTypeAliases(),
				$intermediaryNameScope->getConstUses(),
				$intermediaryNameScope->getClassNameForTypeAlias(),
			);
		} finally {
			unset($this->inProcess[$nameScopeKey]);
			unset($this->inProcessNameScopes[$nameScopeKey]);
		}
	}

	/**
	 * @return array{array<string, IntermediaryNameScope>}
	 */
	private function getNameScopeMap(string $fileName): array
	{
		if (isset($this->memoryCache[$fileName])) {
			// LRU: move the freshly-accessed entry to the end so eviction drops
			// genuinely cold files, not hot dependencies inserted early on.
			$cachedEntry = $this->memoryCache[$fileName];
			unset($this->memoryCache[$fileName]);
			$this->memoryCache[$fileName] = $cachedEntry;

			return $cachedEntry;
		}

		$cacheKey = sprintf('ftm-%s', $fileName);
		$variableCacheKey = sprintf('v5-%s', ComposerHelper::getPhpDocParserVersion());
		$cached = $this->loadCachedPhpDocNodeMap($cacheKey, $variableCacheKey);
		if ($cached === null) {
			[$nameScopeMap, $files] = $this->createPhpDocNodeMap($fileName, null, null, [], $fileName);
			$filesWithHashes = [];
			foreach ($files as $file) {
				$newHash = $this->fileContentHasher->hash($file);
				$filesWithHashes[$file] = $newHash;
			}
			$this->cache->save($cacheKey, $variableCacheKey, [$nameScopeMap, $filesWithHashes]);
		} else {
			[$nameScopeMap] = $cached;
		}
		while ($this->memoryCacheCount >= $this->nameScopeMapMemoryCacheCountMax) {
			$oldestKey = array_key_first($this->memoryCache);
			if ($oldestKey === null) {
				break;
			}
			unset($this->memoryCache[$oldestKey]);
			$this->memoryCacheCount--;
		}

		$this->memoryCache[$fileName] = [$nameScopeMap];
		$this->memoryCacheCount++;

		return $this->memoryCache[$fileName];
	}

	/**
	 * @param non-empty-string $cacheKey
	 * @return array{array<string, IntermediaryNameScope>, list<string>}|null
	 */
	private function loadCachedPhpDocNodeMap(string $cacheKey, string $variableCacheKey): ?array
	{
		$cached = $this->cache->load($cacheKey, $variableCacheKey);
		if ($cached !== null) {
			/**
			 * @var array<string, string> $filesWithHashes
			 */
			[$nameScopeMap, $filesWithHashes] = $cached;
			$useCache = true;
			foreach ($filesWithHashes as $file => $hash) {
				$newHash = $this->fileContentHasher->hash($file);
				if ($newHash === false) {
					$useCache = false;
					break;
				}
				if ($newHash === $hash) {
					continue;
				}
				$useCache = false;
				break;
			}

			if ($useCache) {
				$pool = [];
				foreach ($nameScopeMap as $nameScopeKey => $intermediaryNameScope) {
					$nameScopeMap[$nameScopeKey] = $intermediaryNameScope->intern($pool);
				}

				return [$nameScopeMap, array_keys($filesWithHashes)];
			}
		}

		return null;
	}

	/**
	 * @param array<string, string> $traitMethodAliases
	 * @param array<string, true> $activeTraitResolutions
	 * @return array{array<string, IntermediaryNameScope>, list<string>}
	 */
	private function createPhpDocNodeMap(string $fileName, ?string $lookForTrait, ?string $traitUseClass, array $traitMethodAliases, string $originalClassFileName, array $activeTraitResolutions = []): array
	{
		/** @var array<string, IntermediaryNameScope> $nameScopeMap */
		$nameScopeMap = [];

		/** @var array<int, IntermediaryNameScope> $typeMapStack */
		$typeMapStack = [];

		/** @var array<int, array<string, true>> $typeAliasStack */
		$typeAliasStack = [];

		/** @var string[] $classStack */
		$classStack = [];
		if ($lookForTrait !== null && $traitUseClass !== null) {
			$classStack[] = $traitUseClass;
			$typeAliasStack[] = [];
		}
		$namespace = null;

		$traitFound = false;

		$files = [$fileName];

		/** @var array<string|null> $functionStack */
		$functionStack = [];
		$uses = [];
		$constUses = [];
		$this->processNodes(
			$this->phpParser->parseFile($fileName),
			function (Node $node) use ($fileName, $lookForTrait, &$traitFound, $traitMethodAliases, $originalClassFileName, $activeTraitResolutions, &$nameScopeMap, &$typeMapStack, &$typeAliasStack, &$classStack, &$namespace, &$functionStack, &$uses, &$constUses, &$files): ?int {
				if ($node instanceof Node\Stmt\ClassLike) {
					if ($traitFound && $fileName === $originalClassFileName) {
						return self::SKIP_NODE;
					}

					if ($lookForTrait !== null && !$traitFound) {
						if (!$node instanceof Node\Stmt\Trait_) {
							return self::SKIP_NODE;
						}
						if ((string) $node->namespacedName !== $lookForTrait) {
							return self::SKIP_NODE;
						}

						$traitFound = true;
						$functionStack[] = null;
					} else {
						if ($node->name === null) {
							if (!$node instanceof Node\Stmt\Class_) {
								throw new ShouldNotHappenException();
							}

							$className = $this->anonymousClassNameHelper->getAnonymousClassName($node, $fileName);
						} elseif ($node instanceof Node\Stmt\Class_ && $node->isAnonymous()) {
							$className = $node->name->name;
						} else {
							if ($traitFound) {
								return self::SKIP_NODE;
							}
							$className = ltrim(sprintf('%s\\%s', $namespace, $node->name->name), '\\');
						}
						$classStack[] = $className;
						$functionStack[] = null;
					}
				} elseif ($node instanceof Node\Stmt\ClassMethod) {
					if (array_key_exists($node->name->name, $traitMethodAliases)) {
						$functionStack[] = $traitMethodAliases[$node->name->name];
					} else {
						$functionStack[] = $node->name->name;
					}
				} elseif ($node instanceof Node\Stmt\Function_) {
					$functionStack[] = ltrim(sprintf('%s\\%s', $namespace, $node->name->name), '\\');
				} elseif ($node instanceof Node\PropertyHook) {
					$propertyName = $node->getAttribute('propertyName');
					if ($propertyName !== null) {
						$functionStack[] = sprintf('$%s::%s', $propertyName, $node->name->toString());
					}
				}

				$className = array_last($classStack);
				$functionName = array_last($functionStack);
				$nameScopeKey = $this->getNameScopeKey($originalClassFileName, $className, $lookForTrait, $functionName);

				$phpDocNode = null;
				$docComment = null;
				if (
					$node instanceof Node\Stmt
					|| ($node instanceof Node\PropertyHook && $node->getAttribute('propertyName') !== null)
				) {
					$docComment = GetLastDocComment::forNode($node);
					if ($docComment !== null) {
						$phpDocNode = $this->phpDocStringResolver->resolve($docComment);
					}
				}

				if ($node instanceof Node\Stmt\ClassLike || $node instanceof Node\Stmt\ClassMethod || $node instanceof Node\Stmt\Function_ || $node instanceof Node\PropertyHook) {
					if ($phpDocNode !== null) {
						if ($node instanceof Node\Stmt\ClassLike) {
							$typeAliasStack[] = $this->getTypeAliasesMap($phpDocNode);
						}

						$parentNameScope = array_last($typeMapStack);

						$typeMapStack[] = new IntermediaryNameScope(
							$namespace,
							$uses,
							$className,
							$functionName,
							$this->chooseTemplateTagValueNodesByPriority($phpDocNode->getTags()),
							$parentNameScope,
							array_last($typeAliasStack) ?? [],
							constUses: $constUses,
							typeAliasClassName: $lookForTrait,
						);
					} elseif ($node instanceof Node\Stmt\ClassLike) {
						$typeAliasStack[] = [];
					} else {
						$parentNameScope = array_last($typeMapStack);
						$typeMapStack[] = new IntermediaryNameScope(
							$namespace,
							$uses,
							$className,
							$functionName,
							[],
							$parentNameScope,
							array_last($typeAliasStack) ?? [],
							constUses: $constUses,
							typeAliasClassName: $lookForTrait,
						);
					}
				}

				if (
					(
						$node instanceof Node\PropertyHook
						|| (
							$node instanceof Node\Stmt
							&& !$node instanceof Node\Stmt\Namespace_
							&& !$node instanceof Node\Stmt\Declare_
							&& !$node instanceof Node\Stmt\Use_
							&& !$node instanceof Node\Stmt\GroupUse
							&& !$node instanceof Node\Stmt\TraitUse
							&& !$node instanceof Node\Stmt\TraitUseAdaptation
							&& !$node instanceof Node\Stmt\InlineHTML
							&& !($node instanceof Node\Stmt\Expression && $node->expr instanceof Node\Expr\Include_)
						)
					) && !array_key_exists($nameScopeKey, $nameScopeMap)
				) {
					$parentNameScope = array_last($typeMapStack);
					$typeAliasesMap = array_last($typeAliasStack) ?? [];
					$nameScopeMap[$nameScopeKey] = new IntermediaryNameScope(
						$namespace,
						$uses,
						$className,
						$functionName,
						$parentNameScope !== null ? $parentNameScope->getTemplatePhpDocNodes() : [],
						$parentNameScope !== null ? $parentNameScope->getParent() : null,
						$typeAliasesMap,
						constUses: $constUses,
						typeAliasClassName: $lookForTrait,
					);
				}

				if ($node instanceof Node\Stmt\ClassLike || $node instanceof Node\Stmt\ClassMethod || $node instanceof Node\Stmt\Function_ || $node instanceof Node\PropertyHook) {
					if ($phpDocNode !== null || !$node instanceof Node\Stmt\ClassLike) {
						return self::POP_TYPE_MAP_STACK;
					}

					return null;
				}

				if ($node instanceof Node\Stmt\Namespace_) {
					$namespace = $node->name !== null ? (string) $node->name : null;
				} elseif ($node instanceof Node\Stmt\Use_) {
					if ($node->type === Node\Stmt\Use_::TYPE_NORMAL) {
						foreach ($node->uses as $use) {
							$uses[strtolower($use->getAlias()->name)] = (string) $use->name;
						}
					} elseif ($node->type === Node\Stmt\Use_::TYPE_CONSTANT) {
						foreach ($node->uses as $use) {
							$constUses[strtolower($use->getAlias()->name)] = (string) $use->name;
						}
					}
				} elseif ($node instanceof Node\Stmt\GroupUse) {
					$prefix = (string) $node->prefix;
					foreach ($node->uses as $use) {
						if ($node->type === Node\Stmt\Use_::TYPE_NORMAL || $use->type === Node\Stmt\Use_::TYPE_NORMAL) {
							$uses[strtolower($use->getAlias()->name)] = sprintf('%s\\%s', $prefix, (string) $use->name);
						} elseif ($node->type === Node\Stmt\Use_::TYPE_CONSTANT || $use->type === Node\Stmt\Use_::TYPE_CONSTANT) {
							$constUses[strtolower($use->getAlias()->name)] = sprintf('%s\\%s', $prefix, (string) $use->name);
						}
					}
				} elseif ($node instanceof Node\Stmt\TraitUse) {
					$traitMethodAliases = [];
					foreach ($node->adaptations as $traitUseAdaptation) {
						if (!$traitUseAdaptation instanceof Node\Stmt\TraitUseAdaptation\Alias) {
							continue;
						}

						if ($traitUseAdaptation->newName === null) {
							continue;
						}

						$methodName = $traitUseAdaptation->method->toString();
						$newTraitName = $traitUseAdaptation->newName->toString();

						if ($traitUseAdaptation->trait === null) {
							foreach ($node->traits as $traitName) {
								$traitMethodAliases[$traitName->toString()][$methodName] = $newTraitName;
							}
							continue;
						}

						$traitMethodAliases[$traitUseAdaptation->trait->toString()][$methodName] = $newTraitName;
					}

					foreach ($node->traits as $traitName) {
						/** @var class-string $traitName */
						$traitName = (string) $traitName;
						$reflectionProvider = $this->reflectionProviderProvider->getReflectionProvider();
						if (!$reflectionProvider->hasClass($traitName)) {
							continue;
						}

						$traitReflection = $reflectionProvider->getClass($traitName);
						if (!$traitReflection->isTrait()) {
							continue;
						}
						if ($traitReflection->getFileName() === null) {
							continue;
						}
						if (!is_file($traitReflection->getFileName())) {
							continue;
						}

						$className = array_last($classStack);
						if ($className === null) {
							throw new ShouldNotHappenException();
						}

						$traitResolutionKey = $this->getTraitResolutionKey($traitReflection->getFileName(), $traitName, $className, $originalClassFileName);
						if (isset($activeTraitResolutions[$traitResolutionKey])) {
							continue;
						}

						$nestedActiveTraitResolutions = $activeTraitResolutions;
						$nestedActiveTraitResolutions[$traitResolutionKey] = true;

						[$traitNameScopeMap, $traitFiles] = $this->createPhpDocNodeMap(
							$traitReflection->getFileName(),
							$traitName,
							$className,
							$traitMethodAliases[$traitName] ?? [],
							$originalClassFileName,
							$nestedActiveTraitResolutions,
						);
						$nameScopeMap = array_merge($nameScopeMap, array_map(static fn ($originalNameScope) => $originalNameScope->getTraitData() === null ? $originalNameScope->withTraitData($originalClassFileName, $className, $traitName, $lookForTrait, $docComment) : $originalNameScope, $traitNameScopeMap));
						$files = array_merge($files, $traitFiles);
					}
				}

				return null;
			},
			static function (Node $node, $callbackResult) use (&$namespace, &$functionStack, &$classStack, &$typeAliasStack, &$uses, &$typeMapStack, &$constUses): void {
				if ($node instanceof Node\Stmt\ClassLike) {
					if (count($classStack) === 0) {
						throw new ShouldNotHappenException();
					}
					array_pop($classStack);

					if (count($typeAliasStack) === 0) {
						throw new ShouldNotHappenException();
					}

					array_pop($typeAliasStack);

					if (count($functionStack) === 0) {
						throw new ShouldNotHappenException();
					}

					array_pop($functionStack);
				} elseif ($node instanceof Node\Stmt\Namespace_) {
					$namespace = null;
					$uses = [];
					$constUses = [];
				} elseif ($node instanceof Node\Stmt\ClassMethod || $node instanceof Node\Stmt\Function_) {
					if (count($functionStack) === 0) {
						throw new ShouldNotHappenException();
					}

					array_pop($functionStack);
				} elseif ($node instanceof Node\PropertyHook) {
					$propertyName = $node->getAttribute('propertyName');
					if ($propertyName !== null) {
						if (count($functionStack) === 0) {
							throw new ShouldNotHappenException();
						}

						array_pop($functionStack);
					}
				}
				if ($callbackResult !== self::POP_TYPE_MAP_STACK) {
					return;
				}

				if (count($typeMapStack) === 0) {
					throw new ShouldNotHappenException();
				}
				array_pop($typeMapStack);
			},
		);

		if (count($typeMapStack) > 0) {
			throw new ShouldNotHappenException();
		}

		return [$nameScopeMap, $files];
	}

	/**
	 * @param PhpDocTagNode[] $tags
	 * @return array<string, array{string, TemplateTagValueNode}>
	 */
	private function chooseTemplateTagValueNodesByPriority(array $tags): array
	{
		$resolved = [];
		$resolvedPrefix = [];

		$prefixPriority = [
			'' => 0,
			'phan' => 1,
			'psalm' => 2,
			'phpstan' => 3,
		];
		foreach ($tags as $phpDocTagNode) {
			$valueNode = $phpDocTagNode->value;
			if (!$valueNode instanceof TemplateTagValueNode) {
				continue;
			}

			$tagName = $phpDocTagNode->name;
			if (str_starts_with($tagName, '@phan-')) {
				$prefix = 'phan';
			} elseif (str_starts_with($tagName, '@psalm-')) {
				$prefix = 'psalm';
			} elseif (str_starts_with($tagName, '@phpstan-')) {
				$prefix = 'phpstan';
			} else {
				$prefix = '';
			}

			if (isset($resolved[$valueNode->name])) {
				$setPrefix = $resolvedPrefix[$valueNode->name];
				if ($prefixPriority[$prefix] <= $prefixPriority[$setPrefix]) {
					continue;
				}
			}

			$resolved[$valueNode->name] = [$phpDocTagNode->name, $valueNode];
			$resolvedPrefix[$valueNode->name] = $prefix;
		}

		return $resolved;
	}

	/**
	 * @return array<string, true>
	 */
	private function getTypeAliasesMap(PhpDocNode $phpDocNode): array
	{
		$nameScope = new NameScope(null, []);

		$aliasesMap = [];
		foreach (array_keys($this->phpDocNodeResolver->resolveTypeAliasImportTags($phpDocNode, $nameScope)) as $key) {
			$aliasesMap[$key] = true;
		}

		foreach (array_keys($this->phpDocNodeResolver->resolveTypeAliasTags($phpDocNode, $nameScope)) as $key) {
			$aliasesMap[$key] = true;
		}

		return $aliasesMap;
	}

	/**
	 * @param Node[]|Node|scalar|null $node
	 * @param Closure(Node $node): mixed $nodeCallback
	 * @param Closure(Node $node, mixed $callbackResult): void $endNodeCallback
	 */
	private function processNodes($node, Closure $nodeCallback, Closure $endNodeCallback): void
	{
		if ($node instanceof Node) {
			$callbackResult = $nodeCallback($node);
			if ($callbackResult === self::SKIP_NODE) {
				return;
			}
			foreach ($node->getSubNodeNames() as $subNodeName) {
				$subNode = $node->{$subNodeName};
				$this->processNodes($subNode, $nodeCallback, $endNodeCallback);
			}
			$endNodeCallback($node, $callbackResult);
		} elseif (is_array($node)) {
			foreach ($node as $subNode) {
				$this->processNodes($subNode, $nodeCallback, $endNodeCallback);
			}
		}
	}

	private function getNameScopeKey(
		?string $file,
		?string $class,
		?string $trait,
		?string $function,
	): string
	{
		if ($class === null && $trait === null && $function === null) {
			return md5(sprintf('%s', $file ?? 'no-file'));
		}

		if ($class !== null && str_contains($class, 'class@anonymous')) {
			throw new ShouldNotHappenException('Wrong anonymous class name, FilTypeMapper should be called with ClassReflection::getName().');
		}

		return md5(sprintf('%s-%s-%s-%s', $file ?? 'no-file', $class, $trait, $function));
	}

	private function getPhpDocKey(string $nameScopeKey, string $docComment): string
	{
		$doc = new Doc($docComment);
		return md5(sprintf('%s-%s', $nameScopeKey, $doc->getReformattedText()));
	}

	private function getTraitResolutionKey(string $fileName, string $traitName, string $className, string $originalClassFileName): string
	{
		return md5(sprintf('%s-%s-%s-%s', $fileName, $traitName, $className, $originalClassFileName));
	}

}
