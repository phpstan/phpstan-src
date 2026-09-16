/*
 * PHPStanTurbo\InterpolatedStringHandler — native implementation of
 * PHPStan\Analyser\ExprHandler\InterpolatedStringHandler.
 *
 * A DI service (#[AutowiredService]): the constructor keeps the twin's
 * arginfo so Nette autowires it. processExpr() is registered as the class's
 * handler entry (Engine.h). The twin's closures are native closures capturing
 * what the PHP closures capture: the typeCallback ($this, $expr,
 * $partResults — the parts' results keyed by spl_object_id()) and the
 * specifyTypesCallback ($this, $expr).
 *
 * NodeScopeResolver, ExpressionResult, ExpressionContext,
 * ImplicitToStringCallHelper, VariableFlow, DefaultNarrowingHelper and the
 * Type kernel are called through their direct entries;
 * InitializerExprTypeResolver::resolveConcatType() through a cached site.
 */

#include "support.h"
#include "generated/InterpolatedStringHandler.h"

namespace slots = ptdecl::InterpolatedStringHandler::slot;
namespace sigs = ptdecl::InterpolatedStringHandler::sig;
#include "SimpleExprHandlers.h"

zend_class_entry *pt_ce_interpolated_string_handler = nullptr;

namespace {

using phpstanturbo::visitors::NodeProp;

NodeProp pt_ish_parts = PT_NODE_PROP(PT_CLASS_INTERPOLATED_STRING, "parts");
NodeProp pt_ish_part_value = PT_NODE_PROP(PT_CLASS_INTERPOLATED_STRING_PART, "value");

pt_method_site pt_ish_resolve_concat_type_site;

/* $initializerExprTypeResolver->resolveConcatType($left, $right) */
zv::Val resolveConcatType(zval *resolver, zval *left, zval *right)
{
	zv::Args argv{left, right};
	return pt_call_method_cached(pt_ish_resolve_concat_type_site, Z_OBJ_P(resolver), PT_LC("resolveconcattype"), 2, argv);
}

/* foreach ($expr->parts as $part): the array iterated (an addref'ed copy,
 * as foreach holds it), or UNDEF with the warning raised for a non-array;
 * false = pending exception */
[[nodiscard]] bool partsOf(zval *expr, zv::Arr &out)
{
	zval *parts = ptoh::operand(pt_ish_parts, expr);
	if (UNEXPECTED(parts == NULL)) return false;
	if (UNEXPECTED(Z_TYPE_P(parts) != IS_ARRAY)) {
		zend_error(E_WARNING, "foreach() argument must be of type array|object, %s given", zend_zval_value_name(parts));
		if (UNEXPECTED(EG(exception))) return false;
		out = zv::Arr::empty();
		return true;
	}
	out = zv::Arr::copyOfTable(Z_ARRVAL_P(parts));
	return true;
}

} // namespace

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\ExprHandler\InterpolatedStringHandler; UNDEF =
 * pending exception. */
class InterpolatedStringHandler
{
public:
	static constexpr const char *closureName = "PHPStan\\Analyser\\ExprHandler\\InterpolatedStringHandler::{closure}";
	static constexpr uint32_t defaultNarrowingHelperSlot = slots::defaultNarrowingHelper;

	explicit InterpolatedStringHandler(zend_object *self) : self(self) {}

	/* the constructor body: the promoted properties */
	void construct(zval *initializerExprTypeResolver, zval *implicitToStringCallHelper, zval *expressionResultFactory, zval *defaultNarrowingHelper) const
	{
		pt_write_slot(self, slots::initializerExprTypeResolver, initializerExprTypeResolver);
		pt_write_slot(self, slots::implicitToStringCallHelper, implicitToStringCallHelper);
		pt_write_slot(self, slots::expressionResultFactory, expressionResultFactory);
		pt_write_slot(self, slots::defaultNarrowingHelper, defaultNarrowingHelper);
	}

	/* Mirrors supports(); false = pending exception */
	[[nodiscard]] static bool supports(zval *expr, bool &out)
	{
		int is = ptoh::isInstance(expr, PT_CLASS_INTERPOLATED_STRING);
		if (UNEXPECTED(is < 0)) return false;
		out = is == 1;
		return true;
	}

	/* Mirrors processExpr(). */
	zv::Val processExpr(zval *nodeScopeResolver, zval *stmt, zval *expr, zval *scope, zval *storage, zval *nodeCallback, zval *context) const
	{
		zval *beforeScope = scope;
		bool hasYield = false;
		zv::Val throwPoints = zv::Arr::empty();
		zv::Arr variableFlows = zv::Arr::empty();
		zv::Val impurePoints = zv::Arr::empty();
		bool isAlwaysTerminating = false;
		zv::Arr partResults = zv::Arr::empty();
		zv::Val scopeHold;

		zv::Arr parts;
		if (UNEXPECTED(!partsOf(expr, parts))) return zv::Val();
		zend_class_entry *exprCe = pt_class(PT_CLASS_EXPR);
		if (UNEXPECTED(exprCe == NULL)) return zv::Val();
		zval *helper = OBJ_PROP_NUM(self, slots::implicitToStringCallHelper);
		for (zv::ArrayEntry entry : zv::TableRef(parts.table())) {
			zval *part = entry.value().deref().raw();
			if (Z_TYPE_P(part) != IS_OBJECT || !instanceof_function(Z_OBJCE_P(part), exprCe)) {
				continue;
			}
			zv::Val partContext = pt_expression_context_enter_deep_keeping_value_flow(context);
			if (UNEXPECTED(partContext.isUndef())) return zv::Val();
			zv::Val partResult = pt_node_scope_resolver_process_expr_node(nodeScopeResolver, stmt, part, scope, storage, nodeCallback, partContext.raw());
			if (UNEXPECTED(partResult.isUndef())) return zv::Val();
			zv::Val variableFlow = pt_expression_result_variable_flow(partResult.raw());
			if (UNEXPECTED(variableFlow.isUndef())) return zv::Val();
			variableFlows.push(std::move(variableFlow));
			partResults.separate();
			{
				zval stored;
				ZVAL_COPY(&stored, partResult.raw());
				zend_hash_index_update(partResults.table(), Z_OBJ_HANDLE_P(part), &stored);
			}
			if (!hasYield && UNEXPECTED(!pt_expression_result_has_yield(partResult.raw(), hasYield))) return zv::Val();
			{
				zv::Val hold;
				zval *points = pt_expression_result_throw_points(partResult.raw(), hold);
				if (UNEXPECTED(points == NULL || !ptse::mergeInto(throwPoints, points))) return zv::Val();
			}
			{
				zv::Val hold;
				zval *points = pt_expression_result_impure_points(partResult.raw(), hold);
				if (UNEXPECTED(points == NULL || !ptse::mergeInto(impurePoints, points))) return zv::Val();
			}

			zv::Val toStringResult = pt_implicit_to_string_call_helper_process_implicit_to_string_call(helper, part, scope, partResult.raw());
			if (UNEXPECTED(toStringResult.isUndef())) return zv::Val();
			{
				zv::Val hold;
				zval *points = pt_expression_result_throw_points(toStringResult.raw(), hold);
				if (UNEXPECTED(points == NULL || !ptse::mergeInto(throwPoints, points))) return zv::Val();
			}
			{
				zv::Val hold;
				zval *points = pt_expression_result_impure_points(toStringResult.raw(), hold);
				if (UNEXPECTED(points == NULL || !ptse::mergeInto(impurePoints, points))) return zv::Val();
			}

			if (!isAlwaysTerminating && UNEXPECTED(!pt_expression_result_is_always_terminating(partResult.raw(), isAlwaysTerminating))) return zv::Val();
			zv::Val nextHold;
			zval *nextScope = pt_expression_result_scope(partResult.raw(), nextHold);
			if (UNEXPECTED(nextScope == NULL)) return zv::Val();
			scopeHold = zv::Val::copyOf(zv::Ref(nextScope));
			scope = scopeHold.raw();
		}

		zv::Val variableFlow = pt_variable_flow_sequence_list(variableFlows.table());
		if (UNEXPECTED(variableFlow.isUndef())) return zv::Val();
		zv::Val typeCallback = pt_native_closure(&typeCallbackBody, self, expr, partResults.raw());
		zv::Val specifyTypesCallback = pt_native_closure(&ptse::specifyDefaultTypesBody<InterpolatedStringHandler>, self, expr);
		pt_expression_result_args args(scope, beforeScope, expr, hasYield, isAlwaysTerminating, throwPoints.raw(), impurePoints.raw(), typeCallback.raw(), specifyTypesCallback.raw());
		args.withVariableFlow(variableFlow.raw());
		return pt_expression_result_create(OBJ_PROP_NUM(self, slots::expressionResultFactory), args);
	}

	/* the handler entry (Engine.h) */
	static zv::Val processExprEntry(zend_object *handler, zval *nodeScopeResolver, zval *stmt, zval *expr, zval *scope, zval *storage, zval *nodeCallback, zval *context)
	{
		return InterpolatedStringHandler(handler).processExpr(nodeScopeResolver, stmt, expr, scope, storage, nodeCallback, context);
	}

private:
	zend_object *self;

	/* function (bool $nativeTypesPromoted) use ($expr, $partResults): Type —
	 * captures: $this, $expr, $partResults */
	static void typeCallbackBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		if (UNEXPECTED(!ptse::requireArgs(argc, 1, closureName))) return;
		bool nativeTypesPromoted = zend_is_true(&argv[0]);
		zv::Val type;
		pt_engine_with_stack([&]() { type = resolveType(captures, nativeTypesPromoted); });
		if (UNEXPECTED(type.isUndef())) return;
		type.intoReturnValue(return_value);
	}

	static zv::Val resolveType(zval *captures, bool nativeTypesPromoted)
	{
		zval *resolver = OBJ_PROP_NUM(Z_OBJ(captures[0]), slots::initializerExprTypeResolver);
		zval *expr = &captures[1];
		HashTable *partResults = Z_ARRVAL(captures[2]);
		zv::Val resultType = zv::Val::null();
		zv::Arr parts;
		if (UNEXPECTED(!partsOf(expr, parts))) return zv::Val();
		zend_class_entry *partCe = pt_class(PT_CLASS_INTERPOLATED_STRING_PART);
		if (UNEXPECTED(partCe == NULL)) return zv::Val();
		for (zv::ArrayEntry entry : zv::TableRef(parts.table())) {
			zval *part = entry.value().deref().raw();
			zv::Val partType;
			if (Z_TYPE_P(part) == IS_OBJECT && instanceof_function(Z_OBJCE_P(part), partCe)) {
				zval *value = ptoh::operand(pt_ish_part_value, part);
				if (UNEXPECTED(value == NULL)) return zv::Val();
				zval constantString;
				if (UNEXPECTED(!pt_constant_string_type_new(&constantString, Z_STR_P(value)))) return zv::Val();
				partType = zv::Val::adopt(constantString);
			} else {
				zval *partResult = Z_TYPE_P(part) == IS_OBJECT ? zend_hash_index_find(partResults, Z_OBJ_HANDLE_P(part)) : NULL;
				if (UNEXPECTED(partResult == NULL)) {
					if (Z_TYPE_P(part) != IS_OBJECT) {
						zend_type_error("spl_object_id(): Argument #1 ($object) must be of type object, %s given", zend_zval_value_name(part));
						return zv::Val();
					}
					zend_error(E_WARNING, "Undefined array key " ZEND_LONG_FMT, (zend_long) Z_OBJ_HANDLE_P(part));
					if (UNEXPECTED(EG(exception))) return zv::Val();
					zend_throw_error(NULL, "Call to a member function %s() on null", nativeTypesPromoted ? "getNativeType" : "getType");
					return zv::Val();
				}
				zv::Val type = ptse::typeOf(partResult, nativeTypesPromoted);
				if (UNEXPECTED(type.isUndef())) return zv::Val();
				if (UNEXPECTED(Z_TYPE_P(type.raw()) != IS_OBJECT)) {
					zend_throw_error(NULL, "Call to a member function toString() on %s", zend_zval_value_name(type.raw()));
					return zv::Val();
				}
				partType = pt_type_call(Z_OBJ_P(type.raw()), PT_LC("tostring"), 0, NULL);
				if (UNEXPECTED(partType.isUndef())) return zv::Val();
			}
			if (resultType.isNull()) {
				resultType = std::move(partType);
				continue;
			}

			resultType = resolveConcatType(resolver, resultType.raw(), partType.raw());
			if (UNEXPECTED(resultType.isUndef())) return zv::Val();
		}

		if (!resultType.isNull()) return resultType;
		zval empty;
		if (UNEXPECTED(!pt_constant_string_type_new(&empty, ZSTR_EMPTY_ALLOC()))) return zv::Val();
		return zv::Val::adopt(empty);
	}
};

} // namespace phpstanturbo

using phpstanturbo::InterpolatedStringHandler;

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

void pt_register_interpolated_string_handler()
{
	reg::Class cls("PHPStan\\Analyser\\ExprHandler\\InterpolatedStringHandler");
	ptdecl::InterpolatedStringHandler::declareClass(cls);
	ptdecl::InterpolatedStringHandler::declareProperties(cls);

	/* the real parameter class names: the DI container autowires the
	 * service by reflecting the constructor */
	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *initializerExprTypeResolver, *implicitToStringCallHelper, *expressionResultFactory, *defaultNarrowingHelper;
		if (!zp::parse<zp::Obj, zp::Obj, zp::Obj, zp::Obj>(execute_data, initializerExprTypeResolver, implicitToStringCallHelper, expressionResultFactory, defaultNarrowingHelper)) RETURN_THROWS();
		InterpolatedStringHandler(Z_OBJ_P(ZEND_THIS)).construct(initializerExprTypeResolver, implicitToStringCallHelper, expressionResultFactory, defaultNarrowingHelper);
	});

	cls.method(sigs::supports, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *expr;
		if (!zp::parse<zp::Obj>(execute_data, expr)) RETURN_THROWS();
		bool out;
		if (UNEXPECTED(!InterpolatedStringHandler::supports(expr, out))) RETURN_THROWS();
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
		PT_RETURN_VAL(InterpolatedStringHandler(Z_OBJ_P(ZEND_THIS)).processExpr(nodeScopeResolver, stmt, expr, scope, storage, nodeCallback, context));
	});

	cls.shadow(&pt_ce_interpolated_string_handler);
	pt_expr_handler_entry_register(&pt_ce_interpolated_string_handler, &InterpolatedStringHandler::processExprEntry);
}

/* }}} */
