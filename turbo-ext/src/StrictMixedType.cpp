/*
 * PHPStanTurbo\StrictMixedType — native implementation of
 * PHPStan\Type\StrictMixedType.
 *
 * Declared as PHPStan\Type\StrictMixedType itself at activation: not final
 * (the PHP TemplateStrictMixedType extends it), implementing
 * PHPStan\Type\CompoundType. The twin has no state and no constructor.
 *
 * The twin makes no `$this->method()` calls of its own.
 */

#include "TypeTraits.h"
#include "generated/StrictMixedType.h"

namespace sigs = ptdecl::StrictMixedType::sig;

zend_class_entry *pt_ce_strict_mixed_type = nullptr;

namespace phpstanturbo {

/* Mirrors PHPStan\Type\StrictMixedType. The twin has no state, and none
 * of its methods reads $this, so every method is static. */
class StrictMixedType
{
public:
	/* AcceptsResult::createYes() */
	static zv::Val accepts() { return pt_type_accepts_result(PT_TRI_YES); }

	/* yes for a StrictMixedType or a MixedType that is no TemplateMixedType,
	 * maybe otherwise; UNDEF = pending exception */
	static zv::Val isAcceptedBy(zval *acceptingType)
	{
		bool yes;
		if (UNEXPECTED(!isStrictOrPlainMixed(acceptingType, yes))) return zv::Val();
		return pt_type_accepts_result(yes ? PT_TRI_YES : PT_TRI_MAYBE);
	}

	/* IsSuperTypeOfResult::createYes() */
	static zv::Val isSuperTypeOf() { return pt_type_is_super_type_of_result(PT_TRI_YES); }

	/* yes for a StrictMixedType or a MixedType that is no TemplateMixedType,
	 * maybe otherwise; UNDEF = pending exception */
	static zv::Val isSubTypeOf(zval *otherType)
	{
		bool yes;
		if (UNEXPECTED(!isStrictOrPlainMixed(otherType, yes))) return zv::Val();
		return pt_type_is_super_type_of_result(yes ? PT_TRI_YES : PT_TRI_MAYBE);
	}

	/* $type instanceof self */
	static bool equals(zval *type) { return instanceof_function(Z_OBJCE_P(type), pt_ce_strict_mixed_type); }

	/* $level->handle(): 'mixed' for the type-only, value and precise
	 * levels, 'strict-mixed' for the cache level; UNDEF = pending exception */
	static zv::Val describe(zval *level)
	{
		pt_verbosity_case which;
		if (UNEXPECTED(!pt_type_verbosity_case(level, which))) return zv::Val();
		if (which == PT_VERBOSITY_CACHE) return zv::Val::string("strict-mixed", sizeof("strict-mixed") - 1);
		return zv::Val::string("mixed", sizeof("mixed") - 1);
	}

	/* new ErrorType() */
	static zv::Val error() { return pt_type_new_error_type(); }

	/* new BooleanType() */
	static zv::Val boolean()
	{
		return pt_val_of<pt_boolean_type_new>();
	}

	/* new ConstantBooleanType(false) */
	static zv::Val toGetClassResultType()
	{
		zval result;
		if (UNEXPECTED(!pt_constant_boolean_type_new(&result, false))) return zv::Val();
		return zv::Val::adopt(result);
	}

	/* new ClassNameToObjectTypeResult(new MixedType(), false) */
	static zv::Val toObjectTypeForInstanceofCheck()
	{
		zv::Val mixed = pt_type_new_mixed_type();
		if (UNEXPECTED(mixed.isUndef())) return zv::Val();
		return classNameToObjectTypeResult(std::move(mixed));
	}

	/* new ClassNameToObjectTypeResult(new UnionType([new ObjectWithoutClassType(),
	 * new ClassStringType()]), false) when strings are allowed, of an
	 * ObjectWithoutClassType alone otherwise; UNDEF = pending exception */
	static zv::Val toObjectTypeForIsACheck(bool allowString)
	{
		zv::Val objectWithoutClass = pt_type_new_object_without_class_type();
		if (UNEXPECTED(objectWithoutClass.isUndef())) return zv::Val();
		if (!allowString) return classNameToObjectTypeResult(std::move(objectWithoutClass));
		zval classString;
		if (UNEXPECTED(!pt_class_string_type_new(&classString))) return zv::Val();
		zv::Arr types = zv::Arr::create(2);
		types.push(std::move(objectWithoutClass));
		types.push(zv::Val::adopt(classString));
		zv::Val unionType = pt_type_new_union(std::move(types));
		if (UNEXPECTED(unionType.isUndef())) return zv::Val();
		return classNameToObjectTypeResult(std::move(unionType));
	}

	/* TemplateTypeMap::createEmpty() */
	static zv::Val inferTemplateTypes() { return pt_type_call_static(PT_CLASS_TEMPLATE_TYPE_MAP, PT_LC("createempty"), 0, NULL); }

	/* new IdentifierTypeNode('mixed') */
	static zv::Val toPhpDocNode()
	{
		zv::Val name = zv::Val::string("mixed", 5);
		return pt_type_new(PT_CLASS_IDENTIFIER_TYPE_NODE, 1, name.raw());
	}

private:
	/* `$type instanceof self || ($type instanceof MixedType && !$type
	 * instanceof TemplateMixedType)`; false = pending exception */
	[[nodiscard]] static bool isStrictOrPlainMixed(zval *type, bool &out)
	{
		zend_class_entry *ce = Z_OBJCE_P(type);
		if (instanceof_function(ce, pt_ce_strict_mixed_type)) {
			out = true;
			return true;
		}
		if (!instanceof_function(ce, pt_ce_mixed_type)) {
			out = false;
			return true;
		}
		bool isTemplateMixed;
		if (UNEXPECTED(!pt_type_instanceof(type, PT_CLASS_TEMPLATE_MIXED_TYPE, isTemplateMixed))) return false;
		out = !isTemplateMixed;
		return true;
	}

	/* new ClassNameToObjectTypeResult($type, false) */
	static zv::Val classNameToObjectTypeResult(zv::Val type)
	{
		zv::Args args{type.raw(), false};
		return pt_type_new(PT_CLASS_CLASS_NAME_TO_OBJECT_TYPE_RESULT, 2, args);
	}
};

} // namespace phpstanturbo

using phpstanturbo::StrictMixedType;

/* {{{ engine ABI glue: parameter parsing + registration */

/* the trivial bodies the twin repeats (one handler per body and arity;
 * each method is still declared exactly once, at its registration line) */

static void ZEND_FASTCALL smtEmptyArray0(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	RETURN_EMPTY_ARRAY();
}

static void ZEND_FASTCALL smtEmptyArray1(INTERNAL_FUNCTION_PARAMETERS)
{
	PT_ARGS(1, 1);
	RETURN_EMPTY_ARRAY();
}

static void ZEND_FASTCALL smtNo0(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	PT_RETURN_TRINARY(PT_TRI_NO);
}

static void ZEND_FASTCALL smtNo1(INTERNAL_FUNCTION_PARAMETERS)
{
	PT_ARGS(1, 1);
	PT_RETURN_TRINARY(PT_TRI_NO);
}

/* new ErrorType() */
static void ZEND_FASTCALL smtError0(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	PT_RETURN_VAL(StrictMixedType::error());
}

static void ZEND_FASTCALL smtError1(INTERNAL_FUNCTION_PARAMETERS)
{
	PT_ARGS(1, 1);
	PT_RETURN_VAL(StrictMixedType::error());
}

static void ZEND_FASTCALL smtError2(INTERNAL_FUNCTION_PARAMETERS)
{
	PT_ARGS(2, 2);
	PT_RETURN_VAL(StrictMixedType::error());
}

/* $this */
static void ZEND_FASTCALL smtThis0(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	RETURN_OBJ_COPY(Z_OBJ_P(ZEND_THIS));
}

static void ZEND_FASTCALL smtThis1(INTERNAL_FUNCTION_PARAMETERS)
{
	PT_ARGS(1, 1);
	RETURN_OBJ_COPY(Z_OBJ_P(ZEND_THIS));
}

static void ZEND_FASTCALL smtShouldNotHappen1(INTERNAL_FUNCTION_PARAMETERS)
{
	PT_ARGS(1, 1);
	pt_throw_should_not_happen();
	RETURN_THROWS();
}

static void ZEND_FASTCALL smtShouldNotHappen2(INTERNAL_FUNCTION_PARAMETERS)
{
	PT_ARGS(2, 2);
	pt_throw_should_not_happen();
	RETURN_THROWS();
}

void pt_register_strict_mixed_type()
{
	reg::Class cls("PHPStan\\Type\\StrictMixedType");
	ptdecl::StrictMixedType::declareClass(cls);
	ptdecl::StrictMixedType::declareProperties(cls);

	cls.method(sigs::getReferencedClasses, smtEmptyArray0);
	cls.method(sigs::getObjectClassNames, smtEmptyArray0);
	cls.method(sigs::getObjectClassReflections, smtEmptyArray0);
	cls.method(sigs::getConstantStrings, smtEmptyArray0);

	cls.method(sigs::accepts, [](INTERNAL_FUNCTION_PARAMETERS) {
		PT_ARGS(2, 2);
		PT_RETURN_VAL(StrictMixedType::accepts());
	});

	cls.method(sigs::isAcceptedBy, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *acceptingType;
		bool strictTypes;
		if (!zp::parse<zp::Obj, zp::Bool>(execute_data, acceptingType, strictTypes)) RETURN_THROWS();
		PT_RETURN_VAL(StrictMixedType::isAcceptedBy(acceptingType));
	});

	cls.method(sigs::isSuperTypeOf, [](INTERNAL_FUNCTION_PARAMETERS) {
		PT_ARGS(1, 1);
		PT_RETURN_VAL(StrictMixedType::isSuperTypeOf());
	});

	cls.method(sigs::isSubTypeOf, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *otherType;
		if (!zp::parse<zp::Obj>(execute_data, otherType)) RETURN_THROWS();
		PT_RETURN_VAL(StrictMixedType::isSubTypeOf(otherType));
	});

	cls.method(sigs::equals, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *type;
		if (!zp::parse<zp::Obj>(execute_data, type)) RETURN_THROWS();
		RETURN_BOOL(StrictMixedType::equals(type));
	});

	cls.method(sigs::describe, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *level;
		if (!zp::parse<zp::Obj>(execute_data, level)) RETURN_THROWS();
		PT_RETURN_VAL(StrictMixedType::describe(level));
	});

	cls.method(sigs::getTemplateType, smtError2);
	cls.method(sigs::isObject, smtNo0);
	cls.method(sigs::getClassStringType, smtError0);
	cls.method(sigs::isEnum, smtNo0);
	cls.method(sigs::canAccessProperties, smtNo0);
	cls.method(sigs::hasProperty, smtNo1);
	cls.method(sigs::getProperty, smtShouldNotHappen2);
	cls.method(sigs::getUnresolvedPropertyPrototype, smtShouldNotHappen2);
	cls.method(sigs::hasInstanceProperty, smtNo1);
	cls.method(sigs::getInstanceProperty, smtShouldNotHappen2);
	cls.method(sigs::getUnresolvedInstancePropertyPrototype, smtShouldNotHappen2);
	cls.method(sigs::hasStaticProperty, smtNo1);
	cls.method(sigs::getStaticProperty, smtShouldNotHappen2);
	cls.method(sigs::getUnresolvedStaticPropertyPrototype, smtShouldNotHappen2);
	cls.method(sigs::canCallMethods, smtNo0);
	cls.method(sigs::hasMethod, smtNo1);
	cls.method(sigs::getMethod, smtShouldNotHappen2);
	cls.method(sigs::getUnresolvedMethodPrototype, smtShouldNotHappen2);
	cls.method(sigs::canAccessConstants, smtNo0);
	cls.method(sigs::hasConstant, smtNo1);
	cls.method(sigs::getConstant, smtShouldNotHappen1);
	cls.method(sigs::isIterable, smtNo0);
	cls.method(sigs::isIterableAtLeastOnce, smtNo0);
	cls.method(sigs::getIterableKeyType, smtThis0);
	cls.method(sigs::getIterableValueType, smtThis0);
	cls.method(sigs::isNull, smtNo0);
	cls.method(sigs::isConstantValue, smtNo0);
	cls.method(sigs::isConstantScalarValue, smtNo0);
	cls.method(sigs::getConstantScalarTypes, smtEmptyArray0);
	cls.method(sigs::getConstantScalarValues, smtEmptyArray0);
	cls.method(sigs::isTrue, smtNo0);
	cls.method(sigs::isFalse, smtNo0);
	cls.method(sigs::isBoolean, smtNo0);
	cls.method(sigs::isFloat, smtNo0);
	cls.method(sigs::isInteger, smtNo0);
	cls.method(sigs::isString, smtNo0);
	cls.method(sigs::isNumericString, smtNo0);
	cls.method(sigs::isDecimalIntegerString, smtNo0);
	cls.method(sigs::isNonEmptyString, smtNo0);
	cls.method(sigs::isNonFalsyString, smtNo0);
	cls.method(sigs::isLiteralString, smtNo0);
	cls.method(sigs::isLowercaseString, smtNo0);
	cls.method(sigs::isUppercaseString, smtNo0);
	cls.method(sigs::isClassString, smtNo0);
	cls.method(sigs::getClassStringObjectType, smtError0);
	cls.method(sigs::getObjectTypeOrClassStringObjectType, smtError0);
	cls.method(sigs::isVoid, smtNo0);
	cls.method(sigs::isScalar, smtNo0);

	cls.method(sigs::looseCompare, [](INTERNAL_FUNCTION_PARAMETERS) {
		PT_ARGS(2, 2);
		PT_RETURN_VAL(StrictMixedType::boolean());
	});

	cls.method(sigs::isOffsetAccessible, smtNo0);
	cls.method(sigs::isOffsetAccessLegal, smtNo0);
	cls.method(sigs::hasOffsetValueType, smtNo1);
	cls.method(sigs::getOffsetValueType, smtError1);
	cls.method(sigs::setOffsetValueType, [](INTERNAL_FUNCTION_PARAMETERS) {
		PT_ARGS(2, 3);
		PT_RETURN_VAL(StrictMixedType::error());
	});
	cls.method(sigs::setExistingOffsetValueType, smtError2);
	cls.method(sigs::unsetOffset, smtError1);
	cls.method(sigs::isCallable, smtNo0);
	cls.method(sigs::getCallableParametersAcceptors, smtEmptyArray1);
	cls.method(sigs::isCloneable, smtNo0);

	cls.method(sigs::toBoolean, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		PT_RETURN_VAL(StrictMixedType::boolean());
	});

	cls.method(sigs::toNumber, smtError0);
	cls.method(sigs::toBitwiseNotType, smtError0);

	cls.method(sigs::toGetClassResultType, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		PT_RETURN_VAL(StrictMixedType::toGetClassResultType());
	});

	cls.method(sigs::toClassConstantType, smtError1);

	cls.method(sigs::toObjectTypeForInstanceofCheck, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		PT_RETURN_VAL(StrictMixedType::toObjectTypeForInstanceofCheck());
	});

	cls.method(sigs::toObjectTypeForIsACheck, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *objectOrClassType;
		bool allowString, allowSameClass;
		if (!zp::parse<zp::Obj, zp::Bool, zp::Bool>(execute_data, objectOrClassType, allowString, allowSameClass)) RETURN_THROWS();
		PT_RETURN_VAL(StrictMixedType::toObjectTypeForIsACheck(allowString));
	});

	cls.method(sigs::toAbsoluteNumber, smtError0);
	cls.method(sigs::toInteger, smtError0);
	cls.method(sigs::toFloat, smtError0);
	cls.method(sigs::toString, smtError0);
	cls.method(sigs::toArray, smtError0);
	cls.method(sigs::toArrayKey, smtError0);
	cls.method(sigs::toCoercedArgumentType, smtThis1);

	cls.method(sigs::inferTemplateTypes, [](INTERNAL_FUNCTION_PARAMETERS) {
		PT_ARGS(1, 1);
		PT_RETURN_VAL(StrictMixedType::inferTemplateTypes());
	});

	cls.method(sigs::getReferencedTemplateTypes, smtEmptyArray1);
	cls.method(sigs::getEnumCases, smtEmptyArray0);
	cls.method(sigs::getEnumCaseObject, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_NULL();
	});
	cls.method("traverse", reg::Public, 1, { reg::callableArg("cb") }, pt_type_identity_traverse_handler(), &ptret::type);
	cls.method(sigs::traverseSimultaneously, [](INTERNAL_FUNCTION_PARAMETERS) {
		PT_ARGS(2, 2);
		RETURN_OBJ_COPY(Z_OBJ_P(ZEND_THIS));
	});
	cls.method(sigs::exponentiate, smtError1);
	cls.method(sigs::getFiniteTypes, smtEmptyArray0);

	cls.method(sigs::toPhpDocNode, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		PT_RETURN_VAL(StrictMixedType::toPhpDocNode());
	});

	cls.method(sigs::hasTemplateOrLateResolvableType, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_FALSE;
	});

	/* the traits, in the twin's `use` order (UndecidedComparisonCompoundTypeTrait
	 * brings UndecidedComparisonTypeTrait with it); the class body above wins
	 * over every name it declares */
	ptdecl::StrictMixedType::registerTraits(cls);

	cls.shadow(&pt_ce_strict_mixed_type);
}

/* }}} */
