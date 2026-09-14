/*
 * PHPStanTurbo\StringType — native implementation of PHPStan\Type\StringType.
 *
 * Declared as PHPStan\Type\StringType itself at activation: not final
 * (ConstantStringType, ClassStringType, the PHP TemplateStringType and the
 * String*AcceptingObjectWithToStringType classes extend it), implementing
 * PHPStan\Type\Type.
 */

#include "TypeTraits.h"
#include "generated/StringType.h"

namespace sigs = ptdecl::StringType::sig;

zend_class_entry *pt_ce_string_type = nullptr;

namespace phpstanturbo {

/* Mirrors PHPStan\Type\StringType. The twin has no state. */
class StringType
{
public:
	explicit StringType(zend_object *self) : self(self) {}

	/* new StringType(): the constructor is empty, so instantiating the
	 * class is all `new` does; UNDEF = pending exception */
	static zv::Val create() { return pt_new_instance(pt_ce_string_type); }

	static const char *describe() { return "string"; }

	static zv::Val getConstantStrings() { return zv::Val(zv::Arr::empty()); }

	static zend_long isOffsetAccessible() { return PT_TRI_YES; }
	static zend_long isOffsetAccessLegal() { return PT_TRI_YES; }

	/* $offsetType->isInteger()->and(TrinaryLogic::createMaybe()) — the
	 * bitwise and TrinaryLogic::and() is; -1 = pending exception */
	[[nodiscard]] static zend_long hasOffsetValueType(zval *offsetType)
	{
		zend_long isInteger = pt_type_op_trinary(Z_OBJ_P(offsetType), PT_OP_IS_INTEGER, 0, NULL);
		if (UNEXPECTED(isInteger < 0)) return -1;
		return isInteger & PT_TRI_MAYBE;
	}

	/* new ErrorType() when $this->hasOffsetValueType($offsetType)->no(),
	 * non-empty-string otherwise; UNDEF = pending exception */
	zv::Val getOffsetValueType(zval *offsetType) const
	{
		zend_long has;
		if (EXPECTED(isExact())) {
			has = hasOffsetValueType(offsetType);
		} else {
			has = pt_type_call_trinary(self, PT_LC("hasoffsetvaluetype"), 1, offsetType);
		}
		if (UNEXPECTED(has < 0)) return zv::Val();
		if (has == PT_TRI_NO) return pt_type_new_error_type();
		return nonEmptyString();
	}

	/* new ErrorType() for a null offset or a value with no string form,
	 * non-empty-string for an integer or mixed offset, new ErrorType()
	 * otherwise; offsetType NULL = the twin's null; UNDEF = pending
	 * exception */
	static zv::Val setOffsetValueType(zval *offsetType, zval *valueType)
	{
		/* $offsetType === null — a NULL pointer from zpp, or a null zval from a
		 * caller forwarding the accessory types' parsed arguments */
		if (offsetType == NULL || Z_TYPE_P(offsetType) == IS_NULL) return pt_type_new_error_type();

		zv::Val valueStringType = pt_type_call(Z_OBJ_P(valueType), PT_LC("tostring"), 0, NULL);
		if (UNEXPECTED(valueStringType.isUndef())) return zv::Val();
		bool isError;
		if (UNEXPECTED(!pt_type_instanceof_ce(valueStringType.raw(), pt_ce_error_type, isError))) return zv::Val();
		if (isError) return pt_type_new_error_type();

		zend_long isInteger = pt_type_op_trinary(Z_OBJ_P(offsetType), PT_OP_IS_INTEGER, 0, NULL);
		if (UNEXPECTED(isInteger < 0)) return zv::Val();
		if (isInteger == PT_TRI_YES || instanceof_function(Z_OBJCE_P(offsetType), pt_ce_mixed_type)) return nonEmptyString();

		return pt_type_new_error_type();
	}

	/* $this */
	zv::Val setExistingOffsetValueType() const { return thisValue(); }

	static zv::Val unsetOffset() { return pt_type_new_error_type(); }

	/* yes for a StringType, the CompoundType callback, no for a non-object
	 * or under strict types, else whether the object's class has a native
	 * __toString(); UNDEF = pending exception */
	zv::Val accepts(zval *type, bool strictTypes) const
	{
		/* $type instanceof self */
		if (instanceof_function(Z_OBJCE_P(type), pt_ce_string_type)) return pt_type_accepts_result(PT_TRI_YES);

		bool compound;
		if (UNEXPECTED(!pt_type_instanceof(type, PT_CLASS_COMPOUND_TYPE, compound))) return zv::Val();
		if (compound) {
			zv::Args args{self, strictTypes};
			return pt_type_call(Z_OBJ_P(type), PT_LC("isacceptedby"), 2, args);
		}

		zv::Val thatClassNames = pt_type_op(Z_OBJ_P(type), PT_OP_GET_OBJECT_CLASS_NAMES, 0, NULL);
		if (UNEXPECTED(thatClassNames.isUndef())) return zv::Val();
		if (UNEXPECTED(!zv::Ref(thatClassNames.raw()).isArray())) {
			zend_type_error("phpstan_turbo: %s::getObjectClassNames() must return array", ZSTR_VAL(Z_OBJCE_P(type)->name));
			return zv::Val();
		}
		zv::ArrRef names(thatClassNames.raw());
		if (names.size() > 1) {
			pt_throw_should_not_happen();
			return zv::Val();
		}

		if (names.size() == 0 || strictTypes) return pt_type_accepts_result(PT_TRI_NO);

		/* $thatClassNames[0] */
		zv::Ref className = names.findIndex(0);
		if (UNEXPECTED(className.raw() == NULL || !className.isString())) {
			zend_type_error("phpstan_turbo: %s::getObjectClassNames() must return a list of strings", ZSTR_VAL(Z_OBJCE_P(type)->name));
			return zv::Val();
		}

		zv::Val reflectionProvider = pt_reflection_provider_instance();
		if (UNEXPECTED(reflectionProvider.isUndef())) return zv::Val();
		zv::Val hasClass = pt_reflection_provider_has_class_zv(Z_OBJ_P(reflectionProvider.raw()), className.raw());
		if (UNEXPECTED(hasClass.isUndef())) return zv::Val();
		if (!zend_is_true(hasClass.raw())) return pt_type_accepts_result(PT_TRI_NO);

		zv::Val typeClass = pt_reflection_provider_get_class(Z_OBJ_P(reflectionProvider.raw()), className.raw());
		if (UNEXPECTED(typeClass.isUndef())) return zv::Val();
		zv::Val toString = zv::Val::string("__toString", sizeof("__toString") - 1);
		zv::Val hasNativeMethod = pt_type_call(Z_OBJ_P(typeClass.raw()), PT_LC("hasnativemethod"), 1, toString.raw());
		if (UNEXPECTED(hasNativeMethod.isUndef())) return zv::Val();
		return pt_type_accepts_result(zend_is_true(hasNativeMethod.raw()) ? PT_TRI_YES : PT_TRI_NO);
	}

	static zv::Val toNumber() { return pt_type_new_error_type(); }

	/* new StringType() */
	static zv::Val toBitwiseNotType() { return create(); }

	static zv::Val toAbsoluteNumber() { return pt_type_new_error_type(); }

	/* new IntegerType() */
	static zv::Val toInteger()
	{
		return pt_val_of<pt_integer_type_new>();
	}

	/* new FloatType() — the shadowing class */
	static zv::Val toFloat()
	{
		return pt_val_of<pt_float_type_new>();
	}

	/* $this */
	zv::Val toString() const { return thisValue(); }

	/* new ConstantArrayType([new ConstantIntegerType(0)], [$this], [1],
	 * isList: TrinaryLogic::createYes()) — the named argument skips
	 * $optionalKeys, whose default is [] */
	zv::Val toArray() const { return pt_type_scalar_to_array(self); }

	/* $this unless ReportUnsafeArrayStringKeyCastingToggle::getLevel() is
	 * PREVENT; then $this / new IntegerType() when $this->isDecimalIntegerString()
	 * is no / yes, and int|(string&non-decimal-int) otherwise; UNDEF =
	 * pending exception */
	zv::Val toArrayKey() const
	{
		zv::Val level = pt_type_call_static(PT_CLASS_REPORT_UNSAFE_ARRAY_STRING_KEY_CASTING_TOGGLE, PT_LC("getlevel"), 0, NULL);
		if (UNEXPECTED(level.isUndef())) return zv::Val();
		zval *prevent = toggleConstant("PREVENT", sizeof("PREVENT") - 1);
		if (UNEXPECTED(prevent == NULL)) return zv::Val();
		/* $level !== ReportUnsafeArrayStringKeyCastingToggle::PREVENT */
		if (!zend_is_identical(level.raw(), prevent)) return thisValue();

		zend_long isDecimalIntString;
		if (EXPECTED(isExact())) {
			isDecimalIntString = isDecimalIntegerString();
		} else {
			isDecimalIntString = pt_type_call_trinary(self, PT_LC("isdecimalintegerstring"), 0, NULL);
			if (UNEXPECTED(isDecimalIntString < 0)) return zv::Val();
		}
		if (isDecimalIntString == PT_TRI_NO) return thisValue();
		if (isDecimalIntString == PT_TRI_YES) return toInteger();

		/* new UnionType([new IntegerType(), TypeCombinator::intersect($this,
		 * new AccessoryDecimalIntegerStringType(inverse: true))]) — the named
		 * argument is the constructor's first parameter */
		zv::Val integer = toInteger();
		if (UNEXPECTED(integer.isUndef())) return zv::Val();
		zval accessoryRaw;
		if (UNEXPECTED(!pt_accessory_decimal_integer_string_type_new(&accessoryRaw, true))) return zv::Val();
		zv::Val accessory = zv::Val::adopt(accessoryRaw);
		if (UNEXPECTED(accessory.isUndef())) return zv::Val();
		zv::Args args{self, accessory.raw()};
		zv::Val intersected = pt_type_combinator_call(PT_LC("intersect"), 2, args);
		if (UNEXPECTED(intersected.isUndef())) return zv::Val();
		zv::Arr types = zv::Arr::create(2);
		types.push(std::move(integer));
		types.push(std::move(intersected));
		return pt_type_new_union(std::move(types));
	}

	/* $this under strict types; otherwise TypeCombinator::union($this,
	 * $this->toBoolean()) when $this->isNumericString()->no(), and
	 * TypeCombinator::union($this->toInteger(), $this->toFloat(), $this,
	 * $this->toBoolean()) else; UNDEF = pending exception */
	zv::Val toCoercedArgumentType(bool strictTypes) const
	{
		if (strictTypes) return thisValue();

		zend_long isNumeric;
		if (EXPECTED(isExact())) {
			isNumeric = isNumericString();
		} else {
			isNumeric = pt_type_call_trinary(self, PT_LC("isnumericstring"), 0, NULL);
			if (UNEXPECTED(isNumeric < 0)) return zv::Val();
		}
		/* UndecidedBooleanTypeTrait's toBoolean(): new BooleanType() */
		zv::Val boolean;
		if (EXPECTED(isExact())) {
			zval created;
			if (UNEXPECTED(!pt_boolean_type_new(&created))) return zv::Val();
			boolean = zv::Val::adopt(created);
		} else {
			boolean = pt_type_call(self, PT_LC("toboolean"), 0, NULL);
			if (UNEXPECTED(boolean.isUndef())) return zv::Val();
		}
		if (isNumeric == PT_TRI_NO) {
			zv::Args args{self, boolean.raw()};
			return pt_type_combinator_call(PT_LC("union"), 2, args);
		}

		zv::Val integer = isExact() ? toInteger() : pt_type_call(self, PT_LC("tointeger"), 0, NULL);
		if (UNEXPECTED(integer.isUndef())) return zv::Val();
		zv::Val floatType = isExact() ? toFloat() : pt_type_call(self, PT_LC("tofloat"), 0, NULL);
		if (UNEXPECTED(floatType.isUndef())) return zv::Val();
		zv::Args args{integer.raw(), floatType.raw(), self, boolean.raw()};
		return pt_type_combinator_call(PT_LC("union"), 4, args);
	}

	static zend_long isNull() { return PT_TRI_NO; }
	static zend_long isTrue() { return PT_TRI_NO; }
	static zend_long isFalse() { return PT_TRI_NO; }
	static zend_long isBoolean() { return PT_TRI_NO; }
	static zend_long isFloat() { return PT_TRI_NO; }
	static zend_long isInteger() { return PT_TRI_NO; }
	static zend_long isString() { return PT_TRI_YES; }
	static zend_long isNumericString() { return PT_TRI_MAYBE; }
	static zend_long isDecimalIntegerString() { return PT_TRI_MAYBE; }
	static zend_long isNonEmptyString() { return PT_TRI_MAYBE; }
	static zend_long isNonFalsyString() { return PT_TRI_MAYBE; }
	static zend_long isLiteralString() { return PT_TRI_MAYBE; }
	static zend_long isLowercaseString() { return PT_TRI_MAYBE; }
	static zend_long isUppercaseString() { return PT_TRI_MAYBE; }
	static zend_long isClassString() { return PT_TRI_MAYBE; }

	/* new ObjectWithoutClassType() */
	static zv::Val getClassStringObjectType() { return pt_type_new_object_without_class_type(); }
	static zv::Val getObjectTypeOrClassStringObjectType() { return pt_type_new_object_without_class_type(); }

	static zend_long isScalar() { return PT_TRI_YES; }

	/* new ConstantBooleanType(false) for an array, new BooleanType()
	 * otherwise; UNDEF = pending exception */
	static zv::Val looseCompare(zval *type)
	{
		zend_long isArray = pt_type_op_trinary(Z_OBJ_P(type), PT_OP_IS_ARRAY, 0, NULL);
		if (UNEXPECTED(isArray < 0)) return zv::Val();
		zval result;
		if (isArray == PT_TRI_YES) {
			if (UNEXPECTED(!pt_constant_boolean_type_new(&result, false))) return zv::Val();
			return zv::Val::adopt(result);
		}
		if (UNEXPECTED(!pt_boolean_type_new(&result))) return zv::Val();
		return zv::Val::adopt(result);
	}

	/* maybe when $this->isClassString()->yes(), no otherwise; -1 = pending
	 * exception */
	[[nodiscard]] zend_long hasMethod() const
	{
		zend_long isClassString;
		if (EXPECTED(isExact())) {
			isClassString = StringType::isClassString();
		} else {
			isClassString = pt_type_call_trinary(self, PT_LC("isclassstring"), 0, NULL);
			if (UNEXPECTED(isClassString < 0)) return -1;
		}
		return isClassString == PT_TRI_YES ? PT_TRI_MAYBE : PT_TRI_NO;
	}

	/* TypeCombinator::intersect($this, new AccessoryNonEmptyStringType())
	 * for the empty ConstantStringType, new ConstantStringType('') for
	 * AccessoryNonEmptyStringType, null otherwise; UNDEF = pending
	 * exception */
	zv::Val tryRemove(zval *typeToRemove) const
	{
		if (instanceof_function(Z_OBJCE_P(typeToRemove), pt_ce_constant_string_type)) {
			zv::Val value = pt_constant_string_get_value(Z_OBJ_P(typeToRemove));
			if (UNEXPECTED(value.isUndef())) return zv::Val();
			if (ZSTR_LEN(zv::Ref(value.raw()).asString()) == 0) {
				zv::Val accessory = pt_type_new_shadowed(pt_accessory_non_empty_string_type_new);
				if (UNEXPECTED(accessory.isUndef())) return zv::Val();
				zv::Args args{self, accessory.raw()};
				return pt_type_combinator_call(PT_LC("intersect"), 2, args);
			}
		}

		if (instanceof_function(Z_OBJCE_P(typeToRemove), pt_ce_accessory_non_empty_string_type)) return pt_type_new_constant_string("", 0);

		return zv::Val::null();
	}

	static zv::Val getFiniteTypes() { return zv::Val(zv::Arr::empty()); }

	/* ExponentiateHelper::exponentiate($this, $exponent) */
	zv::Val exponentiate(zval *exponent) const
	{
		zv::Args args{self, exponent};
		return pt_type_call_static(PT_CLASS_EXPONENTIATE_HELPER, PT_LC("exponentiate"), 2, args);
	}

	/* new IdentifierTypeNode('string') */
	static zv::Val toPhpDocNode()
	{
		zv::Val name = zv::Val::string("string", sizeof("string") - 1);
		return pt_type_new(PT_CLASS_IDENTIFIER_TYPE_NODE, 1, name.raw());
	}

	static bool hasTemplateOrLateResolvableType() { return false; }

private:
	zend_object *self;

	/* exactly a StringType, none of its methods overridden: $this-calls
	 * can go straight to the C++ methods */
	bool isExact() const { return self->ce == pt_ce_string_type; }

	zv::Val thisValue() const { return pt_this_value(self); }

	/* new IntersectionType([new StringType(), new AccessoryNonEmptyStringType()]) */
	static zv::Val nonEmptyString()
	{
		zv::Val string = create();
		if (UNEXPECTED(string.isUndef())) return zv::Val();
		zv::Val accessory = pt_type_new_shadowed(pt_accessory_non_empty_string_type_new);
		if (UNEXPECTED(accessory.isUndef())) return zv::Val();
		zv::Arr types = zv::Arr::create(2);
		types.push(std::move(string));
		types.push(std::move(accessory));
		return pt_intersection_of(std::move(types));
	}

	/* ReportUnsafeArrayStringKeyCastingToggle::<name> — a literal class
	 * constant, borrowed; NULL = pending exception */
	[[nodiscard]] static zval *toggleConstant(const char *name, size_t len)
	{
		zend_class_entry *ce = pt_class(PT_CLASS_REPORT_UNSAFE_ARRAY_STRING_KEY_CASTING_TOGGLE);
		if (UNEXPECTED(ce == NULL)) return NULL;
		zend_class_constant *constant = (zend_class_constant *) zend_hash_str_find_ptr(&ce->constants_table, name, len);
		if (UNEXPECTED(constant == NULL)) {
			zend_throw_error(NULL, "phpstan_turbo: %s::%s not found", ZSTR_VAL(ce->name), name);
			return NULL;
		}
		if (UNEXPECTED(Z_TYPE(constant->value) == IS_CONSTANT_AST && zval_update_constant_ex(&constant->value, ce) != SUCCESS)) return NULL;
		return &constant->value;
	}
};

} // namespace phpstanturbo

using phpstanturbo::StringType;

bool pt_string_type_new(zval *out)
{
	return pt_val_into(StringType::create(), out);
}

zv::Val pt_string_type_accepts(zend_object *self, zval *type, bool strictTypes)
{
	return StringType(self).accepts(type, strictTypes);
}

zv::Val pt_string_type_has_offset_value_type(zend_object *self, zval *offsetType)
{
	(void) self;
	zend_long result = StringType::hasOffsetValueType(offsetType);
	if (UNEXPECTED(result < 0)) return zv::Val();
	return pt_type_trinary(result);
}

zv::Val pt_string_type_get_offset_value_type(zend_object *self, zval *offsetType)
{
	return StringType(self).getOffsetValueType(offsetType);
}

zv::Val pt_string_type_set_offset_value_type(zend_object *self, zval *offsetType, zval *valueType)
{
	(void) self;
	return StringType::setOffsetValueType(offsetType, valueType);
}

zv::Val pt_string_type_try_remove(zend_object *self, zval *typeToRemove)
{
	return StringType(self).tryRemove(typeToRemove);
}

/* {{{ engine ABI glue: parameter parsing + registration */

#define PT_THIS StringType(Z_OBJ_P(ZEND_THIS))

void pt_register_string_type()
{
	reg::Class cls("PHPStan\\Type\\StringType");
	ptdecl::StringType::declareClass(cls);
	ptdecl::StringType::declareProperties(cls);

	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
	});

	cls.method(sigs::describe, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *level;
		if (!zp::parse<zp::Obj>(execute_data, level)) RETURN_THROWS();
		RETURN_STRING(StringType::describe());
	});
	cls.op(PT_OP_DESCRIBE, PT_OP_LAMBDA { return pt_op_string(StringType::describe()); });

	cls.method<&StringType::getConstantStrings>(sigs::getConstantStrings);

	cls.method(sigs::isOffsetAccessible, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_COPY(pt_trinary_singleton(StringType::isOffsetAccessible()));
	});

	cls.method(sigs::isOffsetAccessLegal, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_COPY(pt_trinary_singleton(StringType::isOffsetAccessLegal()));
	});

	cls.method(sigs::hasOffsetValueType, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *offsetType;
		if (!zp::parse<zp::Obj>(execute_data, offsetType)) RETURN_THROWS();
		PT_RETURN_TRINARY_OR_THROW(StringType::hasOffsetValueType(offsetType));
	});
	cls.op(PT_OP_HAS_OFFSET_VALUE_TYPE, PT_OP_LAMBDA { return pt_op_trinary(StringType::hasOffsetValueType(argv)); });

	cls.method<&StringType::getOffsetValueType, zp::Obj>(sigs::getOffsetValueType);
	cls.op<PT_OP_GET_OFFSET_VALUE_TYPE, &StringType::getOffsetValueType>();

	cls.method(sigs::setOffsetValueType, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *offsetType, *valueType;
		bool unionValues = true;
		if (!zp::parse<zp::ObjOrNull, zp::Obj, zp::Opt<zp::Bool>>(execute_data, offsetType, valueType, unionValues)) RETURN_THROWS();
		(void) unionValues;
		PT_RETURN_VAL(StringType::setOffsetValueType(offsetType, valueType));
	});

	cls.method(sigs::setExistingOffsetValueType, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *offsetType, *valueType;
		if (!zp::parse<zp::Obj, zp::Obj>(execute_data, offsetType, valueType)) RETURN_THROWS();
		PT_RETURN_VAL(PT_THIS.setExistingOffsetValueType());
	});

	cls.method(sigs::unsetOffset, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *offsetType;
		if (!zp::parse<zp::Obj>(execute_data, offsetType)) RETURN_THROWS();
		PT_RETURN_VAL(StringType::unsetOffset());
	});

	cls.method<&StringType::accepts, zp::Obj, zp::Bool>(sigs::accepts);
	cls.op(PT_OP_ACCEPTS, PT_OP_LAMBDA { return StringType(self).accepts(argv, (Z_TYPE(argv[1]) == IS_TRUE)); });

	cls.method<&StringType::toNumber>(sigs::toNumber);

	cls.method<&StringType::toBitwiseNotType>(sigs::toBitwiseNotType);

	cls.method<&StringType::toAbsoluteNumber>(sigs::toAbsoluteNumber);

	cls.method<&StringType::toInteger>(sigs::toInteger);

	cls.method<&StringType::toFloat>(sigs::toFloat);

	cls.method<&StringType::toString>(sigs::toString);

	cls.method<&StringType::toArray>(sigs::toArray);

	cls.method<&StringType::toArrayKey>(sigs::toArrayKey);
	cls.op<PT_OP_TO_ARRAY_KEY, &StringType::toArrayKey>();

	cls.method<&StringType::toCoercedArgumentType, zp::Bool>(sigs::toCoercedArgumentType);

	cls.method(sigs::isNull, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_COPY(pt_trinary_singleton(StringType::isNull()));
	});
	cls.op(PT_OP_IS_NULL, PT_OP_LAMBDA { return pt_op_trinary(StringType::isNull()); });

	cls.method(sigs::isTrue, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_COPY(pt_trinary_singleton(StringType::isTrue()));
	});

	cls.method(sigs::isFalse, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_COPY(pt_trinary_singleton(StringType::isFalse()));
	});

	cls.method(sigs::isBoolean, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_COPY(pt_trinary_singleton(StringType::isBoolean()));
	});
	cls.op(PT_OP_IS_BOOLEAN, PT_OP_LAMBDA { return pt_op_trinary(StringType::isBoolean()); });

	cls.method(sigs::isFloat, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_COPY(pt_trinary_singleton(StringType::isFloat()));
	});
	cls.op(PT_OP_IS_FLOAT, PT_OP_LAMBDA { return pt_op_trinary(StringType::isFloat()); });

	cls.method(sigs::isInteger, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_COPY(pt_trinary_singleton(StringType::isInteger()));
	});
	cls.op(PT_OP_IS_INTEGER, PT_OP_LAMBDA { return pt_op_trinary(StringType::isInteger()); });

	cls.method(sigs::isString, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_COPY(pt_trinary_singleton(StringType::isString()));
	});
	cls.op(PT_OP_IS_STRING, PT_OP_LAMBDA { return pt_op_trinary(StringType::isString()); });

	cls.method(sigs::isNumericString, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_COPY(pt_trinary_singleton(StringType::isNumericString()));
	});

	cls.method(sigs::isDecimalIntegerString, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_COPY(pt_trinary_singleton(StringType::isDecimalIntegerString()));
	});

	cls.method(sigs::isNonEmptyString, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_COPY(pt_trinary_singleton(StringType::isNonEmptyString()));
	});

	cls.method(sigs::isNonFalsyString, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_COPY(pt_trinary_singleton(StringType::isNonFalsyString()));
	});

	cls.method(sigs::isLiteralString, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_COPY(pt_trinary_singleton(StringType::isLiteralString()));
	});

	cls.method(sigs::isLowercaseString, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_COPY(pt_trinary_singleton(StringType::isLowercaseString()));
	});

	cls.method(sigs::isUppercaseString, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_COPY(pt_trinary_singleton(StringType::isUppercaseString()));
	});

	cls.method(sigs::isClassString, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_COPY(pt_trinary_singleton(StringType::isClassString()));
	});

	cls.method<&StringType::getClassStringObjectType>(sigs::getClassStringObjectType);

	cls.method<&StringType::getObjectTypeOrClassStringObjectType>(sigs::getObjectTypeOrClassStringObjectType);

	cls.method(sigs::isScalar, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_COPY(pt_trinary_singleton(StringType::isScalar()));
	});

	cls.method(sigs::looseCompare, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *type, *phpVersion;
		if (!zp::parse<zp::Obj, zp::Zval>(execute_data, type, phpVersion)) RETURN_THROWS();
		(void) phpVersion;
		PT_RETURN_VAL(StringType::looseCompare(type));
	});

	cls.method(sigs::hasMethod, [](INTERNAL_FUNCTION_PARAMETERS) {
		zend_string *methodName;
		if (!zp::parse<zp::Str>(execute_data, methodName)) RETURN_THROWS();
		(void) methodName;
		PT_RETURN_TRINARY_OR_THROW(PT_THIS.hasMethod());
	});
	cls.op<PT_OP_HAS_METHOD, &StringType::hasMethod>();

	cls.method<&StringType::tryRemove, zp::Obj>(sigs::tryRemove);

	cls.method<&StringType::getFiniteTypes>(sigs::getFiniteTypes);

	cls.method<&StringType::exponentiate, zp::Obj>(sigs::exponentiate);

	cls.method<&StringType::toPhpDocNode>(sigs::toPhpDocNode);

	cls.method(sigs::hasTemplateOrLateResolvableType, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_BOOL(StringType::hasTemplateOrLateResolvableType());
	});
	cls.op(PT_OP_HAS_TEMPLATE_OR_LATE_RESOLVABLE_TYPE, PT_OP_LAMBDA { return zv::Val::boolean(StringType::hasTemplateOrLateResolvableType()); });

	/* the traits, in the twin's `use` order; the class body above wins over
	 * every name it declares (getConstantStrings, isNull, isTrue, isFalse,
	 * isBoolean, isFloat, isInteger, isString and the other is*String
	 * queries, getClassStringObjectType, getObjectTypeOrClassStringObjectType,
	 * hasMethod, the offset methods) */
	ptdecl::StringType::registerTraits(cls);

	cls.shadow(&pt_ce_string_type);
}

/* }}} */
