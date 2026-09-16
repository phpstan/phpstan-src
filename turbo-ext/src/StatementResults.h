/*
 * The exit-point walks StatementResult and InternalStatementResult share —
 * the twins repeat getExitPointsByType(), getExitPointsForOuterLoop() and
 * the filterOutLoopExitPoints() scan verbatim over their own exit point
 * classes; here they are written once over the exit point flavour
 * (StatementResult.cpp, InternalStatementResult.cpp).
 */

#ifndef PHPSTANTURBO_STATEMENT_RESULTS_H
#define PHPSTANTURBO_STATEMENT_RESULTS_H

#include "support.h"
#include "zv.h"
#include "AnalyserValues.h"
#include "TypeTraits.h"

namespace ptsr {

/* the exit point class a result holds: its readers and its constructor */
struct ExitPointFlavour
{
	zval *(*statement)(zval *exitPoint, zv::Val &hold);
	zval *(*scope)(zval *exitPoint, zv::Val &hold);
	zv::Val (*create)(zval *statement, zval *scope);
};

/* $exitPoint->method() on a non-object: the engine's Error; NULL */
inline zval *memberCallOnNonObject(const char *method, zval *value)
{
	zend_throw_error(NULL, "Call to a member function %s() on %s", method, zend_zval_value_name(value));
	return NULL;
}

/* $exitPoint->getStatement(), borrowed (kept alive in hold); NULL = pending
 * exception */
inline zval *statementOf(const ExitPointFlavour &flavour, zval *exitPoint, zv::Val &hold)
{
	if (UNEXPECTED(Z_TYPE_P(exitPoint) != IS_OBJECT)) return memberCallOnNonObject("getStatement", exitPoint);
	return flavour.statement(exitPoint, hold);
}

/* $exitPoint->getScope(), borrowed (kept alive in hold); NULL = pending
 * exception */
inline zval *scopeOf(const ExitPointFlavour &flavour, zval *exitPoint, zv::Val &hold)
{
	if (UNEXPECTED(Z_TYPE_P(exitPoint) != IS_OBJECT)) return memberCallOnNonObject("getScope", exitPoint);
	return flavour.scope(exitPoint, hold);
}

/* $statement->num, dereferenced: the declared slot of Break_ / Continue_,
 * through the engine's read otherwise (a dynamic property, or null with the
 * "Undefined property" warning — the twins' contract names Break_ and
 * Continue_ only); rv holds an engine-read value; NULL = pending exception */
inline zval *numOf(zval *statement, zval &rv)
{
	ZVAL_UNDEF(&rv);
	zv::Ref num = zv::ObjRef(Z_OBJ_P(statement)).prop(PT_LC("num"));
	if (EXPECTED(num.raw() != NULL)) return num.deref().raw();
	zval *value = zend_read_property(Z_OBJCE_P(statement), Z_OBJ_P(statement), PT_LC("num"), false, &rv);
	if (UNEXPECTED(EG(exception))) return NULL;
	ZVAL_DEREF(value);
	return value;
}

/* $value instanceof Int_ ? $value->value : absent; false = not an Int_ */
inline bool intValueOf(zval *value, zend_class_entry *intCe, zend_long &out)
{
	if (value == NULL || Z_TYPE_P(value) != IS_OBJECT || !instanceof_function(Z_OBJCE_P(value), intCe)) return false;
	zv::Ref intValue = zv::ObjRef(Z_OBJ_P(value)).prop(PT_LC("value"));
	out = intValue.raw() != NULL ? zval_get_long(intValue.deref().raw()) : 0;
	return true;
}

/* Mirrors getExitPointsByType(): the list of the exit points whose statement
 * is a $stmtClass leaving the innermost loop; UNDEF = pending exception */
inline zv::Val exitPointsByType(const ExitPointFlavour &flavour, HashTable *exitPoints, zend_class_entry *stmtClass)
{
	zend_class_entry *intCe = pt_class(PT_CLASS_SCALAR_INT);
	if (UNEXPECTED(intCe == NULL)) return zv::Val();
	zv::Arr result = zv::Arr::empty();
	for (auto entry : zv::TableRef(exitPoints)) {
		zval *exitPoint = entry.value().deref().raw();
		zv::Val hold;
		zval *statement = statementOf(flavour, exitPoint, hold);
		if (UNEXPECTED(statement == NULL)) return zv::Val();
		if (stmtClass == NULL || Z_TYPE_P(statement) != IS_OBJECT || !instanceof_function(Z_OBJCE_P(statement), stmtClass)) continue;

		zval rv;
		zval *value = numOf(statement, rv);
		zv::Val rvHold = zv::Val::adopt(rv);
		if (UNEXPECTED(value == NULL)) return zv::Val();
		zend_long intValue;
		if (Z_TYPE_P(value) != IS_NULL && intValueOf(value, intCe, intValue) && intValue != 1) continue;

		result.push(zv::Ref(exitPoint));
	}
	return zv::Val(std::move(result));
}

/* Mirrors getExitPointsForOuterLoop(); UNDEF = pending exception */
inline zv::Val exitPointsForOuterLoop(const ExitPointFlavour &flavour, HashTable *exitPoints)
{
	zend_class_entry *intCe = pt_class(PT_CLASS_SCALAR_INT);
	zend_class_entry *continueCe = pt_class(PT_CLASS_CONTINUE_STMT);
	zend_class_entry *breakCe = pt_class(PT_CLASS_BREAK_STMT);
	if (UNEXPECTED(intCe == NULL || continueCe == NULL || breakCe == NULL)) return zv::Val();
	zv::Arr result = zv::Arr::empty();
	for (auto entry : zv::TableRef(exitPoints)) {
		zval *exitPoint = entry.value().deref().raw();
		zv::Val hold;
		zval *statement = statementOf(flavour, exitPoint, hold);
		if (UNEXPECTED(statement == NULL)) return zv::Val();
		bool isContinue = Z_TYPE_P(statement) == IS_OBJECT && instanceof_function(Z_OBJCE_P(statement), continueCe);
		if (!isContinue && (Z_TYPE_P(statement) != IS_OBJECT || !instanceof_function(Z_OBJCE_P(statement), breakCe))) {
			result.push(zv::Ref(exitPoint));
			continue;
		}
		zval rv;
		zval *num = numOf(statement, rv);
		zv::Val rvHold = zv::Val::adopt(rv);
		if (UNEXPECTED(num == NULL)) return zv::Val();
		if (Z_TYPE_P(num) == IS_NULL) continue;
		zend_long value;
		if (!intValueOf(num, intCe, value)) continue;
		if (value == 1) continue;

		zv::Val newNode = zv::Val::null();
		if (value > 2) {
			zval newValue;
			ZVAL_LONG(&newValue, value - 1);
			newNode = pt_type_new(PT_CLASS_SCALAR_INT, 1, &newValue);
			if (UNEXPECTED(newNode.isUndef())) return zv::Val();
		}
		zv::Val newStatement = pt_type_new(isContinue ? PT_CLASS_CONTINUE_STMT : PT_CLASS_BREAK_STMT, 1, newNode.raw());
		if (UNEXPECTED(newStatement.isUndef())) return zv::Val();

		zv::Val scopeHold;
		zval *scope = scopeOf(flavour, exitPoint, scopeHold);
		if (UNEXPECTED(scope == NULL)) return zv::Val();
		zv::Val newExitPoint = flavour.create(newStatement.raw(), scope);
		if (UNEXPECTED(newExitPoint.isUndef())) return zv::Val();
		result.push(std::move(newExitPoint));
	}
	return zv::Val(std::move(result));
}

/* The scan of filterOutLoopExitPoints() on an always-terminating result:
 * 1 when an exit point leaves exactly this loop (the twin returns the
 * non-terminating copy), 0 when none does ($this), -1 = pending exception */
[[nodiscard]] inline int leavesThisLoop(const ExitPointFlavour &flavour, HashTable *exitPoints)
{
	zend_class_entry *intCe = pt_class(PT_CLASS_SCALAR_INT);
	zend_class_entry *continueCe = pt_class(PT_CLASS_CONTINUE_STMT);
	zend_class_entry *breakCe = pt_class(PT_CLASS_BREAK_STMT);
	if (UNEXPECTED(intCe == NULL || continueCe == NULL || breakCe == NULL)) return -1;
	for (auto entry : zv::TableRef(exitPoints)) {
		zval *exitPoint = entry.value().deref().raw();
		zv::Val hold;
		zval *statement = statementOf(flavour, exitPoint, hold);
		if (UNEXPECTED(statement == NULL)) return -1;
		if (Z_TYPE_P(statement) != IS_OBJECT || (!instanceof_function(Z_OBJCE_P(statement), breakCe) && !instanceof_function(Z_OBJCE_P(statement), continueCe))) continue;

		zval rv;
		zval *num = numOf(statement, rv);
		zv::Val rvHold = zv::Val::adopt(rv);
		if (UNEXPECTED(num == NULL)) return -1;
		zend_long value;
		if (!intValueOf(num, intCe, value)) return 1;
		if (value != 1) continue;

		return 1;
	}
	return 0;
}

} // namespace ptsr

#endif
