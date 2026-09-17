/*
 * What the small expression handler ports share (CastHandler.cpp,
 * CastStringHandler.cpp, InterpolatedStringHandler.cpp,
 * UnaryMinusHandler.cpp, UnaryPlusHandler.cpp, BitwiseNotHandler.cpp, the
 * inc/dec handlers, the clone/eval/exit/include/print/shell-exec/throw/
 * error-suppress/pipe handlers and the yield handlers): the reads of a
 * processed child's result the twins hand straight to
 * ExpressionResultFactory::create(), the `$nativeTypesPromoted ?
 * $result->getNativeType() : $result->getType()` read, and the
 * `fn (TypeSpecifierContext $context, bool $nativeTypesPromoted) =>
 * $this->defaultNarrowingHelper->specifyDefaultTypes($expr, $context)`
 * closure most of them create.
 */

#ifndef PHPSTANTURBO_SIMPLE_EXPR_HANDLERS_H
#define PHPSTANTURBO_SIMPLE_EXPR_HANDLERS_H

#include "OperatorHandlers.h"
#include "CallHandlerSupport.h"

namespace ptse {

/* {{{ a processed child's result */

/* $result->getScope() / ->getVariableFlow() / ->hasYield() /
 * ->isAlwaysTerminating() / ->getThrowPoints() / ->getImpurePoints() — the
 * borrowed readers (kept alive by the result and the holds) */
struct ChildResult
{
	zval *scope = NULL;
	zval *throwPoints = NULL;
	zval *impurePoints = NULL;
	zv::Val variableFlow;
	bool hasYield = false;
	bool isAlwaysTerminating = false;
	zv::Val scopeHold;
	zv::Val throwPointsHold;
	zv::Val impurePointsHold;

	/* false = pending exception */
	[[nodiscard]] bool read(zval *result)
	{
		scope = pt_expression_result_scope(result, scopeHold);
		if (UNEXPECTED(scope == NULL)) return false;
		variableFlow = pt_expression_result_variable_flow(result);
		if (UNEXPECTED(variableFlow.isUndef())) return false;
		if (UNEXPECTED(!pt_expression_result_has_yield(result, hasYield))) return false;
		if (UNEXPECTED(!pt_expression_result_is_always_terminating(result, isAlwaysTerminating))) return false;
		throwPoints = pt_expression_result_throw_points(result, throwPointsHold);
		if (UNEXPECTED(throwPoints == NULL)) return false;
		impurePoints = pt_expression_result_impure_points(result, impurePointsHold);
		return impurePoints != NULL;
	}
};

/* $nativeTypesPromoted ? $result->getNativeType() : $result->getType() */
inline zv::Val typeOf(zval *result, bool nativeTypesPromoted)
{
	return nativeTypesPromoted ? pt_expression_result_get_native_type(result) : pt_expression_result_get_type(result);
}

/* $result->getThrowPoints() / ->getImpurePoints() as an owned array (the
 * borrowed reader copied) */
inline zv::Val throwPointsOf(zval *result)
{
	zv::Val hold;
	zval *value = pt_expression_result_throw_points(result, hold);
	if (UNEXPECTED(value == NULL)) return zv::Val();
	return hold.isUndef() ? zv::Val::copyOf(zv::Ref(value)) : std::move(hold);
}

inline zv::Val impurePointsOf(zval *result)
{
	zv::Val hold;
	zval *value = pt_expression_result_impure_points(result, hold);
	if (UNEXPECTED(value == NULL)) return zv::Val();
	return hold.isUndef() ? zv::Val::copyOf(zv::Ref(value)) : std::move(hold);
}

/* $into = array_merge($into, $more) of two arrays; false = pending exception */
[[nodiscard]] inline bool mergeInto(zv::Val &into, zval *more)
{
	if (UNEXPECTED(Z_TYPE_P(more) != IS_ARRAY)) {
		zend_type_error("array_merge(): Argument #2 must be of type array, %s given", zend_zval_value_name(more));
		return false;
	}
	into = ptcall::arrayMerge(into.raw(), more);
	return true;
}

/* array_merge($points, [$value]) */
inline zv::Val mergeOne(zval *points, zv::Val value)
{
	if (UNEXPECTED(Z_TYPE_P(points) != IS_ARRAY)) {
		zend_type_error("array_merge(): Argument #1 must be of type array, %s given", zend_zval_value_name(points));
		return zv::Val();
	}
	zv::Arr one = zv::Arr::create(1);
	one.push(std::move(value));
	return ptcall::arrayMerge(points, one.raw());
}

/* $result->getScope() as an owned value */
inline zv::Val scopeOf(zval *result)
{
	zv::Val hold;
	zval *value = pt_expression_result_scope(result, hold);
	if (UNEXPECTED(value == NULL)) return zv::Val();
	return hold.isUndef() ? zv::Val::copyOf(zv::Ref(value)) : std::move(hold);
}

/* }}} */

/* {{{ the inc/dec handlers' value flow */

/* $write->getId() of a VariableWrite */
inline zv::Val variableWriteId(zval *write)
{
	bool error = false;
	const pt_variable_write_slots *writeSlots = pt_variable_write_slots_of(Z_OBJ_P(write), error);
	if (writeSlots != NULL) return zv::Val::copyOf(zv::ObjRef(write).propAtOffset(writeSlots->id));
	if (UNEXPECTED(error)) return zv::Val();
	return pt_type_call(Z_OBJ_P(write), PT_LC("getid"), 0, NULL);
}

/* $valueFlowWrite !== null ? $context->enterDeep()->enterValueFlow($valueFlowWrite,
 * false) : $context->enterDeep() */
inline zv::Val valueFlowContext(zval *context, zval *valueFlowWrite)
{
	zv::Val deep = pt_expression_context_enter_deep(context);
	if (UNEXPECTED(deep.isUndef()) || Z_TYPE_P(valueFlowWrite) == IS_NULL) return deep;
	return pt_expression_context_enter_value_flow(deep.raw(), valueFlowWrite, false);
}

/* VariableFlow::sequence($varResult->getVariableFlow(), $valueFlowWrite !==
 * null && $context->isValueConsumed() ? VariableFlow::inputs($valueFlowWrite->getId(),
 * $context->getValueFlowTarget() !== null ? $context->getValueFlowTarget()->getId() : null)
 * : null, VariableFlowBuilder::targetWrite($var, $kind, $assignedScope, $storage)) */
inline zv::Val incDecFlow(zval *varFlow, zval *valueFlowWrite, zval *context, zval *var, zend_long kind, zval *assignedScope, zval *storage)
{
	zv::Val inputsFlow = zv::Val::null();
	if (Z_TYPE_P(valueFlowWrite) != IS_NULL) {
		bool consumed;
		if (UNEXPECTED(!pt_expression_context_is_value_consumed(context, consumed))) return zv::Val();
		if (consumed) {
			zv::Val writeId = variableWriteId(valueFlowWrite);
			if (UNEXPECTED(writeId.isUndef())) return zv::Val();
			zv::Val valueFlowTarget = pt_expression_context_get_value_flow_target(context);
			if (UNEXPECTED(valueFlowTarget.isUndef())) return zv::Val();
			zv::Val targetId = zv::Val::null();
			if (!valueFlowTarget.isNull()) {
				zv::Val target = pt_expression_context_get_value_flow_target(context);
				if (UNEXPECTED(target.isUndef())) return zv::Val();
				targetId = variableWriteId(target.raw());
				if (UNEXPECTED(targetId.isUndef())) return zv::Val();
			}
			inputsFlow = pt_variable_flow_inputs(zval_get_long(writeId.raw()), targetId.raw());
			if (UNEXPECTED(inputsFlow.isUndef())) return zv::Val();
		}
	}
	zv::Val targetWriteFlow = pt_variable_flow_builder_target_write(var, kind, assignedScope, storage, NULL);
	if (UNEXPECTED(targetWriteFlow.isUndef())) return zv::Val();
	zv::Args flows{varFlow, inputsFlow.raw(), targetWriteFlow.raw()};
	return pt_variable_flow_sequence(3, flows);
}

/* }}} */

/* {{{ the closures */

/* the ArgumentCountError of a closure body called with too few arguments;
 * false = raised */
[[nodiscard]] inline bool requireArgs(uint32_t argc, uint32_t expected, const char *closureName)
{
	return ptcall::requireArguments(argc, expected, closureName);
}

/* fn (TypeSpecifierContext $context, bool $nativeTypesPromoted) =>
 * $this->defaultNarrowingHelper->specifyDefaultTypes($expr, $context) —
 * captures: $this, $expr. H names the handler: `H::defaultNarrowingHelperSlot`
 * and `H::closureName`. */
template <typename H>
void specifyDefaultTypesBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
{
	if (UNEXPECTED(!requireArgs(argc, 2, H::closureName))) return;
	zv::Val types = pt_default_narrowing_helper_specify_default_types(OBJ_PROP_NUM(Z_OBJ(captures[0]), H::defaultNarrowingHelperSlot), &captures[1], &argv[0]);
	if (UNEXPECTED(types.isUndef())) return;
	types.intoReturnValue(return_value);
}

/* static fn (bool $nativeTypesPromoted): Type => ($nativeTypesPromoted ?
 * $result->getNativeType() : $result->getType()) — captures: $result */
template <typename H>
void childTypeBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
{
	if (UNEXPECTED(!requireArgs(argc, 1, H::closureName))) return;
	bool nativeTypesPromoted = zend_is_true(&argv[0]);
	zv::Val type;
	pt_engine_with_stack([&]() { type = typeOf(&captures[0], nativeTypesPromoted); });
	if (UNEXPECTED(type.isUndef())) return;
	type.intoReturnValue(return_value);
}

/* static fn (bool $nativeTypesPromoted): Type => new MixedType() /
 * new NonAcceptingNeverType() — captures nothing */
template <typename H>
void mixedTypeBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
{
	(void) captures;
	(void) argv;
	if (UNEXPECTED(!requireArgs(argc, 1, H::closureName))) return;
	zv::Val type = pt_type_new_mixed_type();
	if (UNEXPECTED(type.isUndef())) return;
	type.intoReturnValue(return_value);
}

template <typename H>
void nonAcceptingNeverTypeBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
{
	(void) captures;
	(void) argv;
	if (UNEXPECTED(!requireArgs(argc, 1, H::closureName))) return;
	if (UNEXPECTED(!pt_non_accepting_never_type_new(return_value))) ZVAL_NULL(return_value);
}

/* }}} */

} // namespace ptse

#endif /* PHPSTANTURBO_SIMPLE_EXPR_HANDLERS_H */
