/*
 * PHPStanTurbo\ConstantFloatType — native implementation of
 * PHPStan\Type\Constant\ConstantFloatType.
 *
 * Declared as PHPStan\Type\Constant\ConstantFloatType itself at
 * activation: not final, extending the native FloatType (declared first —
 * Shadow.cpp materialises a parent plan before its child) and implementing
 * PHPStan\Type\ConstantScalarType. State is the twin's `private float
 * $value`, a declared typed property slot (IS_PROP_UNINIT until the
 * constructor writes it), so the std object handlers do GC/clone.
 *
 * The class body's methods live here; the three traits
 * (ConstantScalarTypeTrait, ConstantScalarToBooleanTrait,
 * ConstantNumericComparisonTypeTrait) come from the shared registrars in
 * TypeTraits.cpp, run after the class's own methods so the class body's
 * equals() wins. Everything else is inherited from FloatType.
 *
 * Float conversions the twin leaves to PHP — the (string) cast under the
 * `precision` ini and under precision -1, the (int) cast with its
 * out-of-range diagnostics — go through the engine's own routines so the
 * outcome is the twin's.
 */

#include "TypeTraits.h"
#include "generated/ConstantFloatType.h"

namespace slots = ptdecl::ConstantFloatType::slot;
namespace sigs = ptdecl::ConstantFloatType::sig;

#include <cmath>
#include <cstring>

zend_class_entry *pt_ce_constant_float_type = nullptr;

namespace phpstanturbo {

/* Mirrors PHPStan\Type\Constant\ConstantFloatType. State lives in the PHP
 * object's $value. */
class ConstantFloatType
{
public:
	explicit ConstantFloatType(zend_object *self) : self(self) {}

	/* __construct(private float $value): initializes the typed slot;
	 * parent::__construct() is FloatType's empty constructor */
	void construct(double value)
	{
		zval *slot = OBJ_PROP_NUM(self, slots::value);
		ZVAL_DOUBLE(slot, value);
		Z_PROP_FLAG_P(slot) = 0; /* no longer IS_PROP_UNINIT */
	}

	/* new ConstantFloatType($value); UNDEF = pending exception */
	static zv::Val create(double value)
	{
		zval object;
		if (UNEXPECTED(object_init_ex(&object, pt_ce_constant_float_type) != SUCCESS)) return zv::Val();
		ConstantFloatType(Z_OBJ(object)).construct(value);
		return zv::Val::adopt(object);
	}

	/* $this->value; false with an Error pending when the constructor never
	 * ran (ReflectionClass::newInstanceWithoutConstructor()) — the twin's
	 * typed-property read raises the same */
	[[nodiscard]] bool value(double &out) const
	{
		zval *slot = OBJ_PROP_NUM(self, slots::value);
		if (UNEXPECTED(Z_TYPE_P(slot) != IS_DOUBLE)) {
			zend_throw_error(NULL, "Typed property %s::$value must not be accessed before initialization", ZSTR_VAL(pt_ce_constant_float_type->name));
			return false;
		}
		out = Z_DVAL_P(slot);
		return true;
	}

	bool getValue(double &out) const { return value(out); }

	/* $type instanceof self && ($this->value === $type->value || both NAN)
	 * — the private slot declared here, read directly on a subclass too;
	 * false = pending exception */
	[[nodiscard]] bool equals(zval *type, bool &out) const
	{
		if (!instanceof_function(Z_OBJCE_P(type), pt_ce_constant_float_type)) {
			out = false;
			return true;
		}
		double v = 0.0, typeValue = 0.0;
		if (UNEXPECTED(!value(v) || !ConstantFloatType(Z_OBJ_P(type)).value(typeValue))) return false;
		out = v == typeValue || (std::isnan(v) && std::isnan(typeValue));
		return true;
	}

	/* (string) $value under ini precision -1 ('NAN' for a NAN), with '.0'
	 * appended when finite and the result carries no '.' — the shortest
	 * round-trip form zend_double_to_str() produces under that ini value:
	 * its negative ndigit selects zend_gcvt()'s mode-0 conversion */
	static zv::Val castFloatToString(double value)
	{
		if (std::isnan(value)) return zv::Val::string("NAN", 3);
		char buf[ZEND_DOUBLE_MAX_LENGTH];
		zend_gcvt(value, -1, '.', 'E', buf);
		size_t len = strlen(buf);
		if (std::isfinite(value) && memchr(buf, '.', len) == NULL) {
			memcpy(buf + len, ".0", 3);
			len += 2;
		}
		return zv::Val::string(buf, len);
	}

	/* $level->handle(static fn () => 'float', fn () => $this->castFloatToString($this->value)):
	 * 'float' for the type-only level, the value for every other one (the
	 * precise and cache callbacks are not given, so handle() falls through
	 * to the value callback); UNDEF = pending exception */
	zv::Val describe(zval *level) const
	{
		zv::Val typeOnly = pt_type_call(Z_OBJ_P(level), PT_LC("istypeonly"), 0, NULL);
		if (UNEXPECTED(typeOnly.isUndef())) return zv::Val();
		if (zend_is_true(typeOnly.raw())) return zv::Val::string("float", 5);
		double v = 0.0;
		if (UNEXPECTED(!value(v))) return zv::Val();
		return castFloatToString(v);
	}

	/* new UnionType([new ConstantStringType('0'), new ConstantStringType('-0')])
	 * for 0.0 (and -0.0, which === 0.0), new ConstantStringType((string) $this->value)
	 * otherwise — the cast under the current `precision` ini */
	zv::Val toString() const
	{
		double v = 0.0;
		if (UNEXPECTED(!value(v))) return zv::Val();
		if (v == 0.0) {
			zv::Val zero = pt_type_new_constant_string("0", 1);
			if (UNEXPECTED(zero.isUndef())) return zv::Val();
			zv::Val negativeZero = pt_type_new_constant_string("-0", 2);
			if (UNEXPECTED(negativeZero.isUndef())) return zv::Val();
			zv::Arr types = zv::Arr::create(2);
			types.push(std::move(zero));
			types.push(std::move(negativeZero));
			return pt_type_new_union(std::move(types));
		}
		zend_string *str = zend_double_to_str(v);
		zv::Val result = pt_type_new_constant_string(ZSTR_VAL(str), ZSTR_LEN(str));
		zend_string_release(str);
		return result;
	}

	/* new ConstantIntegerType((int) $this->value) */
	zv::Val toInteger() const
	{
		zend_long v;
		if (UNEXPECTED(!integerValue(v))) return zv::Val();
		return pt_type_new_constant_integer(v);
	}

	/* new ConstantIntegerType(~ (int) $this->value) */
	zv::Val toBitwiseNotType() const
	{
		zend_long v;
		if (UNEXPECTED(!integerValue(v))) return zv::Val();
		return pt_type_new_constant_integer(~v);
	}

	/* new self(abs($this->value)) */
	zv::Val toAbsoluteNumber() const
	{
		double v = 0.0;
		if (UNEXPECTED(!value(v))) return zv::Val();
		return create(std::fabs(v));
	}

	/* new ConstantIntegerType((int) $this->value) */
	zv::Val toArrayKey() const { return toInteger(); }

	/* [] for a NAN, [$this] otherwise */
	zv::Val getFiniteTypes() const
	{
		double v = 0.0;
		if (UNEXPECTED(!value(v))) return zv::Val();
		if (std::isnan(v)) return zv::Val(zv::Arr::empty());
		zv::Arr types = zv::Arr::create(1);
		zval selfZv;
		ZVAL_OBJ(&selfZv, self);
		types.push(zv::Ref(&selfZv));
		return zv::Val(std::move(types));
	}

	/* new FloatType() */
	static zv::Val generalize()
	{
		return pt_val_of<pt_float_type_new>();
	}

	/* new ConstTypeNode(new ConstExprFloatNode($this->castFloatToString($this->value))) */
	zv::Val toPhpDocNode() const
	{
		double v = 0.0;
		if (UNEXPECTED(!value(v))) return zv::Val();
		zv::Val str = castFloatToString(v);
		zv::Val constExpr = pt_type_new(PT_CLASS_CONST_EXPR_FLOAT_NODE, 1, str.raw());
		if (UNEXPECTED(constExpr.isUndef())) return zv::Val();
		return pt_type_new(PT_CLASS_CONST_TYPE_NODE, 1, constExpr.raw());
	}

private:
	zend_object *self;

	/* (int) $this->value — the engine's cast, with the diagnostics it
	 * raises for a NAN, an infinity or an out-of-range value; false =
	 * pending exception */
	bool integerValue(zend_long &out) const
	{
		double v = 0.0;
		if (UNEXPECTED(!value(v))) return false;
		zval asDouble;
		ZVAL_DOUBLE(&asDouble, v);
		out = zval_get_long(&asDouble);
		return !EG(exception);
	}
};

} // namespace phpstanturbo

using phpstanturbo::ConstantFloatType;

bool pt_constant_float_type_new(zval *out, double value)
{
	return pt_val_into(ConstantFloatType::create(value), out);
}

/* {{{ engine ABI glue: parameter parsing + registration */

#define PT_THIS ConstantFloatType(Z_OBJ_P(ZEND_THIS))

void pt_register_constant_float_type()
{
	reg::Class cls("PHPStan\\Type\\Constant\\ConstantFloatType");
	ptdecl::ConstantFloatType::declareClass(cls);
	/* "value" must stay the first declared property (slots::value) */
	ptdecl::ConstantFloatType::declareProperties(cls);

	cls.method<&ConstantFloatType::construct, zp::Double>(sigs::__construct);

	cls.method(sigs::getValue, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		double value = 0.0;
		if (UNEXPECTED(!PT_THIS.getValue(value))) RETURN_THROWS();
		RETURN_DOUBLE(value);
	});

	cls.method<&ConstantFloatType::equals, zp::TypeObj>(sigs::equals);
	cls.op<PT_OP_EQUALS, &ConstantFloatType::equals>();

	cls.method<&ConstantFloatType::describe, zp::Obj>(sigs::describe);
	cls.op<PT_OP_DESCRIBE, &ConstantFloatType::describe>();

	cls.method<&ConstantFloatType::toString>(sigs::toString);

	cls.method<&ConstantFloatType::toInteger>(sigs::toInteger);

	cls.method<&ConstantFloatType::toBitwiseNotType>(sigs::toBitwiseNotType);

	cls.method<&ConstantFloatType::toAbsoluteNumber>(sigs::toAbsoluteNumber);

	cls.method<&ConstantFloatType::toArrayKey>(sigs::toArrayKey);
	cls.op<PT_OP_TO_ARRAY_KEY, &ConstantFloatType::toArrayKey>();

	cls.method<&ConstantFloatType::getFiniteTypes>(sigs::getFiniteTypes);

	cls.method(sigs::generalize, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *precision;
		if (!zp::parse<zp::Obj>(execute_data, precision)) RETURN_THROWS();
		PT_RETURN_VAL(ConstantFloatType::generalize());
	});

	cls.method<&ConstantFloatType::toPhpDocNode>(sigs::toPhpDocNode);

	/* the traits, in the twin's `use` order; the class body above wins over
	 * every name it declares (equals, getFiniteTypes) */
	ptdecl::ConstantFloatType::registerTraits(cls);

	cls.shadow(&pt_ce_constant_float_type);
}

/* }}} */
