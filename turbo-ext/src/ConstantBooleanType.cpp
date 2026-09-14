/*
 * PHPStanTurbo\ConstantBooleanType — native implementation of
 * PHPStan\Type\Constant\ConstantBooleanType.
 *
 * Declared as PHPStan\Type\Constant\ConstantBooleanType itself at
 * activation: not final, extending the native BooleanType (declared first —
 * Shadow.cpp materialises a parent plan before its child) and implementing
 * PHPStan\Type\ConstantScalarType. State is the twin's `private bool
 * $value`, a declared typed property slot (IS_PROP_UNINIT until the
 * constructor writes it), so the std object handlers do GC/clone.
 *
 * The class body's methods live here; ConstantScalarTypeTrait comes from
 * the shared registrar in TypeTraits.cpp, run after the class's own methods
 * so the class body's looseCompare() wins, and the trait's looseCompare()
 * is registered under its alias scalarLooseCompare (private) exactly as
 * `use ConstantScalarTypeTrait { looseCompare as private scalarLooseCompare; }`
 * declares. Everything else is inherited from BooleanType.
 */

#include "TypeTraits.h"
#include "generated/ConstantBooleanType.h"

namespace slots = ptdecl::ConstantBooleanType::slot;
namespace sigs = ptdecl::ConstantBooleanType::sig;

zend_class_entry *pt_ce_constant_boolean_type = nullptr;

namespace phpstanturbo {

/* Mirrors PHPStan\Type\Constant\ConstantBooleanType. State lives in the
 * PHP object's $value. */
class ConstantBooleanType
{
public:
	explicit ConstantBooleanType(zend_object *self) : self(self) {}

	/* __construct(private bool $value): initializes the typed slot;
	 * parent::__construct() is BooleanType's empty constructor */
	void construct(bool value)
	{
		zval *slot = OBJ_PROP_NUM(self, slots::value);
		ZVAL_BOOL(slot, value);
		Z_PROP_FLAG_P(slot) = 0; /* no longer IS_PROP_UNINIT */
	}

	/* new ConstantBooleanType($value); UNDEF = pending exception */
	static zv::Val create(bool value)
	{
		zval object;
		if (UNEXPECTED(object_init_ex(&object, pt_ce_constant_boolean_type) != SUCCESS)) return zv::Val();
		ConstantBooleanType(Z_OBJ(object)).construct(value);
		return zv::Val::adopt(object);
	}

	/* $this->value; false with an Error pending when the constructor never
	 * ran (ReflectionClass::newInstanceWithoutConstructor()) — the twin's
	 * typed-property read raises the same */
	[[nodiscard]] bool value(bool &out) const
	{
		zval *slot = OBJ_PROP_NUM(self, slots::value);
		if (UNEXPECTED(Z_TYPE_P(slot) != IS_TRUE && Z_TYPE_P(slot) != IS_FALSE)) {
			zend_throw_error(NULL, "Typed property %s::$value must not be accessed before initialization", ZSTR_VAL(pt_ce_constant_boolean_type->name));
			return false;
		}
		out = Z_TYPE_P(slot) == IS_TRUE;
		return true;
	}

	bool getValue(bool &out) const { return value(out); }

	/* 'true' / 'false'; NULL = pending exception */
	const char *describe() const
	{
		bool v = false;
		if (UNEXPECTED(!value(v))) return NULL;
		return v ? "true" : "false";
	}

	/* StaticTypeFactory::falsey() for true, new NeverType() for false */
	zv::Val getSmallerType() const
	{
		bool v = false;
		if (UNEXPECTED(!value(v))) return zv::Val();
		if (v) return falsey();
		return pt_type_new_never_type();
	}

	/* new MixedType() for true, StaticTypeFactory::falsey() for false */
	zv::Val getSmallerOrEqualType() const
	{
		bool v = false;
		if (UNEXPECTED(!value(v))) return zv::Val();
		if (v) return pt_type_new_mixed_type();
		return falsey();
	}

	/* new NeverType() for true, StaticTypeFactory::truthy() for false */
	zv::Val getGreaterType() const
	{
		bool v = false;
		if (UNEXPECTED(!value(v))) return zv::Val();
		if (v) return pt_type_new_never_type();
		return truthy();
	}

	/* StaticTypeFactory::truthy() for true, new MixedType() for false */
	zv::Val getGreaterOrEqualType() const
	{
		bool v = false;
		if (UNEXPECTED(!value(v))) return zv::Val();
		if (v) return truthy();
		return pt_type_new_mixed_type();
	}

	/* $this */
	zv::Val toBoolean() const { return thisValue(); }

	/* new ConstantIntegerType((int) $this->value) */
	zv::Val toNumber() const { return toInteger(); }

	static zv::Val toBitwiseNotType() { return pt_type_new_error_type(); }

	/* $this->toNumber()->toAbsoluteNumber(); UNDEF = pending exception */
	zv::Val toAbsoluteNumber() const
	{
		zv::Val number = isExact() ? toNumber() : pt_type_call(self, PT_LC("tonumber"), 0, NULL);
		if (UNEXPECTED(number.isUndef())) return zv::Val();
		if (UNEXPECTED(!zv::Ref(number.raw()).isObject())) {
			zend_type_error("phpstan_turbo: toNumber() must return %s", ZSTR_VAL(pt_ce_constant_boolean_type->parent->name));
			return zv::Val();
		}
		return pt_type_call(zv::Ref(number.raw()).asObject(), PT_LC("toabsolutenumber"), 0, NULL);
	}

	/* new ConstantStringType((string) $this->value): '1' or '' */
	zv::Val toString() const
	{
		bool v = false;
		if (UNEXPECTED(!value(v))) return zv::Val();
		return v ? pt_type_new_constant_string("1", 1) : pt_type_new_constant_string("", 0);
	}

	/* new ConstantIntegerType((int) $this->value) */
	zv::Val toInteger() const
	{
		bool v = false;
		if (UNEXPECTED(!value(v))) return zv::Val();
		return pt_type_new_constant_integer(v ? 1 : 0);
	}

	/* new ConstantFloatType((float) $this->value) */
	zv::Val toFloat() const
	{
		bool v = false;
		if (UNEXPECTED(!value(v))) return zv::Val();
		return pt_type_new_constant_float(v ? 1.0 : 0.0);
	}

	/* new ConstantIntegerType((int) $this->value) */
	zv::Val toArrayKey() const { return toInteger(); }

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

	/* TrinaryLogic::createFromBoolean($this->value === true); -1 = pending
	 * exception */
	[[nodiscard]] zend_long isTrue() const
	{
		bool v = false;
		if (UNEXPECTED(!value(v))) return -1;
		return v ? PT_TRI_YES : PT_TRI_NO;
	}

	/* TrinaryLogic::createFromBoolean($this->value === false) */
	zend_long isFalse() const
	{
		bool v = false;
		if (UNEXPECTED(!value(v))) return -1;
		return v ? PT_TRI_NO : PT_TRI_YES;
	}

	/* new BooleanType() */
	static zv::Val generalize()
	{
		return pt_val_of<pt_boolean_type_new>();
	}

	/* new IdentifierTypeNode($this->value ? 'true' : 'false') */
	zv::Val toPhpDocNode() const
	{
		const char *name = describe();
		if (UNEXPECTED(name == NULL)) return zv::Val();
		zv::Val nameZv = zv::Val::string(name, strlen(name));
		return pt_type_new(PT_CLASS_IDENTIFIER_TYPE_NODE, 1, nameZv.raw());
	}

	/* $this when $type->isObject()->yes(), else
	 * $this->scalarLooseCompare($type, $phpVersion) — the aliased trait
	 * method, a private call that is never overridden */
	zv::Val looseCompare(zval *type, zval *phpVersion) const
	{
		zend_long isObject = pt_type_call_trinary(Z_OBJ_P(type), PT_LC("isobject"), 0, NULL);
		if (UNEXPECTED(isObject < 0)) return zv::Val();
		if (isObject == PT_TRI_YES) return thisValue();
		return pt_type_constant_scalar_loose_compare(self, pt_ce_constant_boolean_type, type, phpVersion);
	}

private:
	zend_object *self;

	/* exactly a ConstantBooleanType, none of its methods overridden:
	 * $this-calls can go straight to the C++ methods */
	bool isExact() const { return self->ce == pt_ce_constant_boolean_type; }

	zv::Val thisValue() const { return pt_this_value(self); }

	static zv::Val falsey() { return pt_static_type_factory_falsey(); }
	static zv::Val truthy() { return pt_static_type_factory_truthy(); }
};

} // namespace phpstanturbo

using phpstanturbo::ConstantBooleanType;

bool pt_constant_boolean_type_new(zval *out, bool value)
{
	return pt_val_into(ConstantBooleanType::create(value), out);
}

bool pt_constant_boolean_type_value(zend_object *object, bool &out)
{
	return ConstantBooleanType(object).value(out);
}

/* {{{ engine ABI glue: parameter parsing + registration */

#define PT_THIS ConstantBooleanType(Z_OBJ_P(ZEND_THIS))

/* the getSmallerType() family: one PhpVersion argument, never read */
static void pt_cbt_comparison_type(INTERNAL_FUNCTION_PARAMETERS, zv::Val (ConstantBooleanType::*method)() const)
{
	zval *phpVersion;
	if (!zp::parse<zp::Zval>(execute_data, phpVersion)) RETURN_THROWS();
	PT_RETURN_VAL((PT_THIS.*method)());
}

void pt_register_constant_boolean_type()
{
	reg::Class cls("PHPStan\\Type\\Constant\\ConstantBooleanType");
	ptdecl::ConstantBooleanType::declareClass(cls);
	/* "value" must stay the first declared property (slots::value) */
	ptdecl::ConstantBooleanType::declareProperties(cls);

	cls.method<&ConstantBooleanType::construct, zp::Bool>(sigs::__construct);

	cls.method<&ConstantBooleanType::getValue>(sigs::getValue);

	cls.method(sigs::describe, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *level;
		if (!zp::parse<zp::Obj>(execute_data, level)) RETURN_THROWS();
		const char *described = PT_THIS.describe();
		if (UNEXPECTED(described == NULL)) RETURN_THROWS();
		RETURN_STRING(described);
	});
	cls.op(PT_OP_DESCRIBE, PT_OP_LAMBDA { const char *described = ConstantBooleanType(self).describe(); return described == NULL ? zv::Val() : pt_op_string(described); });

	cls.method(sigs::getSmallerType, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_cbt_comparison_type(INTERNAL_FUNCTION_PARAM_PASSTHRU, &ConstantBooleanType::getSmallerType);
	});

	cls.method(sigs::getSmallerOrEqualType, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_cbt_comparison_type(INTERNAL_FUNCTION_PARAM_PASSTHRU, &ConstantBooleanType::getSmallerOrEqualType);
	});

	cls.method(sigs::getGreaterType, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_cbt_comparison_type(INTERNAL_FUNCTION_PARAM_PASSTHRU, &ConstantBooleanType::getGreaterType);
	});

	cls.method(sigs::getGreaterOrEqualType, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_cbt_comparison_type(INTERNAL_FUNCTION_PARAM_PASSTHRU, &ConstantBooleanType::getGreaterOrEqualType);
	});

	cls.method<&ConstantBooleanType::toBoolean>(sigs::toBoolean);

	cls.method<&ConstantBooleanType::toNumber>(sigs::toNumber);

	cls.method<&ConstantBooleanType::toBitwiseNotType>(sigs::toBitwiseNotType);

	cls.method<&ConstantBooleanType::toAbsoluteNumber>(sigs::toAbsoluteNumber);

	cls.method<&ConstantBooleanType::toString>(sigs::toString);

	cls.method<&ConstantBooleanType::toInteger>(sigs::toInteger);

	cls.method<&ConstantBooleanType::toFloat>(sigs::toFloat);

	cls.method<&ConstantBooleanType::toArrayKey>(sigs::toArrayKey);
	cls.op<PT_OP_TO_ARRAY_KEY, &ConstantBooleanType::toArrayKey>();

	cls.method<&ConstantBooleanType::toCoercedArgumentType, zp::Bool>(sigs::toCoercedArgumentType);

	cls.method(sigs::isTrue, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		PT_RETURN_TRINARY_OR_THROW(PT_THIS.isTrue());
	});

	cls.method(sigs::isFalse, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		PT_RETURN_TRINARY_OR_THROW(PT_THIS.isFalse());
	});

	cls.method(sigs::generalize, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *precision;
		if (!zp::parse<zp::Obj>(execute_data, precision)) RETURN_THROWS();
		PT_RETURN_VAL(ConstantBooleanType::generalize());
	});

	cls.method<&ConstantBooleanType::toPhpDocNode>(sigs::toPhpDocNode);

	cls.method<&ConstantBooleanType::looseCompare, zp::Obj, zp::Zval>(sigs::looseCompare);

	/* `use ConstantScalarTypeTrait { looseCompare as private scalarLooseCompare; }`:
	 * the trait's looseCompare() under its alias, private like the
	 * declaration; the registrar below skips the trait's own looseCompare()
	 * because the class body declares one */
	cls.method("scalarLooseCompare", reg::Private, 2, { reg::obj("type", ptcls::type), reg::obj("phpVersion", ptcls::phpVersion) }, pt_type_trait_constant_scalar_loose_compare, &ptret::booleanType);
	ptdecl::ConstantBooleanType::registerTraits(cls);

	cls.shadow(&pt_ce_constant_boolean_type);
}

/* }}} */
