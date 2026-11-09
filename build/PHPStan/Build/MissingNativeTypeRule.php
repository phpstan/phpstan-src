<?php declare(strict_types = 1);

namespace PHPStan\Build;

use PhpParser\Modifiers;
use PhpParser\Node;
use PhpParser\Node\ComplexType;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Fixable\PhpDoc\PhpDocEditor;
use PHPStan\Node\ClassPropertyNode;
use PHPStan\Node\InClassMethodNode;
use PHPStan\Node\InFunctionNode;
use PHPStan\Php\PhpVersion;
use PHPStan\PhpDocParser\Ast\Node as PhpDocAstNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\ParamTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocChildNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTagNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTextNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\ReturnTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\VarTagValueNode;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ExtendedParametersAcceptor;
use PHPStan\Reflection\Php\PhpFunctionFromParserNodeReflection;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\MissingTypehintCheck;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\ShouldNotHappenException;
use PHPStan\TrinaryLogic;
use PHPStan\Type\NativeTypePosition;
use PHPStan\Type\ParserNodeTypeToPHPStanType;
use PHPStan\Type\Type;
use function array_filter;
use function array_map;
use function array_pop;
use function array_values;
use function count;
use function implode;
use function in_array;
use function sprintf;
use function str_starts_with;
use function strtolower;
use function trim;

/**
 * Reports parameters, return types and properties without a native type
 * when their PHPDoc type can be declared natively. The fix declares the native type
 * and removes the PHPDoc tag when the native type says the same.
 *
 * A return type or a property type is added only when nothing can override it,
 * an overriding method or property without the same native type would no longer compile.
 * A parameter type is added only when it is contravariant with the overridden methods.
 *
 * @implements Rule<Node>
 */
final class MissingNativeTypeRule implements Rule
{

	private const PARAM_TAGS = ['@param', '@phpstan-param', '@psalm-param'];

	private const RETURN_TAGS = ['@return', '@phpstan-return', '@psalm-return'];

	private const VAR_TAGS = ['@var', '@phpstan-var', '@psalm-var'];

	public function __construct(
		private PhpVersion $phpVersion,
		private PhpDocEditor $phpDocEditor,
		private MissingTypehintCheck $missingTypehintCheck,
	)
	{
	}

	public function getNodeType(): string
	{
		return Node::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if ($node instanceof InClassMethodNode) {
			if ($scope->isInTrait()) {
				// one fix of the trait for each class using it
				return [];
			}

			$classReflection = $node->getClassReflection();
			$methodName = $node->getOriginalNode()->name->toString();
			return $this->processFunctionLike(
				$node->getOriginalNode(),
				$node->getMethodReflection(),
				$classReflection,
				sprintf('Method %s::%s()', $classReflection->getDisplayName(), $methodName),
			);
		}

		if ($node instanceof InFunctionNode) {
			return $this->processFunctionLike(
				$node->getOriginalNode(),
				$node->getFunctionReflection(),
				null,
				sprintf('Function %s()', $node->getFunctionReflection()->getName()),
			);
		}

		if ($node instanceof ClassPropertyNode) {
			return $this->processProperty($node, $scope);
		}

		return [];
	}

	/**
	 * @return list<IdentifierRuleError>
	 */
	private function processFunctionLike(
		Node\Stmt\ClassMethod|Node\Stmt\Function_ $functionNode,
		PhpFunctionFromParserNodeReflection $function,
		?ClassReflection $classReflection,
		string $functionDescription,
	): array
	{
		$lowercasedName = strtolower($functionNode->name->toString());
		$isMethod = $functionNode instanceof Node\Stmt\ClassMethod;
		if ($isMethod && str_starts_with($lowercasedName, '__') && !in_array($lowercasedName, ['__construct', '__invoke'], true)) {
			// PHP checks the native types of magic methods
			return [];
		}

		$ancestorMethods = $classReflection !== null ? $this->getAncestorMethods($classReflection, $functionNode->name->toString()) : [];

		/** @var array<int, Identifier|Name|ComplexType> $paramTypeNodes */
		$paramTypeNodes = [];
		$sameParamNames = [];
		$descriptions = [];
		$parameters = $function->getParameters();
		foreach ($functionNode->params as $i => $paramNode) {
			if ($paramNode->type !== null || $paramNode->byRef || !isset($parameters[$i])) {
				// the type of a parameter passed by reference describes the variable after the call too
				continue;
			}

			$parameter = $parameters[$i];
			$type = $parameter->getType();
			// a promoted parameter declares a property too
			$position = $paramNode->isPromoted() ? NativeTypePosition::property($this->phpVersion) : NativeTypePosition::parameter($this->phpVersion);
			$typeNode = $type->toNativePhpTypeNode($position);
			if ($typeNode === null) {
				continue;
			}

			$nativeType = ParserNodeTypeToPHPStanType::resolve($typeNode, $classReflection);
			if (!$nativeType->isSuperTypeOf($type)->yes()) {
				continue;
			}

			$defaultValue = $parameter->getDefaultValue();
			if ($defaultValue !== null && !$nativeType->isSuperTypeOf($defaultValue)->yes()) {
				continue;
			}

			if (!self::isContravariant($nativeType, $i, $ancestorMethods)) {
				continue;
			}

			if ($paramNode->isPromoted() && $classReflection !== null && !$this->canDeclarePropertyType($classReflection, $parameter->getName(), ($paramNode->flags & Modifiers::PRIVATE) !== 0)) {
				continue;
			}

			$paramTypeNodes[$i] = $typeNode;
			$descriptions[] = sprintf('%s %s$%s', self::printTypeNode($typeNode), $parameter->isVariadic() ? '...' : '', $parameter->getName());
			if (!$this->isSameType($nativeType, $type)) {
				continue;
			}

			$sameParamNames[] = $parameter->getName();
		}

		$returnTypeNode = null;
		$isSameReturnType = false;
		if (
			$functionNode->returnType === null
			&& !in_array($lowercasedName, ['__construct', '__destruct'], true)
			&& (!$functionNode instanceof Node\Stmt\ClassMethod || $classReflection === null || !self::canBeOverridden($classReflection, $functionNode))
		) {
			$returnType = $function->getReturnType();
			$returnTypeNode = $returnType->toNativePhpTypeNode(NativeTypePosition::returnType($this->phpVersion));
			if ($returnTypeNode !== null) {
				$nativeReturnType = ParserNodeTypeToPHPStanType::resolve($returnTypeNode, $classReflection);
				if (!$nativeReturnType->isSuperTypeOf($returnType)->yes() || !self::isCovariant($nativeReturnType, $ancestorMethods)) {
					$returnTypeNode = null;
				} else {
					$descriptions[] = sprintf('return type %s', self::printTypeNode($returnTypeNode));
					$isSameReturnType = $this->isSameType($nativeReturnType, $returnType);
				}
			}
		}

		if ($descriptions === []) {
			return [];
		}

		return [
			RuleErrorBuilder::message(sprintf('%s is missing native types: %s.', $functionDescription, implode(', ', $descriptions)))
				->identifier('phpstan.missingNativeType')
				->fixNode($functionNode, function (Node\Stmt\ClassMethod|Node\Stmt\Function_ $functionNode) use ($paramTypeNodes, $sameParamNames, $returnTypeNode, $isSameReturnType) {
					foreach ($paramTypeNodes as $i => $typeNode) {
						$param = $functionNode->params[$i];
						if ($param->flags === 0 && $param->attrGroups === []) {
							$param->type = $typeNode;
							continue;
						}

						// the format-preserving printer would insert the type before the modifiers and attributes
						$functionNode->params[$i] = new Node\Param($param->var, $param->default, $typeNode, $param->byRef, $param->variadic, flags: $param->flags, attrGroups: array_values($param->attrGroups), hooks: $param->hooks);
					}
					if ($returnTypeNode !== null) {
						$functionNode->returnType = $returnTypeNode;
					}

					if ($sameParamNames !== [] || $isSameReturnType) {
						$sameParamTagNames = array_map(static fn (string $name): string => '$' . $name, $sameParamNames);
						$this->removePhpDocTags($functionNode, static function (PhpDocTagNode $tag) use ($sameParamTagNames, $isSameReturnType): bool {
							$value = $tag->value;
							if ($value instanceof ParamTagValueNode) {
								return in_array($tag->name, self::PARAM_TAGS, true)
									&& $value->description === ''
									&& in_array($value->parameterName, $sameParamTagNames, true);
							}

							return $isSameReturnType
								&& $value instanceof ReturnTagValueNode
								&& in_array($tag->name, self::RETURN_TAGS, true)
								&& $value->description === '';
						});
					}

					return $functionNode;
				})
				->build(),
		];
	}

	/**
	 * @return list<IdentifierRuleError>
	 */
	private function processProperty(ClassPropertyNode $node, Scope $scope): array
	{
		$propertyNode = $node->getOriginalNode()->getAttribute('originalPropertyStmt');
		if (
			!$propertyNode instanceof Node\Stmt\Property
			|| $propertyNode->type !== null
			|| count($propertyNode->props) !== 1
			|| $node->hasHooks()
			|| $node->isDeclaredInTrait()
		) {
			return [];
		}

		$type = $node->getPhpDocType();
		if ($type === null) {
			return [];
		}

		$classReflection = $node->getClassReflection();
		if (!$this->canDeclarePropertyType($classReflection, $node->getName(), $node->isPrivate())) {
			return [];
		}

		$typeNode = $type->toNativePhpTypeNode(NativeTypePosition::property($this->phpVersion));
		if ($typeNode === null) {
			return [];
		}

		$nativeType = ParserNodeTypeToPHPStanType::resolve($typeNode, $classReflection);
		if (!$nativeType->isSuperTypeOf($type)->yes()) {
			return [];
		}

		$default = $node->getDefault();
		$addNullDefault = false;
		if ($default !== null) {
			if (!$nativeType->isSuperTypeOf($scope->getType($default))->yes()) {
				return [];
			}
		} elseif (!$nativeType->isNull()->no()) {
			// a property without a native type is null until it is assigned, a typed one would be uninitialized
			$addNullDefault = true;
		} elseif ($node->isStatic()) {
			// nothing reports reading an uninitialized static property
			return [];
		}

		$isSameType = $this->isSameType($nativeType, $type);

		return [
			RuleErrorBuilder::message(sprintf('Property %s::$%s is missing native type %s.', $classReflection->getDisplayName(), $node->getName(), self::printTypeNode($typeNode)))
				->identifier('phpstan.missingNativeType')
				->fixNode($propertyNode, function (Node\Stmt\Property $propertyNode) use ($typeNode, $addNullDefault, $isSameType) {
					$propertyNode->type = $typeNode;
					if ($addNullDefault) {
						$propertyNode->props[0]->default = new ConstFetch(new Name('null'));
					}

					if ($isSameType) {
						$this->removePhpDocTags($propertyNode, static fn (PhpDocTagNode $tag): bool => in_array($tag->name, self::VAR_TAGS, true)
							&& $tag->value instanceof VarTagValueNode
							&& $tag->value->description === '');
					}

					return $propertyNode;
				})
				->build(),
		];
	}

	/**
	 * @param callable(PhpDocTagNode): bool $shouldRemove
	 */
	private function removePhpDocTags(Node $node, callable $shouldRemove): void
	{
		$this->phpDocEditor->edit($node, static function (PhpDocAstNode $phpDocNode) use ($shouldRemove): ?PhpDocNode {
			if (!$phpDocNode instanceof PhpDocNode) {
				return null;
			}

			$children = array_values(array_filter(
				$phpDocNode->children,
				static fn (PhpDocChildNode $child): bool => !$child instanceof PhpDocTagNode || !$shouldRemove($child),
			));

			// the empty line that separated the removed tags from the description
			while ($children !== [] && $children[count($children) - 1] instanceof PhpDocTextNode && trim($children[count($children) - 1]->text) === '') {
				array_pop($children);
			}

			$phpDocNode->children = $children;

			return $phpDocNode;
		});
	}

	/**
	 * A property type must stay the same in child classes, and a parent property needs the same type too.
	 */
	private function canDeclarePropertyType(ClassReflection $classReflection, string $propertyName, bool $isPrivate): bool
	{
		if (!$isPrivate && !self::isFinal($classReflection)) {
			return false;
		}

		foreach ($classReflection->getParents() as $parent) {
			if ($parent->hasNativeProperty($propertyName) && !$parent->getNativeProperty($propertyName)->isPrivate()) {
				return false;
			}
		}

		return true;
	}

	/**
	 * @return list<ExtendedParametersAcceptor> variants of the parent and interface methods the method has to be compatible with
	 */
	private function getAncestorMethods(ClassReflection $classReflection, string $methodName): array
	{
		$isConstructor = strtolower($methodName) === '__construct';
		$variants = [];
		foreach ([...$classReflection->getParents(), ...array_values($classReflection->getInterfaces())] as $ancestor) {
			if (!$ancestor->hasNativeMethod($methodName)) {
				continue;
			}

			$method = $ancestor->getNativeMethod($methodName);
			if ($method->isPrivate()) {
				continue;
			}

			$isAbstract = $method->isAbstract();
			if ($isConstructor && !$ancestor->isInterface() && $isAbstract !== true && !($isAbstract instanceof TrinaryLogic && $isAbstract->yes())) {
				// PHP checks the signature of a constructor only against an abstract one
				continue;
			}

			foreach ($method->getVariants() as $variant) {
				$variants[] = $variant;
			}
		}

		return $variants;
	}

	/**
	 * @param list<ExtendedParametersAcceptor> $ancestorMethods
	 */
	private static function isContravariant(Type $nativeType, int $position, array $ancestorMethods): bool
	{
		foreach ($ancestorMethods as $ancestorMethod) {
			$ancestorParameters = $ancestorMethod->getParameters();
			$ancestorParameter = $ancestorParameters[$position] ?? null;
			if ($ancestorParameter === null) {
				$lastParameter = $ancestorParameters[count($ancestorParameters) - 1] ?? null;
				if ($lastParameter === null || !$lastParameter->isVariadic()) {
					continue;
				}

				$ancestorParameter = $lastParameter;
			}

			if (!$nativeType->isSuperTypeOf($ancestorParameter->getNativeType())->yes()) {
				return false;
			}
		}

		return true;
	}

	/**
	 * @param list<ExtendedParametersAcceptor> $ancestorMethods
	 */
	private static function isCovariant(Type $nativeReturnType, array $ancestorMethods): bool
	{
		foreach ($ancestorMethods as $ancestorMethod) {
			if (!$ancestorMethod->getNativeReturnType()->isSuperTypeOf($nativeReturnType)->yes()) {
				return false;
			}
		}

		return true;
	}

	private static function canBeOverridden(ClassReflection $classReflection, Node\Stmt\ClassMethod $method): bool
	{
		return !$method->isPrivate() && !$method->isFinal() && !self::isFinal($classReflection);
	}

	private static function isFinal(ClassReflection $classReflection): bool
	{
		return $classReflection->isFinalByKeyword() || $classReflection->isAnonymous() || $classReflection->isEnum();
	}

	/**
	 * Whether the PHPDoc type says nothing more than the native type. An explicit `mixed[]` says more than `array`:
	 * they accept the same values, but only the former has a value type.
	 */
	private function isSameType(Type $nativeType, Type $phpDocType): bool
	{
		return $nativeType->isSuperTypeOf($phpDocType)->yes()
			&& $phpDocType->isSuperTypeOf($nativeType)->yes()
			&& count($this->missingTypehintCheck->getIterableTypesWithMissingValueTypehint($nativeType)) === count($this->missingTypehintCheck->getIterableTypesWithMissingValueTypehint($phpDocType));
	}

	private static function printTypeNode(Identifier|Name|ComplexType $node): string
	{
		if ($node instanceof Node\NullableType) {
			return '?' . self::printTypeNode($node->type);
		}

		if ($node instanceof Node\UnionType) {
			return implode('|', array_map(static fn (Identifier|Name|Node\IntersectionType $member): string => $member instanceof Node\IntersectionType ? '(' . self::printTypeNode($member) . ')' : self::printTypeNode($member), $node->types));
		}

		if ($node instanceof Node\IntersectionType) {
			return implode('&', array_map(static fn (Identifier|Name $member): string => self::printTypeNode($member), $node->types));
		}

		if ($node instanceof Identifier || $node instanceof Name) {
			return $node->toString();
		}

		throw new ShouldNotHappenException();
	}

}
