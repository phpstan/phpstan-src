/*
 * TrustedTypes — drops the engine's run-time type checks from PHPStan's own
 * code.
 *
 * PHPStan's code is verified by PHPStan at its strictest level, so the
 * engine's argument and return type checks on it re-check what analysis
 * already proved — and they are not free: a class-typed parameter costs a
 * class-entry lookup plus an instanceof walk on every call (ZEND_RECV), a
 * typed return the same on the way out (ZEND_VERIFY_RETURN_TYPE). A php-src
 * build that skipped them for PHPStan's files measured -8.3% user CPU on a
 * self-analysis (-10% including typed-property writes, which an extension
 * cannot reach — see below).
 *
 * The switch lives in the optimizer: opcache lets extensions register
 * passes (zend_optimizer_register_pass) that run at the end of
 * zend_optimize_script(), before the script is persisted, so what shared
 * memory (and a file cache) hold is the stripped code. For every function
 * of a script whose filename starts with the trusted prefix — the running
 * phar, handed over by TurboExtensionEnabler::trustOwnTypesIfSuitable():
 *
 *   arguments: ZEND_ACC_HAS_TYPE_HINTS is cleared. The engine then skips
 *   the RECV opcodes of the passed arguments altogether ("Skip useless
 *   ZEND_RECV and ZEND_RECV_INIT opcodes" in i_init_func_execute_data) and
 *   RECV_INIT stops verifying defaults. Reflection reads arg_info and is
 *   unaffected; so are the inheritance checks, which compare arg_info.
 *
 *   returns: ZEND_VERIFY_RETURN_TYPE on a variable or temporary becomes a
 *   NOP. The constant-operand form defines the temporary the following
 *   RETURN reads and takes the cheap scalar-mask path anyway, the
 *   implicit end-of-function check (no operand) is what reports a missing
 *   return, and by-reference returns type the reference — those stay.
 *
 * Deliberately left checked: any signature that can coerce — a float
 * parameter or return accepts an int and converts it inside the check (the
 * one coercion strict_types keeps), so such functions are left whole; typed
 * variadics (RECV_VARIADIC consults arg_info directly); typed property
 * writes (zend_std_write_property, no flag an extension can flip without
 * changing reflection or variance).
 *
 * Code outside the prefix — extensions, bootstrap files, the analysed
 * project — is never touched, and since a check sits in the callee it keeps
 * checking what it receives from PHPStan and what it returns to it. What is
 * lost is the TypeError at the boundary when such code passes a wrong value
 * *into* PHPStan: it surfaces later. A --debug run keeps the checks — the
 * "run with --debug" advice on internal errors then yields the original
 * error.
 */

#include "support.h"

#pragma GCC diagnostic push
#pragma GCC diagnostic ignored "-Wpragmas"
#pragma GCC diagnostic ignored "-Wunknown-warning-option"
#pragma GCC diagnostic ignored "-Wunused-parameter"
#pragma GCC diagnostic ignored "-Wignored-qualifiers"
#pragma GCC diagnostic ignored "-Wdeprecated-declarations"
#pragma GCC diagnostic ignored "-Wattributes"
#include "zend_vm.h"
#include "Optimizer/zend_optimizer.h" /* zend_script, zend_optimizer_pass_t */
#pragma GCC diagnostic pop

#ifdef PHP_WIN32
#include <windows.h>
#else
#include <dlfcn.h>
#endif

typedef int (*pt_tt_register_pass_t)(zend_optimizer_pass_t pass);

static bool pt_tt_pass_registered = false;

static bool pt_tt_matches(const zend_string *filename)
{
	size_t len = PT_G(trusted_types_prefix_len);
	return len > 0
		&& filename != NULL
		&& ZSTR_LEN(filename) >= len
		&& memcmp(ZSTR_VAL(filename), PT_G(trusted_types_prefix), len) == 0;
}

/* float without int accepts an int and converts it inside the check */
static bool pt_tt_type_coerces(const zend_type *type)
{
	uint32_t mask = ZEND_TYPE_PURE_MASK(*type);
	return (mask & MAY_BE_DOUBLE) != 0 && (mask & MAY_BE_LONG) == 0;
}

static bool pt_tt_signature_coerces(const zend_op_array *op_array)
{
	uint32_t count = op_array->num_args + ((op_array->fn_flags & ZEND_ACC_VARIADIC) ? 1 : 0);
	for (uint32_t i = 0; i < count; i++) {
		if (pt_tt_type_coerces(&op_array->arg_info[i].type)) {
			return true;
		}
	}
	if ((op_array->fn_flags & ZEND_ACC_HAS_RETURN_TYPE)
		&& pt_tt_type_coerces(&op_array->arg_info[-1].type)) {
		return true;
	}
	return false;
}

static void pt_tt_strip(zend_op_array *op_array)
{
	/* closures and arrow functions declared inside, whatever the outer is */
	for (uint32_t i = 0; i < op_array->num_dynamic_func_defs; i++) {
		pt_tt_strip(op_array->dynamic_func_defs[i]);
	}
	/* top-level code has no signature */
	if (op_array->function_name == NULL) {
		return;
	}
	if (pt_tt_signature_coerces(op_array)) {
		return;
	}

	op_array->fn_flags &= ~ZEND_ACC_HAS_TYPE_HINTS;

	if ((op_array->fn_flags & ZEND_ACC_HAS_RETURN_TYPE) == 0
		|| (op_array->fn_flags & ZEND_ACC_RETURN_REFERENCE) != 0) {
		return;
	}
	zend_op *opline = op_array->opcodes;
	zend_op *end = opline + op_array->last;
	for (; opline < end; opline++) {
		if (opline->opcode != ZEND_VERIFY_RETURN_TYPE
			|| (opline->op1_type & (IS_CV | IS_TMP_VAR | IS_VAR)) == 0
			|| opline->result_type != IS_UNUSED) {
			continue;
		}
		opline->opcode = ZEND_NOP;
		opline->op1_type = IS_UNUSED;
		opline->op1.num = 0;
		opline->op2_type = IS_UNUSED;
		opline->op2.num = 0;
		opline->extended_value = 0;
		/* handlers were assigned before the registered passes run */
		zend_vm_set_opcode_handler(opline);
	}
}

static void pt_tt_strip_class(zend_class_entry *ce)
{
	zval *zv;
	ZEND_HASH_FOREACH_VAL(&ce->function_table, zv) {
		zend_op_array *op_array = (zend_op_array *) Z_PTR_P(zv);
		/* early-bound classes already carry their same-file parent's methods */
		if (op_array->type == ZEND_USER_FUNCTION && op_array->scope == ce) {
			pt_tt_strip(op_array);
		}
	} ZEND_HASH_FOREACH_END();

#if PHP_VERSION_ID >= 80400
	ZEND_HASH_FOREACH_VAL(&ce->properties_info, zv) {
		zend_property_info *prop = (zend_property_info *) Z_PTR_P(zv);
		if (prop->ce != ce || prop->hooks == NULL) {
			continue;
		}
		for (int i = 0; i < ZEND_PROPERTY_HOOK_COUNT; i++) {
			if (prop->hooks[i] != NULL && prop->hooks[i]->type == ZEND_USER_FUNCTION) {
				pt_tt_strip(&prop->hooks[i]->op_array);
			}
		}
	} ZEND_HASH_FOREACH_END();
#endif
}

static void pt_tt_pass(zend_script *script, void *ctx)
{
	(void) ctx;
	if (!pt_tt_matches(script->filename)) {
		return;
	}

	pt_tt_strip(&script->main_op_array);

	zval *zv;
	ZEND_HASH_FOREACH_VAL(&script->function_table, zv) {
		pt_tt_strip((zend_op_array *) Z_PTR_P(zv));
	} ZEND_HASH_FOREACH_END();

	ZEND_HASH_FOREACH_VAL(&script->class_table, zv) {
		/* class_alias() entries are IS_ALIAS_PTR to a class listed on its own */
		if (Z_TYPE_P(zv) == IS_PTR) {
			pt_tt_strip_class((zend_class_entry *) Z_PTR_P(zv));
		}
	} ZEND_HASH_FOREACH_END();
}

/* opcache exports the registration under ZEND_API; it is a shared
 * zend_extension loaded RTLD_GLOBAL or linked into the binary, and absent
 * altogether on some hosts, so it is resolved by name instead of linked. */
static bool pt_tt_register_pass()
{
	if (pt_tt_pass_registered) {
		return true;
	}

	pt_tt_register_pass_t register_pass = NULL;
#ifdef PHP_WIN32
	HMODULE opcache = GetModuleHandleA("php_opcache.dll");
	if (opcache != NULL) {
		register_pass = reinterpret_cast<pt_tt_register_pass_t>(GetProcAddress(opcache, "zend_optimizer_register_pass"));
	}
#else
	register_pass = reinterpret_cast<pt_tt_register_pass_t>(dlsym(RTLD_DEFAULT, "zend_optimizer_register_pass"));
#endif
	if (register_pass == NULL) {
		return false;
	}
	/* -1 when the (32-slot) table is full */
	if (register_pass(pt_tt_pass) < 0) {
		return false;
	}
	pt_tt_pass_registered = true;
	return true;
}

bool pt_trusted_types_set_prefix(zend_string *prefix)
{
	PT_G(trusted_types_prefix_len) = 0;
	if (ZSTR_LEN(prefix) == 0 || ZSTR_LEN(prefix) >= sizeof(PT_G(trusted_types_prefix))) {
		return false;
	}
	if (!pt_tt_register_pass()) {
		return false;
	}
	memcpy(PT_G(trusted_types_prefix), ZSTR_VAL(prefix), ZSTR_LEN(prefix));
	PT_G(trusted_types_prefix_len) = ZSTR_LEN(prefix);
	return true;
}
