/*
 * PHPStanTurbo\AccessoryNonFalsyStringType — native implementation of
 * PHPStan\Type\Accessory\AccessoryNonFalsyStringType.
 *
 * The `$this->method()` calls the twin makes (equals(), isSubTypeOf(),
 * hasOffsetValueType(), toInteger(), toFloat(), toBoolean()) go through the
 * object's class entry — a subclass may have overridden them — with a
 * direct C++ call when the object is exactly an
 * AccessoryNonFalsyStringType.
 */

#include "TypeTraits.h"
#include "generated/AccessoryNonFalsyStringType.h"

namespace sigs = ptdecl::AccessoryNonFalsyStringType::sig;

zend_class_entry *pt_ce_accessory_non_falsy_string_type = nullptr;

namespace phpstanturbo {

/* Mirrors PHPStan\Type\Accessory\AccessoryNonFalsyStringType. */
class AccessoryNonFalsyStringType
{
public:
	explicit AccessoryNonFalsyStringType(zend_object *self) : self(self) {}

	/* yes for a non-falsy string; the CompoundType callback; else the
	 * non-falsy-string verdict as a fresh result; UNDEF = pending exception */
	zv::Val accepts(zval *type, bool strictTypes) const
	{
		zend_long isNonFalsyString = pt_type_call_trinary(Z_OBJ_P(type), PT_LC("isnonfalsystring"), 0, NULL);
		if (UNEXPECTED(isNonFalsyString < 0)) return zv::Val();
		if (isNonFalsyString == PT_TRI_YES) return pt_type_accepts_result(PT_TRI_YES);
		bool compound;
		if (UNEXPECTED(!pt_type_instanceof(type, PT_CLASS_COMPOUND_TYPE, compound))) return zv::Val();
		if (compound) {
			zv::Args args{self, strictTypes};
			return pt_type_call(Z_OBJ_P(type), PT_LC("isacceptedby"), 2, args);
		}
		return pt_type_new_accepts_result(isNonFalsyString);
	}

	/* the CompoundType callback; yes for an equal type; else the
	 * non-falsy-string verdict; UNDEF = pending exception */
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
		zend_long isNonFalsyString = pt_type_call_trinary(Z_OBJ_P(type), PT_LC("isnonfalsystring"), 0, NULL);
		if (UNEXPECTED(isNonFalsyString < 0)) return zv::Val();
		return pt_type_new_is_super_type_of_result(isNonFalsyString);
	}

	/* the union/intersection callback; yes for an AccessoryNonEmptyStringType;
	 * else the other type's non-falsy-string verdict, and'ed with maybe
	 * unless it is an AccessoryNonFalsyStringType; UNDEF = pending
	 * exception */
	zv::Val isSubTypeOf(zval *otherType) const
	{
		bool unionOrIntersection;
		if (UNEXPECTED(!isUnionOrIntersection(otherType, unionOrIntersection))) return zv::Val();
		if (unionOrIntersection) {
			zval selfZv;
			ZVAL_OBJ(&selfZv, self);
			return pt_type_op(Z_OBJ_P(otherType), PT_OP_IS_SUPER_TYPE_OF, 1, &selfZv);
		}
		if (instanceof_function(Z_OBJCE_P(otherType), pt_ce_accessory_non_empty_string_type)) return pt_type_is_super_type_of_result(PT_TRI_YES);
		zend_long value = pt_type_call_trinary(Z_OBJ_P(otherType), PT_LC("isnonfalsystring"), 0, NULL);
		if (UNEXPECTED(value < 0)) return zv::Val();
		if (!instanceof_function(Z_OBJCE_P(otherType), pt_ce_accessory_non_falsy_string_type)) {
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
	static bool equals(zval *type) { return instanceof_function(Z_OBJCE_P(type), pt_ce_accessory_non_falsy_string_type); }

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
	 * when that is an ErrorType; $this for a non-falsy value, string
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
		zend_long valueIsNonFalsy = pt_type_call_trinary(Z_OBJ_P(valueType), PT_LC("isnonfalsystring"), 0, NULL);
		if (UNEXPECTED(valueIsNonFalsy < 0)) return zv::Val();
		if (valueIsNonFalsy == PT_TRI_YES) return thisValue();
		return pt_type_new_string_type();
	}

	/* new IntersectionType([new StringType(), new AccessoryNonEmptyStringType()]) */
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
		 * new ConstantBooleanType(true) unless overridden) */
		zv::Val boolean = pt_type_call(self, PT_LC("toboolean"), 0, NULL);
		if (UNEXPECTED(boolean.isUndef())) return zv::Val();
		zv::Args args{integer.raw(), floating.raw(), self, boolean.raw()};
		return pt_type_combinator_call(PT_LC("union"), 4, args);
	}

	/* new ConstantBooleanType(false) when null|false|''|array{} covers the
	 * type, new BooleanType() otherwise; UNDEF = pending exception */
	static zv::Val looseCompare(zval *type)
	{
		/* $dominated = TypeCombinator::union(new NullType(), new
		 * ConstantBooleanType(false), new ConstantStringType(''), new
		 * ConstantArrayType([], [])) */
		zval nullRaw;
		if (UNEXPECTED(!pt_null_type_new(&nullRaw))) return zv::Val();
		zv::Val nullType = zv::Val::adopt(nullRaw);
		zval falseRaw;
		if (UNEXPECTED(!pt_constant_boolean_type_new(&falseRaw, false))) return zv::Val();
		zv::Val falseType = zv::Val::adopt(falseRaw);
		zv::Val emptyString = pt_type_new_constant_string("", 0);
		if (UNEXPECTED(emptyString.isUndef())) return zv::Val();
		zval emptyArrayArgs[2], emptyArrayRaw;
		ZVAL_EMPTY_ARRAY(&emptyArrayArgs[0]);
		ZVAL_EMPTY_ARRAY(&emptyArrayArgs[1]);
		zv::Val emptyArray = pt_constant_array_type_new(&emptyArrayRaw, &emptyArrayArgs[0], &emptyArrayArgs[1]) ? zv::Val::adopt(emptyArrayRaw) : zv::Val();
		if (UNEXPECTED(emptyArray.isUndef())) return zv::Val();
		zv::Args args{nullType.raw(), falseType.raw(), emptyString.raw(), emptyArray.raw()};
		zv::Val dominated = pt_type_combinator_call(PT_LC("union"), 4, args);
		if (UNEXPECTED(dominated.isUndef())) return zv::Val();
		if (UNEXPECTED(!zv::Ref(dominated.raw()).isObject())) {
			zend_type_error("phpstan_turbo: TypeCombinator::union() must return %s", ptcls::type);
			return zv::Val();
		}
		zv::Val covers = pt_type_op(Z_OBJ_P(dominated.raw()), PT_OP_IS_SUPER_TYPE_OF, 1, type);
		if (UNEXPECTED(covers.isUndef())) return zv::Val();
		zend_long coversValue = pt_type_result_trinary(covers.raw());
		if (UNEXPECTED(coversValue < 0)) return zv::Val();
		zval result;
		if (coversValue == PT_TRI_YES) {
			if (UNEXPECTED(!pt_constant_boolean_type_new(&result, false))) return zv::Val();
			return zv::Val::adopt(result);
		}
		if (UNEXPECTED(!pt_boolean_type_new(&result))) return zv::Val();
		return zv::Val::adopt(result);
	}

	/* new StringType() */
	static zv::Val generalize() { return pt_type_new_string_type(); }

	/* new BenevolentUnionType([new FloatType(), new IntegerType()]) */
	static zv::Val exponentiate() { return pt_type_new_float_or_int_benevolent_union(); }

	/* new IdentifierTypeNode('non-falsy-string') */
	static zv::Val toPhpDocNode() { return pt_type_new_identifier_type_node(PT_LC("non-falsy-string")); }

private:
	zend_object *self;

	bool isExact() const { return self->ce == pt_ce_accessory_non_falsy_string_type; }

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

using phpstanturbo::AccessoryNonFalsyStringType;

bool pt_accessory_non_falsy_string_type_new(zval *out)
{
	return object_init_ex(out, pt_ce_accessory_non_falsy_string_type) == SUCCESS;
}

/* {{{ engine ABI glue: parameter parsing + registration */

#define PT_THIS AccessoryNonFalsyStringType(Z_OBJ_P(ZEND_THIS))

/* the trivial bodies the twin repeats (one handler per body and arity;
 * each method is still declared exactly once, at its registration line) */

static void ZEND_FASTCALL anfsEmptyArray0(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	RETURN_EMPTY_ARRAY();
}

static void ZEND_FASTCALL anfsNo0(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	PT_RETURN_TRINARY(PT_TRI_NO);
}

static void ZEND_FASTCALL anfsMaybe0(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	PT_RETURN_TRINARY(PT_TRI_MAYBE);
}

static void ZEND_FASTCALL anfsYes0(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	PT_RETURN_TRINARY(PT_TRI_YES);
}

static void ZEND_FASTCALL anfsThis0(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	RETURN_OBJ_COPY(Z_OBJ_P(ZEND_THIS));
}

static void ZEND_FASTCALL anfsThis2(INTERNAL_FUNCTION_PARAMETERS)
{
	PT_ARGS(2, 2);
	RETURN_OBJ_COPY(Z_OBJ_P(ZEND_THIS));
}

static void ZEND_FASTCALL anfsError0(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	PT_RETURN_VAL(pt_type_new_error_type());
}

static void ZEND_FASTCALL anfsError1(INTERNAL_FUNCTION_PARAMETERS)
{
	PT_ARGS(1, 1);
	PT_RETURN_VAL(pt_type_new_error_type());
}

static void ZEND_FASTCALL anfsString1(INTERNAL_FUNCTION_PARAMETERS)
{
	PT_ARGS(1, 1);
	PT_RETURN_VAL(pt_type_new_string_type());
}

static void ZEND_FASTCALL anfsObjectWithoutClass0(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	PT_RETURN_VAL(pt_type_new_object_without_class_type());
}

/* (Type $type) → a Type */
static void pt_anfs_one_type(INTERNAL_FUNCTION_PARAMETERS, zv::Val (AccessoryNonFalsyStringType::*method)(zval *) const)
{
	zval *type;
	if (!zp::parse<zp::Obj>(execute_data, type)) RETURN_THROWS();
	PT_RETURN_VAL((PT_THIS.*method)(type));
}

PT_MINIT_REGISTRATION(pt_register_accessory_non_falsy_string_type)
{
	reg::Class cls("PHPStan\\Type\\Accessory\\AccessoryNonFalsyStringType");
	ptdecl::AccessoryNonFalsyStringType::declareClass(cls);
	ptdecl::AccessoryNonFalsyStringType::declareProperties(cls);

	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
	});

	cls.method(sigs::getReferencedClasses, anfsEmptyArray0);
	cls.op(PT_OP_GET_REFERENCED_CLASSES, PT_OP_LAMBDA { return pt_op_empty_array(); });
	cls.method(sigs::getObjectClassNames, anfsEmptyArray0);
	cls.op(PT_OP_GET_OBJECT_CLASS_NAMES, PT_OP_LAMBDA { return pt_op_empty_array(); });
	cls.method(sigs::getObjectClassReflections, anfsEmptyArray0);
	cls.op(PT_OP_GET_OBJECT_CLASS_REFLECTIONS, PT_OP_LAMBDA { return pt_op_empty_array(); });
	cls.method(sigs::getConstantStrings, anfsEmptyArray0);

	cls.method<&AccessoryNonFalsyStringType::accepts, zp::Obj, zp::Bool>(sigs::accepts);
	cls.op(PT_OP_ACCEPTS, PT_OP_LAMBDA { return AccessoryNonFalsyStringType(self).accepts(argv, (Z_TYPE(argv[1]) == IS_TRUE)); });

	cls.method(sigs::isSuperTypeOf, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_anfs_one_type(INTERNAL_FUNCTION_PARAM_PASSTHRU, &AccessoryNonFalsyStringType::isSuperTypeOf);
	});
	cls.op<PT_OP_IS_SUPER_TYPE_OF, &AccessoryNonFalsyStringType::isSuperTypeOf>();

	cls.method(sigs::isSubTypeOf, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_anfs_one_type(INTERNAL_FUNCTION_PARAM_PASSTHRU, &AccessoryNonFalsyStringType::isSubTypeOf);
	});
	cls.op<PT_OP_IS_SUB_TYPE_OF, &AccessoryNonFalsyStringType::isSubTypeOf>();

	cls.method(sigs::isAcceptedBy, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *acceptingType;
		bool strictTypes;
		if (!zp::parse<zp::Obj, zp::Bool>(execute_data, acceptingType, strictTypes)) RETURN_THROWS();
		PT_RETURN_VAL(PT_THIS.isAcceptedBy(acceptingType));
	});

	cls.method(sigs::equals, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *type;
		if (!zp::parse<zp::TypeObj>(execute_data, type)) RETURN_THROWS();
		RETURN_BOOL(AccessoryNonFalsyStringType::equals(type));
	});
	cls.op(PT_OP_EQUALS, PT_OP_LAMBDA { return zv::Val::boolean(AccessoryNonFalsyStringType::equals(argv)); });

	cls.method(sigs::describe, [](INTERNAL_FUNCTION_PARAMETERS) {
		PT_ARGS(1, 1);
		RETURN_STRINGL("non-falsy-string", sizeof("non-falsy-string") - 1);
	});
	cls.op(PT_OP_DESCRIBE, PT_OP_LAMBDA { return zv::Val::string("non-falsy-string", sizeof("non-falsy-string") - 1); });

	cls.method(sigs::isOffsetAccessible, anfsYes0);
	cls.method(sigs::isOffsetAccessLegal, anfsYes0);

	cls.method(sigs::hasOffsetValueType, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *offsetType;
		if (!zp::parse<zp::Obj>(execute_data, offsetType)) RETURN_THROWS();
		zend_long value = AccessoryNonFalsyStringType::hasOffsetValueType(offsetType);
		if (UNEXPECTED(value < 0)) RETURN_THROWS();
		PT_RETURN_TRINARY(value);
	});
	cls.op(PT_OP_HAS_OFFSET_VALUE_TYPE, PT_OP_LAMBDA { return pt_op_trinary(AccessoryNonFalsyStringType::hasOffsetValueType(argv)); });

	cls.method(sigs::getOffsetValueType, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_anfs_one_type(INTERNAL_FUNCTION_PARAM_PASSTHRU, &AccessoryNonFalsyStringType::getOffsetValueType);
	});
	cls.op<PT_OP_GET_OFFSET_VALUE_TYPE, &AccessoryNonFalsyStringType::getOffsetValueType>();

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

	cls.method(sigs::setExistingOffsetValueType, anfsThis2);
	cls.method(sigs::unsetOffset, anfsError1);
	cls.method(sigs::toNumber, anfsError0);

	cls.method<&AccessoryNonFalsyStringType::toBitwiseNotType>(sigs::toBitwiseNotType);

	cls.method(sigs::toAbsoluteNumber, anfsError0);

	cls.method<&AccessoryNonFalsyStringType::toInteger>(sigs::toInteger);

	cls.method<&AccessoryNonFalsyStringType::toFloat>(sigs::toFloat);

	cls.method(sigs::toString, anfsThis0);

	cls.method<&AccessoryNonFalsyStringType::toArray>(sigs::toArray);

	cls.method(sigs::toArrayKey, anfsThis0);
	cls.op(PT_OP_TO_ARRAY_KEY, PT_OP_LAMBDA { return pt_op_this(self); });

	cls.method<&AccessoryNonFalsyStringType::toCoercedArgumentType, zp::Bool>(sigs::toCoercedArgumentType);

	cls.method(sigs::isNull, anfsNo0);
	cls.op(PT_OP_IS_NULL, PT_OP_LAMBDA { return pt_op_trinary(PT_TRI_NO); });
	cls.method(sigs::isConstantValue, anfsMaybe0);
	cls.method(sigs::isConstantScalarValue, anfsMaybe0);
	cls.op(PT_OP_IS_CONSTANT_SCALAR_VALUE, PT_OP_LAMBDA { return pt_op_trinary(PT_TRI_MAYBE); });
	cls.method(sigs::getConstantScalarTypes, anfsEmptyArray0);
	cls.method(sigs::getConstantScalarValues, anfsEmptyArray0);
	cls.op(PT_OP_GET_CONSTANT_SCALAR_VALUES, PT_OP_LAMBDA { return pt_op_empty_array(); });
	cls.method(sigs::isTrue, anfsNo0);
	cls.method(sigs::isFalse, anfsNo0);
	cls.method(sigs::isBoolean, anfsNo0);
	cls.op(PT_OP_IS_BOOLEAN, PT_OP_LAMBDA { return pt_op_trinary(PT_TRI_NO); });
	cls.method(sigs::isFloat, anfsNo0);
	cls.op(PT_OP_IS_FLOAT, PT_OP_LAMBDA { return pt_op_trinary(PT_TRI_NO); });
	cls.method(sigs::isInteger, anfsNo0);
	cls.op(PT_OP_IS_INTEGER, PT_OP_LAMBDA { return pt_op_trinary(PT_TRI_NO); });
	cls.method(sigs::isString, anfsYes0);
	cls.op(PT_OP_IS_STRING, PT_OP_LAMBDA { return pt_op_trinary(PT_TRI_YES); });
	cls.method(sigs::isNumericString, anfsMaybe0);
	cls.method(sigs::isDecimalIntegerString, anfsMaybe0);
	cls.method(sigs::isNonEmptyString, anfsYes0);
	cls.method(sigs::isNonFalsyString, anfsYes0);
	cls.method(sigs::isLiteralString, anfsMaybe0);
	cls.method(sigs::isLowercaseString, anfsMaybe0);
	cls.method(sigs::isClassString, anfsMaybe0);
	cls.method(sigs::isUppercaseString, anfsMaybe0);
	cls.method(sigs::getClassStringObjectType, anfsObjectWithoutClass0);
	cls.method(sigs::getObjectTypeOrClassStringObjectType, anfsObjectWithoutClass0);
	cls.method(sigs::isVoid, anfsNo0);
	cls.op(PT_OP_IS_VOID, PT_OP_LAMBDA { return pt_op_trinary(PT_TRI_NO); });
	cls.method(sigs::isScalar, anfsYes0);

	cls.method(sigs::looseCompare, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *type, *phpVersion;
		if (!zp::parse<zp::Obj, zp::Zval>(execute_data, type, phpVersion)) RETURN_THROWS();
		PT_RETURN_VAL(AccessoryNonFalsyStringType::looseCompare(type));
	});

	cls.method("traverse", reg::Public, 1, { reg::callableArg("cb") }, pt_type_identity_traverse_handler(), &ptret::type);
	cls.op(PT_OP_TRAVERSE, PT_OP_LAMBDA { return pt_op_traverse_identity(self); });
	cls.method(sigs::traverseSimultaneously, anfsThis2);
	cls.method(sigs::generalize, anfsString1);

	cls.method(sigs::exponentiate, [](INTERNAL_FUNCTION_PARAMETERS) {
		PT_ARGS(1, 1);
		PT_RETURN_VAL(AccessoryNonFalsyStringType::exponentiate());
	});

	cls.method(sigs::getFiniteTypes, anfsEmptyArray0);

	cls.method(sigs::getDefaultBaseType, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		PT_RETURN_VAL(pt_type_new_string_type());
	});

	cls.method<&AccessoryNonFalsyStringType::toPhpDocNode>(sigs::toPhpDocNode);

	cls.method(sigs::hasTemplateOrLateResolvableType, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_FALSE;
	});
	cls.op(PT_OP_HAS_TEMPLATE_OR_LATE_RESOLVABLE_TYPE, PT_OP_LAMBDA { return zv::Val::boolean(false); });

	/* the traits, in the twin's `use` order (UndecidedComparisonCompoundTypeTrait
	 * brings UndecidedComparisonTypeTrait with it); the class body above wins
	 * over every name it declares */
	ptdecl::AccessoryNonFalsyStringType::registerTraits(cls);

	cls.shadow(&pt_ce_accessory_non_falsy_string_type);
}

/* }}} */
