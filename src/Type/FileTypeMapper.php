<?php declare(strict_types = 1);

namespace PHPStan\Type;

use Closure;
use PhpParser\Comment\Doc;
use PhpParser\Node;
use PHPStan\Analyser\NameScope;
use PHPStan\BetterReflection\Util\GetLastDocComment;
use PHPStan\Broker\AnonymousClassNameHelper;
use PHPStan\Cache\Cache;
use PHPStan\Parser\Parser;
use PHPStan\Php\PhpVersion;
use PHPStan\PhpDoc\PhpDocNodeResolver;
use PHPStan\PhpDoc\PhpDocStringResolver;
use PHPStan\PhpDoc\ResolvedPhpDocBlock;
use PHPStan\PhpDoc\Tag\TemplateTag;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocNode;
use PHPStan\Reflection\ReflectionProvider\ReflectionProviderProvider;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\Generic\TemplateType;
use PHPStan\Type\Generic\TemplateTypeFactory;
use PHPStan\Type\Generic\TemplateTypeHelper;
use PHPStan\Type\Generic\TemplateTypeMap;
use ReflectionClass;
use function array_key_exists;
use function array_keys;
use function array_map;
use function array_merge;
use function array_pop;
use function array_slice;
use function count;
use function filemtime;
use function implode;
use function is_array;
use function is_callable;
use function is_file;
use function ltrim;
use function md5;
use function sprintf;
use function strpos;
use function strtolower;
use function time;
use function trait_exists;

class FileTypeMapper
{

	private const SKIP_NODE = 1;
	private const POP_TYPE_MAP_STACK = 2;

	/** @var NameScope[][] */
	private array $memoryCache = [];

	private int $memoryCacheCount = 0;

	/** @var (false|callable(): NameScope|NameScope)[][] */
	private array $inProcess = [];

	/** @var array<string, ResolvedPhpDocBlock> */
	private array $resolvedPhpDocBlockCache = [];

	private int $resolvedPhpDocBlockCacheCount = 0;

	/** @var array<string, bool> */
	private array $alreadyProcessedDependentFiles = [];

	public function __construct(
		private ReflectionProviderProvider $reflectionProviderProvider,
		private Parser $phpParser,
		private PhpDocStringResolver $phpDocStringResolver,
		private PhpDocNodeResolver $phpDocNodeResolver,
		private Cache $cache,
		private AnonymousClassNameHelper $anonymousClassNameHelper,
		private PhpVersion $phpVersion,
	)
	{
	}

	/** @api */
	public function getResolvedPhpDoc(
		string $fileName,
		?string $className,
		?string $traitName,
		?string $functionName,
		string $docComment,
	): ResolvedPhpDocBlock
	{
		if ($className === null && $traitName !== null) {
			throw new ShouldNotHappenException();
		}

		if ($docComment === '') {
			return ResolvedPhpDocBlock::createEmpty();
		}

		$nameScopeKey = $this->getNameScopeKey($fileName, $className, $traitName, $functionName);
		$phpDocKey = md5(sprintf('%s-%s', $nameScopeKey, $docComment));
		if (isset($this->resolvedPhpDocBlockCache[$phpDocKey])) {
			return $this->resolvedPhpDocBlockCache[$phpDocKey];
		}
		$nameScopeMap = [];

		if (!isset($this->inProcess[$fileName])) {
			$nameScopeMap = $this->getNameScopeMap($fileName);
		}

		if (isset($nameScopeMap[$nameScopeKey])) {
			return $this->createResolvedPhpDocBlock($phpDocKey, $nameScopeMap[$nameScopeKey], $docComment, $fileName);
		}

		if (!isset($this->inProcess[$fileName][$nameScopeKey])) { // wrong $fileName due to traits
			return ResolvedPhpDocBlock::createEmpty();
		}

		if ($this->inProcess[$fileName][$nameScopeKey] === false) { // PHPDoc has cyclic dependency
			return ResolvedPhpDocBlock::createEmpty();
		}

		if (is_callable($this->inProcess[$fileName][$nameScopeKey])) {
			$resolveCallback = $this->inProcess[$fileName][$nameScopeKey];
			$this->inProcess[$fileName][$nameScopeKey] = false;
			$this->inProcess[$fileName][$nameScopeKey] = $resolveCallback();
		}

		return $this->createResolvedPhpDocBlock($phpDocKey, $this->inProcess[$fileName][$nameScopeKey], $docComment, $fileName);
	}

	private function createResolvedPhpDocBlock(string $phpDocKey, NameScope $nameScope, string $phpDocString, string $fileName): ResolvedPhpDocBlock
	{
		$phpDocNode = $this->resolvePhpDocStringToDocNode($phpDocString);
		$templateTags = $this->phpDocNodeResolver->resolveTemplateTags($phpDocNode, $nameScope);
		$templateTypeScope = $nameScope->getTemplateTypeScope();

		if ($templateTypeScope !== null) {
			$templateTypeMap = new TemplateTypeMap(array_map(static fn (TemplateTag $tag): Type => TemplateTypeFactory::fromTemplateTag($templateTypeScope, $tag), $templateTags));
			$nameScope = $nameScope->withTemplateTypeMap(
				new TemplateTypeMap(array_merge(
					$nameScope->getTemplateTypeMap()->getTypes(),
					$templateTypeMap->getTypes(),
				)),
			);
			$templateTags = $this->phpDocNodeResolver->resolveTemplateTags($phpDocNode, $nameScope);
			$templateTypeMap = new TemplateTypeMap(array_map(static fn (TemplateTag $tag): Type => TemplateTypeFactory::fromTemplateTag($templateTypeScope, $tag), $templateTags));
			$nameScope = $nameScope->withTemplateTypeMap(
				new TemplateTypeMap(array_merge(
					$nameScope->getTemplateTypeMap()->getTypes(),
					$templateTypeMap->getTypes(),
				)),
			);
		} else {
			$templateTypeMap = TemplateTypeMap::createEmpty();
		}

		if ($this->resolvedPhpDocBlockCacheCount >= 512) {
			$this->resolvedPhpDocBlockCache = array_slice(
				$this->resolvedPhpDocBlockCache,
				1,
				null,
				true,
			);

			$this->resolvedPhpDocBlockCacheCount--;
		}

		$this->resolvedPhpDocBlockCache[$phpDocKey] = ResolvedPhpDocBlock::create(
			$phpDocNode,
			$phpDocString,
			$fileName,
			$nameScope,
			$templateTypeMap,
			$templateTags,
			$this->phpDocNodeResolver,
		);
		$this->resolvedPhpDocBlockCacheCount++;

		return $this->resolvedPhpDocBlockCache[$phpDocKey];
	}

	private function resolvePhpDocStringToDocNode(string $phpDocString): PhpDocNode
	{
		return $this->phpDocStringResolver->resolve($phpDocString);
	}

	/**
	 * @return NameScope[]
	 */
	private function getNameScopeMap(string $fileName): array
	{
		if (!isset($this->memoryCache[$fileName])) {
			$cacheKey = sprintf('%s-phpdocstring-v17-require-once', $fileName);
			$variableCacheKey = sprintf('%s-%s', implode(',', array_map(static fn (array $file): string => sprintf('%s-%d', $file['filename'], $file['modifiedTime']), $this->getCachedDependentFilesWithTimestamps($fileName))), $this->phpVersion->getVersionString());
			$map = $this->cache->load($cacheKey, $variableCacheKey);

			if ($map === null) {
				$map = $this->createResolvedPhpDocMap($fileName);
				$this->cache->save($cacheKey, $variableCacheKey, $map);
			}

			if ($this->memoryCacheCount >= 512) {
				$this->memoryCache = array_slice(
					$this->memoryCache,
					1,
					null,
					true,
				);
				$this->memoryCacheCount--;
			}

			$this->memoryCache[$fileName] = $map;
			$this->memoryCacheCount++;
		}

		return $this->memoryCache[$fileName];
	}

	/**
	 * @return NameScope[]
	 */
	private function createResolvedPhpDocMap(string $fileName): array
	{
		$nameScopeMap = $this->createNameScopeMap($fileName, null, null, [], $fileName);
		$resolvedNameScopeMap = [];

		try {
			$this->inProcess[$fileName] = $nameScopeMap;

			foreach ($nameScopeMap as $nameScopeKey => $resolveCallback) {
				$this->inProcess[$fileName][$nameScopeKey] = false;
				$this->inProcess[$fileName][$nameScopeKey] = $data = $resolveCallback();
				$resolvedNameScopeMap[$nameScopeKey] = $data;
			}

		} finally {
			unset($this->inProcess[$fileName]);
		}

		return $resolvedNameScopeMap;
	}

	/**
	 * @param array<string, string> $traitMethodAliases
	 * @return (callable(): NameScope)[]
	 */
	private function createNameScopeMap(
		string $fileName,
		?string $lookForTrait,
		?string $traitUseClass,
		array $traitMethodAliases,
		string $originalClassFileName,
	): array
	{
		/** @var (callable(): NameScope)[] $nameScopeMap */
		$nameScopeMap = [];

		/** @var (callable(): TemplateTypeMap)[] $typeMapStack */
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

		/** @var array<string|null> $functionStack */
		$functionStack = [];
		$uses = [];
		$this->processNodes(
			$this->phpParser->parseFile($fileName),
			function (Node $node) use ($fileName, $lookForTrait, &$traitFound, $traitMethodAliases, $originalClassFileName, &$nameScopeMap, &$classStack, &$typeAliasStack, &$namespace, &$functionStack, &$uses, &$typeMapStack): ?int {
				if ($node instanceof Node\Stmt\ClassLike) {
					if ($lookForTrait !== null && !$traitFound) {
						if (!$node instanceof Node\Stmt\Trait_) {
							return self::SKIP_NODE;
						}
						if ((string) $node->namespacedName !== $lookForTrait) {
							return self::SKIP_NODE;
						}

						$traitFound = true;
					} else {
						if ($node->name === null) {
							if (!$node instanceof Node\Stmt\Class_) {
								throw new ShouldNotHappenException();
							}

							$className = $this->anonymousClassNameHelper->getAnonymousClassName($node, $fileName);
						} elseif ((bool) $node->getAttribute('anonymousClass', false)) {
							$className = $node->name->name;
						} else {
							if ($traitFound) {
								return self::SKIP_NODE;
							}
							$className = ltrim(sprintf('%s\\%s', $namespace, $node->name->name), '\\');
						}
						$classStack[] = $className;
						$typeAliasStack[] = $this->getTypeAliasesMap($node->getDocComment());
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
				}

				$className = $classStack[count($classStack) - 1] ?? null;
				$functionName = $functionStack[count($functionStack) - 1] ?? null;
				$resolvableTemplateTypes = ($className !== null && $lookForTrait === null) || $functionName !== null;

				if ($node instanceof Node\Stmt\ClassLike || $node instanceof Node\Stmt\ClassMethod || $node instanceof Node\Stmt\Function_) {
					$phpDocString = GetLastDocComment::forNode($node);
					if ($phpDocString !== '') {
						$typeMapStack[] = function () use ($namespace, $uses, $className, $functionName, $phpDocString, $typeMapStack, $resolvableTemplateTypes): TemplateTypeMap {
							$phpDocNode = $this->resolvePhpDocStringToDocNode($phpDocString);
							$nameScope = new NameScope($namespace, $uses, $className, $functionName);
							$templateTags = $this->phpDocNodeResolver->resolveTemplateTags($phpDocNode, $nameScope);
							$templateTypeScope = $nameScope->getTemplateTypeScope();
							if ($templateTypeScope === null) {
								throw new ShouldNotHappenException();
							}
							$templateTypeMap = new TemplateTypeMap(array_map(static fn (TemplateTag $tag): Type => TemplateTypeFactory::fromTemplateTag($templateTypeScope, $tag), $templateTags));
							$nameScope = $nameScope->withTemplateTypeMap($templateTypeMap);
							$templateTags = $this->phpDocNodeResolver->resolveTemplateTags($phpDocNode, $nameScope);
							$templateTypeMap = new TemplateTypeMap(array_map(static fn (TemplateTag $tag): Type => TemplateTypeFactory::fromTemplateTag($templateTypeScope, $tag), $templateTags));
							$typeMapCb = $typeMapStack[count($typeMapStack) - 1] ?? null;

							return (new TemplateTypeMap(array_merge(
								$typeMapCb !== null ? $typeMapCb()->getTypes() : [],
								$templateTypeMap->getTypes(),
							)))->map(static fn (string $name, Type $type): Type => TypeTraverser::map($type, static function (Type $type, callable $traverse) use ($className, $resolvableTemplateTypes): Type {
									if (!$type instanceof TemplateType) {
										return $traverse($type);
									}

									if (!$resolvableTemplateTypes) {
										return $traverse($type->toArgument());
									}

									$scope = $type->getScope();

									if ($scope->getClassName() === null || $scope->getFunctionName() !== null || $scope->getClassName() !== $className) {
										return $traverse($type->toArgument());
									}

									return $traverse($type);
							}));
						};
					}
				}

				$typeMapCb = $typeMapStack[count($typeMapStack) - 1] ?? null;
				$typeAliasesMap = $typeAliasStack[count($typeAliasStack) - 1] ?? [];

				$nameScopeKey = $this->getNameScopeKey($originalClassFileName, $className, $lookForTrait, $functionName);
				if (
					$node instanceof Node\Stmt
					&& !$node instanceof Node\Stmt\Namespace_
					&& !$node instanceof Node\Stmt\Declare_
					&& !$node instanceof Node\Stmt\DeclareDeclare
					&& !$node instanceof Node\Stmt\Use_
					&& !$node instanceof Node\Stmt\UseUse
					&& !$node instanceof Node\Stmt\GroupUse
					&& !$node instanceof Node\Stmt\TraitUse
					&& !$node instanceof Node\Stmt\TraitUseAdaptation
					&& !$node instanceof Node\Stmt\InlineHTML
					&& !($node instanceof Node\Stmt\Expression && $node->expr instanceof Node\Expr\Include_)
					&& !array_key_exists($nameScopeKey, $nameScopeMap)
				) {
					$nameScopeMap[$nameScopeKey] = static fn (): NameScope => new NameScope(
						$namespace,
						$uses,
						$className,
						$functionName,
						($typeMapCb !== null ? $typeMapCb() : TemplateTypeMap::createEmpty()),
						$typeAliasesMap,
					);
				}

				if ($node instanceof Node\Stmt\ClassLike || $node instanceof Node\Stmt\ClassMethod || $node instanceof Node\Stmt\Function_) {
					$phpDocString = GetLastDocComment::forNode($node);
					if ($phpDocString !== '') {
						return self::POP_TYPE_MAP_STACK;
					}

					return null;
				}

				if ($node instanceof Node\Stmt\Namespace_) {
					$namespace = (string) $node->name;
				} elseif ($node instanceof Node\Stmt\Use_ && $node->type === Node\Stmt\Use_::TYPE_NORMAL) {
					foreach ($node->uses as $use) {
						$uses[strtolower($use->getAlias()->name)] = (string) $use->name;
					}
				} elseif ($node instanceof Node\Stmt\GroupUse) {
					$prefix = (string) $node->prefix;
					foreach ($node->uses as $use) {
						if ($node->type !== Node\Stmt\Use_::TYPE_NORMAL && $use->type !== Node\Stmt\Use_::TYPE_NORMAL) {
							continue;
						}

						$uses[strtolower($use->getAlias()->name)] = sprintf('%s\\%s', $prefix, (string) $use->name);
					}
				} elseif ($node instanceof Node\Stmt\TraitUse) {
					$traitMethodAliases = [];
					foreach ($node->adaptations as $traitUseAdaptation) {
						if (!$traitUseAdaptation instanceof Node\Stmt\TraitUseAdaptation\Alias) {
							continue;
						}

						if ($traitUseAdaptation->trait === null) {
							continue;
						}

						if ($traitUseAdaptation->newName === null) {
							continue;
						}

						$traitMethodAliases[$traitUseAdaptation->trait->toString()][$traitUseAdaptation->method->toString()] = $traitUseAdaptation->newName->toString();
					}

					$useDocComment = null;
					if ($node->getDocComment() !== null) {
						$useDocComment = $node->getDocComment()->getText();
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

						$className = $classStack[count($classStack) - 1] ?? null;
						if ($className === null) {
							throw new ShouldNotHappenException();
						}

						$traitPhpDocMap = $this->createNameScopeMap(
							$traitReflection->getFileName(),
							$traitName,
							$className,
							$traitMethodAliases[$traitName] ?? [],
							$originalClassFileName,
						);
						$finalTraitPhpDocMap = [];
						foreach ($traitPhpDocMap as $nameScopeTraitKey => $callback) {
							$finalTraitPhpDocMap[$nameScopeTraitKey] = function () use ($callback, $traitReflection, $fileName, $className, $lookForTrait, $useDocComment): NameScope {
								/** @var NameScope $original */
								$original = $callback();
								if (!$traitReflection->isGeneric()) {
									return $original;
								}

								$traitTemplateTypeMap = $traitReflection->getTemplateTypeMap();

								$useType = null;
								if ($useDocComment !== null) {
									$useTags = $this->getResolvedPhpDoc(
										$fileName,
										$className,
										$lookForTrait,
										null,
										$useDocComment,
									)->getUsesTags();
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
								}

								if ($useType === null) {
									return $original->withTemplateTypeMap($traitTemplateTypeMap->resolveToBounds());
								}

								$transformedTraitTypeMap = $traitReflection->typeMapFromList($useType->getTypes());

								return $original->withTemplateTypeMap($traitTemplateTypeMap->map(static fn (string $name, Type $type): Type => TemplateTypeHelper::resolveTemplateTypes($type, $transformedTraitTypeMap)));
							};
						}
						$nameScopeMap = array_merge($nameScopeMap, $finalTraitPhpDocMap);
					}
				}

				return null;
			},
			static function (Node $node, $callbackResult) use ($lookForTrait, &$namespace, &$functionStack, &$classStack, &$typeAliasStack, &$uses, &$typeMapStack): void {
				if ($node instanceof Node\Stmt\ClassLike && $lookForTrait === null) {
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
				} elseif ($node instanceof Node\Stmt\ClassMethod || $node instanceof Node\Stmt\Function_) {
					if (count($functionStack) === 0) {
						throw new ShouldNotHappenException();
					}

					array_pop($functionStack);
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

		return $nameScopeMap;
	}

	/**
	 * @return array<string, true>
	 */
	private function getTypeAliasesMap(?Doc $docComment): array
	{
		if ($docComment === null) {
			return [];
		}

		$phpDocNode = $this->phpDocStringResolver->resolve($docComment->getText());
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
	 * @param Node[]|Node|scalar $node
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
		string $file,
		?string $class,
		?string $trait,
		?string $function,
	): string
	{
		if ($class === null && $trait === null && $function === null) {
			return md5(sprintf('%s', $file));
		}

		if ($class !== null && strpos($class, 'class@anonymous') !== false) {
			throw new ShouldNotHappenException('Wrong anonymous class name, FilTypeMapper should be called with ClassReflection::getName().');
		}

		return md5(sprintf('%s-%s-%s-%s', $file, $class, $trait, $function));
	}

	/**
	 * @return array<array{filename: string, modifiedTime: int}>
	 */
	private function getCachedDependentFilesWithTimestamps(string $fileName): array
	{
		$cacheKey = sprintf('dependentFilesTimestamps-%s-v2-enum', $fileName);
		$fileModifiedTime = filemtime($fileName);
		if ($fileModifiedTime === false) {
			$fileModifiedTime = time();
		}
		$variableCacheKey = sprintf('%d-%s', $fileModifiedTime, $this->phpVersion->getVersionString());
		/** @var array<array{filename: string, modifiedTime: int}>|null $cachedFilesTimestamps */
		$cachedFilesTimestamps = $this->cache->load($cacheKey, $variableCacheKey);
		if ($cachedFilesTimestamps !== null) {
			$useCached = true;
			foreach ($cachedFilesTimestamps as $cachedFile) {
				$cachedFilename = $cachedFile['filename'];
				$cachedTimestamp = $cachedFile['modifiedTime'];

				if (!is_file($cachedFilename)) {
					$useCached = false;
					break;
				}

				$currentTimestamp = filemtime($cachedFilename);
				if ($currentTimestamp === false) {
					$useCached = false;
					break;
				}

				if ($currentTimestamp !== $cachedTimestamp) {
					$useCached = false;
					break;
				}
			}

			if ($useCached) {
				return $cachedFilesTimestamps;
			}
		}

		$filesTimestamps = [];
		foreach ($this->getDependentFiles($fileName) as $dependentFile) {
			$dependentFileModifiedTime = filemtime($dependentFile);
			if ($dependentFileModifiedTime === false) {
				$dependentFileModifiedTime = time();
			}

			$filesTimestamps[] = [
				'filename' => $dependentFile,
				'modifiedTime' => $dependentFileModifiedTime,
			];
		}

		$this->cache->save($cacheKey, $variableCacheKey, $filesTimestamps);

		return $filesTimestamps;
	}

	/**
	 * @return string[]
	 */
	private function getDependentFiles(string $fileName): array
	{
		$dependentFiles = [$fileName];

		if (isset($this->alreadyProcessedDependentFiles[$fileName])) {
			return $dependentFiles;
		}

		$this->alreadyProcessedDependentFiles[$fileName] = true;

		$this->processNodes(
			$this->phpParser->parseFile($fileName),
			function (Node $node) use (&$dependentFiles) {
				if ($node instanceof Node\Stmt\Declare_) {
					return null;
				}
				if ($node instanceof Node\Stmt\Namespace_) {
					return null;
				}

				if (!$node instanceof Node\Stmt\Class_ && !$node instanceof Node\Stmt\Trait_ && !$node instanceof Node\Stmt\Enum_) {
					return null;
				}

				foreach ($node->stmts as $stmt) {
					if (!$stmt instanceof Node\Stmt\TraitUse) {
						continue;
					}

					foreach ($stmt->traits as $traitName) {
						$traitName = (string) $traitName;
						if (!trait_exists($traitName)) {
							continue;
						}

						$traitReflection = new ReflectionClass($traitName);
						if ($traitReflection->getFileName() === false) {
							continue;
						}
						if (!is_file($traitReflection->getFileName())) {
							continue;
						}

						foreach ($this->getDependentFiles($traitReflection->getFileName()) as $traitFileName) {
							$dependentFiles[] = $traitFileName;
						}
					}
				}

				return null;
			},
			static function (): void {
			},
		);

		unset($this->alreadyProcessedDependentFiles[$fileName]);

		return $dependentFiles;
	}

}
