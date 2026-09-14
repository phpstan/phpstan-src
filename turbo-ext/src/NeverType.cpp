/*
 * PHPStanTurbo\NeverType — native implementation of PHPStan\Type\NeverType.
 *
 * Declared as PHPStan\Type\NeverType itself at activation: not final (the
 * PHP NonAcceptingNeverType extends it), implementing
 * PHPStan\Type\CompoundType. State is the twin's two promoted constructor
 * properties, `private bool $isExplicit` and `private ?string $reason`,
 * declared typed property slots (IS_PROP_UNINIT until the constructor
 * writes them), so the std object handlers do GC/clone.
 */

#include "TypeTraits.h"
#include "generated/NeverType.h"

namespace slots = ptdecl::NeverType::slot;
namespace sigs = ptdecl::NeverType::sig;

zend_class_entry *pt_ce_never_type = nullptr;

namespace phpstanturbo {

/* Mirrors PHPStan\Type\NeverType. State lives in the PHP object's
 * $isExplicit and $reason. */
class NeverType
{
public:
	explicit NeverType(zend_object *self) : self(self) {}

	/* __construct(private bool $isExplicit = false, private ?string $reason
	 * = null): initializes the typed slots; $reason borrowed, NULL for null */
	void construct(bool isExplicit, zend_string *reason)
	{
		zval *isExplicitSlot = OBJ_PROP_NUM(self, slots::isExplicit);
		zval *reasonSlot = OBJ_PROP_NUM(self, slots::reason);
		ZVAL_BOOL(isExplicitSlot, isExplicit);
		if (reason == NULL) {
			ZVAL_NULL(reasonSlot);
		} else {
			ZVAL_STR_COPY(reasonSlot, reason);
		}
		Z_PROP_FLAG_P(isExplicitSlot) = 0; /* no longer IS_PROP_UNINIT */
		Z_PROP_FLAG_P(reasonSlot) = 0;
	}

	/* new NeverType($isExplicit) — exactly the class, as the twin's `new
	 * NeverType()` sites spell it; UNDEF = pending exception */
	static zv::Val create(bool isExplicit = false)
	{
		zval object;
		if (UNEXPECTED(object_init_ex(&object, pt_ce_never_type) != SUCCESS)) return zv::Val();
		NeverType(Z_OBJ(object)).construct(isExplicit, NULL);
		return zv::Val::adopt(object);
	}

	/* $this->isExplicit; false with an Error pending when the constructor
	 * never ran (ReflectionClass::newInstanceWithoutConstructor()) — the
	 * twin's typed-property read raises the same */
	[[nodiscard]] bool isExplicit(bool &out) const
	{
		zval *slot = OBJ_PROP_NUM(self, slots::isExplicit);
		if (UNEXPECTED(Z_TYPE_P(slot) != IS_TRUE && Z_TYPE_P(slot) != IS_FALSE)) {
			zend_throw_error(NULL, "Typed property %s::$isExplicit must not be accessed before initialization", ZSTR_VAL(pt_ce_never_type->name));
			return false;
		}
		out = Z_TYPE_P(slot) == IS_TRUE;
		return true;
	}

	/* $this->reason (borrowed, IS_NULL or IS_STRING); NULL with an Error
	 * pending when uninitialized */
	[[nodiscard]] zval *reason() const
	{
		zval *slot = OBJ_PROP_NUM(self, slots::reason);
		if (UNEXPECTED(Z_TYPE_P(slot) == IS_UNDEF)) {
			zend_throw_error(NULL, "Typed property %s::$reason must not be accessed before initialization", ZSTR_VAL(pt_ce_never_type->name));
			return NULL;
		}
		return slot;
	}

	/* AcceptsResult::createYes() */
	static zv::Val accepts() { return pt_type_accepts_result(PT_TRI_YES); }

	/* yes for a NeverType, maybe for a TemplateType, no otherwise; UNDEF =
	 * pending exception */
	static zv::Val isSuperTypeOf(zval *type)
	{
		if (instanceof_function(Z_OBJCE_P(type), pt_ce_never_type)) return pt_type_is_super_type_of_result(PT_TRI_YES);
		bool isTemplate;
		if (UNEXPECTED(!pt_type_instanceof(type, PT_CLASS_TEMPLATE_TYPE, isTemplate))) return zv::Val();
		if (isTemplate) return pt_type_is_super_type_of_result(PT_TRI_MAYBE);
		return pt_type_is_super_type_of_result(PT_TRI_NO);
	}

	/* $type instanceof self */
	static bool equals(zval *type) { return instanceof_function(Z_OBJCE_P(type), pt_ce_never_type); }

	/* IsSuperTypeOfResult::createYes() */
	static zv::Val isSubTypeOf() { return pt_type_is_super_type_of_result(PT_TRI_YES); }

	/* $this->isSubTypeOf($acceptingType)->toAcceptsResult(); UNDEF =
	 * pending exception */
	zv::Val isAcceptedBy(zval *acceptingType) const
	{
		zv::Val result = isExact() ? isSubTypeOf() : pt_type_op(self, PT_OP_IS_SUB_TYPE_OF, 1, acceptingType);
		if (UNEXPECTED(result.isUndef())) return zv::Val();
		if (UNEXPECTED(!zv::Ref(result.raw()).isObject())) {
			zend_type_error("phpstan_turbo: isSubTypeOf() must return %s", ZSTR_VAL(pt_ce_is_super_type_of_result->name));
			return zv::Val();
		}
		return pt_type_call(Z_OBJ_P(result.raw()), PT_LC("toacceptsresult"), 0, NULL);
	}

	static const char *describe() { return "*NEVER*"; }

	/* new NeverType() */
	static zv::Val never() { return create(); }

	/* new ErrorType() */
	static zv::Val error() { return pt_type_new_error_type(); }

	/* $this->getKeysArray(); UNDEF = pending exception */
	zv::Val getKeysArrayFiltered() const
	{
		if (EXPECTED(isExact())) return never();
		return pt_type_call(self, PT_LC("getkeysarray"), 0, NULL);
	}

	/* new ClassNameToObjectTypeResult($this, false) */
	zv::Val toObjectTypeForInstanceofCheck() const
	{
		zv::Args args{self, false};
		return pt_type_new(PT_CLASS_CLASS_NAME_TO_OBJECT_TYPE_RESULT, 2, args);
	}

	/* new BooleanType() */
	static zv::Val looseCompare()
	{
		return pt_val_of<pt_boolean_type_new>();
	}

	/* new IdentifierTypeNode('never') */
	static zv::Val toPhpDocNode()
	{
		zv::Val name = zv::Val::string("never", 5);
		return pt_type_new(PT_CLASS_IDENTIFIER_TYPE_NODE, 1, name.raw());
	}

private:
	zend_object *self;

	/* exactly a NeverType, none of its methods overridden: $this-calls can
	 * go straight to the C++ methods */
	bool isExact() const { return self->ce == pt_ce_never_type; }
};

} // namespace phpstanturbo

using phpstanturbo::NeverType;

bool pt_never_type_new(zval *out, bool isExplicit)
{
	return pt_val_into(NeverType::create(isExplicit), out);
}

void pt_never_type_construct(zend_object *self, bool isExplicit, zend_string *reason)
{
	NeverType(self).construct(isExplicit, reason);
}

/* {{{ engine ABI glue: parameter parsing + registration */

#define PT_THIS NeverType(Z_OBJ_P(ZEND_THIS))

/* the trivial bodies the twin repeats (one handler per body and arity;
 * each method is still declared exactly once, at its registration line) */

static void ZEND_FASTCALL ntEmptyArray0(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	RETURN_EMPTY_ARRAY();
}

static void ZEND_FASTCALL ntNo0(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	PT_RETURN_TRINARY(PT_TRI_NO);
}

static void ZEND_FASTCALL ntNo1(INTERNAL_FUNCTION_PARAMETERS)
{
	PT_ARGS(1, 1);
	PT_RETURN_TRINARY(PT_TRI_NO);
}

static void ZEND_FASTCALL ntYes0(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	PT_RETURN_TRINARY(PT_TRI_YES);
}

static void ZEND_FASTCALL ntYes1(INTERNAL_FUNCTION_PARAMETERS)
{
	PT_ARGS(1, 1);
	PT_RETURN_TRINARY(PT_TRI_YES);
}

static void ZEND_FASTCALL ntMaybe0(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	PT_RETURN_TRINARY(PT_TRI_MAYBE);
}

/* new NeverType() */
static void ZEND_FASTCALL ntNever0(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	PT_RETURN_VAL(NeverType::never());
}

static void ZEND_FASTCALL ntNever1(INTERNAL_FUNCTION_PARAMETERS)
{
	PT_ARGS(1, 1);
	PT_RETURN_VAL(NeverType::never());
}

static void ZEND_FASTCALL ntNever2(INTERNAL_FUNCTION_PARAMETERS)
{
	PT_ARGS(2, 2);
	PT_RETURN_VAL(NeverType::never());
}

static void ZEND_FASTCALL ntNever3(INTERNAL_FUNCTION_PARAMETERS)
{
	PT_ARGS(3, 3);
	PT_RETURN_VAL(NeverType::never());
}

/* new ErrorType() */
static void ZEND_FASTCALL ntError0(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	PT_RETURN_VAL(NeverType::error());
}

static void ZEND_FASTCALL ntError2(INTERNAL_FUNCTION_PARAMETERS)
{
	PT_ARGS(2, 2);
	PT_RETURN_VAL(NeverType::error());
}

/* $this */
static void ZEND_FASTCALL ntThis0(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	RETURN_OBJ_COPY(Z_OBJ_P(ZEND_THIS));
}

static void ZEND_FASTCALL ntThis1(INTERNAL_FUNCTION_PARAMETERS)
{
	PT_ARGS(1, 1);
	RETURN_OBJ_COPY(Z_OBJ_P(ZEND_THIS));
}

static void ZEND_FASTCALL ntShouldNotHappen1(INTERNAL_FUNCTION_PARAMETERS)
{
	PT_ARGS(1, 1);
	pt_throw_should_not_happen();
	RETURN_THROWS();
}

static void ZEND_FASTCALL ntShouldNotHappen2(INTERNAL_FUNCTION_PARAMETERS)
{
	PT_ARGS(2, 2);
	pt_throw_should_not_happen();
	RETURN_THROWS();
}

void pt_register_never_type()
{
	reg::Class cls("PHPStan\\Type\\NeverType");
	ptdecl::NeverType::declareClass(cls);
	/* "isExplicit" and "reason" must stay the first two declared properties
	 * (slots::isExplicit, slots::reason) */
	ptdecl::NeverType::declareProperties(cls);

	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		bool isExplicit = false;
		zend_string *reason = NULL;
		if (!zp::parse<zp::Opt<zp::Bool>, zp::Opt<zp::StrOrNull>>(execute_data, isExplicit, reason)) RETURN_THROWS();
		PT_THIS.construct(isExplicit, reason);
	});

	cls.method<&NeverType::isExplicit>(sigs::isExplicit);

	cls.method(sigs::getReason, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		zval *reason = PT_THIS.reason();
		if (UNEXPECTED(reason == NULL)) RETURN_THROWS();
		RETURN_COPY(reason);
	});

	cls.method(sigs::getReferencedClasses, ntEmptyArray0);
	cls.method(sigs::getArrays, ntEmptyArray0);
	cls.method(sigs::getConstantArrays, ntEmptyArray0);
	cls.op(PT_OP_GET_CONSTANT_ARRAYS, PT_OP_LAMBDA { return pt_op_empty_array(); });
	cls.method(sigs::getObjectClassNames, ntEmptyArray0);
	cls.op(PT_OP_GET_OBJECT_CLASS_NAMES, PT_OP_LAMBDA { return pt_op_empty_array(); });
	cls.method(sigs::getObjectClassReflections, ntEmptyArray0);
	cls.method(sigs::getConstantStrings, ntEmptyArray0);

	cls.method(sigs::accepts, [](INTERNAL_FUNCTION_PARAMETERS) {
		PT_ARGS(2, 2);
		PT_RETURN_VAL(NeverType::accepts());
	});
	cls.op(PT_OP_ACCEPTS, PT_OP_LAMBDA { return NeverType::accepts(); });

	cls.method<&NeverType::isSuperTypeOf, zp::Obj>(sigs::isSuperTypeOf);
	cls.op(PT_OP_IS_SUPER_TYPE_OF, PT_OP_LAMBDA { return NeverType::isSuperTypeOf(argv); });

	cls.method(sigs::equals, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *type;
		if (!zp::parse<zp::Obj>(execute_data, type)) RETURN_THROWS();
		RETURN_BOOL(NeverType::equals(type));
	});
	cls.op(PT_OP_EQUALS, PT_OP_LAMBDA { return zv::Val::boolean(NeverType::equals(argv)); });

	cls.method(sigs::isSubTypeOf, [](INTERNAL_FUNCTION_PARAMETERS) {
		PT_ARGS(1, 1);
		PT_RETURN_VAL(NeverType::isSubTypeOf());
	});
	cls.op(PT_OP_IS_SUB_TYPE_OF, PT_OP_LAMBDA { return NeverType::isSubTypeOf(); });

	cls.method(sigs::isAcceptedBy, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *acceptingType;
		bool strictTypes;
		if (!zp::parse<zp::Obj, zp::Bool>(execute_data, acceptingType, strictTypes)) RETURN_THROWS();
		PT_RETURN_VAL(PT_THIS.isAcceptedBy(acceptingType));
	});

	cls.method(sigs::describe, [](INTERNAL_FUNCTION_PARAMETERS) {
		PT_ARGS(1, 1);
		RETURN_STRING(NeverType::describe());
	});
	cls.op(PT_OP_DESCRIBE, PT_OP_LAMBDA { return pt_op_string(NeverType::describe()); });

	cls.method(sigs::getTemplateType, ntNever2);
	cls.method(sigs::isObject, ntNo0);
	cls.method(sigs::getClassStringType, ntNever0);
	cls.method(sigs::isEnum, ntNo0);
	cls.method(sigs::canAccessProperties, ntYes0);
	cls.method(sigs::hasProperty, ntNo1);
	cls.method(sigs::getProperty, ntShouldNotHappen2);
	cls.method(sigs::getUnresolvedPropertyPrototype, ntShouldNotHappen2);
	cls.method(sigs::hasInstanceProperty, ntNo1);
	cls.method(sigs::getInstanceProperty, ntShouldNotHappen2);
	cls.method(sigs::getUnresolvedInstancePropertyPrototype, ntShouldNotHappen2);
	cls.method(sigs::hasStaticProperty, ntNo1);
	cls.method(sigs::getStaticProperty, ntShouldNotHappen2);
	cls.method(sigs::getUnresolvedStaticPropertyPrototype, ntShouldNotHappen2);
	cls.method(sigs::canCallMethods, ntYes0);
	cls.method(sigs::hasMethod, ntNo1);
	cls.method(sigs::getMethod, ntShouldNotHappen2);
	cls.method(sigs::getUnresolvedMethodPrototype, ntShouldNotHappen2);
	cls.method(sigs::canAccessConstants, ntYes0);
	cls.method(sigs::hasConstant, ntNo1);
	cls.method(sigs::getConstant, ntShouldNotHappen1);
	cls.method(sigs::isIterable, ntYes0);
	cls.method(sigs::isIterableAtLeastOnce, ntMaybe0);
	cls.op(PT_OP_IS_ITERABLE_AT_LEAST_ONCE, PT_OP_LAMBDA { return pt_op_trinary(PT_TRI_MAYBE); });
	cls.method(sigs::getArraySize, ntNever0);
	cls.method(sigs::getIterableKeyType, ntNever0);
	cls.op(PT_OP_GET_ITERABLE_KEY_TYPE, PT_OP_LAMBDA { return NeverType::never(); });
	cls.method(sigs::getFirstIterableKeyType, ntNever0);
	cls.method(sigs::getLastIterableKeyType, ntNever0);
	cls.method(sigs::getIterableValueType, ntNever0);
	cls.op(PT_OP_GET_ITERABLE_VALUE_TYPE, PT_OP_LAMBDA { return NeverType::never(); });
	cls.method(sigs::getFirstIterableValueType, ntNever0);
	cls.method(sigs::getLastIterableValueType, ntNever0);
	cls.method(sigs::isArray, ntNo0);
	cls.op(PT_OP_IS_ARRAY, PT_OP_LAMBDA { return pt_op_trinary(PT_TRI_NO); });
	cls.method(sigs::isConstantArray, ntNo0);
	cls.op(PT_OP_IS_CONSTANT_ARRAY, PT_OP_LAMBDA { return pt_op_trinary(PT_TRI_NO); });
	cls.method(sigs::isOversizedArray, ntNo0);
	cls.method(sigs::isList, ntNo0);
	cls.op(PT_OP_IS_LIST, PT_OP_LAMBDA { return pt_op_trinary(PT_TRI_NO); });
	cls.method(sigs::isOffsetAccessible, ntYes0);
	cls.method(sigs::isOffsetAccessLegal, ntYes0);
	cls.method(sigs::hasOffsetValueType, ntYes1);
	cls.method(sigs::getOffsetValueType, ntNever1);
	cls.method(sigs::setOffsetValueType, [](INTERNAL_FUNCTION_PARAMETERS) {
		PT_ARGS(2, 3);
		PT_RETURN_VAL(NeverType::error());
	});
	cls.method(sigs::setExistingOffsetValueType, ntError2);
	cls.method(sigs::unsetOffset, ntNever1);

	cls.method(sigs::getKeysArrayFiltered, [](INTERNAL_FUNCTION_PARAMETERS) {
		PT_ARGS(2, 2);
		PT_RETURN_VAL(PT_THIS.getKeysArrayFiltered());
	});

	cls.method(sigs::getKeysArray, ntNever0);
	cls.method(sigs::getValuesArray, ntNever0);
	cls.method(sigs::chunkArray, ntNever2);
	cls.method(sigs::fillKeysArray, ntNever1);
	cls.method(sigs::flipArray, ntNever0);
	cls.method(sigs::intersectKeyArray, ntNever1);
	cls.method(sigs::popArray, ntNever0);
	cls.method(sigs::reverseArray, ntNever1);
	cls.method(sigs::searchArray, [](INTERNAL_FUNCTION_PARAMETERS) {
		PT_ARGS(1, 2);
		PT_RETURN_VAL(NeverType::never());
	});
	cls.method(sigs::shiftArray, ntNever0);
	cls.method(sigs::shuffleArray, ntNever0);
	cls.method(sigs::sliceArray, ntNever3);
	cls.method(sigs::spliceArray, ntNever3);
	cls.method(sigs::truncateListToSize, ntNever1);
	cls.method(sigs::makeListMaybe, ntNever0);
	cls.method(sigs::mapValueType, ntNever1);
	cls.method(sigs::mapKeyType, ntNever1);
	cls.method(sigs::makeAllArrayKeysOptional, ntNever0);
	cls.method(sigs::changeKeyCaseArray, ntNever1);
	cls.method(sigs::filterArrayRemovingFalsey, ntNever0);
	cls.method(sigs::isCallable, ntNo0);
	cls.op(PT_OP_IS_CALLABLE, PT_OP_LAMBDA { return pt_op_trinary(PT_TRI_NO); });
	cls.method(sigs::getCallableParametersAcceptors, ntShouldNotHappen1);
	cls.method(sigs::isCloneable, ntYes0);
	cls.method(sigs::toNumber, ntThis0);
	cls.method(sigs::toBitwiseNotType, ntThis0);
	cls.method(sigs::toGetClassResultType, ntThis0);
	cls.method(sigs::toClassConstantType, ntThis1);

	cls.method<&NeverType::toObjectTypeForInstanceofCheck>(sigs::toObjectTypeForInstanceofCheck);

	cls.method(sigs::toObjectTypeForIsACheck, [](INTERNAL_FUNCTION_PARAMETERS) {
		PT_ARGS(3, 3);
		PT_RETURN_VAL(PT_THIS.toObjectTypeForInstanceofCheck());
	});

	cls.method(sigs::toAbsoluteNumber, ntThis0);
	cls.method(sigs::toString, ntThis0);
	cls.method(sigs::toInteger, ntThis0);
	cls.method(sigs::toFloat, ntThis0);
	cls.method(sigs::toArray, ntThis0);
	cls.method(sigs::toArrayKey, ntThis0);
	cls.op(PT_OP_TO_ARRAY_KEY, PT_OP_LAMBDA { return pt_op_this(self); });
	cls.method(sigs::toCoercedArgumentType, ntThis1);
	cls.method("traverse", reg::Public, 1, { reg::callableArg("cb") }, pt_type_identity_traverse_handler(), &ptret::type);
	cls.op(PT_OP_TRAVERSE, PT_OP_LAMBDA { return pt_op_traverse_identity(self); });
	cls.method(sigs::traverseSimultaneously, [](INTERNAL_FUNCTION_PARAMETERS) {
		PT_ARGS(2, 2);
		RETURN_OBJ_COPY(Z_OBJ_P(ZEND_THIS));
	});
	cls.method(sigs::isNull, ntNo0);
	cls.op(PT_OP_IS_NULL, PT_OP_LAMBDA { return pt_op_trinary(PT_TRI_NO); });
	cls.method(sigs::isConstantValue, ntNo0);
	cls.method(sigs::isConstantScalarValue, ntNo0);
	cls.op(PT_OP_IS_CONSTANT_SCALAR_VALUE, PT_OP_LAMBDA { return pt_op_trinary(PT_TRI_NO); });
	cls.method(sigs::getConstantScalarTypes, ntEmptyArray0);
	cls.method(sigs::getConstantScalarValues, ntEmptyArray0);
	cls.op(PT_OP_GET_CONSTANT_SCALAR_VALUES, PT_OP_LAMBDA { return pt_op_empty_array(); });
	cls.method(sigs::isTrue, ntNo0);
	cls.method(sigs::isFalse, ntNo0);
	cls.method(sigs::isBoolean, ntNo0);
	cls.op(PT_OP_IS_BOOLEAN, PT_OP_LAMBDA { return pt_op_trinary(PT_TRI_NO); });
	cls.method(sigs::isFloat, ntNo0);
	cls.op(PT_OP_IS_FLOAT, PT_OP_LAMBDA { return pt_op_trinary(PT_TRI_NO); });
	cls.method(sigs::isInteger, ntNo0);
	cls.op(PT_OP_IS_INTEGER, PT_OP_LAMBDA { return pt_op_trinary(PT_TRI_NO); });
	cls.method(sigs::isString, ntNo0);
	cls.op(PT_OP_IS_STRING, PT_OP_LAMBDA { return pt_op_trinary(PT_TRI_NO); });
	cls.method(sigs::isNumericString, ntNo0);
	cls.method(sigs::isDecimalIntegerString, ntNo0);
	cls.method(sigs::isNonEmptyString, ntNo0);
	cls.method(sigs::isNonFalsyString, ntNo0);
	cls.method(sigs::isLiteralString, ntNo0);
	cls.method(sigs::isLowercaseString, ntNo0);
	cls.method(sigs::isUppercaseString, ntNo0);
	cls.method(sigs::isClassString, ntNo0);
	cls.method(sigs::getClassStringObjectType, ntError0);
	cls.method(sigs::getObjectTypeOrClassStringObjectType, ntError0);
	cls.method(sigs::isVoid, ntNo0);
	cls.op(PT_OP_IS_VOID, PT_OP_LAMBDA { return pt_op_trinary(PT_TRI_NO); });
	cls.method(sigs::isScalar, ntNo0);

	cls.method(sigs::looseCompare, [](INTERNAL_FUNCTION_PARAMETERS) {
		PT_ARGS(2, 2);
		PT_RETURN_VAL(NeverType::looseCompare());
	});

	cls.method(sigs::getEnumCases, ntEmptyArray0);
	cls.method(sigs::getEnumCaseObject, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_NULL();
	});
	cls.method(sigs::exponentiate, ntThis1);
	cls.method(sigs::getFiniteTypes, ntEmptyArray0);

	cls.method<&NeverType::toPhpDocNode>(sigs::toPhpDocNode);

	cls.method(sigs::hasTemplateOrLateResolvableType, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_FALSE;
	});
	cls.op(PT_OP_HAS_TEMPLATE_OR_LATE_RESOLVABLE_TYPE, PT_OP_LAMBDA { return zv::Val::boolean(false); });

	/* the traits, in the twin's `use` order (UndecidedComparisonCompoundTypeTrait
	 * brings UndecidedComparisonTypeTrait with it); the class body above wins
	 * over every name it declares */
	ptdecl::NeverType::registerTraits(cls);

	cls.shadow(&pt_ce_never_type);
}

/* }}} */

/* {{{ helpers of the array-shape type (ConstantArrayType.cpp) */

bool pt_never_type_is_explicit(zend_object *object, bool &out)
{
	if (EXPECTED(object->ce == pt_ce_never_type)) return NeverType(object).isExplicit(out);
	zv::Val result = pt_type_call(object, PT_LC("isexplicit"), 0, NULL);
	if (UNEXPECTED(result.isUndef())) return false;
	out = zend_is_true(result.raw());
	return true;
}

/* }}} */
