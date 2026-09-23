/*
 * PHPStanTurbo\TypeSpecifierContext — native implementation of
 * PHPStan\Analyser\TypeSpecifierContext.
 *
 * When the extension is active, PHPStan\Analyser\TypeSpecifierContext is
 * this class, declared under that name at activation (final, like the
 * twin). The singletons live where the twin keeps them — in the class's
 * private static $registry, keyed by value ('' for the null context) — so
 * one process shares one instance per context exactly as the twin does,
 * and native callers (MutatingScope, ExpressionResult, the handlers) reach
 * them and the context queries through the pt_type_specifier_context_*
 * direct entries without an engine frame.
 */

#include "support.h"
#include "generated/TypeSpecifierContext.h"

namespace slots = ptdecl::TypeSpecifierContext::slot;
namespace sigs = ptdecl::TypeSpecifierContext::sig;
#include "zv.h"
#include "TypeTraits.h"

zend_class_entry *pt_ce_type_specifier_context = NULL;

/* the twin's `private static array $registry` slot (borrowed; resolved once
 * per activated class, as TemplateTypeVariance.cpp resolves its registry) */
static zend_class_entry *pt_tsc_registry_ce = nullptr;
static zval *pt_tsc_registry_slot = nullptr;

static zval *pt_tsc_registry()
{
	zend_class_entry *ce = pt_ce_type_specifier_context;
	if (UNEXPECTED(pt_tsc_registry_ce != ce)) {
		ZEND_ASSERT(ce != NULL);
		if (CE_STATIC_MEMBERS(ce) == NULL) {
			zend_class_init_statics(ce);
		}
		zend_property_info *info = (zend_property_info *) zend_hash_str_find_ptr(&ce->properties_info, PT_LC("registry"));
		ZEND_ASSERT(info != NULL && (info->flags & ZEND_ACC_STATIC) != 0);
		pt_tsc_registry_slot = CE_STATIC_MEMBERS(ce) + info->offset;
		pt_tsc_registry_ce = ce;
	}
	return pt_tsc_registry_slot;
}

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\TypeSpecifierContext. State lives in the PHP
 * object's $value: an int, or null for the null context (PT_TSC_NULL
 * below). */
class TypeSpecifierContext
{
public:
	static constexpr zend_long CONTEXT_TRUE = PT_TSC_CONTEXT_TRUE;
	static constexpr zend_long CONTEXT_TRUTHY_BUT_NOT_TRUE = PT_TSC_CONTEXT_TRUTHY_BUT_NOT_TRUE;
	static constexpr zend_long CONTEXT_TRUTHY = PT_TSC_CONTEXT_TRUTHY;
	static constexpr zend_long CONTEXT_FALSE = PT_TSC_CONTEXT_FALSE;
	static constexpr zend_long CONTEXT_FALSEY_BUT_NOT_FALSE = PT_TSC_CONTEXT_FALSEY_BUT_NOT_FALSE;
	static constexpr zend_long CONTEXT_FALSEY = PT_TSC_CONTEXT_FALSEY;
	static constexpr zend_long CONTEXT_BITMASK = PT_TSC_CONTEXT_BITMASK;

	explicit TypeSpecifierContext(zend_object *self) : self(self) {}

	/* $this->value: the int, PT_TSC_NULL for null; PT_TSC_UNINITIALIZED
	 * with the engine's Error pending when the property was never written
	 * (an instance made without its constructor) */
	[[nodiscard]] zend_long value() const
	{
		zval *slot = OBJ_PROP_NUM(self, slots::value);
		if (EXPECTED(Z_TYPE_P(slot) == IS_LONG)) return Z_LVAL_P(slot);
		if (EXPECTED(Z_TYPE_P(slot) == IS_NULL)) return PT_TSC_NULL;
		zend_throw_error(NULL, "Typed property %s::$value must not be accessed before initialization", ZSTR_VAL(self->ce->name));
		return PT_TSC_UNINITIALIZED;
	}

	/* private function __construct(private ?int $value) */
	void construct(zend_long value) { writeValue(self, value); }

	/* self::create($value): self::$registry[$value ?? ''] ??= new self($value)
	 * — the singleton, borrowed from the registry; NULL = pending exception */
	[[nodiscard]] static zend_object *create(zend_long value)
	{
		if (UNEXPECTED(pt_ce_type_specifier_context == NULL)) {
			zend_throw_error(NULL, "phpstan_turbo: TypeSpecifierContext used before the shadowing classes were activated");
			return NULL;
		}
		zval *registry = pt_tsc_registry();
		ZVAL_DEREF(registry);
		if (UNEXPECTED(Z_TYPE_P(registry) != IS_ARRAY)) {
			/* the uninitialized typed static array a dim write initializes */
			array_init(registry);
		}
		zval *entry = value == PT_TSC_NULL
			? zend_hash_find(Z_ARRVAL_P(registry), ZSTR_EMPTY_ALLOC())
			: zend_hash_index_find(Z_ARRVAL_P(registry), (zend_ulong) value);
		if (EXPECTED(entry != NULL)) {
			ZVAL_DEREF(entry);
			if (EXPECTED(Z_TYPE_P(entry) == IS_OBJECT)) return Z_OBJ_P(entry);
		}

		zval object;
		if (UNEXPECTED(object_init_ex(&object, pt_ce_type_specifier_context) != SUCCESS)) return NULL;
		writeValue(Z_OBJ(object), value);
		SEPARATE_ARRAY(registry);
		zval *stored = value == PT_TSC_NULL
			? zend_hash_update(Z_ARRVAL_P(registry), ZSTR_EMPTY_ALLOC(), &object)
			: zend_hash_index_update(Z_ARRVAL_P(registry), (zend_ulong) value, &object);
		return Z_OBJ_P(stored);
	}

	static zend_object *createTrue() { return create(CONTEXT_TRUE); }
	static zend_object *createTruthy() { return create(CONTEXT_TRUTHY); }
	static zend_object *createFalse() { return create(CONTEXT_FALSE); }
	static zend_object *createFalsey() { return create(CONTEXT_FALSEY); }
	static zend_object *createNull() { return create(PT_TSC_NULL); }

	/* NULL = pending exception */
	[[nodiscard]] zend_object *negate() const
	{
		zend_long v = value();
		if (UNEXPECTED(v == PT_TSC_UNINITIALIZED)) return NULL;
		if (v == PT_TSC_NULL) {
			pt_throw_should_not_happen();
			return NULL;
		}
		return create(~v & CONTEXT_BITMASK);
	}

	/* the queries; false = pending exception */
	[[nodiscard]] bool true_(bool &out) const { return is(CONTEXT_TRUE, out); }
	[[nodiscard]] bool truthy(bool &out) const { return is(CONTEXT_TRUTHY, out); }
	[[nodiscard]] bool false_(bool &out) const { return is(CONTEXT_FALSE, out); }
	[[nodiscard]] bool falsey(bool &out) const { return is(CONTEXT_FALSEY, out); }
	[[nodiscard]] bool falseyButNotFalse(bool &out) const { return is(CONTEXT_FALSEY_BUT_NOT_FALSE, out); }

	[[nodiscard]] bool null_(bool &out) const
	{
		zend_long v = value();
		if (UNEXPECTED(v == PT_TSC_UNINITIALIZED)) return false;
		out = v == PT_TSC_NULL;
		return true;
	}

private:
	zend_object *self;

	/* $this->value !== null && (bool) ($this->value & $mask) */
	[[nodiscard]] bool is(zend_long mask, bool &out) const
	{
		zend_long v = value();
		if (UNEXPECTED(v == PT_TSC_UNINITIALIZED)) return false;
		out = v != PT_TSC_NULL && (v & mask) != 0;
		return true;
	}

	static void writeValue(zend_object *object, zend_long value)
	{
		zval *slot = OBJ_PROP_NUM(object, slots::value);
		if (value == PT_TSC_NULL) {
			ZVAL_NULL(slot);
		} else {
			ZVAL_LONG(slot, value);
		}
	}
};

} // namespace phpstanturbo

using phpstanturbo::TypeSpecifierContext;

/* {{{ direct entries (support.h) */

zend_object *pt_type_specifier_context_create_true() { return TypeSpecifierContext::createTrue(); }
zend_object *pt_type_specifier_context_create_truthy() { return TypeSpecifierContext::createTruthy(); }
zend_object *pt_type_specifier_context_create_false() { return TypeSpecifierContext::createFalse(); }
zend_object *pt_type_specifier_context_create_falsey() { return TypeSpecifierContext::createFalsey(); }
zend_object *pt_type_specifier_context_create_null() { return TypeSpecifierContext::createNull(); }

/* a query on any context object: the slot of a native context, the method
 * of anything else (the PHP twin declared next to the native class in the
 * differential tests) */
static bool pt_tsc_query(zend_object *context, bool (TypeSpecifierContext::*query)(bool &) const, const char *lcname, size_t len, bool &out)
{
	if (EXPECTED(context->ce == pt_ce_type_specifier_context)) return (TypeSpecifierContext(context).*query)(out);
	zv::Val result = pt_type_call(context, lcname, len, 0, NULL);
	if (UNEXPECTED(result.isUndef())) return false;
	out = Z_TYPE_P(result.raw()) == IS_TRUE;
	return true;
}

bool pt_type_specifier_context_true(zend_object *context, bool &out) { return pt_tsc_query(context, &TypeSpecifierContext::true_, PT_LC("true"), out); }
bool pt_type_specifier_context_truthy(zend_object *context, bool &out) { return pt_tsc_query(context, &TypeSpecifierContext::truthy, PT_LC("truthy"), out); }
bool pt_type_specifier_context_false(zend_object *context, bool &out) { return pt_tsc_query(context, &TypeSpecifierContext::false_, PT_LC("false"), out); }
bool pt_type_specifier_context_falsey(zend_object *context, bool &out) { return pt_tsc_query(context, &TypeSpecifierContext::falsey, PT_LC("falsey"), out); }
bool pt_type_specifier_context_falsey_but_not_false(zend_object *context, bool &out) { return pt_tsc_query(context, &TypeSpecifierContext::falseyButNotFalse, PT_LC("falseybutnotfalse"), out); }
bool pt_type_specifier_context_null(zend_object *context, bool &out) { return pt_tsc_query(context, &TypeSpecifierContext::null_, PT_LC("null"), out); }

zv::Val pt_type_specifier_context_negate(zend_object *context)
{
	if (EXPECTED(context->ce == pt_ce_type_specifier_context)) {
		zend_object *negated = TypeSpecifierContext(context).negate();
		if (UNEXPECTED(negated == NULL)) return zv::Val();
		GC_ADDREF(negated);
		zval result;
		ZVAL_OBJ(&result, negated);
		return zv::Val::adopt(result);
	}
	return pt_type_call(context, PT_LC("negate"), 0, NULL);
}

/* }}} */

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

#define PT_TSC_THIS TypeSpecifierContext(Z_OBJ_P(ZEND_THIS))

/* a borrowed singleton into the return value */
static void pt_tsc_return_object(zval *return_value, zend_object *context)
{
	if (UNEXPECTED(context == NULL)) RETURN_THROWS();
	RETURN_OBJ_COPY(context);
}

void pt_register_type_specifier_context()
{
	reg::Class cls("PHPStan\\Analyser\\TypeSpecifierContext");
	ptdecl::TypeSpecifierContext::declareClass(cls);
	cls.classConstantLong("CONTEXT_TRUE", TypeSpecifierContext::CONTEXT_TRUE);
	cls.classConstantLong("CONTEXT_TRUTHY_BUT_NOT_TRUE", TypeSpecifierContext::CONTEXT_TRUTHY_BUT_NOT_TRUE);
	cls.classConstantLong("CONTEXT_TRUTHY", TypeSpecifierContext::CONTEXT_TRUTHY);
	cls.classConstantLong("CONTEXT_FALSE", TypeSpecifierContext::CONTEXT_FALSE);
	cls.classConstantLong("CONTEXT_FALSEY_BUT_NOT_FALSE", TypeSpecifierContext::CONTEXT_FALSEY_BUT_NOT_FALSE);
	cls.classConstantLong("CONTEXT_FALSEY", TypeSpecifierContext::CONTEXT_FALSEY);
	cls.classConstantLong("CONTEXT_BITMASK", TypeSpecifierContext::CONTEXT_BITMASK);
	ptdecl::TypeSpecifierContext::declareProperties(cls);

	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zend_long value;
		bool isNull;
		ZEND_PARSE_PARAMETERS_START(1, 1)
			Z_PARAM_LONG_OR_NULL(value, isNull)
		ZEND_PARSE_PARAMETERS_END();
		PT_TSC_THIS.construct(isNull ? PT_TSC_NULL : value);
	});

	cls.method(sigs::create, [](INTERNAL_FUNCTION_PARAMETERS) {
		zend_long value;
		bool isNull;
		ZEND_PARSE_PARAMETERS_START(1, 1)
			Z_PARAM_LONG_OR_NULL(value, isNull)
		ZEND_PARSE_PARAMETERS_END();
		pt_tsc_return_object(return_value, TypeSpecifierContext::create(isNull ? PT_TSC_NULL : value));
	});

	cls.method(sigs::createTrue, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		pt_tsc_return_object(return_value, TypeSpecifierContext::createTrue());
	});

	cls.method(sigs::createTruthy, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		pt_tsc_return_object(return_value, TypeSpecifierContext::createTruthy());
	});

	cls.method(sigs::createFalse, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		pt_tsc_return_object(return_value, TypeSpecifierContext::createFalse());
	});

	cls.method(sigs::createFalsey, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		pt_tsc_return_object(return_value, TypeSpecifierContext::createFalsey());
	});

	cls.method(sigs::createNull, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		pt_tsc_return_object(return_value, TypeSpecifierContext::createNull());
	});

	cls.method(sigs::negate, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		pt_tsc_return_object(return_value, PT_TSC_THIS.negate());
	});

	cls.method<&TypeSpecifierContext::true_>(sigs::true_);
	cls.method<&TypeSpecifierContext::truthy>(sigs::truthy);
	cls.method<&TypeSpecifierContext::false_>(sigs::false_);
	cls.method<&TypeSpecifierContext::falsey>(sigs::falsey);
	cls.method<&TypeSpecifierContext::falseyButNotFalse>(sigs::falseyButNotFalse);
	cls.method<&TypeSpecifierContext::null_>(sigs::null);

	cls.shadow(&pt_ce_type_specifier_context);
}

/* }}} */
