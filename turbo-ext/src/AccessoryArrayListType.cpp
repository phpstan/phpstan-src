/*
 * PHPStanTurbo\AccessoryArrayListType — native implementation of
 * PHPStan\Type\Accessory\AccessoryArrayListType.
 *
 * The `$this->method()` calls the twin makes (equals(), isSubTypeOf(),
 * getIterableKeyType(), hasOffsetValueType()) go through the object's class
 * entry — a subclass may have overridden them — with a direct C++ call when
 * the object is exactly an AccessoryArrayListType.
 */

#include "TypeTraits.h"
#include "generated/AccessoryArrayListType.h"

namespace sigs = ptdecl::AccessoryArrayListType::sig;

zend_class_entry *pt_ce_accessory_array_list_type = nullptr;

namespace phpstanturbo {

/* Mirrors PHPStan\Type\Accessory\AccessoryArrayListType. */
class AccessoryArrayListType
{
public:
	explicit AccessoryArrayListType(zend_object *self) : self(self) {}

	/* yes for a list; the CompoundType callback; else the list verdict as
	 * a fresh result; UNDEF = pending exception */
	zv::Val accepts(zval *type, bool strictTypes) const
	{
		zend_long isList = pt_type_op_trinary(Z_OBJ_P(type), PT_OP_IS_LIST, 0, NULL);
		if (UNEXPECTED(isList < 0)) return zv::Val();
		if (isList == PT_TRI_YES) return pt_type_accepts_result(PT_TRI_YES);
		bool compound;
		if (UNEXPECTED(!pt_type_instanceof(type, PT_CLASS_COMPOUND_TYPE, compound))) return zv::Val();
		if (compound) {
			zv::Args args{self, strictTypes};
			return pt_type_call(Z_OBJ_P(type), PT_LC("isacceptedby"), 2, args);
		}
		return pt_type_new_accepts_result(isList);
	}

	/* yes for an equal type; the CompoundType callback; else the list
	 * verdict; UNDEF = pending exception */
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
		zend_long isList = pt_type_op_trinary(Z_OBJ_P(type), PT_OP_IS_LIST, 0, NULL);
		if (UNEXPECTED(isList < 0)) return zv::Val();
		return pt_type_new_is_super_type_of_result(isList);
	}

	/* the union/intersection callback; yes for an AccessoryArrayListType;
	 * else the other type's list verdict and'ed with maybe; UNDEF =
	 * pending exception */
	zv::Val isSubTypeOf(zval *otherType) const
	{
		bool unionOrIntersection;
		if (UNEXPECTED(!isUnionOrIntersection(otherType, unionOrIntersection))) return zv::Val();
		if (unionOrIntersection) {
			zval selfZv;
			ZVAL_OBJ(&selfZv, self);
			return pt_type_op(Z_OBJ_P(otherType), PT_OP_IS_SUPER_TYPE_OF, 1, &selfZv);
		}
		if (instanceof_function(Z_OBJCE_P(otherType), pt_ce_accessory_array_list_type)) return pt_type_new_is_super_type_of_result(PT_TRI_YES);
		zend_long isList = pt_type_op_trinary(Z_OBJ_P(otherType), PT_OP_IS_LIST, 0, NULL);
		if (UNEXPECTED(isList < 0)) return zv::Val();
		return pt_type_new_is_super_type_of_result(pt_trinary_and(isList, PT_TRI_MAYBE));
	}

	/* $this->isSubTypeOf($acceptingType)->toAcceptsResult() */
	zv::Val isAcceptedBy(zval *acceptingType) const
	{
		return pt_type_sub_type_to_accepts_result(isExact() ? isSubTypeOf(acceptingType) : pt_type_op(self, PT_OP_IS_SUB_TYPE_OF, 1, acceptingType));
	}

	/* $type instanceof self */
	static bool equals(zval *type) { return instanceof_function(Z_OBJCE_P(type), pt_ce_accessory_array_list_type); }

	/* $this->getIterableKeyType()->isSuperTypeOf($offsetType)->result->and(TrinaryLogic::createMaybe());
	 * -1 = pending exception */
	[[nodiscard]] zend_long hasOffsetValueType(zval *offsetType) const
	{
		zv::Val keyType = isExact() ? getIterableKeyType() : pt_type_op(self, PT_OP_GET_ITERABLE_KEY_TYPE, 0, NULL);
		if (UNEXPECTED(keyType.isUndef())) return -1;
		if (UNEXPECTED(!zv::Ref(keyType.raw()).isObject())) {
			zend_type_error("phpstan_turbo: getIterableKeyType() must return %s", ptcls::type);
			return -1;
		}
		zv::Val result = pt_type_op(Z_OBJ_P(keyType.raw()), PT_OP_IS_SUPER_TYPE_OF, 1, offsetType);
		if (UNEXPECTED(result.isUndef())) return -1;
		zend_long value = pt_type_result_trinary(result.raw());
		if (UNEXPECTED(value < 0)) return -1;
		return pt_trinary_and(value, PT_TRI_MAYBE);
	}

	/* $this for an appended value or offset 0, new ErrorType() otherwise;
	 * offsetType NULL = the twin's null; UNDEF = pending exception */
	zv::Val setOffsetValueType(zval *offsetType) const
	{
		if (offsetType == NULL) return thisValue();
		zend_long isZero = zeroIsSuperTypeOf(offsetType);
		if (UNEXPECTED(isZero < 0)) return zv::Val();
		if (isZero == PT_TRI_YES) return thisValue();
		return pt_type_new_error_type();
	}

	/* $this when the offset is not one of a list, new ErrorType()
	 * otherwise; UNDEF = pending exception */
	zv::Val unsetOffset(zval *offsetType) const
	{
		zend_long has = isExact() ? hasOffsetValueType(offsetType) : pt_type_call_trinary(self, PT_LC("hasoffsetvaluetype"), 1, offsetType);
		if (UNEXPECTED(has < 0)) return zv::Val();
		if (has == PT_TRI_NO) return thisValue();
		return pt_type_new_error_type();
	}

	/* $this for another list, mixed otherwise; UNDEF = pending exception */
	zv::Val intersectKeyArray(zval *otherArraysType) const
	{
		zend_long isList = pt_type_op_trinary(Z_OBJ_P(otherArraysType), PT_OP_IS_LIST, 0, NULL);
		if (UNEXPECTED(isList < 0)) return zv::Val();
		if (isList == PT_TRI_YES) return thisValue();
		return pt_type_new_mixed_type();
	}

	/* $this when the keys are dropped, mixed otherwise; UNDEF = pending
	 * exception */
	zv::Val reverseArray(zval *preserveKeys) const
	{
		zend_long preserve = pt_type_trinary_value(preserveKeys);
		if (UNEXPECTED(preserve < 0)) return zv::Val();
		if (preserve == PT_TRI_NO) return thisValue();
		return pt_type_new_mixed_type();
	}

	/* $this when the keys are dropped or the slice starts at 0, mixed
	 * otherwise; UNDEF = pending exception */
	zv::Val sliceArray(zval *offsetType, zval *preserveKeys) const
	{
		zend_long preserve = pt_type_trinary_value(preserveKeys);
		if (UNEXPECTED(preserve < 0)) return zv::Val();
		if (preserve == PT_TRI_NO) return thisValue();
		zend_long isZero = zeroIsSuperTypeOf(offsetType);
		if (UNEXPECTED(isZero < 0)) return zv::Val();
		if (isZero == PT_TRI_YES) return thisValue();
		return pt_type_new_mixed_type();
	}

	/* IntegerRangeType::fromInterval(0, null) — getArraySize() and
	 * getIterableKeyType() */
	static zv::Val getIterableKeyType() { return pt_integer_range_from_interval(NullableLong::of(0), NullableLong::null(), 0); }

	/* new ConstantIntegerType(0) */
	static zv::Val getFirstIterableKeyType() { return pt_type_new_constant_integer(0); }

	/* $this->getIterableKeyType() */
	zv::Val getLastIterableKeyType() const
	{
		return isExact() ? getIterableKeyType() : pt_type_op(self, PT_OP_GET_ITERABLE_KEY_TYPE, 0, NULL);
	}

	/* new UnionType([new ConstantIntegerType(0), new ConstantIntegerType(1)]) */
	static zv::Val toInteger()
	{
		zv::Val zero = pt_type_new_constant_integer(0);
		zv::Val one = pt_type_new_constant_integer(1);
		if (UNEXPECTED(zero.isUndef() || one.isUndef())) return zv::Val();
		zv::Arr types = zv::Arr::create(2);
		types.push(std::move(zero));
		types.push(std::move(one));
		return pt_type_new_union(std::move(types));
	}

	/* new UnionType([new ConstantFloatType(0.0), new ConstantFloatType(1.0)]) */
	static zv::Val toFloat()
	{
		zv::Val zero = pt_type_new_constant_float(0.0);
		zv::Val one = pt_type_new_constant_float(1.0);
		if (UNEXPECTED(zero.isUndef() || one.isUndef())) return zv::Val();
		zv::Arr types = zv::Arr::create(2);
		types.push(std::move(zero));
		types.push(std::move(one));
		return pt_type_new_union(std::move(types));
	}

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

	/* new IdentifierTypeNode('list') */
	static zv::Val toPhpDocNode()
	{
		zv::Val name = zv::Val::string("list", 4);
		return pt_type_new(PT_CLASS_IDENTIFIER_TYPE_NODE, 1, name.raw());
	}

private:
	zend_object *self;

	bool isExact() const { return self->ce == pt_ce_accessory_array_list_type; }

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

using phpstanturbo::AccessoryArrayListType;

bool pt_accessory_array_list_type_new(zval *out)
{
	return object_init_ex(out, pt_ce_accessory_array_list_type) == SUCCESS;
}

/* {{{ engine ABI glue: parameter parsing + registration */

#define PT_THIS AccessoryArrayListType(Z_OBJ_P(ZEND_THIS))

/* the trivial bodies the twin repeats (one handler per body and arity;
 * each method is still declared exactly once, at its registration line) */

static void ZEND_FASTCALL aalEmptyArray0(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	RETURN_EMPTY_ARRAY();
}

static void ZEND_FASTCALL aalNo0(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	PT_RETURN_TRINARY(PT_TRI_NO);
}

static void ZEND_FASTCALL aalMaybe0(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	PT_RETURN_TRINARY(PT_TRI_MAYBE);
}

static void ZEND_FASTCALL aalYes0(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	PT_RETURN_TRINARY(PT_TRI_YES);
}

static void ZEND_FASTCALL aalThis0(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	RETURN_OBJ_COPY(Z_OBJ_P(ZEND_THIS));
}

static void ZEND_FASTCALL aalThis1(INTERNAL_FUNCTION_PARAMETERS)
{
	PT_ARGS(1, 1);
	RETURN_OBJ_COPY(Z_OBJ_P(ZEND_THIS));
}

static void ZEND_FASTCALL aalThis2(INTERNAL_FUNCTION_PARAMETERS)
{
	PT_ARGS(2, 2);
	RETURN_OBJ_COPY(Z_OBJ_P(ZEND_THIS));
}

static void ZEND_FASTCALL aalThis3(INTERNAL_FUNCTION_PARAMETERS)
{
	PT_ARGS(3, 3);
	RETURN_OBJ_COPY(Z_OBJ_P(ZEND_THIS));
}

static void ZEND_FASTCALL aalError0(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	PT_RETURN_VAL(pt_type_new_error_type());
}

static void ZEND_FASTCALL aalError1(INTERNAL_FUNCTION_PARAMETERS)
{
	PT_ARGS(1, 1);
	PT_RETURN_VAL(pt_type_new_error_type());
}

static void ZEND_FASTCALL aalMixed0(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	PT_RETURN_VAL(pt_type_new_mixed_type());
}

static void ZEND_FASTCALL aalMixed1(INTERNAL_FUNCTION_PARAMETERS)
{
	PT_ARGS(1, 1);
	PT_RETURN_VAL(pt_type_new_mixed_type());
}

/* IntegerRangeType::fromInterval(0, null) */
static void ZEND_FASTCALL aalNonNegativeIntegers0(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	PT_RETURN_VAL(AccessoryArrayListType::getIterableKeyType());
}

/* (Type $offsetType) → a Type */
static void pt_aal_one_type(INTERNAL_FUNCTION_PARAMETERS, zv::Val (AccessoryArrayListType::*method)(zval *) const)
{
	zval *type;
	if (!zp::parse<zp::Obj>(execute_data, type)) RETURN_THROWS();
	PT_RETURN_VAL((PT_THIS.*method)(type));
}

void pt_register_accessory_array_list_type()
{
	reg::Class cls("PHPStan\\Type\\Accessory\\AccessoryArrayListType");
	ptdecl::AccessoryArrayListType::declareClass(cls);
	ptdecl::AccessoryArrayListType::declareProperties(cls);

	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
	});

	cls.method(sigs::getReferencedClasses, aalEmptyArray0);
	cls.method(sigs::getObjectClassNames, aalEmptyArray0);
	cls.op(PT_OP_GET_OBJECT_CLASS_NAMES, PT_OP_LAMBDA { return pt_op_empty_array(); });
	cls.method(sigs::getObjectClassReflections, aalEmptyArray0);
	cls.method(sigs::getArrays, aalEmptyArray0);
	cls.method(sigs::getConstantArrays, aalEmptyArray0);
	cls.op(PT_OP_GET_CONSTANT_ARRAYS, PT_OP_LAMBDA { return pt_op_empty_array(); });
	cls.method(sigs::getConstantStrings, aalEmptyArray0);

	cls.method<&AccessoryArrayListType::accepts, zp::Obj, zp::Bool>(sigs::accepts);
	cls.op(PT_OP_ACCEPTS, PT_OP_LAMBDA { return AccessoryArrayListType(self).accepts(argv, (Z_TYPE(argv[1]) == IS_TRUE)); });

	cls.method(sigs::isSuperTypeOf, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_aal_one_type(INTERNAL_FUNCTION_PARAM_PASSTHRU, &AccessoryArrayListType::isSuperTypeOf);
	});
	cls.op<PT_OP_IS_SUPER_TYPE_OF, &AccessoryArrayListType::isSuperTypeOf>();

	cls.method(sigs::isSubTypeOf, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_aal_one_type(INTERNAL_FUNCTION_PARAM_PASSTHRU, &AccessoryArrayListType::isSubTypeOf);
	});
	cls.op<PT_OP_IS_SUB_TYPE_OF, &AccessoryArrayListType::isSubTypeOf>();

	cls.method(sigs::isAcceptedBy, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *acceptingType;
		bool strictTypes;
		if (!zp::parse<zp::Obj, zp::Bool>(execute_data, acceptingType, strictTypes)) RETURN_THROWS();
		PT_RETURN_VAL(PT_THIS.isAcceptedBy(acceptingType));
	});

	cls.method(sigs::equals, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *type;
		if (!zp::parse<zp::Obj>(execute_data, type)) RETURN_THROWS();
		RETURN_BOOL(AccessoryArrayListType::equals(type));
	});
	cls.op(PT_OP_EQUALS, PT_OP_LAMBDA { return zv::Val::boolean(AccessoryArrayListType::equals(argv)); });

	cls.method(sigs::describe, [](INTERNAL_FUNCTION_PARAMETERS) {
		PT_ARGS(1, 1);
		RETURN_STRINGL("list", 4);
	});
	cls.op(PT_OP_DESCRIBE, PT_OP_LAMBDA { return zv::Val::string("list", 4); });

	cls.method(sigs::isOffsetAccessible, aalYes0);
	cls.method(sigs::isOffsetAccessLegal, aalYes0);

	cls.method(sigs::hasOffsetValueType, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *offsetType;
		if (!zp::parse<zp::Obj>(execute_data, offsetType)) RETURN_THROWS();
		PT_RETURN_TRINARY_OR_THROW(PT_THIS.hasOffsetValueType(offsetType));
	});

	cls.method(sigs::getOffsetValueType, aalMixed1);

	cls.method(sigs::setOffsetValueType, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *offsetType, *valueType;
		bool unionValues = true;
		if (!zp::parse<zp::ObjOrNull, zp::Obj, zp::Opt<zp::Bool>>(execute_data, offsetType, valueType, unionValues)) RETURN_THROWS();
		PT_RETURN_VAL(PT_THIS.setOffsetValueType(offsetType));
	});

	cls.method(sigs::setExistingOffsetValueType, aalThis2);

	cls.method(sigs::unsetOffset, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_aal_one_type(INTERNAL_FUNCTION_PARAM_PASSTHRU, &AccessoryArrayListType::unsetOffset);
	});

	cls.method(sigs::getKeysArrayFiltered, [](INTERNAL_FUNCTION_PARAMETERS) {
		PT_ARGS(2, 2);
		/* $this->getKeysArray() — through the object's class */
		PT_RETURN_VAL(pt_type_call(Z_OBJ_P(ZEND_THIS), PT_LC("getkeysarray"), 0, NULL));
	});

	cls.method(sigs::getKeysArray, aalThis0);
	cls.method(sigs::getValuesArray, aalThis0);
	cls.method(sigs::chunkArray, aalThis2);
	cls.method(sigs::fillKeysArray, aalMixed1);
	cls.method(sigs::flipArray, aalMixed0);

	cls.method(sigs::intersectKeyArray, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_aal_one_type(INTERNAL_FUNCTION_PARAM_PASSTHRU, &AccessoryArrayListType::intersectKeyArray);
	});

	cls.method(sigs::popArray, aalThis0);

	cls.method(sigs::reverseArray, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_aal_one_type(INTERNAL_FUNCTION_PARAM_PASSTHRU, &AccessoryArrayListType::reverseArray);
	});

	cls.method(sigs::searchArray, [](INTERNAL_FUNCTION_PARAMETERS) {
		PT_ARGS(1, 2);
		PT_RETURN_VAL(pt_type_new_mixed_type());
	});

	cls.method(sigs::shiftArray, aalThis0);
	cls.method(sigs::shuffleArray, aalThis0);

	cls.method(sigs::sliceArray, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *offsetType, *lengthType, *preserveKeys;
		if (!zp::parse<zp::Obj, zp::Obj, zp::Obj>(execute_data, offsetType, lengthType, preserveKeys)) RETURN_THROWS();
		PT_RETURN_VAL(PT_THIS.sliceArray(offsetType, preserveKeys));
	});

	cls.method(sigs::spliceArray, aalThis3);
	cls.method(sigs::truncateListToSize, aalThis1);
	cls.method(sigs::makeListMaybe, aalMixed0);
	cls.method(sigs::mapValueType, aalThis1);
	cls.method(sigs::mapKeyType, aalThis1);
	cls.method(sigs::makeAllArrayKeysOptional, aalThis0);
	cls.method(sigs::changeKeyCaseArray, aalThis1);
	cls.method(sigs::filterArrayRemovingFalsey, aalMixed0);
	cls.method(sigs::isIterable, aalYes0);
	cls.method(sigs::isIterableAtLeastOnce, aalMaybe0);
	cls.op(PT_OP_IS_ITERABLE_AT_LEAST_ONCE, PT_OP_LAMBDA { return pt_op_trinary(PT_TRI_MAYBE); });
	cls.method(sigs::getArraySize, aalNonNegativeIntegers0);
	cls.method(sigs::getIterableKeyType, aalNonNegativeIntegers0);
	cls.op(PT_OP_GET_ITERABLE_KEY_TYPE, PT_OP_LAMBDA { return AccessoryArrayListType::getIterableKeyType(); });

	cls.method<&AccessoryArrayListType::getFirstIterableKeyType>(sigs::getFirstIterableKeyType);

	cls.method<&AccessoryArrayListType::getLastIterableKeyType>(sigs::getLastIterableKeyType);

	cls.method(sigs::getIterableValueType, aalMixed0);
	cls.op(PT_OP_GET_ITERABLE_VALUE_TYPE, PT_OP_LAMBDA { return pt_type_new_mixed_type(); });
	cls.method(sigs::getFirstIterableValueType, aalMixed0);
	cls.method(sigs::getLastIterableValueType, aalMixed0);
	cls.method(sigs::isArray, aalYes0);
	cls.op(PT_OP_IS_ARRAY, PT_OP_LAMBDA { return pt_op_trinary(PT_TRI_YES); });
	cls.method(sigs::isConstantArray, aalMaybe0);
	cls.op(PT_OP_IS_CONSTANT_ARRAY, PT_OP_LAMBDA { return pt_op_trinary(PT_TRI_MAYBE); });
	cls.method(sigs::isOversizedArray, aalMaybe0);
	cls.method(sigs::isList, aalYes0);
	cls.op(PT_OP_IS_LIST, PT_OP_LAMBDA { return pt_op_trinary(PT_TRI_YES); });
	cls.method(sigs::isNull, aalNo0);
	cls.op(PT_OP_IS_NULL, PT_OP_LAMBDA { return pt_op_trinary(PT_TRI_NO); });
	cls.method(sigs::isConstantValue, aalMaybe0);
	cls.method(sigs::isConstantScalarValue, aalNo0);
	cls.op(PT_OP_IS_CONSTANT_SCALAR_VALUE, PT_OP_LAMBDA { return pt_op_trinary(PT_TRI_NO); });
	cls.method(sigs::getConstantScalarTypes, aalEmptyArray0);
	cls.method(sigs::getConstantScalarValues, aalEmptyArray0);
	cls.op(PT_OP_GET_CONSTANT_SCALAR_VALUES, PT_OP_LAMBDA { return pt_op_empty_array(); });
	cls.method(sigs::isTrue, aalNo0);
	cls.method(sigs::isFalse, aalNo0);
	cls.method(sigs::isBoolean, aalNo0);
	cls.op(PT_OP_IS_BOOLEAN, PT_OP_LAMBDA { return pt_op_trinary(PT_TRI_NO); });
	cls.method(sigs::isFloat, aalNo0);
	cls.op(PT_OP_IS_FLOAT, PT_OP_LAMBDA { return pt_op_trinary(PT_TRI_NO); });
	cls.method(sigs::isInteger, aalNo0);
	cls.op(PT_OP_IS_INTEGER, PT_OP_LAMBDA { return pt_op_trinary(PT_TRI_NO); });
	cls.method(sigs::isString, aalNo0);
	cls.op(PT_OP_IS_STRING, PT_OP_LAMBDA { return pt_op_trinary(PT_TRI_NO); });
	cls.method(sigs::isNumericString, aalNo0);
	cls.method(sigs::isDecimalIntegerString, aalNo0);
	cls.method(sigs::isNonEmptyString, aalNo0);
	cls.method(sigs::isNonFalsyString, aalNo0);
	cls.method(sigs::isLiteralString, aalNo0);
	cls.method(sigs::isLowercaseString, aalNo0);
	cls.method(sigs::isClassString, aalNo0);
	cls.method(sigs::isUppercaseString, aalNo0);
	cls.method(sigs::getClassStringObjectType, aalError0);
	cls.method(sigs::getObjectTypeOrClassStringObjectType, aalError0);
	cls.method(sigs::isVoid, aalNo0);
	cls.op(PT_OP_IS_VOID, PT_OP_LAMBDA { return pt_op_trinary(PT_TRI_NO); });
	cls.method(sigs::isScalar, aalNo0);

	cls.method(sigs::looseCompare, [](INTERNAL_FUNCTION_PARAMETERS) {
		PT_ARGS(2, 2);
		/* new BooleanType() — the shadowing class */
		zval result;
		if (UNEXPECTED(!pt_boolean_type_new(&result))) RETURN_THROWS();
		RETURN_COPY_VALUE(&result);
	});

	cls.method(sigs::toNumber, aalError0);
	cls.method(sigs::toBitwiseNotType, aalError0);
	cls.method(sigs::toAbsoluteNumber, aalError0);

	cls.method<&AccessoryArrayListType::toInteger>(sigs::toInteger);

	cls.method<&AccessoryArrayListType::toFloat>(sigs::toFloat);

	cls.method(sigs::toString, aalError0);
	cls.method(sigs::toArray, aalThis0);
	cls.method(sigs::toArrayKey, aalError0);
	cls.op(PT_OP_TO_ARRAY_KEY, PT_OP_LAMBDA { return pt_type_new_error_type(); });
	cls.method(sigs::toCoercedArgumentType, aalThis1);
	cls.method("traverse", reg::Public, 1, { reg::callableArg("cb") }, pt_type_identity_traverse_handler(), &ptret::type);
	cls.op(PT_OP_TRAVERSE, PT_OP_LAMBDA { return pt_op_traverse_identity(self); });
	cls.method(sigs::traverseSimultaneously, aalThis2);
	cls.method(sigs::exponentiate, aalError1);
	cls.method(sigs::getFiniteTypes, aalEmptyArray0);

	cls.method<&AccessoryArrayListType::getDefaultBaseType>(sigs::getDefaultBaseType);

	cls.method<&AccessoryArrayListType::toPhpDocNode>(sigs::toPhpDocNode);

	cls.method(sigs::hasTemplateOrLateResolvableType, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_FALSE;
	});
	cls.op(PT_OP_HAS_TEMPLATE_OR_LATE_RESOLVABLE_TYPE, PT_OP_LAMBDA { return zv::Val::boolean(false); });

	/* the traits, in the twin's `use` order (UndecidedComparisonCompoundTypeTrait
	 * brings UndecidedComparisonTypeTrait with it); the class body above wins
	 * over every name it declares */
	ptdecl::AccessoryArrayListType::registerTraits(cls);

	cls.shadow(&pt_ce_accessory_array_list_type);
}

/* }}} */
