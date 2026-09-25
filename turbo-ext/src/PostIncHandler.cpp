/*
 * PHPStanTurbo\PostIncHandler — native implementation of
 * PHPStan\Analyser\ExprHandler\PostIncHandler.
 *
 * A DI service (#[AutowiredService]): the constructor keeps the twin's
 * arginfo so Nette autowires it. processExpr() is registered as the class's
 * handler entry (Engine.h). The synthetic pre-increment's typeCallback is
 * IncDecTypeHelper's native closure (pt_inc_dec_type_helper_get_type_callback());
 * the other closures are native closures capturing what the twin's arrow
 * functions capture: the synthetic's specifyTypesCallback ($this,
 * $virtualExpr), the result's typeCallback ($varResult) and
 * specifyTypesCallback ($this, $expr).
 *
 * NodeScopeResolver, ExpressionResult, ExpressionContext, VariableFlow,
 * VariableFlowBuilder, VariableWrite, IncDecTypeHelper, AssignHandler and
 * DefaultNarrowingHelper are called through their direct entries.
 */

#include "support.h"
#include "generated/PostIncHandler.h"

namespace slots = ptdecl::PostIncHandler::slot;
namespace sigs = ptdecl::PostIncHandler::sig;
#include "SimpleExprHandlers.h"

zend_class_entry *pt_ce_post_inc_handler = nullptr;

namespace {

using phpstanturbo::visitors::NodeProp;

NodeProp pt_poih_var = PT_NODE_PROP(PT_CLASS_POST_INC, "var");

/* VariableWrite::KIND_POST_INC */
constexpr zend_long pt_poih_kind = 4;

} // namespace

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\ExprHandler\PostIncHandler; UNDEF = pending
 * exception. */
class PostIncHandler
{
public:
	static constexpr const char *closureName = "PHPStan\\Analyser\\ExprHandler\\PostIncHandler::{closure}";
	static constexpr uint32_t defaultNarrowingHelperSlot = slots::defaultNarrowingHelper;

	explicit PostIncHandler(zend_object *self) : self(self) {}

	/* the constructor body: the promoted properties */
	void construct(zval *expressionResultFactory, zval *defaultNarrowingHelper, zval *incDecTypeHelper, zval *assignHandler) const
	{
		pt_write_slot(self, slots::expressionResultFactory, expressionResultFactory);
		pt_write_slot(self, slots::defaultNarrowingHelper, defaultNarrowingHelper);
		pt_write_slot(self, slots::incDecTypeHelper, incDecTypeHelper);
		pt_write_slot(self, slots::assignHandler, assignHandler);
	}

	/* Mirrors supports(); false = pending exception */
	[[nodiscard]] static bool supports(zval *expr, bool &out)
	{
		int is = ptoh::isInstance(expr, PT_CLASS_POST_INC);
		if (UNEXPECTED(is < 0)) return false;
		out = is == 1;
		return true;
	}

	/* Mirrors processExpr(). */
	zv::Val processExpr(zval *nodeScopeResolver, zval *stmt, zval *expr, zval *scope, zval *storage, zval *nodeCallback, zval *context) const
	{
		zval *var = ptoh::operand(pt_poih_var, expr);
		if (UNEXPECTED(var == NULL)) return zv::Val();
		zv::Val valueFlowWrite = pt_variable_flow_builder_write_site(var, pt_poih_kind, scope, storage);
		if (UNEXPECTED(valueFlowWrite.isUndef())) return zv::Val();
		zv::Val valueContext = ptse::valueFlowContext(context, valueFlowWrite.raw());
		if (UNEXPECTED(valueContext.isUndef())) return zv::Val();
		zv::Val varResult = pt_node_scope_resolver_process_expr_node(nodeScopeResolver, stmt, var, scope, storage, nodeCallback, valueContext.raw());
		if (UNEXPECTED(varResult.isUndef())) return zv::Val();
		ptse::ChildResult child;
		if (UNEXPECTED(!child.read(varResult.raw()))) return zv::Val();

		// the virtual assign writes the incremented value - hand it the synthetic's
		// result so applyWrite composes off it instead of pricing the
		// unprocessed synthetic (and sentinel comparisons against it) on demand
		zv::Val virtualExpr = pt_type_new(PT_CLASS_PRE_INC, 1, var);
		if (UNEXPECTED(virtualExpr.isUndef())) return zv::Val();
		zv::Val virtualTypeCallback = pt_inc_dec_type_helper_get_type_callback(OBJ_PROP_NUM(self, slots::incDecTypeHelper), var, varResult.raw(), true);
		if (UNEXPECTED(virtualTypeCallback.isUndef())) return zv::Val();
		zv::Val virtualSpecifyTypesCallback = pt_native_closure(&ptse::specifyDefaultTypesBody<PostIncHandler>, self, virtualExpr.raw());
		pt_expression_result_args virtualArgs(child.scope, scope, virtualExpr.raw(), false, false, NULL, NULL, virtualTypeCallback.raw(), virtualSpecifyTypesCallback.raw());
		zv::Val virtualExprResult = pt_expression_result_create(OBJ_PROP_NUM(self, slots::expressionResultFactory), virtualArgs);
		if (UNEXPECTED(virtualExprResult.isUndef())) return zv::Val();

		// processVirtualAssign() emits nodes (PropertyAssignNode) carrying the
		// synthetic pre-inc/dec as the assigned expression - store its result so
		// rule-side asks about it answer from the storage
		if (UNEXPECTED(!pt_node_scope_resolver_store_expression_result(nodeScopeResolver, storage, virtualExpr.raw(), virtualExprResult.raw()))) return zv::Val();

		zv::Val assignResult = pt_assign_handler_process_virtual_assign(OBJ_PROP_NUM(self, slots::assignHandler), nodeScopeResolver, child.scope, storage, stmt, var, virtualExpr.raw(), nodeCallback, virtualExprResult.raw());
		if (UNEXPECTED(assignResult.isUndef())) return zv::Val();
		zv::Val assignedScope = ptse::scopeOf(assignResult.raw());
		if (UNEXPECTED(assignedScope.isUndef())) return zv::Val();

		zv::Val variableFlow = ptse::incDecFlow(child.variableFlow.raw(), valueFlowWrite.raw(), context, var, pt_poih_kind, assignedScope.raw(), storage);
		if (UNEXPECTED(variableFlow.isUndef())) return zv::Val();
		// post-increment evaluates to the variable's pre-mutation value
		zv::Val typeCallback = pt_native_closure(&ptse::childTypeBody<PostIncHandler>, varResult.raw());
		zv::Val specifyTypesCallback = pt_native_closure(&ptse::specifyDefaultTypesBody<PostIncHandler>, self, expr);
		pt_expression_result_args args(assignedScope.raw(), scope, expr, child.hasYield, child.isAlwaysTerminating, child.throwPoints, child.impurePoints, typeCallback.raw(), specifyTypesCallback.raw());
		args.withVariableFlow(variableFlow.raw());
		return pt_expression_result_create(OBJ_PROP_NUM(self, slots::expressionResultFactory), args);
	}

	/* the handler entry (Engine.h) */
	static zv::Val processExprEntry(zend_object *handler, zval *nodeScopeResolver, zval *stmt, zval *expr, zval *scope, zval *storage, zval *nodeCallback, zval *context)
	{
		return PostIncHandler(handler).processExpr(nodeScopeResolver, stmt, expr, scope, storage, nodeCallback, context);
	}

private:
	zend_object *self;
};

} // namespace phpstanturbo

using phpstanturbo::PostIncHandler;

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

PT_MINIT_REGISTRATION(pt_register_post_inc_handler)
{
	reg::Class cls("PHPStan\\Analyser\\ExprHandler\\PostIncHandler");
	ptdecl::PostIncHandler::declareClass(cls);
	ptdecl::PostIncHandler::declareProperties(cls);

	/* the real parameter class names: the DI container autowires the
	 * service by reflecting the constructor */
	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *expressionResultFactory, *defaultNarrowingHelper, *incDecTypeHelper, *assignHandler;
		if (!zp::parse<zp::Obj, zp::Obj, zp::Obj, zp::Obj>(execute_data, expressionResultFactory, defaultNarrowingHelper, incDecTypeHelper, assignHandler)) RETURN_THROWS();
		PostIncHandler(Z_OBJ_P(ZEND_THIS)).construct(expressionResultFactory, defaultNarrowingHelper, incDecTypeHelper, assignHandler);
	});

	cls.method(sigs::supports, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *expr;
		if (!zp::parse<zp::Obj>(execute_data, expr)) RETURN_THROWS();
		bool out;
		if (UNEXPECTED(!PostIncHandler::supports(expr, out))) RETURN_THROWS();
		RETURN_BOOL(out);
	});

	cls.method(sigs::processExpr, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *nodeScopeResolver, *stmt, *expr, *scope, *storage, *nodeCallback, *context;
		ZEND_PARSE_PARAMETERS_START(7, 7)
			Z_PARAM_OBJECT(nodeScopeResolver)
			Z_PARAM_OBJECT(stmt)
			Z_PARAM_OBJECT(expr)
			Z_PARAM_OBJECT(scope)
			Z_PARAM_OBJECT(storage)
			Z_PARAM_ZVAL(nodeCallback)
			Z_PARAM_OBJECT(context)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(PostIncHandler(Z_OBJ_P(ZEND_THIS)).processExpr(nodeScopeResolver, stmt, expr, scope, storage, nodeCallback, context));
	});

	cls.shadow(&pt_ce_post_inc_handler);
	pt_expr_handler_entry_register(&pt_ce_post_inc_handler, &PostIncHandler::processExprEntry);
}

/* }}} */
