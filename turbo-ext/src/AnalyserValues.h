/*
 * Inline readers of the analyser value classes the engine trades results
 * with (ExpressionResult.cpp, InternalThrowPoint.cpp, ArgsResult.cpp, ...).
 *
 * The classes are final and their getters return promoted property slots,
 * so a native caller holding an instance of the native class reads the slot
 * in place — borrowed, no call, no addref — and inlines the read. Anything
 * else (the PHP twin declared next to the native class in the differential
 * tests, or an instance whose constructor never ran) takes the cold path:
 * the getter by name, its result kept alive in the caller's `hold`, which
 * also raises the twin's uninitialized-read Error. NULL = pending exception.
 *
 * The factories and the behaviourful entries (toPublic(), resolve(), ...)
 * are declared in support.h.
 */

#ifndef PHPSTANTURBO_ANALYSER_VALUES_H
#define PHPSTANTURBO_ANALYSER_VALUES_H

#include "support.h"
#include "zv.h"
#include "generated/ArgsResult.h"
#include "generated/ExpressionResult.h"
#include "generated/InternalThrowPoint.h"
#include "generated/InternalEndStatementResult.h"
#include "generated/InternalStatementExitPoint.h"
#include "generated/InternalStatementResult.h"
#include "generated/StatementExitPoint.h"
#include "generated/AssignTargetWalkMode.h"
#include "generated/PreparedAssignTarget.h"
#include "generated/TemplateArgumentFrame.h"

zv::Val pt_type_call(zend_object *object, const char *lcname, size_t len, uint32_t argc, zval *argv);

namespace ptav {

/* the declared slot of an instance of exactly ce, borrowed; NULL for any
 * other object or a slot never initialized */
inline zval *slotOf(zval *object, zend_class_entry *ce, uint32_t index)
{
	if (EXPECTED(Z_OBJCE_P(object) == ce)) {
		zval *value = OBJ_PROP_NUM(Z_OBJ_P(object), index);
		if (EXPECTED(Z_TYPE_P(value) != IS_UNDEF)) return value;
	}
	return NULL;
}

/* $object->getter(...$argv) through the engine, the result kept alive in
 * hold; NULL = pending exception */
inline zend_never_inline ZEND_COLD zval *callGetter(zval *object, const char *lcname, size_t len, zv::Val &hold, uint32_t argc = 0, zval *argv = NULL)
{
	hold = pt_type_call(Z_OBJ_P(object), lcname, len, argc, argv);
	return hold.isUndef() ? NULL : hold.raw();
}

/* the slot, or the getter's result */
inline zval *read(zval *object, zend_class_entry *ce, uint32_t index, const char *lcname, size_t len, zv::Val &hold)
{
	zval *value = slotOf(object, ce, index);
	return EXPECTED(value != NULL) ? value : callGetter(object, lcname, len, hold);
}

/* a getter throwing for a null slot: the slot when it holds a value, the
 * getter (and its exception) otherwise */
inline zval *readRequired(zval *object, zend_class_entry *ce, uint32_t index, const char *lcname, size_t len, zv::Val &hold)
{
	zval *value = slotOf(object, ce, index);
	return EXPECTED(value != NULL && Z_TYPE_P(value) != IS_NULL) ? value : callGetter(object, lcname, len, hold);
}

/* a bool getter; false = pending exception */
inline bool readBool(zval *object, zend_class_entry *ce, uint32_t index, const char *lcname, size_t len, bool &out)
{
	zval *value = slotOf(object, ce, index);
	if (EXPECTED(value != NULL)) {
		out = Z_TYPE_P(value) == IS_TRUE;
		return true;
	}
	zv::Val hold;
	value = callGetter(object, lcname, len, hold);
	if (UNEXPECTED(value == NULL)) return false;
	out = zend_is_true(value);
	return true;
}

/* a borrowed read as an owned value (a getter's return value) */
inline zv::Val own(zval *value)
{
	return value != NULL ? zv::Val::copyOf(zv::Ref(value)) : zv::Val();
}

} // namespace ptav

/* {{{ ExpressionResult: $result->getScope() / ->getBeforeScope() /
 * ->getExpr() / ->hasYield() / ->isAlwaysTerminating() / ->getThrowPoints()
 * / ->getImpurePoints() */

inline zval *pt_expression_result_scope(zval *result, zv::Val &hold)
{
	return ptav::read(result, pt_ce_expression_result, ptdecl::ExpressionResult::slot::scope, PT_LC("getscope"), hold);
}

inline zval *pt_expression_result_before_scope(zval *result, zv::Val &hold)
{
	return ptav::read(result, pt_ce_expression_result, ptdecl::ExpressionResult::slot::beforeScope, PT_LC("getbeforescope"), hold);
}

inline zval *pt_expression_result_expr(zval *result, zv::Val &hold)
{
	return ptav::read(result, pt_ce_expression_result, ptdecl::ExpressionResult::slot::expr, PT_LC("getexpr"), hold);
}

inline bool pt_expression_result_has_yield(zval *result, bool &out)
{
	return ptav::readBool(result, pt_ce_expression_result, ptdecl::ExpressionResult::slot::hasYield, PT_LC("hasyield"), out);
}

inline bool pt_expression_result_is_always_terminating(zval *result, bool &out)
{
	return ptav::readBool(result, pt_ce_expression_result, ptdecl::ExpressionResult::slot::isAlwaysTerminating, PT_LC("isalwaysterminating"), out);
}

inline zval *pt_expression_result_throw_points(zval *result, zv::Val &hold)
{
	return ptav::read(result, pt_ce_expression_result, ptdecl::ExpressionResult::slot::throwPoints, PT_LC("getthrowpoints"), hold);
}

inline zval *pt_expression_result_impure_points(zval *result, zv::Val &hold)
{
	return ptav::read(result, pt_ce_expression_result, ptdecl::ExpressionResult::slot::impurePoints, PT_LC("getimpurepoints"), hold);
}

/* }}} */

/* {{{ InternalThrowPoint: $throwPoint->getScope() / ->getType() / ->getNode()
 * / ->isExplicit() / ->canContainAnyThrowable() */

inline zval *pt_internal_throw_point_scope(zval *throwPoint, zv::Val &hold)
{
	return ptav::read(throwPoint, pt_ce_internal_throw_point, ptdecl::InternalThrowPoint::slot::scope, PT_LC("getscope"), hold);
}

inline zval *pt_internal_throw_point_type(zval *throwPoint, zv::Val &hold)
{
	return ptav::read(throwPoint, pt_ce_internal_throw_point, ptdecl::InternalThrowPoint::slot::type, PT_LC("gettype"), hold);
}

inline zval *pt_internal_throw_point_node(zval *throwPoint, zv::Val &hold)
{
	return ptav::read(throwPoint, pt_ce_internal_throw_point, ptdecl::InternalThrowPoint::slot::node, PT_LC("getnode"), hold);
}

inline bool pt_internal_throw_point_is_explicit(zval *throwPoint, bool &out)
{
	return ptav::readBool(throwPoint, pt_ce_internal_throw_point, ptdecl::InternalThrowPoint::slot::explicit_, PT_LC("isexplicit"), out);
}

inline bool pt_internal_throw_point_can_contain_any_throwable(zval *throwPoint, bool &out)
{
	return ptav::readBool(throwPoint, pt_ce_internal_throw_point, ptdecl::InternalThrowPoint::slot::canContainAnyThrowable, PT_LC("cancontainanythrowable"), out);
}

inline bool pt_internal_throw_point_is_from_throw_expr(zval *throwPoint, bool &out)
{
	return ptav::readBool(throwPoint, pt_ce_internal_throw_point, ptdecl::InternalThrowPoint::slot::fromThrowExpr, PT_LC("isfromthrowexpr"), out);
}

/* }}} */

/* {{{ ArgsResult: $argsResult->findArgResult($argValue) (the stored result
 * or a borrowed null) / ->isPassedByReference($arg) /
 * ->getResolvedParametersAcceptor() */

inline zval *pt_args_result_find_arg_result(zval *argsResult, zval *argValue, zv::Val &hold)
{
	zval *argResults = ptav::slotOf(argsResult, pt_ce_args_result, ptdecl::ArgsResult::slot::argResults);
	if (EXPECTED(argResults != NULL)) {
		zval *found = zend_hash_index_find(Z_ARRVAL_P(argResults), Z_OBJ_HANDLE_P(argValue));
		return found != NULL ? found : &EG(uninitialized_zval);
	}
	return ptav::callGetter(argsResult, PT_LC("findargresult"), hold, 1, argValue);
}

inline bool pt_args_result_is_passed_by_reference(zval *argsResult, zval *arg, bool &out)
{
	zval *byRefArguments = ptav::slotOf(argsResult, pt_ce_args_result, ptdecl::ArgsResult::slot::byRefArguments);
	if (EXPECTED(byRefArguments != NULL)) {
		zval *found = zend_hash_index_find(Z_ARRVAL_P(byRefArguments), Z_OBJ_HANDLE_P(arg));
		out = found != NULL && Z_TYPE_P(found) != IS_NULL;
		return true;
	}
	zv::Val hold;
	zval *value = ptav::callGetter(argsResult, PT_LC("ispassedbyreference"), hold, 1, arg);
	if (UNEXPECTED(value == NULL)) return false;
	out = zend_is_true(value);
	return true;
}

inline zval *pt_args_result_resolved_parameters_acceptor(zval *argsResult, zv::Val &hold)
{
	return ptav::read(argsResult, pt_ce_args_result, ptdecl::ArgsResult::slot::resolvedParametersAcceptor, PT_LC("getresolvedparametersacceptor"), hold);
}

/* }}} */

/* {{{ InternalStatementResult: $result->getScope() / ->hasYield() /
 * ->isAlwaysTerminating() / ->isEndReachable() / ->getExitPoints() /
 * ->getThrowPoints() / ->getImpurePoints() / ->getEndStatements() /
 * ->getVariableFlow() */

inline zval *pt_internal_statement_result_scope(zval *result, zv::Val &hold)
{
	return ptav::read(result, pt_ce_internal_statement_result, ptdecl::InternalStatementResult::slot::scope, PT_LC("getscope"), hold);
}

inline bool pt_internal_statement_result_has_yield(zval *result, bool &out)
{
	return ptav::readBool(result, pt_ce_internal_statement_result, ptdecl::InternalStatementResult::slot::hasYield, PT_LC("hasyield"), out);
}

inline bool pt_internal_statement_result_is_always_terminating(zval *result, bool &out)
{
	return ptav::readBool(result, pt_ce_internal_statement_result, ptdecl::InternalStatementResult::slot::isAlwaysTerminating, PT_LC("isalwaysterminating"), out);
}

inline bool pt_internal_statement_result_is_end_reachable(zval *result, bool &out)
{
	return ptav::readBool(result, pt_ce_internal_statement_result, ptdecl::InternalStatementResult::slot::endReachable, PT_LC("isendreachable"), out);
}

inline zval *pt_internal_statement_result_exit_points(zval *result, zv::Val &hold)
{
	return ptav::read(result, pt_ce_internal_statement_result, ptdecl::InternalStatementResult::slot::exitPoints, PT_LC("getexitpoints"), hold);
}

inline zval *pt_internal_statement_result_throw_points(zval *result, zv::Val &hold)
{
	return ptav::read(result, pt_ce_internal_statement_result, ptdecl::InternalStatementResult::slot::throwPoints, PT_LC("getthrowpoints"), hold);
}

inline zval *pt_internal_statement_result_impure_points(zval *result, zv::Val &hold)
{
	return ptav::read(result, pt_ce_internal_statement_result, ptdecl::InternalStatementResult::slot::impurePoints, PT_LC("getimpurepoints"), hold);
}

inline zval *pt_internal_statement_result_end_statements(zval *result, zv::Val &hold)
{
	return ptav::read(result, pt_ce_internal_statement_result, ptdecl::InternalStatementResult::slot::endStatements, PT_LC("getendstatements"), hold);
}

inline zval *pt_internal_statement_result_variable_flow(zval *result, zv::Val &hold)
{
	return ptav::read(result, pt_ce_internal_statement_result, ptdecl::InternalStatementResult::slot::variableFlow, PT_LC("getvariableflow"), hold);
}

/* }}} */

/* {{{ InternalStatementExitPoint / StatementExitPoint: $exitPoint->getStatement()
 * / ->getScope(); InternalEndStatementResult: $endStatement->getStatement() /
 * ->getResult() */

inline zval *pt_internal_statement_exit_point_statement(zval *exitPoint, zv::Val &hold)
{
	return ptav::read(exitPoint, pt_ce_internal_statement_exit_point, ptdecl::InternalStatementExitPoint::slot::statement, PT_LC("getstatement"), hold);
}

inline zval *pt_internal_statement_exit_point_scope(zval *exitPoint, zv::Val &hold)
{
	return ptav::read(exitPoint, pt_ce_internal_statement_exit_point, ptdecl::InternalStatementExitPoint::slot::scope, PT_LC("getscope"), hold);
}

inline zval *pt_statement_exit_point_statement(zval *exitPoint, zv::Val &hold)
{
	return ptav::read(exitPoint, pt_ce_statement_exit_point, ptdecl::StatementExitPoint::slot::statement, PT_LC("getstatement"), hold);
}

inline zval *pt_statement_exit_point_scope(zval *exitPoint, zv::Val &hold)
{
	return ptav::read(exitPoint, pt_ce_statement_exit_point, ptdecl::StatementExitPoint::slot::scope, PT_LC("getscope"), hold);
}

inline zval *pt_internal_end_statement_result_statement(zval *endStatement, zv::Val &hold)
{
	return ptav::read(endStatement, pt_ce_internal_end_statement_result, ptdecl::InternalEndStatementResult::slot::statement, PT_LC("getstatement"), hold);
}

inline zval *pt_internal_end_statement_result_result(zval *endStatement, zv::Val &hold)
{
	return ptav::read(endStatement, pt_ce_internal_end_statement_result, ptdecl::InternalEndStatementResult::slot::result, PT_LC("getresult"), hold);
}

/* }}} */

/* {{{ TemplateArgumentFrame: $frame->isObserving() */

inline bool pt_template_argument_frame_is_observing(zval *frame, bool &out)
{
	zval *resolutions = ptav::slotOf(frame, pt_ce_template_argument_frame, ptdecl::TemplateArgumentFrame::slot::resolutions);
	if (EXPECTED(resolutions != NULL)) {
		out = Z_TYPE_P(resolutions) == IS_NULL;
		return true;
	}
	zv::Val hold;
	zval *value = ptav::callGetter(frame, PT_LC("isobserving"), hold);
	if (UNEXPECTED(value == NULL)) return false;
	out = zend_is_true(value);
	return true;
}

/* }}} */

/* {{{ AssignTargetWalkMode: $mode->enterExpressionAssign() /
 * ->producesTargetReadResult() / ->issetSemanticsForRead() */

inline bool pt_assign_target_walk_mode_enter_expression_assign(zval *mode, bool &out)
{
	return ptav::readBool(mode, pt_ce_assign_target_walk_mode, ptdecl::AssignTargetWalkMode::slot::enterExpressionAssign, PT_LC("enterexpressionassign"), out);
}

inline bool pt_assign_target_walk_mode_produces_target_read_result(zval *mode, bool &out)
{
	return ptav::readBool(mode, pt_ce_assign_target_walk_mode, ptdecl::AssignTargetWalkMode::slot::producesTargetReadResult, PT_LC("producestargetreadresult"), out);
}

inline bool pt_assign_target_walk_mode_isset_semantics_for_read(zval *mode, bool &out)
{
	return ptav::readBool(mode, pt_ce_assign_target_walk_mode, ptdecl::AssignTargetWalkMode::slot::issetSemanticsForRead, PT_LC("issetsemanticsforread"), out);
}

/* }}} */

/* {{{ PreparedAssignTarget: every getter — the bool ones as bools, the
 * kind-specific ones (getRootVar() ... getTargetReadResult()) through the
 * getter when null (its ShouldNotHappenException) */

#define PT_AV_PREPARED_ASSIGN_TARGET(reader, slotName, getter) \
	inline zval *pt_prepared_assign_target_##reader(zval *target, zv::Val &hold) \
	{ \
		return ptav::read(target, pt_ce_prepared_assign_target, ptdecl::PreparedAssignTarget::slot::slotName, PT_LC(getter), hold); \
	}
#define PT_AV_PREPARED_ASSIGN_TARGET_REQUIRED(reader, slotName, getter) \
	inline zval *pt_prepared_assign_target_##reader(zval *target, zv::Val &hold) \
	{ \
		return ptav::readRequired(target, pt_ce_prepared_assign_target, ptdecl::PreparedAssignTarget::slot::slotName, PT_LC(getter), hold); \
	}
#define PT_AV_PREPARED_ASSIGN_TARGET_BOOL(reader, slotName, getter) \
	inline bool pt_prepared_assign_target_##reader(zval *target, bool &out) \
	{ \
		return ptav::readBool(target, pt_ce_prepared_assign_target, ptdecl::PreparedAssignTarget::slot::slotName, PT_LC(getter), out); \
	}

PT_AV_PREPARED_ASSIGN_TARGET(kind, kind, "getkind")
PT_AV_PREPARED_ASSIGN_TARGET(var, var, "getvar")
PT_AV_PREPARED_ASSIGN_TARGET(assigned_expr, assignedExpr, "getassignedexpr")
PT_AV_PREPARED_ASSIGN_TARGET(before_scope, beforeScope, "getbeforescope")
PT_AV_PREPARED_ASSIGN_TARGET(scope, scope, "getscope")
PT_AV_PREPARED_ASSIGN_TARGET_BOOL(enter_expression_assign, enterExpressionAssign, "enterexpressionassign")
PT_AV_PREPARED_ASSIGN_TARGET_BOOL(is_assign_op, isAssignOp, "isassignop")
PT_AV_PREPARED_ASSIGN_TARGET_BOOL(has_yield, hasYield, "hasyield")
PT_AV_PREPARED_ASSIGN_TARGET(throw_points, throwPoints, "getthrowpoints")
PT_AV_PREPARED_ASSIGN_TARGET(impure_points, impurePoints, "getimpurepoints")
PT_AV_PREPARED_ASSIGN_TARGET_BOOL(is_always_terminating, isAlwaysTerminating, "isalwaysterminating")
PT_AV_PREPARED_ASSIGN_TARGET_REQUIRED(root_var, rootVar, "getrootvar")
PT_AV_PREPARED_ASSIGN_TARGET_REQUIRED(var_result, varResult, "getvarresult")
PT_AV_PREPARED_ASSIGN_TARGET_REQUIRED(dim_fetch_stack, dimFetchStack, "getdimfetchstack")
PT_AV_PREPARED_ASSIGN_TARGET_REQUIRED(assigned_property_expr, assignedPropertyExpr, "getassignedpropertyexpr")
PT_AV_PREPARED_ASSIGN_TARGET_REQUIRED(offset_types, offsetTypes, "getoffsettypes")
PT_AV_PREPARED_ASSIGN_TARGET_REQUIRED(offset_native_types, offsetNativeTypes, "getoffsetnativetypes")
PT_AV_PREPARED_ASSIGN_TARGET_REQUIRED(existing_offset_types, existingOffsetTypes, "getexistingoffsettypes")
PT_AV_PREPARED_ASSIGN_TARGET_REQUIRED(existing_offset_native_types, existingOffsetNativeTypes, "getexistingoffsetnativetypes")
PT_AV_PREPARED_ASSIGN_TARGET_REQUIRED(offset_set_target_result, offsetSetTargetResult, "getoffsetsettargetresult")
PT_AV_PREPARED_ASSIGN_TARGET_REQUIRED(object_result, objectResult, "getobjectresult")
PT_AV_PREPARED_ASSIGN_TARGET(property_name, propertyName, "getpropertyname")
PT_AV_PREPARED_ASSIGN_TARGET_REQUIRED(property_holder_type, propertyHolderType, "getpropertyholdertype")
PT_AV_PREPARED_ASSIGN_TARGET_REQUIRED(target_read_result, targetReadResult, "gettargetreadresult")
PT_AV_PREPARED_ASSIGN_TARGET(target_chain_results, targetChainResults, "gettargetchainresults")
PT_AV_PREPARED_ASSIGN_TARGET(variable_name_result, variableNameResult, "getvariablenameresult")

#undef PT_AV_PREPARED_ASSIGN_TARGET
#undef PT_AV_PREPARED_ASSIGN_TARGET_REQUIRED
#undef PT_AV_PREPARED_ASSIGN_TARGET_BOOL

/* }}} */

#endif
