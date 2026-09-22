/*
 * PHPStanTurbo\PassedByReference — native implementation of
 * PHPStan\Reflection\PassedByReference.
 *
 * The three modes are singletons held where the twin keeps them — in the
 * class's private static $registry, keyed by the mode — so a process shares
 * one instance per mode exactly as the twin does. Native callers (the
 * parameter reflections, ArgumentsHandler) create them and read the mode
 * through the pt_passed_by_reference_* direct entries without an engine
 * frame.
 */

#include "support.h"
#include "generated/PassedByReference.h"

namespace slots = ptdecl::PassedByReference::slot;
namespace sigs = ptdecl::PassedByReference::sig;
#include "zv.h"
#include "TypeTraits.h"

zend_class_entry *pt_ce_passed_by_reference = NULL;

/* the twin's `private static array $registry` slot (borrowed; resolved once
 * per activated class) */
static zend_class_entry *pt_pbr_registry_ce = nullptr;
static zval *pt_pbr_registry_slot = nullptr;

static zval *pt_pbr_registry()
{
	zend_class_entry *ce = pt_ce_passed_by_reference;
	if (UNEXPECTED(pt_pbr_registry_ce != ce)) {
		ZEND_ASSERT(ce != NULL);
		if (CE_STATIC_MEMBERS(ce) == NULL) {
			zend_class_init_statics(ce);
		}
		zend_property_info *info = (zend_property_info *) zend_hash_str_find_ptr(&ce->properties_info, PT_LC("registry"));
		ZEND_ASSERT(info != NULL && (info->flags & ZEND_ACC_STATIC) != 0);
		pt_pbr_registry_slot = CE_STATIC_MEMBERS(ce) + info->offset;
		pt_pbr_registry_ce = ce;
	}
	return pt_pbr_registry_slot;
}

namespace phpstanturbo {

/* Mirrors PHPStan\Reflection\PassedByReference. State lives in the PHP
 * object's $value. */
class PassedByReference
{
public:
	static constexpr zend_long NO = PT_PASSED_BY_REFERENCE_NO;
	static constexpr zend_long READS_ARGUMENT = PT_PASSED_BY_REFERENCE_READS_ARGUMENT;
	static constexpr zend_long CREATES_NEW_VARIABLE = PT_PASSED_BY_REFERENCE_CREATES_NEW_VARIABLE;

	explicit PassedByReference(zend_object *self) : self(self) {}

	/* $this->value; -1 with the engine's Error pending when the property was
	 * never written (an instance made without its constructor) */
	[[nodiscard]] zend_long value() const { return valueOf(self); }

	[[nodiscard]] static zend_long valueOf(zend_object *object)
	{
		zval *slot = OBJ_PROP_NUM(object, slots::value);
		if (EXPECTED(Z_TYPE_P(slot) == IS_LONG)) return Z_LVAL_P(slot);
		zend_throw_error(NULL, "Typed property %s::$value must not be accessed before initialization", ZSTR_VAL(object->ce->name));
		return -1;
	}

	/* private function __construct(private int $value) */
	void construct(zend_long value) const { writeValue(self, value); }

	/* self::create($value): array_key_exists($value, self::$registry) ?
	 * self::$registry[$value] : (self::$registry[$value] = new self($value))
	 * — the singleton, borrowed from the registry; NULL = pending exception */
	[[nodiscard]] static zend_object *create(zend_long value)
	{
		if (UNEXPECTED(pt_ce_passed_by_reference == NULL)) {
			zend_throw_error(NULL, "phpstan_turbo: PassedByReference used before the shadowing classes were activated");
			return NULL;
		}
		zval *registry = pt_pbr_registry();
		ZVAL_DEREF(registry);
		if (UNEXPECTED(Z_TYPE_P(registry) != IS_ARRAY)) {
			/* the uninitialized typed static array a dim write initializes */
			array_init(registry);
		}
		zval *entry = zend_hash_index_find(Z_ARRVAL_P(registry), (zend_ulong) value);
		if (EXPECTED(entry != NULL)) {
			ZVAL_DEREF(entry);
			if (EXPECTED(Z_TYPE_P(entry) == IS_OBJECT)) return Z_OBJ_P(entry);
			zend_type_error("PHPStan\\Reflection\\PassedByReference::create(): Return value must be of type PHPStan\\Reflection\\PassedByReference, %s returned", zend_zval_value_name(entry));
			return NULL;
		}

		zval object;
		if (UNEXPECTED(object_init_ex(&object, pt_ce_passed_by_reference) != SUCCESS)) return NULL;
		writeValue(Z_OBJ(object), value);
		SEPARATE_ARRAY(registry);
		zval *stored = zend_hash_index_update(Z_ARRVAL_P(registry), (zend_ulong) value, &object);
		return Z_OBJ_P(stored);
	}

	/* the queries; false = pending exception */
	[[nodiscard]] bool no(bool &out) const { return is(NO, out); }

	[[nodiscard]] bool yes(bool &out) const
	{
		if (UNEXPECTED(!no(out))) return false;
		out = !out;
		return true;
	}

	[[nodiscard]] bool createsNewVariable(bool &out) const { return is(CREATES_NEW_VARIABLE, out); }

	/* $this->value === $other->value */
	[[nodiscard]] bool equals(zend_object *other, bool &out) const
	{
		zend_long v = value();
		if (UNEXPECTED(v < 0)) return false;
		zend_long otherValue = valueOf(other);
		if (UNEXPECTED(otherValue < 0)) return false;
		out = v == otherValue;
		return true;
	}

	/* CreatesNewVariable > ReadsArgument > No — the stronger of the two
	 * (this one on a tie), borrowed; NULL = pending exception */
	[[nodiscard]] zend_object *combine(zend_object *other) const
	{
		zend_long v = value();
		if (UNEXPECTED(v < 0)) return NULL;
		zend_long otherValue = valueOf(other);
		if (UNEXPECTED(otherValue < 0)) return NULL;
		if (v > otherValue) return self;
		if (v < otherValue) return other;
		return self;
	}

private:
	zend_object *self;

	[[nodiscard]] bool is(zend_long mode, bool &out) const
	{
		zend_long v = value();
		if (UNEXPECTED(v < 0)) return false;
		out = v == mode;
		return true;
	}

	static void writeValue(zend_object *object, zend_long value)
	{
		zval *slot = OBJ_PROP_NUM(object, slots::value);
		ZVAL_LONG(slot, value);
		Z_PROP_FLAG_P(slot) = 0; /* no longer IS_PROP_UNINIT */
	}
};

} // namespace phpstanturbo

using phpstanturbo::PassedByReference;

/* {{{ direct entries (support.h) */

zend_object *pt_passed_by_reference_create_no() { return PassedByReference::create(PassedByReference::NO); }
zend_object *pt_passed_by_reference_create_reads_argument() { return PassedByReference::create(PassedByReference::READS_ARGUMENT); }
zend_object *pt_passed_by_reference_create_creates_new_variable() { return PassedByReference::create(PassedByReference::CREATES_NEW_VARIABLE); }

zend_long pt_passed_by_reference_mode(zval *passedByReference)
{
	if (EXPECTED(Z_TYPE_P(passedByReference) == IS_OBJECT && Z_OBJCE_P(passedByReference) == pt_ce_passed_by_reference)) return PassedByReference(Z_OBJ_P(passedByReference)).value();
	if (UNEXPECTED(Z_TYPE_P(passedByReference) != IS_OBJECT)) {
		zend_throw_error(NULL, "Call to a member function no() on %s", zend_zval_value_name(passedByReference));
		return -1;
	}
	/* anything else (the PHP twin declared next to the native class in the
	 * differential tests): the mode out of its queries */
	zv::Val no = pt_type_call(Z_OBJ_P(passedByReference), PT_LC("no"), 0, NULL);
	if (UNEXPECTED(no.isUndef())) return -1;
	if (zend_is_true(no.raw())) return PT_PASSED_BY_REFERENCE_NO;
	zv::Val createsNewVariable = pt_type_call(Z_OBJ_P(passedByReference), PT_LC("createsnewvariable"), 0, NULL);
	if (UNEXPECTED(createsNewVariable.isUndef())) return -1;
	return zend_is_true(createsNewVariable.raw()) ? PT_PASSED_BY_REFERENCE_CREATES_NEW_VARIABLE : PT_PASSED_BY_REFERENCE_READS_ARGUMENT;
}

zv::Val pt_passed_by_reference_combine(zval *passedByReference, zval *other)
{
	if (EXPECTED(Z_TYPE_P(passedByReference) == IS_OBJECT && Z_OBJCE_P(passedByReference) == pt_ce_passed_by_reference && Z_TYPE_P(other) == IS_OBJECT && Z_OBJCE_P(other) == pt_ce_passed_by_reference)) {
		zend_object *combined = PassedByReference(Z_OBJ_P(passedByReference)).combine(Z_OBJ_P(other));
		if (UNEXPECTED(combined == NULL)) return zv::Val();
		zval result;
		ZVAL_OBJ_COPY(&result, combined);
		return zv::Val::adopt(result);
	}
	if (UNEXPECTED(Z_TYPE_P(passedByReference) != IS_OBJECT)) {
		zend_throw_error(NULL, "Call to a member function combine() on %s", zend_zval_value_name(passedByReference));
		return zv::Val();
	}
	return pt_type_call(Z_OBJ_P(passedByReference), PT_LC("combine"), 1, other);
}

/* }}} */

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

#define PT_PBR_THIS PassedByReference(Z_OBJ_P(ZEND_THIS))

/* a borrowed singleton into the return value */
static void pt_pbr_return_object(zval *return_value, zend_object *object)
{
	if (UNEXPECTED(object == NULL)) RETURN_THROWS();
	RETURN_OBJ_COPY(object);
}

/* the `self $other` parameter */
#define PT_PBR_PARSE_OTHER(var) \
	zval *var; \
	ZEND_PARSE_PARAMETERS_START(1, 1) \
		Z_PARAM_OBJECT_OF_CLASS(var, pt_ce_passed_by_reference) \
	ZEND_PARSE_PARAMETERS_END()

void pt_register_passed_by_reference()
{
	reg::Class cls("PHPStan\\Reflection\\PassedByReference");
	ptdecl::PassedByReference::declareClass(cls);
	cls.privateClassConstantLong("NO", PassedByReference::NO);
	cls.privateClassConstantLong("READS_ARGUMENT", PassedByReference::READS_ARGUMENT);
	cls.privateClassConstantLong("CREATES_NEW_VARIABLE", PassedByReference::CREATES_NEW_VARIABLE);
	ptdecl::PassedByReference::declareProperties(cls);

	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zend_long value;
		ZEND_PARSE_PARAMETERS_START(1, 1)
			Z_PARAM_LONG(value)
		ZEND_PARSE_PARAMETERS_END();
		PT_PBR_THIS.construct(value);
	});

	cls.method(sigs::create, [](INTERNAL_FUNCTION_PARAMETERS) {
		zend_long value;
		if (!zp::parse<zp::Long>(execute_data, value)) RETURN_THROWS();
		pt_pbr_return_object(return_value, PassedByReference::create(value));
	});

	cls.method(sigs::createNo, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		pt_pbr_return_object(return_value, PassedByReference::create(PassedByReference::NO));
	});

	cls.method(sigs::createCreatesNewVariable, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		pt_pbr_return_object(return_value, PassedByReference::create(PassedByReference::CREATES_NEW_VARIABLE));
	});

	cls.method(sigs::createReadsArgument, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		pt_pbr_return_object(return_value, PassedByReference::create(PassedByReference::READS_ARGUMENT));
	});

	cls.method<&PassedByReference::no>(sigs::no);
	cls.method<&PassedByReference::yes>(sigs::yes);

	cls.method(sigs::equals, [](INTERNAL_FUNCTION_PARAMETERS) {
		PT_PBR_PARSE_OTHER(other);
		bool out = false;
		if (UNEXPECTED(!PT_PBR_THIS.equals(Z_OBJ_P(other), out))) RETURN_THROWS();
		RETURN_BOOL(out);
	});

	cls.method<&PassedByReference::createsNewVariable>(sigs::createsNewVariable);

	cls.method(sigs::combine, [](INTERNAL_FUNCTION_PARAMETERS) {
		PT_PBR_PARSE_OTHER(other);
		pt_pbr_return_object(return_value, PT_PBR_THIS.combine(Z_OBJ_P(other)));
	});

	cls.shadow(&pt_ce_passed_by_reference);
}

/* }}} */
