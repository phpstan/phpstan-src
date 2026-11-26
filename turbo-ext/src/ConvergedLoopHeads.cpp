/*
 * PHPStanTurbo\ConvergedLoopHeads — native implementation of
 * PHPStan\Analyser\ConvergedLoopHeads.
 *
 * The entry scopes the loops nested in a top-level loop converged to, keyed
 * by the loop node's object id (spl_object_id() is the object handle). The
 * state lives in the twin's $heads, $entries and $warmEntries slots
 * (generated declarations); the loop handlers use the
 * pt_converged_loop_heads_* direct entries (support.h).
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
		zend_ulong id = Z_OBJ_HANDLE_P(loop);
		zval *entries = OBJ_PROP_NUM(self, slots::entries);
		SEPARATE_ARRAY(entries);
		Z_ADDREF_P(entryScope);
		zend_hash_index_update(Z_ARRVAL_P(entries), id, entryScope);
		zval *head = zend_hash_index_find(Z_ARRVAL_P(OBJ_PROP_NUM(self, slots::heads)), id);
		if (head == nullptr) return zv::Val::copyOf(zv::Ref(entryScope));

		zval *warmEntry = zend_hash_index_find(Z_ARRVAL_P(OBJ_PROP_NUM(self, slots::warmEntries)), id);
		if (warmEntry != nullptr) {
			zval *cachedEntry = zend_hash_index_find(Z_ARRVAL_P(warmEntry), 0);
			zval *cachedWarm = zend_hash_index_find(Z_ARRVAL_P(warmEntry), 1);
			zv::Val cachedWarmHold = zv::Val::copyOf(zv::Ref(cachedWarm));
			bool equal = Z_OBJ_P(cachedEntry) == Z_OBJ_P(entryScope);
			if (!equal && UNEXPECTED(!pt_mutating_scope_equals(Z_OBJ_P(cachedEntry), Z_OBJ_P(entryScope), equal))) return zv::Val();
			if (equal) return cachedWarmHold;
		}

		zv::Val warmScope = pt_mutating_scope_merge_with(Z_OBJ_P(entryScope), head);
		if (UNEXPECTED(warmScope.isUndef())) return zv::Val();
		zval pair;
		array_init_size(&pair, 2);
		Z_ADDREF_P(entryScope);
		add_next_index_zval(&pair, entryScope);
		zval warmCopy;
		ZVAL_COPY(&warmCopy, warmScope.raw());
		add_next_index_zval(&pair, &warmCopy);
		zval *warmEntries = OBJ_PROP_NUM(self, slots::warmEntries);
		SEPARATE_ARRAY(warmEntries);
		zend_hash_index_update(Z_ARRVAL_P(warmEntries), id, &pair);

		return warmScope;
	}

	/* Mirrors record(); false = pending exception */
	[[nodiscard]] bool record(zval *loop, zval *head) const
	{
		zend_ulong id = Z_OBJ_HANDLE_P(loop);
		// a walk the entry of which is unreachable does not widen the head
		zval *entry = zend_hash_index_find(Z_ARRVAL_P(OBJ_PROP_NUM(self, slots::entries)), id);
		bool unreachable;
		if (UNEXPECTED(!isUnreachable(Z_OBJ_P(entry), unreachable))) return false;
		if (unreachable) return true;

		zval *previous = zend_hash_index_find(Z_ARRVAL_P(OBJ_PROP_NUM(self, slots::heads)), id);
		// an unchanged head keeps the warm entry derived from it
		if (previous != nullptr) {
			bool equal = Z_OBJ_P(previous) == Z_OBJ_P(head);
			if (!equal && UNEXPECTED(!pt_mutating_scope_equals(Z_OBJ_P(previous), Z_OBJ_P(head), equal))) return false;
			if (equal) return true;
		}

		zval *heads = OBJ_PROP_NUM(self, slots::heads);
		SEPARATE_ARRAY(heads);
		Z_ADDREF_P(head);
		zend_hash_index_update(Z_ARRVAL_P(heads), id, head);
		zval *warmEntries = OBJ_PROP_NUM(self, slots::warmEntries);
		SEPARATE_ARRAY(warmEntries);
		zend_hash_index_del(Z_ARRVAL_P(warmEntries), id);
		return true;
	}

private:
	zend_object *self;

	/* Mirrors isUnreachable(); false = pending exception */
	[[nodiscard]] static bool isUnreachable(zend_object *scope, bool &out)
	{
		out = false;
		zv::Val definedVariables = pt_mutating_scope_get_defined_variables(scope);
		if (UNEXPECTED(definedVariables.isUndef())) return false;
		for (zv::ArrayEntry variable : zv::TableRef(Z_ARRVAL_P(definedVariables.raw()))) {
			zv::Val variableType = pt_mutating_scope_get_variable_type(scope, Z_STR_P(variable.value().raw()));
			if (UNEXPECTED(variableType.isUndef())) return false;
			if (Z_TYPE_P(variableType.raw()) == IS_OBJECT && instanceof_function(Z_OBJCE_P(variableType.raw()), pt_ce_never_type)) {
				out = true;
				return true;
			}
		}

		return true;
	}
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
	if (isNative(heads)) return ConvergedLoopHeads(Z_OBJ_P(heads)).record(loop, head);
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
		if (!ConvergedLoopHeads(Z_OBJ_P(ZEND_THIS)).record(loop, head)) RETURN_THROWS();
	});

	cls.shadow(&pt_ce_converged_loop_heads);
}

/* }}} */
