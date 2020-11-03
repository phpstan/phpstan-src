<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PhpParser\Comment\Doc;
use PhpParser\Node;
use PHPStan\Analyser\NameScope;
use PHPStan\Broker\AnonymousClassNameHelper;
use PHPStan\Cache\Cache;
use PHPStan\Parser\Parser;
use PHPStan\PhpDoc\NameScopedPhpDocString;
use PHPStan\PhpDoc\PhpDocNodeResolver;
use PHPStan\PhpDoc\PhpDocStringResolver;
use PHPStan\PhpDoc\ResolvedPhpDocBlock;
use PHPStan\PhpDoc\Tag\TemplateTag;
use PHPStan\PhpDocParser\Ast\PhpDoc\InvalidTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocNode;
use PHPStan\Reflection\ReflectionProvider\ReflectionProviderProvider;
use PHPStan\Type\Generic\TemplateType;
use PHPStan\Type\Generic\TemplateTypeFactory;
use PHPStan\Type\Generic\TemplateTypeMap;
use function array_key_exists;
use function file_exists;
use function filemtime;

class FileTypeMapper
{

	private const SKIP_NODE = 1;
	private const POP_TYPE_MAP_STACK = 2;

	private ReflectionProviderProvider $reflectionProviderProvider;

	private \PHPStan\Parser\Parser $phpParser;

	private \PHPStan\PhpDoc\PhpDocStringResolver $phpDocStringResolver;

	private \PHPStan\PhpDoc\PhpDocNodeResolver $phpDocNodeResolver;

	private \PHPStan\Cache\Cache $cache;

	private \PHPStan\Broker\AnonymousClassNameHelper $anonymousClassNameHelper;

	/** @var \PHPStan\PhpDoc\NameScopedPhpDocString[][] */
	private array $memoryCache = [];

	/** @var (false|(callable(): \PHPStan\PhpDoc\NameScopedPhpDocString)|\PHPStan\PhpDoc\NameScopedPhpDocString)[][] */
	private array $inProcess = [];

	/** @var array<string, ResolvedPhpDocBlock> */
	private array $resolvedPhpDocBlockCache = [];

	/** @var array<string, bool> */
	private array $alreadyProcessedDependentFiles = [];

	public function __construct(
		ReflectionProviderProvider $reflectionProviderProvider,
		Parser $phpParser,
		PhpDocStringResolver $phpDocStringResolver,
		PhpDocNodeResolver $phpDocNodeResolver,
		Cache $cache,
		AnonymousClassNameHelper $anonymousClassNameHelper
	)
	{
		$this->reflectionProviderProvider = $reflectionProviderProvider;
		$this->phpParser = $phpParser;
		$this->phpDocStringResolver = $phpDocStringResolver;
		$this->phpDocNodeResolver = $phpDocNodeResolver;
		$this->cache = $cache;
		$this->anonymousClassNameHelper = $anonymousClassNameHelper;
	}

	public function getResolvedPhpDoc(
		string $fileName,
		?string $className,
		?string $traitName,
		?string $functionName,
		string $docComment
	): ResolvedPhpDocBlock
	{
		if ($className === null && $traitName !== null) {
			throw new \PHPStan\ShouldNotHappenException();
		}

		$phpDocKey = $this->getPhpDocKey($className, $traitName, $functionName, $docComment);
		if (isset($this->resolvedPhpDocBlockCache[$phpDocKey])) {
			return $this->resolvedPhpDocBlockCache[$phpDocKey];
		}

		$phpDocMap = [];

		if (!isset($this->inProcess[$fileName])) {
			$phpDocMap = $this->getResolvedPhpDocMap($fileName);
		}

		if (isset($phpDocMap[$phpDocKey])) {
			return $this->createResolvedPhpDocBlock($phpDocKey, $phpDocMap[$phpDocKey], $fileName);
		}

		if (!isset($this->inProcess[$fileName][$phpDocKey])) { // wrong $fileName due to traits
			return ResolvedPhpDocBlock::createEmpty();
		}

		if ($this->inProcess[$fileName][$phpDocKey] === false) { // PHPDoc has cyclic dependency
			return ResolvedPhpDocBlock::createEmpty();
		}

		if (is_callable($this->inProcess[$fileName][$phpDocKey])) {
			$resolveCallback = $this->inProcess[$fileName][$phpDocKey];
			$this->inProcess[$fileName][$phpDocKey] = false;
			$this->inProcess[$fileName][$phpDocKey] = $resolveCallback();
		}

		assert($this->inProcess[$fileName][$phpDocKey] instanceof NameScopedPhpDocString);
		return $this->createResolvedPhpDocBlock($phpDocKey, $this->inProcess[$fileName][$phpDocKey], $fileName);
	}

	private function createResolvedPhpDocBlock(string $phpDocKey, NameScopedPhpDocString $nameScopedPhpDocString, string $fileName): ResolvedPhpDocBlock
	{
		$phpDocString = $nameScopedPhpDocString->getPhpDocString();
		$phpDocNode = $this->resolvePhpDocStringToDocNode($phpDocString);
		$nameScope = $nameScopedPhpDocString->getNameScope();
		$templateTags = $this->phpDocNodeResolver->resolveTemplateTags($phpDocNode, $nameScope);
		$templateTypeScope = $nameScope->getTemplateTypeScope();

		if ($templateTypeScope !== null) {
			$templateTypeMap = new TemplateTypeMap(array_map(static function (TemplateTag $tag) use ($templateTypeScope): Type {
				return TemplateTypeFactory::fromTemplateTag($templateTypeScope, $tag);
			}, $templateTags));
			$nameScope = $nameScope->withTemplateTypeMap(
				new TemplateTypeMap(array_merge(
					$nameScope->getTemplateTypeMap()->getTypes(),
					$templateTypeMap->getTypes()
				))
			);
		} else {
			$templateTypeMap = TemplateTypeMap::createEmpty();
		}

		$this->resolvedPhpDocBlockCache[$phpDocKey] = ResolvedPhpDocBlock::create(
			$phpDocNode,
			$phpDocString,
			$fileName,
			$nameScope,
			$templateTypeMap,
			$templateTags,
			$this->phpDocNodeResolver
		);

		return $this->resolvedPhpDocBlockCache[$phpDocKey];
	}

	private function resolvePhpDocStringToDocNode(string $phpDocString): PhpDocNode
	{
		$phpDocParserVersion = 'Version unknown';
		try {
			$phpDocParserVersion = \Jean85\PrettyVersions::getVersion('phpstan/phpdoc-parser')->getPrettyVersion();
		} catch (\OutOfBoundsException $e) {
			// skip
		}
		$cacheKey = sprintf('phpdocstring-%s', $phpDocString);
		$phpDocNodeSerializedString = $this->cache->load($cacheKey, $phpDocParserVersion);
		if ($phpDocNodeSerializedString !== null) {
			$unserializeResult = @unserialize($phpDocNodeSerializedString);
			if ($unserializeResult === false) {
				$error = error_get_last();
				if ($error !== null) {
					throw new \PHPStan\ShouldNotHappenException(sprintf('unserialize() error: %s', $error['message']));
				}

				throw new \PHPStan\ShouldNotHappenException('Unknown unserialize() error');
			}

			return $unserializeResult;
		}

		$phpDocNode = $this->phpDocStringResolver->resolve($phpDocString);
		if ($this->shouldPhpDocNodeBeCachedToDisk($phpDocNode)) {
			$this->cache->save($cacheKey, $phpDocParserVersion, serialize($phpDocNode));
		}

		return $phpDocNode;
	}

	private function shouldPhpDocNodeBeCachedToDisk(PhpDocNode $phpDocNode): bool
	{
		foreach ($phpDocNode->getTags() as $phpDocTag) {
			if (!$phpDocTag->value instanceof InvalidTagValueNode) {
				continue;
			}

			return false;
		}

		return true;
	}

	/**
	 * @param string $fileName
	 * @return \PHPStan\PhpDoc\NameScopedPhpDocString[]
	 */
	private function getResolvedPhpDocMap(string $fileName): array
	{
		if (!isset($this->memoryCache[$fileName])) {
			$cacheKey = sprintf('%s-phpdocstring-v2', $fileName);
			$variableCacheKey = implode(',', array_map(static function (array $file): string {
				return sprintf('%s-%d', $file['filename'], $file['modifiedTime']);
			}, $this->getCachedDependentFilesWithTimestamps($fileName)));
			$map = $this->cache->load($cacheKey, $variableCacheKey);

			if ($map === null) {
				$map = $this->createResolvedPhpDocMap($fileName);
				$this->cache->save($cacheKey, $variableCacheKey, $map);
			}

			$this->memoryCache[$fileName] = $map;
		}

		return $this->memoryCache[$fileName];
	}

	/**
	 * @param string $fileName
	 * @return \PHPStan\PhpDoc\NameScopedPhpDocString[]
	 */
	private function createResolvedPhpDocMap(string $fileName): array
	{
		$phpDocMap = $this->createFilePhpDocMap($fileName, null, null);
		$resolvedPhpDocMap = [];

		try {
			$this->inProcess[$fileName] = $phpDocMap;

			foreach ($phpDocMap as $phpDocKey => $resolveCallback) {
				$this->inProcess[$fileName][$phpDocKey] = false;
				$this->inProcess[$fileName][$phpDocKey] = $data = $resolveCallback();
				$resolvedPhpDocMap[$phpDocKey] = $data;
			}

		} finally {
			unset($this->inProcess[$fileName]);
		}

		return $resolvedPhpDocMap;
	}

	/**
	 * @param string $fileName
	 * @param string|null $lookForTrait
	 * @param string|null $traitUseClass
	 * @param array<string, string> $traitMethodAliases
	 * @return (callable(): \PHPStan\PhpDoc\NameScopedPhpDocString)[]
	 */
	private function createFilePhpDocMap(
		string $fileName,
		?string $lookForTrait,
		?string $traitUseClass,
		array $traitMethodAliases = []
	): array
	{
		/** @var (callable(): \PHPStan\PhpDoc\NameScopedPhpDocString)[] $phpDocMap */
		$phpDocMap = [];

		/** @var (callable(): TemplateTypeMap)[] $typeMapStack */
		$typeMapStack = [];

		/** @var string[] $classStack */
		$classStack = [];
		if ($lookForTrait !== null && $traitUseClass !== null) {
			$classStack[] = $traitUseClass;
		}
		$namespace = null;
		$functionName = null;
		$uses = [];
		$this->processNodes(
			$this->phpParser->parseFile($fileName),
			function (\PhpParser\Node $node) use ($fileName, $lookForTrait, $traitMethodAliases, &$phpDocMap, &$classStack, &$namespace, &$functionName, &$uses, &$typeMapStack) {
				$resolvableTemplateTypes = false;
				if ($node instanceof Node\Stmt\ClassLike) {
					if ($lookForTrait !== null) {
						if (!$node instanceof Node\Stmt\Trait_) {
							return self::SKIP_NODE;
						}
						if ((string) $node->namespacedName !== $lookForTrait) {
							return self::SKIP_NODE;
						}
					} else {
						if ($node->name === null) {
							if (!$node instanceof Node\Stmt\Class_) {
								throw new \PHPStan\ShouldNotHappenException();
							}

							$className = $this->anonymousClassNameHelper->getAnonymousClassName($node, $fileName);
						} elseif ((bool) $node->getAttribute('anonymousClass', false)) {
							$className = $node->name->name;
						} else {
							$className = ltrim(sprintf('%s\\%s', $namespace, $node->name->name), '\\');
						}
						$classStack[] = $className;
						$functionName = null;
						$resolvableTemplateTypes = true;
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
						if ($traitReflection->getFileName() === false) {
							continue;
						}
						if (!file_exists($traitReflection->getFileName())) {
							continue;
						}

						$className = $classStack[count($classStack) - 1] ?? null;
						if ($className === null) {
							throw new \PHPStan\ShouldNotHappenException();
						}

						$traitPhpDocMap = $this->createFilePhpDocMap(
							$traitReflection->getFileName(),
							$traitName,
							$className,
							$traitMethodAliases[$traitName] ?? []
						);
						$phpDocMap = array_merge($phpDocMap, $traitPhpDocMap);
					}
					return null;
				} elseif ($node instanceof \PhpParser\Node\Stmt\Namespace_) {
					$namespace = (string) $node->name;
					return null;
				} elseif ($node instanceof \PhpParser\Node\Stmt\Use_ && $node->type === \PhpParser\Node\Stmt\Use_::TYPE_NORMAL) {
					foreach ($node->uses as $use) {
						$uses[strtolower($use->getAlias()->name)] = (string) $use->name;
					}
					return null;
				} elseif ($node instanceof \PhpParser\Node\Stmt\GroupUse) {
					$prefix = (string) $node->prefix;
					foreach ($node->uses as $use) {
						if ($node->type !== \PhpParser\Node\Stmt\Use_::TYPE_NORMAL && $use->type !== \PhpParser\Node\Stmt\Use_::TYPE_NORMAL) {
							continue;
						}

						$uses[strtolower($use->getAlias()->name)] = sprintf('%s\\%s', $prefix, (string) $use->name);
					}
					return null;
				} elseif ($node instanceof Node\Stmt\ClassMethod) {
					$functionName = $node->name->name;
					if (array_key_exists($functionName, $traitMethodAliases)) {
						$functionName = $traitMethodAliases[$functionName];
					}
					$resolvableTemplateTypes = true;
				} elseif (
					$node instanceof Node\Param
					&& $node->flags !== 0
				) {
					$resolvableTemplateTypes = true;
				} elseif ($node instanceof Node\Stmt\Function_) {
					$functionName = ltrim(sprintf('%s\\%s', $namespace, $node->name->name), '\\');
					$resolvableTemplateTypes = true;
				} elseif ($node instanceof Node\Stmt\Property) {
					$resolvableTemplateTypes = true;
				} elseif (!in_array(get_class($node), [
					Node\Stmt\Foreach_::class,
					Node\Expr\Assign::class,
					Node\Expr\AssignRef::class,
					Node\Stmt\Class_::class,
					Node\Stmt\ClassConst::class,
					Node\Stmt\Static_::class,
					Node\Stmt\Echo_::class,
					Node\Stmt\Return_::class,
					Node\Stmt\Expression::class,
					Node\Stmt\Throw_::class,
					Node\Stmt\If_::class,
					Node\Stmt\While_::class,
					Node\Stmt\Switch_::class,
					Node\Stmt\Nop::class,
				], true)) {
					return null;
				}

				foreach (array_reverse($node->getComments()) as $comment) {
					if (!$comment instanceof Doc) {
						continue;
					}

					$phpDocString = $comment->getText();
					$className = $classStack[count($classStack) - 1] ?? null;
					$typeMapCb = $typeMapStack[count($typeMapStack) - 1] ?? null;

					$phpDocKey = $this->getPhpDocKey($className, $lookForTrait, $functionName, $phpDocString);
					$phpDocMap[$phpDocKey] = static function () use ($phpDocString, $namespace, $uses, $className, $functionName, $typeMapCb, $resolvableTemplateTypes): NameScopedPhpDocString {
						$nameScope = new NameScope(
							$namespace,
							$uses,
							$className,
							$functionName,
							($typeMapCb !== null ? $typeMapCb() : TemplateTypeMap::createEmpty())->map(static function (string $name, Type $type) use ($className, $resolvableTemplateTypes): Type {
								return TypeTraverser::map($type, static function (Type $type, callable $traverse) use ($className, $resolvableTemplateTypes): Type {
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
								});
							})
						);
						return new NameScopedPhpDocString($phpDocString, $nameScope);
					};

					if (!($node instanceof Node\Stmt\ClassLike) && !($node instanceof Node\FunctionLike)) {
						continue;
					}

					$typeMapStack[] = function () use ($fileName, $className, $lookForTrait, $functionName, $phpDocString, $typeMapCb): TemplateTypeMap {
						static $typeMap = null;
						if ($typeMap !== null) {
							return $typeMap;
						}
						$resolvedPhpDoc = $this->getResolvedPhpDoc(
							$fileName,
							$className,
							$lookForTrait,
							$functionName,
							$phpDocString
						);
						return new TemplateTypeMap(array_merge(
							$typeMapCb !== null ? $typeMapCb()->getTypes() : [],
							$resolvedPhpDoc->getTemplateTypeMap()->getTypes()
						));
					};

					return self::POP_TYPE_MAP_STACK;
				}

				return null;
			},
			static function (\PhpParser\Node $node, $callbackResult) use ($lookForTrait, &$namespace, &$functionName, &$classStack, &$uses, &$typeMapStack): void {
				if ($node instanceof Node\Stmt\ClassLike && $lookForTrait === null) {
					if (count($classStack) === 0) {
						throw new \PHPStan\ShouldNotHappenException();
					}
					array_pop($classStack);
				} elseif ($node instanceof \PhpParser\Node\Stmt\Namespace_) {
					$namespace = null;
					$uses = [];
				} elseif ($node instanceof Node\Stmt\ClassMethod || $node instanceof Node\Stmt\Function_) {
					$functionName = null;
				}
				if ($callbackResult !== self::POP_TYPE_MAP_STACK) {
					return;
				}

				if (count($typeMapStack) === 0) {
					throw new \PHPStan\ShouldNotHappenException();
				}
				array_pop($typeMapStack);
			}
		);

		if (count($typeMapStack) > 0) {
			throw new \PHPStan\ShouldNotHappenException();
		}

		return $phpDocMap;
	}

	/**
	 * @param \PhpParser\Node[]|\PhpParser\Node|scalar $node
	 * @param \Closure(\PhpParser\Node $node): mixed $nodeCallback
	 * @param \Closure(\PhpParser\Node $node, mixed $callbackResult): void $endNodeCallback
	 */
	private function processNodes($node, \Closure $nodeCallback, \Closure $endNodeCallback): void
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

	private function getPhpDocKey(
		?string $class,
		?string $trait,
		?string $function,
		string $docComment
	): string
	{
		$docComment = \Nette\Utils\Strings::replace($docComment, '#\s+#', ' ');

		return md5(sprintf('%s-%s-%s-%s', $class, $trait, $function, $docComment));
	}

	/**
	 * @param string $fileName
	 * @return array<array{filename: string, modifiedTime: int}>
	 */
	private function getCachedDependentFilesWithTimestamps(string $fileName): array
	{
		$cacheKey = sprintf('dependentFilesTimestamps-%s', $fileName);
		$fileModifiedTime = filemtime($fileName);
		if ($fileModifiedTime === false) {
			$fileModifiedTime = time();
		}
		$variableCacheKey = sprintf('%d', $fileModifiedTime);
		/** @var array<array{filename: string, modifiedTime: int}>|null $cachedFilesTimestamps */
		$cachedFilesTimestamps = $this->cache->load($cacheKey, $variableCacheKey);
		if ($cachedFilesTimestamps !== null) {
			$useCached = true;
			foreach ($cachedFilesTimestamps as $cachedFile) {
				$cachedFilename = $cachedFile['filename'];
				$cachedTimestamp = $cachedFile['modifiedTime'];

				if (!file_exists($cachedFilename)) {
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
	 * @param string $fileName
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

				if (!$node instanceof Node\Stmt\Class_ && !$node instanceof Node\Stmt\Trait_) {
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

						$traitReflection = new \ReflectionClass($traitName);
						if ($traitReflection->getFileName() === false) {
							continue;
						}
						if (!file_exists($traitReflection->getFileName())) {
							continue;
						}

						foreach ($this->getDependentFiles($traitReflection->getFileName()) as $traitFileName) {
							$dependentFiles[] = $traitFileName;
						}
					}
				}
			},
			static function (): void {
			}
		);

		unset($this->alreadyProcessedDependentFiles[$fileName]);

		return $dependentFiles;
	}

}
