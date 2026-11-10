/*
 * What FunctionCallableNodeHandler.cpp, MethodCallableNodeHandler.cpp and
 * StaticMethodCallableNodeHandler.cpp share: the bound arguments of a partial
 * function application, evaluated when the closure is created. The twins
 * repeat the same loop:
 *
 *   foreach ($expr->getOriginalNode()->getRawArgs() as $arg) {
 *       if (!$arg instanceof Arg) {
 *           continue;
 *       }
 *       $argResult = $nodeScopeResolver->processExprNode($stmt, $arg->value, $scope, $storage, $nodeCallback, ExpressionContext::createDeep($context->shouldResolveTemplateArguments()));
 *       $scope = $argResult->getScope();
 *       $hasYield = $hasYield || $argResult->hasYield();
 *       $throwPoints = array_merge($throwPoints, $argResult->getThrowPoints());
 *       $impurePoints = array_merge($impurePoints, $argResult->getImpurePoints());
 *       $isAlwaysTerminating = $isAlwaysTerminating || $argResult->isAlwaysTerminating();
 *       $variableFlows[] = $argResult->getVariableFlow();
 *       $argsDependencies[] = $argResult->getDependencies();
 *   }
 */

#ifndef PHPSTANTURBO_CALLABLE_NODE_ARGS_H
#define PHPSTANTURBO_CALLABLE_NODE_ARGS_H

#include "support.h"
#include "zv.h"
#include "VirtualExprHandlers.h"
#include "CallHandlerSupport.h"

namespace ptveh {

inline NodeProp argValue = PT_NODE_PROP(PT_CLASS_ARG, "value");

/* The loop above over the raw arguments of the *CallableNode's original
 * call: currentScope (kept alive in scopeHold), hasYield,
 * isAlwaysTerminating, throwPoints and impurePoints are updated in place,
 * each argument's variable flow and dependencies pushed to flows and
 * dependencies; false = pending exception */
[[nodiscard]] inline bool processBoundArgs(zval *originalNode, zval *nodeScopeResolver, zval *stmt, zval *&currentScope, zv::Val &scopeHold, zval *storage, zval *nodeCallback, zval *context, bool &hasYield, bool &isAlwaysTerminating, zv::Val &throwPoints, zv::Val &impurePoints, zv::Arr &flows, zv::Arr &dependencies)
{
	zv::Val rawArgsHold;
	zval *rawArgs = pt_call_like_raw_args(Z_OBJ_P(originalNode), rawArgsHold);
	if (UNEXPECTED(rawArgs == NULL)) return false;
	zend_class_entry *argCe = pt_class(PT_CLASS_ARG);
	if (UNEXPECTED(argCe == NULL)) return false;
	for (auto entry : zv::ArrRef(rawArgs)) {
		zv::Ref arg = entry.value().deref();
		if (!arg.instanceOf(argCe)) continue;
		zval *value = nodeRead(argValue, arg.raw());
		if (UNEXPECTED(value == NULL)) return false;
		zv::Val argContext = createDeepContext(context);
		if (UNEXPECTED(argContext.isUndef())) return false;
		zv::Val argResult = pt_node_scope_resolver_process_expr_node(nodeScopeResolver, stmt, value, currentScope, storage, nodeCallback, argContext.raw());
		if (UNEXPECTED(argResult.isUndef())) return false;
		zv::Val argScopeHold;
		zval *argScope = pt_expression_result_scope(argResult.raw(), argScopeHold);
		if (UNEXPECTED(argScope == NULL)) return false;
		scopeHold = argScopeHold.isUndef() ? zv::Val::copyOf(zv::Ref(argScope)) : std::move(argScopeHold);
		currentScope = scopeHold.raw();
		bool argHasYield;
		if (UNEXPECTED(!pt_expression_result_has_yield(argResult.raw(), argHasYield))) return false;
		hasYield = hasYield || argHasYield;
		zv::Val borrowedHold;
		zval *borrowed = pt_expression_result_throw_points(argResult.raw(), borrowedHold);
		if (UNEXPECTED(borrowed == NULL)) return false;
		throwPoints = ptcall::arrayMerge(throwPoints.raw(), borrowed);
		zv::Val impureHold;
		borrowed = pt_expression_result_impure_points(argResult.raw(), impureHold);
		if (UNEXPECTED(borrowed == NULL)) return false;
		impurePoints = ptcall::arrayMerge(impurePoints.raw(), borrowed);
		if (!isAlwaysTerminating && UNEXPECTED(!pt_expression_result_is_always_terminating(argResult.raw(), isAlwaysTerminating))) return false;
		zv::Val flow = pt_expression_result_variable_flow(argResult.raw());
		if (UNEXPECTED(flow.isUndef())) return false;
		flows.push(std::move(flow));
		zv::Val dependencyHold;
		zval *dependency = pt_expression_result_dependencies(argResult.raw(), dependencyHold);
		if (UNEXPECTED(dependency == NULL)) return false;
		dependencies.push(zv::Ref(dependency));
	}
	return true;
}

} // namespace ptveh

#endif
