/*
 * PHPStanTurbo\StatementContext — native implementation of
 * PHPStan\Analyser\StatementContext.
 *
 * A final value class with a private constructor: instances come only from
 * the static factories and the enter*() / with*() derivations. The state
 * lives in the twin's three promoted property slots (generated
 * declarations). Native callers use the pt_statement_context_* direct
 * entries (support.h).
 */

#include "support.h"
#include "generated/StatementContext.h"

namespace slots = ptdecl::StatementContext::slot;
namespace sigs = ptdecl::StatementContext::sig;
#include "zv.h"
#include "TypeTraits.h"

zend_class_entry *pt_ce_statement_context = nullptr;

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\StatementContext; zv::Val results use UNDEF for a
 * pending exception. */
class StatementContext
{
public:
	explicit StatementContext(zend_object *self) : self(self) {}

	/* the constructor body: the promoted properties; a NULL type is null */
	void construct(bool isTopLevel, zend_long foreachUnrollFactor, bool resolveTemplateArguments, zval *expectedReturnType, zval *nativeExpectedReturnType)
	{
		writeSlot(slots::isTopLevel, isTopLevel ? IS_TRUE : IS_FALSE, 0);
		writeSlot(slots::foreachUnrollFactor, IS_LONG, foreachUnrollFactor);
		writeSlot(slots::resolveTemplateArguments, resolveTemplateArguments ? IS_TRUE : IS_FALSE, 0);
		writeObjectSlot(slots::expectedReturnType, expectedReturnType);
		writeObjectSlot(slots::nativeExpectedReturnType, nativeExpectedReturnType);
	}

	/* Mirrors createTopLevel(). */
	static zv::Val createTopLevel(bool resolveTemplateArguments)
	{
		return newSelf(true, 1, resolveTemplateArguments, nullptr, nullptr);
	}

	/* Mirrors createDeep(). */
	static zv::Val createDeep(bool resolveTemplateArguments)
	{
		return newSelf(false, 1, resolveTemplateArguments, nullptr, nullptr);
	}

	bool isTopLevel() const { return Z_TYPE_P(slot(slots::isTopLevel)) == IS_TRUE; }
	zend_long getForeachUnrollFactor() const { return Z_LVAL_P(slot(slots::foreachUnrollFactor)); }
	bool shouldResolveTemplateArguments() const { return Z_TYPE_P(slot(slots::resolveTemplateArguments)) == IS_TRUE; }
	/* borrowed: the Type object, or NULL for null */
	zval *expectedReturnType() const { return objectSlot(slots::expectedReturnType); }
	zval *nativeExpectedReturnType() const { return objectSlot(slots::nativeExpectedReturnType); }

	zv::Val getExpectedReturnType() const { return slotValue(slots::expectedReturnType); }
	zv::Val getNativeExpectedReturnType() const { return slotValue(slots::nativeExpectedReturnType); }

	/* Mirrors withExpectedReturnType(). */
	zv::Val withExpectedReturnType(zval *expectedReturnType, zval *nativeExpectedReturnType) const
	{
		if (expectedReturnType == nullptr && nativeExpectedReturnType == nullptr) return thisValue();

		return newSelf(isTopLevel(), getForeachUnrollFactor(), shouldResolveTemplateArguments(), expectedReturnType, nativeExpectedReturnType);
	}

	/* Mirrors withoutTemplateArgumentResolution(). */
	zv::Val withoutTemplateArgumentResolution() const
	{
		if (!shouldResolveTemplateArguments()) return thisValue();

		return newSelf(isTopLevel(), getForeachUnrollFactor(), false, expectedReturnType(), nativeExpectedReturnType());
	}

	/* Mirrors enterDeep(). */
	zv::Val enterDeep() const
	{
		if (isTopLevel()) return newSelf(false, getForeachUnrollFactor(), shouldResolveTemplateArguments(), expectedReturnType(), nativeExpectedReturnType());

		return thisValue();
	}

	/* Mirrors enterUnrolledForeach(): an int overflow of the product is a
	 * float, which the constructor's int parameter rejects under the twin's
	 * strict_types */
	zv::Val enterUnrolledForeach(zend_long totalKeys) const
	{
		zend_long product = 0;
		if (UNEXPECTED(multiplyOverflows(getForeachUnrollFactor(), totalKeys, product))) {
			zend_type_error("%s::__construct(): Argument #2 ($foreachUnrollFactor) must be of type int, float given", ZSTR_VAL(self->ce->name));
			return zv::Val();
		}

		return newSelf(isTopLevel(), product, shouldResolveTemplateArguments(), expectedReturnType(), nativeExpectedReturnType());
	}

private:
	zend_object *self;

	/* $a * $b of two ints: false with the product, true when PHP would
	 * overflow it into a float — the engine's ZEND_SIGNED_MULTIPLY_LONG,
	 * spelled with the GCC/Clang builtin where there is one (the macro hides
	 * an assignment in an if condition, which the lint rejects) */
#if defined(__GNUC__)
	static bool multiplyOverflows(zend_long a, zend_long b, zend_long &product)
	{
		return __builtin_mul_overflow(a, b, &product);
	}
#else
	static bool multiplyOverflows(zend_long a, zend_long b, zend_long &product)
	{
		double productDouble = 0;
		zend_long overflowed = 0;
		ZEND_SIGNED_MULTIPLY_LONG(a, b, product, productDouble, overflowed);
		(void) productDouble;
		return overflowed != 0;
	}
#endif

	zval *slot(uint32_t index) const { return OBJ_PROP_NUM(self, index); }

	zval *objectSlot(uint32_t index) const
	{
		zval *p = slot(index);
		return Z_TYPE_P(p) == IS_OBJECT ? p : nullptr;
	}

	zv::Val slotValue(uint32_t index) const
	{
		zval value;
		ZVAL_COPY(&value, slot(index));
		return zv::Val::adopt(value);
	}

	/* a nullable Type property: NULL writes null */
	void writeObjectSlot(uint32_t index, zval *value)
	{
		zval *p = slot(index);
		zval old;
		ZVAL_COPY_VALUE(&old, p);
		if (value != nullptr && Z_TYPE_P(value) == IS_OBJECT) {
			ZVAL_COPY(p, value);
		} else {
			ZVAL_NULL(p);
		}
		zval_ptr_dtor(&old);
	}

	zv::Val thisValue() const
	{
		zval value;
		ZVAL_OBJ_COPY(&value, self);
		return zv::Val::adopt(value);
	}

	void writeSlot(uint32_t index, uint8_t type, zend_long lval)
	{
		zval *p = slot(index);
		zval old;
		ZVAL_COPY_VALUE(&old, p);
		if (type == IS_LONG) {
			ZVAL_LONG(p, lval);
		} else {
			Z_TYPE_INFO_P(p) = type;
		}
		zval_ptr_dtor(&old);
	}

	/* new self(...) — the class is final */
	static zv::Val newSelf(bool isTopLevel, zend_long foreachUnrollFactor, bool resolveTemplateArguments, zval *expectedReturnType, zval *nativeExpectedReturnType)
	{
		zval object;
		if (UNEXPECTED(object_init_ex(&object, pt_ce_statement_context) != SUCCESS)) return zv::Val();
		StatementContext(Z_OBJ(object)).construct(isTopLevel, foreachUnrollFactor, resolveTemplateArguments, expectedReturnType, nativeExpectedReturnType);
		return zv::Val::adopt(object);
	}
};

} // namespace phpstanturbo

using phpstanturbo::StatementContext;

/* {{{ direct entries for native callers: the slots of a native context, the
 * methods of anything else (the PHP twin under the prefixed differential
 * activation) */

namespace {

inline bool isNative(zval *context)
{
	return EXPECTED(Z_OBJCE_P(context) == pt_ce_statement_context);
}

[[nodiscard]] bool boolMethod(zval *context, const char *lcname, size_t len, bool &out)
{
	zv::Val result = pt_type_call(Z_OBJ_P(context), lcname, len, 0, NULL);
	if (UNEXPECTED(result.isUndef())) return false;
	out = Z_TYPE_P(result.raw()) == IS_TRUE;
	return true;
}

} // namespace

zv::Val pt_statement_context_create_top_level(bool resolveTemplateArguments)
{
	return StatementContext::createTopLevel(resolveTemplateArguments);
}

zv::Val pt_statement_context_create_deep(bool resolveTemplateArguments)
{
	return StatementContext::createDeep(resolveTemplateArguments);
}

bool pt_statement_context_is_top_level(zval *context, bool &out)
{
	if (isNative(context)) {
		out = StatementContext(Z_OBJ_P(context)).isTopLevel();
		return true;
	}
	return boolMethod(context, PT_LC("istoplevel"), out);
}

bool pt_statement_context_should_resolve_template_arguments(zval *context, bool &out)
{
	if (isNative(context)) {
		out = StatementContext(Z_OBJ_P(context)).shouldResolveTemplateArguments();
		return true;
	}
	return boolMethod(context, PT_LC("shouldresolvetemplatearguments"), out);
}

bool pt_statement_context_get_foreach_unroll_factor(zval *context, zend_long &out)
{
	if (isNative(context)) {
		out = StatementContext(Z_OBJ_P(context)).getForeachUnrollFactor();
		return true;
	}
	zv::Val result = pt_type_call(Z_OBJ_P(context), PT_LC("getforeachunrollfactor"), 0, NULL);
	if (UNEXPECTED(result.isUndef())) return false;
	out = zval_get_long(result.raw());
	return true;
}

zv::Val pt_statement_context_without_template_argument_resolution(zval *context)
{
	if (isNative(context)) return StatementContext(Z_OBJ_P(context)).withoutTemplateArgumentResolution();
	return pt_type_call(Z_OBJ_P(context), PT_LC("withouttemplateargumentresolution"), 0, NULL);
}

zv::Val pt_statement_context_enter_deep(zval *context)
{
	if (isNative(context)) return StatementContext(Z_OBJ_P(context)).enterDeep();
	return pt_type_call(Z_OBJ_P(context), PT_LC("enterdeep"), 0, NULL);
}

zv::Val pt_statement_context_with_expected_return_type(zval *context, zval *expectedReturnType, zval *nativeExpectedReturnType)
{
	if (isNative(context)) return StatementContext(Z_OBJ_P(context)).withExpectedReturnType(expectedReturnType, nativeExpectedReturnType);
	zval argv[2];
	if (expectedReturnType != nullptr) ZVAL_COPY_VALUE(&argv[0], expectedReturnType); else ZVAL_NULL(&argv[0]);
	if (nativeExpectedReturnType != nullptr) ZVAL_COPY_VALUE(&argv[1], nativeExpectedReturnType); else ZVAL_NULL(&argv[1]);
	return pt_type_call(Z_OBJ_P(context), PT_LC("withexpectedreturntype"), 2, argv);
}

zv::Val pt_statement_context_get_expected_return_type(zval *context)
{
	if (isNative(context)) return StatementContext(Z_OBJ_P(context)).getExpectedReturnType();
	return pt_type_call(Z_OBJ_P(context), PT_LC("getexpectedreturntype"), 0, NULL);
}

zv::Val pt_statement_context_get_native_expected_return_type(zval *context)
{
	if (isNative(context)) return StatementContext(Z_OBJ_P(context)).getNativeExpectedReturnType();
	return pt_type_call(Z_OBJ_P(context), PT_LC("getnativeexpectedreturntype"), 0, NULL);
}

zv::Val pt_statement_context_enter_unrolled_foreach(zval *context, zend_long totalKeys)
{
	if (isNative(context)) return StatementContext(Z_OBJ_P(context)).enterUnrolledForeach(totalKeys);
	zv::Args argv{totalKeys};
	return pt_type_call(Z_OBJ_P(context), PT_LC("enterunrolledforeach"), 1, argv);
}

/* }}} */

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

PT_MINIT_REGISTRATION(pt_register_statement_context)
{
	reg::Class cls("PHPStan\\Analyser\\StatementContext");
	ptdecl::StatementContext::declareClass(cls);
	ptdecl::StatementContext::declareProperties(cls);

	/* private like the twin's: `new StatementContext(...)` from userland
	 * fails the same way; the native derivations fill the slots without it */
	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		bool isTopLevel;
		zend_long foreachUnrollFactor = 1;
		bool resolveTemplateArguments = true;
		zval *expectedReturnType = nullptr;
		zval *nativeExpectedReturnType = nullptr;
		ZEND_PARSE_PARAMETERS_START(1, 5)
			Z_PARAM_BOOL(isTopLevel)
			Z_PARAM_OPTIONAL
			Z_PARAM_LONG(foreachUnrollFactor)
			Z_PARAM_BOOL(resolveTemplateArguments)
			Z_PARAM_OBJECT_OR_NULL(expectedReturnType)
			Z_PARAM_OBJECT_OR_NULL(nativeExpectedReturnType)
		ZEND_PARSE_PARAMETERS_END();
		StatementContext(Z_OBJ_P(ZEND_THIS)).construct(isTopLevel, foreachUnrollFactor, resolveTemplateArguments, expectedReturnType, nativeExpectedReturnType);
	});

	cls.method(sigs::createTopLevel, [](INTERNAL_FUNCTION_PARAMETERS) {
		bool resolveTemplateArguments = true;
		if (!zp::parse<zp::Opt<zp::Bool>>(execute_data, resolveTemplateArguments)) RETURN_THROWS();
		PT_RETURN_VAL(StatementContext::createTopLevel(resolveTemplateArguments));
	});

	cls.method(sigs::createDeep, [](INTERNAL_FUNCTION_PARAMETERS) {
		bool resolveTemplateArguments = true;
		if (!zp::parse<zp::Opt<zp::Bool>>(execute_data, resolveTemplateArguments)) RETURN_THROWS();
		PT_RETURN_VAL(StatementContext::createDeep(resolveTemplateArguments));
	});

	cls.method(sigs::isTopLevel, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_BOOL(StatementContext(Z_OBJ_P(ZEND_THIS)).isTopLevel());
	});

	cls.method(sigs::getForeachUnrollFactor, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_LONG(StatementContext(Z_OBJ_P(ZEND_THIS)).getForeachUnrollFactor());
	});

	cls.method(sigs::shouldResolveTemplateArguments, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		RETURN_BOOL(StatementContext(Z_OBJ_P(ZEND_THIS)).shouldResolveTemplateArguments());
	});

	cls.method<&StatementContext::withoutTemplateArgumentResolution>(sigs::withoutTemplateArgumentResolution);

	cls.method(sigs::withExpectedReturnType, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *expectedReturnType = nullptr;
		zval *nativeExpectedReturnType = nullptr;
		ZEND_PARSE_PARAMETERS_START(2, 2)
			Z_PARAM_OBJECT_OR_NULL(expectedReturnType)
			Z_PARAM_OBJECT_OR_NULL(nativeExpectedReturnType)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(StatementContext(Z_OBJ_P(ZEND_THIS)).withExpectedReturnType(expectedReturnType, nativeExpectedReturnType));
	});

	cls.method(sigs::getExpectedReturnType, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		PT_RETURN_VAL(StatementContext(Z_OBJ_P(ZEND_THIS)).getExpectedReturnType());
	});

	cls.method(sigs::getNativeExpectedReturnType, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		PT_RETURN_VAL(StatementContext(Z_OBJ_P(ZEND_THIS)).getNativeExpectedReturnType());
	});
	cls.method<&StatementContext::enterDeep>(sigs::enterDeep);

	cls.method(sigs::enterUnrolledForeach, [](INTERNAL_FUNCTION_PARAMETERS) {
		zend_long totalKeys;
		if (!zp::parse<zp::Long>(execute_data, totalKeys)) RETURN_THROWS();
		PT_RETURN_VAL(StatementContext(Z_OBJ_P(ZEND_THIS)).enterUnrolledForeach(totalKeys));
	});

	cls.shadow(&pt_ce_statement_context);
}

/* }}} */
