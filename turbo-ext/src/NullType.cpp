/*
 * PHPStanTurbo\NullType — native implementation of PHPStan\Type\NullType.
 *
 * Declared as PHPStan\Type\NullType itself at activation: not final (the
 * PHP TemplateNullType extends it), implementing
 * PHPStan\Type\ConstantScalarType. The class body's methods live here; the
 * seven traits the twin is composed of come from the shared registrars in
 * TypeTraits.cpp, run after the class's own methods so the class body
 * wins over the traits exactly as in PHP (toClassConstantType() over
 * NonObjectTypeTrait's).
 */

#include "TypeTraits.h"
#include "generated/NullType.h"

namespace sigs = ptdecl::NullType::sig;

zend_class_entry *pt_ce_null_type = nullptr;

namespace phpstanturbo {

/* Mirrors PHPStan\Type\NullType. The twin has no state. */
class NullType
{
public:
	explicit NullType(zend_object *self) : self(self) {}

	/* new NullType(): the constructor is empty, so instantiating the class
	 * is all `new` does; UNDEF = pending exception */
	static zv::Val create() { return pt_new_instance(pt_ce_null_type); }

	static zv::Val getReferencedClasses() { return zv::Val(zv::Arr::empty()); }
	static zv::Val getObjectClassNames() { return zv::Val(zv::Arr::empty()); }
	static zv::Val getObjectClassReflections() { return zv::Val(zv::Arr::empty()); }
	static zv::Val getConstantStrings() { return zv::Val(zv::Arr::empty()); }

	static zv::Val getValue() { return zv::Val::null(); }

	/* $this */
	zv::Val generalize() const { return thisValue(); }

	/* yes for a NullType, the CompoundType callback, no otherwise; UNDEF =
	 * pending exception */
	zv::Val accepts(zval *type, bool strictTypes) const
	{
		/* $type instanceof self */
		if (instanceof_function(Z_OBJCE_P(type), pt_ce_null_type)) return pt_type_accepts_result(PT_TRI_YES);

		bool compound;
		if (UNEXPECTED(!pt_type_instanceof(type, PT_CLASS_COMPOUND_TYPE, compound))) return zv::Val();
		if (compound) {
			zv::Args args{self, strictTypes};
			return pt_type_call(Z_OBJ_P(type), PT_LC("isacceptedby"), 2, args);
		}

		return pt_type_accepts_result(PT_TRI_NO);
	}

	/* yes for a NullType, the CompoundType callback, no otherwise; UNDEF =
	 * pending exception */
	zv::Val isSuperTypeOf(zval *type) const
	{
		/* $type instanceof self */
		if (instanceof_function(Z_OBJCE_P(type), pt_ce_null_type)) return pt_type_is_super_type_of_result(PT_TRI_YES);

		bool compound;
		if (UNEXPECTED(!pt_type_instanceof(type, PT_CLASS_COMPOUND_TYPE, compound))) return zv::Val();
		if (compound) {
			zval selfZv;
			ZVAL_OBJ(&selfZv, self);
			return pt_type_call(Z_OBJ_P(type), PT_LC("issubtypeof"), 1, &selfZv);
		}

		return pt_type_is_super_type_of_result(PT_TRI_NO);
	}

	/* $type instanceof self */
	static bool equals(zval *type) { return instanceof_function(Z_OBJCE_P(type), pt_ce_null_type); }

	/* isSmallerThan() / isSmallerThanOrEqual(): `null < $value` / `null <=
	 * $value` against a ConstantScalarType's value, the CompoundType
	 * callback, yes for an object, maybe otherwise; UNDEF = pending
	 * exception */
	zv::Val isSmallerThan(zval *otherType, zval *phpVersion, bool orEqual) const
	{
		bool isConstantScalar;
		if (UNEXPECTED(!pt_type_instanceof(otherType, PT_CLASS_CONSTANT_SCALAR_TYPE, isConstantScalar))) return zv::Val();
		if (isConstantScalar) {
			zv::Val otherValue = pt_type_call(Z_OBJ_P(otherType), PT_LC("getvalue"), 0, NULL);
			if (UNEXPECTED(otherValue.isUndef())) return zv::Val();
			zval null;
			ZVAL_NULL(&null);
			int comparison = zend_compare(&null, otherValue.raw());
			if (UNEXPECTED(EG(exception))) return zv::Val();
			return pt_type_trinary((orEqual ? comparison <= 0 : comparison < 0) ? PT_TRI_YES : PT_TRI_NO);
		}

		bool compound;
		if (UNEXPECTED(!pt_type_instanceof(otherType, PT_CLASS_COMPOUND_TYPE, compound))) return zv::Val();
		if (compound) {
			zv::Args args{self, phpVersion};
			if (orEqual) return pt_type_call(Z_OBJ_P(otherType), PT_LC("isgreaterthanorequal"), 2, args);
			return pt_type_call(Z_OBJ_P(otherType), PT_LC("isgreaterthan"), 2, args);
		}

		zend_long isObject = pt_type_call_trinary(Z_OBJ_P(otherType), PT_LC("isobject"), 0, NULL);
		if (UNEXPECTED(isObject < 0)) return zv::Val();
		if (isObject == PT_TRI_YES) return pt_type_trinary(PT_TRI_YES);

		return pt_type_trinary(PT_TRI_MAYBE);
	}

	static const char *describe() { return "null"; }

	/* new ConstantIntegerType(0) */
	static zv::Val toNumber() { return pt_type_new_constant_integer(0); }

	static zv::Val toBitwiseNotType() { return pt_type_new_error_type(); }

	/* $this — null `::class` reads as `null` */
	zv::Val toClassConstantType() const { return thisValue(); }

	/* $this->toNumber()->toAbsoluteNumber(); UNDEF = pending exception */
	zv::Val toAbsoluteNumber() const
	{
		zv::Val number = thisToNumber();
		if (UNEXPECTED(number.isUndef())) return zv::Val();
		return pt_type_call(zv::Ref(number.raw()).asObject(), PT_LC("toabsolutenumber"), 0, NULL);
	}

	/* new ConstantStringType('') */
	static zv::Val toString() { return pt_type_new_constant_string("", 0); }

	/* $this->toNumber(); UNDEF = pending exception */
	zv::Val toInteger() const { return thisToNumber(); }

	/* $this->toNumber()->toFloat(); UNDEF = pending exception */
	zv::Val toFloat() const
	{
		zv::Val number = thisToNumber();
		if (UNEXPECTED(number.isUndef())) return zv::Val();
		return pt_type_call(zv::Ref(number.raw()).asObject(), PT_LC("tofloat"), 0, NULL);
	}

	/* new ConstantArrayType([], []) */
	static zv::Val toArray() { return emptyConstantArray(); }

	/* new ConstantStringType('') */
	static zv::Val toArrayKey() { return pt_type_new_constant_string("", 0); }

	/* $this */
	zv::Val toCoercedArgumentType() const { return thisValue(); }

	static zend_long isOffsetAccessible() { return PT_TRI_YES; }
	static zend_long isOffsetAccessLegal() { return PT_TRI_YES; }
	static zend_long hasOffsetValueType() { return PT_TRI_NO; }
	static zv::Val getOffsetValueType() { return pt_type_new_error_type(); }

	/* (new ConstantArrayType([], []))->setOffsetValueType($offsetType,
	 * $valueType, $unionValues); offsetType NULL = the twin's null; UNDEF =
	 * pending exception */
	static zv::Val setOffsetValueType(zval *offsetType, zval *valueType, bool unionValues)
	{
		zv::Val array = emptyConstantArray();
		if (UNEXPECTED(array.isUndef())) return zv::Val();
		zval args[3];
		if (offsetType != NULL) {
			ZVAL_COPY_VALUE(&args[0], offsetType);
		} else {
			ZVAL_NULL(&args[0]);
		}
		ZVAL_COPY_VALUE(&args[1], valueType);
		ZVAL_BOOL(&args[2], unionValues);
		return pt_type_call(zv::Ref(array.raw()).asObject(), PT_LC("setoffsetvaluetype"), 3, args);
	}

	/* $this */
	zv::Val setExistingOffsetValueType() const { return thisValue(); }

	/* $this */
	zv::Val unsetOffset() const { return thisValue(); }

	/* $this */
	zv::Val traverse() const { return thisValue(); }

	/* $this */
	zv::Val traverseSimultaneously() const { return thisValue(); }

	static zend_long isNull() { return PT_TRI_YES; }
	static zend_long isConstantValue() { return PT_TRI_YES; }
	static zend_long isConstantScalarValue() { return PT_TRI_YES; }

	/* [$this] */
	zv::Val getConstantScalarTypes() const
	{
		zv::Arr types = zv::Arr::create(1);
		zval selfZv;
		ZVAL_OBJ(&selfZv, self);
		types.push(zv::Ref(&selfZv));
		return zv::Val(std::move(types));
	}

	/* [$this->getValue()]; UNDEF = pending exception */
	zv::Val getConstantScalarValues() const
	{
		zv::Val value = thisGetValue();
		if (UNEXPECTED(value.isUndef())) return zv::Val();
		zv::Arr values = zv::Arr::create(1);
		values.push(std::move(value));
		return zv::Val(std::move(values));
	}

	static zend_long isTrue() { return PT_TRI_NO; }
	static zend_long isFalse() { return PT_TRI_NO; }
	static zend_long isBoolean() { return PT_TRI_NO; }
	static zend_long isFloat() { return PT_TRI_NO; }
	static zend_long isInteger() { return PT_TRI_NO; }
	static zend_long isString() { return PT_TRI_NO; }
	static zend_long isNumericString() { return PT_TRI_NO; }
	static zend_long isDecimalIntegerString() { return PT_TRI_NO; }
	static zend_long isNonEmptyString() { return PT_TRI_NO; }
	static zend_long isNonFalsyString() { return PT_TRI_NO; }
	static zend_long isLiteralString() { return PT_TRI_NO; }
	static zend_long isLowercaseString() { return PT_TRI_NO; }
	static zend_long isUppercaseString() { return PT_TRI_NO; }
	static zend_long isClassString() { return PT_TRI_NO; }
	static zv::Val getClassStringObjectType() { return pt_type_new_error_type(); }
	static zv::Val getObjectTypeOrClassStringObjectType() { return pt_type_new_error_type(); }
	static zend_long isVoid() { return PT_TRI_NO; }
	static zend_long isScalar() { return PT_TRI_NO; }

	/* LooseComparisonHelper::compareConstantScalars($this, $type, $phpVersion)
	 * for a ConstantScalarType, new ConstantBooleanType($this->getValue() == [])
	 * for an empty constant array, the CompoundType callback, new
	 * BooleanType() otherwise; UNDEF = pending exception */
	zv::Val looseCompare(zval *type, zval *phpVersion) const
	{
		bool isConstantScalar;
		if (UNEXPECTED(!pt_type_instanceof(type, PT_CLASS_CONSTANT_SCALAR_TYPE, isConstantScalar))) return zv::Val();
		if (isConstantScalar) {
			zv::Args args{self, type, phpVersion};
			return pt_type_call_static(PT_CLASS_LOOSE_COMPARISON_HELPER, PT_LC("compareconstantscalars"), 3, args);
		}

		zend_long isConstantArray = pt_type_call_trinary(Z_OBJ_P(type), PT_LC("isconstantarray"), 0, NULL);
		if (UNEXPECTED(isConstantArray < 0)) return zv::Val();
		if (isConstantArray == PT_TRI_YES) {
			zend_long atLeastOnce = pt_type_call_trinary(Z_OBJ_P(type), PT_LC("isiterableatleastonce"), 0, NULL);
			if (UNEXPECTED(atLeastOnce < 0)) return zv::Val();
			if (atLeastOnce == PT_TRI_NO) {
				zv::Val value = thisGetValue();
				if (UNEXPECTED(value.isUndef())) return zv::Val();
				zval emptyArray;
				ZVAL_EMPTY_ARRAY(&emptyArray);
				int comparison = zend_compare(value.raw(), &emptyArray);
				if (UNEXPECTED(EG(exception))) return zv::Val();
				zval result;
				if (UNEXPECTED(!pt_constant_boolean_type_new(&result, comparison == 0))) return zv::Val();
				return zv::Val::adopt(result);
			}
		}

		bool compound;
		if (UNEXPECTED(!pt_type_instanceof(type, PT_CLASS_COMPOUND_TYPE, compound))) return zv::Val();
		if (compound) {
			zv::Args args{self, phpVersion};
			return pt_type_call(Z_OBJ_P(type), PT_LC("loosecompare"), 2, args);
		}

		return pt_val_of<pt_boolean_type_new>();
	}

	/* new NeverType() */
	static zv::Val getSmallerType() { return pt_type_new(PT_CLASS_NEVER_TYPE, 0, NULL); }

	/* all falsey types except '0': new UnionType([new NullType(), new
	 * ConstantBooleanType(false), new ConstantIntegerType(0), new
	 * ConstantFloatType(0.0), new ConstantStringType(''), new
	 * ConstantArrayType([], [])]) */
	static zv::Val getSmallerOrEqualType()
	{
		zv::Arr types = falseyTypes();
		if (UNEXPECTED(types.isUndef())) return zv::Val();
		return pt_type_new_union(std::move(types));
	}

	/* all truthy types, but also '0': new MixedType(subtractedType: new
	 * UnionType([...the falsey types])) — the named argument skips
	 * $isExplicitMixed, whose default is false */
	static zv::Val getGreaterType()
	{
		zv::Arr types = falseyTypes();
		if (UNEXPECTED(types.isUndef())) return zv::Val();
		zv::Val unionType = pt_type_new_union(std::move(types));
		if (UNEXPECTED(unionType.isUndef())) return zv::Val();
		zval args[2];
		ZVAL_FALSE(&args[0]);
		ZVAL_COPY_VALUE(&args[1], unionType.raw());
		return pt_type_new(PT_CLASS_MIXED_TYPE, 2, args);
	}

	/* new MixedType() */
	static zv::Val getGreaterOrEqualType() { return pt_type_new_mixed_type(); }

	/* [$this] */
	zv::Val getFiniteTypes() const { return getConstantScalarTypes(); }

	/* new UnionType([new ConstantIntegerType(0), new ConstantIntegerType(1)]) */
	static zv::Val exponentiate()
	{
		zv::Val zero = pt_type_new_constant_integer(0);
		if (UNEXPECTED(zero.isUndef())) return zv::Val();
		zv::Val one = pt_type_new_constant_integer(1);
		if (UNEXPECTED(one.isUndef())) return zv::Val();
		zv::Arr types = zv::Arr::create(2);
		types.push(std::move(zero));
		types.push(std::move(one));
		return pt_type_new_union(std::move(types));
	}

	/* new IdentifierTypeNode('null') */
	static zv::Val toPhpDocNode()
	{
		zv::Val name = zv::Val::string("null", 4);
		return pt_type_new(PT_CLASS_IDENTIFIER_TYPE_NODE, 1, name.raw());
	}

	static bool hasTemplateOrLateResolvableType() { return false; }

private:
	zend_object *self;

	/* exactly a NullType, none of its methods overridden: $this-calls can
	 * go straight to the C++ methods */
	bool isExact() const { return self->ce == pt_ce_null_type; }

	zv::Val thisValue() const { return pt_this_value(self); }

	/* $this->toNumber() — through the object's class; a non-object result
	 * is a TypeError, as the twin's ->toAbsoluteNumber() call would raise;
	 * UNDEF = pending exception */
	zv::Val thisToNumber() const
	{
		zv::Val number = isExact() ? toNumber() : pt_type_call(self, PT_LC("tonumber"), 0, NULL);
		if (UNEXPECTED(number.isUndef())) return zv::Val();
		if (UNEXPECTED(!zv::Ref(number.raw()).isObject())) {
			zend_type_error("phpstan_turbo: %s::toNumber() must return an object", ZSTR_VAL(self->ce->name));
			return zv::Val();
		}
		return number;
	}

	/* $this->getValue() — through the object's class; UNDEF = pending
	 * exception */
	zv::Val thisGetValue() const
	{
		if (EXPECTED(isExact())) return getValue();
		return pt_type_call(self, PT_LC("getvalue"), 0, NULL);
	}

	/* new ConstantArrayType([], []) */
	static zv::Val emptyConstantArray()
	{
		zval args[2];
		ZVAL_EMPTY_ARRAY(&args[0]);
		ZVAL_EMPTY_ARRAY(&args[1]);
		return pt_type_new(PT_CLASS_CONSTANT_ARRAY_TYPE, 2, args);
	}

	/* [new NullType(), new ConstantBooleanType(false), new
	 * ConstantIntegerType(0), new ConstantFloatType(0.0), new
	 * ConstantStringType(''), new ConstantArrayType([], [])]; UNDEF =
	 * pending exception */
	static zv::Arr falseyTypes()
	{
		zv::Arr types = zv::Arr::create(6);
		zv::Val nullType = create();
		if (UNEXPECTED(nullType.isUndef())) return zv::Arr();
		types.push(std::move(nullType));
		zval falseType;
		if (UNEXPECTED(!pt_constant_boolean_type_new(&falseType, false))) return zv::Arr();
		types.push(zv::Val::adopt(falseType));
		zv::Val zero = pt_type_new_constant_integer(0);
		if (UNEXPECTED(zero.isUndef())) return zv::Arr();
		types.push(std::move(zero));
		zv::Val floatZero = pt_type_new_constant_float(0.0);
		if (UNEXPECTED(floatZero.isUndef())) return zv::Arr();
		types.push(std::move(floatZero));
		zv::Val emptyString = pt_type_new_constant_string("", 0);
		if (UNEXPECTED(emptyString.isUndef())) return zv::Arr();
		types.push(std::move(emptyString));
		zv::Val emptyArray = emptyConstantArray();
		if (UNEXPECTED(emptyArray.isUndef())) return zv::Arr();
		types.push(std::move(emptyArray));
		return types;
	}
};

} // namespace phpstanturbo

using phpstanturbo::NullType;

bool pt_null_type_new(zval *out)
{
	return pt_val_into(NullType::create(), out);
}

/* {{{ engine ABI glue: parameter parsing + registration */

#define PT_THIS NullType(Z_OBJ_P(ZEND_THIS))

/* the getSmallerType() family: one PhpVersion argument, never read */
static void pt_null_comparison_type(INTERNAL_FUNCTION_PARAMETERS, zv::Val (*method)())
{
	zval *phpVersion;
	if (!zp::parse<zp::Zval>(execute_data, phpVersion)) RETURN_THROWS();
	PT_RETURN_VAL(method());
}

void pt_register_null_type()
{
	reg::Class cls("PHPStan\\Type\\NullType");
	ptdecl::NullType::declareClass(cls);
	ptdecl::NullType::declareProperties(cls);

	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
	});

	cls.method<&NullType::getReferencedClasses>(sigs::getReferencedClasses);

	cls.method<&NullType::getObjectClassNames>(sigs::getObjectClassNames);

	cls.method<&NullType::getObjectClassReflections>(sigs::getObjectClassReflections);

	cls.method<&NullType::getConstantStrings>(sigs::getConstantStrings);

	/* the twin declares no return type (`@return null`) */
	cls.method<&NullType::getValue>(sigs::getValue);

	cls.method(sigs::generalize, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *precision;
		if (!zp::parse<zp::Obj>(execute_data, precision)) RETURN_THROWS();
		PT_RETURN_VAL(PT_THIS.generalize());
	});

	cls.method<&NullType::accepts, zp::Obj, zp::Bool>(sigs::accepts);

	cls.method<&NullType::isSuperTypeOf, zp::Obj>(sigs::isSuperTypeOf);

	cls.method(sigs::equals, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *type;
		if (!zp::parse<zp::Obj>(execute_data, type)) RETURN_THROWS();
		RETURN_BOOL(NullType::equals(type));
	});

	cls.method(sigs::isSmallerThan, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *otherType, *phpVersion;
		if (!zp::parse<zp::Obj, zp::Zval>(execute_data, otherType, phpVersion)) RETURN_THROWS();
		PT_RETURN_VAL(PT_THIS.isSmallerThan(otherType, phpVersion, false));
	});

	cls.method(sigs::isSmallerThanOrEqual, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *otherType, *phpVersion;
		if (!zp::parse<zp::Obj, zp::Zval>(execute_data, otherType, phpVersion)) RETURN_THROWS();
		PT_RETURN_VAL(PT_THIS.isSmallerThan(otherType, phpVersion, true));
	});

	cls.method(sigs::describe, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *level;
		if (!zp::parse<zp::Obj>(execute_data, level)) RETURN_THROWS();
		RETURN_STRING(NullType::describe());
	});

	cls.method<&NullType::toNumber>(sigs::toNumber);

	cls.method<&NullType::toBitwiseNotType>(sigs::toBitwiseNotType);

	cls.method(sigs::toClassConstantType, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *reflectionProvider;
		if (!zp::parse<zp::Obj>(execute_data, reflectionProvider)) RETURN_THROWS();
		PT_RETURN_VAL(PT_THIS.toClassConstantType());
	});

	cls.method<&NullType::toAbsoluteNumber>(sigs::toAbsoluteNumber);

	cls.method<&NullType::toString>(sigs::toString);

	cls.method<&NullType::toInteger>(sigs::toInteger);

	cls.method<&NullType::toFloat>(sigs::toFloat);

	cls.method<&NullType::toArray>(sigs::toArray);

	cls.method<&NullType::toArrayKey>(sigs::toArrayKey);

	cls.method(sigs::toCoercedArgumentType, [](INTERNAL_FUNCTION_PARAMETERS) {
		bool strictTypes;
		if (!zp::parse<zp::Bool>(execute_data, strictTypes)) RETURN_THROWS();
		PT_RETURN_VAL(PT_THIS.toCoercedArgumentType());
	});

	cls.method(sigs::isOffsetAccessible, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_COPY(pt_trinary_singleton(NullType::isOffsetAccessible()));
	});

	cls.method(sigs::isOffsetAccessLegal, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_COPY(pt_trinary_singleton(NullType::isOffsetAccessLegal()));
	});

	cls.method(sigs::hasOffsetValueType, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *offsetType;
		if (!zp::parse<zp::Obj>(execute_data, offsetType)) RETURN_THROWS();
		RETURN_COPY(pt_trinary_singleton(NullType::hasOffsetValueType()));
	});

	cls.method(sigs::getOffsetValueType, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *offsetType;
		if (!zp::parse<zp::Obj>(execute_data, offsetType)) RETURN_THROWS();
		PT_RETURN_VAL(NullType::getOffsetValueType());
	});

	cls.method(sigs::setOffsetValueType, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *offsetType, *valueType;
		bool unionValues = true;
		if (!zp::parse<zp::ObjOrNull, zp::Obj, zp::Opt<zp::Bool>>(execute_data, offsetType, valueType, unionValues)) RETURN_THROWS();
		PT_RETURN_VAL(NullType::setOffsetValueType(offsetType, valueType, unionValues));
	});

	cls.method(sigs::setExistingOffsetValueType, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *offsetType, *valueType;
		if (!zp::parse<zp::Obj, zp::Obj>(execute_data, offsetType, valueType)) RETURN_THROWS();
		PT_RETURN_VAL(PT_THIS.setExistingOffsetValueType());
	});

	cls.method(sigs::unsetOffset, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *offsetType;
		if (!zp::parse<zp::Obj>(execute_data, offsetType)) RETURN_THROWS();
		PT_RETURN_VAL(PT_THIS.unsetOffset());
	});

	cls.method(sigs::traverse, [](INTERNAL_FUNCTION_PARAMETERS) {
		zend_fcall_info fci;
		zend_fcall_info_cache fcc;
		ZEND_PARSE_PARAMETERS_START(1, 1)
			Z_PARAM_FUNC(fci, fcc)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(PT_THIS.traverse());
	});

	cls.method(sigs::traverseSimultaneously, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *right;
		zend_fcall_info fci;
		zend_fcall_info_cache fcc;
		ZEND_PARSE_PARAMETERS_START(2, 2)
			Z_PARAM_OBJECT(right)
			Z_PARAM_FUNC(fci, fcc)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(PT_THIS.traverseSimultaneously());
	});

	cls.method(sigs::isNull, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_COPY(pt_trinary_singleton(NullType::isNull()));
	});

	cls.method(sigs::isConstantValue, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_COPY(pt_trinary_singleton(NullType::isConstantValue()));
	});

	cls.method(sigs::isConstantScalarValue, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_COPY(pt_trinary_singleton(NullType::isConstantScalarValue()));
	});

	cls.method<&NullType::getConstantScalarTypes>(sigs::getConstantScalarTypes);

	cls.method<&NullType::getConstantScalarValues>(sigs::getConstantScalarValues);

	cls.method(sigs::isTrue, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_COPY(pt_trinary_singleton(NullType::isTrue()));
	});

	cls.method(sigs::isFalse, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_COPY(pt_trinary_singleton(NullType::isFalse()));
	});

	cls.method(sigs::isBoolean, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_COPY(pt_trinary_singleton(NullType::isBoolean()));
	});

	cls.method(sigs::isFloat, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_COPY(pt_trinary_singleton(NullType::isFloat()));
	});

	cls.method(sigs::isInteger, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_COPY(pt_trinary_singleton(NullType::isInteger()));
	});

	cls.method(sigs::isString, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_COPY(pt_trinary_singleton(NullType::isString()));
	});

	cls.method(sigs::isNumericString, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_COPY(pt_trinary_singleton(NullType::isNumericString()));
	});

	cls.method(sigs::isDecimalIntegerString, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_COPY(pt_trinary_singleton(NullType::isDecimalIntegerString()));
	});

	cls.method(sigs::isNonEmptyString, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_COPY(pt_trinary_singleton(NullType::isNonEmptyString()));
	});

	cls.method(sigs::isNonFalsyString, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_COPY(pt_trinary_singleton(NullType::isNonFalsyString()));
	});

	cls.method(sigs::isLiteralString, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_COPY(pt_trinary_singleton(NullType::isLiteralString()));
	});

	cls.method(sigs::isLowercaseString, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_COPY(pt_trinary_singleton(NullType::isLowercaseString()));
	});

	cls.method(sigs::isUppercaseString, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_COPY(pt_trinary_singleton(NullType::isUppercaseString()));
	});

	cls.method(sigs::isClassString, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_COPY(pt_trinary_singleton(NullType::isClassString()));
	});

	cls.method<&NullType::getClassStringObjectType>(sigs::getClassStringObjectType);

	cls.method<&NullType::getObjectTypeOrClassStringObjectType>(sigs::getObjectTypeOrClassStringObjectType);

	cls.method(sigs::isVoid, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_COPY(pt_trinary_singleton(NullType::isVoid()));
	});

	cls.method(sigs::isScalar, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_COPY(pt_trinary_singleton(NullType::isScalar()));
	});

	cls.method<&NullType::looseCompare, zp::Obj, zp::Zval>(sigs::looseCompare);

	cls.method(sigs::getSmallerType, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_null_comparison_type(INTERNAL_FUNCTION_PARAM_PASSTHRU, &NullType::getSmallerType);
	});

	cls.method(sigs::getSmallerOrEqualType, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_null_comparison_type(INTERNAL_FUNCTION_PARAM_PASSTHRU, &NullType::getSmallerOrEqualType);
	});

	cls.method(sigs::getGreaterType, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_null_comparison_type(INTERNAL_FUNCTION_PARAM_PASSTHRU, &NullType::getGreaterType);
	});

	cls.method(sigs::getGreaterOrEqualType, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_null_comparison_type(INTERNAL_FUNCTION_PARAM_PASSTHRU, &NullType::getGreaterOrEqualType);
	});

	cls.method<&NullType::getFiniteTypes>(sigs::getFiniteTypes);

	cls.method(sigs::exponentiate, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *exponent;
		if (!zp::parse<zp::Obj>(execute_data, exponent)) RETURN_THROWS();
		PT_RETURN_VAL(NullType::exponentiate());
	});

	cls.method<&NullType::toPhpDocNode>(sigs::toPhpDocNode);

	cls.method(sigs::hasTemplateOrLateResolvableType, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_BOOL(NullType::hasTemplateOrLateResolvableType());
	});

	/* the traits, in the twin's `use` order; the class body above wins over
	 * every name it declares (toClassConstantType, getConstantStrings) */
	ptdecl::NullType::registerTraits(cls);

	cls.shadow(&pt_ce_null_type);
}

/* }}} */
