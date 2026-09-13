/*
 * PHPStanTurbo\ConstantIntegerType — native implementation of
 * PHPStan\Type\Constant\ConstantIntegerType.
 *
 * Declared as PHPStan\Type\Constant\ConstantIntegerType itself at
 * activation: not final (the PHP TemplateConstantIntegerType extends it),
 * extending the native IntegerType (declared first — Shadow.cpp
 * materialises a parent plan before its child) and implementing
 * PHPStan\Type\ConstantScalarType. State is the twin's `private int
 * $value`, a declared typed property slot (IS_PROP_UNINIT until the
 * constructor writes it), so the std object handlers do GC/clone.
 *
 * The class body's methods live here; the three traits
 * (ConstantScalarTypeTrait, ConstantScalarToBooleanTrait,
 * ConstantNumericComparisonTypeTrait) come from the shared registrars in
 * TypeTraits.cpp, run after the class's own methods so the class body's
 * isSuperTypeOf() wins. Everything else is inherited from IntegerType.
 */

#include "TypeTraits.h"
#include "generated/ConstantIntegerType.h"

namespace slots = ptdecl::ConstantIntegerType::slot;
namespace sigs = ptdecl::ConstantIntegerType::sig;

zend_class_entry *pt_ce_constant_integer_type = nullptr;

namespace phpstanturbo {

/* Mirrors PHPStan\Type\Constant\ConstantIntegerType. State lives in the
 * PHP object's $value. */
class ConstantIntegerType
{
public:
	explicit ConstantIntegerType(zend_object *self) : self(self) {}

	/* __construct(private int $value): initializes the typed slot;
	 * parent::__construct() is IntegerType's empty constructor */
	void construct(zend_long value)
	{
		zval *slot = OBJ_PROP_NUM(self, slots::value);
		ZVAL_LONG(slot, value);
		Z_PROP_FLAG_P(slot) = 0; /* no longer IS_PROP_UNINIT */
	}

	/* new ConstantIntegerType($value); UNDEF = pending exception */
	static zv::Val create(zend_long value)
	{
		zval object;
		if (UNEXPECTED(object_init_ex(&object, pt_ce_constant_integer_type) != SUCCESS)) return zv::Val();
		ConstantIntegerType(Z_OBJ(object)).construct(value);
		return zv::Val::adopt(object);
	}

	/* $this->value; false with an Error pending when the constructor never
	 * ran (ReflectionClass::newInstanceWithoutConstructor()) — the twin's
	 * typed-property read raises the same */
	[[nodiscard]] bool value(zend_long &out) const
	{
		zval *slot = OBJ_PROP_NUM(self, slots::value);
		if (UNEXPECTED(Z_TYPE_P(slot) != IS_LONG)) {
			zend_throw_error(NULL, "Typed property %s::$value must not be accessed before initialization", ZSTR_VAL(pt_ce_constant_integer_type->name));
			return false;
		}
		out = Z_LVAL_P(slot);
		return true;
	}

	bool getValue(zend_long &out) const { return value(out); }

	/* yes/no against another ConstantIntegerType's value, maybe/no against
	 * an IntegerRangeType's bounds, maybe for any other IntegerType, the
	 * CompoundType callback, no otherwise; UNDEF = pending exception */
	zv::Val isSuperTypeOf(zval *type) const
	{
		zend_long v = 0;
		if (UNEXPECTED(!value(v))) return zv::Val();
		/* $type instanceof self: $this->value === $type->value — the
		 * private slot declared here, read directly on a subclass too */
		if (instanceof_function(Z_OBJCE_P(type), pt_ce_constant_integer_type)) {
			zend_long typeValue = 0;
			if (UNEXPECTED(!ConstantIntegerType(Z_OBJ_P(type)).value(typeValue))) return zv::Val();
			return pt_type_is_super_type_of_result(v == typeValue ? PT_TRI_YES : PT_TRI_NO);
		}

		if (instanceof_function(Z_OBJCE_P(type), pt_ce_integer_range_type)) {
			NullableLong min, max;
			if (UNEXPECTED(!pt_integer_range_bounds(Z_OBJ_P(type), min, max))) return zv::Val();
			if ((min.isNull || min.value <= v) && (max.isNull || v <= max.value)) return pt_type_is_super_type_of_result(PT_TRI_MAYBE);
			return pt_type_is_super_type_of_result(PT_TRI_NO);
		}

		/* $type instanceof parent */
		if (instanceof_function(Z_OBJCE_P(type), pt_ce_integer_type)) return pt_type_is_super_type_of_result(PT_TRI_MAYBE);

		bool compound;
		if (UNEXPECTED(!pt_type_instanceof(type, PT_CLASS_COMPOUND_TYPE, compound))) return zv::Val();
		if (compound) {
			zval selfZv;
			ZVAL_OBJ(&selfZv, self);
			return pt_type_call(Z_OBJ_P(type), PT_LC("issubtypeof"), 1, &selfZv);
		}

		return pt_type_is_super_type_of_result(PT_TRI_NO);
	}

	/* $level->handle(static fn () => 'int', fn () => sprintf('%s', $this->value)):
	 * 'int' for the type-only level, the value for every other one (the
	 * precise and cache callbacks are not given, so handle() falls through
	 * to the value callback); UNDEF = pending exception */
	zv::Val describe(zval *level) const
	{
		zv::Val typeOnly = pt_type_call(Z_OBJ_P(level), PT_LC("istypeonly"), 0, NULL);
		if (UNEXPECTED(typeOnly.isUndef())) return zv::Val();
		if (zend_is_true(typeOnly.raw())) return zv::Val::string("int", 3);
		zend_long v = 0;
		if (UNEXPECTED(!value(v))) return zv::Val();
		return zv::Val::adoptString(zend_long_to_str(v));
	}

	/* new ConstantFloatType($this->value) — the int coerced to float */
	zv::Val toFloat() const
	{
		zend_long v = 0;
		if (UNEXPECTED(!value(v))) return zv::Val();
		return pt_type_new_constant_float((double) v);
	}

	/* new self(~$this->value) */
	zv::Val toBitwiseNotType() const
	{
		zend_long v = 0;
		if (UNEXPECTED(!value(v))) return zv::Val();
		return create(~v);
	}

	/* new self(abs($this->value)); new ConstantFloatType(-(float) $this->value)
	 * for PHP_INT_MIN, whose absolute value is not an int */
	zv::Val toAbsoluteNumber() const
	{
		zend_long v = 0;
		if (UNEXPECTED(!value(v))) return zv::Val();
		if (v == ZEND_LONG_MIN) return pt_type_new_constant_float(-(double) v);
		return create(v < 0 ? -v : v);
	}

	/* new ConstantStringType((string) $this->value) */
	zv::Val toString() const
	{
		zend_long v = 0;
		if (UNEXPECTED(!value(v))) return zv::Val();
		zend_string *str = zend_long_to_str(v);
		zv::Val result = pt_type_new_constant_string(ZSTR_VAL(str), ZSTR_LEN(str));
		zend_string_release(str);
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
			/* ConstantScalarToBooleanTrait's toBoolean():
			 * new ConstantBooleanType((bool) $this->value) */
			zv::Val boolean;
			if (EXPECTED(isExact())) {
				zend_long v = 0;
				if (UNEXPECTED(!value(v))) return zv::Val();
				zval created;
				if (UNEXPECTED(!pt_constant_boolean_type_new(&created, v != 0))) return zv::Val();
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

	/* new IntegerType() */
	static zv::Val generalize()
	{
		return pt_val_of<pt_integer_type_new>();
	}

	/* new ConstTypeNode(new ConstExprIntegerNode((string) $this->value)) */
	zv::Val toPhpDocNode() const
	{
		zend_long v = 0;
		if (UNEXPECTED(!value(v))) return zv::Val();
		zv::Val str = zv::Val::adoptString(zend_long_to_str(v));
		zv::Val constExpr = pt_type_new(PT_CLASS_CONST_EXPR_INTEGER_NODE, 1, str.raw());
		if (UNEXPECTED(constExpr.isUndef())) return zv::Val();
		return pt_type_new(PT_CLASS_CONST_TYPE_NODE, 1, constExpr.raw());
	}

private:
	zend_object *self;

	/* exactly a ConstantIntegerType, none of its methods overridden:
	 * $this-calls can go straight to the C++ methods */
	bool isExact() const { return self->ce == pt_ce_constant_integer_type; }

	zv::Val thisValue() const { return pt_this_value(self); }
};

} // namespace phpstanturbo

using phpstanturbo::ConstantIntegerType;

bool pt_constant_integer_type_new(zval *out, zend_long value)
{
	return pt_val_into(ConstantIntegerType::create(value), out);
}

bool pt_constant_integer_type_value(zend_object *object, zend_long &out)
{
	return ConstantIntegerType(object).value(out);
}

bool pt_constant_integer_get_value(zend_object *object, zend_long &out)
{
	if (EXPECTED(object->ce == pt_ce_constant_integer_type)) return ConstantIntegerType(object).value(out);
	/* a subclass may override getValue() */
	zv::Val result = pt_type_call(object, PT_LC("getvalue"), 0, NULL);
	if (UNEXPECTED(result.isUndef())) return false;
	if (UNEXPECTED(!zv::Ref(result.raw()).isLong())) {
		zend_type_error("phpstan_turbo: %s::getValue() must return int", ZSTR_VAL(object->ce->name));
		return false;
	}
	out = zv::Ref(result.raw()).asLong();
	return true;
}

/* {{{ engine ABI glue: parameter parsing + registration */

#define PT_THIS ConstantIntegerType(Z_OBJ_P(ZEND_THIS))

void pt_register_constant_integer_type()
{
	reg::Class cls("PHPStan\\Type\\Constant\\ConstantIntegerType");
	ptdecl::ConstantIntegerType::declareClass(cls);
	/* "value" must stay the first declared property (slots::value) */
	ptdecl::ConstantIntegerType::declareProperties(cls);

	cls.method<&ConstantIntegerType::construct, zp::Long>(sigs::__construct);

	cls.method(sigs::getValue, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		zend_long value = 0;
		if (UNEXPECTED(!PT_THIS.getValue(value))) RETURN_THROWS();
		RETURN_LONG(value);
	});

	cls.method<&ConstantIntegerType::isSuperTypeOf, zp::Obj>(sigs::isSuperTypeOf);

	cls.method<&ConstantIntegerType::describe, zp::Obj>(sigs::describe);

	cls.method<&ConstantIntegerType::toFloat>(sigs::toFloat);

	cls.method<&ConstantIntegerType::toBitwiseNotType>(sigs::toBitwiseNotType);

	cls.method<&ConstantIntegerType::toAbsoluteNumber>(sigs::toAbsoluteNumber);

	cls.method<&ConstantIntegerType::toString>(sigs::toString);

	cls.method<&ConstantIntegerType::toArrayKey>(sigs::toArrayKey);

	cls.method<&ConstantIntegerType::toCoercedArgumentType, zp::Bool>(sigs::toCoercedArgumentType);

	cls.method(sigs::generalize, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *precision;
		if (!zp::parse<zp::Obj>(execute_data, precision)) RETURN_THROWS();
		PT_RETURN_VAL(ConstantIntegerType::generalize());
	});

	cls.method<&ConstantIntegerType::toPhpDocNode>(sigs::toPhpDocNode);

	/* the traits, in the twin's `use` order; the class body above wins over
	 * every name it declares (isSuperTypeOf) */
	ptdecl::ConstantIntegerType::registerTraits(cls);

	cls.shadow(&pt_ce_constant_integer_type);
}

/* }}} */
