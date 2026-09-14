/*
 * PHPStanTurbo\AccessoryNonEmptyStringType — native implementation of
 * PHPStan\Type\Accessory\AccessoryNonEmptyStringType.
 *
 * The `$this->method()` calls the twin makes (equals(), isSubTypeOf(),
 * hasOffsetValueType(), toInteger(), toFloat(), toBoolean()) go through the
 * object's class entry — a subclass may have overridden them — with a
 * direct C++ call when the object is exactly an
 * AccessoryNonEmptyStringType.
 */

#include "TypeTraits.h"
#include "generated/AccessoryNonEmptyStringType.h"

namespace sigs = ptdecl::AccessoryNonEmptyStringType::sig;

zend_class_entry *pt_ce_accessory_non_empty_string_type = nullptr;

namespace phpstanturbo {

/* Mirrors PHPStan\Type\Accessory\AccessoryNonEmptyStringType. */
class AccessoryNonEmptyStringType
{
public:
	explicit AccessoryNonEmptyStringType(zend_object *self) : self(self) {}

	/* yes for a non-empty string; the CompoundType callback; else the
	 * non-empty-string verdict as a fresh result; UNDEF = pending exception */
	zv::Val accepts(zval *type, bool strictTypes) const
	{
		zend_long isNonEmptyString = pt_type_call_trinary(Z_OBJ_P(type), PT_LC("isnonemptystring"), 0, NULL);
		if (UNEXPECTED(isNonEmptyString < 0)) return zv::Val();
		if (isNonEmptyString == PT_TRI_YES) return pt_type_accepts_result(PT_TRI_YES);
		bool compound;
		if (UNEXPECTED(!pt_type_instanceof(type, PT_CLASS_COMPOUND_TYPE, compound))) return zv::Val();
		if (compound) {
			zv::Args args{self, strictTypes};
			return pt_type_call(Z_OBJ_P(type), PT_LC("isacceptedby"), 2, args);
		}
		return pt_type_new_accepts_result(isNonEmptyString);
	}

	/* the CompoundType callback; yes for an equal type and for a non-falsy
	 * string; else the non-empty-string verdict; UNDEF = pending exception */
	zv::Val isSuperTypeOf(zval *type) const
	{
		bool compound;
		if (UNEXPECTED(!pt_type_instanceof(type, PT_CLASS_COMPOUND_TYPE, compound))) return zv::Val();
		if (compound) {
			zval selfZv;
			ZVAL_OBJ(&selfZv, self);
			return pt_type_call(Z_OBJ_P(type), PT_LC("issubtypeof"), 1, &selfZv);
		}
		bool equal;
		if (UNEXPECTED(!thisEquals(type, equal))) return zv::Val();
		if (equal) return pt_type_is_super_type_of_result(PT_TRI_YES);
		zend_long isNonFalsyString = pt_type_call_trinary(Z_OBJ_P(type), PT_LC("isnonfalsystring"), 0, NULL);
		if (UNEXPECTED(isNonFalsyString < 0)) return zv::Val();
		if (isNonFalsyString == PT_TRI_YES) return pt_type_is_super_type_of_result(PT_TRI_YES);
		zend_long isNonEmptyString = pt_type_call_trinary(Z_OBJ_P(type), PT_LC("isnonemptystring"), 0, NULL);
		if (UNEXPECTED(isNonEmptyString < 0)) return zv::Val();
		return pt_type_new_is_super_type_of_result(isNonEmptyString);
	}

	/* the union/intersection callback; else the other type's
	 * non-empty-string verdict, and'ed with maybe unless it is an
	 * AccessoryNonEmptyStringType; UNDEF = pending exception */
	zv::Val isSubTypeOf(zval *otherType) const
	{
		bool unionOrIntersection;
		if (UNEXPECTED(!isUnionOrIntersection(otherType, unionOrIntersection))) return zv::Val();
		if (unionOrIntersection) {
			zval selfZv;
			ZVAL_OBJ(&selfZv, self);
			return pt_type_call(Z_OBJ_P(otherType), PT_LC("issupertypeof"), 1, &selfZv);
		}
		zend_long value = pt_type_call_trinary(Z_OBJ_P(otherType), PT_LC("isnonemptystring"), 0, NULL);
		if (UNEXPECTED(value < 0)) return zv::Val();
		if (!instanceof_function(Z_OBJCE_P(otherType), pt_ce_accessory_non_empty_string_type)) {
			value = pt_trinary_and(value, PT_TRI_MAYBE);
		}
		return pt_type_new_is_super_type_of_result(value);
	}

	/* $this->isSubTypeOf($acceptingType)->toAcceptsResult() */
	zv::Val isAcceptedBy(zval *acceptingType) const
	{
		return pt_type_sub_type_to_accepts_result(isExact() ? isSubTypeOf(acceptingType) : pt_type_call(self, PT_LC("issubtypeof"), 1, acceptingType));
	}

	/* $type instanceof self */
	static bool equals(zval *type) { return instanceof_function(Z_OBJCE_P(type), pt_ce_accessory_non_empty_string_type); }

	/* $offsetType->isInteger()->and(TrinaryLogic::createMaybe()); -1 =
	 * pending exception */
	static zend_long hasOffsetValueType(zval *offsetType) { return pt_type_string_has_offset_value_type(offsetType); }

	/* an ErrorType when $this->hasOffsetValueType($offsetType) is no, a
	 * non-empty string for the offset 0, string otherwise; UNDEF = pending
	 * exception */
	zv::Val getOffsetValueType(zval *offsetType) const
	{
		zend_long has = thisHasOffsetValueType(offsetType);
		if (UNEXPECTED(has < 0)) return zv::Val();
		if (has == PT_TRI_NO) return pt_type_new_error_type();
		/* (new ConstantIntegerType(0))->isSuperTypeOf($offsetType)->yes() */
		zv::Val zero = pt_type_new_constant_integer(0);
		if (UNEXPECTED(zero.isUndef())) return zv::Val();
		zv::Val covers = pt_type_call(Z_OBJ_P(zero.raw()), PT_LC("issupertypeof"), 1, offsetType);
		if (UNEXPECTED(covers.isUndef())) return zv::Val();
		zend_long coversValue = pt_type_result_trinary(covers.raw());
		if (UNEXPECTED(coversValue < 0)) return zv::Val();
		if (coversValue == PT_TRI_YES) return pt_type_new_string_with_accessory(pt_accessory_non_empty_string_type_new);
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
		if (UNEXPECTED(!pt_type_instanceof(stringOffset.raw(), PT_CLASS_ERROR_TYPE, isError))) return zv::Val();
		if (isError) return stringOffset;
		return thisValue();
	}

	/* new IntersectionType([new StringType(), new self()]) */
	static zv::Val toBitwiseNotType() { return pt_type_new_string_with_accessory(pt_accessory_non_empty_string_type_new); }

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

	/* $this under strict types, TypeCombinator::union($this->toInteger(),
	 * $this->toFloat(), $this, $this->toBoolean()) otherwise; UNDEF =
	 * pending exception */
	zv::Val toCoercedArgumentType(bool strictTypes) const
	{
		if (strictTypes) return thisValue();
		zv::Val integer = isExact() ? toInteger() : pt_type_call(self, PT_LC("tointeger"), 0, NULL);
		if (UNEXPECTED(integer.isUndef())) return zv::Val();
		zv::Val floating = isExact() ? toFloat() : pt_type_call(self, PT_LC("tofloat"), 0, NULL);
		if (UNEXPECTED(floating.isUndef())) return zv::Val();
		/* $this->toBoolean() — through the object's class (the trait's
		 * new BooleanType() unless overridden) */
		zv::Val boolean = pt_type_call(self, PT_LC("toboolean"), 0, NULL);
		if (UNEXPECTED(boolean.isUndef())) return zv::Val();
		zv::Args args{integer.raw(), floating.raw(), self, boolean.raw()};
		return pt_type_call_static(PT_CLASS_TYPE_COMBINATOR, PT_LC("union"), 4, args);
	}

	/* new ConstantBooleanType(false) for null and for a string that is not
	 * non-empty, new BooleanType() otherwise; UNDEF = pending exception */
	static zv::Val looseCompare(zval *type)
	{
		zend_long isNull = pt_type_call_trinary(Z_OBJ_P(type), PT_LC("isnull"), 0, NULL);
		if (UNEXPECTED(isNull < 0)) return zv::Val();
		if (isNull == PT_TRI_YES) return constantBoolean(false);
		zend_long isString = pt_type_call_trinary(Z_OBJ_P(type), PT_LC("isstring"), 0, NULL);
		if (UNEXPECTED(isString < 0)) return zv::Val();
		if (isString == PT_TRI_YES) {
			zend_long isNonEmptyString = pt_type_call_trinary(Z_OBJ_P(type), PT_LC("isnonemptystring"), 0, NULL);
			if (UNEXPECTED(isNonEmptyString < 0)) return zv::Val();
			if (isNonEmptyString == PT_TRI_NO) return constantBoolean(false);
		}
		return pt_val_of<pt_boolean_type_new>();
	}

	/* new StringType() */
	static zv::Val generalize() { return pt_type_new_string_type(); }

	/* new AccessoryNonFalsyStringType() for the constant '0', null
	 * otherwise; UNDEF = pending exception */
	static zv::Val tryRemove(zval *typeToRemove)
	{
		if (instanceof_function(Z_OBJCE_P(typeToRemove), pt_ce_constant_string_type)) {
			zv::Val value = pt_constant_string_get_value(Z_OBJ_P(typeToRemove));
			if (UNEXPECTED(value.isUndef())) return zv::Val();
			if (zv::Ref(value.raw()).stringEquals("0")) return pt_type_new_shadowed(pt_accessory_non_falsy_string_type_new);
		}
		return zv::Val::null();
	}

	/* new BenevolentUnionType([new FloatType(), new IntegerType()]) */
	static zv::Val exponentiate() { return pt_type_new_float_or_int_benevolent_union(); }

	/* new IdentifierTypeNode('non-empty-string') */
	static zv::Val toPhpDocNode() { return pt_type_new_identifier_type_node(PT_LC("non-empty-string")); }

private:
	zend_object *self;

	bool isExact() const { return self->ce == pt_ce_accessory_non_empty_string_type; }

	zv::Val thisValue() const { return pt_this_value(self); }

	/* $this->equals($type) — through the object's class; false = pending
	 * exception */
	[[nodiscard]] bool thisEquals(zval *type, bool &out) const
	{
		if (EXPECTED(isExact())) {
			out = equals(type);
			return true;
		}
		return pt_type_call_bool(self, PT_LC("equals"), 1, type, out);
	}

	/* $this->hasOffsetValueType($offsetType) — through the object's class;
	 * -1 = pending exception */
	[[nodiscard]] zend_long thisHasOffsetValueType(zval *offsetType) const
	{
		if (EXPECTED(isExact())) return hasOffsetValueType(offsetType);
		return pt_type_call_trinary(self, PT_LC("hasoffsetvaluetype"), 1, offsetType);
	}

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
};

} // namespace phpstanturbo

using phpstanturbo::AccessoryNonEmptyStringType;

bool pt_accessory_non_empty_string_type_new(zval *out)
{
	return object_init_ex(out, pt_ce_accessory_non_empty_string_type) == SUCCESS;
}

/* {{{ engine ABI glue: parameter parsing + registration */

#define PT_THIS AccessoryNonEmptyStringType(Z_OBJ_P(ZEND_THIS))

/* the trivial bodies the twin repeats (one handler per body and arity;
 * each method is still declared exactly once, at its registration line) */

static void ZEND_FASTCALL anesEmptyArray0(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	RETURN_EMPTY_ARRAY();
}

static void ZEND_FASTCALL anesNo0(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	PT_RETURN_TRINARY(PT_TRI_NO);
}

static void ZEND_FASTCALL anesMaybe0(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	PT_RETURN_TRINARY(PT_TRI_MAYBE);
}

static void ZEND_FASTCALL anesYes0(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	PT_RETURN_TRINARY(PT_TRI_YES);
}

static void ZEND_FASTCALL anesThis0(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	RETURN_OBJ_COPY(Z_OBJ_P(ZEND_THIS));
}

static void ZEND_FASTCALL anesThis2(INTERNAL_FUNCTION_PARAMETERS)
{
	PT_ARGS(2, 2);
	RETURN_OBJ_COPY(Z_OBJ_P(ZEND_THIS));
}

static void ZEND_FASTCALL anesError0(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	PT_RETURN_VAL(pt_type_new_error_type());
}

static void ZEND_FASTCALL anesError1(INTERNAL_FUNCTION_PARAMETERS)
{
	PT_ARGS(1, 1);
	PT_RETURN_VAL(pt_type_new_error_type());
}

static void ZEND_FASTCALL anesString1(INTERNAL_FUNCTION_PARAMETERS)
{
	PT_ARGS(1, 1);
	PT_RETURN_VAL(pt_type_new_string_type());
}

static void ZEND_FASTCALL anesObjectWithoutClass0(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	PT_RETURN_VAL(pt_type_new_object_without_class_type());
}

/* (Type $type) → a Type */
static void pt_anes_one_type(INTERNAL_FUNCTION_PARAMETERS, zv::Val (AccessoryNonEmptyStringType::*method)(zval *) const)
{
	zval *type;
	if (!zp::parse<zp::Obj>(execute_data, type)) RETURN_THROWS();
	PT_RETURN_VAL((PT_THIS.*method)(type));
}

void pt_register_accessory_non_empty_string_type()
{
	reg::Class cls("PHPStan\\Type\\Accessory\\AccessoryNonEmptyStringType");
	ptdecl::AccessoryNonEmptyStringType::declareClass(cls);
	ptdecl::AccessoryNonEmptyStringType::declareProperties(cls);

	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
	});

	cls.method(sigs::getReferencedClasses, anesEmptyArray0);
	cls.method(sigs::getObjectClassNames, anesEmptyArray0);
	cls.method(sigs::getObjectClassReflections, anesEmptyArray0);
	cls.method(sigs::getConstantStrings, anesEmptyArray0);

	cls.method<&AccessoryNonEmptyStringType::accepts, zp::Obj, zp::Bool>(sigs::accepts);

	cls.method(sigs::isSuperTypeOf, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_anes_one_type(INTERNAL_FUNCTION_PARAM_PASSTHRU, &AccessoryNonEmptyStringType::isSuperTypeOf);
	});

	cls.method(sigs::isSubTypeOf, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_anes_one_type(INTERNAL_FUNCTION_PARAM_PASSTHRU, &AccessoryNonEmptyStringType::isSubTypeOf);
	});

	cls.method(sigs::isAcceptedBy, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *acceptingType;
		bool strictTypes;
		if (!zp::parse<zp::Obj, zp::Bool>(execute_data, acceptingType, strictTypes)) RETURN_THROWS();
		PT_RETURN_VAL(PT_THIS.isAcceptedBy(acceptingType));
	});

	cls.method(sigs::equals, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *type;
		if (!zp::parse<zp::Obj>(execute_data, type)) RETURN_THROWS();
		RETURN_BOOL(AccessoryNonEmptyStringType::equals(type));
	});

	cls.method(sigs::describe, [](INTERNAL_FUNCTION_PARAMETERS) {
		PT_ARGS(1, 1);
		RETURN_STRINGL("non-empty-string", sizeof("non-empty-string") - 1);
	});

	cls.method(sigs::isOffsetAccessible, anesYes0);
	cls.method(sigs::isOffsetAccessLegal, anesYes0);

	cls.method(sigs::hasOffsetValueType, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *offsetType;
		if (!zp::parse<zp::Obj>(execute_data, offsetType)) RETURN_THROWS();
		zend_long value = AccessoryNonEmptyStringType::hasOffsetValueType(offsetType);
		if (UNEXPECTED(value < 0)) RETURN_THROWS();
		PT_RETURN_TRINARY(value);
	});

	cls.method(sigs::getOffsetValueType, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_anes_one_type(INTERNAL_FUNCTION_PARAM_PASSTHRU, &AccessoryNonEmptyStringType::getOffsetValueType);
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

	cls.method(sigs::setExistingOffsetValueType, anesThis2);
	cls.method(sigs::unsetOffset, anesError1);
	cls.method(sigs::toNumber, anesError0);

	cls.method<&AccessoryNonEmptyStringType::toBitwiseNotType>(sigs::toBitwiseNotType);

	cls.method(sigs::toAbsoluteNumber, anesError0);

	cls.method<&AccessoryNonEmptyStringType::toInteger>(sigs::toInteger);

	cls.method<&AccessoryNonEmptyStringType::toFloat>(sigs::toFloat);

	cls.method(sigs::toString, anesThis0);

	cls.method<&AccessoryNonEmptyStringType::toArray>(sigs::toArray);

	cls.method(sigs::toArrayKey, anesThis0);

	cls.method<&AccessoryNonEmptyStringType::toCoercedArgumentType, zp::Bool>(sigs::toCoercedArgumentType);

	cls.method(sigs::isNull, anesNo0);
	cls.method(sigs::isConstantValue, anesMaybe0);
	cls.method(sigs::isConstantScalarValue, anesMaybe0);
	cls.method(sigs::getConstantScalarTypes, anesEmptyArray0);
	cls.method(sigs::getConstantScalarValues, anesEmptyArray0);
	cls.method(sigs::isTrue, anesNo0);
	cls.method(sigs::isFalse, anesNo0);
	cls.method(sigs::isBoolean, anesNo0);
	cls.method(sigs::isFloat, anesNo0);
	cls.method(sigs::isInteger, anesNo0);
	cls.method(sigs::isString, anesYes0);
	cls.method(sigs::isNumericString, anesMaybe0);
	cls.method(sigs::isDecimalIntegerString, anesMaybe0);
	cls.method(sigs::isNonEmptyString, anesYes0);
	cls.method(sigs::isNonFalsyString, anesMaybe0);
	cls.method(sigs::isLiteralString, anesMaybe0);
	cls.method(sigs::isLowercaseString, anesMaybe0);
	cls.method(sigs::isClassString, anesMaybe0);
	cls.method(sigs::isUppercaseString, anesMaybe0);
	cls.method(sigs::getClassStringObjectType, anesObjectWithoutClass0);
	cls.method(sigs::getObjectTypeOrClassStringObjectType, anesObjectWithoutClass0);
	cls.method(sigs::isVoid, anesNo0);
	cls.method(sigs::isScalar, anesYes0);

	cls.method(sigs::looseCompare, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *type, *phpVersion;
		if (!zp::parse<zp::Obj, zp::Zval>(execute_data, type, phpVersion)) RETURN_THROWS();
		PT_RETURN_VAL(AccessoryNonEmptyStringType::looseCompare(type));
	});

	cls.method("traverse", reg::Public, 1, { reg::callableArg("cb") }, pt_type_identity_traverse_handler(), &ptret::type);
	cls.method(sigs::traverseSimultaneously, anesThis2);
	cls.method(sigs::generalize, anesString1);

	cls.method<&AccessoryNonEmptyStringType::tryRemove, zp::Obj>(sigs::tryRemove);

	cls.method(sigs::exponentiate, [](INTERNAL_FUNCTION_PARAMETERS) {
		PT_ARGS(1, 1);
		PT_RETURN_VAL(AccessoryNonEmptyStringType::exponentiate());
	});

	cls.method(sigs::getFiniteTypes, anesEmptyArray0);

	cls.method(sigs::getDefaultBaseType, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		PT_RETURN_VAL(pt_type_new_string_type());
	});

	cls.method<&AccessoryNonEmptyStringType::toPhpDocNode>(sigs::toPhpDocNode);

	cls.method(sigs::hasTemplateOrLateResolvableType, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_FALSE;
	});

	/* the traits, in the twin's `use` order (UndecidedComparisonCompoundTypeTrait
	 * brings UndecidedComparisonTypeTrait with it); the class body above wins
	 * over every name it declares */
	ptdecl::AccessoryNonEmptyStringType::registerTraits(cls);

	cls.shadow(&pt_ce_accessory_non_empty_string_type);
}

/* }}} */
