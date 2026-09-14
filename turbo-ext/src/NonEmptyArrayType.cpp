/*
 * PHPStanTurbo\NonEmptyArrayType — native implementation of
 * PHPStan\Type\Accessory\NonEmptyArrayType.
 *
 * The `$this->method()` calls the twin makes (equals(), isSubTypeOf()) go
 * through the object's class entry — a subclass may have overridden them —
 * with a direct C++ call when the object is exactly a NonEmptyArrayType.
 */

#include "TypeTraits.h"
#include "generated/NonEmptyArrayType.h"

namespace sigs = ptdecl::NonEmptyArrayType::sig;

zend_class_entry *pt_ce_non_empty_array_type = nullptr;

namespace phpstanturbo {

/* Mirrors PHPStan\Type\Accessory\NonEmptyArrayType. */
class NonEmptyArrayType
{
public:
	explicit NonEmptyArrayType(zend_object *self) : self(self) {}

	/* yes for a non-empty array; the CompoundType callback; else the
	 * array-and-non-empty verdict as a fresh result; UNDEF = pending
	 * exception */
	zv::Val accepts(zval *type, bool strictTypes) const
	{
		zend_long isNonEmptyArray = arrayAndAtLeastOnce(type);
		if (UNEXPECTED(isNonEmptyArray < 0)) return zv::Val();
		if (isNonEmptyArray == PT_TRI_YES) return pt_type_accepts_result(PT_TRI_YES);
		bool compound;
		if (UNEXPECTED(!pt_type_instanceof(type, PT_CLASS_COMPOUND_TYPE, compound))) return zv::Val();
		if (compound) {
			zv::Args args{self, strictTypes};
			return pt_type_call(Z_OBJ_P(type), PT_LC("isacceptedby"), 2, args);
		}
		return pt_type_new_accepts_result(isNonEmptyArray);
	}

	/* yes for an equal type; the CompoundType callback; else the
	 * array-and-non-empty verdict; UNDEF = pending exception */
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
		zend_long value = arrayAndAtLeastOnce(type);
		if (UNEXPECTED(value < 0)) return zv::Val();
		return pt_type_new_is_super_type_of_result(value);
	}

	/* the union/intersection callback; else the other type's
	 * array-and-non-empty verdict, and'ed with maybe unless it is a
	 * NonEmptyArrayType; UNDEF = pending exception */
	zv::Val isSubTypeOf(zval *otherType) const
	{
		bool unionOrIntersection;
		if (UNEXPECTED(!isUnionOrIntersection(otherType, unionOrIntersection))) return zv::Val();
		if (unionOrIntersection) {
			zval selfZv;
			ZVAL_OBJ(&selfZv, self);
			return pt_type_op(Z_OBJ_P(otherType), PT_OP_IS_SUPER_TYPE_OF, 1, &selfZv);
		}
		zend_long value = arrayAndAtLeastOnce(otherType);
		if (UNEXPECTED(value < 0)) return zv::Val();
		if (!instanceof_function(Z_OBJCE_P(otherType), pt_ce_non_empty_array_type)) {
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
	static bool equals(zval *type) { return instanceof_function(Z_OBJCE_P(type), pt_ce_non_empty_array_type); }

	/* new ConstantBooleanType(false) for an empty array, new BooleanType()
	 * otherwise; UNDEF = pending exception */
	static zv::Val looseCompare(zval *type)
	{
		zend_long isArray = pt_type_op_trinary(Z_OBJ_P(type), PT_OP_IS_ARRAY, 0, NULL);
		if (UNEXPECTED(isArray < 0)) return zv::Val();
		if (isArray == PT_TRI_YES) {
			zend_long atLeastOnce = pt_type_op_trinary(Z_OBJ_P(type), PT_OP_IS_ITERABLE_AT_LEAST_ONCE, 0, NULL);
			if (UNEXPECTED(atLeastOnce < 0)) return zv::Val();
			if (atLeastOnce == PT_TRI_NO) {
				zval result;
				if (UNEXPECTED(!pt_constant_boolean_type_new(&result, false))) return zv::Val();
				return zv::Val::adopt(result);
			}
		}
		return pt_val_of<pt_boolean_type_new>();
	}

	/* $this for a slice from offset 0 with a null length, mixed otherwise;
	 * UNDEF = pending exception */
	zv::Val sliceArray(zval *offsetType, zval *lengthType) const
	{
		zend_long offsetIsZero = zeroIsSuperTypeOf(offsetType);
		if (UNEXPECTED(offsetIsZero < 0)) return zv::Val();
		if (offsetIsZero == PT_TRI_YES) {
			zend_long lengthIsNull = pt_type_op_trinary(Z_OBJ_P(lengthType), PT_OP_IS_NULL, 0, NULL);
			if (UNEXPECTED(lengthIsNull < 0)) return zv::Val();
			if (lengthIsNull == PT_TRI_YES) return thisValue();
		}
		return pt_type_new_mixed_type();
	}

	/* $this for a zero length or a non-empty replacement, mixed otherwise;
	 * UNDEF = pending exception */
	zv::Val spliceArray(zval *lengthType, zval *replacementType) const
	{
		zend_long lengthIsZero = zeroIsSuperTypeOf(lengthType);
		if (UNEXPECTED(lengthIsZero < 0)) return zv::Val();
		if (lengthIsZero == PT_TRI_YES) return thisValue();
		zv::Val replacementArray = pt_type_call(Z_OBJ_P(replacementType), PT_LC("toarray"), 0, NULL);
		if (UNEXPECTED(replacementArray.isUndef())) return zv::Val();
		if (UNEXPECTED(!zv::Ref(replacementArray.raw()).isObject())) {
			zend_type_error("phpstan_turbo: toArray() must return %s", ptcls::type);
			return zv::Val();
		}
		zend_long atLeastOnce = pt_type_op_trinary(Z_OBJ_P(replacementArray.raw()), PT_OP_IS_ITERABLE_AT_LEAST_ONCE, 0, NULL);
		if (UNEXPECTED(atLeastOnce < 0)) return zv::Val();
		if (atLeastOnce == PT_TRI_YES) return thisValue();
		return pt_type_new_mixed_type();
	}

	/* IntegerRangeType::fromInterval(1, null) */
	static zv::Val getArraySize() { return pt_integer_range_from_interval(NullableLong::of(1), NullableLong::null(), 0); }

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

	/* new IdentifierTypeNode('non-empty-array') */
	static zv::Val toPhpDocNode()
	{
		zv::Val name = zv::Val::string("non-empty-array", sizeof("non-empty-array") - 1);
		return pt_type_new(PT_CLASS_IDENTIFIER_TYPE_NODE, 1, name.raw());
	}

private:
	zend_object *self;

	bool isExact() const { return self->ce == pt_ce_non_empty_array_type; }

	zv::Val thisValue() const { return pt_this_value(self); }

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

	/* $type->isArray()->and($type->isIterableAtLeastOnce()); -1 = pending
	 * exception */
	[[nodiscard]] static zend_long arrayAndAtLeastOnce(zval *type)
	{
		zend_long isArray = pt_type_op_trinary(Z_OBJ_P(type), PT_OP_IS_ARRAY, 0, NULL);
		if (UNEXPECTED(isArray < 0)) return -1;
		zend_long atLeastOnce = pt_type_op_trinary(Z_OBJ_P(type), PT_OP_IS_ITERABLE_AT_LEAST_ONCE, 0, NULL);
		if (UNEXPECTED(atLeastOnce < 0)) return -1;
		return pt_trinary_and(isArray, atLeastOnce);
	}

	/* $type instanceof UnionType || $type instanceof IntersectionType;
	 * false = pending exception */
	[[nodiscard]] static bool isUnionOrIntersection(zval *type, bool &out)
	{
		if (UNEXPECTED(!pt_union_type_instanceof(type, out))) return false;
		if (out) return true;
		return pt_intersection_type_instanceof(type, out);
	}

	/* (new ConstantIntegerType(0))->isSuperTypeOf($type)'s trinary; -1 =
	 * pending exception */
	static zend_long zeroIsSuperTypeOf(zval *type)
	{
		zv::Val zero = pt_type_new_constant_integer(0);
		if (UNEXPECTED(zero.isUndef())) return -1;
		zv::Val result = pt_type_op(Z_OBJ_P(zero.raw()), PT_OP_IS_SUPER_TYPE_OF, 1, type);
		if (UNEXPECTED(result.isUndef())) return -1;
		return pt_type_result_trinary(result.raw());
	}
};

} // namespace phpstanturbo

using phpstanturbo::NonEmptyArrayType;

bool pt_non_empty_array_type_new(zval *out)
{
	return object_init_ex(out, pt_ce_non_empty_array_type) == SUCCESS;
}

/* {{{ engine ABI glue: parameter parsing + registration */

#define PT_THIS NonEmptyArrayType(Z_OBJ_P(ZEND_THIS))

/* the trivial bodies the twin repeats (one handler per body and arity;
 * each method is still declared exactly once, at its registration line) */

static void ZEND_FASTCALL neaEmptyArray0(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	RETURN_EMPTY_ARRAY();
}

static void ZEND_FASTCALL neaNo0(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	PT_RETURN_TRINARY(PT_TRI_NO);
}

static void ZEND_FASTCALL neaMaybe0(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	PT_RETURN_TRINARY(PT_TRI_MAYBE);
}

static void ZEND_FASTCALL neaMaybe1(INTERNAL_FUNCTION_PARAMETERS)
{
	PT_ARGS(1, 1);
	PT_RETURN_TRINARY(PT_TRI_MAYBE);
}

static void ZEND_FASTCALL neaYes0(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	PT_RETURN_TRINARY(PT_TRI_YES);
}

static void ZEND_FASTCALL neaThis0(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	RETURN_OBJ_COPY(Z_OBJ_P(ZEND_THIS));
}

static void ZEND_FASTCALL neaThis1(INTERNAL_FUNCTION_PARAMETERS)
{
	PT_ARGS(1, 1);
	RETURN_OBJ_COPY(Z_OBJ_P(ZEND_THIS));
}

static void ZEND_FASTCALL neaThis2(INTERNAL_FUNCTION_PARAMETERS)
{
	PT_ARGS(2, 2);
	RETURN_OBJ_COPY(Z_OBJ_P(ZEND_THIS));
}

static void ZEND_FASTCALL neaError0(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	PT_RETURN_VAL(pt_type_new_error_type());
}

static void ZEND_FASTCALL neaError1(INTERNAL_FUNCTION_PARAMETERS)
{
	PT_ARGS(1, 1);
	PT_RETURN_VAL(pt_type_new_error_type());
}

static void ZEND_FASTCALL neaError2(INTERNAL_FUNCTION_PARAMETERS)
{
	PT_ARGS(2, 2);
	PT_RETURN_VAL(pt_type_new_error_type());
}

static void ZEND_FASTCALL neaMixed0(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	PT_RETURN_VAL(pt_type_new_mixed_type());
}

static void ZEND_FASTCALL neaMixed1(INTERNAL_FUNCTION_PARAMETERS)
{
	PT_ARGS(1, 1);
	PT_RETURN_VAL(pt_type_new_mixed_type());
}

void pt_register_non_empty_array_type()
{
	reg::Class cls("PHPStan\\Type\\Accessory\\NonEmptyArrayType");
	ptdecl::NonEmptyArrayType::declareClass(cls);
	ptdecl::NonEmptyArrayType::declareProperties(cls);

	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
	});

	cls.method(sigs::getReferencedClasses, neaEmptyArray0);
	cls.op(PT_OP_GET_REFERENCED_CLASSES, PT_OP_LAMBDA { return pt_op_empty_array(); });
	cls.method(sigs::getObjectClassNames, neaEmptyArray0);
	cls.op(PT_OP_GET_OBJECT_CLASS_NAMES, PT_OP_LAMBDA { return pt_op_empty_array(); });
	cls.method(sigs::getObjectClassReflections, neaEmptyArray0);
	cls.op(PT_OP_GET_OBJECT_CLASS_REFLECTIONS, PT_OP_LAMBDA { return pt_op_empty_array(); });
	cls.method(sigs::getArrays, neaEmptyArray0);
	cls.method(sigs::getConstantArrays, neaEmptyArray0);
	cls.op(PT_OP_GET_CONSTANT_ARRAYS, PT_OP_LAMBDA { return pt_op_empty_array(); });
	cls.method(sigs::getConstantStrings, neaEmptyArray0);

	cls.method<&NonEmptyArrayType::accepts, zp::Obj, zp::Bool>(sigs::accepts);
	cls.op(PT_OP_ACCEPTS, PT_OP_LAMBDA { return NonEmptyArrayType(self).accepts(argv, (Z_TYPE(argv[1]) == IS_TRUE)); });

	cls.method<&NonEmptyArrayType::isSuperTypeOf, zp::Obj>(sigs::isSuperTypeOf);
	cls.op<PT_OP_IS_SUPER_TYPE_OF, &NonEmptyArrayType::isSuperTypeOf>();

	cls.method<&NonEmptyArrayType::isSubTypeOf, zp::Obj>(sigs::isSubTypeOf);
	cls.op<PT_OP_IS_SUB_TYPE_OF, &NonEmptyArrayType::isSubTypeOf>();

	cls.method(sigs::isAcceptedBy, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *acceptingType;
		bool strictTypes;
		if (!zp::parse<zp::Obj, zp::Bool>(execute_data, acceptingType, strictTypes)) RETURN_THROWS();
		PT_RETURN_VAL(PT_THIS.isAcceptedBy(acceptingType));
	});

	cls.method(sigs::equals, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *type;
		if (!zp::parse<zp::Obj>(execute_data, type)) RETURN_THROWS();
		RETURN_BOOL(NonEmptyArrayType::equals(type));
	});
	cls.op(PT_OP_EQUALS, PT_OP_LAMBDA { return zv::Val::boolean(NonEmptyArrayType::equals(argv)); });

	cls.method(sigs::describe, [](INTERNAL_FUNCTION_PARAMETERS) {
		PT_ARGS(1, 1);
		RETURN_STRINGL("non-empty-array", sizeof("non-empty-array") - 1);
	});
	cls.op(PT_OP_DESCRIBE, PT_OP_LAMBDA { return zv::Val::string("non-empty-array", sizeof("non-empty-array") - 1); });

	cls.method(sigs::isOffsetAccessible, neaYes0);
	cls.method(sigs::isOffsetAccessLegal, neaYes0);
	cls.method(sigs::hasOffsetValueType, neaMaybe1);
	cls.op(PT_OP_HAS_OFFSET_VALUE_TYPE, PT_OP_LAMBDA { return pt_op_trinary(PT_TRI_MAYBE); });
	cls.method(sigs::getOffsetValueType, neaMixed1);
	cls.op(PT_OP_GET_OFFSET_VALUE_TYPE, PT_OP_LAMBDA { return pt_type_new_mixed_type(); });
	cls.method(sigs::setOffsetValueType, [](INTERNAL_FUNCTION_PARAMETERS) {
		PT_ARGS(2, 3);
		RETURN_OBJ_COPY(Z_OBJ_P(ZEND_THIS));
	});
	cls.method(sigs::setExistingOffsetValueType, neaThis2);
	cls.method(sigs::unsetOffset, neaError1);
	cls.method(sigs::getKeysArrayFiltered, neaError2);
	cls.method(sigs::getKeysArray, neaThis0);
	cls.method(sigs::getValuesArray, neaThis0);
	cls.method(sigs::chunkArray, neaThis2);
	cls.method(sigs::fillKeysArray, neaThis1);
	cls.method(sigs::flipArray, neaThis0);
	cls.method(sigs::intersectKeyArray, neaMixed1);
	cls.method(sigs::popArray, neaMixed0);
	cls.method(sigs::reverseArray, neaThis1);
	cls.method(sigs::searchArray, [](INTERNAL_FUNCTION_PARAMETERS) {
		PT_ARGS(1, 2);
		PT_RETURN_VAL(pt_type_new_mixed_type());
	});
	cls.method(sigs::shiftArray, neaMixed0);
	cls.method(sigs::shuffleArray, neaThis0);

	cls.method(sigs::sliceArray, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *offsetType, *lengthType, *preserveKeys;
		if (!zp::parse<zp::Obj, zp::Obj, zp::Obj>(execute_data, offsetType, lengthType, preserveKeys)) RETURN_THROWS();
		PT_RETURN_VAL(PT_THIS.sliceArray(offsetType, lengthType));
	});

	cls.method(sigs::spliceArray, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *offsetType, *lengthType, *replacementType;
		if (!zp::parse<zp::Obj, zp::Obj, zp::Obj>(execute_data, offsetType, lengthType, replacementType)) RETURN_THROWS();
		PT_RETURN_VAL(PT_THIS.spliceArray(lengthType, replacementType));
	});

	cls.method(sigs::truncateListToSize, neaThis1);
	cls.method(sigs::makeListMaybe, neaThis0);
	cls.method(sigs::mapValueType, neaThis1);
	cls.method(sigs::mapKeyType, neaThis1);
	cls.method(sigs::makeAllArrayKeysOptional, neaThis0);
	cls.method(sigs::changeKeyCaseArray, neaThis1);
	cls.method(sigs::filterArrayRemovingFalsey, neaMixed0);
	cls.method(sigs::isIterable, neaYes0);
	cls.method(sigs::isIterableAtLeastOnce, neaYes0);
	cls.op(PT_OP_IS_ITERABLE_AT_LEAST_ONCE, PT_OP_LAMBDA { return pt_op_trinary(PT_TRI_YES); });

	cls.method<&NonEmptyArrayType::getArraySize>(sigs::getArraySize);

	cls.method(sigs::getIterableKeyType, neaMixed0);
	cls.op(PT_OP_GET_ITERABLE_KEY_TYPE, PT_OP_LAMBDA { return pt_type_new_mixed_type(); });
	cls.method(sigs::getFirstIterableKeyType, neaMixed0);
	cls.method(sigs::getLastIterableKeyType, neaMixed0);
	cls.method(sigs::getIterableValueType, neaMixed0);
	cls.op(PT_OP_GET_ITERABLE_VALUE_TYPE, PT_OP_LAMBDA { return pt_type_new_mixed_type(); });
	cls.method(sigs::getFirstIterableValueType, neaMixed0);
	cls.method(sigs::getLastIterableValueType, neaMixed0);
	cls.method(sigs::isArray, neaYes0);
	cls.op(PT_OP_IS_ARRAY, PT_OP_LAMBDA { return pt_op_trinary(PT_TRI_YES); });
	cls.method(sigs::isConstantArray, neaMaybe0);
	cls.op(PT_OP_IS_CONSTANT_ARRAY, PT_OP_LAMBDA { return pt_op_trinary(PT_TRI_MAYBE); });
	cls.method(sigs::isOversizedArray, neaMaybe0);
	cls.method(sigs::isList, neaMaybe0);
	cls.op(PT_OP_IS_LIST, PT_OP_LAMBDA { return pt_op_trinary(PT_TRI_MAYBE); });
	cls.method(sigs::isNull, neaNo0);
	cls.op(PT_OP_IS_NULL, PT_OP_LAMBDA { return pt_op_trinary(PT_TRI_NO); });
	cls.method(sigs::isConstantValue, neaMaybe0);
	cls.method(sigs::isConstantScalarValue, neaNo0);
	cls.op(PT_OP_IS_CONSTANT_SCALAR_VALUE, PT_OP_LAMBDA { return pt_op_trinary(PT_TRI_NO); });
	cls.method(sigs::getConstantScalarTypes, neaEmptyArray0);
	cls.method(sigs::getConstantScalarValues, neaEmptyArray0);
	cls.op(PT_OP_GET_CONSTANT_SCALAR_VALUES, PT_OP_LAMBDA { return pt_op_empty_array(); });
	cls.method(sigs::isTrue, neaNo0);
	cls.method(sigs::isFalse, neaNo0);
	cls.method(sigs::isBoolean, neaNo0);
	cls.op(PT_OP_IS_BOOLEAN, PT_OP_LAMBDA { return pt_op_trinary(PT_TRI_NO); });
	cls.method(sigs::isFloat, neaNo0);
	cls.op(PT_OP_IS_FLOAT, PT_OP_LAMBDA { return pt_op_trinary(PT_TRI_NO); });
	cls.method(sigs::isInteger, neaNo0);
	cls.op(PT_OP_IS_INTEGER, PT_OP_LAMBDA { return pt_op_trinary(PT_TRI_NO); });
	cls.method(sigs::isString, neaNo0);
	cls.op(PT_OP_IS_STRING, PT_OP_LAMBDA { return pt_op_trinary(PT_TRI_NO); });
	cls.method(sigs::isNumericString, neaNo0);
	cls.method(sigs::isDecimalIntegerString, neaNo0);
	cls.method(sigs::isNonEmptyString, neaNo0);
	cls.method(sigs::isNonFalsyString, neaNo0);
	cls.method(sigs::isLiteralString, neaNo0);
	cls.method(sigs::isLowercaseString, neaNo0);
	cls.method(sigs::isClassString, neaNo0);
	cls.method(sigs::isUppercaseString, neaNo0);
	cls.method(sigs::getClassStringObjectType, neaError0);
	cls.method(sigs::getObjectTypeOrClassStringObjectType, neaError0);
	cls.method(sigs::isVoid, neaNo0);
	cls.op(PT_OP_IS_VOID, PT_OP_LAMBDA { return pt_op_trinary(PT_TRI_NO); });
	cls.method(sigs::isScalar, neaNo0);

	cls.method(sigs::looseCompare, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *type, *phpVersion;
		if (!zp::parse<zp::Obj, zp::Zval>(execute_data, type, phpVersion)) RETURN_THROWS();
		PT_RETURN_VAL(NonEmptyArrayType::looseCompare(type));
	});

	cls.method(sigs::toNumber, neaError0);
	cls.method(sigs::toBitwiseNotType, neaError0);
	cls.method(sigs::toAbsoluteNumber, neaError0);

	cls.method<&NonEmptyArrayType::toInteger>(sigs::toInteger);

	cls.method<&NonEmptyArrayType::toFloat>(sigs::toFloat);

	cls.method(sigs::toString, neaError0);
	cls.method(sigs::toArray, neaThis0);
	cls.method(sigs::toArrayKey, neaError0);
	cls.op(PT_OP_TO_ARRAY_KEY, PT_OP_LAMBDA { return pt_type_new_error_type(); });
	cls.method(sigs::toCoercedArgumentType, neaThis1);
	cls.method("traverse", reg::Public, 1, { reg::callableArg("cb") }, pt_type_identity_traverse_handler(), &ptret::type);
	cls.op(PT_OP_TRAVERSE, PT_OP_LAMBDA { return pt_op_traverse_identity(self); });
	cls.method(sigs::traverseSimultaneously, neaThis2);
	cls.method(sigs::exponentiate, neaError1);
	cls.method(sigs::getFiniteTypes, neaEmptyArray0);

	cls.method<&NonEmptyArrayType::getDefaultBaseType>(sigs::getDefaultBaseType);

	cls.method<&NonEmptyArrayType::toPhpDocNode>(sigs::toPhpDocNode);

	cls.method(sigs::hasTemplateOrLateResolvableType, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_FALSE;
	});
	cls.op(PT_OP_HAS_TEMPLATE_OR_LATE_RESOLVABLE_TYPE, PT_OP_LAMBDA { return zv::Val::boolean(false); });

	/* the traits, in the twin's `use` order (UndecidedComparisonCompoundTypeTrait
	 * brings UndecidedComparisonTypeTrait with it); the class body above wins
	 * over every name it declares */
	ptdecl::NonEmptyArrayType::registerTraits(cls);

	cls.shadow(&pt_ce_non_empty_array_type);
}

/* }}} */
