/*
 * PHPStanTurbo\CloneHandler — native implementation of
 * PHPStan\Analyser\ExprHandler\CloneHandler.
 *
 * A DI service (#[AutowiredService]): the constructor keeps the twin's
 * arginfo so Nette autowires it. processExpr() is registered as the class's
 * handler entry (Engine.h); the static resolveCloneType() is exported as
 * pt_clone_handler_resolve_clone_type() (FuncCallHandler's clone
 * reinitialization). The twin's closures are native closures capturing what
 * the PHP closures capture: the typeCallback ($exprResult) and the
 * specifyTypesCallback ($this, $expr). The CloneTypeTraverser
 * resolveCloneType() hands TypeTraverser::map() is its traverse() body as a
 * native closure.
 *
 * NodeScopeResolver, ExpressionResult, ExpressionContext, TypeCombinator,
 * TypeTraverser, DefaultNarrowingHelper and the Type kernel are called
 * through their direct entries.
 */

#include "support.h"
#include "generated/CloneHandler.h"

namespace slots = ptdecl::CloneHandler::slot;
namespace sigs = ptdecl::CloneHandler::sig;
#include "SimpleExprHandlers.h"

zend_class_entry *pt_ce_clone_handler = nullptr;

namespace {

using phpstanturbo::visitors::NodeProp;

NodeProp pt_clh_expr = PT_NODE_PROP(PT_CLASS_CLONE_EXPR, "expr");

/* CloneTypeTraverser::traverse(Type $type, callable $traverse): Type —
 * captures nothing */
void cloneTypeTraverserBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
{
	(void) captures;
	if (UNEXPECTED(!ptse::requireArgs(argc, 2, "PHPStan\\Analyser\\Traverser\\CloneTypeTraverser::traverse"))) return;
	zval *type = &argv[0];
	if (UNEXPECTED(Z_TYPE_P(type) != IS_OBJECT)) {
		zend_type_error("PHPStan\\Analyser\\Traverser\\CloneTypeTraverser::traverse(): Argument #1 ($type) must be of type PHPStan\\Type\\Type, %s given", zend_zval_value_name(type));
		return;
	}
	zend_class_entry *ce = Z_OBJCE_P(type);
	if (instanceof_function(ce, pt_ce_union_type) || instanceof_function(ce, pt_ce_intersection_type)) {
		zv::Val traversed = pt_type_call_callable(&argv[1], 1, type);
		if (UNEXPECTED(traversed.isUndef())) return;
		traversed.intoReturnValue(return_value);
		return;
	}
	if (instanceof_function(ce, pt_ce_this_type)) {
		zv::Val classReflection = pt_type_op(Z_OBJ_P(type), PT_OP_GET_CLASS_REFLECTION, 0, NULL);
		if (UNEXPECTED(classReflection.isUndef())) return;
		zv::Val subtractedType = pt_type_op(Z_OBJ_P(type), PT_OP_GET_SUBTRACTED_TYPE, 0, NULL);
		if (UNEXPECTED(subtractedType.isUndef())) return;
		zval staticType;
		if (UNEXPECTED(!pt_static_type_new(&staticType, classReflection.raw(), subtractedType.isNull() ? NULL : subtractedType.raw()))) return;
		ZVAL_COPY_VALUE(return_value, &staticType);
		return;
	}

	ZVAL_COPY(return_value, type);
}

} // namespace

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\ExprHandler\CloneHandler; UNDEF = pending
 * exception. */
class CloneHandler
{
public:
	static constexpr const char *closureName = "PHPStan\\Analyser\\ExprHandler\\CloneHandler::{closure}";
	static constexpr uint32_t defaultNarrowingHelperSlot = slots::defaultNarrowingHelper;

	explicit CloneHandler(zend_object *self) : self(self) {}

	/* the constructor body: the promoted properties */
	void construct(zval *expressionResultFactory, zval *defaultNarrowingHelper) const
	{
		pt_write_slot(self, slots::expressionResultFactory, expressionResultFactory);
		pt_write_slot(self, slots::defaultNarrowingHelper, defaultNarrowingHelper);
	}

	/* Mirrors resolveCloneType(): TypeTraverser::map(TypeCombinator::intersect($exprType,
	 * new ObjectWithoutClassType()), new CloneTypeTraverser()) */
	static zv::Val resolveCloneType(zval *exprType)
	{
		zval objectWithoutClass;
		if (UNEXPECTED(!pt_object_without_class_type_new(&objectWithoutClass))) return zv::Val();
		zv::Val objectWithoutClassType = zv::Val::adopt(objectWithoutClass);
		zv::Args intersectArgs{exprType, objectWithoutClassType.raw()};
		zv::Val intersected = pt_type_combinator_intersect(2, intersectArgs);
		if (UNEXPECTED(intersected.isUndef())) return zv::Val();
		zv::Val traverser = pt_native_closure(&cloneTypeTraverserBody);
		return pt_type_traverser_map_of(intersected.raw(), traverser.raw());
	}

	/* Mirrors supports(); false = pending exception */
	[[nodiscard]] static bool supports(zval *expr, bool &out)
	{
		int is = ptoh::isInstance(expr, PT_CLASS_CLONE_EXPR);
		if (UNEXPECTED(is < 0)) return false;
		out = is == 1;
		return true;
	}

	/* Mirrors processExpr(). */
	zv::Val processExpr(zval *nodeScopeResolver, zval *stmt, zval *expr, zval *scope, zval *storage, zval *nodeCallback, zval *context) const
	{
		zval *inner = ptoh::operand(pt_clh_expr, expr);
		if (UNEXPECTED(inner == NULL)) return zv::Val();
		zv::Val innerContext = pt_expression_context_enter_deep(context);
		if (UNEXPECTED(innerContext.isUndef())) return zv::Val();
		zv::Val exprResult = pt_node_scope_resolver_process_expr_node(nodeScopeResolver, stmt, inner, scope, storage, nodeCallback, innerContext.raw());
		if (UNEXPECTED(exprResult.isUndef())) return zv::Val();
		ptse::ChildResult child;
		if (UNEXPECTED(!child.read(exprResult.raw()))) return zv::Val();

		zv::Val typeCallback = pt_native_closure(&typeCallbackBody, exprResult.raw());
		zv::Val specifyTypesCallback = pt_native_closure(&ptse::specifyDefaultTypesBody<CloneHandler>, self, expr);
		pt_expression_result_args args(child.scope, scope, expr, child.hasYield, child.isAlwaysTerminating, child.throwPoints, child.impurePoints, typeCallback.raw(), specifyTypesCallback.raw());
		args.withVariableFlow(child.variableFlow.raw());
		return pt_expression_result_create(OBJ_PROP_NUM(self, slots::expressionResultFactory), args);
	}

	/* the handler entry (Engine.h) */
	static zv::Val processExprEntry(zend_object *handler, zval *nodeScopeResolver, zval *stmt, zval *expr, zval *scope, zval *storage, zval *nodeCallback, zval *context)
	{
		return CloneHandler(handler).processExpr(nodeScopeResolver, stmt, expr, scope, storage, nodeCallback, context);
	}

private:
	zend_object *self;

	/* static fn (bool $nativeTypesPromoted): Type =>
	 * self::resolveCloneType($nativeTypesPromoted ? $exprResult->getNativeType()
	 * : $exprResult->getType()) — captures: $exprResult */
	static void typeCallbackBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		if (UNEXPECTED(!ptse::requireArgs(argc, 1, closureName))) return;
		bool nativeTypesPromoted = zend_is_true(&argv[0]);
		zv::Val type;
		pt_engine_with_stack([&]() {
			zv::Val exprType = ptse::typeOf(&captures[0], nativeTypesPromoted);
			if (UNEXPECTED(exprType.isUndef())) return;
			type = resolveCloneType(exprType.raw());
		});
		if (UNEXPECTED(type.isUndef())) return;
		type.intoReturnValue(return_value);
	}
};

} // namespace phpstanturbo

using phpstanturbo::CloneHandler;

zv::Val pt_clone_handler_resolve_clone_type(zval *exprType)
{
	return CloneHandler::resolveCloneType(exprType);
}

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

PT_MINIT_REGISTRATION(pt_register_clone_handler)
{
	reg::Class cls("PHPStan\\Analyser\\ExprHandler\\CloneHandler");
	ptdecl::CloneHandler::declareClass(cls);
	ptdecl::CloneHandler::declareProperties(cls);

	/* the real parameter class names: the DI container autowires the
	 * service by reflecting the constructor */
	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *expressionResultFactory, *defaultNarrowingHelper;
		if (!zp::parse<zp::Obj, zp::Obj>(execute_data, expressionResultFactory, defaultNarrowingHelper)) RETURN_THROWS();
		CloneHandler(Z_OBJ_P(ZEND_THIS)).construct(expressionResultFactory, defaultNarrowingHelper);
	});

	cls.method(sigs::resolveCloneType, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *exprType;
		if (!zp::parse<zp::Obj>(execute_data, exprType)) RETURN_THROWS();
		PT_RETURN_VAL(CloneHandler::resolveCloneType(exprType));
	});

	cls.method(sigs::supports, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *expr;
		if (!zp::parse<zp::Obj>(execute_data, expr)) RETURN_THROWS();
		bool out;
		if (UNEXPECTED(!CloneHandler::supports(expr, out))) RETURN_THROWS();
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
		PT_RETURN_VAL(CloneHandler(Z_OBJ_P(ZEND_THIS)).processExpr(nodeScopeResolver, stmt, expr, scope, storage, nodeCallback, context));
	});

	cls.shadow(&pt_ce_clone_handler);
	pt_expr_handler_entry_register(&pt_ce_clone_handler, &CloneHandler::processExprEntry);
}

/* }}} */
