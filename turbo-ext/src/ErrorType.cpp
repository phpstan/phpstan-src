/*
 * PHPStanTurbo\ErrorType — native implementation of PHPStan\Type\ErrorType.
 *
 * Declared as PHPStan\Type\ErrorType itself at activation: not final
 * (CircularTypeAliasErrorType and AbsorbedTemplateArgumentType extend it,
 * natively; a PHP subclass may too), extending the native MixedType. The
 * twin's state is the promoted `private ?string $reason`, in the slot
 * following MixedType's two; its constructor calls parent::__construct(),
 * which goes to MixedType's constructor body directly.
 *
 * `self` in the twin (subtract(), equals()) is ErrorType itself, never
 * `static` — the native bodies hold the class entry the same way. The one
 * `parent::` call (describe()) goes to MixedType's native body run on the
 * object, so a subclass overriding describeSubtractedType() is still
 * honoured inside it.
 */

#include "TypeTraits.h"
#include "generated/ErrorType.h"

namespace slots = ptdecl::ErrorType::slot;
namespace sigs = ptdecl::ErrorType::sig;

zend_class_entry *pt_ce_error_type = nullptr;

namespace phpstanturbo {

/* Mirrors PHPStan\Type\ErrorType. State lives in the PHP object's $reason
 * (its own) and MixedType's slots. */
class ErrorType
{
public:
	explicit ErrorType(zend_object *self) : self(self) {}

	/* __construct(private ?string $reason = null) { parent::__construct(); }:
	 * the promoted property first (as the engine assigns it), then
	 * MixedType's constructor body; $reason borrowed, NULL for null */
	void construct(zend_string *reason)
	{
		zval *reasonSlot = OBJ_PROP_NUM(self, slots::reason);
		/* the slot is overwritten in place: a repeated __construct() call
		 * would otherwise leak the first value */
		zval previous;
		ZVAL_COPY_VALUE(&previous, reasonSlot);
		if (reason == NULL) {
			ZVAL_NULL(reasonSlot);
		} else {
			ZVAL_STR_COPY(reasonSlot, reason);
		}
		Z_PROP_FLAG_P(reasonSlot) = 0; /* no longer IS_PROP_UNINIT */
		if (Z_TYPE(previous) != IS_UNDEF) {
			zval_ptr_dtor(&previous);
		}
		pt_mixed_type_construct(self, false, NULL);
	}

	/* new ErrorType() / new self() — exactly the class, as the twin's sites
	 * spell it; UNDEF = pending exception */
	static zv::Val create(zend_string *reason = NULL)
	{
		zval object;
		if (UNEXPECTED(object_init_ex(&object, pt_ce_error_type) != SUCCESS)) return zv::Val();
		ErrorType(Z_OBJ(object)).construct(reason);
		return zv::Val::adopt(object);
	}

	/* $this->reason (borrowed, IS_NULL or IS_STRING); NULL with an Error
	 * pending when the constructor never ran
	 * (ReflectionClass::newInstanceWithoutConstructor()) — the twin's
	 * typed-property read raises the same */
	[[nodiscard]] zval *reason() const
	{
		zval *slot = OBJ_PROP_NUM(self, slots::reason);
		if (UNEXPECTED(Z_TYPE_P(slot) == IS_UNDEF)) {
			zend_throw_error(NULL, "Typed property %s::$reason must not be accessed before initialization", ZSTR_VAL(pt_ce_error_type->name));
			return NULL;
		}
		return slot;
	}

	/* $level->handle(): parent::describe($level) for the type-only and
	 * value levels, '*ERROR*' for the precise level and (no cache callback
	 * given) the cache level; UNDEF = pending exception */
	zv::Val describe(zval *level) const
	{
		pt_verbosity_case which;
		if (UNEXPECTED(!pt_type_verbosity_case(level, which))) return zv::Val();
		if (which == PT_VERBOSITY_TYPE_ONLY || which == PT_VERBOSITY_VALUE) return pt_mixed_type_describe(self, level);
		return zv::Val::string("*ERROR*", sizeof("*ERROR*") - 1);
	}

	/* new ErrorType() */
	static zv::Val getIterableKeyType() { return create(); }

	/* new ErrorType() */
	static zv::Val getIterableValueType() { return create(); }

	/* new self() */
	static zv::Val subtract() { return create(); }

	/* $type instanceof self */
	static bool equals(zval *type) { return instanceof_function(Z_OBJCE_P(type), pt_ce_error_type); }

private:
	zend_object *self;
};

} // namespace phpstanturbo

using phpstanturbo::ErrorType;

bool pt_error_type_new(zval *out, zend_string *reason)
{
	return pt_val_into(ErrorType::create(reason), out);
}

void pt_error_type_construct(zend_object *self, zend_string *reason)
{
	ErrorType(self).construct(reason);
}

/* {{{ engine ABI glue: parameter parsing + registration */

#define PT_THIS ErrorType(Z_OBJ_P(ZEND_THIS))

/* new ErrorType() — the bodies getIterableKeyType() and
 * getIterableValueType() share (one handler per arity; each method is
 * still declared exactly once, at its registration line) */
static void ZEND_FASTCALL etError0(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	PT_RETURN_VAL(ErrorType::create());
}

void pt_register_error_type()
{
	reg::Class cls("PHPStan\\Type\\ErrorType");
	ptdecl::ErrorType::declareClass(cls);
	/* "reason" is the class's only declared property, in the slot after the
	 * parent's two (slots::reason) */
	ptdecl::ErrorType::declareProperties(cls);

	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zend_string *reason = NULL;
		if (!zp::parse<zp::Opt<zp::StrOrNull>>(execute_data, reason)) RETURN_THROWS();
		PT_THIS.construct(reason);
	});

	cls.method(sigs::getReason, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		zval *reason = PT_THIS.reason();
		if (UNEXPECTED(reason == NULL)) RETURN_THROWS();
		RETURN_COPY(reason);
	});

	cls.method<&ErrorType::describe, zp::Obj>(sigs::describe);
	cls.op<PT_OP_DESCRIBE, &ErrorType::describe>();

	cls.method(sigs::getIterableKeyType, etError0);
	cls.op(PT_OP_GET_ITERABLE_KEY_TYPE, PT_OP_LAMBDA { return ErrorType::create(); });
	cls.method(sigs::getIterableValueType, etError0);
	cls.op(PT_OP_GET_ITERABLE_VALUE_TYPE, PT_OP_LAMBDA { return ErrorType::create(); });

	cls.method(sigs::subtract, [](INTERNAL_FUNCTION_PARAMETERS) {
		PT_ARGS(1, 1);
		PT_RETURN_VAL(ErrorType::subtract());
	});

	cls.method(sigs::equals, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *type;
		if (!zp::parse<zp::Obj>(execute_data, type)) RETURN_THROWS();
		RETURN_BOOL(ErrorType::equals(type));
	});
	cls.op(PT_OP_EQUALS, PT_OP_LAMBDA { return zv::Val::boolean(ErrorType::equals(argv)); });

	cls.shadow(&pt_ce_error_type);
}

/* }}} */
