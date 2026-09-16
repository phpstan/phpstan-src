/*
 * PHPStanTurbo\NativeParameterReflection — native implementation of
 * PHPStan\Reflection\Native\NativeParameterReflection.
 *
 * A value class over the six promoted constructor properties, in the twin's
 * order (OBJ_PROP_NUM slots); the logic lives in the
 * NativeParameterReflection handle class below, mirroring
 * src/Reflection/Native/NativeParameterReflection.php method for method,
 * the registration lambdas at the bottom are only the engine ABI glue. The
 * standard object handlers do GC/free/clone.
 */

#include "support.h"
#include "generated/NativeParameterReflection.h"

namespace slots = ptdecl::NativeParameterReflection::slot;
namespace sigs = ptdecl::NativeParameterReflection::sig;
#include "zv.h"
#include "TypeTraits.h"

zend_class_entry *pt_ce_native_parameter_reflection = NULL;

namespace phpstanturbo {

/* Mirrors PHPStan\Reflection\Native\NativeParameterReflection. State lives
 * in the PHP object's promoted $name, $optional, $type, $passedByReference,
 * $variadic and $defaultValue. */
class NativeParameterReflection
{
public:
	explicit NativeParameterReflection(zend_object *self) : self(self) {}

	/* __construct(private string $name, private bool $optional, private
	 * Type $type, private PassedByReference $passedByReference, private
	 * bool $variadic, private ?Type $defaultValue): the typed slots
	 * ($defaultValue NULL for null; the objects borrowed) */
	void construct(zend_string *name, bool optional, zval *type, zval *passedByReference, bool variadic, zval *defaultValue) const
	{
		zval value;
		ZVAL_STR_COPY(&value, name);
		write(slots::name, &value);
		ZVAL_BOOL(&value, optional);
		write(slots::optional, &value);
		ZVAL_COPY(&value, type);
		write(slots::type, &value);
		ZVAL_COPY(&value, passedByReference);
		write(slots::passedByReference, &value);
		ZVAL_BOOL(&value, variadic);
		write(slots::variadic, &value);
		if (defaultValue == NULL) {
			ZVAL_NULL(&value);
		} else {
			ZVAL_COPY(&value, defaultValue);
		}
		write(slots::defaultValue, &value);
	}

	/* new self(...) — a fresh instance; UNDEF = pending exception */
	static zv::Val create(zend_string *name, bool optional, zval *type, zval *passedByReference, bool variadic, zval *defaultValue)
	{
		zval object;
		if (UNEXPECTED(object_init_ex(&object, pt_ce_native_parameter_reflection) != SUCCESS)) return zv::Val();
		NativeParameterReflection(Z_OBJ(object)).construct(name, optional, type, passedByReference, variadic, defaultValue);
		return zv::Val::adopt(object);
	}

	/* the getters: the slot's value (UNDEF with an Error pending when the
	 * constructor did not run) */
	zv::Val getName() const { return copyOf(slots::name, "name"); }
	zv::Val isOptional() const { return copyOf(slots::optional, "optional"); }
	zv::Val getType() const { return copyOf(slots::type, "type"); }
	zv::Val passedByReference() const { return copyOf(slots::passedByReference, "passedByReference"); }
	zv::Val isVariadic() const { return copyOf(slots::variadic, "variadic"); }
	zv::Val getDefaultValue() const { return copyOf(slots::defaultValue, "defaultValue"); }

	/* toOptional(): $this when already optional, else a copy with
	 * $optional = true; UNDEF = pending exception */
	zv::Val toOptional() const
	{
		zval *optional = slot(slots::optional, "optional");
		if (UNEXPECTED(optional == NULL)) return zv::Val();
		if (zend_is_true(optional)) {
			zval self;
			ZVAL_OBJ_COPY(&self, this->self);
			return zv::Val::adopt(self);
		}
		zval *name = slot(slots::name, "name");
		zval *type = name != NULL ? slot(slots::type, "type") : NULL;
		zval *passedByReference = type != NULL ? slot(slots::passedByReference, "passedByReference") : NULL;
		zval *variadic = passedByReference != NULL ? slot(slots::variadic, "variadic") : NULL;
		zval *defaultValue = variadic != NULL ? slot(slots::defaultValue, "defaultValue") : NULL;
		if (UNEXPECTED(defaultValue == NULL)) return zv::Val();
		return create(Z_STR_P(name), true, type, passedByReference, zend_is_true(variadic), Z_TYPE_P(defaultValue) == IS_NULL ? NULL : defaultValue);
	}

	/* union(self $other): the merged parameter — optional and variadic
	 * only when both are, the union of the types, the combined
	 * by-reference mode, this default value when both are optional;
	 * UNDEF = pending exception */
	zv::Val union_(zend_object *other) const
	{
		NativeParameterReflection that(other);
		zval *name = slot(slots::name, "name");
		if (UNEXPECTED(name == NULL)) return zv::Val();
		/* $this->optional && $other->optional */
		zval *optional = slot(slots::optional, "optional");
		if (UNEXPECTED(optional == NULL)) return zv::Val();
		bool bothOptional = zend_is_true(optional);
		if (bothOptional) {
			zval *otherOptional = that.slot(slots::optional, "optional");
			if (UNEXPECTED(otherOptional == NULL)) return zv::Val();
			bothOptional = zend_is_true(otherOptional);
		}
		/* TypeCombinator::union($this->type, $other->type) */
		zval *type = slot(slots::type, "type");
		if (UNEXPECTED(type == NULL)) return zv::Val();
		zval *otherType = that.slot(slots::type, "type");
		if (UNEXPECTED(otherType == NULL)) return zv::Val();
		zv::Args unionArgs{type, otherType};
		zv::Val unionType = pt_type_combinator_call(PT_LC("union"), 2, unionArgs);
		if (UNEXPECTED(unionType.isUndef())) return zv::Val();
		/* $this->passedByReference->combine($other->passedByReference) */
		zval *passedByReference = slot(slots::passedByReference, "passedByReference");
		if (UNEXPECTED(passedByReference == NULL)) return zv::Val();
		zval *otherPassedByReference = that.slot(slots::passedByReference, "passedByReference");
		if (UNEXPECTED(otherPassedByReference == NULL)) return zv::Val();
		zv::Val combined = pt_passed_by_reference_combine(passedByReference, otherPassedByReference);
		if (UNEXPECTED(combined.isUndef())) return zv::Val();
		/* $this->variadic && $other->variadic */
		zval *variadic = slot(slots::variadic, "variadic");
		if (UNEXPECTED(variadic == NULL)) return zv::Val();
		bool bothVariadic = zend_is_true(variadic);
		if (bothVariadic) {
			zval *otherVariadic = that.slot(slots::variadic, "variadic");
			if (UNEXPECTED(otherVariadic == NULL)) return zv::Val();
			bothVariadic = zend_is_true(otherVariadic);
		}
		/* $this->optional && $other->optional ? $this->defaultValue : null */
		zval *defaultValue = NULL;
		if (bothOptional) {
			zval *otherOptional = that.slot(slots::optional, "optional");
			if (UNEXPECTED(otherOptional == NULL)) return zv::Val();
			defaultValue = slot(slots::defaultValue, "defaultValue");
			if (UNEXPECTED(defaultValue == NULL)) return zv::Val();
			if (Z_TYPE_P(defaultValue) == IS_NULL) {
				defaultValue = NULL;
			}
		}
		return create(Z_STR_P(name), bothOptional, unionType.raw(), combined.raw(), bothVariadic, defaultValue);
	}

private:
	zend_object *self;

	/* a typed slot written by the constructor (owned value moved in, a
	 * previous value released — a constructor may run twice) */
	void write(uint32_t index, zval *value) const
	{
		zval *slot = OBJ_PROP_NUM(self, index);
		zval previous;
		ZVAL_COPY_VALUE(&previous, slot);
		ZVAL_COPY_VALUE(slot, value);
		Z_PROP_FLAG_P(slot) = 0; /* no longer IS_PROP_UNINIT */
		if (Z_TYPE(previous) != IS_UNDEF) {
			zval_ptr_dtor(&previous);
		}
	}

	/* a slot (borrowed); NULL with the engine's Error pending for a read
	 * before the constructor initialized it */
	zval *slot(uint32_t index, const char *propertyName) const
	{
		zval *value = OBJ_PROP_NUM(self, index);
		if (UNEXPECTED(Z_TYPE_P(value) == IS_UNDEF)) {
			zend_throw_error(NULL, "Typed property %s::$%s must not be accessed before initialization", ZSTR_VAL(pt_ce_native_parameter_reflection->name), propertyName);
			return NULL;
		}
		return value;
	}

	zv::Val copyOf(uint32_t index, const char *propertyName) const
	{
		zval *value = slot(index, propertyName);
		return value != NULL ? zv::Val::copyOf(zv::Ref(value)) : zv::Val();
	}
};

} // namespace phpstanturbo

using phpstanturbo::NativeParameterReflection;

/* {{{ exported helpers: the shadowing class for native callers */

/* new NativeParameterReflection(...$argv) over values as PHP code hands
 * them (the arguments borrowed): directly when they already have the parameter
 * types, through the constructor's parameter parsing (its coercions and
 * TypeErrors) otherwise; UNDEF = pending exception */
zv::Val pt_native_parameter_reflection_new(uint32_t argc, zval *argv)
{
	if (UNEXPECTED(argc != 6)) return pt_type_new_ce(pt_ce_native_parameter_reflection, argc, argv);
	zval *name = &argv[0], *optional = &argv[1], *type = &argv[2], *passedByReference = &argv[3], *variadic = &argv[4], *defaultValue = &argv[5];
	if (EXPECTED(Z_TYPE_P(name) == IS_STRING && (Z_TYPE_P(optional) == IS_TRUE || Z_TYPE_P(optional) == IS_FALSE) && Z_TYPE_P(type) == IS_OBJECT && Z_TYPE_P(passedByReference) == IS_OBJECT && (Z_TYPE_P(variadic) == IS_TRUE || Z_TYPE_P(variadic) == IS_FALSE) && (Z_TYPE_P(defaultValue) == IS_OBJECT || Z_TYPE_P(defaultValue) == IS_NULL))) {
		return NativeParameterReflection::create(Z_STR_P(name), Z_TYPE_P(optional) == IS_TRUE, type, passedByReference, Z_TYPE_P(variadic) == IS_TRUE, Z_TYPE_P(defaultValue) == IS_NULL ? NULL : defaultValue);
	}
	return pt_type_new_ce(pt_ce_native_parameter_reflection, argc, argv);
}

/* }}} */

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

#define PT_THIS NativeParameterReflection(Z_OBJ_P(ZEND_THIS))

void pt_register_native_parameter_reflection()
{

	reg::Class cls("PHPStan\\Reflection\\Native\\NativeParameterReflection");
	ptdecl::NativeParameterReflection::declareClass(cls);
	/* the twin's promoted slots in its order (OBJ_PROP_NUM), typed,
	 * uninitialized until the constructor runs */
	ptdecl::NativeParameterReflection::declareProperties(cls);

	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zend_string *name;
		bool optional, variadic;
		zval *type, *passedByReference, *defaultValue;
		if (!zp::parse<zp::Str, zp::Bool, zp::Obj, zp::Obj, zp::Bool, zp::ObjOrNull>(execute_data, name, optional, type, passedByReference, variadic, defaultValue)) RETURN_THROWS();
		PT_THIS.construct(name, optional, type, passedByReference, variadic, defaultValue);
	});

	cls.method<&NativeParameterReflection::getName>(sigs::getName);

	cls.method<&NativeParameterReflection::isOptional>(sigs::isOptional);

	cls.method<&NativeParameterReflection::getType>(sigs::getType);

	cls.method<&NativeParameterReflection::passedByReference>(sigs::passedByReference);

	cls.method<&NativeParameterReflection::isVariadic>(sigs::isVariadic);

	cls.method<&NativeParameterReflection::getDefaultValue>(sigs::getDefaultValue);

	cls.method<&NativeParameterReflection::toOptional>(sigs::toOptional);

	cls.method(sigs::union_, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *other;
		ZEND_PARSE_PARAMETERS_START(1, 1)
			Z_PARAM_OBJECT_OF_CLASS(other, pt_ce_native_parameter_reflection)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(PT_THIS.union_(Z_OBJ_P(other)));
	});

	cls.shadow(&pt_ce_native_parameter_reflection);
}

/* }}} */
