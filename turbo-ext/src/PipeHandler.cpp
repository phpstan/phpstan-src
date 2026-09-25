/*
 * PHPStanTurbo\PipeHandler — native implementation of
 * PHPStan\Analyser\ExprHandler\PipeHandler.
 *
 * A DI service (#[AutowiredService]): the constructor keeps the twin's
 * arginfo so Nette autowires it. processExpr() is registered as the class's
 * handler entry (Engine.h). The twin's closures are native closures capturing
 * what the PHP closures capture: the first-class callable's stored result's
 * typeCallback ($callableNodeResult), the result's typeCallback
 * ($callResult) and specifyTypesCallback ($this, $expr).
 *
 * NodeScopeResolver, ExpressionResult, ExpressionContext, SpecifiedTypes,
 * VariableFlow(Builder), DefaultNarrowingHelper and the php-parser call
 * nodes' isFirstClassCallable() are called through their direct entries; the
 * nodes' getAttributes() / getAttribute() with a default through cached
 * sites.
 */

#include "support.h"
#include "generated/PipeHandler.h"

namespace slots = ptdecl::PipeHandler::slot;
namespace sigs = ptdecl::PipeHandler::sig;
#include "SimpleExprHandlers.h"

zend_class_entry *pt_ce_pipe_handler = nullptr;

namespace {

using phpstanturbo::visitors::NodeProp;

NodeProp pt_pph_func_call_name = PT_NODE_PROP(PT_CLASS_FUNC_CALL, "name");
NodeProp pt_pph_method_call_var = PT_NODE_PROP(PT_CLASS_METHOD_CALL, "var");
NodeProp pt_pph_method_call_name = PT_NODE_PROP(PT_CLASS_METHOD_CALL, "name");
NodeProp pt_pph_static_call_class = PT_NODE_PROP(PT_CLASS_STATIC_CALL, "class");
NodeProp pt_pph_static_call_name = PT_NODE_PROP(PT_CLASS_STATIC_CALL, "name");

pt_method_site pt_pph_get_attributes_site;
pt_method_site pt_pph_get_attribute_site;

/* the twin's literals, permanent interned strings (module startup) */
zend_string *pt_pph_virtual_pipe_operator_call = nullptr;
zend_string *pt_pph_printer_cache_key = nullptr;
zend_string *pt_pph_arg_attributes = nullptr;

/* $node->getAttributes() */
zv::Val nodeAttributes(zval *node)
{
	return pt_call_method_cached(pt_pph_get_attributes_site, Z_OBJ_P(node), PT_LC("getattributes"), 0, NULL);
}

/* $node->getAttribute(ReversePipeTransformerVisitor::ARG_ATTRIBUTES_NAME, []) */
zv::Val argAttributesOf(zval *node)
{
	zval empty;
	ZVAL_EMPTY_ARRAY(&empty);
	zv::Args argv{pt_pph_arg_attributes, &empty};
	return pt_call_method_cached(pt_pph_get_attribute_site, Z_OBJ_P(node), PT_LC("getattribute"), 2, argv);
}

/* $node instanceof <class> && $node->isFirstClassCallable(); -1 = pending
 * exception */
int isFirstClassCallableOf(zval *node, int classIdx)
{
	int is = ptoh::isInstance(node, classIdx);
	if (is <= 0) return is;
	bool firstClassCallable;
	if (UNEXPECTED(!pt_call_like_is_first_class_callable(Z_OBJ_P(node), firstClassCallable))) return -1;
	return firstClassCallable ? 1 : 0;
}

} // namespace

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\ExprHandler\PipeHandler; UNDEF = pending
 * exception. */
class PipeHandler
{
public:
	static constexpr const char *closureName = "PHPStan\\Analyser\\ExprHandler\\PipeHandler::{closure}";
	static constexpr uint32_t defaultNarrowingHelperSlot = slots::defaultNarrowingHelper;

	explicit PipeHandler(zend_object *self) : self(self) {}

	/* the constructor body: the promoted properties */
	void construct(zval *expressionResultFactory, zval *defaultNarrowingHelper) const
	{
		pt_write_slot(self, slots::expressionResultFactory, expressionResultFactory);
		pt_write_slot(self, slots::defaultNarrowingHelper, defaultNarrowingHelper);
	}

	/* Mirrors supports(); false = pending exception */
	[[nodiscard]] static bool supports(zval *expr, bool &out)
	{
		int is = ptoh::isInstance(expr, PT_CLASS_PIPE_EXPR);
		if (UNEXPECTED(is < 0)) return false;
		out = is == 1;
		return true;
	}

	/* Mirrors processExpr(). */
	zv::Val processExpr(zval *nodeScopeResolver, zval *stmt, zval *expr, zval *scope, zval *storage, zval *nodeCallback, zval *context) const
	{
		zval *right = ptoh::binaryOpRight(expr);
		if (UNEXPECTED(right == NULL)) return zv::Val();
		zv::Val rightAttributes;
		{
			zv::Val attributes = nodeAttributes(right);
			if (UNEXPECTED(attributes.isUndef())) return zv::Val();
			if (UNEXPECTED(Z_TYPE_P(attributes.raw()) != IS_ARRAY)) {
				zend_type_error("array_merge(): Argument #1 must be of type array, %s given", zend_zval_value_name(attributes.raw()));
				return zv::Val();
			}
			zv::Arr virtualCall = zv::Arr::create(1);
			virtualCall.set(pt_pph_virtual_pipe_operator_call, zv::Val::boolean(true));
			rightAttributes = ptcall::arrayMerge(attributes.raw(), virtualCall.raw());
		}
		{
			zval *raw = rightAttributes.raw();
			SEPARATE_ARRAY(raw);
			zend_symtable_del(Z_ARRVAL_P(raw), pt_pph_printer_cache_key);
		}
		zv::Val argAttributes = argAttributesOf(expr);
		if (UNEXPECTED(argAttributes.isUndef())) return zv::Val();

		zval *left = ptoh::binaryOpLeft(expr);
		if (UNEXPECTED(left == NULL)) return zv::Val();
		zv::Val callExpr;
		zv::Val firstClassCallableNode = zv::Val::null();
		int isFuncCall = isFirstClassCallableOf(right, PT_CLASS_FUNC_CALL);
		if (UNEXPECTED(isFuncCall < 0)) return zv::Val();
		if (isFuncCall) {
			zval *name = ptoh::operand(pt_pph_func_call_name, right);
			if (UNEXPECTED(name == NULL)) return zv::Val();
			zv::Val args = argList(left, argAttributes.raw());
			if (UNEXPECTED(args.isUndef())) return zv::Val();
			zv::Args callArgs{name, args.raw(), rightAttributes.raw()};
			callExpr = pt_type_new(PT_CLASS_FUNC_CALL, 3, callArgs);
			if (UNEXPECTED(callExpr.isUndef())) return zv::Val();
			zv::Args nodeArgs{name, right};
			firstClassCallableNode = pt_type_new(PT_CLASS_FUNCTION_CALLABLE_NODE, 2, nodeArgs);
			if (UNEXPECTED(firstClassCallableNode.isUndef())) return zv::Val();
		} else {
			int isMethodCall = isFirstClassCallableOf(right, PT_CLASS_METHOD_CALL);
			if (UNEXPECTED(isMethodCall < 0)) return zv::Val();
			if (isMethodCall) {
				zval *var = ptoh::operand(pt_pph_method_call_var, right);
				if (UNEXPECTED(var == NULL)) return zv::Val();
				zval *name = ptoh::operand(pt_pph_method_call_name, right);
				if (UNEXPECTED(name == NULL)) return zv::Val();
				zv::Val args = argList(left, argAttributes.raw());
				if (UNEXPECTED(args.isUndef())) return zv::Val();
				zv::Args callArgs{var, name, args.raw(), rightAttributes.raw()};
				callExpr = pt_type_new(PT_CLASS_METHOD_CALL, 4, callArgs);
				if (UNEXPECTED(callExpr.isUndef())) return zv::Val();
				var = ptoh::operand(pt_pph_method_call_var, right);
				if (UNEXPECTED(var == NULL)) return zv::Val();
				name = ptoh::operand(pt_pph_method_call_name, right);
				if (UNEXPECTED(name == NULL)) return zv::Val();
				zv::Args nodeArgs{var, name, right};
				firstClassCallableNode = pt_type_new(PT_CLASS_METHOD_CALLABLE_NODE, 3, nodeArgs);
				if (UNEXPECTED(firstClassCallableNode.isUndef())) return zv::Val();
			} else {
				int isStaticCall = isFirstClassCallableOf(right, PT_CLASS_STATIC_CALL);
				if (UNEXPECTED(isStaticCall < 0)) return zv::Val();
				if (isStaticCall) {
					zval *class_ = ptoh::operand(pt_pph_static_call_class, right);
					if (UNEXPECTED(class_ == NULL)) return zv::Val();
					zval *name = ptoh::operand(pt_pph_static_call_name, right);
					if (UNEXPECTED(name == NULL)) return zv::Val();
					zv::Val args = argList(left, argAttributes.raw());
					if (UNEXPECTED(args.isUndef())) return zv::Val();
					zv::Args callArgs{class_, name, args.raw(), rightAttributes.raw()};
					callExpr = pt_type_new(PT_CLASS_STATIC_CALL, 4, callArgs);
					if (UNEXPECTED(callExpr.isUndef())) return zv::Val();
					class_ = ptoh::operand(pt_pph_static_call_class, right);
					if (UNEXPECTED(class_ == NULL)) return zv::Val();
					name = ptoh::operand(pt_pph_static_call_name, right);
					if (UNEXPECTED(name == NULL)) return zv::Val();
					zv::Args nodeArgs{class_, name, right};
					firstClassCallableNode = pt_type_new(PT_CLASS_STATIC_METHOD_CALLABLE_NODE, 3, nodeArgs);
					if (UNEXPECTED(firstClassCallableNode.isUndef())) return zv::Val();
				} else {
					zv::Val args = argList(left, argAttributes.raw());
					if (UNEXPECTED(args.isUndef())) return zv::Val();
					zv::Args callArgs{right, args.raw(), rightAttributes.raw()};
					callExpr = pt_type_new(PT_CLASS_FUNC_CALL, 3, callArgs);
					if (UNEXPECTED(callExpr.isUndef())) return zv::Val();
				}
			}
		}

		zval *factory = OBJ_PROP_NUM(self, slots::expressionResultFactory);
		if (!firstClassCallableNode.isNull()) {
			// store a result for $expr->right so node callbacks asking about its
			// type can be resumed. Its closure type lives on the matching
			// *CallableNode, processed here (storage is available, so the result -
			// not the storage - is captured) and read back in the typeCallback.
			zv::Val callableNodeResult = pt_node_scope_resolver_process_expr_on_demand(nodeScopeResolver, firstClassCallableNode.raw(), scope, storage);
			if (UNEXPECTED(callableNodeResult.isUndef())) return zv::Val();
			zv::Val callableFlow = pt_expression_result_variable_flow(callableNodeResult.raw());
			if (UNEXPECTED(callableFlow.isUndef())) return zv::Val();
			zv::Val callableTypeCallback = pt_native_closure(&ptse::childTypeBody<PipeHandler>, callableNodeResult.raw());
			zv::Val emptySpecifyCallback = pt_specified_types_empty_specify_callback();
			if (UNEXPECTED(emptySpecifyCallback.isUndef())) return zv::Val();
			pt_expression_result_args rightArgs(scope, scope, right, false, false, NULL, NULL, callableTypeCallback.raw(), emptySpecifyCallback.raw());
			rightArgs.withVariableFlow(callableFlow.raw());
			zv::Val rightResult = pt_expression_result_create(factory, rightArgs);
			if (UNEXPECTED(rightResult.isUndef())) return zv::Val();
			if (UNEXPECTED(!pt_node_scope_resolver_store_expression_result(nodeScopeResolver, storage, right, rightResult.raw()))) return zv::Val();
		}

		zv::Val callContext = pt_expression_context_without_value_flow(context);
		if (UNEXPECTED(callContext.isUndef())) return zv::Val();
		zv::Val callResult = pt_node_scope_resolver_process_expr_node(nodeScopeResolver, stmt, callExpr.raw(), scope, storage, nodeCallback, callContext.raw());
		if (UNEXPECTED(callResult.isUndef())) return zv::Val();
		ptse::ChildResult child;
		if (UNEXPECTED(!child.read(callResult.raw()))) return zv::Val();

		zv::Val variableFlow;
		{
			left = ptoh::binaryOpLeft(expr);
			if (UNEXPECTED(left == NULL)) return zv::Val();
			zv::Val leftFlow = pt_variable_flow_builder_child(left, storage);
			if (UNEXPECTED(leftFlow.isUndef())) return zv::Val();
			right = ptoh::binaryOpRight(expr);
			if (UNEXPECTED(right == NULL)) return zv::Val();
			zv::Val rightFlow = pt_variable_flow_builder_child(right, storage);
			if (UNEXPECTED(rightFlow.isUndef())) return zv::Val();
			zv::Val throwsFlow = pt_variable_flow_builder_throws(expr, Z_ARRVAL_P(child.throwPoints));
			if (UNEXPECTED(throwsFlow.isUndef())) return zv::Val();
			zv::Args flows{leftFlow.raw(), rightFlow.raw(), throwsFlow.raw()};
			variableFlow = pt_variable_flow_sequence(3, flows);
			if (UNEXPECTED(variableFlow.isUndef())) return zv::Val();
		}

		// the pipe evaluates to its rewritten call - read that child's result
		zv::Val typeCallback = pt_native_closure(&ptse::childTypeBody<PipeHandler>, callResult.raw());
		zv::Val specifyTypesCallback = pt_native_closure(&ptse::specifyDefaultTypesBody<PipeHandler>, self, expr);
		pt_expression_result_args args(child.scope, scope, expr, child.hasYield, child.isAlwaysTerminating, child.throwPoints, child.impurePoints, typeCallback.raw(), specifyTypesCallback.raw());
		args.withVariableFlow(variableFlow.raw());
		return pt_expression_result_create(factory, args);
	}

	/* the handler entry (Engine.h) */
	static zv::Val processExprEntry(zend_object *handler, zval *nodeScopeResolver, zval *stmt, zval *expr, zval *scope, zval *storage, zval *nodeCallback, zval *context)
	{
		return PipeHandler(handler).processExpr(nodeScopeResolver, stmt, expr, scope, storage, nodeCallback, context);
	}

private:
	zend_object *self;

	/* [new Arg($expr->left, attributes: $argAttributes)] */
	static zv::Val argList(zval *left, zval *argAttributes)
	{
		zv::Args argArgs{left, false, false, argAttributes};
		zv::Val arg = pt_type_new(PT_CLASS_ARG, 4, argArgs);
		if (UNEXPECTED(arg.isUndef())) return zv::Val();
		zv::Arr list = zv::Arr::create(1);
		list.push(std::move(arg));
		return zv::Val(std::move(list));
	}
};

} // namespace phpstanturbo

using phpstanturbo::PipeHandler;

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

PT_MINIT_REGISTRATION(pt_register_pipe_handler)
{
	pt_pph_virtual_pipe_operator_call = zend_string_init_interned(PT_LC("virtualPipeOperatorCall"), 1);
	pt_pph_printer_cache_key = zend_string_init_interned(PT_LC("phpstan_cache_printer"), 1);
	pt_pph_arg_attributes = zend_string_init_interned(PT_LC("argAttributes"), 1);

	reg::Class cls("PHPStan\\Analyser\\ExprHandler\\PipeHandler");
	ptdecl::PipeHandler::declareClass(cls);
	ptdecl::PipeHandler::declareProperties(cls);

	/* the real parameter class names: the DI container autowires the
	 * service by reflecting the constructor */
	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *expressionResultFactory, *defaultNarrowingHelper;
		if (!zp::parse<zp::Obj, zp::Obj>(execute_data, expressionResultFactory, defaultNarrowingHelper)) RETURN_THROWS();
		PipeHandler(Z_OBJ_P(ZEND_THIS)).construct(expressionResultFactory, defaultNarrowingHelper);
	});

	cls.method(sigs::supports, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *expr;
		if (!zp::parse<zp::Obj>(execute_data, expr)) RETURN_THROWS();
		bool out;
		if (UNEXPECTED(!PipeHandler::supports(expr, out))) RETURN_THROWS();
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
		PT_RETURN_VAL(PipeHandler(Z_OBJ_P(ZEND_THIS)).processExpr(nodeScopeResolver, stmt, expr, scope, storage, nodeCallback, context));
	});

	cls.shadow(&pt_ce_pipe_handler);
	pt_expr_handler_entry_register(&pt_ce_pipe_handler, &PipeHandler::processExprEntry);
}

/* }}} */
