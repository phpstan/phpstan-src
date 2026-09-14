/*
 * PHPStanTurbo\AccessoryNumericStringType — native implementation of
 * PHPStan\Type\Accessory\AccessoryNumericStringType.
 *
 * The `$this->method()` calls the twin makes (equals(), isSubTypeOf(),
 * hasOffsetValueType(), toNumber(), toInteger(), toFloat(), toBoolean()) go
 * through the object's class entry — a subclass may have overridden them —
 * with a direct C++ call when the object is exactly an
 * AccessoryNumericStringType.
 */

#include "TypeTraits.h"
#include "generated/AccessoryNumericStringType.h"

namespace sigs = ptdecl::AccessoryNumericStringType::sig;

zend_class_entry *pt_ce_accessory_numeric_string_type = nullptr;

namespace phpstanturbo {

/* Mirrors PHPStan\Type\Accessory\AccessoryNumericStringType. */
class AccessoryNumericStringType
{
public:
	explicit AccessoryNumericStringType(zend_object *self) : self(self) {}

	/* yes for a numeric string; the CompoundType callback; else the
	 * numeric-string verdict as a fresh result; UNDEF = pending exception */
	zv::Val accepts(zval *type, bool strictTypes) const
	{
		zend_long isNumericString = pt_type_call_trinary(Z_OBJ_P(type), PT_LC("isnumericstring"), 0, NULL);
		if (UNEXPECTED(isNumericString < 0)) return zv::Val();
		if (isNumericString == PT_TRI_YES) return pt_type_accepts_result(PT_TRI_YES);
		bool compound;
		if (UNEXPECTED(!pt_type_instanceof(type, PT_CLASS_COMPOUND_TYPE, compound))) return zv::Val();
		if (compound) {
			zv::Args args{self, strictTypes};
			return pt_type_call(Z_OBJ_P(type), PT_LC("isacceptedby"), 2, args);
		}
		return pt_type_new_accepts_result(isNumericString);
	}

	/* the CompoundType callback; yes for an equal type; else the
	 * numeric-string verdict; UNDEF = pending exception */
	zv::Val isSuperTypeOf(zval *type) const
	{
		bool compound;
		if (UNEXPECTED(!pt_type_instanceof(type, PT_CLASS_COMPOUND_TYPE, compound))) return zv::Val();
		if (compound) {
			zval selfZv;
			ZVAL_OBJ(&selfZv, self);
			return pt_type_op(Z_OBJ_P(type), PT_OP_IS_SUB_TYPE_OF, 1, &selfZv);
		}
		bool equal;
		if (UNEXPECTED(!thisEquals(type, equal))) return zv::Val();
		if (equal) return pt_type_is_super_type_of_result(PT_TRI_YES);
		zend_long isNumericString = pt_type_call_trinary(Z_OBJ_P(type), PT_LC("isnumericstring"), 0, NULL);
		if (UNEXPECTED(isNumericString < 0)) return zv::Val();
		return pt_type_new_is_super_type_of_result(isNumericString);
	}

	/* the union/intersection callback; else the other type's numeric-string
	 * verdict, and'ed with maybe unless it is an AccessoryNumericStringType;
	 * UNDEF = pending exception */
	zv::Val isSubTypeOf(zval *otherType) const
	{
		bool unionOrIntersection;
		if (UNEXPECTED(!isUnionOrIntersection(otherType, unionOrIntersection))) return zv::Val();
		if (unionOrIntersection) {
			zval selfZv;
			ZVAL_OBJ(&selfZv, self);
			return pt_type_op(Z_OBJ_P(otherType), PT_OP_IS_SUPER_TYPE_OF, 1, &selfZv);
		}
		zend_long value = pt_type_call_trinary(Z_OBJ_P(otherType), PT_LC("isnumericstring"), 0, NULL);
		if (UNEXPECTED(value < 0)) return zv::Val();
		if (!instanceof_function(Z_OBJCE_P(otherType), pt_ce_accessory_numeric_string_type)) {
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
	static bool equals(zval *type) { return instanceof_function(Z_OBJCE_P(type), pt_ce_accessory_numeric_string_type); }

	/* $offsetType->isInteger()->and(TrinaryLogic::createMaybe()); -1 =
	 * pending exception */
	static zend_long hasOffsetValueType(zval *offsetType) { return pt_type_string_has_offset_value_type(offsetType); }

	/* an ErrorType when $this->hasOffsetValueType($offsetType) is no, string
	 * otherwise; UNDEF = pending exception */
	zv::Val getOffsetValueType(zval *offsetType) const
	{
		zend_long has = thisHasOffsetValueType(offsetType);
		if (UNEXPECTED(has < 0)) return zv::Val();
		if (has == PT_TRI_NO) return pt_type_new_error_type();
		return pt_type_new_string_type();
	}

	/* (new StringType())->setOffsetValueType($offsetType, $valueType, $unionValues)
	 * when that is an ErrorType, $this otherwise; UNDEF = pending exception */
	zv::Val setOffsetValueType(zval *offsetType, zval *valueType) const
	{
		zv::Val string = pt_type_new_string_type();
		if (UNEXPECTED(string.isUndef())) return zv::Val();
		zv::Val stringOffset = pt_string_type_set_offset_value_type(Z_OBJ_P(string.raw()), offsetType, valueType);
		if (UNEXPECTED(stringOffset.isUndef())) return zv::Val();
		bool isError;
		if (UNEXPECTED(!pt_type_instanceof_ce(stringOffset.raw(), pt_ce_error_type, isError))) return zv::Val();
		if (isError) return stringOffset;
		return thisValue();
	}

	/* new UnionType([$this->toInteger(), $this->toFloat()]); UNDEF = pending
	 * exception */
	zv::Val toNumber() const
	{
		zv::Val integer = thisToInteger();
		if (UNEXPECTED(integer.isUndef())) return zv::Val();
		zv::Val floating = thisToFloat();
		if (UNEXPECTED(floating.isUndef())) return zv::Val();
		zv::Arr types = zv::Arr::create(2);
		types.push(std::move(integer));
		types.push(std::move(floating));
		return pt_type_new_union(std::move(types));
	}

	/* new IntersectionType([new StringType(), new AccessoryNonEmptyStringType()]) */
	static zv::Val toBitwiseNotType() { return pt_type_new_string_with_accessory(pt_accessory_non_empty_string_type_new); }

	/* $this->toNumber()->toAbsoluteNumber(); UNDEF = pending exception */
	zv::Val toAbsoluteNumber() const
	{
		zv::Val number = isExact() ? toNumber() : pt_type_call(self, PT_LC("tonumber"), 0, NULL);
		if (UNEXPECTED(number.isUndef())) return zv::Val();
		if (UNEXPECTED(!zv::Ref(number.raw()).isObject())) {
			zend_type_error("phpstan_turbo: toNumber() must return %s", ptcls::type);
			return zv::Val();
		}
		return pt_type_call(Z_OBJ_P(number.raw()), PT_LC("toabsolutenumber"), 0, NULL);
	}

	/* new IntegerType() */
	static zv::Val toInteger()
	{
		return pt_val_of<pt_integer_type_new>();
	}

	/* new FloatType() */
	static zv::Val toFloat()
	{
		return pt_val_of<pt_float_type_new>();
	}

	/* new ConstantArrayType([new ConstantIntegerType(0)], [$this], [1], isList: TrinaryLogic::createYes()) */
	zv::Val toArray() const { return pt_type_string_accessory_to_array(self); }

	/* int|numeric-string unless ReportUnsafeArrayStringKeyCastingToggle::getLevel()
	 * is PREVENT, int|non-decimal-int-string then; UNDEF = pending exception */
	static zv::Val toArrayKey()
	{
		bool notPrevented;
		if (UNEXPECTED(!pt_type_unsafe_array_string_key_casting_not_prevented(notPrevented))) return zv::Val();
		zv::Val integer = toInteger();
		if (UNEXPECTED(integer.isUndef())) return zv::Val();
		zv::Val string;
		if (notPrevented) {
			/* new IntersectionType([new StringType(), new AccessoryNumericStringType()]) */
			string = pt_type_new_string_with_accessory(pt_accessory_numeric_string_type_new);
		} else {
			/* new IntersectionType([new StringType(), new AccessoryDecimalIntegerStringType(inverse: true)]) */
			string = pt_type_new_string_with_accessory(inverseDecimalIntegerString);
		}
		if (UNEXPECTED(string.isUndef())) return zv::Val();
		zv::Arr types = zv::Arr::create(2);
		types.push(std::move(integer));
		types.push(std::move(string));
		return pt_type_new_union(std::move(types));
	}

	/* $this under strict types, TypeCombinator::union($this->toInteger(),
	 * $this->toFloat(), $this, $this->toBoolean()) otherwise; UNDEF =
	 * pending exception */
	zv::Val toCoercedArgumentType(bool strictTypes) const
	{
		if (strictTypes) return thisValue();
		zv::Val integer = thisToInteger();
		if (UNEXPECTED(integer.isUndef())) return zv::Val();
		zv::Val floating = thisToFloat();
		if (UNEXPECTED(floating.isUndef())) return zv::Val();
		/* $this->toBoolean() — through the object's class (the trait's
		 * new BooleanType() unless overridden) */
		zv::Val boolean = pt_type_call(self, PT_LC("toboolean"), 0, NULL);
		if (UNEXPECTED(boolean.isUndef())) return zv::Val();
		zv::Args args{integer.raw(), floating.raw(), self, boolean.raw()};
		return pt_type_combinator_call(PT_LC("union"), 4, args);
	}

	/* new ConstantBooleanType(false) for null and for a string that is not
	 * numeric, new BooleanType() otherwise; UNDEF = pending exception */
	static zv::Val looseCompare(zval *type)
	{
		zend_long isNull = pt_type_op_trinary(Z_OBJ_P(type), PT_OP_IS_NULL, 0, NULL);
		if (UNEXPECTED(isNull < 0)) return zv::Val();
		if (isNull == PT_TRI_YES) return constantBoolean(false);
		zend_long isString = pt_type_op_trinary(Z_OBJ_P(type), PT_OP_IS_STRING, 0, NULL);
		if (UNEXPECTED(isString < 0)) return zv::Val();
		if (isString == PT_TRI_YES) {
			zend_long isNumericString = pt_type_call_trinary(Z_OBJ_P(type), PT_LC("isnumericstring"), 0, NULL);
			if (UNEXPECTED(isNumericString < 0)) return zv::Val();
			if (isNumericString == PT_TRI_NO) return constantBoolean(false);
		}
		return pt_val_of<pt_boolean_type_new>();
	}

	/* new StringType() */
	static zv::Val generalize() { return pt_type_new_string_type(); }

	/* string&numeric-string&non-falsy-string (with $this) for the constant
	 * '0', null otherwise; UNDEF = pending exception */
	zv::Val tryRemove(zval *typeToRemove) const
	{
		if (instanceof_function(Z_OBJCE_P(typeToRemove), pt_ce_constant_string_type)) {
			zv::Val value = pt_constant_string_get_value(Z_OBJ_P(typeToRemove));
			if (UNEXPECTED(value.isUndef())) return zv::Val();
			if (zv::Ref(value.raw()).stringEquals("0")) {
				/* new IntersectionType([new StringType(), $this, new AccessoryNonFalsyStringType()]) */
				zv::Val string = pt_type_new_string_type();
				if (UNEXPECTED(string.isUndef())) return zv::Val();
				zval nonFalsy;
				if (UNEXPECTED(!pt_accessory_non_falsy_string_type_new(&nonFalsy))) return zv::Val();
				zv::Arr types = zv::Arr::create(3);
				types.push(std::move(string));
				types.push(thisValue());
				types.push(zv::Val::adopt(nonFalsy));
				return pt_type_new_intersection(std::move(types));
			}
		}
		return zv::Val::null();
	}

	/* new BenevolentUnionType([new FloatType(), new IntegerType()]) */
	static zv::Val exponentiate() { return pt_type_new_float_or_int_benevolent_union(); }

	/* new IdentifierTypeNode('numeric-string') */
	static zv::Val toPhpDocNode() { return pt_type_new_identifier_type_node(PT_LC("numeric-string")); }

private:
	zend_object *self;

	bool isExact() const { return self->ce == pt_ce_accessory_numeric_string_type; }

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

	/* $this->hasOffsetValueType($offsetType) — through the object's class;
	 * -1 = pending exception */
	[[nodiscard]] zend_long thisHasOffsetValueType(zval *offsetType) const
	{
		if (EXPECTED(isExact())) return hasOffsetValueType(offsetType);
		return pt_type_call_trinary(self, PT_LC("hasoffsetvaluetype"), 1, offsetType);
	}

	/* $this->toInteger() / $this->toFloat() — through the object's class */
	zv::Val thisToInteger() const { return isExact() ? toInteger() : pt_type_call(self, PT_LC("tointeger"), 0, NULL); }
	zv::Val thisToFloat() const { return isExact() ? toFloat() : pt_type_call(self, PT_LC("tofloat"), 0, NULL); }

	/* TrinaryLogic::and(): the minimum */

	/* $type instanceof UnionType || $type instanceof IntersectionType;
	 * false = pending exception */
	[[nodiscard]] static bool isUnionOrIntersection(zval *type, bool &out) { return pt_type_is_union_or_intersection(type, out); }

	/* new ConstantBooleanType($value) — the shadowing class */
	static zv::Val constantBoolean(bool value)
	{
		zval result;
		if (UNEXPECTED(!pt_constant_boolean_type_new(&result, value))) return zv::Val();
		return zv::Val::adopt(result);
	}

	/* new AccessoryDecimalIntegerStringType(inverse: true) */
	static bool inverseDecimalIntegerString(zval *out) { return pt_accessory_decimal_integer_string_type_new(out, true); }
};

} // namespace phpstanturbo

using phpstanturbo::AccessoryNumericStringType;

bool pt_accessory_numeric_string_type_new(zval *out)
{
	return object_init_ex(out, pt_ce_accessory_numeric_string_type) == SUCCESS;
}

/* {{{ engine ABI glue: parameter parsing + registration */

#define PT_THIS AccessoryNumericStringType(Z_OBJ_P(ZEND_THIS))

/* the trivial bodies the twin repeats (one handler per body and arity;
 * each method is still declared exactly once, at its registration line) */

static void ZEND_FASTCALL ansEmptyArray0(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	RETURN_EMPTY_ARRAY();
}

static void ZEND_FASTCALL ansNo0(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	PT_RETURN_TRINARY(PT_TRI_NO);
}

static void ZEND_FASTCALL ansMaybe0(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	PT_RETURN_TRINARY(PT_TRI_MAYBE);
}

static void ZEND_FASTCALL ansYes0(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	PT_RETURN_TRINARY(PT_TRI_YES);
}

static void ZEND_FASTCALL ansThis0(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	RETURN_OBJ_COPY(Z_OBJ_P(ZEND_THIS));
}

static void ZEND_FASTCALL ansThis2(INTERNAL_FUNCTION_PARAMETERS)
{
	PT_ARGS(2, 2);
	RETURN_OBJ_COPY(Z_OBJ_P(ZEND_THIS));
}

static void ZEND_FASTCALL ansError0(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	PT_RETURN_VAL(pt_type_new_error_type());
}

static void ZEND_FASTCALL ansError1(INTERNAL_FUNCTION_PARAMETERS)
{
	PT_ARGS(1, 1);
	PT_RETURN_VAL(pt_type_new_error_type());
}

static void ZEND_FASTCALL ansString1(INTERNAL_FUNCTION_PARAMETERS)
{
	PT_ARGS(1, 1);
	PT_RETURN_VAL(pt_type_new_string_type());
}

/* (Type $type) → a Type */
static void pt_ans_one_type(INTERNAL_FUNCTION_PARAMETERS, zv::Val (AccessoryNumericStringType::*method)(zval *) const)
{
	zval *type;
	if (!zp::parse<zp::Obj>(execute_data, type)) RETURN_THROWS();
	PT_RETURN_VAL((PT_THIS.*method)(type));
}

void pt_register_accessory_numeric_string_type()
{
	reg::Class cls("PHPStan\\Type\\Accessory\\AccessoryNumericStringType");
	ptdecl::AccessoryNumericStringType::declareClass(cls);
	ptdecl::AccessoryNumericStringType::declareProperties(cls);

	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
	});

	cls.method(sigs::getReferencedClasses, ansEmptyArray0);
	cls.method(sigs::getObjectClassNames, ansEmptyArray0);
	cls.op(PT_OP_GET_OBJECT_CLASS_NAMES, PT_OP_LAMBDA { return pt_op_empty_array(); });
	cls.method(sigs::getObjectClassReflections, ansEmptyArray0);
	cls.method(sigs::getConstantStrings, ansEmptyArray0);

	cls.method<&AccessoryNumericStringType::accepts, zp::Obj, zp::Bool>(sigs::accepts);
	cls.op(PT_OP_ACCEPTS, PT_OP_LAMBDA { return AccessoryNumericStringType(self).accepts(argv, (Z_TYPE(argv[1]) == IS_TRUE)); });

	cls.method(sigs::isSuperTypeOf, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_ans_one_type(INTERNAL_FUNCTION_PARAM_PASSTHRU, &AccessoryNumericStringType::isSuperTypeOf);
	});
	cls.op<PT_OP_IS_SUPER_TYPE_OF, &AccessoryNumericStringType::isSuperTypeOf>();

	cls.method(sigs::isSubTypeOf, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_ans_one_type(INTERNAL_FUNCTION_PARAM_PASSTHRU, &AccessoryNumericStringType::isSubTypeOf);
	});
	cls.op<PT_OP_IS_SUB_TYPE_OF, &AccessoryNumericStringType::isSubTypeOf>();

	cls.method(sigs::isAcceptedBy, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *acceptingType;
		bool strictTypes;
		if (!zp::parse<zp::Obj, zp::Bool>(execute_data, acceptingType, strictTypes)) RETURN_THROWS();
		PT_RETURN_VAL(PT_THIS.isAcceptedBy(acceptingType));
	});

	cls.method(sigs::equals, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *type;
		if (!zp::parse<zp::Obj>(execute_data, type)) RETURN_THROWS();
		RETURN_BOOL(AccessoryNumericStringType::equals(type));
	});
	cls.op(PT_OP_EQUALS, PT_OP_LAMBDA { return zv::Val::boolean(AccessoryNumericStringType::equals(argv)); });

	cls.method(sigs::describe, [](INTERNAL_FUNCTION_PARAMETERS) {
		PT_ARGS(1, 1);
		RETURN_STRINGL("numeric-string", sizeof("numeric-string") - 1);
	});
	cls.op(PT_OP_DESCRIBE, PT_OP_LAMBDA { return zv::Val::string("numeric-string", sizeof("numeric-string") - 1); });

	cls.method(sigs::isOffsetAccessible, ansYes0);
	cls.method(sigs::isOffsetAccessLegal, ansYes0);

	cls.method(sigs::hasOffsetValueType, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *offsetType;
		if (!zp::parse<zp::Obj>(execute_data, offsetType)) RETURN_THROWS();
		zend_long value = AccessoryNumericStringType::hasOffsetValueType(offsetType);
		if (UNEXPECTED(value < 0)) RETURN_THROWS();
		PT_RETURN_TRINARY(value);
	});

	cls.method(sigs::getOffsetValueType, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_ans_one_type(INTERNAL_FUNCTION_PARAM_PASSTHRU, &AccessoryNumericStringType::getOffsetValueType);
	});

	cls.method(sigs::setOffsetValueType, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *offsetType, *valueType;
		bool unionValues = true;
		if (!zp::parse<zp::ObjOrNull, zp::Obj, zp::Opt<zp::Bool>>(execute_data, offsetType, valueType, unionValues)) RETURN_THROWS();
		zval nullZv;
		if (offsetType == NULL) {
			ZVAL_NULL(&nullZv);
			offsetType = &nullZv;
		}
		PT_RETURN_VAL(PT_THIS.setOffsetValueType(offsetType, valueType));
	});

	cls.method(sigs::setExistingOffsetValueType, ansThis2);
	cls.method(sigs::unsetOffset, ansError1);

	cls.method<&AccessoryNumericStringType::toNumber>(sigs::toNumber);

	cls.method<&AccessoryNumericStringType::toBitwiseNotType>(sigs::toBitwiseNotType);

	cls.method<&AccessoryNumericStringType::toAbsoluteNumber>(sigs::toAbsoluteNumber);

	cls.method<&AccessoryNumericStringType::toInteger>(sigs::toInteger);

	cls.method<&AccessoryNumericStringType::toFloat>(sigs::toFloat);

	cls.method(sigs::toString, ansThis0);

	cls.method<&AccessoryNumericStringType::toArray>(sigs::toArray);

	cls.method<&AccessoryNumericStringType::toArrayKey>(sigs::toArrayKey);
	cls.op(PT_OP_TO_ARRAY_KEY, PT_OP_LAMBDA { return AccessoryNumericStringType::toArrayKey(); });

	cls.method<&AccessoryNumericStringType::toCoercedArgumentType, zp::Bool>(sigs::toCoercedArgumentType);

	cls.method(sigs::isNull, ansNo0);
	cls.op(PT_OP_IS_NULL, PT_OP_LAMBDA { return pt_op_trinary(PT_TRI_NO); });
	cls.method(sigs::isConstantValue, ansMaybe0);
	cls.method(sigs::isConstantScalarValue, ansMaybe0);
	cls.op(PT_OP_IS_CONSTANT_SCALAR_VALUE, PT_OP_LAMBDA { return pt_op_trinary(PT_TRI_MAYBE); });
	cls.method(sigs::getConstantScalarTypes, ansEmptyArray0);
	cls.method(sigs::getConstantScalarValues, ansEmptyArray0);
	cls.op(PT_OP_GET_CONSTANT_SCALAR_VALUES, PT_OP_LAMBDA { return pt_op_empty_array(); });
	cls.method(sigs::isTrue, ansNo0);
	cls.method(sigs::isFalse, ansNo0);
	cls.method(sigs::isBoolean, ansNo0);
	cls.op(PT_OP_IS_BOOLEAN, PT_OP_LAMBDA { return pt_op_trinary(PT_TRI_NO); });
	cls.method(sigs::isFloat, ansNo0);
	cls.op(PT_OP_IS_FLOAT, PT_OP_LAMBDA { return pt_op_trinary(PT_TRI_NO); });
	cls.method(sigs::isInteger, ansNo0);
	cls.op(PT_OP_IS_INTEGER, PT_OP_LAMBDA { return pt_op_trinary(PT_TRI_NO); });
	cls.method(sigs::isString, ansYes0);
	cls.op(PT_OP_IS_STRING, PT_OP_LAMBDA { return pt_op_trinary(PT_TRI_YES); });
	cls.method(sigs::isNumericString, ansYes0);
	cls.method(sigs::isDecimalIntegerString, ansMaybe0);
	cls.method(sigs::isNonEmptyString, ansYes0);
	cls.method(sigs::isNonFalsyString, ansMaybe0);
	cls.method(sigs::isLiteralString, ansMaybe0);
	cls.method(sigs::isLowercaseString, ansMaybe0);
	cls.method(sigs::isUppercaseString, ansMaybe0);
	cls.method(sigs::isClassString, ansNo0);
	cls.method(sigs::getClassStringObjectType, ansError0);
	cls.method(sigs::getObjectTypeOrClassStringObjectType, ansError0);
	cls.method(sigs::isVoid, ansNo0);
	cls.op(PT_OP_IS_VOID, PT_OP_LAMBDA { return pt_op_trinary(PT_TRI_NO); });
	cls.method(sigs::isScalar, ansYes0);

	cls.method(sigs::looseCompare, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *type, *phpVersion;
		if (!zp::parse<zp::Obj, zp::Zval>(execute_data, type, phpVersion)) RETURN_THROWS();
		PT_RETURN_VAL(AccessoryNumericStringType::looseCompare(type));
	});

	cls.method("traverse", reg::Public, 1, { reg::callableArg("cb") }, pt_type_identity_traverse_handler(), &ptret::type);
	cls.op(PT_OP_TRAVERSE, PT_OP_LAMBDA { return pt_op_traverse_identity(self); });
	cls.method(sigs::traverseSimultaneously, ansThis2);
	cls.method(sigs::generalize, ansString1);

	cls.method(sigs::tryRemove, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_ans_one_type(INTERNAL_FUNCTION_PARAM_PASSTHRU, &AccessoryNumericStringType::tryRemove);
	});

	cls.method(sigs::exponentiate, [](INTERNAL_FUNCTION_PARAMETERS) {
		PT_ARGS(1, 1);
		PT_RETURN_VAL(AccessoryNumericStringType::exponentiate());
	});

	cls.method(sigs::getFiniteTypes, ansEmptyArray0);

	cls.method(sigs::getDefaultBaseType, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		PT_RETURN_VAL(pt_type_new_string_type());
	});

	cls.method<&AccessoryNumericStringType::toPhpDocNode>(sigs::toPhpDocNode);

	cls.method(sigs::hasTemplateOrLateResolvableType, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_FALSE;
	});
	cls.op(PT_OP_HAS_TEMPLATE_OR_LATE_RESOLVABLE_TYPE, PT_OP_LAMBDA { return zv::Val::boolean(false); });

	/* the traits, in the twin's `use` order (UndecidedComparisonCompoundTypeTrait
	 * brings UndecidedComparisonTypeTrait with it); the class body above wins
	 * over every name it declares */
	ptdecl::AccessoryNumericStringType::registerTraits(cls);

	cls.shadow(&pt_ce_accessory_numeric_string_type);
}

/* }}} */
