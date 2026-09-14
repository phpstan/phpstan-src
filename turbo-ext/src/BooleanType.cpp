/*
 * PHPStanTurbo\BooleanType — native implementation of
 * PHPStan\Type\BooleanType.
 *
 * Declared as PHPStan\Type\BooleanType itself at activation: not final
 * (ConstantBooleanType and the PHP TemplateBooleanType extend it),
 * implementing PHPStan\Type\Type.
 */

#include "TypeTraits.h"
#include "generated/BooleanType.h"

namespace sigs = ptdecl::BooleanType::sig;

zend_class_entry *pt_ce_boolean_type = nullptr;

namespace phpstanturbo {

/* Mirrors PHPStan\Type\BooleanType. The twin has no state. */
class BooleanType
{
public:
	explicit BooleanType(zend_object *self) : self(self) {}

	/* new BooleanType(): the constructor is empty, so instantiating the
	 * class is all `new` does; UNDEF = pending exception */
	static zv::Val create() { return pt_new_instance(pt_ce_boolean_type); }

	static zv::Val getConstantStrings() { return zv::Val(zv::Arr::empty()); }

	/* UNDEF = pending exception */
	static zv::Val getConstantScalarTypes() { return bothConstants(); }

	static zv::Val getConstantScalarValues()
	{
		zv::Arr values = zv::Arr::create(2);
		values.push(zv::Val::boolean(true));
		values.push(zv::Val::boolean(false));
		return zv::Val(std::move(values));
	}

	static const char *describe() { return "bool"; }

	/* $this->toInteger(); UNDEF = pending exception */
	zv::Val toNumber() const
	{
		if (EXPECTED(isExact())) return toInteger();
		return pt_type_call(self, PT_LC("tointeger"), 0, NULL);
	}

	static zv::Val toBitwiseNotType() { return pt_type_new_error_type(); }

	/* $this->toNumber()->toAbsoluteNumber(); UNDEF = pending exception */
	zv::Val toAbsoluteNumber() const
	{
		zv::Val number = isExact() ? toNumber() : pt_type_call(self, PT_LC("tonumber"), 0, NULL);
		if (UNEXPECTED(number.isUndef())) return zv::Val();
		if (UNEXPECTED(!zv::Ref(number.raw()).isObject())) {
			zend_type_error("phpstan_turbo: toNumber() must return %s", ZSTR_VAL(pt_ce_boolean_type->name));
			return zv::Val();
		}
		return pt_type_call(zv::Ref(number.raw()).asObject(), PT_LC("toabsolutenumber"), 0, NULL);
	}

	/* new UnionType([new ConstantStringType(''), new ConstantStringType('1')]) */
	static zv::Val toString()
	{
		zv::Val empty = pt_type_new_constant_string("", 0);
		if (UNEXPECTED(empty.isUndef())) return zv::Val();
		zv::Val one = pt_type_new_constant_string("1", 1);
		if (UNEXPECTED(one.isUndef())) return zv::Val();
		return unionOf(std::move(empty), std::move(one));
	}

	/* new UnionType([new ConstantIntegerType(0), new ConstantIntegerType(1)]) */
	static zv::Val toInteger() { return zeroOrOne(); }

	/* new UnionType([new ConstantFloatType(0.0), new ConstantFloatType(1.0)]) */
	static zv::Val toFloat()
	{
		zv::Val zero = pt_type_new_constant_float(0.0);
		if (UNEXPECTED(zero.isUndef())) return zv::Val();
		zv::Val one = pt_type_new_constant_float(1.0);
		if (UNEXPECTED(one.isUndef())) return zv::Val();
		return unionOf(std::move(zero), std::move(one));
	}

	/* new ConstantArrayType([new ConstantIntegerType(0)], [$this], [1],
	 * isList: TrinaryLogic::createYes()) — the named argument skips
	 * $optionalKeys, whose default is [] */
	zv::Val toArray() const { return pt_type_scalar_to_array(self); }

	/* new UnionType([new ConstantIntegerType(0), new ConstantIntegerType(1)]) */
	static zv::Val toArrayKey() { return zeroOrOne(); }

	/* TypeCombinator::union($this->toInteger(), $this->toFloat(),
	 * $this->toString(), $this) unless strict; UNDEF = pending exception */
	zv::Val toCoercedArgumentType(bool strictTypes) const
	{
		if (!strictTypes) {
			zv::Val integer = isExact() ? toInteger() : pt_type_call(self, PT_LC("tointeger"), 0, NULL);
			if (UNEXPECTED(integer.isUndef())) return zv::Val();
			zv::Val floatType = isExact() ? toFloat() : pt_type_call(self, PT_LC("tofloat"), 0, NULL);
			if (UNEXPECTED(floatType.isUndef())) return zv::Val();
			zv::Val string = isExact() ? toString() : pt_type_call(self, PT_LC("tostring"), 0, NULL);
			if (UNEXPECTED(string.isUndef())) return zv::Val();
			zv::Args args{integer.raw(), floatType.raw(), string.raw(), self};
			return pt_type_combinator_call(PT_LC("union"), 4, args);
		}
		return thisValue();
	}

	static zend_long isOffsetAccessLegal() { return PT_TRI_YES; }
	static zend_long isNull() { return PT_TRI_NO; }
	static zend_long isTrue() { return PT_TRI_MAYBE; }
	static zend_long isFalse() { return PT_TRI_MAYBE; }
	static zend_long isBoolean() { return PT_TRI_YES; }
	static zend_long isScalar() { return PT_TRI_YES; }

	/* new BooleanType() */
	static zv::Val looseCompare() { return create(); }

	/* new ConstantBooleanType(!$typeToRemove->getValue()) for a
	 * ConstantBooleanType, null otherwise; UNDEF = pending exception */
	static zv::Val tryRemove(zval *typeToRemove)
	{
		if (!instanceof_function(Z_OBJCE_P(typeToRemove), pt_ce_constant_boolean_type)) return zv::Val::null();
		bool value;
		if (EXPECTED(Z_OBJCE_P(typeToRemove) == pt_ce_constant_boolean_type)) {
			if (UNEXPECTED(!pt_constant_boolean_type_value(Z_OBJ_P(typeToRemove), value))) return zv::Val();
		} else {
			/* a subclass may override getValue() */
			zv::Val result = pt_type_call(Z_OBJ_P(typeToRemove), PT_LC("getvalue"), 0, NULL);
			if (UNEXPECTED(result.isUndef())) return zv::Val();
			value = zend_is_true(result.raw());
		}
		zval removed;
		if (UNEXPECTED(!pt_constant_boolean_type_new(&removed, !value))) return zv::Val();
		return zv::Val::adopt(removed);
	}

	/* [new ConstantBooleanType(true), new ConstantBooleanType(false)] */
	static zv::Val getFiniteTypes() { return bothConstants(); }

	/* ExponentiateHelper::exponentiate($this, $exponent) */
	zv::Val exponentiate(zval *exponent) const
	{
		zv::Args args{self, exponent};
		return pt_type_call_static(PT_CLASS_EXPONENTIATE_HELPER, PT_LC("exponentiate"), 2, args);
	}

	/* new IdentifierTypeNode('bool') */
	static zv::Val toPhpDocNode()
	{
		zv::Val name = zv::Val::string("bool", 4);
		return pt_type_new(PT_CLASS_IDENTIFIER_TYPE_NODE, 1, name.raw());
	}

	/* yes when $this->isTrue()->yes(), no when $this->isFalse()->yes(),
	 * maybe otherwise; -1 = pending exception */
	[[nodiscard]] zend_long toTrinaryLogic() const
	{
		if (EXPECTED(isExact())) return PT_TRI_MAYBE; /* isTrue() and isFalse() are both maybe */
		if (self->ce == pt_ce_constant_boolean_type) {
			bool value;
			if (UNEXPECTED(!pt_constant_boolean_type_value(self, value))) return -1;
			return value ? PT_TRI_YES : PT_TRI_NO;
		}
		zend_long isTrue = pt_type_call_trinary(self, PT_LC("istrue"), 0, NULL);
		if (UNEXPECTED(isTrue < 0)) return -1;
		if (isTrue == PT_TRI_YES) return PT_TRI_YES;
		zend_long isFalse = pt_type_call_trinary(self, PT_LC("isfalse"), 0, NULL);
		if (UNEXPECTED(isFalse < 0)) return -1;
		if (isFalse == PT_TRI_YES) return PT_TRI_NO;
		return PT_TRI_MAYBE;
	}

	static bool hasTemplateOrLateResolvableType() { return false; }

private:
	zend_object *self;

	/* exactly a BooleanType, none of its methods overridden: $this-calls
	 * can go straight to the C++ methods */
	bool isExact() const { return self->ce == pt_ce_boolean_type; }

	zv::Val thisValue() const { return pt_this_value(self); }

	static zv::Val unionOf(zv::Val first, zv::Val second)
	{
		zv::Arr types = zv::Arr::create(2);
		types.push(std::move(first));
		types.push(std::move(second));
		return pt_type_new_union(std::move(types));
	}

	static zv::Val zeroOrOne()
	{
		zv::Val zero = pt_type_new_constant_integer(0);
		if (UNEXPECTED(zero.isUndef())) return zv::Val();
		zv::Val one = pt_type_new_constant_integer(1);
		if (UNEXPECTED(one.isUndef())) return zv::Val();
		return unionOf(std::move(zero), std::move(one));
	}

	/* [new ConstantBooleanType(true), new ConstantBooleanType(false)] */
	static zv::Val bothConstants()
	{
		zval trueType, falseType;
		if (UNEXPECTED(!pt_constant_boolean_type_new(&trueType, true))) return zv::Val();
		if (UNEXPECTED(!pt_constant_boolean_type_new(&falseType, false))) {
			zval_ptr_dtor(&trueType);
			return zv::Val();
		}
		zv::Arr types = zv::Arr::create(2);
		types.push(zv::Val::adopt(trueType));
		types.push(zv::Val::adopt(falseType));
		return zv::Val(std::move(types));
	}
};

} // namespace phpstanturbo

using phpstanturbo::BooleanType;

bool pt_boolean_type_new(zval *out)
{
	return pt_val_into(BooleanType::create(), out);
}

/* {{{ engine ABI glue: parameter parsing + registration */

#define PT_THIS BooleanType(Z_OBJ_P(ZEND_THIS))

void pt_register_boolean_type()
{
	reg::Class cls("PHPStan\\Type\\BooleanType");
	ptdecl::BooleanType::declareClass(cls);
	ptdecl::BooleanType::declareProperties(cls);

	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
	});

	cls.method<&BooleanType::getConstantStrings>(sigs::getConstantStrings);

	cls.method<&BooleanType::getConstantScalarTypes>(sigs::getConstantScalarTypes);

	cls.method<&BooleanType::getConstantScalarValues>(sigs::getConstantScalarValues);
	cls.op(PT_OP_GET_CONSTANT_SCALAR_VALUES, PT_OP_LAMBDA { return BooleanType::getConstantScalarValues(); });

	cls.method(sigs::describe, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *level;
		if (!zp::parse<zp::Obj>(execute_data, level)) RETURN_THROWS();
		RETURN_STRING(BooleanType::describe());
	});
	cls.op(PT_OP_DESCRIBE, PT_OP_LAMBDA { return pt_op_string(BooleanType::describe()); });

	cls.method<&BooleanType::toNumber>(sigs::toNumber);

	cls.method<&BooleanType::toBitwiseNotType>(sigs::toBitwiseNotType);

	cls.method<&BooleanType::toAbsoluteNumber>(sigs::toAbsoluteNumber);

	cls.method<&BooleanType::toString>(sigs::toString);

	cls.method<&BooleanType::toInteger>(sigs::toInteger);

	cls.method<&BooleanType::toFloat>(sigs::toFloat);

	cls.method<&BooleanType::toArray>(sigs::toArray);

	cls.method<&BooleanType::toArrayKey>(sigs::toArrayKey);
	cls.op(PT_OP_TO_ARRAY_KEY, PT_OP_LAMBDA { return BooleanType::toArrayKey(); });

	cls.method<&BooleanType::toCoercedArgumentType, zp::Bool>(sigs::toCoercedArgumentType);

	cls.method(sigs::isOffsetAccessLegal, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_COPY(pt_trinary_singleton(BooleanType::isOffsetAccessLegal()));
	});

	cls.method(sigs::isNull, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_COPY(pt_trinary_singleton(BooleanType::isNull()));
	});
	cls.op(PT_OP_IS_NULL, PT_OP_LAMBDA { return pt_op_trinary(BooleanType::isNull()); });

	cls.method(sigs::isTrue, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_COPY(pt_trinary_singleton(BooleanType::isTrue()));
	});

	cls.method(sigs::isFalse, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_COPY(pt_trinary_singleton(BooleanType::isFalse()));
	});

	cls.method(sigs::isBoolean, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_COPY(pt_trinary_singleton(BooleanType::isBoolean()));
	});
	cls.op(PT_OP_IS_BOOLEAN, PT_OP_LAMBDA { return pt_op_trinary(BooleanType::isBoolean()); });

	cls.method(sigs::isScalar, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_COPY(pt_trinary_singleton(BooleanType::isScalar()));
	});

	cls.method(sigs::looseCompare, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *type, *phpVersion;
		if (!zp::parse<zp::Obj, zp::Zval>(execute_data, type, phpVersion)) RETURN_THROWS();
		PT_RETURN_VAL(BooleanType::looseCompare());
	});

	cls.method<&BooleanType::tryRemove, zp::Obj>(sigs::tryRemove);

	cls.method<&BooleanType::getFiniteTypes>(sigs::getFiniteTypes);

	cls.method<&BooleanType::exponentiate, zp::Obj>(sigs::exponentiate);

	cls.method<&BooleanType::toPhpDocNode>(sigs::toPhpDocNode);

	cls.method(sigs::toTrinaryLogic, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		zend_long value = PT_THIS.toTrinaryLogic();
		if (UNEXPECTED(value < 0)) RETURN_THROWS();
		RETURN_COPY(pt_trinary_singleton(value));
	});

	cls.method(sigs::hasTemplateOrLateResolvableType, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_BOOL(BooleanType::hasTemplateOrLateResolvableType());
	});
	cls.op(PT_OP_HAS_TEMPLATE_OR_LATE_RESOLVABLE_TYPE, PT_OP_LAMBDA { return zv::Val::boolean(BooleanType::hasTemplateOrLateResolvableType()); });

	/* the traits, in the twin's `use` order; the class body above wins over
	 * every name it declares (getConstantStrings, getConstantScalarTypes,
	 * getConstantScalarValues, isNull, isTrue, isFalse, isBoolean) */
	ptdecl::BooleanType::registerTraits(cls);

	cls.shadow(&pt_ce_boolean_type);
}

/* }}} */
