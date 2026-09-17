/*
 * PHPStanTurbo\DummyParameter — native implementation of
 * PHPStan\Reflection\Php\DummyParameter.
 *
 * A non-final value class over its six private slots in the twin's order
 * (the declared $passedByReference first, then the promoted ones); the
 * getters read `$this`'s own slot, which is the same slot for any subclass
 * (ExtendedDummyParameter.cpp, or a PHP subclass). The constructor body is
 * exported for ExtendedDummyParameter's parent::__construct() and for native
 * callers creating instances; the slot readers native callers use for an
 * instance of exactly this class or ExtendedDummyParameter are in
 * ParameterValues.h.
 */

#include "support.h"
#include "generated/DummyParameter.h"

namespace slots = ptdecl::DummyParameter::slot;
namespace sigs = ptdecl::DummyParameter::sig;
#include "zv.h"
#include "TypeTraits.h"

zend_class_entry *pt_ce_dummy_parameter = NULL;

namespace phpstanturbo {

/* Mirrors PHPStan\Reflection\Php\DummyParameter. */
class DummyParameter
{
public:
	explicit DummyParameter(zend_object *self) : self(self) {}

	/* __construct(private string $name, private Type $type, private bool
	 * $optional, ?PassedByReference $passedByReference, private bool
	 * $variadic, private ?Type $defaultValue): the promoted slots in
	 * parameter order, then $this->passedByReference = $passedByReference ??
	 * PassedByReference::createNo() ($passedByReference / $defaultValue NULL
	 * for null); false = pending exception */
	[[nodiscard]] bool construct(zend_string *name, zval *type, bool optional, zval *passedByReference, bool variadic, zval *defaultValue) const
	{
		zval value;
		ZVAL_STR_COPY(&value, name);
		write(slots::name, &value);
		ZVAL_COPY(&value, type);
		write(slots::type, &value);
		ZVAL_BOOL(&value, optional);
		write(slots::optional, &value);
		ZVAL_BOOL(&value, variadic);
		write(slots::variadic, &value);
		if (defaultValue == NULL) {
			ZVAL_NULL(&value);
		} else {
			ZVAL_COPY(&value, defaultValue);
		}
		write(slots::defaultValue, &value);
		if (passedByReference != NULL) {
			ZVAL_COPY(&value, passedByReference);
		} else {
			zend_object *no = pt_passed_by_reference_create_no();
			if (UNEXPECTED(no == NULL)) return false;
			ZVAL_OBJ_COPY(&value, no);
		}
		write(slots::passedByReference, &value);
		return true;
	}

	zv::Val getName() const { return copyOf(slots::name, "name"); }
	zv::Val isOptional() const { return copyOf(slots::optional, "optional"); }
	zv::Val getType() const { return copyOf(slots::type, "type"); }
	zv::Val passedByReference() const { return copyOf(slots::passedByReference, "passedByReference"); }
	zv::Val isVariadic() const { return copyOf(slots::variadic, "variadic"); }
	zv::Val getDefaultValue() const { return copyOf(slots::defaultValue, "defaultValue"); }

private:
	zend_object *self;

	/* a typed slot written by the constructor (a previous value released —
	 * a constructor may run twice) */
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

	/* the slot's value; UNDEF with the engine's Error pending before the
	 * constructor initialized it (the property belongs to DummyParameter
	 * whatever the object's class) */
	zv::Val copyOf(uint32_t index, const char *propertyName) const
	{
		zval *value = OBJ_PROP_NUM(self, index);
		if (UNEXPECTED(Z_TYPE_P(value) == IS_UNDEF)) {
			zend_throw_error(NULL, "Typed property PHPStan\\Reflection\\Php\\DummyParameter::$%s must not be accessed before initialization", propertyName);
			return zv::Val();
		}
		return zv::Val::copyOf(zv::Ref(value));
	}
};

} // namespace phpstanturbo

using phpstanturbo::DummyParameter;

/* {{{ direct entries (support.h) */

bool pt_dummy_parameter_construct(zend_object *object, zend_string *name, zval *type, bool optional, zval *passedByReference, bool variadic, zval *defaultValue)
{
	return DummyParameter(object).construct(name, type, optional, passedByReference, variadic, defaultValue);
}

zv::Val pt_dummy_parameter_new(zend_string *name, zval *type, bool optional, zval *passedByReference, bool variadic, zval *defaultValue)
{
	zval object;
	if (UNEXPECTED(object_init_ex(&object, pt_ce_dummy_parameter) != SUCCESS)) return zv::Val();
	zv::Val result = zv::Val::adopt(object);
	if (UNEXPECTED(!DummyParameter(Z_OBJ_P(result.raw())).construct(name, type, optional, passedByReference, variadic, defaultValue))) return zv::Val();
	return result;
}

/* }}} */

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

#define PT_DP_THIS DummyParameter(Z_OBJ_P(ZEND_THIS))

void pt_register_dummy_parameter()
{
	reg::Class cls("PHPStan\\Reflection\\Php\\DummyParameter");
	ptdecl::DummyParameter::declareClass(cls);
	ptdecl::DummyParameter::declareProperties(cls);

	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zend_string *name;
		zval *type, *passedByReference = NULL, *defaultValue = NULL;
		bool optional, variadic;
		ZEND_PARSE_PARAMETERS_START(6, 6)
			Z_PARAM_STR(name)
			Z_PARAM_OBJECT(type)
			Z_PARAM_BOOL(optional)
			Z_PARAM_OBJECT_OR_NULL(passedByReference)
			Z_PARAM_BOOL(variadic)
			Z_PARAM_OBJECT_OR_NULL(defaultValue)
		ZEND_PARSE_PARAMETERS_END();
		if (UNEXPECTED(!PT_DP_THIS.construct(name, type, optional, passedByReference, variadic, defaultValue))) RETURN_THROWS();
	});

	cls.method<&DummyParameter::getName>(sigs::getName);
	cls.op<PT_OP_GET_NAME, &DummyParameter::getName>();
	cls.method<&DummyParameter::isOptional>(sigs::isOptional);
	cls.op<PT_OP_IS_OPTIONAL, &DummyParameter::isOptional>();
	cls.method<&DummyParameter::getType>(sigs::getType);
	cls.op<PT_OP_GET_TYPE, &DummyParameter::getType>();
	cls.method<&DummyParameter::passedByReference>(sigs::passedByReference);
	cls.op<PT_OP_PASSED_BY_REFERENCE, &DummyParameter::passedByReference>();
	cls.method<&DummyParameter::isVariadic>(sigs::isVariadic);
	cls.op<PT_OP_IS_VARIADIC, &DummyParameter::isVariadic>();
	cls.method<&DummyParameter::getDefaultValue>(sigs::getDefaultValue);
	cls.op<PT_OP_GET_DEFAULT_VALUE, &DummyParameter::getDefaultValue>();

	cls.shadow(&pt_ce_dummy_parameter);
}

/* }}} */
