/*
 * PHPStanTurbo\OversizedArrayType — native implementation of
 * PHPStan\Type\Accessory\OversizedArrayType.
 *
 * The `$this->method()` calls the twin makes (equals(), isSubTypeOf()) go
 * through the object's class entry — a subclass may have overridden them —
 * with a direct C++ call when the object is exactly an OversizedArrayType.
 */

#include "TypeTraits.h"
#include "generated/OversizedArrayType.h"

namespace sigs = ptdecl::OversizedArrayType::sig;

zend_class_entry *pt_ce_oversized_array_type = nullptr;

namespace phpstanturbo {

/* Mirrors PHPStan\Type\Accessory\OversizedArrayType. */
class OversizedArrayType
{
public:
	explicit OversizedArrayType(zend_object *self) : self(self) {}

	/* the CompoundType callback; else the array-and-non-empty verdict as
	 * a fresh result; UNDEF = pending exception */
	zv::Val accepts(zval *type, bool strictTypes) const
	{
		bool compound;
		if (UNEXPECTED(!pt_type_instanceof(type, PT_CLASS_COMPOUND_TYPE, compound))) return zv::Val();
		if (compound) {
			zv::Args args{self, strictTypes};
			return pt_type_call(Z_OBJ_P(type), PT_LC("isacceptedby"), 2, args);
		}
		zend_long value = arrayAnd(type, PT_LC("isiterableatleastonce"));
		if (UNEXPECTED(value < 0)) return zv::Val();
		return pt_type_new_accepts_result(value);
	}

	/* yes for an equal type; the CompoundType callback; else the
	 * array-and-oversized verdict; UNDEF = pending exception */
	zv::Val isSuperTypeOf(zval *type) const
	{
		bool equal;
		if (UNEXPECTED(!thisEquals(type, equal))) return zv::Val();
		if (equal) return pt_type_is_super_type_of_result(PT_TRI_YES);
		bool compound;
		if (UNEXPECTED(!pt_type_instanceof(type, PT_CLASS_COMPOUND_TYPE, compound))) return zv::Val();
		if (compound) {
			zval selfZv;
			ZVAL_OBJ(&selfZv, self);
			return pt_type_op(Z_OBJ_P(type), PT_OP_IS_SUB_TYPE_OF, 1, &selfZv);
		}
		zend_long value = arrayAnd(type, PT_LC("isoversizedarray"));
		if (UNEXPECTED(value < 0)) return zv::Val();
		return pt_type_new_is_super_type_of_result(value);
	}

	/* the union/intersection callback; else the other type's
	 * array-and-oversized verdict, and'ed with maybe unless it is an
	 * OversizedArrayType; UNDEF = pending exception */
	zv::Val isSubTypeOf(zval *otherType) const
	{
		bool unionOrIntersection;
		if (UNEXPECTED(!isUnionOrIntersection(otherType, unionOrIntersection))) return zv::Val();
		if (unionOrIntersection) {
			zval selfZv;
			ZVAL_OBJ(&selfZv, self);
			return pt_type_op(Z_OBJ_P(otherType), PT_OP_IS_SUPER_TYPE_OF, 1, &selfZv);
		}
		zend_long value = arrayAnd(otherType, PT_LC("isoversizedarray"));
		if (UNEXPECTED(value < 0)) return zv::Val();
		if (!instanceof_function(Z_OBJCE_P(otherType), pt_ce_oversized_array_type)) {
			value = pt_trinary_and(value, PT_TRI_MAYBE);
		}
		return pt_type_new_is_super_type_of_result(value);
	}

	/* $this->isSubTypeOf($acceptingType)->toAcceptsResult() */
	zv::Val isAcceptedBy(zval *acceptingType) const
	{
		return pt_type_sub_type_to_accepts_result(isExact() ? isSubTypeOf(acceptingType) : pt_type_op(self, PT_OP_IS_SUB_TYPE_OF, 1, acceptingType));
	}

	/* $type instanceof self */
	static bool equals(zval *type) { return instanceof_function(Z_OBJCE_P(type), pt_ce_oversized_array_type); }

	/* IntegerRangeType::fromInterval(0, null) */
	static zv::Val getArraySize() { return pt_integer_range_from_interval(NullableLong::of(0), NullableLong::null(), 0); }

	/* new ConstantIntegerType(1) */
	static zv::Val toInteger() { return pt_type_new_constant_integer(1); }

	/* new ConstantFloatType(1.0) */
	static zv::Val toFloat() { return pt_type_new_constant_float(1.0); }

	/* new ArrayType(new MixedType(), new MixedType()) */
	static zv::Val getDefaultBaseType()
	{
		zv::Val keyType = pt_type_new_mixed_type();
		zv::Val itemType = pt_type_new_mixed_type();
		if (UNEXPECTED(keyType.isUndef() || itemType.isUndef())) return zv::Val();
		zval result;
		if (UNEXPECTED(!pt_array_type_new(&result, keyType.raw(), itemType.raw()))) return zv::Val();
		return zv::Val::adopt(result);
	}

	/* new IdentifierTypeNode('') — no PHPDoc representation */
	static zv::Val toPhpDocNode()
	{
		zv::Val name = zv::Val::string("", 0);
		return pt_type_new(PT_CLASS_IDENTIFIER_TYPE_NODE, 1, name.raw());
	}

private:
	zend_object *self;

	bool isExact() const { return self->ce == pt_ce_oversized_array_type; }

	/* $this->equals($type) — through the object's class; false = pending
	 * exception */
	[[nodiscard]] bool thisEquals(zval *type, bool &out) const
	{
		if (EXPECTED(isExact())) {
			out = equals(type);
			return true;
		}
		return pt_type_op_bool(self, PT_OP_EQUALS, 1, type, out);
	}

	/* TrinaryLogic::and(): the minimum */

	/* $type->isArray()->and($type-><second>()); -1 = pending exception */
	[[nodiscard]] static zend_long arrayAnd(zval *type, const char *secondLcname, size_t secondLen)
	{
		zend_long isArray = pt_type_op_trinary(Z_OBJ_P(type), PT_OP_IS_ARRAY, 0, NULL);
		if (UNEXPECTED(isArray < 0)) return -1;
		zend_long second = pt_type_call_trinary(Z_OBJ_P(type), secondLcname, secondLen, 0, NULL);
		if (UNEXPECTED(second < 0)) return -1;
		return pt_trinary_and(isArray, second);
	}

	/* $type instanceof UnionType || $type instanceof IntersectionType;
	 * false = pending exception */
	[[nodiscard]] static bool isUnionOrIntersection(zval *type, bool &out)
	{
		if (UNEXPECTED(!pt_union_type_instanceof(type, out))) return false;
		if (out) return true;
		return pt_intersection_type_instanceof(type, out);
	}
};

} // namespace phpstanturbo

using phpstanturbo::OversizedArrayType;

bool pt_oversized_array_type_new(zval *out)
{
	return object_init_ex(out, pt_ce_oversized_array_type) == SUCCESS;
}

/* {{{ engine ABI glue: parameter parsing + registration */

#define PT_THIS OversizedArrayType(Z_OBJ_P(ZEND_THIS))

/* the trivial bodies the twin repeats (one handler per body and arity;
 * each method is still declared exactly once, at its registration line) */

static void ZEND_FASTCALL oaEmptyArray0(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	RETURN_EMPTY_ARRAY();
}

static void ZEND_FASTCALL oaNo0(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	PT_RETURN_TRINARY(PT_TRI_NO);
}

static void ZEND_FASTCALL oaMaybe0(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	PT_RETURN_TRINARY(PT_TRI_MAYBE);
}

static void ZEND_FASTCALL oaMaybe1(INTERNAL_FUNCTION_PARAMETERS)
{
	PT_ARGS(1, 1);
	PT_RETURN_TRINARY(PT_TRI_MAYBE);
}

static void ZEND_FASTCALL oaYes0(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	PT_RETURN_TRINARY(PT_TRI_YES);
}

static void ZEND_FASTCALL oaThis0(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	RETURN_OBJ_COPY(Z_OBJ_P(ZEND_THIS));
}

static void ZEND_FASTCALL oaThis1(INTERNAL_FUNCTION_PARAMETERS)
{
	PT_ARGS(1, 1);
	RETURN_OBJ_COPY(Z_OBJ_P(ZEND_THIS));
}

static void ZEND_FASTCALL oaThis2(INTERNAL_FUNCTION_PARAMETERS)
{
	PT_ARGS(2, 2);
	RETURN_OBJ_COPY(Z_OBJ_P(ZEND_THIS));
}

static void ZEND_FASTCALL oaThis3(INTERNAL_FUNCTION_PARAMETERS)
{
	PT_ARGS(3, 3);
	RETURN_OBJ_COPY(Z_OBJ_P(ZEND_THIS));
}

static void ZEND_FASTCALL oaError0(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	PT_RETURN_VAL(pt_type_new_error_type());
}

static void ZEND_FASTCALL oaError1(INTERNAL_FUNCTION_PARAMETERS)
{
	PT_ARGS(1, 1);
	PT_RETURN_VAL(pt_type_new_error_type());
}

static void ZEND_FASTCALL oaMixed0(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	PT_RETURN_VAL(pt_type_new_mixed_type());
}

static void ZEND_FASTCALL oaMixed1(INTERNAL_FUNCTION_PARAMETERS)
{
	PT_ARGS(1, 1);
	PT_RETURN_VAL(pt_type_new_mixed_type());
}

/* new BooleanType() — the shadowing class */
static void ZEND_FASTCALL oaBoolean0(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	zval result;
	if (UNEXPECTED(!pt_boolean_type_new(&result))) RETURN_THROWS();
	RETURN_COPY_VALUE(&result);
}

/* (Type $type) → a Type */
static void pt_oa_one_type(INTERNAL_FUNCTION_PARAMETERS, zv::Val (OversizedArrayType::*method)(zval *) const)
{
	zval *type;
	if (!zp::parse<zp::Obj>(execute_data, type)) RETURN_THROWS();
	PT_RETURN_VAL((PT_THIS.*method)(type));
}

void pt_register_oversized_array_type()
{
	reg::Class cls("PHPStan\\Type\\Accessory\\OversizedArrayType");
	ptdecl::OversizedArrayType::declareClass(cls);
	ptdecl::OversizedArrayType::declareProperties(cls);

	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
	});

	cls.method(sigs::getReferencedClasses, oaEmptyArray0);
	cls.op(PT_OP_GET_REFERENCED_CLASSES, PT_OP_LAMBDA { return pt_op_empty_array(); });
	cls.method(sigs::getObjectClassNames, oaEmptyArray0);
	cls.op(PT_OP_GET_OBJECT_CLASS_NAMES, PT_OP_LAMBDA { return pt_op_empty_array(); });
	cls.method(sigs::getObjectClassReflections, oaEmptyArray0);
	cls.op(PT_OP_GET_OBJECT_CLASS_REFLECTIONS, PT_OP_LAMBDA { return pt_op_empty_array(); });
	cls.method(sigs::getArrays, oaEmptyArray0);
	cls.method(sigs::getConstantArrays, oaEmptyArray0);
	cls.op(PT_OP_GET_CONSTANT_ARRAYS, PT_OP_LAMBDA { return pt_op_empty_array(); });
	cls.method(sigs::getConstantStrings, oaEmptyArray0);

	cls.method<&OversizedArrayType::accepts, zp::Obj, zp::Bool>(sigs::accepts);
	cls.op(PT_OP_ACCEPTS, PT_OP_LAMBDA { return OversizedArrayType(self).accepts(argv, (Z_TYPE(argv[1]) == IS_TRUE)); });

	cls.method(sigs::isSuperTypeOf, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_oa_one_type(INTERNAL_FUNCTION_PARAM_PASSTHRU, &OversizedArrayType::isSuperTypeOf);
	});
	cls.op<PT_OP_IS_SUPER_TYPE_OF, &OversizedArrayType::isSuperTypeOf>();

	cls.method(sigs::isSubTypeOf, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_oa_one_type(INTERNAL_FUNCTION_PARAM_PASSTHRU, &OversizedArrayType::isSubTypeOf);
	});
	cls.op<PT_OP_IS_SUB_TYPE_OF, &OversizedArrayType::isSubTypeOf>();

	cls.method(sigs::isAcceptedBy, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *acceptingType;
		bool strictTypes;
		if (!zp::parse<zp::Obj, zp::Bool>(execute_data, acceptingType, strictTypes)) RETURN_THROWS();
		PT_RETURN_VAL(PT_THIS.isAcceptedBy(acceptingType));
	});

	cls.method(sigs::equals, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *type;
		if (!zp::parse<zp::Obj>(execute_data, type)) RETURN_THROWS();
		RETURN_BOOL(OversizedArrayType::equals(type));
	});
	cls.op(PT_OP_EQUALS, PT_OP_LAMBDA { return zv::Val::boolean(OversizedArrayType::equals(argv)); });

	cls.method(sigs::describe, [](INTERNAL_FUNCTION_PARAMETERS) {
		PT_ARGS(1, 1);
		RETURN_STRINGL("oversized-array", sizeof("oversized-array") - 1);
	});
	cls.op(PT_OP_DESCRIBE, PT_OP_LAMBDA { return zv::Val::string("oversized-array", sizeof("oversized-array") - 1); });

	cls.method(sigs::isOffsetAccessible, oaYes0);
	cls.method(sigs::isOffsetAccessLegal, oaYes0);
	cls.method(sigs::hasOffsetValueType, oaMaybe1);
	cls.op(PT_OP_HAS_OFFSET_VALUE_TYPE, PT_OP_LAMBDA { return pt_op_trinary(PT_TRI_MAYBE); });
	cls.method(sigs::getOffsetValueType, oaMixed1);
	cls.op(PT_OP_GET_OFFSET_VALUE_TYPE, PT_OP_LAMBDA { return pt_type_new_mixed_type(); });
	cls.method(sigs::setOffsetValueType, [](INTERNAL_FUNCTION_PARAMETERS) {
		PT_ARGS(2, 3);
		RETURN_OBJ_COPY(Z_OBJ_P(ZEND_THIS));
	});
	cls.method(sigs::setExistingOffsetValueType, oaThis2);
	cls.method(sigs::unsetOffset, oaError1);

	cls.method(sigs::getKeysArrayFiltered, [](INTERNAL_FUNCTION_PARAMETERS) {
		PT_ARGS(2, 2);
		/* $this->getKeysArray() — through the object's class */
		PT_RETURN_VAL(pt_type_call(Z_OBJ_P(ZEND_THIS), PT_LC("getkeysarray"), 0, NULL));
	});

	cls.method(sigs::getKeysArray, oaThis0);
	cls.method(sigs::getValuesArray, oaThis0);
	cls.method(sigs::chunkArray, oaThis2);
	cls.method(sigs::fillKeysArray, oaThis1);
	cls.method(sigs::flipArray, oaThis0);
	cls.method(sigs::intersectKeyArray, oaThis1);
	cls.method(sigs::popArray, oaThis0);
	cls.method(sigs::reverseArray, oaThis1);
	cls.method(sigs::searchArray, [](INTERNAL_FUNCTION_PARAMETERS) {
		PT_ARGS(1, 2);
		PT_RETURN_VAL(pt_type_new_mixed_type());
	});
	cls.method(sigs::shiftArray, oaThis0);
	cls.method(sigs::shuffleArray, oaThis0);
	cls.method(sigs::sliceArray, oaThis3);
	cls.method(sigs::spliceArray, oaThis3);
	cls.method(sigs::truncateListToSize, oaThis1);
	cls.method(sigs::makeListMaybe, oaThis0);
	cls.method(sigs::mapValueType, oaThis1);
	cls.method(sigs::mapKeyType, oaThis1);
	cls.method(sigs::makeAllArrayKeysOptional, oaThis0);
	cls.method(sigs::changeKeyCaseArray, oaThis1);
	cls.method(sigs::filterArrayRemovingFalsey, oaThis0);
	cls.method(sigs::isIterable, oaYes0);
	cls.method(sigs::isIterableAtLeastOnce, oaMaybe0);
	cls.op(PT_OP_IS_ITERABLE_AT_LEAST_ONCE, PT_OP_LAMBDA { return pt_op_trinary(PT_TRI_MAYBE); });

	cls.method<&OversizedArrayType::getArraySize>(sigs::getArraySize);

	cls.method(sigs::getIterableKeyType, oaMixed0);
	cls.op(PT_OP_GET_ITERABLE_KEY_TYPE, PT_OP_LAMBDA { return pt_type_new_mixed_type(); });
	cls.method(sigs::getFirstIterableKeyType, oaMixed0);
	cls.method(sigs::getLastIterableKeyType, oaMixed0);
	cls.method(sigs::getIterableValueType, oaMixed0);
	cls.op(PT_OP_GET_ITERABLE_VALUE_TYPE, PT_OP_LAMBDA { return pt_type_new_mixed_type(); });
	cls.method(sigs::getFirstIterableValueType, oaMixed0);
	cls.method(sigs::getLastIterableValueType, oaMixed0);
	cls.method(sigs::isArray, oaYes0);
	cls.op(PT_OP_IS_ARRAY, PT_OP_LAMBDA { return pt_op_trinary(PT_TRI_YES); });
	cls.method(sigs::isConstantArray, oaMaybe0);
	cls.op(PT_OP_IS_CONSTANT_ARRAY, PT_OP_LAMBDA { return pt_op_trinary(PT_TRI_MAYBE); });
	cls.method(sigs::isOversizedArray, oaYes0);
	cls.method(sigs::isList, oaMaybe0);
	cls.op(PT_OP_IS_LIST, PT_OP_LAMBDA { return pt_op_trinary(PT_TRI_MAYBE); });
	cls.method(sigs::isNull, oaNo0);
	cls.op(PT_OP_IS_NULL, PT_OP_LAMBDA { return pt_op_trinary(PT_TRI_NO); });
	cls.method(sigs::isConstantValue, oaMaybe0);
	cls.method(sigs::isConstantScalarValue, oaNo0);
	cls.op(PT_OP_IS_CONSTANT_SCALAR_VALUE, PT_OP_LAMBDA { return pt_op_trinary(PT_TRI_NO); });
	cls.method(sigs::getConstantScalarTypes, oaEmptyArray0);
	cls.method(sigs::getConstantScalarValues, oaEmptyArray0);
	cls.op(PT_OP_GET_CONSTANT_SCALAR_VALUES, PT_OP_LAMBDA { return pt_op_empty_array(); });
	cls.method(sigs::isTrue, oaNo0);
	cls.method(sigs::isFalse, oaNo0);
	cls.method(sigs::isBoolean, oaNo0);
	cls.op(PT_OP_IS_BOOLEAN, PT_OP_LAMBDA { return pt_op_trinary(PT_TRI_NO); });
	cls.method(sigs::isFloat, oaNo0);
	cls.op(PT_OP_IS_FLOAT, PT_OP_LAMBDA { return pt_op_trinary(PT_TRI_NO); });
	cls.method(sigs::isInteger, oaNo0);
	cls.op(PT_OP_IS_INTEGER, PT_OP_LAMBDA { return pt_op_trinary(PT_TRI_NO); });
	cls.method(sigs::isString, oaNo0);
	cls.op(PT_OP_IS_STRING, PT_OP_LAMBDA { return pt_op_trinary(PT_TRI_NO); });
	cls.method(sigs::isNumericString, oaNo0);
	cls.method(sigs::isDecimalIntegerString, oaNo0);
	cls.method(sigs::isNonEmptyString, oaNo0);
	cls.method(sigs::isNonFalsyString, oaNo0);
	cls.method(sigs::isLiteralString, oaNo0);
	cls.method(sigs::isLowercaseString, oaNo0);
	cls.method(sigs::isClassString, oaNo0);
	cls.method(sigs::isUppercaseString, oaNo0);
	cls.method(sigs::getClassStringObjectType, oaError0);
	cls.method(sigs::getObjectTypeOrClassStringObjectType, oaError0);
	cls.method(sigs::isVoid, oaNo0);
	cls.op(PT_OP_IS_VOID, PT_OP_LAMBDA { return pt_op_trinary(PT_TRI_NO); });
	cls.method(sigs::isScalar, oaNo0);

	cls.method(sigs::looseCompare, [](INTERNAL_FUNCTION_PARAMETERS) {
		PT_ARGS(2, 2);
		zval result;
		if (UNEXPECTED(!pt_boolean_type_new(&result))) RETURN_THROWS();
		RETURN_COPY_VALUE(&result);
	});

	cls.method(sigs::toNumber, oaError0);
	cls.method(sigs::toBitwiseNotType, oaError0);
	cls.method(sigs::toAbsoluteNumber, oaError0);
	cls.method(sigs::toBoolean, oaBoolean0);

	cls.method<&OversizedArrayType::toInteger>(sigs::toInteger);

	cls.method<&OversizedArrayType::toFloat>(sigs::toFloat);

	cls.method(sigs::toString, oaError0);
	cls.method(sigs::toArray, oaMixed0);
	cls.method(sigs::toArrayKey, oaError0);
	cls.op(PT_OP_TO_ARRAY_KEY, PT_OP_LAMBDA { return pt_type_new_error_type(); });
	cls.method(sigs::toCoercedArgumentType, oaThis1);
	cls.method("traverse", reg::Public, 1, { reg::callableArg("cb") }, pt_type_identity_traverse_handler(), &ptret::type);
	cls.op(PT_OP_TRAVERSE, PT_OP_LAMBDA { return pt_op_traverse_identity(self); });
	cls.method(sigs::traverseSimultaneously, oaThis2);
	cls.method(sigs::exponentiate, oaError1);
	cls.method(sigs::getFiniteTypes, oaEmptyArray0);

	cls.method<&OversizedArrayType::getDefaultBaseType>(sigs::getDefaultBaseType);

	cls.method<&OversizedArrayType::toPhpDocNode>(sigs::toPhpDocNode);

	cls.method(sigs::hasTemplateOrLateResolvableType, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_FALSE;
	});
	cls.op(PT_OP_HAS_TEMPLATE_OR_LATE_RESOLVABLE_TYPE, PT_OP_LAMBDA { return zv::Val::boolean(false); });

	/* the traits, in the twin's `use` order (UndecidedComparisonCompoundTypeTrait
	 * brings UndecidedComparisonTypeTrait with it); the class body above wins
	 * over every name it declares */
	ptdecl::OversizedArrayType::registerTraits(cls);

	cls.shadow(&pt_ce_oversized_array_type);
}

/* }}} */
