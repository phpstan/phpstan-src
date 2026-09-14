/*
 * PHPStanTurbo\AccessoryLowercaseStringType — native implementation of
 * PHPStan\Type\Accessory\AccessoryLowercaseStringType.
 *
 * The `$this->method()` calls the twin makes (equals(), isSubTypeOf(),
 * hasOffsetValueType(), toInteger(), toFloat(), toBoolean(),
 * isNumericString()) go through the object's class entry — a subclass may
 * have overridden them — with a direct C++ call when the object is exactly
 * an AccessoryLowercaseStringType.
 */

#include "TypeTraits.h"
#include "generated/AccessoryLowercaseStringType.h"

namespace sigs = ptdecl::AccessoryLowercaseStringType::sig;

zend_class_entry *pt_ce_accessory_lowercase_string_type = nullptr;

namespace phpstanturbo {

/* Mirrors PHPStan\Type\Accessory\AccessoryLowercaseStringType. */
class AccessoryLowercaseStringType
{
public:
	explicit AccessoryLowercaseStringType(zend_object *self) : self(self) {}

	/* yes for a lowercase string; the CompoundType callback; else the
	 * lowercase-string verdict as a fresh result; UNDEF = pending exception */
	zv::Val accepts(zval *type, bool strictTypes) const
	{
		zend_long isLowercaseString = pt_type_call_trinary(Z_OBJ_P(type), PT_LC("islowercasestring"), 0, NULL);
		if (UNEXPECTED(isLowercaseString < 0)) return zv::Val();
		if (isLowercaseString == PT_TRI_YES) return pt_type_accepts_result(PT_TRI_YES);
		bool compound;
		if (UNEXPECTED(!pt_type_instanceof(type, PT_CLASS_COMPOUND_TYPE, compound))) return zv::Val();
		if (compound) {
			zv::Args args{self, strictTypes};
			return pt_type_call(Z_OBJ_P(type), PT_LC("isacceptedby"), 2, args);
		}
		return pt_type_new_accepts_result(isLowercaseString);
	}

	/* the CompoundType callback; yes for an equal type; else the
	 * lowercase-string verdict; UNDEF = pending exception */
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
		zend_long isLowercaseString = pt_type_call_trinary(Z_OBJ_P(type), PT_LC("islowercasestring"), 0, NULL);
		if (UNEXPECTED(isLowercaseString < 0)) return zv::Val();
		return pt_type_new_is_super_type_of_result(isLowercaseString);
	}

	/* the union/intersection callback; else the other type's
	 * lowercase-string verdict, and'ed with maybe unless it is an
	 * AccessoryLowercaseStringType; UNDEF = pending exception */
	zv::Val isSubTypeOf(zval *otherType) const
	{
		bool unionOrIntersection;
		if (UNEXPECTED(!isUnionOrIntersection(otherType, unionOrIntersection))) return zv::Val();
		if (unionOrIntersection) {
			zval selfZv;
			ZVAL_OBJ(&selfZv, self);
			return pt_type_op(Z_OBJ_P(otherType), PT_OP_IS_SUPER_TYPE_OF, 1, &selfZv);
		}
		zend_long value = pt_type_call_trinary(Z_OBJ_P(otherType), PT_LC("islowercasestring"), 0, NULL);
		if (UNEXPECTED(value < 0)) return zv::Val();
		if (!instanceof_function(Z_OBJCE_P(otherType), pt_ce_accessory_lowercase_string_type)) {
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
	static bool equals(zval *type) { return instanceof_function(Z_OBJCE_P(type), pt_ce_accessory_lowercase_string_type); }

	/* $offsetType->isInteger()->and(TrinaryLogic::createMaybe()); -1 =
	 * pending exception */
	static zend_long hasOffsetValueType(zval *offsetType) { return pt_type_string_has_offset_value_type(offsetType); }

	/* an ErrorType when $this->hasOffsetValueType($offsetType) is no, a
	 * lowercase string otherwise; UNDEF = pending exception */
	zv::Val getOffsetValueType(zval *offsetType) const
	{
		zend_long has = thisHasOffsetValueType(offsetType);
		if (UNEXPECTED(has < 0)) return zv::Val();
		if (has == PT_TRI_NO) return pt_type_new_error_type();
		return pt_type_new_string_with_accessory(pt_accessory_lowercase_string_type_new);
	}

	/* (new StringType())->setOffsetValueType($offsetType, $valueType, $unionValues)
	 * when that is an ErrorType; $this for a lowercase value, string
	 * otherwise; UNDEF = pending exception */
	zv::Val setOffsetValueType(zval *offsetType, zval *valueType) const
	{
		zv::Val string = pt_type_new_string_type();
		if (UNEXPECTED(string.isUndef())) return zv::Val();
		zv::Val stringOffset = pt_string_type_set_offset_value_type(Z_OBJ_P(string.raw()), offsetType, valueType);
		if (UNEXPECTED(stringOffset.isUndef())) return zv::Val();
		bool isError;
		if (UNEXPECTED(!pt_type_instanceof_ce(stringOffset.raw(), pt_ce_error_type, isError))) return zv::Val();
		if (isError) return stringOffset;
		zend_long valueIsLowercase = pt_type_call_trinary(Z_OBJ_P(valueType), PT_LC("islowercasestring"), 0, NULL);
		if (UNEXPECTED(valueIsLowercase < 0)) return zv::Val();
		if (valueIsLowercase == PT_TRI_YES) return thisValue();
		return pt_type_new_string_type();
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

	/* new BooleanType() */
	static zv::Val toBoolean()
	{
		return pt_val_of<pt_boolean_type_new>();
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
		zv::Val boolean = isExact() ? toBoolean() : pt_type_call(self, PT_LC("toboolean"), 0, NULL);
		if (UNEXPECTED(boolean.isUndef())) return zv::Val();
		zv::Args args{integer.raw(), floating.raw(), self, boolean.raw()};
		return pt_type_combinator_call(PT_LC("union"), 4, args);
	}

	/* new ConstantBooleanType(false) for a string that is not lowercase
	 * unless both sides may be numeric, new BooleanType() otherwise; UNDEF
	 * = pending exception */
	zv::Val looseCompare(zval *type) const
	{
		/* $type->isString()->yes() && $type->isLowercaseString()->no()
		 * && ($type->isNumericString()->no() || $this->isNumericString()->no())
		 * — short-circuiting like the twin */
		zend_long isString = pt_type_op_trinary(Z_OBJ_P(type), PT_OP_IS_STRING, 0, NULL);
		if (UNEXPECTED(isString < 0)) return zv::Val();
		if (isString == PT_TRI_YES) {
			zend_long isLowercase = pt_type_call_trinary(Z_OBJ_P(type), PT_LC("islowercasestring"), 0, NULL);
			if (UNEXPECTED(isLowercase < 0)) return zv::Val();
			if (isLowercase == PT_TRI_NO) {
				zend_long typeIsNumeric = pt_type_call_trinary(Z_OBJ_P(type), PT_LC("isnumericstring"), 0, NULL);
				if (UNEXPECTED(typeIsNumeric < 0)) return zv::Val();
				bool dominated = typeIsNumeric == PT_TRI_NO;
				if (!dominated) {
					/* $this->isNumericString() — through the object's class
					 * (maybe unless overridden) */
					zend_long thisIsNumeric = isExact() ? PT_TRI_MAYBE : pt_type_call_trinary(self, PT_LC("isnumericstring"), 0, NULL);
					if (UNEXPECTED(thisIsNumeric < 0)) return zv::Val();
					dominated = thisIsNumeric == PT_TRI_NO;
				}
				if (dominated) {
					zval result;
					if (UNEXPECTED(!pt_constant_boolean_type_new(&result, false))) return zv::Val();
					return zv::Val::adopt(result);
				}
			}
		}
		return toBoolean();
	}

	/* new StringType() */
	static zv::Val generalize() { return pt_type_new_string_type(); }

	/* new BenevolentUnionType([new FloatType(), new IntegerType()]) */
	static zv::Val exponentiate() { return pt_type_new_float_or_int_benevolent_union(); }

	/* new IdentifierTypeNode('lowercase-string') */
	static zv::Val toPhpDocNode() { return pt_type_new_identifier_type_node(PT_LC("lowercase-string")); }

private:
	zend_object *self;

	bool isExact() const { return self->ce == pt_ce_accessory_lowercase_string_type; }

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

	/* TrinaryLogic::and(): the minimum */

	/* $type instanceof UnionType || $type instanceof IntersectionType;
	 * false = pending exception */
	[[nodiscard]] static bool isUnionOrIntersection(zval *type, bool &out) { return pt_type_is_union_or_intersection(type, out); }
};

} // namespace phpstanturbo

using phpstanturbo::AccessoryLowercaseStringType;

bool pt_accessory_lowercase_string_type_new(zval *out)
{
	return object_init_ex(out, pt_ce_accessory_lowercase_string_type) == SUCCESS;
}

/* {{{ engine ABI glue: parameter parsing + registration */

#define PT_THIS AccessoryLowercaseStringType(Z_OBJ_P(ZEND_THIS))

/* the trivial bodies the twin repeats (one handler per body and arity;
 * each method is still declared exactly once, at its registration line) */

static void ZEND_FASTCALL alcsEmptyArray0(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	RETURN_EMPTY_ARRAY();
}

static void ZEND_FASTCALL alcsNo0(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	PT_RETURN_TRINARY(PT_TRI_NO);
}

static void ZEND_FASTCALL alcsMaybe0(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	PT_RETURN_TRINARY(PT_TRI_MAYBE);
}

static void ZEND_FASTCALL alcsMaybe1(INTERNAL_FUNCTION_PARAMETERS)
{
	PT_ARGS(1, 1);
	PT_RETURN_TRINARY(PT_TRI_MAYBE);
}

static void ZEND_FASTCALL alcsYes0(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	PT_RETURN_TRINARY(PT_TRI_YES);
}

static void ZEND_FASTCALL alcsThis0(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	RETURN_OBJ_COPY(Z_OBJ_P(ZEND_THIS));
}

static void ZEND_FASTCALL alcsThis2(INTERNAL_FUNCTION_PARAMETERS)
{
	PT_ARGS(2, 2);
	RETURN_OBJ_COPY(Z_OBJ_P(ZEND_THIS));
}

static void ZEND_FASTCALL alcsError0(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	PT_RETURN_VAL(pt_type_new_error_type());
}

static void ZEND_FASTCALL alcsError1(INTERNAL_FUNCTION_PARAMETERS)
{
	PT_ARGS(1, 1);
	PT_RETURN_VAL(pt_type_new_error_type());
}

static void ZEND_FASTCALL alcsString0(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	PT_RETURN_VAL(pt_type_new_string_type());
}

static void ZEND_FASTCALL alcsString1(INTERNAL_FUNCTION_PARAMETERS)
{
	PT_ARGS(1, 1);
	PT_RETURN_VAL(pt_type_new_string_type());
}

static void ZEND_FASTCALL alcsObjectWithoutClass0(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	PT_RETURN_VAL(pt_type_new_object_without_class_type());
}

/* (Type $type) → a Type */
static void pt_alcs_one_type(INTERNAL_FUNCTION_PARAMETERS, zv::Val (AccessoryLowercaseStringType::*method)(zval *) const)
{
	zval *type;
	if (!zp::parse<zp::Obj>(execute_data, type)) RETURN_THROWS();
	PT_RETURN_VAL((PT_THIS.*method)(type));
}

void pt_register_accessory_lowercase_string_type()
{
	reg::Class cls("PHPStan\\Type\\Accessory\\AccessoryLowercaseStringType");
	ptdecl::AccessoryLowercaseStringType::declareClass(cls);
	ptdecl::AccessoryLowercaseStringType::declareProperties(cls);

	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
	});

	cls.method(sigs::getReferencedClasses, alcsEmptyArray0);
	cls.method(sigs::getObjectClassNames, alcsEmptyArray0);
	cls.op(PT_OP_GET_OBJECT_CLASS_NAMES, PT_OP_LAMBDA { return pt_op_empty_array(); });
	cls.method(sigs::getObjectClassReflections, alcsEmptyArray0);
	cls.method(sigs::getConstantStrings, alcsEmptyArray0);

	cls.method<&AccessoryLowercaseStringType::accepts, zp::Obj, zp::Bool>(sigs::accepts);
	cls.op(PT_OP_ACCEPTS, PT_OP_LAMBDA { return AccessoryLowercaseStringType(self).accepts(argv, (Z_TYPE(argv[1]) == IS_TRUE)); });

	cls.method(sigs::isSuperTypeOf, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_alcs_one_type(INTERNAL_FUNCTION_PARAM_PASSTHRU, &AccessoryLowercaseStringType::isSuperTypeOf);
	});
	cls.op<PT_OP_IS_SUPER_TYPE_OF, &AccessoryLowercaseStringType::isSuperTypeOf>();

	cls.method(sigs::isSubTypeOf, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_alcs_one_type(INTERNAL_FUNCTION_PARAM_PASSTHRU, &AccessoryLowercaseStringType::isSubTypeOf);
	});
	cls.op<PT_OP_IS_SUB_TYPE_OF, &AccessoryLowercaseStringType::isSubTypeOf>();

	cls.method(sigs::isAcceptedBy, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *acceptingType;
		bool strictTypes;
		if (!zp::parse<zp::Obj, zp::Bool>(execute_data, acceptingType, strictTypes)) RETURN_THROWS();
		PT_RETURN_VAL(PT_THIS.isAcceptedBy(acceptingType));
	});

	cls.method(sigs::equals, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *type;
		if (!zp::parse<zp::Obj>(execute_data, type)) RETURN_THROWS();
		RETURN_BOOL(AccessoryLowercaseStringType::equals(type));
	});
	cls.op(PT_OP_EQUALS, PT_OP_LAMBDA { return zv::Val::boolean(AccessoryLowercaseStringType::equals(argv)); });

	cls.method(sigs::describe, [](INTERNAL_FUNCTION_PARAMETERS) {
		PT_ARGS(1, 1);
		RETURN_STRINGL("lowercase-string", sizeof("lowercase-string") - 1);
	});
	cls.op(PT_OP_DESCRIBE, PT_OP_LAMBDA { return zv::Val::string("lowercase-string", sizeof("lowercase-string") - 1); });

	cls.method(sigs::isOffsetAccessible, alcsYes0);
	cls.method(sigs::isOffsetAccessLegal, alcsYes0);

	cls.method(sigs::hasOffsetValueType, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *offsetType;
		if (!zp::parse<zp::Obj>(execute_data, offsetType)) RETURN_THROWS();
		zend_long value = AccessoryLowercaseStringType::hasOffsetValueType(offsetType);
		if (UNEXPECTED(value < 0)) RETURN_THROWS();
		PT_RETURN_TRINARY(value);
	});

	cls.method(sigs::getOffsetValueType, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_alcs_one_type(INTERNAL_FUNCTION_PARAM_PASSTHRU, &AccessoryLowercaseStringType::getOffsetValueType);
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

	cls.method(sigs::setExistingOffsetValueType, alcsThis2);
	cls.method(sigs::unsetOffset, alcsError1);
	cls.method(sigs::toNumber, alcsError0);
	cls.method(sigs::toBitwiseNotType, alcsString0);
	cls.method(sigs::toAbsoluteNumber, alcsError0);

	cls.method<&AccessoryLowercaseStringType::toInteger>(sigs::toInteger);

	cls.method<&AccessoryLowercaseStringType::toFloat>(sigs::toFloat);

	cls.method(sigs::toString, alcsThis0);

	cls.method<&AccessoryLowercaseStringType::toBoolean>(sigs::toBoolean);

	cls.method<&AccessoryLowercaseStringType::toArray>(sigs::toArray);

	cls.method(sigs::toArrayKey, alcsThis0);
	cls.op(PT_OP_TO_ARRAY_KEY, PT_OP_LAMBDA { return pt_op_this(self); });

	cls.method<&AccessoryLowercaseStringType::toCoercedArgumentType, zp::Bool>(sigs::toCoercedArgumentType);

	cls.method(sigs::isNull, alcsNo0);
	cls.op(PT_OP_IS_NULL, PT_OP_LAMBDA { return pt_op_trinary(PT_TRI_NO); });
	cls.method(sigs::isConstantValue, alcsMaybe0);
	cls.method(sigs::isConstantScalarValue, alcsMaybe0);
	cls.op(PT_OP_IS_CONSTANT_SCALAR_VALUE, PT_OP_LAMBDA { return pt_op_trinary(PT_TRI_MAYBE); });
	cls.method(sigs::getConstantScalarTypes, alcsEmptyArray0);
	cls.method(sigs::getConstantScalarValues, alcsEmptyArray0);
	cls.op(PT_OP_GET_CONSTANT_SCALAR_VALUES, PT_OP_LAMBDA { return pt_op_empty_array(); });
	cls.method(sigs::isTrue, alcsNo0);
	cls.method(sigs::isFalse, alcsNo0);
	cls.method(sigs::isBoolean, alcsNo0);
	cls.op(PT_OP_IS_BOOLEAN, PT_OP_LAMBDA { return pt_op_trinary(PT_TRI_NO); });
	cls.method(sigs::isFloat, alcsNo0);
	cls.op(PT_OP_IS_FLOAT, PT_OP_LAMBDA { return pt_op_trinary(PT_TRI_NO); });
	cls.method(sigs::isInteger, alcsNo0);
	cls.op(PT_OP_IS_INTEGER, PT_OP_LAMBDA { return pt_op_trinary(PT_TRI_NO); });
	cls.method(sigs::isString, alcsYes0);
	cls.op(PT_OP_IS_STRING, PT_OP_LAMBDA { return pt_op_trinary(PT_TRI_YES); });
	cls.method(sigs::isNumericString, alcsMaybe0);
	cls.method(sigs::isDecimalIntegerString, alcsMaybe0);
	cls.method(sigs::isNonEmptyString, alcsMaybe0);
	cls.method(sigs::isNonFalsyString, alcsMaybe0);
	cls.method(sigs::isLiteralString, alcsMaybe0);
	cls.method(sigs::isLowercaseString, alcsYes0);
	cls.method(sigs::isClassString, alcsMaybe0);
	cls.method(sigs::isUppercaseString, alcsMaybe0);
	cls.method(sigs::getClassStringObjectType, alcsObjectWithoutClass0);
	cls.method(sigs::getObjectTypeOrClassStringObjectType, alcsObjectWithoutClass0);
	cls.method(sigs::isVoid, alcsNo0);
	cls.op(PT_OP_IS_VOID, PT_OP_LAMBDA { return pt_op_trinary(PT_TRI_NO); });
	cls.method(sigs::isScalar, alcsYes0);
	cls.method(sigs::hasMethod, alcsMaybe1);

	cls.method(sigs::looseCompare, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *type, *phpVersion;
		if (!zp::parse<zp::Obj, zp::Zval>(execute_data, type, phpVersion)) RETURN_THROWS();
		PT_RETURN_VAL(PT_THIS.looseCompare(type));
	});

	cls.method("traverse", reg::Public, 1, { reg::callableArg("cb") }, pt_type_identity_traverse_handler(), &ptret::type);
	cls.op(PT_OP_TRAVERSE, PT_OP_LAMBDA { return pt_op_traverse_identity(self); });
	cls.method(sigs::traverseSimultaneously, alcsThis2);
	cls.method(sigs::generalize, alcsString1);

	cls.method(sigs::exponentiate, [](INTERNAL_FUNCTION_PARAMETERS) {
		PT_ARGS(1, 1);
		PT_RETURN_VAL(AccessoryLowercaseStringType::exponentiate());
	});

	cls.method(sigs::getFiniteTypes, alcsEmptyArray0);

	cls.method(sigs::getDefaultBaseType, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		PT_RETURN_VAL(pt_type_new_string_type());
	});

	cls.method<&AccessoryLowercaseStringType::toPhpDocNode>(sigs::toPhpDocNode);

	cls.method(sigs::hasTemplateOrLateResolvableType, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_FALSE;
	});
	cls.op(PT_OP_HAS_TEMPLATE_OR_LATE_RESOLVABLE_TYPE, PT_OP_LAMBDA { return zv::Val::boolean(false); });

	/* the traits, in the twin's `use` order (UndecidedComparisonCompoundTypeTrait
	 * brings UndecidedComparisonTypeTrait with it); the class body above wins
	 * over every name it declares */
	ptdecl::AccessoryLowercaseStringType::registerTraits(cls);

	cls.shadow(&pt_ce_accessory_lowercase_string_type);
}

/* }}} */
