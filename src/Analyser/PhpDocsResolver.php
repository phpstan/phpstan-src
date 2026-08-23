<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node;
use PhpParser\Node\Expr\Variable;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\PhpDoc\PhpDocInheritanceResolver;
use PHPStan\PhpDoc\ResolvedPhpDocBlock;
use PHPStan\PhpDoc\Tag\VarTag;
use PHPStan\Reflection\Assertions;
use PHPStan\Reflection\ClassReflection;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\FileTypeMapper;
use PHPStan\Type\Generic\TemplateTypeHelper;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\StaticType;
use PHPStan\Type\ThisType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeTraverser;
use PHPStan\Type\UnionType;
use function array_key_exists;
use function array_map;
use function count;
use function is_string;
use function sprintf;
use function strtolower;
use function trim;

#[AutowiredService]
final class PhpDocsResolver
{

	public function __construct(
		private FileTypeMapper $fileTypeMapper,
		private PhpDocInheritanceResolver $phpDocInheritanceResolver,
	)
	{
	}

	/**
	 * @return array{TemplateTypeMap, array<string, Type>, array<string, bool>, array<string, Type>, ?Type, ?Type, ?string, bool, bool, bool, bool|null, bool, bool, string|null, Assertions, ?Type, array<string, Type>, array<(string|int), VarTag>, bool, ?ResolvedPhpDocBlock, array<string, bool>}
	 */
	public function getPhpDocs(Scope $scope, Node\FunctionLike|Node\Stmt\Property $node): array
	{
		$templateTypeMap = TemplateTypeMap::createEmpty();
		$phpDocParameterTypes = [];
		$phpDocImmediatelyInvokedCallableParameters = [];
		$phpDocClosureThisTypeParameters = [];
		$phpDocReturnType = null;
		$phpDocThrowType = null;
		$deprecatedDescription = null;
		$isDeprecated = false;
		$isInternal = false;
		$isFinal = false;
		$isPure = null;
		$isAllowedPrivateMutation = false;
		$acceptsNamedArguments = true;
		$isReadOnly = $scope->isInClass() && $scope->getClassReflection()->isImmutable();
		$asserts = Assertions::createEmpty();
		$selfOutType = null;
		$docComment = $node->getDocComment() !== null
			? $node->getDocComment()->getText()
			: null;

		$file = $scope->getFile();
		$class = $scope->isInClass() ? $scope->getClassReflection()->getName() : null;
		$trait = $scope->isInTrait() ? $scope->getTraitReflection()->getName() : null;
		$resolvedPhpDoc = null;
		$functionName = null;
		$phpDocParameterOutTypes = [];
		$phpDocPureUnlessCallableIsImpureParameters = [];

		if ($node instanceof Node\Stmt\ClassMethod) {
			if (!$scope->isInClass()) {
				throw new ShouldNotHappenException();
			}
			$functionName = $node->name->name;
			$positionalParameterNames = array_map(static function (Node\Param $param): string {
				if (!$param->var instanceof Variable || !is_string($param->var->name)) {
					throw new ShouldNotHappenException();
				}

				return $param->var->name;
			}, $node->getParams());
			$currentResolvedPhpDoc = null;
			if ($docComment !== null) {
				$currentResolvedPhpDoc = $this->fileTypeMapper->getResolvedPhpDoc(
					$file,
					$class,
					$trait,
					$node->name->name,
					$docComment,
				);
			}
			$methodNameForInheritance = $node->getAttribute('originalTraitMethodName') ?? $node->name->name;
			$resolvedPhpDoc = $this->phpDocInheritanceResolver->resolvePhpDocForMethod(
				$scope->getClassReflection(),
				$methodNameForInheritance,
				$currentResolvedPhpDoc,
				$positionalParameterNames,
			);

			if ($node->name->toLowerString() === '__construct') {
				foreach ($node->params as $param) {
					if ($param->flags === 0) {
						continue;
					}

					if ($param->getDocComment() === null) {
						continue;
					}

					if (
						!$param->var instanceof Variable
						|| !is_string($param->var->name)
					) {
						throw new ShouldNotHappenException();
					}

					$paramPhpDoc = $this->fileTypeMapper->getResolvedPhpDoc(
						$file,
						$class,
						$trait,
						'__construct',
						$param->getDocComment()->getText(),
					);
					$varTags = $paramPhpDoc->getVarTags();
					if (isset($varTags[0]) && count($varTags) === 1) {
						$phpDocType = $varTags[0]->getType();
					} elseif (isset($varTags[$param->var->name])) {
						$phpDocType = $varTags[$param->var->name]->getType();
					} else {
						continue;
					}

					$phpDocParameterTypes[$param->var->name] = $phpDocType;
				}
			}
		} elseif ($node instanceof Node\Stmt\Function_) {
			$functionName = trim($scope->getNamespace() . '\\' . $node->name->name, '\\');
		} elseif ($node instanceof Node\PropertyHook) {
			$propertyName = $node->getAttribute('propertyName');
			if ($propertyName !== null) {
				$functionName = sprintf('$%s::%s', $propertyName, $node->name->toString());
			}
		}

		if ($docComment !== null && $resolvedPhpDoc === null) {
			$resolvedPhpDoc = $this->fileTypeMapper->getResolvedPhpDoc(
				$file,
				$class,
				$trait,
				$functionName,
				$docComment,
			);
		}

		$varTags = [];
		if ($resolvedPhpDoc !== null) {
			$templateTypeMap = $resolvedPhpDoc->getTemplateTypeMap();
			$phpDocImmediatelyInvokedCallableParameters = $resolvedPhpDoc->getParamsImmediatelyInvokedCallable();
			foreach ($resolvedPhpDoc->getParamTags() as $paramName => $paramTag) {
				if (array_key_exists($paramName, $phpDocParameterTypes)) {
					continue;
				}
				$paramType = $paramTag->getType();
				if ($scope->isInClass()) {
					$paramType = $this->transformStaticType($scope->getClassReflection(), $paramType);
				}
				$phpDocParameterTypes[$paramName] = $paramType;
			}
			foreach ($resolvedPhpDoc->getParamClosureThisTags() as $paramName => $paramClosureThisTag) {
				if (array_key_exists($paramName, $phpDocClosureThisTypeParameters)) {
					continue;
				}
				$paramClosureThisType = $paramClosureThisTag->getType();
				if ($scope->isInClass()) {
					$paramClosureThisType = $this->transformStaticType($scope->getClassReflection(), $paramClosureThisType);
				}
				$phpDocClosureThisTypeParameters[$paramName] = $paramClosureThisType;
			}

			foreach ($resolvedPhpDoc->getParamOutTags() as $paramName => $paramOutTag) {
				$phpDocParameterOutTypes[$paramName] = $paramOutTag->getType();
			}
			if ($node instanceof Node\FunctionLike) {
				$nativeReturnType = $scope->getFunctionType($node->getReturnType(), false, false);
				$phpDocReturnType = $this->getPhpDocReturnType($resolvedPhpDoc, $nativeReturnType);
				if ($phpDocReturnType !== null && $scope->isInClass()) {
					$phpDocReturnType = $this->transformStaticType($scope->getClassReflection(), $phpDocReturnType);
				}
			}
			$phpDocThrowType = $resolvedPhpDoc->getThrowsTag() !== null ? $resolvedPhpDoc->getThrowsTag()->getType() : null;
			$deprecatedDescription = $resolvedPhpDoc->getDeprecatedTag() !== null ? $resolvedPhpDoc->getDeprecatedTag()->getMessage() : null;
			$isDeprecated = $resolvedPhpDoc->isDeprecated();
			$isInternal = $resolvedPhpDoc->isInternal();
			$isFinal = $resolvedPhpDoc->isFinal();
			$isPure = $resolvedPhpDoc->isPure();
			$isAllowedPrivateMutation = $resolvedPhpDoc->isAllowedPrivateMutation();
			$acceptsNamedArguments = $resolvedPhpDoc->acceptsNamedArguments();
			$isReadOnly = $isReadOnly || $resolvedPhpDoc->isReadOnly();
			$asserts = Assertions::createFromResolvedPhpDocBlock($resolvedPhpDoc);
			$selfOutType = $resolvedPhpDoc->getSelfOutTag() !== null ? $resolvedPhpDoc->getSelfOutTag()->getType() : null;
			$varTags = $resolvedPhpDoc->getVarTags();
			$phpDocPureUnlessCallableIsImpureParameters = $resolvedPhpDoc->getParamsPureUnlessCallableIsImpure();
		}

		if ($acceptsNamedArguments && $scope->isInClass()) {
			$acceptsNamedArguments = $scope->getClassReflection()->acceptsNamedArguments();
		}

		if ($isPure === null && $node instanceof Node\FunctionLike && $scope->isInClass()) {
			// a set hook has no return type node of its own, but it always returns
			// void - the class-level @phpstan-pure must not make it pure
			$isSetHook = $node instanceof Node\PropertyHook && $node->name->toLowerString() === 'set';
			$classResolvedPhpDoc = $scope->getClassReflection()->getResolvedPhpDoc();
			if ($classResolvedPhpDoc !== null && $classResolvedPhpDoc->areAllMethodsPure()) {
				if (
					strtolower($functionName ?? '') === '__construct'
					|| (
						!$isSetHook
						&& ($phpDocReturnType === null || !$phpDocReturnType->isVoid()->yes())
						&& !$scope->getFunctionType($node->getReturnType(), false, false)->isVoid()->yes()
					)
				) {
					$isPure = true;
				}
			} elseif ($classResolvedPhpDoc !== null && $classResolvedPhpDoc->areAllMethodsImpure()) {
				$isPure = false;
			}
		}

		return [$templateTypeMap, $phpDocParameterTypes, $phpDocImmediatelyInvokedCallableParameters, $phpDocClosureThisTypeParameters, $phpDocReturnType, $phpDocThrowType, $deprecatedDescription, $isDeprecated, $isInternal, $isFinal, $isPure, $acceptsNamedArguments, $isReadOnly, $docComment, $asserts, $selfOutType, $phpDocParameterOutTypes, $varTags, $isAllowedPrivateMutation, $resolvedPhpDoc, $phpDocPureUnlessCallableIsImpureParameters];
	}

	private function getPhpDocReturnType(ResolvedPhpDocBlock $resolvedPhpDoc, Type $nativeReturnType): ?Type
	{
		$returnTag = $resolvedPhpDoc->getReturnTag();

		if ($returnTag === null) {
			return null;
		}

		$phpDocReturnType = $returnTag->getType();

		if ($returnTag->isExplicit()) {
			return $phpDocReturnType;
		}

		if ($nativeReturnType->isSuperTypeOf(TemplateTypeHelper::resolveToBounds($phpDocReturnType))->yes()) {
			return $phpDocReturnType;
		}

		if ($phpDocReturnType instanceof UnionType) {
			$types = [];
			foreach ($phpDocReturnType->getTypes() as $innerType) {
				if (!$nativeReturnType->isSuperTypeOf($innerType)->yes()) {
					continue;
				}

				$types[] = $innerType;
			}

			if (count($types) === 0) {
				return null;
			}

			return TypeCombinator::union(...$types);
		}

		return null;
	}

	private function transformStaticType(ClassReflection $declaringClass, Type $type): Type
	{
		return TypeTraverser::map($type, static function (Type $type, callable $traverse) use ($declaringClass): Type {
			if ($type instanceof StaticType) {
				$changedType = $type->changeBaseClass($declaringClass);
				if ($declaringClass->isFinal() && !$type instanceof ThisType) {
					$changedType = $changedType->getStaticObjectType();
				}
				return $traverse($changedType);
			}

			return $traverse($type);
		});
	}

}
