<?php declare(strict_types = 1);

namespace PHPStan\Rules\Api;

use PhpParser\Node;
use PhpParser\Node\Expr\Instanceof_;
use PHPStan\Analyser\Scope;
use PHPStan\Parser\TypeTraverserInstanceofVisitor;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\Accessory\AccessoryArrayListType;
use PHPStan\Type\Accessory\AccessoryLiteralStringType;
use PHPStan\Type\Accessory\AccessoryNonEmptyStringType;
use PHPStan\Type\Accessory\AccessoryNonFalsyStringType;
use PHPStan\Type\Accessory\AccessoryNumericStringType;
use PHPStan\Type\Accessory\AccessoryType;
use PHPStan\Type\Accessory\HasMethodType;
use PHPStan\Type\Accessory\HasOffsetType;
use PHPStan\Type\Accessory\HasPropertyType;
use PHPStan\Type\Accessory\NonEmptyArrayType;
use PHPStan\Type\Accessory\OversizedArrayType;
use PHPStan\Type\ArrayType;
use PHPStan\Type\BooleanType;
use PHPStan\Type\CallableType;
use PHPStan\Type\ClassStringType;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\ConstantScalarType;
use PHPStan\Type\ConstantType;
use PHPStan\Type\Enum\EnumCaseObjectType;
use PHPStan\Type\FloatType;
use PHPStan\Type\Generic\GenericClassStringType;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\IterableType;
use PHPStan\Type\NullType;
use PHPStan\Type\ObjectShapeType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\ObjectWithoutClassType;
use PHPStan\Type\StringType;
use PHPStan\Type\TypeWithClassName;
use PHPStan\Type\VoidType;
use function array_key_exists;
use function sprintf;
use function strtolower;

/**
 * @implements Rule<Instanceof_>
 */
class ApiInstanceofTypeRule implements Rule
{

	private const MAP = [
		TypeWithClassName::class => 'Type::getObjectClassNames() or Type::getObjectClassReflections()',
		EnumCaseObjectType::class => 'Type::getEnumCases()',
		ConstantArrayType::class => 'Type::getConstantArrays()',
		ArrayType::class => 'Type::isArray() or Type::getArrays()',
		ConstantStringType::class => 'Type::getConstantStrings()',
		StringType::class => 'Type::isString()',
		ClassStringType::class => 'Type::isClassStringType()',
		IntegerType::class => 'Type::isInteger()',
		FloatType::class => 'Type::isFloat()',
		NullType::class => 'Type::isNull()',
		VoidType::class => 'Type::isVoid()',
		BooleanType::class => 'Type::isBoolean()',
		ConstantBooleanType::class => 'Type::isTrue() or Type::isFalse()',
		CallableType::class => 'Type::isCallable() and Type::getCallableParametersAcceptors()',
		IterableType::class => 'Type::isIterable()',
		ObjectWithoutClassType::class => 'Type::isObject()',
		ObjectType::class => 'Type::isObject() or Type::getObjectClassNames()',
		GenericClassStringType::class => 'Type::isClassStringType() and Type::getClassStringObjectType()',
		GenericObjectType::class => null,
		IntersectionType::class => null,
		ConstantType::class => 'Type::isConstantValue() or Type::generalize()',
		ConstantScalarType::class => 'Type::isConstantScalarValue() or Type::getConstantScalarTypes() or Type::getConstantScalarValues()',
		ObjectShapeType::class => 'Type::isObject() and Type::hasProperty()',

		// accessory types
		NonEmptyArrayType::class => 'Type::isIterableAtLeastOnce()',
		OversizedArrayType::class => 'Type::isOversizedArray()',
		AccessoryArrayListType::class => 'Type::isList()',
		AccessoryNumericStringType::class => 'Type::isNumericString()',
		AccessoryLiteralStringType::class => 'Type::isLiteralString()',
		AccessoryNonEmptyStringType::class => 'Type::isNonEmptyString()',
		AccessoryNonFalsyStringType::class => 'Type::isNonFalsyString()',
		HasMethodType::class => 'Type::hasMethod()',
		HasPropertyType::class => 'Type::hasProperty()',
		HasOffsetType::class => 'Type::hasOffsetValueType()',
		AccessoryType::class => 'methods on PHPStan\\Type\\Type',
	];

	public function __construct(
		private ReflectionProvider $reflectionProvider,
		private bool $enabled,
		private bool $deprecationRulesInstalled,
	)
	{
	}

	public function getNodeType(): string
	{
		return Instanceof_::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!$this->enabled && !$this->deprecationRulesInstalled) {
			return [];
		}

		if (!$node->class instanceof Node\Name) {
			return [];
		}

		if ($node->getAttribute(TypeTraverserInstanceofVisitor::ATTRIBUTE_NAME, false) === true) {
			return [];
		}

		$lowerMap = [];
		foreach (self::MAP as $className => $method) {
			$lowerMap[strtolower($className)] = $method;
		}

		$className = $scope->resolveName($node->class);
		$lowerClassName = strtolower($className);
		if (!array_key_exists($lowerClassName, $lowerMap)) {
			return [];
		}

		if ($this->reflectionProvider->hasClass($className)) {
			$classReflection = $this->reflectionProvider->getClass($className);
			if ($classReflection->isSubclassOf(AccessoryType::class)) {
				if ($className === $classReflection->getName()) {
					return [];
				}
			}
		}

		$tip = 'Learn more: <fg=cyan>https://phpstan.org/blog/why-is-instanceof-type-wrong-and-getting-deprecated</>';
		if ($lowerMap[$lowerClassName] === null) {
			return [
				RuleErrorBuilder::message(sprintf(
					'Doing instanceof %s is error-prone and deprecated.',
					$className,
				))->identifier('phpstanApi.instanceofType')->tip($tip)->build(),
			];
		}

		return [
			RuleErrorBuilder::message(sprintf(
				'Doing instanceof %s is error-prone and deprecated. Use %s instead.',
				$className,
				$lowerMap[$lowerClassName],
			))->identifier('phpstanApi.instanceofType')->tip($tip)->build(),
		];
	}

}
