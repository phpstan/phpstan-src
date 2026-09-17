/*
 * The shared helpers of the native loop and control-flow statement handlers
 * (WhileHandler.cpp, DoWhileHandler.cpp, ForHandler.cpp, SwitchHandler.cpp,
 * TryCatchHandler.cpp, ForeachHandler.cpp): the convergence-pass pieces they
 * all spell the same way (the condition's boolean projection, the pass
 * callbacks, the array merges of throw and impure points, the break /
 * continue exit points) and their calls into the analyser classes that are
 * not ported yet (LoopWrittenVariableNames, php-parser's Name, the DI
 * container) — one inline helper per called method over one shared cached
 * method site, so a later port switches every handler in one place.
 */

#ifndef PHPSTANTURBO_LOOP_HANDLER_CALLS_H
#define PHPSTANTURBO_LOOP_HANDLER_CALLS_H

#include "support.h"
#include "zv.h"
#include "TypeTraits.h"
#include "TypeOps.h"
#include "Engine.h"
#include "AnalyserValues.h"
#include "StmtHandlerCalls.h"

/* NodeScopeResolver::LOOP_SCOPE_ITERATIONS / ::GENERALIZE_AFTER_ITERATION */
#define PT_LH_LOOP_SCOPE_ITERATIONS_LIMIT 3
#define PT_LH_GENERALIZE_AFTER_ITERATION_LIMIT 1

namespace ptlh {

/* {{{ the PHP collaborators */

inline pt_method_site nameToLowerStringSite;
inline pt_method_site containerGetByTypeSite;

/* LoopWrittenVariableNames::collect($loop, $passFlow) ($passFlow IS_NULL
 * for null) */
inline zv::Val loopWrittenVariableNames(zval *loop, zval *passFlow)
{
	return pt_loop_written_variable_names_collect(loop, passFlow);
}

/* $name->toLowerString() */
inline zv::Val nameToLowerString(zval *name)
{
	return pt_call_method_cached(nameToLowerStringSite, Z_OBJ_P(name), PT_LC("tolowerstring"), 0, NULL);
}

/* $container->getByType($className) for a permanent interned class name */
inline zv::Val containerGetByType(zval *container, zend_string *className)
{
	zval name;
	ZVAL_INTERNED_STR(&name, className);
	return pt_call_method_cached(containerGetByTypeSite, Z_OBJ_P(container), PT_LC("getbytype"), 1, &name);
}

/* }}} */

/* $prevScope->generalizeWith($bodyScope, LoopWrittenVariableNames::collect($stmt, $passFlow)) */
inline zv::Val generalizeWithWrittenNames(zval *prevScope, zval *bodyScope, zval *stmt, zval *passFlow)
{
	zv::Val names = loopWrittenVariableNames(stmt, passFlow);
	if (UNEXPECTED(names.isUndef())) return zv::Val();
	return pt_mutating_scope_generalize_with_names(Z_OBJ_P(prevScope), Z_OBJ_P(bodyScope), names.raw());
}

/* new NoopNodeCallback() / new RecordingNodeCallback() */
inline zv::Val newNoopNodeCallback()
{
	return pt_type_new(PT_CLASS_NOOP_NODE_CALLBACK, 0, NULL);
}

inline zv::Val newRecordingNodeCallback()
{
	return pt_type_new_ce(pt_ce_recording_node_callback, 0, NULL);
}

/* $bodyIsReplayable ? new RecordingNodeCallback() : new NoopNodeCallback() */
inline zv::Val newPassNodeCallback(bool replayable)
{
	return replayable ? newRecordingNodeCallback() : newNoopNodeCallback();
}

/* $into = array_merge($into, $more): the array itself when $into is empty and
 * $more a list without holes (what array_merge() returns then), appended
 * otherwise; false = pending exception */
[[nodiscard]] inline bool mergeInto(zv::Arr &into, zval *more)
{
	if (EXPECTED(Z_TYPE_P(more) == IS_ARRAY)) {
		HashTable *moreTable = Z_ARRVAL_P(more);
		uint32_t count = zend_hash_num_elements(moreTable);
		if (count == 0) return true;
		if (zend_hash_num_elements(into.table()) == 0 && HT_IS_PACKED(moreTable) && moreTable->nNumUsed == count) {
			into = zv::Arr::copyOfTable(moreTable);
			return true;
		}
	}
	return pt_callable_array_merge_into(into, more);
}

/* an owned array copy of a borrowed read (NULL = pending exception) */
[[nodiscard]] inline bool arrayOf(zval *value, zv::Arr &out)
{
	if (UNEXPECTED(value == NULL)) return false;
	if (UNEXPECTED(Z_TYPE_P(value) != IS_ARRAY)) {
		zend_type_error("array_merge(): Argument #1 must be of type array, %s given", zend_zval_value_name(value));
		return false;
	}
	out = zv::Arr::copyOfTable(Z_ARRVAL_P(value));
	return true;
}

/* the boolean projection of a condition's type:
 * ($treatPhpDocTypesAsCertain ? $result->getType() : $result->getNativeType())->toBoolean()
 * with its isTrue() / isFalse() answers (both pure; asked once each) */
struct ConditionBoolean
{
	zend_long isTrue = PT_TRI_NO;
	zend_long isFalse = PT_TRI_NO;
};

[[nodiscard]] inline bool conditionBooleanOfType(zval *type, ConditionBoolean &out)
{
	if (UNEXPECTED(Z_TYPE_P(type) != IS_OBJECT)) {
		zend_throw_error(NULL, "Call to a member function toBoolean() on %s", zend_zval_value_name(type));
		return false;
	}
	zv::Val boolean = pt_type_call(Z_OBJ_P(type), PT_LC("toboolean"), 0, NULL);
	if (UNEXPECTED(boolean.isUndef())) return false;
	if (UNEXPECTED(Z_TYPE_P(boolean.raw()) != IS_OBJECT)) {
		zend_throw_error(NULL, "Call to a member function isTrue() on %s", zend_zval_value_name(boolean.raw()));
		return false;
	}
	out.isTrue = pt_type_call_trinary(Z_OBJ_P(boolean.raw()), PT_LC("istrue"), 0, NULL);
	if (UNEXPECTED(out.isTrue < 0)) return false;
	out.isFalse = pt_type_call_trinary(Z_OBJ_P(boolean.raw()), PT_LC("isfalse"), 0, NULL);
	return out.isFalse >= 0;
}

[[nodiscard]] inline bool conditionBoolean(zval *result, bool treatPhpDocTypesAsCertain, ConditionBoolean &out)
{
	zv::Val type = treatPhpDocTypesAsCertain ? pt_expression_result_get_type(result) : pt_expression_result_get_native_type(result);
	if (UNEXPECTED(type.isUndef())) return false;
	return conditionBooleanOfType(type.raw(), out);
}

/* the node class-map entries of Break_ / Continue_ (NULL = pending
 * exception) */
inline zend_class_entry *breakClass()
{
	return pt_class(PT_CLASS_BREAK_STMT);
}

inline zend_class_entry *continueClass()
{
	return pt_class(PT_CLASS_CONTINUE_STMT);
}

/* $result->getExitPointsByType(Break_::class) / (Continue_::class) */
inline zv::Val breakExitPoints(zval *result)
{
	zend_class_entry *ce = breakClass();
	if (UNEXPECTED(ce == NULL)) return zv::Val();
	return pt_internal_statement_result_exit_points_by_type(result, ce);
}

inline zv::Val continueExitPoints(zval *result)
{
	zend_class_entry *ce = continueClass();
	if (UNEXPECTED(ce == NULL)) return zv::Val();
	return pt_internal_statement_result_exit_points_by_type(result, ce);
}

/* count() of a list the value classes returned */
inline uint32_t countOf(zval *list)
{
	return Z_TYPE_P(list) == IS_ARRAY ? zend_hash_num_elements(Z_ARRVAL_P(list)) : 0;
}

/* $scope = $scope === null ? $other : $scope->mergeWith($other) — `scope`
 * null or UNDEF for null; false = pending exception */
[[nodiscard]] inline bool mergeOrTake(zv::Val &scope, zval *other)
{
	if (scope.isNull()) {
		scope = zv::Val::copyOf(zv::Ref(other));
		return true;
	}
	zv::Val merged = pt_mutating_scope_merge_with(Z_OBJ_P(scope.raw()), other);
	if (UNEXPECTED(merged.isUndef())) return false;
	scope = std::move(merged);
	return true;
}

/* $scope = $other->mergeWith($scope) ($scope null or UNDEF for null); false
 * = pending exception */
[[nodiscard]] inline bool otherMergeWith(zval *other, zv::Val &scope)
{
	zv::Val merged = pt_mutating_scope_merge_with(Z_OBJ_P(other), scope.isUndef() ? NULL : scope.raw());
	if (UNEXPECTED(merged.isUndef())) return false;
	scope = std::move(merged);
	return true;
}

/* $a->equals($b); false = pending exception */
[[nodiscard]] inline bool scopesEqual(zval *a, zval *b, bool &out)
{
	return pt_mutating_scope_equals(Z_OBJ_P(a), Z_OBJ_P(b), out);
}

/* the twin's VariableWrite::KIND_* values the handlers pass */
inline constexpr zend_long PT_LH_WRITE_KIND_FOREACH_VALUE = 9;
inline constexpr zend_long PT_LH_WRITE_KIND_FOREACH_KEY = 10;
inline constexpr zend_long PT_LH_WRITE_KIND_CATCH = 11;

/* $write->isOffsetWrite() and $write->getId() of a VariableWrite (the slots
 * of exactly that class, the getters otherwise); false = pending exception */
[[nodiscard]] inline bool variableWriteInfo(zval *write, bool &isOffsetWrite, zend_long &id)
{
	if (UNEXPECTED(Z_TYPE_P(write) != IS_OBJECT)) {
		zend_throw_error(NULL, "Call to a member function isOffsetWrite() on %s", zend_zval_value_name(write));
		return false;
	}
	bool error;
	const pt_variable_write_slots *slots = pt_variable_write_slots_of(Z_OBJ_P(write), error);
	if (EXPECTED(slots != NULL)) {
		isOffsetWrite = Z_TYPE_P(OBJ_PROP(Z_OBJ_P(write), slots->offsetWrite)) == IS_TRUE;
		id = Z_LVAL_P(OBJ_PROP(Z_OBJ_P(write), slots->id));
		return true;
	}
	if (UNEXPECTED(error)) return false;
	zv::Val offsetWrite = pt_type_call(Z_OBJ_P(write), PT_LC("isoffsetwrite"), 0, NULL);
	if (UNEXPECTED(offsetWrite.isUndef())) return false;
	isOffsetWrite = zend_is_true(offsetWrite.raw());
	if (isOffsetWrite) return true;
	zv::Val idValue = pt_type_call(Z_OBJ_P(write), PT_LC("getid"), 0, NULL);
	if (UNEXPECTED(idValue.isUndef())) return false;
	id = zval_get_long(idValue.raw());
	return true;
}

/* $this->promotedBool of a handler (a typed bool slot) */
inline bool boolSlot(zend_object *handler, uint32_t slot)
{
	return Z_TYPE_P(OBJ_PROP_NUM(handler, slot)) == IS_TRUE;
}

} // namespace ptlh

#endif /* PHPSTANTURBO_LOOP_HANDLER_CALLS_H */
