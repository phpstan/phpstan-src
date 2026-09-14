/*
 * PHPStanTurbo\AccessoryDecimalIntegerStringType — native implementation of
 * PHPStan\Type\Accessory\AccessoryDecimalIntegerStringType.
 *
 * State is the twin's promoted `private bool $inverse = false`, a declared
 * typed property slot (IS_PROP_UNINIT until the constructor writes it), so
 * the std object handlers do GC/clone.
 *
 * The `$this->method()` calls the twin makes (equals(), isSubTypeOf(),
 * hasOffsetValueType(), toNumber(), toInteger(), toFloat(), toBoolean(),
 * isNonEmptyString(), isNonFalsyString()) go through the object's class
 * entry — a subclass may have overridden them — with a direct C++ call when
 * the object is exactly an AccessoryDecimalIntegerStringType. The private
 * slot of another instance (`$type->inverse`) is read directly, as the twin
 * does from inside the class.
 */

#include "TypeTraits.h"
#include "generated/AccessoryDecimalIntegerStringType.h"

namespace slots = ptdecl::AccessoryDecimalIntegerStringType::slot;
namespace sigs = ptdecl::AccessoryDecimalIntegerStringType::sig;

zend_class_entry *pt_ce_accessory_decimal_integer_string_type = nullptr;

namespace phpstanturbo {

/* Mirrors PHPStan\Type\Accessory\AccessoryDecimalIntegerStringType. State
 * lives in the PHP object's $inverse. */
class AccessoryDecimalIntegerStringType
{
public:
	explicit AccessoryDecimalIntegerStringType(zend_object *self) : self(self) {}

	/* __construct(private bool $inverse = false) */
	void construct(bool inverse)
	{
		zv::ObjRef(self).propAtWrite(slots::inverse, zv::Val::boolean(inverse));
		Z_PROP_FLAG_P(OBJ_PROP_NUM(self, slots::inverse)) = 0; /* no longer IS_PROP_UNINIT */
	}

	/* $this->inverse; false with an Error pending when the constructor never
	 * ran — the twin's typed-property read raises the same */
	[[nodiscard]] bool inverse(bool &out) const { return inverseOf(self, out); }

	static bool inverseOf(zend_object *object, bool &out)
	{
		zval *slot = OBJ_PROP_NUM(object, slots::inverse);
		if (UNEXPECTED(Z_TYPE_P(slot) != IS_TRUE && Z_TYPE_P(slot) != IS_FALSE)) {
			zend_throw_error(NULL, "Typed property %s::$inverse must not be accessed before initialization", ZSTR_VAL(pt_ce_accessory_decimal_integer_string_type->name));
			return false;
		}
		out = Z_TYPE_P(slot) == IS_TRUE;
		return true;
	}

	/* yes for a string whose decimal-int verdict matches the direction; the
	 * CompoundType callback; else string-and-(negated-)decimal-int as a
	 * fresh result; UNDEF = pending exception */
	zv::Val accepts(zval *type, bool strictTypes) const
	{
		zend_long isDecimalIntegerString = pt_type_call_trinary(Z_OBJ_P(type), PT_LC("isdecimalintegerstring"), 0, NULL);
		if (UNEXPECTED(isDecimalIntegerString < 0)) return zv::Val();
		zend_long isString = pt_type_call_trinary(Z_OBJ_P(type), PT_LC("isstring"), 0, NULL);
		if (UNEXPECTED(isString < 0)) return zv::Val();
		bool inverse = false;
		if (UNEXPECTED(!this->inverse(inverse))) return zv::Val();
		if (isString == PT_TRI_YES && (inverse ? isDecimalIntegerString == PT_TRI_NO : isDecimalIntegerString == PT_TRI_YES)) {
			return pt_type_accepts_result(PT_TRI_YES);
		}
		bool compound;
		if (UNEXPECTED(!pt_type_instanceof(type, PT_CLASS_COMPOUND_TYPE, compound))) return zv::Val();
		if (compound) {
			zv::Args args{self, strictTypes};
			return pt_type_call(Z_OBJ_P(type), PT_LC("isacceptedby"), 2, args);
		}
		return pt_type_new_accepts_result(pt_trinary_and(isString, inverse ? trinaryNegate(isDecimalIntegerString) : isDecimalIntegerString));
	}

	/* the CompoundType callback; yes for an equal type; else
	 * string-and-(negated-)decimal-int; UNDEF = pending exception */
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
		zend_long isDecimalIntegerString = pt_type_call_trinary(Z_OBJ_P(type), PT_LC("isdecimalintegerstring"), 0, NULL);
		if (UNEXPECTED(isDecimalIntegerString < 0)) return zv::Val();
		zend_long isString = pt_type_call_trinary(Z_OBJ_P(type), PT_LC("isstring"), 0, NULL);
		if (UNEXPECTED(isString < 0)) return zv::Val();
		bool inverse = false;
		if (UNEXPECTED(!this->inverse(inverse))) return zv::Val();
		return pt_type_new_is_super_type_of_result(pt_trinary_and(isString, inverse ? trinaryNegate(isDecimalIntegerString) : isDecimalIntegerString));
	}

	/* the union/intersection callback; yes for a numeric, lowercase or
	 * uppercase accessory when not inverted; else the other type's
	 * string-and-(negated-)decimal-int verdict, and'ed with maybe unless it
	 * equals $this; UNDEF = pending exception */
	zv::Val isSubTypeOf(zval *otherType) const
	{
		bool unionOrIntersection;
		if (UNEXPECTED(!isUnionOrIntersection(otherType, unionOrIntersection))) return zv::Val();
		if (unionOrIntersection) {
			zval selfZv;
			ZVAL_OBJ(&selfZv, self);
			return pt_type_call(Z_OBJ_P(otherType), PT_LC("issupertypeof"), 1, &selfZv);
		}
		zend_class_entry *otherCe = Z_OBJCE_P(otherType);
		if (instanceof_function(otherCe, pt_ce_accessory_numeric_string_type)
			|| instanceof_function(otherCe, pt_ce_accessory_lowercase_string_type)
			|| instanceof_function(otherCe, pt_ce_accessory_uppercase_string_type)
		) {
			bool inverse = false;
			if (UNEXPECTED(!this->inverse(inverse))) return zv::Val();
			if (!inverse) return pt_type_is_super_type_of_result(PT_TRI_YES);
		}
		zend_long isString = pt_type_call_trinary(Z_OBJ_P(otherType), PT_LC("isstring"), 0, NULL);
		if (UNEXPECTED(isString < 0)) return zv::Val();
		bool inverse = false;
		if (UNEXPECTED(!this->inverse(inverse))) return zv::Val();
		zend_long isDecimalIntegerString = pt_type_call_trinary(Z_OBJ_P(otherType), PT_LC("isdecimalintegerstring"), 0, NULL);
		if (UNEXPECTED(isDecimalIntegerString < 0)) return zv::Val();
		zend_long otherTypeResult = pt_trinary_and(isString, inverse ? trinaryNegate(isDecimalIntegerString) : isDecimalIntegerString);
		/* $otherType->equals($this) */
		zval selfZv;
		ZVAL_OBJ(&selfZv, self);
		zv::Val equal = pt_type_call(Z_OBJ_P(otherType), PT_LC("equals"), 1, &selfZv);
		if (UNEXPECTED(equal.isUndef())) return zv::Val();
		return pt_type_new_is_super_type_of_result(pt_trinary_and(otherTypeResult, zend_is_true(equal.raw()) ? PT_TRI_YES : PT_TRI_MAYBE));
	}

	/* $this->isSubTypeOf($acceptingType)->toAcceptsResult() */
	zv::Val isAcceptedBy(zval *acceptingType) const
	{
		return pt_type_sub_type_to_accepts_result(isExact() ? isSubTypeOf(acceptingType) : pt_type_call(self, PT_LC("issubtypeof"), 1, acceptingType));
	}

	/* $type instanceof self && $this->inverse === $type->inverse; false =
	 * pending exception */
	bool equals(zval *type, bool &out) const
	{
		if (!instanceof_function(Z_OBJCE_P(type), pt_ce_accessory_decimal_integer_string_type)) {
			out = false;
			return true;
		}
		bool inverse = false, otherInverse = false;
		if (UNEXPECTED(!this->inverse(inverse) || !inverseOf(Z_OBJ_P(type), otherInverse))) return false;
		out = inverse == otherInverse;
		return true;
	}

	/* 'non-decimal-int-string' / 'decimal-int-string'; UNDEF = pending
	 * exception */
	zv::Val describe() const
	{
		bool inverse = false;
		if (UNEXPECTED(!this->inverse(inverse))) return zv::Val();
		return inverse ? zv::Val::string(PT_LC("non-decimal-int-string")) : zv::Val::string(PT_LC("decimal-int-string"));
	}

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

	/* null when inverted; string&decimal-int-string&non-falsy-string (with
	 * $this) for the constant '0', null otherwise; UNDEF = pending
	 * exception */
	zv::Val tryRemove(zval *typeToRemove) const
	{
		bool inverse = false;
		if (UNEXPECTED(!this->inverse(inverse))) return zv::Val();
		if (inverse) return zv::Val::null();
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

	/* new UnionType([$this->toInteger(), $this->toFloat()]) when inverted,
	 * $this->toInteger() otherwise; UNDEF = pending exception */
	zv::Val toNumber() const
	{
		bool inverse = false;
		if (UNEXPECTED(!this->inverse(inverse))) return zv::Val();
		if (inverse) {
			zv::Val integer = thisToInteger();
			if (UNEXPECTED(integer.isUndef())) return zv::Val();
			zv::Val floating = thisToFloat();
			if (UNEXPECTED(floating.isUndef())) return zv::Val();
			zv::Arr types = zv::Arr::create(2);
			types.push(std::move(integer));
			types.push(std::move(floating));
			return pt_type_new_union(std::move(types));
		}
		return thisToInteger();
	}

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

	/* string&non-empty-string when $this->isNonEmptyString() is yes (the
	 * decimal-integer property does not survive `~`), string otherwise;
	 * UNDEF = pending exception */
	zv::Val toBitwiseNotType() const
	{
		zend_long nonEmpty = isExact() ? isNonEmptyString() : pt_type_call_trinary(self, PT_LC("isnonemptystring"), 0, NULL);
		if (UNEXPECTED(nonEmpty < 0)) return zv::Val();
		if (nonEmpty == PT_TRI_YES) return pt_type_new_string_with_accessory(pt_accessory_non_empty_string_type_new);
		return pt_type_new_string_type();
	}

	/* $this->isNonFalsyString()->negate()->toBooleanType(); UNDEF = pending
	 * exception */
	zv::Val toBoolean() const
	{
		zend_long nonFalsy = isExact() ? isNonFalsyString() : pt_type_call_trinary(self, PT_LC("isnonfalsystring"), 0, NULL);
		if (UNEXPECTED(nonFalsy < 0)) return zv::Val();
		zend_long negated = trinaryNegate(nonFalsy);
		zval result;
		if (negated == PT_TRI_MAYBE) {
			if (UNEXPECTED(!pt_boolean_type_new(&result))) return zv::Val();
			return zv::Val::adopt(result);
		}
		if (UNEXPECTED(!pt_constant_boolean_type_new(&result, negated == PT_TRI_YES))) return zv::Val();
		return zv::Val::adopt(result);
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

	/* $this when inverted, new IntegerType() otherwise; UNDEF = pending
	 * exception */
	zv::Val toArrayKey() const
	{
		bool inverse = false;
		if (UNEXPECTED(!this->inverse(inverse))) return zv::Val();
		if (inverse) return thisValue();
		return toInteger();
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
		zv::Val boolean = isExact() ? toBoolean() : pt_type_call(self, PT_LC("toboolean"), 0, NULL);
		if (UNEXPECTED(boolean.isUndef())) return zv::Val();
		zv::Args args{integer.raw(), floating.raw(), self, boolean.raw()};
		return pt_type_call_static(PT_CLASS_TYPE_COMBINATOR, PT_LC("union"), 4, args);
	}

	/* maybe when inverted, no otherwise; -1 = pending exception */
	[[nodiscard]] zend_long isCallable() const
	{
		bool inverse = false;
		if (UNEXPECTED(!this->inverse(inverse))) return -1;
		return inverse ? PT_TRI_MAYBE : PT_TRI_NO;
	}

	/* [new TrivialParametersAcceptor()] when inverted, a
	 * ShouldNotHappenException otherwise; UNDEF = pending exception */
	zv::Val getCallableParametersAcceptors() const
	{
		bool inverse = false;
		if (UNEXPECTED(!this->inverse(inverse))) return zv::Val();
		if (inverse) {
			zv::Val acceptor = pt_type_new(PT_CLASS_TRIVIAL_PARAMETERS_ACCEPTOR, 0, NULL);
			if (UNEXPECTED(acceptor.isUndef())) return zv::Val();
			zv::Arr acceptors = zv::Arr::create(1);
			acceptors.push(std::move(acceptor));
			return zv::Val(std::move(acceptors));
		}
		pt_throw_should_not_happen();
		return zv::Val();
	}

	/* the string accessory verdicts: maybe when inverted, yes otherwise;
	 * -1 = pending exception */
	[[nodiscard]] zend_long isNumericString() const { return maybeWhenInverse(); }
	zend_long isNonEmptyString() const { return maybeWhenInverse(); }
	zend_long isLowercaseString() const { return maybeWhenInverse(); }
	zend_long isUppercaseString() const { return maybeWhenInverse(); }

	/* TrinaryLogic::createFromBoolean(!$this->inverse); -1 = pending
	 * exception */
	[[nodiscard]] zend_long isDecimalIntegerString() const
	{
		bool inverse = false;
		if (UNEXPECTED(!this->inverse(inverse))) return -1;
		return inverse ? PT_TRI_NO : PT_TRI_YES;
	}

	/* maybe; the trait-free answer the twin spells out (a subclass may
	 * override it — toBoolean() and toBitwiseNotType() ask through the
	 * object's class) */
	static zend_long isNonFalsyString() { return PT_TRI_MAYBE; }

	/* new BooleanType() when inverted (numeric or empty, nothing decidable);
	 * else new ConstantBooleanType(false) for null and for a string that is
	 * not numeric, new BooleanType() otherwise; UNDEF = pending exception */
	zv::Val looseCompare(zval *type) const
	{
		bool inverse = false;
		if (UNEXPECTED(!this->inverse(inverse))) return zv::Val();
		zval result;
		if (!inverse) {
			zend_long isNull = pt_type_call_trinary(Z_OBJ_P(type), PT_LC("isnull"), 0, NULL);
			if (UNEXPECTED(isNull < 0)) return zv::Val();
			if (isNull == PT_TRI_YES) {
				if (UNEXPECTED(!pt_constant_boolean_type_new(&result, false))) return zv::Val();
				return zv::Val::adopt(result);
			}
			zend_long isString = pt_type_call_trinary(Z_OBJ_P(type), PT_LC("isstring"), 0, NULL);
			if (UNEXPECTED(isString < 0)) return zv::Val();
			if (isString == PT_TRI_YES) {
				zend_long isNumericString = pt_type_call_trinary(Z_OBJ_P(type), PT_LC("isnumericstring"), 0, NULL);
				if (UNEXPECTED(isNumericString < 0)) return zv::Val();
				if (isNumericString == PT_TRI_NO) {
					if (UNEXPECTED(!pt_constant_boolean_type_new(&result, false))) return zv::Val();
					return zv::Val::adopt(result);
				}
			}
		}
		if (UNEXPECTED(!pt_boolean_type_new(&result))) return zv::Val();
		return zv::Val::adopt(result);
	}

	/* new StringType() */
	static zv::Val generalize() { return pt_type_new_string_type(); }

	/* new BenevolentUnionType([new FloatType(), new IntegerType()]) */
	static zv::Val exponentiate() { return pt_type_new_float_or_int_benevolent_union(); }

	/* new IdentifierTypeNode('non-decimal-int-string' / 'decimal-int-string');
	 * UNDEF = pending exception */
	zv::Val toPhpDocNode() const
	{
		bool inverse = false;
		if (UNEXPECTED(!this->inverse(inverse))) return zv::Val();
		if (inverse) return pt_type_new_identifier_type_node(PT_LC("non-decimal-int-string"));
		return pt_type_new_identifier_type_node(PT_LC("decimal-int-string"));
	}

private:
	zend_object *self;

	bool isExact() const { return self->ce == pt_ce_accessory_decimal_integer_string_type; }

	zv::Val thisValue() const { return pt_this_value(self); }

	/* $this->equals($type) — through the object's class; false = pending
	 * exception */
	[[nodiscard]] bool thisEquals(zval *type, bool &out) const
	{
		if (EXPECTED(isExact())) return equals(type, out);
		return pt_type_call_bool(self, PT_LC("equals"), 1, type, out);
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

	/* $this->inverse ? TrinaryLogic::createMaybe() : TrinaryLogic::createYes();
	 * -1 = pending exception */
	[[nodiscard]] zend_long maybeWhenInverse() const
	{
		bool inverse = false;
		if (UNEXPECTED(!this->inverse(inverse))) return -1;
		return inverse ? PT_TRI_MAYBE : PT_TRI_YES;
	}

	/* TrinaryLogic::and(): the minimum */

	/* TrinaryLogic::negate(): 3 >> $value */
	static zend_long trinaryNegate(zend_long a) { return 3 >> a; }

	/* $type instanceof UnionType || $type instanceof IntersectionType;
	 * false = pending exception */
	[[nodiscard]] static bool isUnionOrIntersection(zval *type, bool &out) { return pt_type_is_union_or_intersection(type, out); }
};

} // namespace phpstanturbo

using phpstanturbo::AccessoryDecimalIntegerStringType;

bool pt_accessory_decimal_integer_string_type_new(zval *out, bool inverse)
{
	if (UNEXPECTED(object_init_ex(out, pt_ce_accessory_decimal_integer_string_type) != SUCCESS)) return false;
	AccessoryDecimalIntegerStringType(Z_OBJ_P(out)).construct(inverse);
	return true;
}

/* {{{ engine ABI glue: parameter parsing + registration */

#define PT_THIS AccessoryDecimalIntegerStringType(Z_OBJ_P(ZEND_THIS))

/* the trivial bodies the twin repeats (one handler per body and arity;
 * each method is still declared exactly once, at its registration line) */

static void ZEND_FASTCALL adisEmptyArray0(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	RETURN_EMPTY_ARRAY();
}

static void ZEND_FASTCALL adisNo0(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	PT_RETURN_TRINARY(PT_TRI_NO);
}

static void ZEND_FASTCALL adisMaybe0(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	PT_RETURN_TRINARY(PT_TRI_MAYBE);
}

static void ZEND_FASTCALL adisYes0(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	PT_RETURN_TRINARY(PT_TRI_YES);
}

static void ZEND_FASTCALL adisMaybeWhenInverse0(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	PT_RETURN_TRINARY_OR_THROW(PT_THIS.isNumericString());
}

static void ZEND_FASTCALL adisThis0(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	RETURN_OBJ_COPY(Z_OBJ_P(ZEND_THIS));
}

static void ZEND_FASTCALL adisThis2(INTERNAL_FUNCTION_PARAMETERS)
{
	PT_ARGS(2, 2);
	RETURN_OBJ_COPY(Z_OBJ_P(ZEND_THIS));
}

static void ZEND_FASTCALL adisError0(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	PT_RETURN_VAL(pt_type_new_error_type());
}

static void ZEND_FASTCALL adisError1(INTERNAL_FUNCTION_PARAMETERS)
{
	PT_ARGS(1, 1);
	PT_RETURN_VAL(pt_type_new_error_type());
}

static void ZEND_FASTCALL adisString1(INTERNAL_FUNCTION_PARAMETERS)
{
	PT_ARGS(1, 1);
	PT_RETURN_VAL(pt_type_new_string_type());
}

/* (Type $type) → a Type */
static void pt_adis_one_type(INTERNAL_FUNCTION_PARAMETERS, zv::Val (AccessoryDecimalIntegerStringType::*method)(zval *) const)
{
	zval *type;
	if (!zp::parse<zp::Obj>(execute_data, type)) RETURN_THROWS();
	PT_RETURN_VAL((PT_THIS.*method)(type));
}

/* () → a Type */
static void pt_adis_no_args(INTERNAL_FUNCTION_PARAMETERS, zv::Val (AccessoryDecimalIntegerStringType::*method)() const)
{
	ZEND_PARSE_PARAMETERS_NONE();
	PT_RETURN_VAL((PT_THIS.*method)());
}

void pt_register_accessory_decimal_integer_string_type()
{
	reg::Class cls("PHPStan\\Type\\Accessory\\AccessoryDecimalIntegerStringType");
	ptdecl::AccessoryDecimalIntegerStringType::declareClass(cls);
	/* "inverse" must stay the first declared property (slots::inverse) */
	ptdecl::AccessoryDecimalIntegerStringType::declareProperties(cls);

	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		bool inverse = false;
		if (!zp::parse<zp::Opt<zp::Bool>>(execute_data, inverse)) RETURN_THROWS();
		PT_THIS.construct(inverse);
	});

	cls.method(sigs::getReferencedClasses, adisEmptyArray0);
	cls.method(sigs::getObjectClassNames, adisEmptyArray0);
	cls.method(sigs::getObjectClassReflections, adisEmptyArray0);
	cls.method(sigs::getConstantStrings, adisEmptyArray0);

	cls.method<&AccessoryDecimalIntegerStringType::accepts, zp::Obj, zp::Bool>(sigs::accepts);

	cls.method(sigs::isSuperTypeOf, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_adis_one_type(INTERNAL_FUNCTION_PARAM_PASSTHRU, &AccessoryDecimalIntegerStringType::isSuperTypeOf);
	});

	cls.method(sigs::isSubTypeOf, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_adis_one_type(INTERNAL_FUNCTION_PARAM_PASSTHRU, &AccessoryDecimalIntegerStringType::isSubTypeOf);
	});

	cls.method(sigs::isAcceptedBy, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *acceptingType;
		bool strictTypes;
		if (!zp::parse<zp::Obj, zp::Bool>(execute_data, acceptingType, strictTypes)) RETURN_THROWS();
		PT_RETURN_VAL(PT_THIS.isAcceptedBy(acceptingType));
	});

	cls.method<&AccessoryDecimalIntegerStringType::equals, zp::Obj>(sigs::equals);

	cls.method(sigs::describe, [](INTERNAL_FUNCTION_PARAMETERS) {
		PT_ARGS(1, 1);
		PT_RETURN_VAL(PT_THIS.describe());
	});

	cls.method(sigs::isOffsetAccessible, adisYes0);
	cls.method(sigs::isOffsetAccessLegal, adisYes0);

	cls.method(sigs::hasOffsetValueType, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *offsetType;
		if (!zp::parse<zp::Obj>(execute_data, offsetType)) RETURN_THROWS();
		PT_RETURN_TRINARY_OR_THROW(AccessoryDecimalIntegerStringType::hasOffsetValueType(offsetType));
	});

	cls.method(sigs::getOffsetValueType, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_adis_one_type(INTERNAL_FUNCTION_PARAM_PASSTHRU, &AccessoryDecimalIntegerStringType::getOffsetValueType);
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

	cls.method(sigs::setExistingOffsetValueType, adisThis2);
	cls.method(sigs::unsetOffset, adisError1);

	cls.method(sigs::tryRemove, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_adis_one_type(INTERNAL_FUNCTION_PARAM_PASSTHRU, &AccessoryDecimalIntegerStringType::tryRemove);
	});

	cls.method(sigs::toNumber, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_adis_no_args(INTERNAL_FUNCTION_PARAM_PASSTHRU, &AccessoryDecimalIntegerStringType::toNumber);
	});

	cls.method(sigs::toAbsoluteNumber, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_adis_no_args(INTERNAL_FUNCTION_PARAM_PASSTHRU, &AccessoryDecimalIntegerStringType::toAbsoluteNumber);
	});

	cls.method(sigs::toBitwiseNotType, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_adis_no_args(INTERNAL_FUNCTION_PARAM_PASSTHRU, &AccessoryDecimalIntegerStringType::toBitwiseNotType);
	});

	cls.method(sigs::toBoolean, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_adis_no_args(INTERNAL_FUNCTION_PARAM_PASSTHRU, &AccessoryDecimalIntegerStringType::toBoolean);
	});

	cls.method<&AccessoryDecimalIntegerStringType::toInteger>(sigs::toInteger);

	cls.method<&AccessoryDecimalIntegerStringType::toFloat>(sigs::toFloat);

	cls.method(sigs::toString, adisThis0);

	cls.method(sigs::toArray, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_adis_no_args(INTERNAL_FUNCTION_PARAM_PASSTHRU, &AccessoryDecimalIntegerStringType::toArray);
	});

	cls.method(sigs::toArrayKey, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_adis_no_args(INTERNAL_FUNCTION_PARAM_PASSTHRU, &AccessoryDecimalIntegerStringType::toArrayKey);
	});

	cls.method<&AccessoryDecimalIntegerStringType::toCoercedArgumentType, zp::Bool>(sigs::toCoercedArgumentType);

	cls.method(sigs::isNull, adisNo0);
	cls.method(sigs::isConstantValue, adisMaybe0);
	cls.method(sigs::isConstantScalarValue, adisMaybe0);
	cls.method(sigs::getConstantScalarTypes, adisEmptyArray0);
	cls.method(sigs::getConstantScalarValues, adisEmptyArray0);

	cls.method(sigs::isCallable, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		PT_RETURN_TRINARY_OR_THROW(PT_THIS.isCallable());
	});

	cls.method(sigs::getCallableParametersAcceptors, [](INTERNAL_FUNCTION_PARAMETERS) {
		PT_ARGS(1, 1);
		PT_RETURN_VAL(PT_THIS.getCallableParametersAcceptors());
	});

	cls.method(sigs::isTrue, adisNo0);
	cls.method(sigs::isFalse, adisNo0);
	cls.method(sigs::isBoolean, adisNo0);
	cls.method(sigs::isFloat, adisNo0);
	cls.method(sigs::isInteger, adisNo0);
	cls.method(sigs::isString, adisYes0);
	cls.method(sigs::isNumericString, adisMaybeWhenInverse0);

	cls.method(sigs::isDecimalIntegerString, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		PT_RETURN_TRINARY_OR_THROW(PT_THIS.isDecimalIntegerString());
	});

	cls.method(sigs::isNonEmptyString, adisMaybeWhenInverse0);
	cls.method(sigs::isNonFalsyString, adisMaybe0);
	cls.method(sigs::isLiteralString, adisMaybe0);
	cls.method(sigs::isLowercaseString, adisMaybeWhenInverse0);
	cls.method(sigs::isUppercaseString, adisMaybeWhenInverse0);
	cls.method(sigs::isClassString, adisNo0);
	cls.method(sigs::getClassStringObjectType, adisError0);
	cls.method(sigs::getObjectTypeOrClassStringObjectType, adisError0);
	cls.method(sigs::isVoid, adisNo0);
	cls.method(sigs::isScalar, adisYes0);

	cls.method(sigs::looseCompare, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *type, *phpVersion;
		if (!zp::parse<zp::Obj, zp::Zval>(execute_data, type, phpVersion)) RETURN_THROWS();
		PT_RETURN_VAL(PT_THIS.looseCompare(type));
	});

	cls.method("traverse", reg::Public, 1, { reg::callableArg("cb") }, pt_type_identity_traverse_handler(), &ptret::type);
	cls.method(sigs::traverseSimultaneously, adisThis2);
	cls.method(sigs::generalize, adisString1);

	cls.method(sigs::exponentiate, [](INTERNAL_FUNCTION_PARAMETERS) {
		PT_ARGS(1, 1);
		PT_RETURN_VAL(AccessoryDecimalIntegerStringType::exponentiate());
	});

	cls.method(sigs::getFiniteTypes, adisEmptyArray0);

	cls.method(sigs::getDefaultBaseType, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		PT_RETURN_VAL(pt_type_new_string_type());
	});

	cls.method(sigs::toPhpDocNode, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_adis_no_args(INTERNAL_FUNCTION_PARAM_PASSTHRU, &AccessoryDecimalIntegerStringType::toPhpDocNode);
	});

	cls.method(sigs::hasTemplateOrLateResolvableType, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_FALSE;
	});

	/* the traits, in the twin's `use` order (UndecidedComparisonCompoundTypeTrait
	 * brings UndecidedComparisonTypeTrait with it); the class body above wins
	 * over every name it declares */
	ptdecl::AccessoryDecimalIntegerStringType::registerTraits(cls);

	cls.shadow(&pt_ce_accessory_decimal_integer_string_type);
}

/* }}} */
