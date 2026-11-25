/*
 * PHPStanTurbo\ConvergedLoopHeads — native implementation of
 * PHPStan\Analyser\ConvergedLoopHeads.
 *
 * The entry scopes the loops nested in a top-level loop converged to, keyed
 * by the loop node's object id (spl_object_id() is the object handle). The
 * state lives in the twin's $heads slot (generated declarations); the loop
 * handlers use the pt_converged_loop_heads_* direct entries (support.h).
 */

#include "support.h"
#include "generated/ConvergedLoopHeads.h"

namespace slots = ptdecl::ConvergedLoopHeads::slot;
namespace sigs = ptdecl::ConvergedLoopHeads::sig;
#include "zv.h"

zend_class_entry *pt_ce_converged_loop_heads = nullptr;

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\ConvergedLoopHeads; zv::Val results use UNDEF for
 * a pending exception. */
class ConvergedLoopHeads
{
public:
	explicit ConvergedLoopHeads(zend_object *self) : self(self) {}

	/* new self() — the class is final */
	static zv::Val create()
	{
		zval object;
		if (UNEXPECTED(object_init_ex(&object, pt_ce_converged_loop_heads) != SUCCESS)) return zv::Val();
		return zv::Val::adopt(object);
	}

	/* Mirrors warmStart(). */
	zv::Val warmStart(zval *loop, zval *entryScope) const
	{
		zval *head = zend_hash_index_find(Z_ARRVAL_P(OBJ_PROP_NUM(self, slots::heads)), Z_OBJ_HANDLE_P(loop));
		if (head == nullptr) return zv::Val::copyOf(zv::Ref(entryScope));

		return pt_mutating_scope_merge_with(Z_OBJ_P(entryScope), head);
	}

	/* Mirrors record(). */
	void record(zval *loop, zval *head) const
	{
		zval *heads = OBJ_PROP_NUM(self, slots::heads);
		SEPARATE_ARRAY(heads);
		Z_ADDREF_P(head);
		zend_hash_index_update(Z_ARRVAL_P(heads), Z_OBJ_HANDLE_P(loop), head);
	}

private:
	zend_object *self;
};

} // namespace phpstanturbo

using phpstanturbo::ConvergedLoopHeads;

/* {{{ direct entries for native callers: the slot of a native instance, the
 * methods of anything else (the PHP twin under the prefixed differential
 * activation) */

namespace {

inline bool isNative(zval *heads)
{
	return EXPECTED(Z_OBJCE_P(heads) == pt_ce_converged_loop_heads);
}

} // namespace

zv::Val pt_converged_loop_heads_new()
{
	return ConvergedLoopHeads::create();
}

zv::Val pt_converged_loop_heads_warm_start(zval *heads, zval *loop, zval *entryScope)
{
	if (isNative(heads)) return ConvergedLoopHeads(Z_OBJ_P(heads)).warmStart(loop, entryScope);
	zv::Args argv{loop, entryScope};
	return pt_type_call(Z_OBJ_P(heads), PT_LC("warmstart"), 2, argv);
}

bool pt_converged_loop_heads_record(zval *heads, zval *loop, zval *head)
{
	if (isNative(heads)) {
		ConvergedLoopHeads(Z_OBJ_P(heads)).record(loop, head);
		return true;
	}
	zv::Args argv{loop, head};
	zv::Val result = pt_type_call(Z_OBJ_P(heads), PT_LC("record"), 2, argv);
	return !result.isUndef();
}

/* }}} */

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

PT_MINIT_REGISTRATION(pt_register_converged_loop_heads)
{
	reg::Class cls("PHPStan\\Analyser\\ConvergedLoopHeads");
	ptdecl::ConvergedLoopHeads::declareClass(cls);
	ptdecl::ConvergedLoopHeads::declareProperties(cls);

	cls.method(sigs::warmStart, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *loop;
		zval *entryScope;
		if (!zp::parse<zp::Obj, zp::Obj>(execute_data, loop, entryScope)) RETURN_THROWS();
		PT_RETURN_VAL(ConvergedLoopHeads(Z_OBJ_P(ZEND_THIS)).warmStart(loop, entryScope));
	});

	cls.method(sigs::record, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *loop;
		zval *head;
		if (!zp::parse<zp::Obj, zp::Obj>(execute_data, loop, head)) RETURN_THROWS();
		ConvergedLoopHeads(Z_OBJ_P(ZEND_THIS)).record(loop, head);
	});

	cls.shadow(&pt_ce_converged_loop_heads);
}

/* }}} */
