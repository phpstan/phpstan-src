/*
 * PHPStanTurbo\TryCatchHandler — native implementation of
 * PHPStan\Analyser\StmtHandler\TryCatchHandler.
 *
 * A DI service (#[AutowiredService]): the constructor keeps the twin's
 * arginfo so Nette autowires it. processStmt() is registered as the class's
 * statement-handler entry (Engine.h).
 *
 * The try, catch and finally blocks are walked through NodeScopeResolver's
 * direct entries; InternalThrowPoint, InternalStatementExitPoint,
 * MutatingScope, the Type kernel (TypeCombinator, ObjectType,
 * isSuperTypeOf()), StatementsHandler, VariableFlow, VariableFlowBuilder
 * and the statement results through theirs. The twin's per-catch
 * `$matchingCatchTypes` flags are a flat vector of bools; the
 * `new ObjectType(Throwable|Exception|Error::class)` probes are created
 * where the twin creates them.
 */

#include "support.h"
#include "generated/TryCatchHandler.h"

namespace slots = ptdecl::TryCatchHandler::slot;
namespace sigs = ptdecl::TryCatchHandler::sig;
#include "zv.h"
#include "TypeTraits.h"
#include "TypeOps.h"
#include "Engine.h"
#include "AnalyserValues.h"
#include "StmtHandlerCalls.h"
#include "LoopHandlerCalls.h"

#include <algorithm>
#include <vector>

zend_class_entry *pt_ce_try_catch_handler = nullptr;

namespace {

pt_property_site pt_tch_stmts_site;
pt_property_site pt_tch_catches_site;
pt_property_site pt_tch_finally_site;
pt_property_site pt_tch_catch_types_site;
pt_property_site pt_tch_catch_var_site;
pt_property_site pt_tch_catch_stmts_site;
pt_property_site pt_tch_finally_stmts_site;
pt_property_site pt_tch_variable_name_site;
pt_property_site pt_tch_expression_expr_site;
pt_property_site pt_tch_return_expr_site;
pt_method_site pt_tch_name_to_string_site;

/* the class names of the twin's `new ObjectType(...)` probes (module
 * startup) */
zend_string *pt_tch_throwable_class = nullptr;
zend_string *pt_tch_exception_class = nullptr;
zend_string *pt_tch_error_class = nullptr;

/* $name->toString() */
zv::Val nameToString(zval *name)
{
	return pt_call_method_cached(pt_tch_name_to_string_site, Z_OBJ_P(name), PT_LC("tostring"), 0, NULL);
}

/* new ObjectType($className) */
zv::Val newObjectType(zend_string *className)
{
	zval type;
	if (UNEXPECTED(!pt_object_type_new(&type, className))) return zv::Val();
	return zv::Val::adopt(type);
}

/* $type->isSuperTypeOf($other) as a PT_TRI_* value; -1 = pending exception */
zend_long isSuperTypeOf(zval *type, zval *other)
{
	if (UNEXPECTED(Z_TYPE_P(type) != IS_OBJECT)) {
		zend_throw_error(NULL, "Call to a member function isSuperTypeOf() on %s", zend_zval_value_name(type));
		return -1;
	}
	zv::Val result = pt_type_op(Z_OBJ_P(type), PT_OP_IS_SUPER_TYPE_OF, 1, other);
	if (UNEXPECTED(result.isUndef())) return -1;
	if (UNEXPECTED(Z_TYPE_P(result.raw()) != IS_OBJECT)) {
		zend_throw_error(NULL, "Call to a member function yes() on %s", zend_zval_value_name(result.raw()));
		return -1;
	}
	return pt_result_value(Z_OBJ_P(result.raw()));
}

/* $type->isSuperTypeOf(new ObjectType($className))->yes(); false = pending
 * exception */
[[nodiscard]] bool isSuperTypeOfClass(zval *type, zend_string *className, bool &out)
{
	zv::Val probe = newObjectType(className);
	if (UNEXPECTED(probe.isUndef())) return false;
	zend_long value = isSuperTypeOf(type, probe.raw());
	if (UNEXPECTED(value < 0)) return false;
	out = value == PT_TRI_YES;
	return true;
}

/* $type instanceof NeverType */
inline bool isNeverType(zval *type)
{
	return Z_TYPE_P(type) == IS_OBJECT && instanceof_function(Z_OBJCE_P(type), pt_ce_never_type);
}

/* $exitPoint->getStatement() instanceof Stmt\Expression &&
 * $exitPoint->getStatement()->expr instanceof Expr\Throw_; false = pending
 * exception */
[[nodiscard]] bool isThrowStatement(zval *statement, bool &out)
{
	bool error = false;
	out = false;
	if (!ptsh::isInstanceOf(statement, PT_CLASS_EXPRESSION_STMT, error)) return !error;
	zval *expr = ptsh::readNodeProperty(pt_tch_expression_expr_site, statement, PT_LC("expr"));
	if (UNEXPECTED(expr == NULL)) return false;
	out = ptsh::isInstanceOf(expr, PT_CLASS_THROW_EXPR, error);
	return !error;
}

} // namespace

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\StmtHandler\TryCatchHandler; UNDEF = pending
 * exception. */
class TryCatchHandler
{
public:
	explicit TryCatchHandler(zend_object *self) : self(self) {}

	/* the constructor body: the promoted property */
	void construct(zval *statementsHandler)
	{
		zv::ObjRef(self).propAtWrite(slots::statementsHandler, zv::Val::copyOf(zv::Ref(statementsHandler)));
	}

	/* Mirrors supports(); false = pending exception */
	[[nodiscard]] bool supports(zval *stmt, bool &out) const
	{
		bool error = false;
		out = ptsh::isInstanceOf(stmt, PT_CLASS_TRY_CATCH_STMT, error);
		return !error;
	}

	/* Mirrors processStmt(). */
	zv::Val processStmt(zval *nodeScopeResolver, zval *stmt, zval *scope, zval *storage, zval *nodeCallback, zval *context) const
	{
		zv::Arr catchFlows = zv::Arr::empty();
		zv::Val finallyFlow = zv::Val::null();
		zv::Val branchScopeResult;
		{
			zval *stmts = ptsh::readNodeProperty(pt_tch_stmts_site, stmt, PT_LC("stmts"));
			if (UNEXPECTED(stmts == NULL)) return zv::Val();
			zv::Val stmtsHold = zv::Val::copyOf(zv::Ref(stmts));
			branchScopeResult = pt_node_scope_resolver_process_stmt_nodes_internal(nodeScopeResolver, stmt, stmtsHold.raw(), scope, storage, nodeCallback, context);
			if (UNEXPECTED(branchScopeResult.isUndef())) return zv::Val();
		}
		zval *branch = branchScopeResult.raw();
		zv::Val branchScope;
		{
			zv::Val hold;
			zval *resultScope = pt_internal_statement_result_scope(branch, hold);
			if (UNEXPECTED(resultScope == NULL)) return zv::Val();
			branchScope = zv::Val::copyOf(zv::Ref(resultScope));
		}
		bool alwaysTerminating;
		if (UNEXPECTED(!pt_internal_statement_result_is_always_terminating(branch, alwaysTerminating))) return zv::Val();
		zv::Val finalScope = alwaysTerminating ? zv::Val::null() : zv::Val::copyOf(branchScope.ref());

		zv::Arr exitPoints = zv::Arr::empty();
		zv::Arr finallyExitPoints = zv::Arr::empty();
		bool hasYield;
		if (UNEXPECTED(!pt_internal_statement_result_has_yield(branch, hasYield))) return zv::Val();

		zval *finallyNode = ptsh::readNodeProperty(pt_tch_finally_site, stmt, PT_LC("finally"));
		if (UNEXPECTED(finallyNode == NULL)) return zv::Val();
		zv::Val finallyScope = Z_TYPE_P(finallyNode) != IS_NULL ? zv::Val::copyOf(branchScope.ref()) : zv::Val::null();
		{
			zv::Val hold;
			zval *branchExitPoints = pt_internal_statement_result_exit_points(branch, hold);
			if (UNEXPECTED(branchExitPoints == NULL)) return zv::Val();
			if (UNEXPECTED(!collectExitPoints(branchExitPoints, finallyExitPoints, finallyScope, exitPoints))) return zv::Val();
		}

		zv::Arr throwPoints, impurePoints;
		{
			zv::Val hold;
			if (UNEXPECTED(!ptlh::arrayOf(pt_internal_statement_result_throw_points(branch, hold), throwPoints))) return zv::Val();
		}
		{
			zv::Val hold;
			if (UNEXPECTED(!ptlh::arrayOf(pt_internal_statement_result_impure_points(branch, hold), impurePoints))) return zv::Val();
		}
		zv::Arr throwPointsForLater = zv::Arr::empty();
		zv::Val pastCatchTypes = pt_type_new_never_type();
		if (UNEXPECTED(pastCatchTypes.isUndef())) return zv::Val();

		zval *catches = ptsh::readNodeProperty(pt_tch_catches_site, stmt, PT_LC("catches"));
		if (UNEXPECTED(catches == NULL)) return zv::Val();
		if (UNEXPECTED(Z_TYPE_P(catches) != IS_ARRAY)) {
			zend_error(E_WARNING, "foreach() argument must be of type array|object, %s given", zend_zval_value_name(catches));
			return zv::Val();
		}
		zv::Val catchesHold = zv::Val::copyOf(zv::Ref(catches));
		for (auto catchEntry : zv::ArrRef(catchesHold.raw())) {
			zval *catchNode = catchEntry.value().deref().raw();
			if (UNEXPECTED(!pt_node_scope_resolver_call_node_callback(nodeScopeResolver, nodeCallback, catchNode, scope, storage))) return zv::Val();

			zv::Arr originalCatchTypes = zv::Arr::empty();
			zv::Arr catchTypes = zv::Arr::empty();
			{
				zval *types = ptsh::readNodeProperty(pt_tch_catch_types_site, catchNode, PT_LC("types"));
				if (UNEXPECTED(types == NULL)) return zv::Val();
				if (UNEXPECTED(Z_TYPE_P(types) != IS_ARRAY)) {
					zend_error(E_WARNING, "foreach() argument must be of type array|object, %s given", zend_zval_value_name(types));
					return zv::Val();
				}
				zv::Val typesHold = zv::Val::copyOf(zv::Ref(types));
				for (auto typeEntry : zv::ArrRef(typesHold.raw())) {
					zval *catchNodeType = typeEntry.value().deref().raw();
					if (UNEXPECTED(Z_TYPE_P(catchNodeType) != IS_OBJECT)) {
						zend_throw_error(NULL, "Call to a member function toString() on %s", zend_zval_value_name(catchNodeType));
						return zv::Val();
					}
					zv::Val className = nameToString(catchNodeType);
					if (UNEXPECTED(className.isUndef())) return zv::Val();
					if (UNEXPECTED(Z_TYPE_P(className.raw()) != IS_STRING)) {
						zend_type_error("PHPStan\\Type\\ObjectType::__construct(): Argument #1 ($className) must be of type string, %s given", zend_zval_value_name(className.raw()));
						return zv::Val();
					}
					zv::Val catchType = newObjectType(Z_STR_P(className.raw()));
					if (UNEXPECTED(catchType.isUndef())) return zv::Val();
					originalCatchTypes.push(zv::Ref(catchType.raw()));
					zv::Val removed = pt_type_combinator_remove(catchType.raw(), pastCatchTypes.raw());
					if (UNEXPECTED(removed.isUndef())) return zv::Val();
					catchTypes.push(std::move(removed));
				}
			}

			zv::Val originalCatchType = pt_type_combinator_union(zend_hash_num_elements(originalCatchTypes.table()), packedArgv(originalCatchTypes));
			if (UNEXPECTED(originalCatchType.isUndef())) return zv::Val();
			zv::Val catchType = pt_type_combinator_union(zend_hash_num_elements(catchTypes.table()), packedArgv(catchTypes));
			if (UNEXPECTED(catchType.isUndef())) return zv::Val();
			{
				zv::Args unionArgv{pastCatchTypes.raw(), originalCatchType.raw()};
				zv::Val past = pt_type_combinator_union(2, unionArgv);
				if (UNEXPECTED(past.isUndef())) return zv::Val();
				pastCatchTypes = std::move(past);
			}

			/* $matchingThrowPoints (keyed like the twin's) and $matchingCatchTypes */
			zv::Arr matchingThrowPoints = zv::Arr::empty();
			uint32_t catchTypeCount = zend_hash_num_elements(originalCatchTypes.table());
			std::vector<bool> matchingCatchTypes(catchTypeCount, false);

			// throwable matches all
			for (uint32_t catchTypeIndex = 0; catchTypeIndex < catchTypeCount; catchTypeIndex++) {
				zval *catchTypeItem = zend_hash_index_find(originalCatchTypes.table(), catchTypeIndex);
				bool matchesThrowable;
				if (UNEXPECTED(!isSuperTypeOfClass(catchTypeItem, pt_tch_throwable_class, matchesThrowable))) return zv::Val();
				if (!matchesThrowable) continue;
				for (auto throwEntry : zv::ArrRef(throwPoints.raw())) {
					setMatching(matchingThrowPoints, throwEntry);
					matchingCatchTypes[catchTypeIndex] = true;
				}
			}

			// explicit only
			bool onlyExplicitIsThrow = true;
			if (zend_hash_num_elements(matchingThrowPoints.table()) == 0) {
				for (auto throwEntry : zv::ArrRef(throwPoints.raw())) {
					zval *throwPoint = throwEntry.value().deref().raw();
					zv::Val typeHold;
					zval *throwType = pt_internal_throw_point_type(throwPoint, typeHold);
					if (UNEXPECTED(throwType == NULL)) return zv::Val();
					if (isNeverType(throwType)) continue;
					for (uint32_t catchTypeIndex = 0; catchTypeIndex < catchTypeCount; catchTypeIndex++) {
						zval *catchTypeItem = zend_hash_index_find(catchTypes.table(), catchTypeIndex);
						zv::Val againTypeHold;
						zval *againType = pt_internal_throw_point_type(throwPoint, againTypeHold);
						if (UNEXPECTED(againType == NULL)) return zv::Val();
						zend_long superType = isSuperTypeOf(catchTypeItem, againType);
						if (UNEXPECTED(superType < 0)) return zv::Val();
						if (superType == PT_TRI_NO) continue;

						matchingCatchTypes[catchTypeIndex] = true;
						bool isExplicit;
						if (UNEXPECTED(!pt_internal_throw_point_is_explicit(throwPoint, isExplicit))) return zv::Val();
						if (!isExplicit) continue;
						zv::Val nodeHold;
						zval *throwNode = pt_internal_throw_point_node(throwPoint, nodeHold);
						if (UNEXPECTED(throwNode == NULL)) return zv::Val();
						bool error = false;
						bool isThrow = ptsh::isInstanceOf(throwNode, PT_CLASS_THROW_EXPR, error);
						if (UNEXPECTED(error)) return zv::Val();
						if (!isThrow) {
							if (UNEXPECTED(!isThrowStatement(throwNode, isThrow))) return zv::Val();
						}
						if (!isThrow) {
							onlyExplicitIsThrow = false;
						}

						setMatching(matchingThrowPoints, throwEntry);
					}
				}
			}

			// implicit only
			// Broad catches also cover undocumented exceptions when a documented throw matches.
			bool implicitOnly = zend_hash_num_elements(matchingThrowPoints.table()) == 0 || onlyExplicitIsThrow;
			if (!implicitOnly) {
				if (UNEXPECTED(!isSuperTypeOfClass(originalCatchType.raw(), pt_tch_exception_class, implicitOnly))) return zv::Val();
			}
			if (!implicitOnly) {
				if (UNEXPECTED(!isSuperTypeOfClass(originalCatchType.raw(), pt_tch_error_class, implicitOnly))) return zv::Val();
			}
			if (implicitOnly) {
				for (auto throwEntry : zv::ArrRef(throwPoints.raw())) {
					zval *throwPoint = throwEntry.value().deref().raw();
					bool isExplicit;
					if (UNEXPECTED(!pt_internal_throw_point_is_explicit(throwPoint, isExplicit))) return zv::Val();
					if (isExplicit) continue;
					{
						zv::Val typeHold;
						zval *throwType = pt_internal_throw_point_type(throwPoint, typeHold);
						if (UNEXPECTED(throwType == NULL)) return zv::Val();
						if (isNeverType(throwType)) continue;
					}

					for (uint32_t catchTypeIndex = 0; catchTypeIndex < catchTypeCount; catchTypeIndex++) {
						zval *catchTypeItem = zend_hash_index_find(catchTypes.table(), catchTypeIndex);
						zv::Val typeHold;
						zval *throwType = pt_internal_throw_point_type(throwPoint, typeHold);
						if (UNEXPECTED(throwType == NULL)) return zv::Val();
						zend_long superType = isSuperTypeOf(catchTypeItem, throwType);
						if (UNEXPECTED(superType < 0)) return zv::Val();
						if (superType == PT_TRI_NO) continue;

						setMatching(matchingThrowPoints, throwEntry);
					}
				}
			}

			// include previously removed throw points
			if (zend_hash_num_elements(matchingThrowPoints.table()) == 0) {
				bool matchesThrowable;
				if (UNEXPECTED(!isSuperTypeOfClass(originalCatchType.raw(), pt_tch_throwable_class, matchesThrowable))) return zv::Val();
				if (matchesThrowable) {
					zv::Val hold;
					zval *originalThrowPoints = pt_internal_statement_result_throw_points(branch, hold);
					if (UNEXPECTED(originalThrowPoints == NULL)) return zv::Val();
					zv::Val originalHold = zv::Val::copyOf(zv::Ref(originalThrowPoints));
					if (Z_TYPE_P(originalHold.raw()) == IS_ARRAY) {
						for (auto originalEntry : zv::ArrRef(originalHold.raw())) {
							zval *originalThrowPoint = originalEntry.value().deref().raw();
							bool canContainAnyThrowable;
							if (UNEXPECTED(!pt_internal_throw_point_can_contain_any_throwable(originalThrowPoint, canContainAnyThrowable))) return zv::Val();
							if (!canContainAnyThrowable) continue;

							matchingThrowPoints.push(zv::Ref(originalThrowPoint));
							std::fill(matchingCatchTypes.begin(), matchingCatchTypes.end(), true);
						}
					}
				}
			}

			// emit error
			for (uint32_t catchTypeIndex = 0; catchTypeIndex < catchTypeCount; catchTypeIndex++) {
				if (matchingCatchTypes[catchTypeIndex]) continue;
				zval *caughtType = zend_hash_index_find(catchTypes.table(), catchTypeIndex);
				zval *originalCaughtType = zend_hash_index_find(originalCatchTypes.table(), catchTypeIndex);
				zv::Args nodeArgv{catchNode, caughtType, originalCaughtType};
				zv::Val unthrownNode = pt_type_new(PT_CLASS_CATCH_WITH_UNTHROWN_EXCEPTION_NODE, 3, nodeArgv);
				if (UNEXPECTED(unthrownNode.isUndef())) return zv::Val();
				if (UNEXPECTED(!pt_node_scope_resolver_call_node_callback(nodeScopeResolver, nodeCallback, unthrownNode.raw(), scope, storage))) return zv::Val();
			}

			if (zend_hash_num_elements(matchingThrowPoints.table()) == 0) {
				zval *statementsHandler = OBJ_PROP_NUM(self, slots::statementsHandler);
				zv::Val mentionFlow = pt_statements_handler_get_variable_mention_flow(statementsHandler, catchNode);
				if (UNEXPECTED(mentionFlow.isUndef())) return zv::Val();
				zv::Arr catchFlow = zv::Arr::create(2);
				catchFlow.push(std::move(originalCatchType));
				catchFlow.push(std::move(mentionFlow));
				catchFlows.push(zv::Val(std::move(catchFlow)));
				continue;
			}

			// recompute throw points
			{
				zv::Arr newThrowPoints = zv::Arr::empty();
				for (auto throwEntry : zv::ArrRef(throwPoints.raw())) {
					zval *throwPoint = throwEntry.value().deref().raw();
					zv::Val newThrowPoint = pt_internal_throw_point_subtract_catch_type(throwPoint, originalCatchType.raw());
					if (UNEXPECTED(newThrowPoint.isUndef())) return zv::Val();
					zv::Val newTypeHold;
					zval *newType = pt_internal_throw_point_type(newThrowPoint.raw(), newTypeHold);
					if (UNEXPECTED(newType == NULL)) return zv::Val();
					if (isNeverType(newType)) {
						bool canContainAnyThrowable;
						if (UNEXPECTED(!pt_internal_throw_point_can_contain_any_throwable(throwPoint, canContainAnyThrowable))) return zv::Val();
						bool skip = !canContainAnyThrowable;
						if (!skip) {
							if (UNEXPECTED(!isSuperTypeOfClass(originalCatchType.raw(), pt_tch_throwable_class, skip))) return zv::Val();
						}
						if (skip) continue;
						// Keep the fallback Throwable path for enclosing try blocks without
						// introducing any other exception types after the documented ones were caught.
						zv::Val scopeHold, nodeHold;
						zval *throwScope = pt_internal_throw_point_scope(throwPoint, scopeHold);
						if (UNEXPECTED(throwScope == NULL)) return zv::Val();
						zval *throwNode = pt_internal_throw_point_node(throwPoint, nodeHold);
						if (UNEXPECTED(throwNode == NULL)) return zv::Val();
						zv::Val newTypeValue = zv::Val::copyOf(zv::Ref(newType));
						newThrowPoint = pt_internal_throw_point_create_implicit(throwScope, throwNode, newTypeValue.raw());
						if (UNEXPECTED(newThrowPoint.isUndef())) return zv::Val();
					}

					newThrowPoints.push(std::move(newThrowPoint));
				}
				throwPoints = std::move(newThrowPoints);
			}

			zv::Val catchScope = zv::Val::null();
			for (auto matchingEntry : zv::ArrRef(matchingThrowPoints.raw())) {
				zv::Val hold;
				zval *matchingScope = pt_internal_throw_point_scope(matchingEntry.value().deref().raw(), hold);
				if (UNEXPECTED(matchingScope == NULL)) return zv::Val();
				if (UNEXPECTED(!ptlh::mergeOrTake(catchScope, matchingScope))) return zv::Val();
			}

			zend_string *variableName = NULL;
			zv::Val catchVarHold;
			{
				zval *catchVar = ptsh::readNodeProperty(pt_tch_catch_var_site, catchNode, PT_LC("var"));
				if (UNEXPECTED(catchVar == NULL)) return zv::Val();
				catchVarHold = zv::Val::copyOf(zv::Ref(catchVar));
			}
			if (!catchVarHold.isNull()) {
				zval *name = ptsh::readNodeProperty(pt_tch_variable_name_site, catchVarHold.raw(), PT_LC("name"));
				if (UNEXPECTED(name == NULL)) return zv::Val();
				if (Z_TYPE_P(name) != IS_STRING) {
					pt_throw_should_not_happen();
					return zv::Val();
				}

				variableName = Z_STR_P(name);
				zv::Val typeExpr = pt_type_new(PT_CLASS_TYPE_EXPR, 1, catchType.raw());
				if (UNEXPECTED(typeExpr.isUndef())) return zv::Val();
				zv::Args assignArgv{catchVarHold.raw(), typeExpr.raw()};
				zv::Val assignNode = pt_type_new(PT_CLASS_VARIABLE_ASSIGN_NODE, 2, assignArgv);
				if (UNEXPECTED(assignNode.isUndef())) return zv::Val();
				if (UNEXPECTED(!pt_node_scope_resolver_call_node_callback(nodeScopeResolver, nodeCallback, assignNode.raw(), scope, storage))) return zv::Val();
			}
			zv::Val variableNameHold = variableName != NULL ? zv::Val::string(variableName) : zv::Val::null();

			zv::Val catchScopeResult;
			{
				zval *catchStmts = ptsh::readNodeProperty(pt_tch_catch_stmts_site, catchNode, PT_LC("stmts"));
				if (UNEXPECTED(catchStmts == NULL)) return zv::Val();
				zv::Val catchStmtsHold = zv::Val::copyOf(zv::Ref(catchStmts));
				if (UNEXPECTED(catchScope.isNull())) {
					zend_throw_error(NULL, "Call to a member function enterCatchType() on null");
					return zv::Val();
				}
				zv::Val enteredScope = pt_mutating_scope_enter_catch_type(Z_OBJ_P(catchScope.raw()), catchType.raw(), variableName != NULL ? Z_STR_P(variableNameHold.raw()) : NULL);
				if (UNEXPECTED(enteredScope.isUndef())) return zv::Val();
				catchScopeResult = pt_node_scope_resolver_process_stmt_nodes_internal(nodeScopeResolver, catchNode, catchStmtsHold.raw(), enteredScope.raw(), storage, nodeCallback, context);
				if (UNEXPECTED(catchScopeResult.isUndef())) return zv::Val();
			}
			zval *catchResult = catchScopeResult.raw();
			zv::Val catchScopeForFinally;
			{
				zv::Val hold;
				zval *resultScope = pt_internal_statement_result_scope(catchResult, hold);
				if (UNEXPECTED(resultScope == NULL)) return zv::Val();
				catchScopeForFinally = zv::Val::copyOf(zv::Ref(resultScope));
			}
			{
				zv::Val writeFlow = zv::Val::null();
				zval *catchVar = ptsh::readNodeProperty(pt_tch_catch_var_site, catchNode, PT_LC("var"));
				if (UNEXPECTED(catchVar == NULL)) return zv::Val();
				if (Z_TYPE_P(catchVar) != IS_NULL) {
					zv::Val catchVarNow = zv::Val::copyOf(zv::Ref(catchVar));
					writeFlow = pt_variable_flow_builder_target_write(catchVarNow.raw(), ptlh::PT_LH_WRITE_KIND_CATCH, catchScopeForFinally.raw(), storage, NULL);
					if (UNEXPECTED(writeFlow.isUndef())) return zv::Val();
				}
				zv::Val bodyFlowHold;
				zval *bodyFlow = pt_internal_statement_result_variable_flow(catchResult, bodyFlowHold);
				if (UNEXPECTED(bodyFlow == NULL)) return zv::Val();
				zv::Args flows{writeFlow.raw(), bodyFlow};
				zv::Val sequence = pt_variable_flow_sequence(2, flows);
				if (UNEXPECTED(sequence.isUndef())) return zv::Val();
				zv::Arr catchFlow = zv::Arr::create(2);
				catchFlow.push(zv::Ref(originalCatchType.raw()));
				catchFlow.push(std::move(sequence));
				catchFlows.push(zv::Val(std::move(catchFlow)));
			}

			bool catchAlwaysTerminating;
			if (UNEXPECTED(!pt_internal_statement_result_is_always_terminating(catchResult, catchAlwaysTerminating))) return zv::Val();
			if (!catchAlwaysTerminating) {
				zv::Val hold;
				zval *resultScope = pt_internal_statement_result_scope(catchResult, hold);
				if (UNEXPECTED(resultScope == NULL)) return zv::Val();
				if (UNEXPECTED(!ptlh::otherMergeWith(resultScope, finalScope))) return zv::Val();
			}
			alwaysTerminating = alwaysTerminating && catchAlwaysTerminating;
			if (!hasYield) {
				if (UNEXPECTED(!pt_internal_statement_result_has_yield(catchResult, hasYield))) return zv::Val();
			}
			zv::Val catchThrowPoints;
			{
				zv::Val hold;
				zval *points = pt_internal_statement_result_throw_points(catchResult, hold);
				if (UNEXPECTED(points == NULL)) return zv::Val();
				catchThrowPoints = zv::Val::copyOf(zv::Ref(points));
			}
			{
				zv::Val hold;
				zval *more = pt_internal_statement_result_impure_points(catchResult, hold);
				if (UNEXPECTED(more == NULL || !ptlh::mergeInto(impurePoints, more))) return zv::Val();
			}
			if (UNEXPECTED(!ptlh::mergeInto(throwPointsForLater, catchThrowPoints.raw()))) return zv::Val();

			if (!finallyScope.isNull()) {
				zv::Val merged = pt_mutating_scope_merge_with(Z_OBJ_P(finallyScope.raw()), catchScopeForFinally.raw());
				if (UNEXPECTED(merged.isUndef())) return zv::Val();
				finallyScope = std::move(merged);
			}
			{
				zv::Val hold;
				zval *catchExitPoints = pt_internal_statement_result_exit_points(catchResult, hold);
				if (UNEXPECTED(catchExitPoints == NULL)) return zv::Val();
				if (UNEXPECTED(!collectExitPoints(catchExitPoints, finallyExitPoints, finallyScope, exitPoints))) return zv::Val();
			}

			if (Z_TYPE_P(catchThrowPoints.raw()) == IS_ARRAY) {
				for (auto throwEntry : zv::ArrRef(catchThrowPoints.raw())) {
					if (finallyScope.isNull()) continue;
					zv::Val hold;
					zval *throwScope = pt_internal_throw_point_scope(throwEntry.value().deref().raw(), hold);
					if (UNEXPECTED(throwScope == NULL)) return zv::Val();
					zv::Val merged = pt_mutating_scope_merge_with(Z_OBJ_P(finallyScope.raw()), throwScope);
					if (UNEXPECTED(merged.isUndef())) return zv::Val();
					finallyScope = std::move(merged);
				}
			}
		}

		if (finalScope.isNull()) {
			finalScope = zv::Val::copyOf(zv::Ref(scope));
		}

		for (auto throwEntry : zv::ArrRef(throwPoints.raw())) {
			if (finallyScope.isNull()) continue;
			zv::Val hold;
			zval *throwScope = pt_internal_throw_point_scope(throwEntry.value().deref().raw(), hold);
			if (UNEXPECTED(throwScope == NULL)) return zv::Val();
			zv::Val merged = pt_mutating_scope_merge_with(Z_OBJ_P(finallyScope.raw()), throwScope);
			if (UNEXPECTED(merged.isUndef())) return zv::Val();
			finallyScope = std::move(merged);
		}

		if (!finallyScope.isNull()) {
			zv::Val originalFinallyScope = zv::Val::copyOf(finallyScope.ref());
			zv::Val finallyResult;
			{
				zval *finallyNow = ptsh::readNodeProperty(pt_tch_finally_site, stmt, PT_LC("finally"));
				if (UNEXPECTED(finallyNow == NULL)) return zv::Val();
				zv::Val finallyHold = zv::Val::copyOf(zv::Ref(finallyNow));
				if (UNEXPECTED(Z_TYPE_P(finallyHold.raw()) != IS_OBJECT)) {
					zend_error(E_WARNING, "Attempt to read property \"stmts\" on %s", zend_zval_value_name(finallyHold.raw()));
					return zv::Val();
				}
				zval *finallyStmts = ptsh::readNodeProperty(pt_tch_finally_stmts_site, finallyHold.raw(), PT_LC("stmts"));
				if (UNEXPECTED(finallyStmts == NULL)) return zv::Val();
				zv::Val finallyStmtsHold = zv::Val::copyOf(zv::Ref(finallyStmts));
				finallyResult = pt_node_scope_resolver_process_stmt_nodes_internal(nodeScopeResolver, finallyHold.raw(), finallyStmtsHold.raw(), finallyScope.raw(), storage, nodeCallback, context);
				if (UNEXPECTED(finallyResult.isUndef())) return zv::Val();
			}
			zval *finallyRes = finallyResult.raw();
			{
				zv::Val hold;
				zval *flow = pt_internal_statement_result_variable_flow(finallyRes, hold);
				if (UNEXPECTED(flow == NULL)) return zv::Val();
				finallyFlow = zv::Val::copyOf(zv::Ref(flow));
			}
			bool finallyAlwaysTerminating;
			if (UNEXPECTED(!pt_internal_statement_result_is_always_terminating(finallyRes, finallyAlwaysTerminating))) return zv::Val();
			alwaysTerminating = alwaysTerminating || finallyAlwaysTerminating;
			if (!hasYield) {
				if (UNEXPECTED(!pt_internal_statement_result_has_yield(finallyRes, hasYield))) return zv::Val();
			}
			{
				zv::Val hold;
				zval *more = pt_internal_statement_result_throw_points(finallyRes, hold);
				if (UNEXPECTED(more == NULL || !ptlh::mergeInto(throwPointsForLater, more))) return zv::Val();
			}
			{
				zv::Val hold;
				zval *more = pt_internal_statement_result_impure_points(finallyRes, hold);
				if (UNEXPECTED(more == NULL || !ptlh::mergeInto(impurePoints, more))) return zv::Val();
			}
			{
				zv::Val hold;
				zval *resultScope = pt_internal_statement_result_scope(finallyRes, hold);
				if (UNEXPECTED(resultScope == NULL)) return zv::Val();
				finallyScope = zv::Val::copyOf(zv::Ref(resultScope));
			}
			if (!finallyAlwaysTerminating) {
				zv::Val processed = pt_mutating_scope_process_finally_scope(Z_OBJ_P(finalScope.raw()), Z_OBJ_P(finallyScope.raw()), Z_OBJ_P(originalFinallyScope.raw()));
				if (UNEXPECTED(processed.isUndef())) return zv::Val();
				finalScope = std::move(processed);

				// the finally block runs after the exit point, so its changes are
				// part of the state the exit point leaves the try-catch with
				zv::Arr exitPointsAfterFinally = zv::Arr::empty();
				for (auto exitEntry : zv::ArrRef(exitPoints.raw())) {
					zval *exitPoint = exitEntry.value().deref().raw();
					zv::Val statementHold, exitScopeHold;
					zval *exitStatement = pt_internal_statement_exit_point_statement(exitPoint, statementHold);
					if (UNEXPECTED(exitStatement == NULL)) return zv::Val();
					zv::Val exitStatementValue = zv::Val::copyOf(zv::Ref(exitStatement));
					zval *exitScope = pt_internal_statement_exit_point_scope(exitPoint, exitScopeHold);
					if (UNEXPECTED(exitScope == NULL)) return zv::Val();
					zv::Val exitPointScope = pt_mutating_scope_process_finally_scope(Z_OBJ_P(exitScope), Z_OBJ_P(finallyScope.raw()), Z_OBJ_P(originalFinallyScope.raw()));
					if (UNEXPECTED(exitPointScope.isUndef())) return zv::Val();
					zv::Val afterFinally = pt_internal_statement_exit_point_new(exitStatementValue.raw(), exitPointScope.raw());
					if (UNEXPECTED(afterFinally.isUndef())) return zv::Val();
					exitPointsAfterFinally.push(std::move(afterFinally));

					bool error = false;
					if (!ptsh::isInstanceOf(exitStatementValue.raw(), PT_CLASS_RETURN_STMT, error)) {
						if (UNEXPECTED(error)) return zv::Val();
						continue;
					}
					zval *returnExpr = ptsh::readNodeProperty(pt_tch_return_expr_site, exitStatementValue.raw(), PT_LC("expr"));
					if (UNEXPECTED(returnExpr == NULL)) return zv::Val();
					if (Z_TYPE_P(returnExpr) == IS_NULL) continue;

					zv::Val returnNode = pt_type_new(PT_CLASS_RETURN_AFTER_FINALLY_NODE, 1, exitStatementValue.raw());
					if (UNEXPECTED(returnNode.isUndef())) return zv::Val();
					if (UNEXPECTED(!pt_node_scope_resolver_call_node_callback(nodeScopeResolver, nodeCallback, returnNode.raw(), exitPointScope.raw(), storage))) return zv::Val();
				}
				exitPoints = std::move(exitPointsAfterFinally);
			}
			{
				zv::Val hold;
				zval *finallyExits = pt_internal_statement_result_exit_points(finallyRes, hold);
				if (UNEXPECTED(finallyExits == NULL)) return zv::Val();
				if (ptlh::countOf(finallyExits) > 0 && finallyAlwaysTerminating) {
					zv::Val publicResult = pt_internal_statement_result_to_public(finallyRes);
					if (UNEXPECTED(publicResult.isUndef())) return zv::Val();
					zv::Val publicHold;
					zval *publicExitPoints = pt_statement_result_exit_points(publicResult.raw(), publicHold);
					if (UNEXPECTED(publicExitPoints == NULL)) return zv::Val();
					zv::Args nodeArgv{publicExitPoints, finallyExitPoints.raw()};
					zv::Val exitPointsNode = pt_type_new(PT_CLASS_FINALLY_EXIT_POINTS_NODE, 2, nodeArgv);
					if (UNEXPECTED(exitPointsNode.isUndef())) return zv::Val();
					if (UNEXPECTED(!pt_node_scope_resolver_call_node_callback(nodeScopeResolver, nodeCallback, exitPointsNode.raw(), scope, storage))) return zv::Val();
				}
			}
			{
				zv::Val hold;
				zval *more = pt_internal_statement_result_exit_points(finallyRes, hold);
				if (UNEXPECTED(more == NULL || !ptlh::mergeInto(exitPoints, more))) return zv::Val();
			}
		}

		if (UNEXPECTED(!ptlh::mergeInto(throwPoints, throwPointsForLater.raw()))) return zv::Val();
		zv::Val variableFlow;
		{
			zv::Val hold;
			zval *bodyFlow = pt_internal_statement_result_variable_flow(branch, hold);
			if (UNEXPECTED(bodyFlow == NULL)) return zv::Val();
			variableFlow = pt_variable_flow_try_catch(bodyFlow, catchFlows.raw(), finallyFlow.raw());
			if (UNEXPECTED(variableFlow.isUndef())) return zv::Val();
		}
		return pt_internal_statement_result_new(finalScope.raw(), hasYield, alwaysTerminating, exitPoints.raw(), throwPoints.raw(), impurePoints.raw(), NULL, variableFlow.raw());
	}

	/* the statement-handler entry (Engine.h) */
	static zv::Val processStmtEntry(zend_object *handler, zval *nodeScopeResolver, zval *stmt, zval *scope, zval *storage, zval *nodeCallback, zval *context)
	{
		return TryCatchHandler(handler).processStmt(nodeScopeResolver, stmt, scope, storage, nodeCallback, context);
	}

private:
	zend_object *self;

	/* the argv of a TypeCombinator::union(...$list) spread over a packed
	 * list built here (no holes) */
	static zval *packedArgv(zv::Arr &list)
	{
		HashTable *table = list.table();
		if (zend_hash_num_elements(table) == 0) return NULL;
		ZEND_ASSERT(HT_IS_PACKED(table) && table->nNumUsed == zend_hash_num_elements(table));
		return table->arPacked;
	}

	/* $matchingThrowPoints[$throwPointIndex] = $throwPoint */
	static void setMatching(zv::Arr &matchingThrowPoints, const zv::ArrayEntry &throwEntry)
	{
		matchingThrowPoints.separate();
		zval *throwPoint = throwEntry.value().deref().raw();
		Z_TRY_ADDREF_P(throwPoint);
		if (throwEntry.hasStringKey()) {
			zend_symtable_update(matchingThrowPoints.table(), throwEntry.stringKey(), throwPoint);
		} else {
			zend_hash_index_update(matchingThrowPoints.table(), throwEntry.indexKey(), throwPoint);
		}
	}

	/* the exit points of a try / catch block: every one's public form into
	 * $finallyExitPoints, the non-throw ones merged into $finallyScope (when
	 * there is one) and kept in $exitPoints; false = pending exception */
	[[nodiscard]] static bool collectExitPoints(zval *blockExitPoints, zv::Arr &finallyExitPoints, zv::Val &finallyScope, zv::Arr &exitPoints)
	{
		if (UNEXPECTED(Z_TYPE_P(blockExitPoints) != IS_ARRAY)) {
			zend_error(E_WARNING, "foreach() argument must be of type array|object, %s given", zend_zval_value_name(blockExitPoints));
			return EG(exception) == NULL;
		}
		zv::Val held = zv::Val::copyOf(zv::Ref(blockExitPoints));
		for (auto entry : zv::ArrRef(held.raw())) {
			zval *exitPoint = entry.value().deref().raw();
			zv::Val publicExitPoint = pt_internal_statement_exit_point_to_public(exitPoint);
			if (UNEXPECTED(publicExitPoint.isUndef())) return false;
			finallyExitPoints.push(std::move(publicExitPoint));
			{
				zv::Val statementHold;
				zval *statement = pt_internal_statement_exit_point_statement(exitPoint, statementHold);
				if (UNEXPECTED(statement == NULL)) return false;
				bool isThrow;
				if (UNEXPECTED(!isThrowStatement(statement, isThrow))) return false;
				if (isThrow) continue;
			}
			if (!finallyScope.isNull()) {
				zv::Val hold;
				zval *exitScope = pt_internal_statement_exit_point_scope(exitPoint, hold);
				if (UNEXPECTED(exitScope == NULL)) return false;
				zv::Val merged = pt_mutating_scope_merge_with(Z_OBJ_P(finallyScope.raw()), exitScope);
				if (UNEXPECTED(merged.isUndef())) return false;
				finallyScope = std::move(merged);
			}
			exitPoints.push(zv::Ref(exitPoint));
		}
		return true;
	}
};

} // namespace phpstanturbo

using phpstanturbo::TryCatchHandler;

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

void pt_register_try_catch_handler()
{
	pt_tch_throwable_class = zend_string_init_interned(PT_LC("Throwable"), 1);
	pt_tch_exception_class = zend_string_init_interned(PT_LC("Exception"), 1);
	pt_tch_error_class = zend_string_init_interned(PT_LC("Error"), 1);

	reg::Class cls("PHPStan\\Analyser\\StmtHandler\\TryCatchHandler");
	ptdecl::TryCatchHandler::declareClass(cls);
	ptdecl::TryCatchHandler::declareProperties(cls);

	/* the real parameter class name: the DI container autowires the
	 * service by reflecting the constructor */
	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *statementsHandler;
		if (!zp::parse<zp::Obj>(execute_data, statementsHandler)) RETURN_THROWS();
		TryCatchHandler(Z_OBJ_P(ZEND_THIS)).construct(statementsHandler);
	});

	cls.method<&TryCatchHandler::supports, zp::Obj>(sigs::supports);

	cls.method(sigs::processStmt, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *nodeScopeResolver, *stmt, *scope, *storage, *nodeCallback, *context;
		ZEND_PARSE_PARAMETERS_START(6, 6)
			Z_PARAM_OBJECT(nodeScopeResolver)
			Z_PARAM_OBJECT(stmt)
			Z_PARAM_OBJECT(scope)
			Z_PARAM_OBJECT(storage)
			Z_PARAM_ZVAL(nodeCallback)
			Z_PARAM_OBJECT(context)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(TryCatchHandler(Z_OBJ_P(ZEND_THIS)).processStmt(nodeScopeResolver, stmt, scope, storage, nodeCallback, context));
	});

	cls.shadow(&pt_ce_try_catch_handler);
	pt_stmt_handler_entry_register(&pt_ce_try_catch_handler, &TryCatchHandler::processStmtEntry);
}

/* }}} */
