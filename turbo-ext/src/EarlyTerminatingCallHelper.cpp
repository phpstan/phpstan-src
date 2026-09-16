/*
 * PHPStanTurbo\EarlyTerminatingCallHelper — native implementation of
 * PHPStan\Analyser\ExprHandler\Helper\EarlyTerminatingCallHelper.
 *
 * A DI service (#[AutowiredService]): the constructor keeps the twin's exact
 * arginfo, so Nette autowires it by reflection and pairs the two
 * #[AutowiredParameter]s by name. The state lives in the twin's property
 * slots (generated declarations). isEarlyTerminatingMethodCall() answers
 * the common call — a method name no configured class lists — with one
 * symtable probe on the lowercased name, without a frame; the configured
 * names go through the Type's getObjectClassNames() op, the memoized
 * reflection provider and ExtensionClassHelper's memo
 * (ReflectionAccess.cpp).
 */

#include "support.h"
#include "generated/EarlyTerminatingCallHelper.h"

namespace slots = ptdecl::EarlyTerminatingCallHelper::slot;
namespace sigs = ptdecl::EarlyTerminatingCallHelper::sig;
#include "zv.h"
#include "TypeTraits.h"
#include "TypeOps.h"

static zend_class_entry *pt_ce_early_terminating_call_helper;

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\ExprHandler\Helper\EarlyTerminatingCallHelper. */
class EarlyTerminatingCallHelper
{
public:
	explicit EarlyTerminatingCallHelper(zend_object *self) : self(self) {}

	/* Mirrors __construct(): the promoted properties, then the lowercased
	 * method-name set; false = pending exception */
	static bool construct(zend_object *object, zval *reflectionProvider, zval *earlyTerminatingMethodCalls, zval *earlyTerminatingFunctionCalls)
	{
		writeSlot(object, slots::reflectionProvider, zv::Val::copyOf(zv::Ref(reflectionProvider)));
		writeSlot(object, slots::earlyTerminatingMethodCalls, zv::Val::copyOf(zv::Ref(earlyTerminatingMethodCalls)));
		writeSlot(object, slots::earlyTerminatingFunctionCalls, zv::Val::copyOf(zv::Ref(earlyTerminatingFunctionCalls)));

		zv::Arr earlyTerminatingMethodNames = zv::Arr::empty();
		for (zv::ArrayEntry entry : zv::ArrRef(earlyTerminatingMethodCalls)) {
			zval *methodNames = entry.value().deref().raw();
			if (UNEXPECTED(Z_TYPE_P(methodNames) != IS_ARRAY)) {
				/* foreach over a non-iterable: the warning, then nothing */
				if (Z_TYPE_P(methodNames) == IS_OBJECT) {
					zend_throw_error(NULL, "phpstan_turbo: earlyTerminatingMethodCalls entries must be arrays");
					return false;
				}
				zend_error(E_WARNING, "foreach() argument must be of type array|object, %s given", zend_zval_value_name(methodNames));
				if (UNEXPECTED(EG(exception))) return false;
				continue;
			}
			for (zv::ArrayEntry name : zv::ArrRef(methodNames)) {
				zval *methodName = name.value().deref().raw();
				if (UNEXPECTED(Z_TYPE_P(methodName) != IS_STRING)) {
					zend_type_error("strtolower(): Argument #1 ($string) must be of type string, %s given", zend_zval_value_name(methodName));
					return false;
				}
				zv::Str lower = zv::Str::adopt(zend_string_tolower(Z_STR_P(methodName)));
				earlyTerminatingMethodNames.set(lower.get(), zv::Val::boolean(true));
			}
		}
		writeSlot(object, slots::earlyTerminatingMethodNames, std::move(earlyTerminatingMethodNames));
		return true;
	}

	/* Mirrors isEarlyTerminatingMethodCall(); false = pending exception */
	[[nodiscard]] bool isEarlyTerminatingMethodCall(zend_string *methodName, zval *calledOnType, bool &out) const
	{
		out = false;
		zval *names = slot(slots::earlyTerminatingMethodNames);
		if (UNEXPECTED(Z_TYPE_P(names) != IS_ARRAY)) return uninitialized("earlyTerminatingMethodNames");
		zv::Str lower = zv::Str::adopt(zend_string_tolower(methodName));
		if (EXPECTED(zend_symtable_find(Z_ARRVAL_P(names), lower.get()) == NULL)) return true;

		zv::Val classNames = pt_type_op(Z_OBJ_P(calledOnType), PT_OP_GET_OBJECT_CLASS_NAMES, 0, NULL);
		if (UNEXPECTED(classNames.isUndef())) return false;
		if (UNEXPECTED(Z_TYPE_P(classNames.raw()) != IS_ARRAY)) {
			zend_error(E_WARNING, "foreach() argument must be of type array|object, %s given", zend_zval_value_name(classNames.raw()));
			return !EG(exception);
		}

		zval *reflectionProvider = slot(slots::reflectionProvider);
		if (UNEXPECTED(Z_TYPE_P(reflectionProvider) != IS_OBJECT)) return uninitialized("reflectionProvider");
		for (zv::ArrayEntry entry : zv::ArrRef(classNames.raw())) {
			zval *referencedClass = entry.value().deref().raw();
			bool hasClass;
			if (UNEXPECTED(!pt_reflection_provider_has_class(Z_OBJ_P(reflectionProvider), referencedClass, hasClass))) return false;
			if (!hasClass) continue;

			zv::Val extensionClassNames = pt_extension_class_helper_get_extension_class_names(reflectionProvider, referencedClass);
			if (UNEXPECTED(extensionClassNames.isUndef())) return false;
			if (UNEXPECTED(Z_TYPE_P(extensionClassNames.raw()) != IS_ARRAY)) continue;

			zval *methodCalls = slot(slots::earlyTerminatingMethodCalls);
			if (UNEXPECTED(Z_TYPE_P(methodCalls) != IS_ARRAY)) return uninitialized("earlyTerminatingMethodCalls");
			for (zv::ArrayEntry extensionEntry : zv::ArrRef(extensionClassNames.raw())) {
				zval *className = extensionEntry.value().deref().raw();
				zval *methods = issetOffset(methodCalls, className);
				if (methods == NULL) continue;
				if (UNEXPECTED(EG(exception))) return false;
				bool found;
				if (UNEXPECTED(!inArrayStrict(methodName, methods, found))) return false;
				if (found) {
					out = true;
					return true;
				}
			}
		}

		return true;
	}

	/* Mirrors isEarlyTerminatingFunctionCall(); false = pending exception */
	[[nodiscard]] bool isEarlyTerminatingFunctionCall(zend_string *functionName, bool &out) const
	{
		zval *functionCalls = slot(slots::earlyTerminatingFunctionCalls);
		if (UNEXPECTED(Z_TYPE_P(functionCalls) != IS_ARRAY)) {
			out = false;
			return uninitialized("earlyTerminatingFunctionCalls");
		}
		return inArrayStrict(functionName, functionCalls, out);
	}

private:
	zend_object *self;

	zval *slot(uint32_t index) const { return OBJ_PROP_NUM(self, index); }

	/* the engine's Error for reading a never-written typed property; false */
	bool uninitialized(const char *property) const
	{
		zend_throw_error(NULL, "Typed property %s::$%s must not be accessed before initialization", ZSTR_VAL(self->ce->name), property);
		return false;
	}

	static void writeSlot(zend_object *object, uint32_t index, zv::Val value)
	{
		zv::ObjRef(object).propAtWrite(index, std::move(value));
		Z_PROP_FLAG_P(OBJ_PROP_NUM(object, index)) = 0; /* no longer IS_PROP_UNINIT */
	}

	/* isset($table[$key]) for a class-name element of a PHP array: the
	 * entry (dereferenced) when set, NULL otherwise — a string offset is a
	 * symtable lookup, an int offset an index lookup, anything else is
	 * never set (the notice-free isset() semantics) */
	static zval *issetOffset(zval *table, zval *key)
	{
		zval *found = NULL;
		if (Z_TYPE_P(key) == IS_STRING) {
			found = zend_symtable_find(Z_ARRVAL_P(table), Z_STR_P(key));
		} else if (Z_TYPE_P(key) == IS_LONG) {
			found = zend_hash_index_find(Z_ARRVAL_P(table), (zend_ulong) Z_LVAL_P(key));
		}
		if (found == NULL) return NULL;
		ZVAL_DEREF(found);
		return Z_TYPE_P(found) == IS_NULL ? NULL : found;
	}

	/* in_array($needle, $haystack, true) for a string needle; false = pending
	 * exception (the TypeError of a non-array haystack) */
	[[nodiscard]] static bool inArrayStrict(zend_string *needle, zval *haystack, bool &out)
	{
		out = false;
		if (UNEXPECTED(Z_TYPE_P(haystack) != IS_ARRAY)) {
			zend_type_error("in_array(): Argument #2 ($haystack) must be of type array, %s given", zend_zval_value_name(haystack));
			return false;
		}
		for (zv::ArrayEntry entry : zv::ArrRef(haystack)) {
			zval *value = entry.value().deref().raw();
			if (Z_TYPE_P(value) == IS_STRING && zend_string_equals(Z_STR_P(value), needle)) {
				out = true;
				return true;
			}
		}
		return true;
	}
};

} // namespace phpstanturbo

using phpstanturbo::EarlyTerminatingCallHelper;

/* {{{ direct entries (support.h) */

bool pt_early_terminating_call_helper_is_early_terminating_method_call(zval *helper, zval *methodName, zval *calledOnType, bool &out)
{
	if (EXPECTED(Z_OBJCE_P(helper) == pt_ce_early_terminating_call_helper && Z_TYPE_P(methodName) == IS_STRING && Z_TYPE_P(calledOnType) == IS_OBJECT)) {
		return EarlyTerminatingCallHelper(Z_OBJ_P(helper)).isEarlyTerminatingMethodCall(Z_STR_P(methodName), calledOnType, out);
	}
	zv::Args argv{methodName, calledOnType};
	zv::Val result = pt_type_call(Z_OBJ_P(helper), PT_LC("isearlyterminatingmethodcall"), 2, argv);
	if (UNEXPECTED(result.isUndef())) return false;
	out = zend_is_true(result.raw());
	return true;
}

bool pt_early_terminating_call_helper_is_early_terminating_function_call(zval *helper, zend_string *functionName, bool &out)
{
	if (EXPECTED(Z_OBJCE_P(helper) == pt_ce_early_terminating_call_helper)) return EarlyTerminatingCallHelper(Z_OBJ_P(helper)).isEarlyTerminatingFunctionCall(functionName, out);
	zv::Args argv{functionName};
	zv::Val result = pt_type_call(Z_OBJ_P(helper), PT_LC("isearlyterminatingfunctioncall"), 1, argv);
	if (UNEXPECTED(result.isUndef())) return false;
	out = zend_is_true(result.raw());
	return true;
}

/* }}} */

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

void pt_register_early_terminating_call_helper()
{
	reg::Class cls("PHPStan\\Analyser\\ExprHandler\\Helper\\EarlyTerminatingCallHelper");
	ptdecl::EarlyTerminatingCallHelper::declareClass(cls);
	ptdecl::EarlyTerminatingCallHelper::declareProperties(cls);

	/* the DI service's constructor: the generated arginfo names the twin's
	 * parameter classes exactly (README rule 6) */
	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *reflectionProvider, *earlyTerminatingMethodCalls, *earlyTerminatingFunctionCalls;
		if (!zp::parse<zp::Obj, zp::Arr, zp::Arr>(execute_data, reflectionProvider, earlyTerminatingMethodCalls, earlyTerminatingFunctionCalls)) RETURN_THROWS();
		if (UNEXPECTED(!EarlyTerminatingCallHelper::construct(Z_OBJ_P(ZEND_THIS), reflectionProvider, earlyTerminatingMethodCalls, earlyTerminatingFunctionCalls))) RETURN_THROWS();
	});

	cls.method(sigs::isEarlyTerminatingMethodCall, [](INTERNAL_FUNCTION_PARAMETERS) {
		zend_string *methodName;
		zval *calledOnType;
		if (!zp::parse<zp::Str, zp::Obj>(execute_data, methodName, calledOnType)) RETURN_THROWS();
		bool out;
		if (UNEXPECTED(!EarlyTerminatingCallHelper(Z_OBJ_P(ZEND_THIS)).isEarlyTerminatingMethodCall(methodName, calledOnType, out))) RETURN_THROWS();
		RETURN_BOOL(out);
	});

	cls.method<&EarlyTerminatingCallHelper::isEarlyTerminatingFunctionCall, zp::Str>(sigs::isEarlyTerminatingFunctionCall);

	cls.shadow(&pt_ce_early_terminating_call_helper);
}

/* }}} */
