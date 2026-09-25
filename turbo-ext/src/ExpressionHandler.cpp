/*
 * PHPStanTurbo\ExpressionHandler — native implementation of
 * PHPStan\Analyser\StmtHandler\ExpressionHandler.
 *
 * A DI service (#[AutowiredService]): the constructor keeps the twin's
 * arginfo so Nette autowires it. processStmt() is registered as the class's
 * statement-handler entry (Engine.h), so a native NodeScopeResolver
 * dispatches to it without an engine frame. The gatherer frame the twin
 * pushes is a native closure capturing what the PHP closure captures —
 * $currentScope by value and $hasAssign by reference.
 *
 * ExpressionResult, MutatingScope, the contexts, InternalStatementResult,
 * InternalStatementExitPoint, the Type kernel, NodeScopeResolver and
 * StatementsHandler are called through their direct entries.
 */

#include "support.h"
#include "generated/ExpressionHandler.h"

namespace slots = ptdecl::ExpressionHandler::slot;
namespace sigs = ptdecl::ExpressionHandler::sig;
#include "zv.h"
#include "TypeTraits.h"
#include "TypeOps.h"
#include "Engine.h"
#include "AnalyserValues.h"
#include "StmtHandlerCalls.h"

zend_class_entry *pt_ce_expression_handler = nullptr;

namespace {

pt_property_site pt_exh_expression_expr_site;
pt_property_site pt_exh_throw_expr_site;

/* $stmt->expr of the Expression statement (NULL = pending exception) */
zval *statementExpr(zval *stmt)
{
	return ptsh::readNodeProperty(pt_exh_expression_expr_site, stmt, PT_LC("expr"));
}

/* $throw->expr of a Throw_ expression (NULL = pending exception) */
zval *thrownExpr(zval *throwExpr)
{
	return ptsh::readNodeProperty(pt_exh_throw_expr_site, throwExpr, PT_LC("expr"));
}

/* $expr instanceof Expr\Throw_; false with *error on a pending exception */
bool isThrow(zval *expr, bool &error)
{
	return ptsh::isInstanceOf(expr, PT_CLASS_THROW_EXPR, error);
}

/* "Call to a member function x() on y" for a non-object receiver */
zend_never_inline ZEND_COLD void memberCallOnNonObject(const char *method, zval *value)
{
	zend_throw_error(NULL, "Call to a member function %s() on %s", method, zend_zval_value_name(value));
}

} // namespace

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\StmtHandler\ExpressionHandler; UNDEF = pending
 * exception. */
class ExpressionHandler
{
public:
	explicit ExpressionHandler(zend_object *self) : self(self) {}

	/* the constructor body: the promoted property */
	void construct(zval *statementsHandler)
	{
		zv::ObjRef(self).propAtWrite(slots::statementsHandler, zv::Val::copyOf(zv::Ref(statementsHandler)));
	}

	/* Mirrors supports(); false = pending exception */
	[[nodiscard]] bool supports(zval *stmt, bool &out) const
	{
		bool error = false;
		out = ptsh::isInstanceOf(stmt, PT_CLASS_EXPRESSION_STMT, error);
		return !error;
	}

	/* Mirrors processStmt(). */
	zv::Val processStmt(zval *nodeScopeResolver, zval *stmt, zval *scope, zval *storage, zval *nodeCallback, zval *context) const
	{
		zval *statementsHandler = OBJ_PROP_NUM(self, slots::statementsHandler);
		zval *preAnnotationScope = scope;
		zval *stmtScope = scope;
		zv::Val annotatedScope;
		zval *expr = statementExpr(stmt);
		if (UNEXPECTED(expr == NULL)) return zv::Val();
		bool error = false;
		if (isThrow(expr, error)) {
			zval *thrown = thrownExpr(expr);
			if (UNEXPECTED(thrown == NULL)) return zv::Val();
			annotatedScope = pt_statements_handler_process_stmt_var_annotation(statementsHandler, nodeScopeResolver, scope, storage, stmt, thrown, nodeCallback);
			if (UNEXPECTED(annotatedScope.isUndef())) return zv::Val();
			stmtScope = annotatedScope.raw();
			scope = stmtScope;
		}
		if (UNEXPECTED(error)) return zv::Val();

		/* $hasAssign, captured by reference like `use (&$hasAssign)` */
		zval hasAssignValue = {};
		ZVAL_FALSE(&hasAssignValue);
		zval hasAssignReference;
		ZVAL_NEW_REF(&hasAssignReference, &hasAssignValue);
		zv::Val hasAssign = zv::Val::adopt(hasAssignReference);
		zval captures[2];
		ZVAL_COPY_VALUE(&captures[0], scope);
		ZVAL_COPY_VALUE(&captures[1], hasAssign.raw());
		zv::Val gatherer = pt_native_closure_new(&gathererBody, 2, captures, 1u << 1);
		if (UNEXPECTED(!pt_node_scope_resolver_push_node_gatherer(nodeScopeResolver, gatherer.raw()))) return zv::Val();

		zv::Val result = processExpression(nodeScopeResolver, stmt, scope, storage, nodeCallback, context, statementsHandler, preAnnotationScope);
		pt_finally([&]() { (void) pt_node_scope_resolver_pop_node_gatherer(nodeScopeResolver); });
		if (UNEXPECTED(result.isUndef() || EG(exception) != NULL)) return zv::Val();

		if (UNEXPECTED(!pt_node_scope_resolver_call_node_callback(nodeScopeResolver, nodeCallback, stmt, stmtScope, storage))) return zv::Val();

		zval *resultValue = result.raw();
		zv::Val throwPointsHold;
		zval *throwPoints = pt_expression_result_throw_points(resultValue, throwPointsHold);
		if (UNEXPECTED(throwPoints == NULL)) return zv::Val();
		uint32_t explicitThrowPoints = 0;
		if (UNEXPECTED(!countExplicitThrowPoints(throwPoints, explicitThrowPoints))) return zv::Val();

		zv::Val impurePointsHold;
		zval *impurePoints = pt_expression_result_impure_points(resultValue, impurePointsHold);
		if (UNEXPECTED(impurePoints == NULL)) return zv::Val();
		if (UNEXPECTED(Z_TYPE_P(impurePoints) != IS_ARRAY)) {
			zend_type_error("count(): Argument #1 ($value) must be of type Countable|array, %s given", zend_zval_value_name(impurePoints));
			return zv::Val();
		}
		if (zend_hash_num_elements(Z_ARRVAL_P(impurePoints)) == 0 && explicitThrowPoints == 0) {
			expr = statementExpr(stmt);
			if (UNEXPECTED(expr == NULL)) return zv::Val();
			bool incDec = ptsh::isInstanceOf(expr, PT_CLASS_POST_INC, error)
				|| ptsh::isInstanceOf(expr, PT_CLASS_PRE_INC, error)
				|| ptsh::isInstanceOf(expr, PT_CLASS_POST_DEC, error)
				|| ptsh::isInstanceOf(expr, PT_CLASS_PRE_DEC, error);
			if (UNEXPECTED(error)) return zv::Val();
			if (!incDec) {
				zv::Args nodeArgv{expr, Z_TYPE_P(Z_REFVAL_P(hasAssign.raw())) == IS_TRUE};
				zv::Val noopNode = pt_type_new(PT_CLASS_NOOP_EXPRESSION_NODE, 2, nodeArgv);
				if (UNEXPECTED(noopNode.isUndef())) return zv::Val();
				if (UNEXPECTED(!pt_node_scope_resolver_call_node_callback(nodeScopeResolver, nodeCallback, noopNode.raw(), scope, storage))) return zv::Val();
			}
		}

		zv::Val resultScopeHold;
		zval *resultScope = pt_expression_result_scope(resultValue, resultScopeHold);
		if (UNEXPECTED(resultScope == NULL)) return zv::Val();
		// the expression statement was just processed; read its narrowing from
		// the result instead of re-resolving it via specifyTypesInCondition().
		zend_object *nullContext = pt_type_specifier_context_create_null();
		if (UNEXPECTED(nullContext == NULL)) return zv::Val();
		zval nullContextValue;
		ZVAL_OBJ(&nullContextValue, nullContext);
		zv::Val specifiedTypes = pt_expression_result_get_specified_types_for_scope(resultValue, resultScope, &nullContextValue);
		if (UNEXPECTED(specifiedTypes.isUndef())) return zv::Val();
		zv::Val finalScope = pt_mutating_scope_apply_specified_types(Z_OBJ_P(resultScope), specifiedTypes.raw());
		if (UNEXPECTED(finalScope.isUndef())) return zv::Val();

		bool isEquality;
		if (UNEXPECTED(!pt_specified_types_is_equality(specifiedTypes.raw(), isEquality))) return zv::Val();
		if (isEquality) {
			// Statement counterpart of ExpressionResult's equality handling:
			// store the call's true result so a duplicate void assertion statement is
			// reported as always-true. Assigned directly because void calls have no
			// return value to protect, and intersecting true with void would produce never.
			zval *statementExpression = statementExpr(stmt);
			if (UNEXPECTED(statementExpression == NULL)) return zv::Val();
			zval trueType, trueNativeType;
			if (UNEXPECTED(!pt_constant_boolean_type_new(&trueType, true))) return zv::Val();
			zv::Val trueTypeHold = zv::Val::adopt(trueType);
			if (UNEXPECTED(!pt_constant_boolean_type_new(&trueNativeType, true))) return zv::Val();
			zv::Val trueNativeTypeHold = zv::Val::adopt(trueNativeType);
			finalScope = pt_mutating_scope_assign_expression(Z_OBJ_P(finalScope.raw()), Z_OBJ_P(statementExpression), trueTypeHold.raw(), trueNativeTypeHold.raw());
			if (UNEXPECTED(finalScope.isUndef())) return zv::Val();
		}
		bool hasYield;
		if (UNEXPECTED(!pt_expression_result_has_yield(resultValue, hasYield))) return zv::Val();
		throwPoints = pt_expression_result_throw_points(resultValue, throwPointsHold);
		if (UNEXPECTED(throwPoints == NULL)) return zv::Val();
		impurePoints = pt_expression_result_impure_points(resultValue, impurePointsHold);
		if (UNEXPECTED(impurePoints == NULL)) return zv::Val();
		bool isAlwaysTerminating;
		if (UNEXPECTED(!pt_expression_result_is_always_terminating(resultValue, isAlwaysTerminating))) return zv::Val();

		// The expression statement is an exit point when its value type is an
		// explicit never: exit/die/throw, a never-returning call, or a call
		// configured as early-terminating (the call handlers give those never).
		zv::Val statementType = pt_expression_result_get_type(resultValue);
		if (UNEXPECTED(statementType.isUndef())) return zv::Val();
		bool explicitNever = false;
		if (Z_TYPE_P(statementType.raw()) == IS_OBJECT && instanceof_function(Z_OBJCE_P(statementType.raw()), pt_ce_never_type)) {
			if (UNEXPECTED(!pt_never_type_is_explicit(Z_OBJ_P(statementType.raw()), explicitNever))) return zv::Val();
		}
		if (explicitNever) {
			zv::Val exitPoint = pt_internal_statement_exit_point_new(stmt, finalScope.raw());
			if (UNEXPECTED(exitPoint.isUndef())) return zv::Val();
			zv::Arr exitPoints = zv::Arr::create(1);
			exitPoints.push(std::move(exitPoint));
			zv::Val variableFlow = pt_expression_result_variable_flow(resultValue);
			if (UNEXPECTED(variableFlow.isUndef())) return zv::Val();
			return pt_internal_statement_result_new(finalScope.raw(), hasYield, true, exitPoints.raw(), throwPoints, impurePoints, NULL, variableFlow.raw());
		}
		zv::Arr exitPoints = zv::Arr::empty();
		zv::Val variableFlow = pt_expression_result_variable_flow(resultValue);
		if (UNEXPECTED(variableFlow.isUndef())) return zv::Val();
		return pt_internal_statement_result_new(finalScope.raw(), hasYield, isAlwaysTerminating, exitPoints.raw(), throwPoints, impurePoints, NULL, variableFlow.raw());
	}

	/* the statement-handler entry (Engine.h) */
	static zv::Val processStmtEntry(zend_object *handler, zval *nodeScopeResolver, zval *stmt, zval *scope, zval *storage, zval *nodeCallback, zval *context)
	{
		return ExpressionHandler(handler).processStmt(nodeScopeResolver, stmt, scope, storage, nodeCallback, context);
	}

private:
	zend_object *self;

	/* the twin's try block: the expression walked, a thrown expression's
	 * @var-changed-type node emitted */
	static zv::Val processExpression(zval *nodeScopeResolver, zval *stmt, zval *scope, zval *storage, zval *nodeCallback, zval *context, zval *statementsHandler, zval *preAnnotationScope)
	{
		zval *expr = statementExpr(stmt);
		if (UNEXPECTED(expr == NULL)) return zv::Val();
		bool resolveTemplateArguments;
		if (UNEXPECTED(!pt_statement_context_should_resolve_template_arguments(context, resolveTemplateArguments))) return zv::Val();
		zv::Val expressionContext = pt_expression_context_create_top_level(resolveTemplateArguments);
		if (UNEXPECTED(expressionContext.isUndef())) return zv::Val();
		zv::Val result = pt_node_scope_resolver_process_expr_node(nodeScopeResolver, stmt, expr, scope, storage, nodeCallback, expressionContext.raw());
		if (UNEXPECTED(result.isUndef())) return zv::Val();

		expr = statementExpr(stmt);
		if (UNEXPECTED(expr == NULL)) return zv::Val();
		bool error = false;
		if (!isThrow(expr, error)) {
			if (UNEXPECTED(error)) return zv::Val();
			return result;
		}
		// the @var-changed-type node fires now that the thrown expression is stored
		zv::Val resultScopeHold;
		zval *resultScope = pt_expression_result_scope(result.raw(), resultScopeHold);
		if (UNEXPECTED(resultScope == NULL)) return zv::Val();
		zval *thrown = thrownExpr(expr);
		if (UNEXPECTED(thrown == NULL)) return zv::Val();
		zv::Val constraints = pt_statements_handler_emit_var_tag_changed_node(statementsHandler, nodeScopeResolver, preAnnotationScope, storage, stmt, thrown, nodeCallback);
		if (UNEXPECTED(constraints.isUndef())) return zv::Val();
		zv::Val constrainedScope = pt_mutating_scope_add_template_argument_constraints(Z_OBJ_P(resultScope), constraints.raw());
		if (UNEXPECTED(constrainedScope.isUndef())) return zv::Val();
		return pt_expression_result_with_scope(result.raw(), constrainedScope.raw());
	}

	/* count(array_filter($throwPoints, static fn ($throwPoint) => $throwPoint->isExplicit()));
	 * false = pending exception */
	[[nodiscard]] static bool countExplicitThrowPoints(zval *throwPoints, uint32_t &count)
	{
		if (UNEXPECTED(Z_TYPE_P(throwPoints) != IS_ARRAY)) {
			zend_type_error("array_filter(): Argument #1 ($array) must be of type array, %s given", zend_zval_value_name(throwPoints));
			return false;
		}
		for (auto entry : zv::TableRef(Z_ARRVAL_P(throwPoints))) {
			zval *throwPoint = entry.value().deref().raw();
			if (UNEXPECTED(Z_TYPE_P(throwPoint) != IS_OBJECT)) {
				memberCallOnNonObject("isExplicit", throwPoint);
				return false;
			}
			bool isExplicit;
			if (UNEXPECTED(!pt_internal_throw_point_is_explicit(throwPoint, isExplicit))) return false;
			if (isExplicit) {
				count++;
			}
		}
		return true;
	}

	/* static function (Node $node, Scope $scope) use ($currentScope, &$hasAssign): void —
	 * captures: $currentScope, &$hasAssign */
	static void gathererBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		(void) return_value;
		if (UNEXPECTED(argc < 2)) {
			zend_throw_error(zend_ce_argument_count_error, "Too few arguments to function PHPStan\\Analyser\\StmtHandler\\ExpressionHandler::{closure}(), %u passed and exactly 2 expected", argc);
			return;
		}
		zval *node = &argv[0];
		zval *scope = &argv[1];
		bool error = false;
		if (!ptsh::isInstanceOf(node, PT_CLASS_VARIABLE_ASSIGN_NODE, error) && !ptsh::isInstanceOf(node, PT_CLASS_PROPERTY_ASSIGN_NODE, error)) return;
		if (UNEXPECTED(Z_TYPE_P(scope) != IS_OBJECT)) {
			memberCallOnNonObject("getAnonymousFunctionReflection", scope);
			return;
		}
		zval *currentScope = &captures[0];
		zv::Val anonymousFunction = pt_mutating_scope_get_anonymous_function_reflection(Z_OBJ_P(scope));
		if (UNEXPECTED(anonymousFunction.isUndef())) return;
		zv::Val currentAnonymousFunction = pt_mutating_scope_get_anonymous_function_reflection(Z_OBJ_P(currentScope));
		if (UNEXPECTED(currentAnonymousFunction.isUndef())) return;
		if (!zend_is_identical(anonymousFunction.raw(), currentAnonymousFunction.raw())) return;
		zv::Val function = pt_mutating_scope_get_function(Z_OBJ_P(scope));
		if (UNEXPECTED(function.isUndef())) return;
		zv::Val currentFunction = pt_mutating_scope_get_function(Z_OBJ_P(currentScope));
		if (UNEXPECTED(currentFunction.isUndef())) return;
		if (!zend_is_identical(function.raw(), currentFunction.raw())) return;

		zval *hasAssign = Z_REFVAL(captures[1]);
		zval_ptr_dtor(hasAssign);
		ZVAL_TRUE(hasAssign);
	}
};

} // namespace phpstanturbo

using phpstanturbo::ExpressionHandler;

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

PT_MINIT_REGISTRATION(pt_register_expression_handler)
{
	reg::Class cls("PHPStan\\Analyser\\StmtHandler\\ExpressionHandler");
	ptdecl::ExpressionHandler::declareClass(cls);
	ptdecl::ExpressionHandler::declareProperties(cls);

	/* the real parameter class names: the DI container autowires the
	 * service by reflecting the constructor */
	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *statementsHandler;
		if (!zp::parse<zp::Obj>(execute_data, statementsHandler)) RETURN_THROWS();
		ExpressionHandler(Z_OBJ_P(ZEND_THIS)).construct(statementsHandler);
	});

	cls.method<&ExpressionHandler::supports, zp::Obj>(sigs::supports);

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
		PT_RETURN_VAL(ExpressionHandler(Z_OBJ_P(ZEND_THIS)).processStmt(nodeScopeResolver, stmt, scope, storage, nodeCallback, context));
	});

	cls.shadow(&pt_ce_expression_handler);
	pt_stmt_handler_entry_register(&pt_ce_expression_handler, &ExpressionHandler::processStmtEntry);
}

/* }}} */
