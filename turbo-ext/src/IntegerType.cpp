/*
 * PHPStanTurbo\IntegerType — native implementation of
 * PHPStan\Type\IntegerType.
 *
 * Declared as PHPStan\Type\IntegerType itself at activation: not final
 * (ConstantIntegerType, IntegerRangeType and the PHP TemplateIntegerType
 * extend it), implementing PHPStan\Type\Type.
 */

#include "TypeTraits.h"
#include "generated/IntegerType.h"

namespace sigs = ptdecl::IntegerType::sig;

zend_class_entry *pt_ce_integer_type = nullptr;

namespace phpstanturbo {

/* Mirrors PHPStan\Type\IntegerType. The twin has no state. */
class IntegerType
{
public:
	explicit IntegerType(zend_object *self) : self(self) {}

	/* new IntegerType(): the constructor is empty, so instantiating the
	 * class is all `new` does; UNDEF = pending exception */
	static zv::Val create() { return pt_new_instance(pt_ce_integer_type); }

	static const char *describe() { return "int"; }

	static zv::Val getConstantStrings() { return zv::Val(zv::Arr::empty()); }

	/* $this */
	zv::Val toNumber() const { return thisValue(); }

	/* new IntegerType() */
	static zv::Val toBitwiseNotType() { return create(); }

	/* IntegerRangeType::createAllGreaterThanOrEqualTo(0) */
	static zv::Val toAbsoluteNumber()
	{
		zval zero;
		ZVAL_LONG(&zero, 0);
		return pt_integer_range_create_all_greater_than_or_equal_to(&zero);
	}

	/* new FloatType() — the shadowing class */
	static zv::Val toFloat()
	{
		return pt_val_of<pt_float_type_new>();
	}

	/* $this */
	zv::Val toInteger() const { return thisValue(); }

	/* new IntersectionType([new StringType(), new AccessoryDecimalIntegerStringType()]) */
	static zv::Val toString()
	{
		zval stringZv;
		if (UNEXPECTED(!pt_string_type_new(&stringZv))) return zv::Val();
		zv::Val string = zv::Val::adopt(stringZv);
		zv::Val accessory = pt_type_new(PT_CLASS_ACCESSORY_DECIMAL_INTEGER_STRING_TYPE, 0, NULL);
		if (UNEXPECTED(accessory.isUndef())) return zv::Val();
		zv::Arr types = zv::Arr::create(2);
		types.push(std::move(string));
		types.push(std::move(accessory));
		return pt_type_new(PT_CLASS_INTERSECTION_TYPE, 1, types.raw());
	}

	/* new ConstantArrayType([new ConstantIntegerType(0)], [$this], [1],
	 * isList: TrinaryLogic::createYes()) — the named argument skips
	 * $optionalKeys, whose default is [] */
	zv::Val toArray() const
	{
		zv::Val zero = pt_type_new_constant_integer(0);
		if (UNEXPECTED(zero.isUndef())) return zv::Val();
		zv::Arr keyTypes = zv::Arr::create(1);
		keyTypes.push(std::move(zero));
		zv::Arr valueTypes = zv::Arr::create(1);
		zval selfZv;
		ZVAL_OBJ(&selfZv, self);
		valueTypes.push(zv::Ref(&selfZv));
		zv::Arr nextAutoIndexes = zv::Arr::create(1);
		nextAutoIndexes.push(zv::Val::integer(1));
		zval args[5];
		args[0] = keyTypes.take();
		args[1] = valueTypes.take();
		args[2] = nextAutoIndexes.take();
		ZVAL_EMPTY_ARRAY(&args[3]);
		ZVAL_COPY_VALUE(&args[4], pt_trinary_singleton(PT_TRI_YES));
		zv::Val result = pt_type_new(PT_CLASS_CONSTANT_ARRAY_TYPE, 5, args);
		zval_ptr_dtor(&args[0]);
		zval_ptr_dtor(&args[1]);
		zval_ptr_dtor(&args[2]);
		return result;
	}

	/* $this */
	zv::Val toArrayKey() const { return thisValue(); }

	/* TypeCombinator::union($this, $this->toFloat(), $this->toString(),
	 * $this->toBoolean()) unless strict, TypeCombinator::union($this,
	 * $this->toFloat()) otherwise; UNDEF = pending exception */
	zv::Val toCoercedArgumentType(bool strictTypes) const
	{
		zv::Val floatType = isExact() ? toFloat() : pt_type_call(self, PT_LC("tofloat"), 0, NULL);
		if (UNEXPECTED(floatType.isUndef())) return zv::Val();
		zval args[4];
		ZVAL_OBJ(&args[0], self);
		ZVAL_COPY_VALUE(&args[1], floatType.raw());
		if (!strictTypes) {
			zv::Val string = isExact() ? toString() : pt_type_call(self, PT_LC("tostring"), 0, NULL);
			if (UNEXPECTED(string.isUndef())) return zv::Val();
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
			ZVAL_COPY_VALUE(&args[2], string.raw());
			ZVAL_COPY_VALUE(&args[3], boolean.raw());
			return pt_type_call_static(PT_CLASS_TYPE_COMBINATOR, PT_LC("union"), 4, args);
		}
		return pt_type_call_static(PT_CLASS_TYPE_COMBINATOR, PT_LC("union"), 2, args);
	}

	static zend_long isOffsetAccessLegal() { return PT_TRI_YES; }
	static zend_long isNull() { return PT_TRI_NO; }
	static zend_long isTrue() { return PT_TRI_NO; }
	static zend_long isFalse() { return PT_TRI_NO; }
	static zend_long isBoolean() { return PT_TRI_NO; }
	static zend_long isFloat() { return PT_TRI_NO; }
	static zend_long isInteger() { return PT_TRI_YES; }
	static zend_long isScalar() { return PT_TRI_YES; }

	/* new ConstantBooleanType(false) for an array, and for a non-numeric
	 * string when $phpVersion->nonNumericStringAndIntegerIsFalseOnLooseComparison();
	 * new BooleanType() otherwise. UNDEF = pending exception */
	static zv::Val looseCompare(zval *type, zval *phpVersion)
	{
		zend_long isArray = pt_type_call_trinary(Z_OBJ_P(type), PT_LC("isarray"), 0, NULL);
		if (UNEXPECTED(isArray < 0)) return zv::Val();
		if (isArray == PT_TRI_YES) return constantBoolean(false);

		if (UNEXPECTED(Z_TYPE_P(phpVersion) != IS_OBJECT)) {
			zend_type_error("phpstan_turbo: PhpVersion expected, %s given", zend_zval_value_name(phpVersion));
			return zv::Val();
		}
		zv::Val nonNumeric = pt_type_call(Z_OBJ_P(phpVersion), PT_LC("nonnumericstringandintegerisfalseonloosecomparison"), 0, NULL);
		if (UNEXPECTED(nonNumeric.isUndef())) return zv::Val();
		if (zend_is_true(nonNumeric.raw())) {
			zend_long isString = pt_type_call_trinary(Z_OBJ_P(type), PT_LC("isstring"), 0, NULL);
			if (UNEXPECTED(isString < 0)) return zv::Val();
			if (isString == PT_TRI_YES) {
				zend_long isNumericString = pt_type_call_trinary(Z_OBJ_P(type), PT_LC("isnumericstring"), 0, NULL);
				if (UNEXPECTED(isNumericString < 0)) return zv::Val();
				if (isNumericString == PT_TRI_NO) return constantBoolean(false);
			}
		}

		return pt_val_of<pt_boolean_type_new>();
	}

	/* the integers outside an IntegerRangeType / ConstantIntegerType: the
	 * union of the range below and the range above (either may vanish; both
	 * vanishing is never), null for any other type; UNDEF = pending
	 * exception */
	static zv::Val tryRemove(zval *typeToRemove)
	{
		NullableLong removeValueMin, removeValueMax;
		if (instanceof_function(Z_OBJCE_P(typeToRemove), pt_ce_integer_range_type)) {
			if (UNEXPECTED(!pt_integer_range_bounds(Z_OBJ_P(typeToRemove), removeValueMin, removeValueMax))) return zv::Val();
		} else if (instanceof_function(Z_OBJCE_P(typeToRemove), pt_ce_constant_integer_type)) {
			zend_long value;
			if (UNEXPECTED(!pt_constant_integer_get_value(Z_OBJ_P(typeToRemove), value))) return zv::Val();
			removeValueMin = removeValueMax = NullableLong::of(value);
		} else {
			return zv::Val::null();
		}

		zv::Val lowerPart;
		if (!removeValueMin.isNull) {
			lowerPart = pt_integer_range_from_interval(NullableLong::null(), removeValueMin, -1);
			if (UNEXPECTED(lowerPart.isUndef())) return zv::Val();
		}
		zv::Val upperPart;
		if (!removeValueMax.isNull) {
			upperPart = pt_integer_range_from_interval(removeValueMax, NullableLong::null(), +1);
			if (UNEXPECTED(upperPart.isUndef())) return zv::Val();
		}
		if (!lowerPart.isUndef() && !upperPart.isUndef()) {
			zv::Arr types = zv::Arr::create(2);
			types.push(std::move(lowerPart));
			types.push(std::move(upperPart));
			return pt_type_new_union(std::move(types));
		}
		if (!lowerPart.isUndef()) return lowerPart;
		if (!upperPart.isUndef()) return upperPart;
		return pt_type_new(PT_CLASS_NEVER_TYPE, 0, NULL);
	}

	static zv::Val getFiniteTypes() { return zv::Val(zv::Arr::empty()); }

	/* ExponentiateHelper::exponentiate($this, $exponent) */
	zv::Val exponentiate(zval *exponent) const
	{
		zv::Args args{self, exponent};
		return pt_type_call_static(PT_CLASS_EXPONENTIATE_HELPER, PT_LC("exponentiate"), 2, args);
	}

	/* new IdentifierTypeNode('int') */
	static zv::Val toPhpDocNode()
	{
		zv::Val name = zv::Val::string("int", 3);
		return pt_type_new(PT_CLASS_IDENTIFIER_TYPE_NODE, 1, name.raw());
	}

	static bool hasTemplateOrLateResolvableType() { return false; }

private:
	zend_object *self;

	/* exactly an IntegerType, none of its methods overridden: $this-calls
	 * can go straight to the C++ methods */
	bool isExact() const { return self->ce == pt_ce_integer_type; }

	zv::Val thisValue() const { return pt_this_value(self); }

	static zv::Val constantBoolean(bool value)
	{
		zval result;
		if (UNEXPECTED(!pt_constant_boolean_type_new(&result, value))) return zv::Val();
		return zv::Val::adopt(result);
	}
};

} // namespace phpstanturbo

using phpstanturbo::IntegerType;

bool pt_integer_type_new(zval *out)
{
	return pt_val_into(IntegerType::create(), out);
}

zv::Val pt_integer_type_loose_compare(zend_object *self, zval *type, zval *phpVersion)
{
	(void) self;
	return IntegerType::looseCompare(type, phpVersion);
}

zv::Val pt_integer_type_exponentiate(zend_object *self, zval *exponent)
{
	return IntegerType(self).exponentiate(exponent);
}

/* {{{ engine ABI glue: parameter parsing + registration */

#define PT_THIS IntegerType(Z_OBJ_P(ZEND_THIS))

void pt_register_integer_type()
{
	reg::Class cls("PHPStan\\Type\\IntegerType");
	ptdecl::IntegerType::declareClass(cls);
	ptdecl::IntegerType::declareProperties(cls);

	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
	});

	cls.method(sigs::describe, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *level;
		if (!zp::parse<zp::Obj>(execute_data, level)) RETURN_THROWS();
		RETURN_STRING(IntegerType::describe());
	});

	cls.method<&IntegerType::getConstantStrings>(sigs::getConstantStrings);

	cls.method<&IntegerType::toNumber>(sigs::toNumber);

	cls.method<&IntegerType::toBitwiseNotType>(sigs::toBitwiseNotType);

	cls.method<&IntegerType::toAbsoluteNumber>(sigs::toAbsoluteNumber);

	cls.method<&IntegerType::toFloat>(sigs::toFloat);

	cls.method<&IntegerType::toInteger>(sigs::toInteger);

	cls.method<&IntegerType::toString>(sigs::toString);

	cls.method<&IntegerType::toArray>(sigs::toArray);

	cls.method<&IntegerType::toArrayKey>(sigs::toArrayKey);

	cls.method<&IntegerType::toCoercedArgumentType, zp::Bool>(sigs::toCoercedArgumentType);

	cls.method(sigs::isOffsetAccessLegal, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_COPY(pt_trinary_singleton(IntegerType::isOffsetAccessLegal()));
	});

	cls.method(sigs::isNull, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_COPY(pt_trinary_singleton(IntegerType::isNull()));
	});

	cls.method(sigs::isTrue, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_COPY(pt_trinary_singleton(IntegerType::isTrue()));
	});

	cls.method(sigs::isFalse, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_COPY(pt_trinary_singleton(IntegerType::isFalse()));
	});

	cls.method(sigs::isBoolean, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_COPY(pt_trinary_singleton(IntegerType::isBoolean()));
	});

	cls.method(sigs::isFloat, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_COPY(pt_trinary_singleton(IntegerType::isFloat()));
	});

	cls.method(sigs::isInteger, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_COPY(pt_trinary_singleton(IntegerType::isInteger()));
	});

	cls.method(sigs::isScalar, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_COPY(pt_trinary_singleton(IntegerType::isScalar()));
	});

	cls.method<&IntegerType::looseCompare, zp::Obj, zp::Zval>(sigs::looseCompare);

	cls.method<&IntegerType::tryRemove, zp::Obj>(sigs::tryRemove);

	cls.method<&IntegerType::getFiniteTypes>(sigs::getFiniteTypes);

	cls.method<&IntegerType::exponentiate, zp::Obj>(sigs::exponentiate);

	cls.method<&IntegerType::toPhpDocNode>(sigs::toPhpDocNode);

	cls.method(sigs::hasTemplateOrLateResolvableType, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_BOOL(IntegerType::hasTemplateOrLateResolvableType());
	});

	/* the traits, in the twin's `use` order; the class body above wins over
	 * every name it declares (getConstantStrings, isNull, isTrue, isFalse,
	 * isBoolean, isFloat, isInteger) */
	ptdecl::IntegerType::registerTraits(cls);

	cls.shadow(&pt_ce_integer_type);
}

/* }}} */
