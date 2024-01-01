<?php declare(strict_types = 1);

namespace PHPStan\Type;

use Closure;
use PhpParser\Node;
use PHPStan\Analyser\NameScope;
use PHPStan\BetterReflection\Util\GetLastDocComment;
use PHPStan\Broker\AnonymousClassNameHelper;
use PHPStan\File\FileHelper;
use PHPStan\Parser\Parser;
use PHPStan\PhpDoc\PhpDocNodeResolver;
use PHPStan\PhpDoc\PhpDocStringResolver;
use PHPStan\PhpDoc\ResolvedPhpDocBlock;
use PHPStan\PhpDoc\Tag\TemplateTag;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocNode;
use PHPStan\Reflection\ReflectionProvider\ReflectionProviderProvider;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\Generic\TemplateTypeFactory;
use PHPStan\Type\Generic\TemplateTypeHelper;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\Generic\TemplateTypeVariance;
use PHPStan\Type\Generic\TemplateTypeVarianceMap;
use function array_key_exists;
use function array_keys;
use function array_map;
use function array_merge;
use function array_pop;
use function array_slice;
use function count;
use function is_array;
use function is_callable;
use function is_file;
use function ltrim;
use function md5;
use function sprintf;
use function str_contains;
use function strtolower;

class FileTypeMapper
{

	private const SKIP_NODE = 1;
	private const POP_TYPE_MAP_STACK = 2;

	/** @var NameScope[][] */
	private array $memoryCache = [];

	private int $memoryCacheCount = 0;

	/** @var (true|callable(): NameScope|NameScope)[][] */
	private array $inProcess = [];

	/** @var array<string, ResolvedPhpDocBlock> */
	private array $resolvedPhpDocBlockCache = [];

	private int $resolvedPhpDocBlockCacheCount = 0;

	public function __construct(
		private ReflectionProviderProvider $reflectionProviderProvider,
		private Parser $phpParser,
		private PhpDocStringResolver $phpDocStringResolver,
		private PhpDocNodeResolver $phpDocNodeResolver,
		private AnonymousClassNameHelper $anonymousClassNameHelper,
		private FileHelper $fileHelper,
	)
	{
	}

	/** @api */
	public function getResolvedPhpDoc(
		?string $fileName,
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

		if ($fileName !== null) {
			$fileName = $this->fileHelper->normalizePath($fileName);
		}

		$nameScopeKey = $this->getNameScopeKey($fileName, $className, $traitName, $functionName);
		$phpDocKey = md5(sprintf('%s-%s', $nameScopeKey, $docComment));
		if (isset($this->resolvedPhpDocBlockCache[$phpDocKey])) {
			return $this->resolvedPhpDocBlockCache[$phpDocKey];
		}

		if ($fileName === null) {
			return $this->createResolvedPhpDocBlock($phpDocKey, new NameScope(null, []), $docComment, null);
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

		if ($this->inProcess[$fileName][$nameScopeKey] === true) { // PHPDoc has cyclic dependency
			return ResolvedPhpDocBlock::createEmpty();
		}

		if (is_callable($this->inProcess[$fileName][$nameScopeKey])) {
			$resolveCallback = $this->inProcess[$fileName][$nameScopeKey];
			$this->inProcess[$fileName][$nameScopeKey] = true;
			$this->inProcess[$fileName][$nameScopeKey] = $resolveCallback();
		}

		return $this->createResolvedPhpDocBlock($phpDocKey, $this->inProcess[$fileName][$nameScopeKey], $docComment, $fileName);
	}

	private function createResolvedPhpDocBlock(string $phpDocKey, NameScope $nameScope, string $phpDocString, ?string $fileName): ResolvedPhpDocBlock
	{
		$phpDocNode = $this->phpDocStringResolver->resolve($phpDocString);
		if ($this->resolvedPhpDocBlockCacheCount >= 2048) {
			$this->resolvedPhpDocBlockCache = array_slice(
				$this->resolvedPhpDocBlockCache,
				1,
				null,
				true,
			);

			$this->resolvedPhpDocBlockCacheCount--;
		}

		$templateTypeMap = $nameScope->getTemplateTypeMap();
		$phpDocTemplateTypes = [];
		$templateTags = $this->phpDocNodeResolver->resolveTemplateTags($phpDocNode, $nameScope);
		foreach (array_keys($templateTags) as $name) {
			$templateType = $templateTypeMap->getType($name);
			if ($templateType === null) {
				continue;
			}
			$phpDocTemplateTypes[$name] = $templateType;
		}

		$this->resolvedPhpDocBlockCache[$phpDocKey] = ResolvedPhpDocBlock::create(
			$phpDocNode,
			$phpDocString,
			$fileName,
			$nameScope,
			new TemplateTypeMap($phpDocTemplateTypes),
			$templateTags,
			$this->phpDocNodeResolver,
			$this->reflectionProviderProvider->getReflectionProvider(),
		);
		$this->resolvedPhpDocBlockCacheCount++;

		return $this->resolvedPhpDocBlockCache[$phpDocKey];
	}

	/**
	 * @return NameScope[]
	 */
	private function getNameScopeMap(string $fileName): array
	{
		if (!isset($this->memoryCache[$fileName])) {
			$map = $this->createResolvedPhpDocMap($fileName);
			if ($this->memoryCacheCount >= 2048) {
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
		$phpDocNodeMap = $this->createPhpDocNodeMap($fileName, null, $fileName, [], $fileName);
		$nameScopeMap = $this->createNameScopeMap($fileName, null, null, [], $fileName, $phpDocNodeMap);
		$resolvedNameScopeMap = [];

		try {
			$this->inProcess[$fileName] = $nameScopeMap;

			foreach ($nameScopeMap as $nameScopeKey => $resolveCallback) {
				$this->inProcess[$fileName][$nameScopeKey] = true;
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
	 * @return array<string, PhpDocNode>
	 */
	private function createPhpDocNodeMap(string $fileName, ?string $lookForTrait, ?string $traitUseClass, array $traitMethodAliases, string $originalClassFileName): array
	{
		/** @var array<string, PhpDocNode> $phpDocNodeMap */
		$phpDocNodeMap = [];

		/** @var string[] $classStack */
		$classStack = [];
		if ($lookForTrait !== null && $traitUseClass !== null) {
			$classStack[] = $traitUseClass;
		}
		$namespace = null;

		$traitFound = false;

		/** @var array<string|null> $functionStack */
		$functionStack = [];
		$this->processNodes(
			$this->phpParser->parseFile($fileName),
			function (Node $node) use ($fileName, $lookForTrait, &$traitFound, $traitMethodAliases, $originalClassFileName, &$phpDocNodeMap, &$classStack, &$namespace, &$functionStack): ?int {
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
						} elseif ((bool) $node->getAttribute('anonymousClass', false)) {
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
				}

				$className = $classStack[count($classStack) - 1] ?? null;
				$functionName = $functionStack[count($functionStack) - 1] ?? null;

				if ($node instanceof Node\Stmt\ClassLike || $node instanceof Node\Stmt\ClassMethod || $node instanceof Node\Stmt\Function_) {
					$docComment = GetLastDocComment::forNode($node);
					if ($docComment !== null) {
						$nameScopeKey = $this->getNameScopeKey($originalClassFileName, $className, $lookForTrait, $functionName);
						$phpDocNodeMap[$nameScopeKey] = $this->phpDocStringResolver->resolve($docComment);
					}

					return null;
				}

				if ($node instanceof Node\Stmt\Namespace_) {
					$namespace = $node->name !== null ? (string) $node->name : null;
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

						$className = $classStack[count($classStack) - 1] ?? null;
						if ($className === null) {
							throw new ShouldNotHappenException();
						}

						$phpDocNodeMap = array_merge($phpDocNodeMap, $this->createPhpDocNodeMap(
							$traitReflection->getFileName(),
							$traitName,
							$className,
							$traitMethodAliases[$traitName] ?? [],
							$originalClassFileName,
						));
					}
				}

				return null;
			},
			static function (Node $node) use (&$namespace, &$functionStack, &$classStack): void {
				if ($node instanceof Node\Stmt\ClassLike) {
					if (count($classStack) === 0) {
						throw new ShouldNotHappenException();
					}
					array_pop($classStack);

					if (count($functionStack) === 0) {
						throw new ShouldNotHappenException();
					}

					array_pop($functionStack);
				} elseif ($node instanceof Node\Stmt\Namespace_) {
					$namespace = null;
				} elseif ($node instanceof Node\Stmt\ClassMethod || $node instanceof Node\Stmt\Function_) {
					if (count($functionStack) === 0) {
						throw new ShouldNotHappenException();
					}

					array_pop($functionStack);
				}
			},
		);

		return $phpDocNodeMap;
	}

	/**
	 * @param array<string, string> $traitMethodAliases
	 * @param array<string, PhpDocNode> $phpDocNodeMap
	 * @return (callable(): NameScope)[]
	 */
	private function createNameScopeMap(
		string $fileName,
		?string $lookForTrait,
		?string $traitUseClass,
		array $traitMethodAliases,
		string $originalClassFileName,
		array $phpDocNodeMap,
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
		$constUses = [];
		$this->processNodes(
			$this->phpParser->parseFile($fileName),
			function (Node $node) use ($fileName, $lookForTrait, $phpDocNodeMap, &$traitFound, $traitMethodAliases, $originalClassFileName, &$nameScopeMap, &$classStack, &$typeAliasStack, &$namespace, &$functionStack, &$uses, &$typeMapStack, &$constUses): ?int {
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
						$traitNameScopeKey = $this->getNameScopeKey($originalClassFileName, $classStack[count($classStack) - 1] ?? null, $lookForTrait, null);
						if (array_key_exists($traitNameScopeKey, $phpDocNodeMap)) {
							$typeAliasStack[] = $this->getTypeAliasesMap($phpDocNodeMap[$traitNameScopeKey]);
						} else {
							$typeAliasStack[] = [];
						}
						$functionStack[] = null;
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
						$classNameScopeKey = $this->getNameScopeKey($originalClassFileName, $className, $lookForTrait, null);
						if (array_key_exists($classNameScopeKey, $phpDocNodeMap)) {
							$typeAliasStack[] = $this->getTypeAliasesMap($phpDocNodeMap[$classNameScopeKey]);
						} else {
							$typeAliasStack[] = [];
						}
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
				$nameScopeKey = $this->getNameScopeKey($originalClassFileName, $className, $lookForTrait, $functionName);

				if ($node instanceof Node\Stmt\ClassLike || $node instanceof Node\Stmt\ClassMethod || $node instanceof Node\Stmt\Function_) {
					if (array_key_exists($nameScopeKey, $phpDocNodeMap)) {
						$phpDocNode = $phpDocNodeMap[$nameScopeKey];
						$typeMapStack[] = function () use ($namespace, $uses, $className, $lookForTrait, $functionName, $phpDocNode, $typeMapStack, $typeAliasStack, $constUses): TemplateTypeMap {
							$typeMapCb = $typeMapStack[count($typeMapStack) - 1] ?? null;
							$currentTypeMap = $typeMapCb !== null ? $typeMapCb() : null;
							$typeAliasesMap = $typeAliasStack[count($typeAliasStack) - 1] ?? [];
							$nameScope = new NameScope($namespace, $uses, $className, $functionName, $currentTypeMap, $typeAliasesMap, false, $constUses, $lookForTrait);
							$templateTags = $this->phpDocNodeResolver->resolveTemplateTags($phpDocNode, $nameScope);
							$templateTypeScope = $nameScope->getTemplateTypeScope();
							if ($templateTypeScope === null) {
								throw new ShouldNotHappenException();
							}
							$templateTypeMap = new TemplateTypeMap(array_map(static fn (TemplateTag $tag): Type => TemplateTypeFactory::fromTemplateTag($templateTypeScope, $tag), $templateTags));
							$nameScope = $nameScope->withTemplateTypeMap($templateTypeMap);
							$templateTags = $this->phpDocNodeResolver->resolveTemplateTags($phpDocNode, $nameScope);
							$templateTypeMap = new TemplateTypeMap(array_map(static fn (TemplateTag $tag): Type => TemplateTypeFactory::fromTemplateTag($templateTypeScope, $tag), $templateTags));

							return new TemplateTypeMap(array_merge(
								$currentTypeMap !== null ? $currentTypeMap->getTypes() : [],
								$templateTypeMap->getTypes(),
							));
						};
					}
				}

				$typeMapCb = $typeMapStack[count($typeMapStack) - 1] ?? null;
				$typeAliasesMap = $typeAliasStack[count($typeAliasStack) - 1] ?? [];

				if (
					$node instanceof Node\Stmt
					&& !$node instanceof Node\Stmt\Namespace_
					&& !$node instanceof Node\Stmt\Declare_
					&& !$node instanceof Node\Stmt\Use_
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
						false,
						$constUses,
						$lookForTrait,
					);
				}

				if ($node instanceof Node\Stmt\ClassLike || $node instanceof Node\Stmt\ClassMethod || $node instanceof Node\Stmt\Function_) {
					if (array_key_exists($nameScopeKey, $phpDocNodeMap)) {
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
							$phpDocNodeMap,
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

								return $original->withTemplateTypeMap($traitTemplateTypeMap->map(static fn (string $name, Type $type): Type => TemplateTypeHelper::resolveTemplateTypes($type, $transformedTraitTypeMap, TemplateTypeVarianceMap::createEmpty(), TemplateTypeVariance::createStatic())));
							};
						}
						$nameScopeMap = array_merge($nameScopeMap, $finalTraitPhpDocMap);
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

}
