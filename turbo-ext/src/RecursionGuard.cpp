/*
 * PHPStanTurbo\RecursionGuard — native implementation of PHPStan\Type\RecursionGuard.
 *
 * When the extension is active, PHPStan\Type\RecursionGuard is this class,
 * declared under that name at activation (final, like the twin). The
 * context lives where the twin keeps it — in the class's private static
 * $context array, keyed the way PHP keys it (a numeric description becomes
 * an integer key, an object id is one) — and the TypeCombinatorCache memo
 * reads it through pt_recursion_guard_active().
 */

#include "support.h"
#include "generated/RecursionGuard.h"

namespace sigs = ptdecl::RecursionGuard::sig;
#include "zv.h"
#include "TypeTraits.h"

zend_class_entry *pt_ce_recursion_guard = NULL;

/* the twin's `private static array $context` slot (borrowed; resolved once
 * per activated class) */
static zend_class_entry *pt_rg_context_ce = nullptr;
static zval *pt_rg_context_slot = nullptr;

static zval *pt_rg_context()
{
	zend_class_entry *ce = pt_ce_recursion_guard;
	if (UNEXPECTED(pt_rg_context_ce != ce)) {
		ZEND_ASSERT(ce != NULL);
		if (CE_STATIC_MEMBERS(ce) == NULL) {
			zend_class_init_statics(ce);
		}
		zend_property_info *info = (zend_property_info *) zend_hash_str_find_ptr(&ce->properties_info, PT_LC("context"));
		ZEND_ASSERT(info != NULL && (info->flags & ZEND_ACC_STATIC) != 0);
		pt_rg_context_slot = CE_STATIC_MEMBERS(ce) + info->offset;
		pt_rg_context_ce = ce;
	}
	return pt_rg_context_slot;
}

namespace phpstanturbo {

/* Mirrors PHPStan\Type\RecursionGuard. State lives in the class's $context. */
class RecursionGuard
{
public:
	/* run(): keyed by $type->describe(VerbosityLevel::value()); UNDEF =
	 * pending exception */
	static zv::Val run(zval *type, zend_fcall_info *fci, zend_fcall_info_cache *fcc)
	{
		zval *level = pt_verbosity_level_singleton(PT_VERBOSITY_LEVEL_VALUE);
		if (UNEXPECTED(level == NULL)) return zv::Val();
		zv::Val key = pt_type_call(Z_OBJ_P(type), PT_LC("describe"), 1, level);
		if (UNEXPECTED(key.isUndef())) return zv::Val();
		if (UNEXPECTED(!zv::Ref(key.raw()).isString())) {
			zend_type_error("phpstan_turbo: describe() must return string");
			return zv::Val();
		}
		return guarded(zv::Ref(key.raw()).asString(), 0, fci, fcc);
	}

	/* runOnObjectIdentity(): keyed by spl_object_id($type) */
	static zv::Val runOnObjectIdentity(zval *type, zend_fcall_info *fci, zend_fcall_info_cache *fcc)
	{
		return guarded(NULL, (zend_ulong) Z_OBJ_HANDLE_P(type), fci, fcc);
	}

	/* whether $context is non-empty (true too when it cannot be read) */
	static bool active()
	{
		if (UNEXPECTED(pt_ce_recursion_guard == NULL)) return true;
		zval *context = pt_rg_context();
		return Z_TYPE_P(context) != IS_ARRAY || zend_hash_num_elements(Z_ARRVAL_P(context)) > 0;
	}

private:
	/* the shared body: ErrorType when the key is set, else the callback's
	 * result with the key held while it runs and released afterwards — on
	 * an exception too (the twin's finally); skey NULL = the integer key */
	static zv::Val guarded(zend_string *skey, zend_ulong idx, zend_fcall_info *fci, zend_fcall_info_cache *fcc)
	{
		zval *context = pt_rg_context();
		if (UNEXPECTED(Z_TYPE_P(context) != IS_ARRAY)) {
			/* the typed static array a dim write initializes */
			array_init(context);
		}
		if (skey != NULL ? zend_symtable_exists(Z_ARRVAL_P(context), skey) : zend_hash_index_exists(Z_ARRVAL_P(context), idx)) return pt_type_new_error_type();

		SEPARATE_ARRAY(context);
		zval flag;
		ZVAL_TRUE(&flag);
		if (skey != NULL) {
			zend_symtable_update(Z_ARRVAL_P(context), skey, &flag);
		} else {
			zend_hash_index_update(Z_ARRVAL_P(context), idx, &flag);
		}

		zval result;
		bool ok = pt_call_fci(fci, fcc, 0, NULL, &result);

		/* finally: unset(self::$context[$key]) — re-read, the callback may
		 * have replaced the array */
		context = pt_rg_context();
		if (EXPECTED(Z_TYPE_P(context) == IS_ARRAY)) {
			SEPARATE_ARRAY(context);
			if (skey != NULL) {
				zend_symtable_del(Z_ARRVAL_P(context), skey);
			} else {
				zend_hash_index_del(Z_ARRVAL_P(context), idx);
			}
		}

		if (UNEXPECTED(!ok)) return zv::Val();
		return zv::Val::adopt(result);
	}
};

} // namespace phpstanturbo

using phpstanturbo::RecursionGuard;

/* {{{ exported helpers */

/* a callable zval resolved for the guarded call; false = pending exception */
[[nodiscard]] static bool pt_rg_resolve(zval *callback, zend_fcall_info &fci, zend_fcall_info_cache &fcc)
{
	char *error = NULL;
	if (UNEXPECTED(zend_fcall_info_init(callback, 0, &fci, &fcc, NULL, &error) != SUCCESS)) {
		zend_type_error("phpstan_turbo: RecursionGuard callback must be callable%s%s", error != NULL ? ": " : "", error != NULL ? error : "");
		if (error != NULL) {
			efree(error);
		}
		return false;
	}
	if (error != NULL) {
		efree(error);
	}
	return true;
}

bool pt_recursion_guard_run(zval *out, zval *type, zval *callback)
{
	zend_fcall_info fci;
	zend_fcall_info_cache fcc;
	if (UNEXPECTED(!pt_rg_resolve(callback, fci, fcc))) return false;
	zv::Val result = RecursionGuard::run(type, &fci, &fcc);
	if (UNEXPECTED(result.isUndef())) return false;
	result.intoReturnValue(out);
	return true;
}

bool pt_recursion_guard_run_on_object_identity(zval *out, zval *type, zval *callback)
{
	zend_fcall_info fci;
	zend_fcall_info_cache fcc;
	if (UNEXPECTED(!pt_rg_resolve(callback, fci, fcc))) return false;
	zv::Val result = RecursionGuard::runOnObjectIdentity(type, &fci, &fcc);
	if (UNEXPECTED(result.isUndef())) return false;
	result.intoReturnValue(out);
	return true;
}

bool pt_recursion_guard_active()
{
	return RecursionGuard::active();
}

/* }}} */

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

void pt_register_recursion_guard()
{
	reg::Class cls("PHPStan\\Type\\RecursionGuard");
	ptdecl::RecursionGuard::declareClass(cls);
	cls.privateStaticTypedArrayPropertyDefaultEmpty("context");

	cls.method(sigs::run, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *type;
		zend_fcall_info fci;
		zend_fcall_info_cache fcc;
		ZEND_PARSE_PARAMETERS_START(2, 2)
			Z_PARAM_OBJECT(type)
			Z_PARAM_FUNC(fci, fcc)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(RecursionGuard::run(type, &fci, &fcc));
	});

	cls.method(sigs::runOnObjectIdentity, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *type;
		zend_fcall_info fci;
		zend_fcall_info_cache fcc;
		ZEND_PARSE_PARAMETERS_START(2, 2)
			Z_PARAM_OBJECT(type)
			Z_PARAM_FUNC(fci, fcc)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(RecursionGuard::runOnObjectIdentity(type, &fci, &fcc));
	});

	cls.shadow(&pt_ce_recursion_guard);
}

/* }}} */
