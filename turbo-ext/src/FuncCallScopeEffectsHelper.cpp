/*
 * PHPStanTurbo\FuncCallScopeEffectsHelper — native implementation of
 * PHPStan\Analyser\ExprHandler\Helper\FuncCallScopeEffectsHelper.
 *
 * A final DI service (#[AutowiredService] with an #[AutowiredParameter]
 * bool): the constructor keeps the twin's arginfo. FuncCallHandler calls
 * applyCallScopeEffects() for every function call and applyArrayWalkResult()
 * for array_walk() with a by-reference closure; both are exported as
 * pt_func_call_scope_effects_helper_* direct entries.
 *
 * The common call asks the function reflection's name once per branch the
 * twin tests: FunctionReflectionAccess.cpp reads NativeFunctionReflection's
 * name, isBuiltin() and hasSideEffects() from their slots, so those tests
 * cost a slot read and a string compare. MutatingScope, NodeScopeResolver,
 * AssignHandler, ArgsResult, ExpressionResult, OutputBufferHelper,
 * TypeCombinator, ConstantArrayTypeBuilder and the CallLike argument reader
 * are called through their direct entries; the closures
 * getArrayFunctionAppendingType() creates never escape and are spelled as
 * C++ lambdas over the by-reference state they share, the mapValueType()
 * callbacks of applyArrayWalkResult() are native closures. GeneralizePrecision
 * stays PHP behind the cached site in the block below.
 */

#include "support.h"
#include "generated/FuncCallScopeEffectsHelper.h"

namespace slots = ptdecl::FuncCallScopeEffectsHelper::slot;
namespace sigs = ptdecl::FuncCallScopeEffectsHelper::sig;
#include "zv.h"
#include "TypeTraits.h"
#include "TypeOps.h"
#include "Engine.h"
#include "AnalyserValues.h"

zend_class_entry *pt_ce_func_call_scope_effects_helper = nullptr;

namespace {

/* {{{ the PHP collaborators (one site each; switch to their direct entries
 * once they are ported) */

pt_method_site pt_fcse_less_specific_site;

/* $this->assignHandler->processVirtualAssign($nodeScopeResolver, $scope,
 * $storage, $stmt, $var, $assignedExpr, $nodeCallback)->getScope() */
zv::Val processVirtualAssignScope(zval *assignHandler, zval *nodeScopeResolver, zval *scope, zval *storage, zval *stmt, zval *var, zval *assignedExpr, zval *nodeCallback)
{
	if (UNEXPECTED(Z_TYPE_P(var) != IS_OBJECT)) {
		zend_type_error("PHPStan\\Analyser\\ExprHandler\\AssignHandler::processVirtualAssign(): Argument #5 ($var) must be of type PhpParser\\Node\\Expr, %s given", zend_zval_value_name(var));
		return zv::Val();
	}
	zv::Val result = pt_assign_handler_process_virtual_assign(assignHandler, nodeScopeResolver, scope, storage, stmt, var, assignedExpr, nodeCallback, NULL);
	if (UNEXPECTED(result.isUndef())) return zv::Val();
	zv::Val hold;
	return ptav::own(pt_expression_result_scope(result.raw(), hold));
}

/* GeneralizePrecision::lessSpecific() */
zv::Val generalizePrecisionLessSpecific()
{
	return pt_call_static_cached(pt_fcse_less_specific_site, PT_CLASS_GENERALIZE_PRECISION, PT_LC("lessspecific"), 0, NULL);
}

/* }}} */

/* {{{ node reads */

pt_property_site pt_fcse_arg_value_site;
pt_property_site pt_fcse_arg_unpack_site;

/* $call->getArgs() (the CallLike reader, support.h) as an owned value;
 * UNDEF = pending exception */
zv::Val callArgs(zval *call)
{
	zv::Val hold;
	zval *args = pt_call_like_args(Z_OBJ_P(call), hold);
	if (UNEXPECTED(args == NULL)) return zv::Val();
	return hold.isUndef() ? zv::Val::copyOf(zv::Ref(args)) : std::move(hold);
}

/* count($array) of an array getter's result */
inline uint32_t countOf(zval *array)
{
	return Z_TYPE_P(array) == IS_ARRAY ? zend_hash_num_elements(Z_ARRVAL_P(array)) : 0;
}

/* $arg->value of an Arg object (dereferenced, borrowed); the warning PHP
 * raises for a read on a non-object, null then; NULL = pending exception */
zval *argValueOf(zval *arg)
{
	ZVAL_DEREF(arg);
	if (UNEXPECTED(Z_TYPE_P(arg) != IS_OBJECT)) {
		zend_error(E_WARNING, "Attempt to read property \"value\" on %s", zend_zval_value_name(arg));
		if (UNEXPECTED(EG(exception))) return NULL;
		return &EG(uninitialized_zval);
	}
	zval *value = pt_property_cached(pt_fcse_arg_value_site, Z_OBJ_P(arg), PT_LC("value"));
	if (UNEXPECTED(value == NULL)) {
		zend_error(E_WARNING, "Undefined property: %s::$value", ZSTR_VAL(Z_OBJCE_P(arg)->name));
		if (UNEXPECTED(EG(exception))) return NULL;
		return &EG(uninitialized_zval);
	}
	ZVAL_DEREF(value);
	if (UNEXPECTED(Z_TYPE_P(value) == IS_UNDEF)) {
		zend_throw_error(NULL, "Typed property %s::$value must not be accessed before initialization", ZSTR_VAL(Z_OBJCE_P(arg)->name));
		return NULL;
	}
	return value;
}

/* $args[$index]->value, with the warnings of a missing key; NULL = pending
 * exception */
zval *argValueAt(zval *args, zend_ulong index)
{
	zval *arg = Z_TYPE_P(args) == IS_ARRAY ? zend_hash_index_find(Z_ARRVAL_P(args), index) : NULL;
	if (UNEXPECTED(arg == NULL)) {
		zend_error(E_WARNING, "Undefined array key " ZEND_ULONG_FMT, index);
		if (UNEXPECTED(EG(exception))) return NULL;
		zval null;
		ZVAL_NULL(&null);
		return argValueOf(&null);
	}
	return argValueOf(arg);
}

/* isset($args[$index]) */
inline bool argIsset(zval *args, zend_ulong index)
{
	zval *arg = Z_TYPE_P(args) == IS_ARRAY ? zend_hash_index_find(Z_ARRVAL_P(args), index) : NULL;
	if (arg == NULL) return false;
	ZVAL_DEREF(arg);
	return Z_TYPE_P(arg) != IS_NULL;
}

/* }}} */

/* {{{ calls */

/* $argsResult->requireArgResult($argValue): the direct entry for an
 * expression, the method (and its TypeError) for anything else */
zv::Val requireArgResult(zval *argsResult, zval *argValue)
{
	if (EXPECTED(Z_TYPE_P(argValue) == IS_OBJECT)) return pt_args_result_require_arg_result(argsResult, argValue);
	return pt_type_call(Z_OBJ_P(argsResult), PT_LC("requireargresult"), 1, argValue);
}

/* $type->method(...$argv) of a Type by name, the Error PHP raises for a
 * call on a non-object */
zv::Val typeCall(zval *type, const char *lcname, size_t len, const char *name, uint32_t argc, zval *argv)
{
	if (UNEXPECTED(Z_TYPE_P(type) != IS_OBJECT)) {
		zend_throw_error(NULL, "Call to a member function %s() on %s", name, zend_zval_value_name(type));
		return zv::Val();
	}
	return pt_type_call(Z_OBJ_P(type), lcname, len, argc, argv);
}

/* a TrinaryLogic-returning Type method; -1 = pending exception */
zend_long typeCallTrinary(zval *type, const char *lcname, size_t len, const char *name)
{
	zv::Val result = typeCall(type, lcname, len, name, 0, NULL);
	if (UNEXPECTED(result.isUndef())) return -1;
	return pt_type_trinary_value(result.raw());
}

/* new NativeTypeExpr($type, $nativeType) */
zv::Val newNativeTypeExpr(zval *type, zval *nativeType)
{
	zv::Args argv{type, nativeType};
	return pt_type_new(PT_CLASS_NATIVE_TYPE_EXPR, 2, argv);
}

/* new FuncCall(new <Name class>($literal), $args) */
zv::Val newFuncCall(int nameClassIdx, const char *literal, size_t len, zval *args)
{
	zv::Val nameString = zv::Val::string(literal, len);
	zv::Val name = pt_type_new(nameClassIdx, 1, nameString.raw());
	if (UNEXPECTED(name.isUndef())) return zv::Val();
	zv::Args argv{name.raw(), args};
	return pt_type_new(PT_CLASS_FUNC_CALL, 2, argv);
}

/* $scope = $scope->invalidateExpression(new FuncCall(new <Name class>($literal), $args)) */
[[nodiscard]] bool invalidateFuncCall(zv::Val &scope, int nameClassIdx, const char *literal, size_t len, zval *args)
{
	zv::Val call = newFuncCall(nameClassIdx, literal, len, args);
	if (UNEXPECTED(call.isUndef())) return false;
	zv::Val invalidated = pt_mutating_scope_invalidate_expression(Z_OBJ_P(scope.raw()), call.raw());
	if (UNEXPECTED(invalidated.isUndef())) return false;
	scope = std::move(invalidated);
	return true;
}

/* the function reflection's getName() as a string; NULL = pending
 * exception (the TypeError of a non-string name) */
zend_string *functionName(zval *functionReflection, zv::Val &hold)
{
	zval *name = pt_function_reflection_name(functionReflection, hold);
	if (UNEXPECTED(name == NULL)) return NULL;
	if (UNEXPECTED(Z_TYPE_P(name) != IS_STRING)) {
		zend_type_error("%s::getName(): Return value must be of type string, %s returned", ZSTR_VAL(Z_OBJCE_P(functionReflection)->name), zend_zval_value_name(name));
		return NULL;
	}
	return Z_STR_P(name);
}

/* $functionReflection !== null && in_array($functionReflection->getName(), [...], true);
 * false with an exception pending on failure */
template <size_t N>
[[nodiscard]] bool nameIsOneOf(zval *functionReflection, const char *const names[N], bool &out)
{
	out = false;
	if (functionReflection == NULL) return true;
	zv::Val hold;
	zend_string *name = functionName(functionReflection, hold);
	if (UNEXPECTED(name == NULL)) return false;
	for (size_t i = 0; i < N; i++) {
		if (zend_string_equals_cstr(name, names[i], strlen(names[i]))) {
			out = true;
			break;
		}
	}
	return true;
}

const char *const pt_fcse_json[2] = { "json_encode", "json_decode" };
const char *const pt_fcse_file_put_contents[1] = { "file_put_contents" };
const char *const pt_fcse_pop_shift[2] = { "array_pop", "array_shift" };
const char *const pt_fcse_push_unshift[2] = { "array_push", "array_unshift" };
const char *const pt_fcse_fopen[2] = { "fopen", "file_get_contents" };
const char *const pt_fcse_shuffle[1] = { "shuffle" };
const char *const pt_fcse_array_splice[1] = { "array_splice" };
const char *const pt_fcse_sort[3] = { "sort", "rsort", "usort" };
const char *const pt_fcse_key_sort[8] = { "natcasesort", "natsort", "arsort", "asort", "ksort", "krsort", "uasort", "uksort" };
const char *const pt_fcse_extract[1] = { "extract" };
const char *const pt_fcse_clearstatcache[2] = { "clearstatcache", "unlink" };

/* static fn (Type $type): Type => $captured — captures: the type */
void constantTypeCallbackBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
{
	(void) argv;
	if (UNEXPECTED(argc < 1)) {
		zend_throw_error(zend_ce_argument_count_error, "Too few arguments to function PHPStan\\Analyser\\ExprHandler\\Helper\\FuncCallScopeEffectsHelper::{closure}(), %u passed and exactly 1 expected", argc);
		return;
	}
	ZVAL_COPY(return_value, &captures[0]);
}

/* }}} */

} // namespace

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\ExprHandler\Helper\FuncCallScopeEffectsHelper. */
class FuncCallScopeEffectsHelper
{
public:
	explicit FuncCallScopeEffectsHelper(zend_object *self) : self(self) {}

	/* the constructor body: the promoted properties */
	void construct(zval *outputBufferHelper, bool rememberPossiblyImpureFunctionValues, zval *assignHandler) const
	{
		zv::ObjRef object(self);
		object.propAtWrite(slots::outputBufferHelper, zv::Val::copyOf(zv::Ref(outputBufferHelper)));
		object.propAtWrite(slots::rememberPossiblyImpureFunctionValues, zv::Val::boolean(rememberPossiblyImpureFunctionValues));
		object.propAtWrite(slots::assignHandler, zv::Val::copyOf(zv::Ref(assignHandler)));
	}

	/* Mirrors applyArrayWalkResult(). */
	zv::Val applyArrayWalkResult(zval *nodeScopeResolver, zval *stmt, zval *arrayWalkArrayArg, zval *arrayWalkValueTypes, zval *argsResult, zval *scope, zval *storage, zval *nodeCallback) const
	{
		zv::Val arrayWalkArrayArgResult = requireArgResult(argsResult, arrayWalkArrayArg);
		if (UNEXPECTED(arrayWalkArrayArgResult.isUndef())) return zv::Val();
		zv::Val arrayWalkOriginalArrayType = pt_expression_result_get_type_on_scope(arrayWalkArrayArgResult.raw(), scope, false);
		if (UNEXPECTED(arrayWalkOriginalArrayType.isUndef())) return zv::Val();
		zv::Val arrayWalkOriginalArrayNativeType = pt_expression_result_get_type_on_scope(arrayWalkArrayArgResult.raw(), scope, true);
		if (UNEXPECTED(arrayWalkOriginalArrayNativeType.isUndef())) return zv::Val();
		zval *arrayWalkValueType = arrayOffset(arrayWalkValueTypes, 0);
		if (UNEXPECTED(arrayWalkValueType == NULL)) return zv::Val();
		zval *arrayWalkValueNativeType = arrayOffset(arrayWalkValueTypes, 1);
		if (UNEXPECTED(arrayWalkValueNativeType == NULL)) return zv::Val();

		zv::Val valueCallback = pt_native_closure(&constantTypeCallbackBody, arrayWalkValueType);
		zv::Val newArrayType = typeCall(arrayWalkOriginalArrayType.raw(), PT_LC("mapvaluetype"), "mapValueType", 1, valueCallback.raw());
		if (UNEXPECTED(newArrayType.isUndef())) return zv::Val();
		zv::Val nativeValueCallback = pt_native_closure(&constantTypeCallbackBody, arrayWalkValueNativeType);
		zv::Val newArrayNativeType = typeCall(arrayWalkOriginalArrayNativeType.raw(), PT_LC("mapvaluetype"), "mapValueType", 1, nativeValueCallback.raw());
		if (UNEXPECTED(newArrayNativeType.isUndef())) return zv::Val();

		zv::Val assignedExpr = newNativeTypeExpr(newArrayType.raw(), newArrayNativeType.raw());
		if (UNEXPECTED(assignedExpr.isUndef())) return zv::Val();
		return processVirtualAssignScope(assignHandler(), nodeScopeResolver, scope, storage, stmt, arrayWalkArrayArg, assignedExpr.raw(), nodeCallback);
	}

	/* Mirrors applyCallScopeEffects(); $functionReflection /
	 * $parametersAcceptor NULL for null. */
	zv::Val applyCallScopeEffects(zval *nodeScopeResolver, zval *stmt, zval *normalizedExpr, zval *functionReflection, zval *parametersAcceptor, zval *argsResult, zval *scopeIn, zval *scopeBeforeArgs, zval *storage, zval *nodeCallback) const
	{
		zv::Val scope = zv::Val::copyOf(zv::Ref(scopeIn));

		if (parametersAcceptor != NULL && instanceof_function(Z_OBJCE_P(parametersAcceptor), pt_ce_closure_type)) {
			zv::Val closureImpurePoints = pt_type_call(Z_OBJ_P(parametersAcceptor), PT_LC("getimpurepoints"), 0, NULL);
			if (UNEXPECTED(closureImpurePoints.isUndef())) return zv::Val();
			bool inClass = false;
			if (countOf(closureImpurePoints.raw()) > 0 && UNEXPECTED(!pt_mutating_scope_is_in_class(Z_OBJ_P(scope.raw()), inClass))) return zv::Val();
			if (countOf(closureImpurePoints.raw()) > 0 && inClass) {
				zend_long isStatic = typeCallTrinary(parametersAcceptor, PT_LC("isstaticclosure"), "isStaticClosure");
				if (UNEXPECTED(isStatic < 0)) return zv::Val();
				bool isStaticClosure = isStatic == PT_TRI_YES;

				bool keepPropertyFetches = isStaticClosure;
				if (keepPropertyFetches) {
					zv::Val usedVariables = pt_type_call(Z_OBJ_P(parametersAcceptor), PT_LC("getusedvariables"), 0, NULL);
					if (UNEXPECTED(usedVariables.isUndef())) return zv::Val();
					keepPropertyFetches = isEmptyArray(usedVariables.raw());
				}
				if (keepPropertyFetches) {
					zv::Val invalidateExpressions = pt_type_call(Z_OBJ_P(parametersAcceptor), PT_LC("getinvalidateexpressions"), 0, NULL);
					if (UNEXPECTED(invalidateExpressions.isUndef())) return zv::Val();
					keepPropertyFetches = isEmptyArray(invalidateExpressions.raw());
				}

				if (isStaticClosure) {
					zv::Val invalidated = invalidateObjectArgs(nodeScopeResolver, normalizedExpr, argsResult, scope.raw(), storage, nodeCallback);
					if (UNEXPECTED(invalidated.isUndef())) return zv::Val();
					scope = std::move(invalidated);
				}
				zv::Val thisName = zv::Val::string(PT_LC("this"));
				zv::Val thisVariable = pt_type_new(PT_CLASS_VARIABLE, 1, thisName.raw());
				if (UNEXPECTED(thisVariable.isUndef())) return zv::Val();
				zv::Val invalidated = pt_mutating_scope_invalidate_expression(Z_OBJ_P(scope.raw()), thisVariable.raw(), true, NULL, keepPropertyFetches);
				if (UNEXPECTED(invalidated.isUndef())) return zv::Val();
				scope = std::move(invalidated);
			}
		}

		if (functionReflection != NULL && parametersAcceptor != NULL && Z_TYPE_P(OBJ_PROP_NUM(self, slots::rememberPossiblyImpureFunctionValues)) == IS_TRUE) {
			zv::Val sideEffectsHold;
			zval *sideEffects = pt_function_reflection_has_side_effects(functionReflection, sideEffectsHold);
			if (UNEXPECTED(sideEffects == NULL)) return zv::Val();
			zend_long sideEffectsValue = trinaryOf(sideEffects, "maybe");
			if (UNEXPECTED(sideEffectsValue < 0)) return zv::Val();
			bool isBuiltin = false;
			if (sideEffectsValue == PT_TRI_MAYBE && UNEXPECTED(!pt_function_reflection_is_builtin(functionReflection, isBuiltin))) return zv::Val();
			if (sideEffectsValue == PT_TRI_MAYBE && !isBuiltin) {
				zv::Val nameHold;
				zend_string *name = functionName(functionReflection, nameHold);
				if (UNEXPECTED(name == NULL)) return zv::Val();
				zv::Val description = zv::Val::adoptString(zend_string_concat2(ZSTR_VAL(name), ZSTR_LEN(name), PT_LC("()")));
				zv::Args exprArgv{normalizedExpr, normalizedExpr, description.raw()};
				zv::Val possiblyImpureCallExpr = pt_type_new(PT_CLASS_POSSIBLY_IMPURE_CALL_EXPR, 3, exprArgv);
				if (UNEXPECTED(possiblyImpureCallExpr.isUndef())) return zv::Val();
				zv::Val returnTypeHold;
				zval *returnType = pt_parameters_acceptor_return_type(parametersAcceptor, returnTypeHold);
				if (UNEXPECTED(returnType == NULL)) return zv::Val();
				zval mixed;
				if (UNEXPECTED(!pt_mixed_type_new(&mixed))) return zv::Val();
				zv::Val mixedHold = zv::Val::adopt(mixed);
				zv::Val assigned = pt_mutating_scope_assign_expression(Z_OBJ_P(scope.raw()), Z_OBJ_P(possiblyImpureCallExpr.raw()), returnType, mixedHold.raw());
				if (UNEXPECTED(assigned.isUndef())) return zv::Val();
				scope = std::move(assigned);
			}
		}

		bool matches;
		if (UNEXPECTED(!nameIsOneOf<2>(functionReflection, pt_fcse_json, matches))) return zv::Val();
		if (matches) {
			zval empty;
			ZVAL_EMPTY_ARRAY(&empty);
			if (UNEXPECTED(!invalidateFuncCall(scope, PT_CLASS_NAME, PT_LC("json_last_error"), &empty))) return zv::Val();
			if (UNEXPECTED(!invalidateFuncCall(scope, PT_CLASS_FULLY_QUALIFIED, PT_LC("json_last_error"), &empty))) return zv::Val();
			if (UNEXPECTED(!invalidateFuncCall(scope, PT_CLASS_NAME, PT_LC("json_last_error_msg"), &empty))) return zv::Val();
			if (UNEXPECTED(!invalidateFuncCall(scope, PT_CLASS_FULLY_QUALIFIED, PT_LC("json_last_error_msg"), &empty))) return zv::Val();
		}

		if (UNEXPECTED(!nameIsOneOf<1>(functionReflection, pt_fcse_file_put_contents, matches))) return zv::Val();
		if (matches) {
			zv::Val args = callArgs(normalizedExpr);
			if (UNEXPECTED(args.isUndef())) return zv::Val();
			if (countOf(args.raw()) > 0) {
				for (int nameClassIdx : { (int) PT_CLASS_NAME, (int) PT_CLASS_FULLY_QUALIFIED }) {
					zv::Val callArgsNow = callArgs(normalizedExpr);
					if (UNEXPECTED(callArgsNow.isUndef())) return zv::Val();
					zval *firstArg = firstArgOf(callArgsNow.raw());
					if (UNEXPECTED(firstArg == NULL)) return zv::Val();
					zv::Arr contentArgs = zv::Arr::create(1);
					contentArgs.push(zv::Ref(firstArg));
					if (UNEXPECTED(!invalidateFuncCall(scope, nameClassIdx, PT_LC("file_get_contents"), contentArgs.raw()))) return zv::Val();
				}
			}
		}

		if (UNEXPECTED(!nameIsOneOf<2>(functionReflection, pt_fcse_pop_shift, matches))) return zv::Val();
		if (matches) {
			zv::Val args = callArgs(normalizedExpr);
			if (UNEXPECTED(args.isUndef())) return zv::Val();
			if (countOf(args.raw()) >= 1) {
				zval *arrayArg = argValueAt(args.raw(), 0);
				if (UNEXPECTED(arrayArg == NULL)) return zv::Val();
				zv::Val arrayArgHold = zv::Val::copyOf(zv::Ref(arrayArg));
				zv::Val arrayArgResult = requireArgResult(argsResult, arrayArgHold.raw());
				if (UNEXPECTED(arrayArgResult.isUndef())) return zv::Val();
				zv::Val arrayArgType = pt_expression_result_get_type_on_scope(arrayArgResult.raw(), scope.raw(), false);
				if (UNEXPECTED(arrayArgType.isUndef())) return zv::Val();
				zv::Val arrayArgNativeType = pt_expression_result_get_type_on_scope(arrayArgResult.raw(), scope.raw(), true);
				if (UNEXPECTED(arrayArgNativeType.isUndef())) return zv::Val();
				zv::Val nameHold;
				zend_string *name = functionName(functionReflection, nameHold);
				if (UNEXPECTED(name == NULL)) return zv::Val();
				bool isArrayPop = zend_string_equals_literal(name, "array_pop");

				zv::Val type = isArrayPop
					? typeCall(arrayArgType.raw(), PT_LC("poparray"), "popArray", 0, NULL)
					: typeCall(arrayArgType.raw(), PT_LC("shiftarray"), "shiftArray", 0, NULL);
				if (UNEXPECTED(type.isUndef())) return zv::Val();
				zv::Val nativeType = isArrayPop
					? typeCall(arrayArgNativeType.raw(), PT_LC("poparray"), "popArray", 0, NULL)
					: typeCall(arrayArgNativeType.raw(), PT_LC("shiftarray"), "shiftArray", 0, NULL);
				if (UNEXPECTED(nativeType.isUndef())) return zv::Val();
				if (UNEXPECTED(!assignVirtually(scope, nodeScopeResolver, storage, stmt, arrayArgHold.raw(), type.raw(), nativeType.raw(), nodeCallback))) return zv::Val();
			}
		}

		if (UNEXPECTED(!nameIsOneOf<2>(functionReflection, pt_fcse_push_unshift, matches))) return zv::Val();
		if (matches) {
			zv::Val args = callArgs(normalizedExpr);
			if (UNEXPECTED(args.isUndef())) return zv::Val();
			if (countOf(args.raw()) >= 2) {
				zv::Val argsNow = callArgs(normalizedExpr);
				if (UNEXPECTED(argsNow.isUndef())) return zv::Val();
				zval *arrayArg = argValueAt(argsNow.raw(), 0);
				if (UNEXPECTED(arrayArg == NULL)) return zv::Val();
				zv::Val arrayArgHold = zv::Val::copyOf(zv::Ref(arrayArg));

				zv::Val type = getArrayFunctionAppendingType(functionReflection, scopeBeforeArgs, normalizedExpr, argsResult);
				if (UNEXPECTED(type.isUndef())) return zv::Val();
				zv::Val nativeScope = pt_mutating_scope_do_not_treat_phpdoc_types_as_certain(Z_OBJ_P(scopeBeforeArgs));
				if (UNEXPECTED(nativeScope.isUndef())) return zv::Val();
				zv::Val nativeType = getArrayFunctionAppendingType(functionReflection, nativeScope.raw(), normalizedExpr, argsResult);
				if (UNEXPECTED(nativeType.isUndef())) return zv::Val();
				if (UNEXPECTED(!assignVirtually(scope, nodeScopeResolver, storage, stmt, arrayArgHold.raw(), type.raw(), nativeType.raw(), nodeCallback))) return zv::Val();
			}
		}

		if (UNEXPECTED(!nameIsOneOf<2>(functionReflection, pt_fcse_fopen, matches))) return zv::Val();
		if (matches) {
			zv::Val zero = zv::Val::integer(0);
			zv::Val range = pt_integer_range_create_all_greater_than_or_equal_to(zero.raw());
			if (UNEXPECTED(range.isUndef())) return zv::Val();
			zval string;
			if (UNEXPECTED(!pt_string_type_new(&string))) return zv::Val();
			zv::Val stringHold = zv::Val::adopt(string);
			zval array;
			if (UNEXPECTED(!pt_array_type_new(&array, range.raw(), stringHold.raw()))) return zv::Val();
			zv::Val arrayHold = zv::Val::adopt(array);
			zval list;
			if (UNEXPECTED(!pt_accessory_array_list_type_new(&list))) return zv::Val();
			zv::Val listHold = zv::Val::adopt(list);
			zv::Arr members = zv::Arr::create(2);
			members.push(std::move(arrayHold));
			members.push(std::move(listHold));
			zval intersection;
			if (UNEXPECTED(!pt_intersection_type_new(&intersection, members.raw()))) return zv::Val();
			zv::Val intersectionHold = zv::Val::adopt(intersection);

			zval integer;
			if (UNEXPECTED(!pt_integer_type_new(&integer))) return zv::Val();
			zv::Val integerHold = zv::Val::adopt(integer);
			zval nativeString;
			if (UNEXPECTED(!pt_string_type_new(&nativeString))) return zv::Val();
			zv::Val nativeStringHold = zv::Val::adopt(nativeString);
			zval nativeArray;
			if (UNEXPECTED(!pt_array_type_new(&nativeArray, integerHold.raw(), nativeStringHold.raw()))) return zv::Val();
			zv::Val nativeArrayHold = zv::Val::adopt(nativeArray);

			zend_string *variableName = zend_string_init(PT_LC("http_response_header"), 0);
			zv::Val assigned = pt_mutating_scope_assign_variable(Z_OBJ_P(scope.raw()), variableName, intersectionHold.raw(), nativeArrayHold.raw(), pt_trinary_singleton(PT_TRI_YES));
			zend_string_release(variableName);
			if (UNEXPECTED(assigned.isUndef())) return zv::Val();
			scope = std::move(assigned);
		}

		if (UNEXPECTED(!nameIsOneOf<1>(functionReflection, pt_fcse_shuffle, matches))) return zv::Val();
		if (matches) {
			if (UNEXPECTED(!applyArrayTransform(scope, nodeScopeResolver, stmt, normalizedExpr, argsResult, storage, nodeCallback, PT_LC("shufflearray"), "shuffleArray"))) return zv::Val();
		}

		if (UNEXPECTED(!nameIsOneOf<1>(functionReflection, pt_fcse_array_splice, matches))) return zv::Val();
		if (matches) {
			zv::Val args = callArgs(normalizedExpr);
			if (UNEXPECTED(args.isUndef())) return zv::Val();
			if (countOf(args.raw()) >= 2 && UNEXPECTED(!applyArraySplice(scope, nodeScopeResolver, stmt, normalizedExpr, argsResult, storage, nodeCallback))) return zv::Val();
		}

		if (UNEXPECTED(!nameIsOneOf<3>(functionReflection, pt_fcse_sort, matches))) return zv::Val();
		if (matches) {
			zv::Val args = callArgs(normalizedExpr);
			if (UNEXPECTED(args.isUndef())) return zv::Val();
			if (countOf(args.raw()) >= 1 && UNEXPECTED(!applyArrayTransform(scope, nodeScopeResolver, stmt, normalizedExpr, argsResult, storage, nodeCallback, PT_LC("shufflearray"), "shuffleArray"))) return zv::Val();
		}

		if (UNEXPECTED(!nameIsOneOf<8>(functionReflection, pt_fcse_key_sort, matches))) return zv::Val();
		if (matches) {
			zv::Val args = callArgs(normalizedExpr);
			if (UNEXPECTED(args.isUndef())) return zv::Val();
			if (countOf(args.raw()) >= 1 && UNEXPECTED(!applyArrayTransform(scope, nodeScopeResolver, stmt, normalizedExpr, argsResult, storage, nodeCallback, PT_LC("makelistmaybe"), "makeListMaybe"))) return zv::Val();
		}

		if (UNEXPECTED(!nameIsOneOf<1>(functionReflection, pt_fcse_extract, matches))) return zv::Val();
		if (matches && UNEXPECTED(!applyExtract(scope, normalizedExpr, argsResult))) return zv::Val();

		if (UNEXPECTED(!nameIsOneOf<2>(functionReflection, pt_fcse_clearstatcache, matches))) return zv::Val();
		if (matches) {
			zv::Val next = pt_mutating_scope_after_clearstatcache_call(Z_OBJ_P(scope.raw()));
			if (UNEXPECTED(next.isUndef())) return zv::Val();
			scope = std::move(next);
		}

		if (functionReflection != NULL) {
			zv::Val nameHold;
			zend_string *name = functionName(functionReflection, nameHold);
			if (UNEXPECTED(name == NULL)) return zv::Val();
			if (ZSTR_LEN(name) >= sizeof("openssl") - 1 && memcmp(ZSTR_VAL(name), "openssl", sizeof("openssl") - 1) == 0) {
				zv::Val sslNameHold;
				zend_string *sslName = functionName(functionReflection, sslNameHold);
				if (UNEXPECTED(sslName == NULL)) return zv::Val();
				zv::Val next = pt_mutating_scope_after_open_ssl_call(Z_OBJ_P(scope.raw()), sslName);
				if (UNEXPECTED(next.isUndef())) return zv::Val();
				scope = std::move(next);
			}
		}

		zend_long outputBufferDelta = 0;
		if (functionReflection != NULL) {
			zval *outputBufferHelper = OBJ_PROP_NUM(self, slots::outputBufferHelper);
			if (UNEXPECTED(Z_TYPE_P(outputBufferHelper) != IS_OBJECT)) return uninitialized("outputBufferHelper");
			zv::Val nameHold;
			zend_string *name = functionName(functionReflection, nameHold);
			if (UNEXPECTED(name == NULL)) return zv::Val();
			bool ok;
			outputBufferDelta = pt_output_buffer_helper_get_level_delta(outputBufferHelper, name, ok);
			if (UNEXPECTED(!ok)) return zv::Val();
		}
		if (outputBufferDelta != 0) {
			zv::Val next = pt_output_buffer_helper_apply_level_delta(OBJ_PROP_NUM(self, slots::outputBufferHelper), nodeScopeResolver, scope.raw(), outputBufferDelta);
			if (UNEXPECTED(next.isUndef())) return zv::Val();
			scope = std::move(next);
		}

		bool pureCallable = false;
		if (parametersAcceptor != NULL) {
			zend_class_entry *callableCe = pt_class_loaded(PT_CLASS_CALLABLE_PARAMETERS_ACCEPTOR);
			if (UNEXPECTED(EG(exception))) return zv::Val();
			if (callableCe != NULL && instanceof_function(Z_OBJCE_P(parametersAcceptor), callableCe)) {
				zv::Val impurePoints = pt_type_call(Z_OBJ_P(parametersAcceptor), PT_LC("getimpurepoints"), 0, NULL);
				if (UNEXPECTED(impurePoints.isUndef())) return zv::Val();
				pureCallable = countOf(impurePoints.raw()) == 0;
			}
		}
		bool invalidateVolatile;
		if (functionReflection != NULL) {
			bool isBuiltin;
			if (UNEXPECTED(!pt_function_reflection_is_builtin(functionReflection, isBuiltin))) return zv::Val();
			invalidateVolatile = false;
			if (!isBuiltin) {
				zv::Val sideEffectsHold;
				zval *sideEffects = pt_function_reflection_has_side_effects(functionReflection, sideEffectsHold);
				if (UNEXPECTED(sideEffects == NULL)) return zv::Val();
				zend_long sideEffectsValue = trinaryOf(sideEffects, "no");
				if (UNEXPECTED(sideEffectsValue < 0)) return zv::Val();
				invalidateVolatile = sideEffectsValue != PT_TRI_NO;
			}
		} else {
			invalidateVolatile = !pureCallable;
		}
		if (invalidateVolatile) {
			zv::Val next = pt_mutating_scope_invalidate_volatile_expressions(Z_OBJ_P(scope.raw()));
			if (UNEXPECTED(next.isUndef())) return zv::Val();
			scope = std::move(next);
		}
		return scope;
	}

private:
	zend_object *self;

	zval *assignHandler() const { return OBJ_PROP_NUM(self, slots::assignHandler); }

	/* the engine's Error for reading a never-written typed property */
	zv::Val uninitialized(const char *property) const
	{
		zend_throw_error(NULL, "Typed property %s::$%s must not be accessed before initialization", ZSTR_VAL(self->ce->name), property);
		return zv::Val();
	}

	/* $array === [] */
	static bool isEmptyArray(zval *value)
	{
		return Z_TYPE_P(value) == IS_ARRAY && zend_hash_num_elements(Z_ARRVAL_P(value)) == 0;
	}

	/* the PT_TRI_* value of a TrinaryLogic a getter returned, the Error of
	 * a method call on anything else; -1 = pending exception */
	static zend_long trinaryOf(zval *trinary, const char *method)
	{
		if (UNEXPECTED(Z_TYPE_P(trinary) != IS_OBJECT)) {
			zend_throw_error(NULL, "Call to a member function %s() on %s", method, zend_zval_value_name(trinary));
			return -1;
		}
		return pt_type_trinary_value(trinary);
	}

	/* $array[$index] of the array{Type, Type} parameter (the warning of a
	 * missing key, null then); NULL = pending exception */
	static zval *arrayOffset(zval *array, zend_ulong index)
	{
		zval *found = zend_hash_index_find(Z_ARRVAL_P(array), index);
		if (UNEXPECTED(found == NULL)) {
			zend_error(E_WARNING, "Undefined array key " ZEND_ULONG_FMT, index);
			if (UNEXPECTED(EG(exception))) return NULL;
			return &EG(uninitialized_zval);
		}
		ZVAL_DEREF(found);
		return found;
	}

	/* $args[0] (the warning of a missing key, null then); NULL = pending
	 * exception */
	static zval *firstArgOf(zval *args)
	{
		zval *found = Z_TYPE_P(args) == IS_ARRAY ? zend_hash_index_find(Z_ARRVAL_P(args), 0) : NULL;
		if (UNEXPECTED(found == NULL)) {
			zend_error(E_WARNING, "Undefined array key 0");
			if (UNEXPECTED(EG(exception))) return NULL;
			return &EG(uninitialized_zval);
		}
		ZVAL_DEREF(found);
		return found;
	}

	/* $scope = $this->assignHandler->processVirtualAssign($nodeScopeResolver,
	 * $scope, $storage, $stmt, $arrayArg, new NativeTypeExpr($type,
	 * $nativeType), $nodeCallback)->getScope(); false = pending exception */
	[[nodiscard]] bool assignVirtually(zv::Val &scope, zval *nodeScopeResolver, zval *storage, zval *stmt, zval *arrayArg, zval *type, zval *nativeType, zval *nodeCallback) const
	{
		zv::Val assignedExpr = newNativeTypeExpr(type, nativeType);
		if (UNEXPECTED(assignedExpr.isUndef())) return false;
		zv::Val next = processVirtualAssignScope(assignHandler(), nodeScopeResolver, scope.raw(), storage, stmt, arrayArg, assignedExpr.raw(), nodeCallback);
		if (UNEXPECTED(next.isUndef())) return false;
		scope = std::move(next);
		return true;
	}

	/* the shuffle / sort / key-sort branches: $arrayArg =
	 * $normalizedExpr->getArgs()[0]->value, then the virtual assign of
	 * $argsResult->requireArgResult($arrayArg)->getTypeOnScope($scope,
	 * false|true)->method(); false = pending exception */
	[[nodiscard]] bool applyArrayTransform(zv::Val &scope, zval *nodeScopeResolver, zval *stmt, zval *normalizedExpr, zval *argsResult, zval *storage, zval *nodeCallback, const char *lcname, size_t len, const char *name) const
	{
		zv::Val args = callArgs(normalizedExpr);
		if (UNEXPECTED(args.isUndef())) return false;
		zval *arrayArg = argValueAt(args.raw(), 0);
		if (UNEXPECTED(arrayArg == NULL)) return false;
		zv::Val arrayArgHold = zv::Val::copyOf(zv::Ref(arrayArg));

		zv::Val result = requireArgResult(argsResult, arrayArgHold.raw());
		if (UNEXPECTED(result.isUndef())) return false;
		zv::Val argType = pt_expression_result_get_type_on_scope(result.raw(), scope.raw(), false);
		if (UNEXPECTED(argType.isUndef())) return false;
		zv::Val type = typeCall(argType.raw(), lcname, len, name, 0, NULL);
		if (UNEXPECTED(type.isUndef())) return false;

		zv::Val nativeResult = requireArgResult(argsResult, arrayArgHold.raw());
		if (UNEXPECTED(nativeResult.isUndef())) return false;
		zv::Val argNativeType = pt_expression_result_get_type_on_scope(nativeResult.raw(), scope.raw(), true);
		if (UNEXPECTED(argNativeType.isUndef())) return false;
		zv::Val nativeType = typeCall(argNativeType.raw(), lcname, len, name, 0, NULL);
		if (UNEXPECTED(nativeType.isUndef())) return false;

		return assignVirtually(scope, nodeScopeResolver, storage, stmt, arrayArgHold.raw(), type.raw(), nativeType.raw(), nodeCallback);
	}

	/* the array_splice() branch; false = pending exception */
	[[nodiscard]] bool applyArraySplice(zv::Val &scope, zval *nodeScopeResolver, zval *stmt, zval *normalizedExpr, zval *argsResult, zval *storage, zval *nodeCallback) const
	{
		zv::Val args = callArgs(normalizedExpr);
		if (UNEXPECTED(args.isUndef())) return false;
		zval *arrayArg = argValueAt(args.raw(), 0);
		if (UNEXPECTED(arrayArg == NULL)) return false;
		zv::Val arrayArgHold = zv::Val::copyOf(zv::Ref(arrayArg));
		zv::Val arrayArgResult = requireArgResult(argsResult, arrayArgHold.raw());
		if (UNEXPECTED(arrayArgResult.isUndef())) return false;
		zv::Val arrayArgType = pt_expression_result_get_type(arrayArgResult.raw());
		if (UNEXPECTED(arrayArgType.isUndef())) return false;
		zv::Val arrayArgNativeType = pt_expression_result_get_native_type(arrayArgResult.raw());
		if (UNEXPECTED(arrayArgNativeType.isUndef())) return false;

		zv::Val offsetArgs = callArgs(normalizedExpr);
		if (UNEXPECTED(offsetArgs.isUndef())) return false;
		zval *offsetArg = argValueAt(offsetArgs.raw(), 1);
		if (UNEXPECTED(offsetArg == NULL)) return false;
		zv::Val offsetArgHold = zv::Val::copyOf(zv::Ref(offsetArg));
		zv::Val offsetResult = requireArgResult(argsResult, offsetArgHold.raw());
		if (UNEXPECTED(offsetResult.isUndef())) return false;
		zv::Val offsetType = pt_expression_result_get_type(offsetResult.raw());
		if (UNEXPECTED(offsetType.isUndef())) return false;

		zv::Val lengthType;
		zv::Val lengthArgs = callArgs(normalizedExpr);
		if (UNEXPECTED(lengthArgs.isUndef())) return false;
		if (argIsset(lengthArgs.raw(), 2)) {
			zv::Val lengthArgsNow = callArgs(normalizedExpr);
			if (UNEXPECTED(lengthArgsNow.isUndef())) return false;
			zval *lengthArg = argValueAt(lengthArgsNow.raw(), 2);
			if (UNEXPECTED(lengthArg == NULL)) return false;
			zv::Val lengthArgHold = zv::Val::copyOf(zv::Ref(lengthArg));
			zv::Val lengthResult = requireArgResult(argsResult, lengthArgHold.raw());
			if (UNEXPECTED(lengthResult.isUndef())) return false;
			lengthType = pt_expression_result_get_type(lengthResult.raw());
			if (UNEXPECTED(lengthType.isUndef())) return false;
		} else {
			zval null;
			if (UNEXPECTED(!pt_null_type_new(&null))) return false;
			lengthType = zv::Val::adopt(null);
		}

		zv::Val replacementType;
		zv::Val replacementNativeType;
		zv::Val replacementArgs = callArgs(normalizedExpr);
		if (UNEXPECTED(replacementArgs.isUndef())) return false;
		if (argIsset(replacementArgs.raw(), 3)) {
			zv::Val replacementArgsNow = callArgs(normalizedExpr);
			if (UNEXPECTED(replacementArgsNow.isUndef())) return false;
			zval *replacementArg = argValueAt(replacementArgsNow.raw(), 3);
			if (UNEXPECTED(replacementArg == NULL)) return false;
			zv::Val replacementArgHold = zv::Val::copyOf(zv::Ref(replacementArg));
			zv::Val replacementResult = requireArgResult(argsResult, replacementArgHold.raw());
			if (UNEXPECTED(replacementResult.isUndef())) return false;
			replacementType = pt_expression_result_get_type(replacementResult.raw());
			if (UNEXPECTED(replacementType.isUndef())) return false;
			replacementNativeType = pt_expression_result_get_native_type(replacementResult.raw());
			if (UNEXPECTED(replacementNativeType.isUndef())) return false;
		} else {
			replacementType = emptyConstantArray();
			if (UNEXPECTED(replacementType.isUndef())) return false;
			replacementNativeType = emptyConstantArray();
			if (UNEXPECTED(replacementNativeType.isUndef())) return false;
		}

		zv::Args spliceArgs{offsetType.raw(), lengthType.raw(), replacementType.raw()};
		zv::Val type = typeCall(arrayArgType.raw(), PT_LC("splicearray"), "spliceArray", 3, spliceArgs);
		if (UNEXPECTED(type.isUndef())) return false;
		zv::Args nativeSpliceArgs{offsetType.raw(), lengthType.raw(), replacementNativeType.raw()};
		zv::Val nativeType = typeCall(arrayArgNativeType.raw(), PT_LC("splicearray"), "spliceArray", 3, nativeSpliceArgs);
		if (UNEXPECTED(nativeType.isUndef())) return false;

		return assignVirtually(scope, nodeScopeResolver, storage, stmt, arrayArgHold.raw(), type.raw(), nativeType.raw(), nodeCallback);
	}

	/* new ConstantArrayType([], []) */
	static zv::Val emptyConstantArray()
	{
		zval empty;
		ZVAL_EMPTY_ARRAY(&empty);
		zval array;
		if (UNEXPECTED(!pt_constant_array_type_new(&array, &empty, &empty))) return zv::Val();
		return zv::Val::adopt(array);
	}

	/* the extract() branch; false = pending exception */
	[[nodiscard]] static bool applyExtract(zv::Val &scope, zval *normalizedExpr, zval *argsResult)
	{
		zv::Val args = callArgs(normalizedExpr);
		if (UNEXPECTED(args.isUndef())) return false;
		zval *extractedArg = argValueAt(args.raw(), 0);
		if (UNEXPECTED(extractedArg == NULL)) return false;
		zv::Val extractedArgHold = zv::Val::copyOf(zv::Ref(extractedArg));
		zv::Val extractedResult = requireArgResult(argsResult, extractedArgHold.raw());
		if (UNEXPECTED(extractedResult.isUndef())) return false;
		zv::Val extractedType = pt_expression_result_get_type_on_scope(extractedResult.raw(), scope.raw(), false);
		if (UNEXPECTED(extractedType.isUndef())) return false;
		if (UNEXPECTED(Z_TYPE_P(extractedType.raw()) != IS_OBJECT)) {
			zend_throw_error(NULL, "Call to a member function getConstantArrays() on %s", zend_zval_value_name(extractedType.raw()));
			return false;
		}
		zv::Val constantArrays = pt_type_op(Z_OBJ_P(extractedType.raw()), PT_OP_GET_CONSTANT_ARRAYS, 0, NULL);
		if (UNEXPECTED(constantArrays.isUndef())) return false;
		if (countOf(constantArrays.raw()) == 0) {
			zv::Val next = pt_mutating_scope_after_extract_call(Z_OBJ_P(scope.raw()));
			if (UNEXPECTED(next.isUndef())) return false;
			scope = std::move(next);
			return true;
		}

		zv::Arr properties = zv::Arr::empty();
		zv::Arr optionalProperties = zv::Arr::empty();
		zv::Arr refCount = zv::Arr::empty();
		for (zv::ArrayEntry arrayEntry : zv::ArrRef(constantArrays.raw())) {
			zval *constantArray = arrayEntry.value().deref().raw();
			if (UNEXPECTED(Z_TYPE_P(constantArray) != IS_OBJECT)) {
				zend_throw_error(NULL, "Call to a member function getKeyTypes() on %s", zend_zval_value_name(constantArray));
				return false;
			}
			zv::Val keyTypes = pt_type_op(Z_OBJ_P(constantArray), PT_OP_GET_KEY_TYPES, 0, NULL);
			if (UNEXPECTED(keyTypes.isUndef())) return false;
			if (Z_TYPE_P(keyTypes.raw()) != IS_ARRAY) continue;
			for (zv::ArrayEntry keyEntry : zv::ArrRef(keyTypes.raw())) {
				zval *keyType = keyEntry.value().deref().raw();
				if (UNEXPECTED(Z_TYPE_P(keyType) != IS_OBJECT)) {
					zend_throw_error(NULL, "Call to a member function isString() on %s", zend_zval_value_name(keyType));
					return false;
				}
				zend_long isString = pt_type_op_trinary(Z_OBJ_P(keyType), PT_OP_IS_STRING, 0, NULL);
				if (UNEXPECTED(isString < 0)) return false;
				if (isString == PT_TRI_NO) {
					// integers as variable names not allowed
					continue;
				}
				zv::Val keyValue = pt_type_op(Z_OBJ_P(keyType), PT_OP_GET_VALUE, 0, NULL);
				if (UNEXPECTED(keyValue.isUndef())) return false;
				zv::Str key = zv::Str::adopt(zval_try_get_string(keyValue.raw()));
				if (UNEXPECTED(key.isNull())) return false;

				zval i;
				if (keyEntry.hasStringKey()) {
					ZVAL_STR(&i, keyEntry.stringKey());
				} else {
					ZVAL_LONG(&i, (zend_long) keyEntry.indexKey());
				}
				zv::Val valueTypes = pt_type_op(Z_OBJ_P(constantArray), PT_OP_GET_VALUE_TYPES, 0, NULL);
				if (UNEXPECTED(valueTypes.isUndef())) return false;
				zval *valueType = arrayDim(valueTypes.raw(), &i);
				if (UNEXPECTED(valueType == NULL)) return false;
				zv::Val valueTypeHold = zv::Val::copyOf(zv::Ref(valueType));
				zv::Val optionalValue = pt_type_call(Z_OBJ_P(constantArray), PT_LC("isoptionalkey"), 1, &i);
				if (UNEXPECTED(optionalValue.isUndef())) return false;
				bool optional = zend_is_true(optionalValue.raw());
				if (optional) {
					optionalProperties.push(zv::Val::string(key.get()));
				}
				zval *existing = zend_symtable_find(properties.table(), key.get());
				if (existing != NULL && Z_TYPE_P(existing) != IS_NULL) {
					zv::Args unionArgs{existing, valueTypeHold.raw()};
					zv::Val unioned = pt_type_combinator_union(2, unionArgs);
					if (UNEXPECTED(unioned.isUndef())) return false;
					properties.set(key.get(), std::move(unioned));
					refCount.separate();
					zval *count = zend_symtable_find(refCount.table(), key.get());
					if (count != NULL) {
						increment_function(count);
					}
				} else {
					properties.set(key.get(), std::move(valueTypeHold));
					refCount.set(key.get(), zv::Val::integer(1));
				}
			}
		}

		uint32_t constantArrayCount = countOf(constantArrays.raw());
		zv::Val propertiesHold = zv::Val(std::move(properties));
		for (zv::ArrayEntry entry : zv::ArrRef(propertiesHold.raw())) {
			zval nameKey;
			zend_string *stringName = entry.stringKeyOrNull();
			if (stringName != NULL) {
				ZVAL_STR(&nameKey, stringName);
			} else {
				ZVAL_LONG(&nameKey, (zend_long) entry.indexKey());
			}
			zv::Val type = zv::Val::copyOf(entry.value().deref());

			bool optional = false;
			if (stringName != NULL) {
				for (zv::ArrayEntry optionalEntry : zv::ArrRef(optionalProperties.raw())) {
					zval *optionalName = optionalEntry.value().raw();
					if (Z_TYPE_P(optionalName) == IS_STRING && zend_string_equals(Z_STR_P(optionalName), stringName)) {
						optional = true;
						break;
					}
				}
			}
			if (!optional) {
				zval *count = stringName != NULL ? zend_hash_find(refCount.table(), stringName) : zend_hash_index_find(refCount.table(), entry.indexKey());
				zend_long countValue = count != NULL ? zval_get_long(count) : 0;
				optional = countValue < (zend_long) constantArrayCount;
			}
			if (UNEXPECTED(stringName == NULL)) {
				zend_type_error("PHPStan\\Analyser\\MutatingScope::%s(): Argument #1 ($variableName) must be of type string, int given", optional ? "hasVariableType" : "assignVariable");
				return false;
			}

			if (!optional) {
				zv::Val next = pt_mutating_scope_assign_variable(Z_OBJ_P(scope.raw()), stringName, type.raw(), type.raw(), pt_trinary_singleton(PT_TRI_YES));
				if (UNEXPECTED(next.isUndef())) return false;
				scope = std::move(next);
				continue;
			}

			zv::Val hasVariable = pt_mutating_scope_has_variable_type(Z_OBJ_P(scope.raw()), stringName);
			if (UNEXPECTED(hasVariable.isUndef())) return false;
			zend_long hasVariableValue = trinaryOf(hasVariable.raw(), "no");
			if (UNEXPECTED(hasVariableValue < 0)) return false;
			if (hasVariableValue != PT_TRI_NO) {
				zv::Val variableType = pt_mutating_scope_get_variable_type(Z_OBJ_P(scope.raw()), stringName);
				if (UNEXPECTED(variableType.isUndef())) return false;
				zv::Args unionArgs{variableType.raw(), type.raw()};
				zv::Val unioned = pt_type_combinator_union(2, unionArgs);
				if (UNEXPECTED(unioned.isUndef())) return false;
				type = std::move(unioned);
			}
			zv::Val hasNow = pt_mutating_scope_has_variable_type(Z_OBJ_P(scope.raw()), stringName);
			if (UNEXPECTED(hasNow.isUndef())) return false;
			zend_long hasNowValue = trinaryOf(hasNow.raw(), "or");
			if (UNEXPECTED(hasNowValue < 0)) return false;
			zv::Val next = pt_mutating_scope_assign_variable(Z_OBJ_P(scope.raw()), stringName, type.raw(), type.raw(), pt_trinary_singleton(pt_trinary_or(hasNowValue, PT_TRI_MAYBE)));
			if (UNEXPECTED(next.isUndef())) return false;
			scope = std::move(next);
		}
		return true;
	}

	/* $array[$key] of a list read with the warning of a missing key (null
	 * then); NULL = pending exception */
	static zval *arrayDim(zval *array, zval *key)
	{
		if (UNEXPECTED(Z_TYPE_P(array) != IS_ARRAY)) {
			return &EG(uninitialized_zval);
		}
		zval *found = Z_TYPE_P(key) == IS_LONG ? zend_hash_index_find(Z_ARRVAL_P(array), (zend_ulong) Z_LVAL_P(key)) : zend_symtable_find(Z_ARRVAL_P(array), Z_STR_P(key));
		if (UNEXPECTED(found == NULL)) {
			if (Z_TYPE_P(key) == IS_LONG) {
				zend_error(E_WARNING, "Undefined array key " ZEND_LONG_FMT, Z_LVAL_P(key));
			} else {
				zend_error(E_WARNING, "Undefined array key \"%s\"", ZSTR_VAL(Z_STR_P(key)));
			}
			if (UNEXPECTED(EG(exception))) return NULL;
			return &EG(uninitialized_zval);
		}
		ZVAL_DEREF(found);
		return found;
	}

	/* Mirrors invalidateObjectArgs(). */
	static zv::Val invalidateObjectArgs(zval *nodeScopeResolver, zval *normalizedExpr, zval *argsResult, zval *scopeIn, zval *storage, zval *nodeCallback)
	{
		zv::Val scope = zv::Val::copyOf(zv::Ref(scopeIn));
		zv::Val args = callArgs(normalizedExpr);
		if (UNEXPECTED(args.isUndef())) return zv::Val();
		if (UNEXPECTED(Z_TYPE_P(args.raw()) != IS_ARRAY)) return scope;
		for (zv::ArrayEntry entry : zv::ArrRef(args.raw())) {
			zval *argValue = argValueOf(entry.value().raw());
			if (UNEXPECTED(argValue == NULL)) return zv::Val();
			zv::Val argValueHold = zv::Val::copyOf(zv::Ref(argValue));
			// a default-value argument ArgumentsNormalizer synthesized for an omitted
			// optional parameter was never processed, and holds no expression the caller
			// could observe afterwards
			if (UNEXPECTED(Z_TYPE_P(argValueHold.raw()) != IS_OBJECT)) {
				/* the method raises the TypeError of a non-expression */
				zv::Val found = pt_type_call(Z_OBJ_P(argsResult), PT_LC("findargresult"), 1, argValueHold.raw());
				if (UNEXPECTED(found.isUndef())) return zv::Val();
				if (Z_TYPE_P(found.raw()) == IS_NULL) continue;
				pt_throw_should_not_happen();
				return zv::Val();
			}
			zv::Val findHold;
			zval *argResult = pt_args_result_find_arg_result(argsResult, argValueHold.raw(), findHold);
			if (UNEXPECTED(argResult == NULL)) return zv::Val();
			if (Z_TYPE_P(argResult) == IS_NULL) continue;
			zv::Val argResultHold = zv::Val::copyOf(zv::Ref(argResult));

			zv::Val argType = pt_expression_result_get_type_on_scope(argResultHold.raw(), scope.raw(), false);
			if (UNEXPECTED(argType.isUndef())) return zv::Val();
			zend_long isObject = typeCallTrinary(argType.raw(), PT_LC("isobject"), "isObject");
			if (UNEXPECTED(isObject < 0)) return zv::Val();
			if (isObject == PT_TRI_NO) {
				zval resource;
				if (UNEXPECTED(!pt_resource_type_new(&resource))) return zv::Val();
				zv::Val resourceHold = zv::Val::adopt(resource);
				zv::Val isSuperType = pt_type_op(Z_OBJ_P(resourceHold.raw()), PT_OP_IS_SUPER_TYPE_OF, 1, argType.raw());
				if (UNEXPECTED(isSuperType.isUndef())) return zv::Val();
				zend_long verdict = pt_type_result_trinary(isSuperType.raw());
				if (UNEXPECTED(verdict < 0)) return zv::Val();
				if (verdict == PT_TRI_NO) continue;
			}

			zv::Val invalidateNode = pt_type_new(PT_CLASS_INVALIDATE_EXPR_NODE, 1, argValueHold.raw());
			if (UNEXPECTED(invalidateNode.isUndef())) return zv::Val();
			if (UNEXPECTED(!pt_node_scope_resolver_call_node_callback(nodeScopeResolver, nodeCallback, invalidateNode.raw(), scope.raw(), storage))) return zv::Val();
			zv::Val next = pt_mutating_scope_invalidate_expression(Z_OBJ_P(scope.raw()), argValueHold.raw(), true, NULL, false);
			if (UNEXPECTED(next.isUndef())) return zv::Val();
			scope = std::move(next);
		}

		return scope;
	}

	/* $scope->toWalkScope() and its ->nativeTypesPromoted, then
	 * $result->getTypeOnScope() on them — the twin's
	 * `->getTypeOnScope($scope->toWalkScope(), $scope->toWalkScope()->nativeTypesPromoted)` */
	static zv::Val typeOnWalkScope(zval *result, zval *scope)
	{
		zv::Val walkScope = pt_mutating_scope_to_walk_scope(Z_OBJ_P(scope));
		if (UNEXPECTED(walkScope.isUndef())) return zv::Val();
		zv::Val promotedScope = pt_mutating_scope_to_walk_scope(Z_OBJ_P(scope));
		if (UNEXPECTED(promotedScope.isUndef())) return zv::Val();
		bool promoted;
		if (UNEXPECTED(!pt_mutating_scope_native_types_promoted(Z_OBJ_P(promotedScope.raw()), promoted))) return zv::Val();
		return pt_expression_result_get_type_on_scope(result, walkScope.raw(), promoted);
	}

	/* the $setOffsetValueTypes closure of getArrayFunctionAppendingType():
	 * setOffsetValueType(offsetType (NULL = null), valueType, optional) for
	 * each call argument; nonConstantArrayWasUnpacked set when an unpacked
	 * argument is not a single constant array; false = pending exception */
	template <typename Setter>
	[[nodiscard]] static bool setOffsetValueTypes(zval *scope, zval *callArgs, zval *argsResult, Setter &&setOffsetValueType, bool &nonConstantArrayWasUnpacked)
	{
		for (zv::ArrayEntry entry : zv::ArrRef(callArgs)) {
			zval *callArg = entry.value().deref().raw();
			zval *callArgValue = argValueOf(callArg);
			if (UNEXPECTED(callArgValue == NULL)) return false;
			zv::Val callArgValueHold = zv::Val::copyOf(zv::Ref(callArgValue));
			zv::Val callArgResult = requireArgResult(argsResult, callArgValueHold.raw());
			if (UNEXPECTED(callArgResult.isUndef())) return false;
			zv::Val callArgType = typeOnWalkScope(callArgResult.raw(), scope);
			if (UNEXPECTED(callArgType.isUndef())) return false;

			zval *unpack = pt_property_cached(pt_fcse_arg_unpack_site, Z_OBJ_P(callArg), PT_LC("unpack"));
			if (unpack != NULL) {
				ZVAL_DEREF(unpack);
			}
			if (unpack != NULL && zend_is_true(unpack)) {
				zv::Val constantArrays = typeOpOn(callArgType.raw(), PT_OP_GET_CONSTANT_ARRAYS, "getConstantArrays");
				if (UNEXPECTED(constantArrays.isUndef())) return false;
				zv::Val iterableValueTypes;
				if (countOf(constantArrays.raw()) == 1) {
					zval *first = zend_hash_index_find(Z_ARRVAL_P(constantArrays.raw()), 0);
					if (UNEXPECTED(first == NULL)) {
						zend_error(E_WARNING, "Undefined array key 0");
						if (UNEXPECTED(EG(exception))) return false;
						zend_throw_error(NULL, "Call to a member function getValueTypes() on null");
						return false;
					}
					ZVAL_DEREF(first);
					iterableValueTypes = typeOpOn(first, PT_OP_GET_VALUE_TYPES, "getValueTypes");
					if (UNEXPECTED(iterableValueTypes.isUndef())) return false;
				} else {
					zv::Val iterableValueType = pt_type_op(Z_OBJ_P(callArgType.raw()), PT_OP_GET_ITERABLE_VALUE_TYPE, 0, NULL);
					if (UNEXPECTED(iterableValueType.isUndef())) return false;
					zv::Arr list = zv::Arr::create(1);
					list.push(std::move(iterableValueType));
					iterableValueTypes = zv::Val(std::move(list));
					nonConstantArrayWasUnpacked = true;
				}

				zend_long atLeastOnce = pt_type_op_trinary(Z_OBJ_P(callArgType.raw()), PT_OP_IS_ITERABLE_AT_LEAST_ONCE, 0, NULL);
				if (UNEXPECTED(atLeastOnce < 0)) return false;
				bool isOptional = atLeastOnce != PT_TRI_YES;
				if (Z_TYPE_P(iterableValueTypes.raw()) != IS_ARRAY) continue;
				for (zv::ArrayEntry valueEntry : zv::ArrRef(iterableValueTypes.raw())) {
					zval *iterableValueType = valueEntry.value().deref().raw();
					if (Z_TYPE_P(iterableValueType) == IS_OBJECT && instanceof_function(Z_OBJCE_P(iterableValueType), pt_ce_union_type)) {
						zv::Val innerTypes = pt_type_op(Z_OBJ_P(iterableValueType), PT_OP_GET_TYPES, 0, NULL);
						if (UNEXPECTED(innerTypes.isUndef())) return false;
						if (Z_TYPE_P(innerTypes.raw()) != IS_ARRAY) continue;
						for (zv::ArrayEntry innerEntry : zv::ArrRef(innerTypes.raw())) {
							if (UNEXPECTED(!setOffsetValueType(NULL, innerEntry.value().deref().raw(), isOptional))) return false;
						}
					} else if (UNEXPECTED(!setOffsetValueType(NULL, iterableValueType, isOptional))) {
						return false;
					}
				}
				continue;
			}
			if (UNEXPECTED(!setOffsetValueType(NULL, callArgType.raw(), false))) return false;
		}
		return true;
	}

	/* $value->op() of a Type-returning getter on a value that must be an
	 * object */
	static zv::Val typeOpOn(zval *value, pt_type_op_id op, const char *name)
	{
		if (UNEXPECTED(Z_TYPE_P(value) != IS_OBJECT)) {
			zend_throw_error(NULL, "Call to a member function %s() on %s", name, zend_zval_value_name(value));
			return zv::Val();
		}
		return pt_type_op(Z_OBJ_P(value), op, 0, NULL);
	}

	/* Mirrors getArrayFunctionAppendingType(). */
	static zv::Val getArrayFunctionAppendingType(zval *functionReflection, zval *scope, zval *expr, zval *argsResult)
	{
		zv::Val args = callArgs(expr);
		if (UNEXPECTED(args.isUndef())) return zv::Val();
		zval *arrayArg = argValueAt(args.raw(), 0);
		if (UNEXPECTED(arrayArg == NULL)) return zv::Val();
		zv::Val arrayArgHold = zv::Val::copyOf(zv::Ref(arrayArg));
		zv::Val arrayArgResult = requireArgResult(argsResult, arrayArgHold.raw());
		if (UNEXPECTED(arrayArgResult.isUndef())) return zv::Val();
		zv::Val arrayType = typeOnWalkScope(arrayArgResult.raw(), scope);
		if (UNEXPECTED(arrayType.isUndef())) return zv::Val();

		/* $callArgs = array_slice($expr->getArgs(), 1) */
		zv::Val allArgs = callArgs(expr);
		if (UNEXPECTED(allArgs.isUndef())) return zv::Val();
		zv::Arr callArgs = zv::Arr::create(countOf(allArgs.raw()));
		if (Z_TYPE_P(allArgs.raw()) == IS_ARRAY) {
			uint32_t position = 0;
			for (zv::ArrayEntry entry : zv::ArrRef(allArgs.raw())) {
				if (position++ == 0) continue;
				if (entry.hasStringKey()) {
					callArgs.separate();
					Z_TRY_ADDREF_P(entry.value().raw());
					zend_hash_update(callArgs.table(), entry.stringKey(), entry.value().raw());
				} else {
					callArgs.push(entry.value());
				}
			}
		}

		if (UNEXPECTED(Z_TYPE_P(arrayType.raw()) != IS_OBJECT)) {
			zend_throw_error(NULL, "Call to a member function getConstantArrays() on %s", zend_zval_value_name(arrayType.raw()));
			return zv::Val();
		}
		zv::Val constantArrays = pt_type_op(Z_OBJ_P(arrayType.raw()), PT_OP_GET_CONSTANT_ARRAYS, 0, NULL);
		if (UNEXPECTED(constantArrays.isUndef())) return zv::Val();
		if (countOf(constantArrays.raw()) > 0) {
			zv::Arr newArrayTypes = zv::Arr::create(countOf(constantArrays.raw()));
			zv::Val nameHold;
			zend_string *name = functionName(functionReflection, nameHold);
			if (UNEXPECTED(name == NULL)) return zv::Val();
			bool prepend = zend_string_equals_literal(name, "array_unshift");
			bool nonConstantArrayWasUnpacked = false;
			for (zv::ArrayEntry arrayEntry : zv::ArrRef(constantArrays.raw())) {
				zval *constantArrayIn = arrayEntry.value().deref().raw();
				zv::Val arrayTypeBuilder = prepend
					? pt_constant_array_type_builder_create_empty()
					: pt_constant_array_type_builder_create_from_constant_array(constantArrayIn);
				if (UNEXPECTED(arrayTypeBuilder.isUndef())) return zv::Val();

				auto setOnBuilder = [&](zval *offsetType, zval *valueType, bool optional) -> bool {
					return pt_constant_array_type_builder_set_offset_value_type(arrayTypeBuilder.raw(), offsetType, valueType, optional);
				};
				if (UNEXPECTED(!setOffsetValueTypes(scope, callArgs.raw(), argsResult, setOnBuilder, nonConstantArrayWasUnpacked))) return zv::Val();

				if (prepend) {
					zv::Val keyTypes = typeOpOn(constantArrayIn, PT_OP_GET_KEY_TYPES, "getKeyTypes");
					if (UNEXPECTED(keyTypes.isUndef())) return zv::Val();
					zv::Val valueTypes = pt_type_op(Z_OBJ_P(constantArrayIn), PT_OP_GET_VALUE_TYPES, 0, NULL);
					if (UNEXPECTED(valueTypes.isUndef())) return zv::Val();
					if (Z_TYPE_P(keyTypes.raw()) == IS_ARRAY) {
						for (zv::ArrayEntry keyEntry : zv::ArrRef(keyTypes.raw())) {
							zval *keyType = keyEntry.value().deref().raw();
							zval k;
							if (keyEntry.hasStringKey()) {
								ZVAL_STR(&k, keyEntry.stringKey());
							} else {
								ZVAL_LONG(&k, (zend_long) keyEntry.indexKey());
							}
							zv::Val constantStrings = typeCall(keyType, PT_LC("getconstantstrings"), "getConstantStrings", 0, NULL);
							if (UNEXPECTED(constantStrings.isUndef())) return zv::Val();
							zv::Val offsetType = zv::Val::null();
							if (countOf(constantStrings.raw()) == 1) {
								zv::Val again = typeCall(keyType, PT_LC("getconstantstrings"), "getConstantStrings", 0, NULL);
								if (UNEXPECTED(again.isUndef())) return zv::Val();
								zval *first = arrayDim(again.raw(), zeroKey());
								if (UNEXPECTED(first == NULL)) return zv::Val();
								offsetType = zv::Val::copyOf(zv::Ref(first));
							}
							zval *valueType = arrayDim(valueTypes.raw(), &k);
							if (UNEXPECTED(valueType == NULL)) return zv::Val();
							zv::Val valueTypeHold = zv::Val::copyOf(zv::Ref(valueType));
							zv::Val optional = pt_type_call(Z_OBJ_P(constantArrayIn), PT_LC("isoptionalkey"), 1, &k);
							if (UNEXPECTED(optional.isUndef())) return zv::Val();
							if (UNEXPECTED(!pt_constant_array_type_builder_set_offset_value_type(arrayTypeBuilder.raw(), offsetType.isNull() ? NULL : offsetType.raw(), valueTypeHold.raw(), zend_is_true(optional.raw())))) return zv::Val();
						}
					}

					zv::Val unsealedTypes = pt_type_call(Z_OBJ_P(constantArrayIn), PT_LC("getunsealedtypes"), 0, NULL);
					if (UNEXPECTED(unsealedTypes.isUndef())) return zv::Val();
					if (Z_TYPE_P(unsealedTypes.raw()) != IS_NULL) {
						zval zeroIndex, oneIndex;
						ZVAL_LONG(&zeroIndex, 0);
						ZVAL_LONG(&oneIndex, 1);
						zval *unsealedKey = arrayDim(unsealedTypes.raw(), &zeroIndex);
						if (UNEXPECTED(unsealedKey == NULL)) return zv::Val();
						zv::Val unsealedKeyHold = zv::Val::copyOf(zv::Ref(unsealedKey));
						zval *unsealedValue = arrayDim(unsealedTypes.raw(), &oneIndex);
						if (UNEXPECTED(unsealedValue == NULL)) return zv::Val();
						if (UNEXPECTED(!pt_constant_array_type_builder_make_unsealed(arrayTypeBuilder.raw(), unsealedKeyHold.raw(), unsealedValue))) return zv::Val();
					}
				}

				zv::Val constantArray = pt_constant_array_type_builder_get_array(arrayTypeBuilder.raw());
				if (UNEXPECTED(constantArray.isUndef())) return zv::Val();

				zend_long isConstantArray = pt_type_op_trinary(Z_OBJ_P(constantArray.raw()), PT_OP_IS_CONSTANT_ARRAY, 0, NULL);
				if (UNEXPECTED(isConstantArray < 0)) return zv::Val();
				if (isConstantArray == PT_TRI_YES && nonConstantArrayWasUnpacked) {
					zv::Val innerConstantArrays = pt_type_op(Z_OBJ_P(constantArray.raw()), PT_OP_GET_CONSTANT_ARRAYS, 0, NULL);
					if (UNEXPECTED(innerConstantArrays.isUndef())) return zv::Val();
					zend_long isList = pt_type_op_trinary(Z_OBJ_P(constantArray.raw()), PT_OP_IS_LIST, 0, NULL);
					if (UNEXPECTED(isList < 0)) return zv::Val();
					if (isList == PT_TRI_YES) {
						// A list can't preserve precise indices when an
						// unknown number of values is prepended/appended —
						// every index would be shifted by an unknown
						// amount. Degrade to a `non-empty-list<...>` of
						// the value union.
						zv::Val precision = generalizePrecisionLessSpecific();
						if (UNEXPECTED(precision.isUndef())) return zv::Val();
						zv::Val generalized = pt_type_call(Z_OBJ_P(constantArray.raw()), PT_LC("generalize"), 1, precision.raw());
						if (UNEXPECTED(generalized.isUndef())) return zv::Val();
						zv::Val keyType = typeOpOn(generalized.raw(), PT_OP_GET_ITERABLE_KEY_TYPE, "getIterableKeyType");
						if (UNEXPECTED(keyType.isUndef())) return zv::Val();
						zv::Val itemType = pt_type_op(Z_OBJ_P(constantArray.raw()), PT_OP_GET_ITERABLE_VALUE_TYPE, 0, NULL);
						if (UNEXPECTED(itemType.isUndef())) return zv::Val();
						zval array;
						if (UNEXPECTED(!pt_array_type_new(&array, keyType.raw(), itemType.raw()))) return zv::Val();
						zv::Val arrayHold = zv::Val::adopt(array);
						zend_long atLeastOnce = pt_type_op_trinary(Z_OBJ_P(constantArray.raw()), PT_OP_IS_ITERABLE_AT_LEAST_ONCE, 0, NULL);
						if (UNEXPECTED(atLeastOnce < 0)) return zv::Val();
						zv::Val degraded;
						if (atLeastOnce == PT_TRI_YES) {
							zval nonEmpty;
							if (UNEXPECTED(!pt_non_empty_array_type_new(&nonEmpty))) return zv::Val();
							zv::Arr members = zv::Arr::create(2);
							members.push(std::move(arrayHold));
							members.push(zv::Val::adopt(nonEmpty));
							zval intersection;
							if (UNEXPECTED(!pt_intersection_type_new(&intersection, members.raw()))) return zv::Val();
							degraded = zv::Val::adopt(intersection);
						} else {
							degraded = std::move(arrayHold);
						}
						zval list;
						if (UNEXPECTED(!pt_accessory_array_list_type_new(&list))) return zv::Val();
						zv::Val listHold = zv::Val::adopt(list);
						zv::Args intersectArgs{degraded.raw(), listHold.raw()};
						zv::Val intersected = pt_type_combinator_intersect(2, intersectArgs);
						if (UNEXPECTED(intersected.isUndef())) return zv::Val();
						constantArray = std::move(intersected);
					} else if (countOf(innerConstantArrays.raw()) == 1) {
						// Associative input — string keys keep their
						// precise values and the unknown count of
						// unpacked items lives in an unsealed `int` slot
						// of the result. Drops the auto-indexed
						// representatives that the unpacked-arg loop
						// inserted (they stand in for "0..N-1 of the
						// unpack value type" and are now subsumed by the
						// unsealed slot).
						zv::Val unsealed = unsealIntKeys(innerConstantArrays.raw());
						if (UNEXPECTED(unsealed.isUndef())) return zv::Val();
						constantArray = std::move(unsealed);
					}
				}

				newArrayTypes.push(std::move(constantArray));
			}

			/* TypeCombinator::union(...$newArrayTypes): a list built by push */
			HashTable *table = newArrayTypes.table();
			return pt_type_combinator_union(zend_hash_num_elements(table), table->arPacked);
		}

		bool ignored = false;
		auto setOnArrayType = [&](zval *offsetType, zval *valueType, bool optional) -> bool {
			zend_long atLeastOnce = typeOpTrinaryOn(arrayType.raw(), PT_OP_IS_ITERABLE_AT_LEAST_ONCE, "isIterableAtLeastOnce");
			if (UNEXPECTED(atLeastOnce < 0)) return false;
			bool isIterableAtLeastOnce = atLeastOnce == PT_TRI_YES || !optional;
			zval null;
			ZVAL_NULL(&null);
			zv::Args setArgs{offsetType != NULL ? offsetType : &null, valueType};
			zv::Val next = typeCall(arrayType.raw(), PT_LC("setoffsetvaluetype"), "setOffsetValueType", 2, setArgs);
			if (UNEXPECTED(next.isUndef())) return false;
			arrayType = std::move(next);
			if (isIterableAtLeastOnce) return true;

			zv::Val empty = emptyConstantArray();
			if (UNEXPECTED(empty.isUndef())) return false;
			zv::Args unionArgs{arrayType.raw(), empty.raw()};
			zv::Val unioned = pt_type_combinator_union(2, unionArgs);
			if (UNEXPECTED(unioned.isUndef())) return false;
			arrayType = std::move(unioned);
			return true;
		};
		if (UNEXPECTED(!setOffsetValueTypes(scope, callArgs.raw(), argsResult, setOnArrayType, ignored))) return zv::Val();

		return arrayType;
	}

	/* the TrinaryLogic op of a value that must be an object; -1 = pending
	 * exception */
	static zend_long typeOpTrinaryOn(zval *value, pt_type_op_id op, const char *name)
	{
		if (UNEXPECTED(Z_TYPE_P(value) != IS_OBJECT)) {
			zend_throw_error(NULL, "Call to a member function %s() on %s", name, zend_zval_value_name(value));
			return -1;
		}
		return pt_type_op_trinary(Z_OBJ_P(value), op, 0, NULL);
	}

	/* a zval holding the integer key 0 (static, never released) */
	static zval *zeroKey()
	{
		static zval zero;
		ZVAL_LONG(&zero, 0);
		return &zero;
	}

	/* the associative branch: $constantArrays[0]'s string keys into a fresh
	 * builder, the int-keyed values into an unsealed `int` slot */
	static zv::Val unsealIntKeys(zval *constantArrays)
	{
		zval *first = arrayDim(constantArrays, zeroKey());
		if (UNEXPECTED(first == NULL)) return zv::Val();
		zv::Val firstHold = zv::Val::copyOf(zv::Ref(first));
		zv::Val builder = pt_constant_array_type_builder_create_empty();
		if (UNEXPECTED(builder.isUndef())) return zv::Val();
		zv::Arr intValues = zv::Arr::empty();

		zv::Val keyTypes = typeOpOn(firstHold.raw(), PT_OP_GET_KEY_TYPES, "getKeyTypes");
		if (UNEXPECTED(keyTypes.isUndef())) return zv::Val();
		if (Z_TYPE_P(keyTypes.raw()) == IS_ARRAY) {
			for (zv::ArrayEntry keyEntry : zv::ArrRef(keyTypes.raw())) {
				zval *keyType = keyEntry.value().deref().raw();
				zval i;
				if (keyEntry.hasStringKey()) {
					ZVAL_STR(&i, keyEntry.stringKey());
				} else {
					ZVAL_LONG(&i, (zend_long) keyEntry.indexKey());
				}
				zv::Val valueTypes = pt_type_op(Z_OBJ_P(firstHold.raw()), PT_OP_GET_VALUE_TYPES, 0, NULL);
				if (UNEXPECTED(valueTypes.isUndef())) return zv::Val();
				zval *valueType = arrayDim(valueTypes.raw(), &i);
				if (UNEXPECTED(valueType == NULL)) return zv::Val();
				zv::Val valueTypeHold = zv::Val::copyOf(zv::Ref(valueType));
				zend_long isString = typeOpTrinaryOn(keyType, PT_OP_IS_STRING, "isString");
				if (UNEXPECTED(isString < 0)) return zv::Val();
				if (isString == PT_TRI_YES) {
					zv::Val optional = pt_type_call(Z_OBJ_P(firstHold.raw()), PT_LC("isoptionalkey"), 1, &i);
					if (UNEXPECTED(optional.isUndef())) return zv::Val();
					if (UNEXPECTED(!pt_constant_array_type_builder_set_offset_value_type(builder.raw(), keyType, valueTypeHold.raw(), zend_is_true(optional.raw())))) return zv::Val();
					continue;
				}
				intValues.push(std::move(valueTypeHold));
			}
		}

		zval integer;
		if (UNEXPECTED(!pt_integer_type_new(&integer))) return zv::Val();
		zv::Val unsealedKey = zv::Val::adopt(integer);
		zv::Val unsealedValue;
		uint32_t intValueCount = zend_hash_num_elements(intValues.table());
		if (intValueCount > 0) {
			unsealedValue = pt_type_combinator_union(intValueCount, intValues.table()->arPacked);
		} else {
			zval mixed;
			if (UNEXPECTED(!pt_mixed_type_new(&mixed))) return zv::Val();
			unsealedValue = zv::Val::adopt(mixed);
		}
		if (UNEXPECTED(unsealedValue.isUndef())) return zv::Val();

		zend_long isUnsealed = pt_type_op_trinary(Z_OBJ_P(firstHold.raw()), PT_OP_IS_UNSEALED, 0, NULL);
		if (UNEXPECTED(isUnsealed < 0)) return zv::Val();
		if (isUnsealed == PT_TRI_YES) {
			zv::Val existing = pt_type_call(Z_OBJ_P(firstHold.raw()), PT_LC("getunsealedtypes"), 0, NULL);
			if (UNEXPECTED(existing.isUndef())) return zv::Val();
			if (Z_TYPE_P(existing.raw()) != IS_NULL) {
				zval zeroIndex, oneIndex;
				ZVAL_LONG(&zeroIndex, 0);
				ZVAL_LONG(&oneIndex, 1);
				zval *existingKey = arrayDim(existing.raw(), &zeroIndex);
				if (UNEXPECTED(existingKey == NULL)) return zv::Val();
				zv::Args keyArgs{unsealedKey.raw(), existingKey};
				zv::Val key = pt_type_combinator_union(2, keyArgs);
				if (UNEXPECTED(key.isUndef())) return zv::Val();
				unsealedKey = std::move(key);
				zval *existingValue = arrayDim(existing.raw(), &oneIndex);
				if (UNEXPECTED(existingValue == NULL)) return zv::Val();
				zv::Args valueArgs{unsealedValue.raw(), existingValue};
				zv::Val value = pt_type_combinator_union(2, valueArgs);
				if (UNEXPECTED(value.isUndef())) return zv::Val();
				unsealedValue = std::move(value);
			}
		}
		if (UNEXPECTED(!pt_constant_array_type_builder_make_unsealed(builder.raw(), unsealedKey.raw(), unsealedValue.raw()))) return zv::Val();
		return pt_constant_array_type_builder_get_array(builder.raw());
	}
};

} // namespace phpstanturbo

using phpstanturbo::FuncCallScopeEffectsHelper;

zv::Val pt_func_call_scope_effects_helper_apply_array_walk_result(zval *helper, zval *nodeScopeResolver, zval *stmt, zval *arrayWalkArrayArg, zval *arrayWalkValueTypes, zval *argsResult, zval *scope, zval *storage, zval *nodeCallback)
{
	if (EXPECTED(Z_OBJCE_P(helper) == pt_ce_func_call_scope_effects_helper)) return FuncCallScopeEffectsHelper(Z_OBJ_P(helper)).applyArrayWalkResult(nodeScopeResolver, stmt, arrayWalkArrayArg, arrayWalkValueTypes, argsResult, scope, storage, nodeCallback);
	zv::Args argv{nodeScopeResolver, stmt, arrayWalkArrayArg, arrayWalkValueTypes, argsResult, scope, storage, nodeCallback};
	return pt_type_call(Z_OBJ_P(helper), PT_LC("applyarraywalkresult"), 8, argv);
}

zv::Val pt_func_call_scope_effects_helper_apply_call_scope_effects(zval *helper, zval *nodeScopeResolver, zval *stmt, zval *normalizedExpr, zval *functionReflection, zval *parametersAcceptor, zval *argsResult, zval *scope, zval *scopeBeforeArgs, zval *storage, zval *nodeCallback)
{
	if (functionReflection != NULL && Z_TYPE_P(functionReflection) == IS_NULL) functionReflection = NULL;
	if (parametersAcceptor != NULL && Z_TYPE_P(parametersAcceptor) == IS_NULL) parametersAcceptor = NULL;
	if (EXPECTED(Z_OBJCE_P(helper) == pt_ce_func_call_scope_effects_helper)) return FuncCallScopeEffectsHelper(Z_OBJ_P(helper)).applyCallScopeEffects(nodeScopeResolver, stmt, normalizedExpr, functionReflection, parametersAcceptor, argsResult, scope, scopeBeforeArgs, storage, nodeCallback);
	zval null;
	ZVAL_NULL(&null);
	zv::Args argv{nodeScopeResolver, stmt, normalizedExpr, functionReflection != NULL ? functionReflection : &null, parametersAcceptor != NULL ? parametersAcceptor : &null, argsResult, scope, scopeBeforeArgs, storage, nodeCallback};
	return pt_type_call(Z_OBJ_P(helper), PT_LC("applycallscopeeffects"), 10, argv);
}

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

void pt_register_func_call_scope_effects_helper()
{
	reg::Class cls("PHPStan\\Analyser\\ExprHandler\\Helper\\FuncCallScopeEffectsHelper");
	ptdecl::FuncCallScopeEffectsHelper::declareClass(cls);
	ptdecl::FuncCallScopeEffectsHelper::declareProperties(cls);

	/* the real parameter class names: the DI container autowires the
	 * service by reflecting the constructor */
	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *outputBufferHelper, *assignHandler;
		bool rememberPossiblyImpureFunctionValues;
		if (!zp::parse<zp::Obj, zp::Bool, zp::Obj>(execute_data, outputBufferHelper, rememberPossiblyImpureFunctionValues, assignHandler)) RETURN_THROWS();
		FuncCallScopeEffectsHelper(Z_OBJ_P(ZEND_THIS)).construct(outputBufferHelper, rememberPossiblyImpureFunctionValues, assignHandler);
	});

	cls.method(sigs::applyArrayWalkResult, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *nodeScopeResolver, *stmt, *arrayWalkArrayArg, *arrayWalkValueTypes, *argsResult, *scope, *storage, *nodeCallback;
		ZEND_PARSE_PARAMETERS_START(8, 8)
			Z_PARAM_OBJECT(nodeScopeResolver)
			Z_PARAM_OBJECT(stmt)
			Z_PARAM_OBJECT(arrayWalkArrayArg)
			Z_PARAM_ARRAY(arrayWalkValueTypes)
			Z_PARAM_OBJECT(argsResult)
			Z_PARAM_OBJECT(scope)
			Z_PARAM_OBJECT(storage)
			Z_PARAM_ZVAL(nodeCallback)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(FuncCallScopeEffectsHelper(Z_OBJ_P(ZEND_THIS)).applyArrayWalkResult(nodeScopeResolver, stmt, arrayWalkArrayArg, arrayWalkValueTypes, argsResult, scope, storage, nodeCallback));
	});

	cls.method(sigs::applyCallScopeEffects, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *nodeScopeResolver, *stmt, *normalizedExpr, *functionReflection, *parametersAcceptor, *argsResult, *scope, *scopeBeforeArgs, *storage, *nodeCallback;
		ZEND_PARSE_PARAMETERS_START(10, 10)
			Z_PARAM_OBJECT(nodeScopeResolver)
			Z_PARAM_OBJECT(stmt)
			Z_PARAM_OBJECT(normalizedExpr)
			Z_PARAM_OBJECT_OR_NULL(functionReflection)
			Z_PARAM_OBJECT_OR_NULL(parametersAcceptor)
			Z_PARAM_OBJECT(argsResult)
			Z_PARAM_OBJECT(scope)
			Z_PARAM_OBJECT(scopeBeforeArgs)
			Z_PARAM_OBJECT(storage)
			Z_PARAM_ZVAL(nodeCallback)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(FuncCallScopeEffectsHelper(Z_OBJ_P(ZEND_THIS)).applyCallScopeEffects(nodeScopeResolver, stmt, normalizedExpr, functionReflection, parametersAcceptor, argsResult, scope, scopeBeforeArgs, storage, nodeCallback));
	});

	cls.shadow(&pt_ce_func_call_scope_effects_helper);
}

/* }}} */
