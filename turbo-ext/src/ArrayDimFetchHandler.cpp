/*
 * PHPStanTurbo\ArrayDimFetchHandler — native implementation of
 * PHPStan\Analyser\ExprHandler\ArrayDimFetchHandler.
 *
 * A DI service (#[AutowiredService]): the constructor keeps the twin's
 * arginfo so Nette autowires it. processExpr() is registered as the class's
 * handler entry and composeResult() — which AssignHandler calls across
 * handlers — is exported as pt_array_dim_fetch_handler_compose_result()
 * (Engine.h conventions). The twin's closures are native closures capturing
 * what the PHP closures capture: the `$arr[]` typeCallback (nothing), the
 * offset typeCallback ($this, $varResult, $dimResult, $offsetGetCall, $scope)
 * and the two specifyTypesCallbacks ($this, $expr; $this, $expr,
 * $beforeScope); the $shortCircuit closure the typeCallback creates and calls
 * itself is inlined, as is the private static offsetRead().
 *
 * NodeScopeResolver, MutatingScope, ExpressionResult, ExpressionContext,
 * VariableFlow(Builder), IssetabilityDescriptor, DefaultNarrowingHelper,
 * MethodThrowPointHelper, MethodCallReturnTypeHelper, the method reflections,
 * VariableWriteOffset, TypeCombinator and the Type kernel are called through
 * their direct entries; ParametersAcceptorSelector through its
 * CallHandlerSupport.h helper.
 */

#include "support.h"
#include "generated/ArrayDimFetchHandler.h"

namespace slots = ptdecl::ArrayDimFetchHandler::slot;
namespace sigs = ptdecl::ArrayDimFetchHandler::sig;
#include "CallHandlerSupport.h"

zend_class_entry *pt_ce_array_dim_fetch_handler = nullptr;

namespace {

using namespace ptcall;

constexpr const char *pt_adfh_closure_name = "PHPStan\\Analyser\\ExprHandler\\ArrayDimFetchHandler::{closure}";

/* {{{ the PhpParser nodes' properties */

pt_property_site pt_adfh_var_site;
pt_property_site pt_adfh_dim_site;
pt_property_site pt_adfh_variable_name_site;

zval *exprVar(zval *expr) { return nodeProperty(pt_adfh_var_site, expr, PT_LC("var")); }
zval *exprDim(zval *expr) { return nodeProperty(pt_adfh_dim_site, expr, PT_LC("dim")); }
zval *variableName(zval *variable) { return nodeProperty(pt_adfh_variable_name_site, variable, PT_LC("name")); }

/* }}} */

/* {{{ small value helpers */

/* the permanent interned literals (module startup) */
zend_string *pt_adfh_offset_get = nullptr;
zend_string *pt_adfh_array_access = nullptr;
zend_string *pt_adfh_synthetic_site_attribute = nullptr;

/* $type->$op() as a PT_TRI_* value (a TrinaryLogic or a result object); -1 =
 * pending exception */
zend_long typeOpTrinary(zval *type, pt_type_op_id op, uint32_t argc, zval *argv)
{
	zv::Val result = pt_type_op(Z_OBJ_P(type), op, argc, argv);
	if (UNEXPECTED(result.isUndef())) return -1;
	zval *value = result.raw();
	if (EXPECTED(Z_TYPE_P(value) == IS_OBJECT && Z_OBJCE_P(value) == pt_ce_trinary)) return pt_trinary_value(Z_OBJ_P(value));
	return pt_type_result_trinary(value);
}

/* (new ObjectType(ArrayAccess::class))->isSuperTypeOf($type) as a PT_TRI_*
 * value; -1 = pending exception */
zend_long arrayAccessIsSuperTypeOf(zval *type)
{
	zval className;
	ZVAL_STR(&className, pt_adfh_array_access);
	zv::Val arrayAccess = pt_type_new_object_type(&className);
	if (UNEXPECTED(arrayAccess.isUndef())) return -1;
	return typeOpTrinary(arrayAccess.raw(), PT_OP_IS_SUPER_TYPE_OF, 1, type);
}

/* $write->getId() of a VariableWrite: the slot of the PHP class, the method
 * otherwise */
zv::Val variableWriteId(zval *write)
{
	bool error = false;
	const pt_variable_write_slots *writeSlots = pt_variable_write_slots_of(Z_OBJ_P(write), error);
	if (writeSlots != NULL) return zv::Val::copyOf(zv::ObjRef(write).propAtOffset(writeSlots->id));
	if (UNEXPECTED(error)) return zv::Val();
	return pt_type_call(Z_OBJ_P(write), PT_LC("getid"), 0, NULL);
}

/* }}} */

} // namespace

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\ExprHandler\ArrayDimFetchHandler; UNDEF = pending
 * exception. */
class ArrayDimFetchHandler
{
public:
	explicit ArrayDimFetchHandler(zend_object *self) : self(self) {}

	/* the constructor body: the promoted properties */
	void construct(zval *expressionResultFactory, zval *defaultNarrowingHelper, zval *methodThrowPointHelper, zval *methodCallReturnTypeHelper) const
	{
		pt_write_slot(self, slots::expressionResultFactory, expressionResultFactory);
		pt_write_slot(self, slots::defaultNarrowingHelper, defaultNarrowingHelper);
		pt_write_slot(self, slots::methodThrowPointHelper, methodThrowPointHelper);
		pt_write_slot(self, slots::methodCallReturnTypeHelper, methodCallReturnTypeHelper);
	}

	/* Mirrors supports(); false = pending exception */
	[[nodiscard]] bool supports(zval *expr, bool &out) const
	{
		int is = isInstanceOf(expr, PT_CLASS_ARRAY_DIM_FETCH);
		if (UNEXPECTED(is < 0)) return false;
		out = is == 1;
		return true;
	}

	/* Mirrors processExpr(). */
	zv::Val processExpr(zval *nodeScopeResolver, zval *stmt, zval *expr, zval *scope, zval *storage, zval *nodeCallback, zval *context) const
	{
		zval *beforeScope = scope;
		zval *dim = exprDim(expr);
		if (UNEXPECTED(dim == NULL)) return zv::Val();
		if (Z_TYPE_P(dim) == IS_NULL) {
			zval *var = exprVar(expr);
			if (UNEXPECTED(var == NULL)) return zv::Val();
			zv::Val varContext = rootContext(context);
			if (UNEXPECTED(varContext.isUndef())) return zv::Val();
			zv::Val varResult = pt_node_scope_resolver_process_expr_node(nodeScopeResolver, stmt, var, scope, storage, nodeCallback, varContext.raw());
			if (UNEXPECTED(varResult.isUndef())) return zv::Val();

			return composeResult(nodeScopeResolver, stmt, expr, NULL, varResult.raw(), storage, context, beforeScope);
		}

		zv::Val dimContext = pt_expression_context_enter_deep_keeping_value_flow(context);
		if (UNEXPECTED(dimContext.isUndef())) return zv::Val();
		zv::Val dimResult = pt_node_scope_resolver_process_expr_node(nodeScopeResolver, stmt, dim, scope, storage, nodeCallback, dimContext.raw());
		if (UNEXPECTED(dimResult.isUndef())) return zv::Val();
		zval *var = exprVar(expr);
		if (UNEXPECTED(var == NULL)) return zv::Val();
		zv::Val hold;
		zval *dimScope = pt_expression_result_scope(dimResult.raw(), hold);
		if (UNEXPECTED(dimScope == NULL)) return zv::Val();
		zv::Val varContext = rootContext(context);
		if (UNEXPECTED(varContext.isUndef())) return zv::Val();
		zv::Val varResult = pt_node_scope_resolver_process_expr_node(nodeScopeResolver, stmt, var, dimScope, storage, nodeCallback, varContext.raw());
		if (UNEXPECTED(varResult.isUndef())) return zv::Val();

		return composeResult(nodeScopeResolver, stmt, expr, dimResult.raw(), varResult.raw(), storage, context, beforeScope);
	}

	/* Mirrors composeResult(); $dimResult NULL (or IS_NULL) for null */
	zv::Val composeResult(zval *nodeScopeResolver, zval *stmt, zval *expr, zval *dimResult, zval *varResult, zval *storage, zval *context, zval *beforeScope) const
	{
		(void) nodeScopeResolver;
		(void) stmt;
		(void) storage;
		if (dimResult != NULL && Z_TYPE_P(dimResult) == IS_NULL) dimResult = NULL;

		zv::Val hold;
		zval *borrowed = pt_expression_result_scope(varResult, hold);
		if (UNEXPECTED(borrowed == NULL)) return zv::Val();
		zv::Val scope = zv::Val::copyOf(zv::Ref(borrowed));
		zval *dim = exprDim(expr);
		if (UNEXPECTED(dim == NULL)) return zv::Val();
		if (Z_TYPE_P(dim) == IS_NULL || dimResult == NULL) {
			zv::Val variableFlow;
			{
				zv::Val varFlow = pt_expression_result_variable_flow(varResult);
				if (UNEXPECTED(varFlow.isUndef())) return zv::Val();
				zv::Val readFlow = offsetRead(expr, NULL, context);
				if (UNEXPECTED(readFlow.isUndef())) return zv::Val();
				zv::Args flows{varFlow.raw(), readFlow.raw()};
				variableFlow = pt_variable_flow_sequence(2, flows);
				if (UNEXPECTED(variableFlow.isUndef())) return zv::Val();
			}
			bool hasYield;
			if (UNEXPECTED(!pt_expression_result_has_yield(varResult, hasYield))) return zv::Val();
			bool isAlwaysTerminating;
			if (UNEXPECTED(!pt_expression_result_is_always_terminating(varResult, isAlwaysTerminating))) return zv::Val();
			zv::Val throwPointsHold, impurePointsHold;
			zval *throwPoints = pt_expression_result_throw_points(varResult, throwPointsHold);
			if (UNEXPECTED(throwPoints == NULL)) return zv::Val();
			zv::Val throwPointsValue = zv::Val::copyOf(zv::Ref(throwPoints));
			zval *impurePoints = pt_expression_result_impure_points(varResult, impurePointsHold);
			if (UNEXPECTED(impurePoints == NULL)) return zv::Val();
			zv::Val impurePointsValue = zv::Val::copyOf(zv::Ref(impurePoints));
			bool containsNullsafe;
			if (UNEXPECTED(!pt_expression_result_contains_nullsafe(varResult, containsNullsafe))) return zv::Val();
			zv::Val typeCallback = pt_native_closure(&appendTypeCallbackBody);
			zv::Val specifyTypesCallback = pt_native_closure(&appendSpecifyTypesCallbackBody, self, expr);

			pt_expression_result_args args(scope.raw(), beforeScope, expr, hasYield, isAlwaysTerminating, throwPointsValue.raw(), impurePointsValue.raw(), typeCallback.raw(), specifyTypesCallback.raw());
			args.withVariableFlow(variableFlow.raw()).withContainsNullsafe(containsNullsafe);
			return pt_expression_result_create(OBJ_PROP_NUM(self, slots::expressionResultFactory), args);
		}

		zv::Val throwPoints;
		{
			zv::Val dimHold;
			zval *dimPoints = pt_expression_result_throw_points(dimResult, dimHold);
			if (UNEXPECTED(dimPoints == NULL)) return zv::Val();
			zval *varPoints = pt_expression_result_throw_points(varResult, hold);
			if (UNEXPECTED(varPoints == NULL)) return zv::Val();
			throwPoints = arrayMerge(dimPoints, varPoints);
		}
		zv::Val impurePoints;
		{
			zv::Val dimHold;
			zval *dimPoints = pt_expression_result_impure_points(dimResult, dimHold);
			if (UNEXPECTED(dimPoints == NULL)) return zv::Val();
			zval *varPoints = pt_expression_result_impure_points(varResult, hold);
			if (UNEXPECTED(varPoints == NULL)) return zv::Val();
			impurePoints = arrayMerge(dimPoints, varPoints);
		}

		zv::Val varType = pt_expression_result_get_type(varResult);
		if (UNEXPECTED(varType.isUndef())) return zv::Val();
		// an offset read that is a link in a nullsafe chain may never run - see
		// MethodCallHandler::processExpr(). The dimension is walked BEFORE the
		// receiver here, so the short-circuited world is the pre-dimension scope.
		bool mayShortCircuit;
		if (UNEXPECTED(!pt_expression_result_may_short_circuit(varResult, varType.raw(), mayShortCircuit))) return zv::Val();
		if (mayShortCircuit) {
			scope = pt_mutating_scope_merge_with(Z_OBJ_P(scope.raw()), beforeScope);
			if (UNEXPECTED(scope.isUndef())) return zv::Val();
		}
		zv::Val offsetGetCall = zv::Val::null();
		zend_long varIsArray = typeOpTrinary(varType.raw(), PT_OP_IS_ARRAY, 0, NULL);
		if (UNEXPECTED(varIsArray < 0)) return zv::Val();
		if (varIsArray != PT_TRI_YES) {
			zend_long isArrayAccess = arrayAccessIsSuperTypeOf(varType.raw());
			if (UNEXPECTED(isArrayAccess < 0)) return zv::Val();
			if (isArrayAccess != PT_TRI_NO) {
				zv::Val typeExpr = pt_type_new(PT_CLASS_TYPE_EXPR, 1, varType.raw());
				if (UNEXPECTED(typeExpr.isUndef())) return zv::Val();
				zv::Args methodCallArgv{typeExpr.raw(), pt_adfh_offset_get};
				zv::Val methodCall = pt_type_new(PT_CLASS_METHOD_CALL, 2, methodCallArgv);
				if (UNEXPECTED(methodCall.isUndef())) return zv::Val();
				zv::Val offsetGetThrowPoints = pt_method_throw_point_helper_get_throw_points_for_call_on_type(OBJ_PROP_NUM(self, slots::methodThrowPointHelper), scope.raw(), context, varType.raw(), methodCall.raw());
				if (UNEXPECTED(offsetGetThrowPoints.isUndef())) return zv::Val();
				if (UNEXPECTED(!offsetGetThrowPoints.ref().isArray())) {
					zend_type_error("array_merge(): Argument #2 must be of type array, %s given", zend_zval_value_name(offsetGetThrowPoints.raw()));
					return zv::Val();
				}
				throwPoints = arrayMerge(throwPoints.raw(), offsetGetThrowPoints.raw());
				// the offsetGet return type resolves directly in the typeCallback
				// (per flavour); the fabricated node is only the payload dynamic
				// return type extensions receive - nothing walks it
				offsetGetCall = newOffsetGetCall(expr);
				if (UNEXPECTED(offsetGetCall.isUndef())) return zv::Val();
			}
		}

		zv::Val variableFlow;
		{
			zv::Val varFlow = pt_expression_result_variable_flow(varResult);
			if (UNEXPECTED(varFlow.isUndef())) return zv::Val();
			zv::Val dimFlow = pt_expression_result_variable_flow(dimResult);
			if (UNEXPECTED(dimFlow.isUndef())) return zv::Val();
			if (mayShortCircuit) {
				// the dimension was not evaluated in the short-circuited world
				zv::Args choiceArgv{dimFlow.raw(), zv::null};
				zv::Val choice = pt_variable_flow_choice(2, choiceArgv);
				if (UNEXPECTED(choice.isUndef())) return zv::Val();
				dimFlow = std::move(choice);
			}
			zv::Val readFlow = offsetRead(expr, dimResult, context);
			if (UNEXPECTED(readFlow.isUndef())) return zv::Val();
			zv::Val throwsFlow = pt_variable_flow_builder_throws(expr, Z_ARRVAL_P(throwPoints.raw()));
			if (UNEXPECTED(throwsFlow.isUndef())) return zv::Val();
			zv::Args flows{varFlow.raw(), dimFlow.raw(), readFlow.raw(), throwsFlow.raw()};
			variableFlow = pt_variable_flow_sequence(4, flows);
			if (UNEXPECTED(variableFlow.isUndef())) return zv::Val();
		}
		bool hasYield;
		if (UNEXPECTED(!pt_expression_result_has_yield(dimResult, hasYield))) return zv::Val();
		if (!hasYield && UNEXPECTED(!pt_expression_result_has_yield(varResult, hasYield))) return zv::Val();
		bool isAlwaysTerminating = false;
		if (!mayShortCircuit && UNEXPECTED(!pt_expression_result_is_always_terminating(dimResult, isAlwaysTerminating))) return zv::Val();
		if (!isAlwaysTerminating && UNEXPECTED(!pt_expression_result_is_always_terminating(varResult, isAlwaysTerminating))) return zv::Val();
		bool containsNullsafe;
		if (UNEXPECTED(!pt_expression_result_contains_nullsafe(varResult, containsNullsafe))) return zv::Val();
		zv::Val issetabilityDescriptor = pt_issetability_descriptor_offset(varResult, dimResult);
		if (UNEXPECTED(issetabilityDescriptor.isUndef())) return zv::Val();
		zv::Val typeCallback = pt_native_closure(&typeCallbackBody, self, varResult, dimResult, offsetGetCall.raw(), scope.raw());
		zv::Val specifyTypesCallback = pt_native_closure(&specifyTypesCallbackBody, self, expr, beforeScope);

		pt_expression_result_args args(scope.raw(), beforeScope, expr, hasYield, isAlwaysTerminating, throwPoints.raw(), impurePoints.raw(), typeCallback.raw(), specifyTypesCallback.raw());
		args.withVariableFlow(variableFlow.raw()).withContainsNullsafe(containsNullsafe).withIssetabilityDescriptor(issetabilityDescriptor.raw());
		return pt_expression_result_create(OBJ_PROP_NUM(self, slots::expressionResultFactory), args);
	}

	/* the handler entry (Engine.h) */
	static zv::Val processExprEntry(zend_object *handler, zval *nodeScopeResolver, zval *stmt, zval *expr, zval *scope, zval *storage, zval *nodeCallback, zval *context)
	{
		return ArrayDimFetchHandler(handler).processExpr(nodeScopeResolver, stmt, expr, scope, storage, nodeCallback, context);
	}

private:
	zend_object *self;

	/* $context->enterDeepKeepingValueFlow()->enterArrayDimFetchRoot() */
	static zv::Val rootContext(zval *context)
	{
		zv::Val deep = pt_expression_context_enter_deep_keeping_value_flow(context);
		if (UNEXPECTED(deep.isUndef())) return zv::Val();
		return pt_expression_context_enter_array_dim_fetch_root(deep.raw());
	}

	/* new MethodCall($expr->var, new Identifier('offsetGet'), [new
	 * Arg($expr->dim)], [TemplateArgumentFrame::SYNTHETIC_SITE_ATTRIBUTE => true]) */
	static zv::Val newOffsetGetCall(zval *expr)
	{
		zval *var = exprVar(expr);
		if (UNEXPECTED(var == NULL)) return zv::Val();
		zval name;
		ZVAL_STR(&name, pt_adfh_offset_get);
		zv::Val identifier = pt_name_node_new(PT_CLASS_IDENTIFIER, &name);
		if (UNEXPECTED(identifier.isUndef())) return zv::Val();
		zval *dim = exprDim(expr);
		if (UNEXPECTED(dim == NULL)) return zv::Val();
		zv::Val arg = pt_type_new(PT_CLASS_ARG, 1, dim);
		if (UNEXPECTED(arg.isUndef())) return zv::Val();
		zv::Arr args = zv::Arr::create(1);
		args.push(std::move(arg));
		zv::Arr attributes = zv::Arr::create(1);
		attributes.set(pt_adfh_synthetic_site_attribute, zv::Val::boolean(true));
		zv::Args argv{var, identifier.raw(), args.raw(), attributes.raw()};
		return pt_type_new(PT_CLASS_METHOD_CALL, 4, argv);
	}

	/* Mirrors the private static offsetRead(); PHP null where the twin
	 * returns null */
	static zv::Val offsetRead(zval *expr, zval *dimResult, zval *context)
	{
		bool unsetTarget;
		if (UNEXPECTED(!pt_expression_context_is_unset_target(context, unsetTarget))) return zv::Val();
		if (unsetTarget) return zv::Val::null();
		zval *var = exprVar(expr);
		if (UNEXPECTED(var == NULL)) return zv::Val();
		int isVariable = isInstanceOf(var, PT_CLASS_VARIABLE);
		if (UNEXPECTED(isVariable < 0)) return zv::Val();
		if (!isVariable) return zv::Val::null();
		zval *name = variableName(var);
		if (UNEXPECTED(name == NULL)) return zv::Val();
		if (Z_TYPE_P(name) != IS_STRING) return zv::Val::null();

		zv::Val target = pt_expression_context_get_value_flow_target(context);
		if (UNEXPECTED(target.isUndef())) return zv::Val();
		zv::Val targetId = zv::Val::null();
		if (!target.isNull()) {
			targetId = variableWriteId(target.raw());
			if (UNEXPECTED(targetId.isUndef())) return zv::Val();
		}
		zv::Val offset = zv::Val::null();
		if (dimResult != NULL) {
			zv::Val dimType = pt_expression_result_get_type(dimResult);
			if (UNEXPECTED(dimType.isUndef())) return zv::Val();
			offset = pt_variable_write_offset_from_type(dimType.raw());
			if (UNEXPECTED(offset.isUndef())) return zv::Val();
		}
		var = exprVar(expr);
		if (UNEXPECTED(var == NULL)) return zv::Val();
		name = variableName(var);
		if (UNEXPECTED(name == NULL)) return zv::Val();
		if (UNEXPECTED(Z_TYPE_P(name) != IS_STRING)) {
			zend_type_error("PHPStan\\Analyser\\VariableFlow::read(): Argument #1 ($name) must be of type string, %s given", zend_zval_value_name(name));
			return zv::Val();
		}
		return pt_variable_flow_read(Z_STR_P(name), targetId.isNull() ? NULL : targetId.raw(), false, offset.isNull() ? NULL : offset.raw());
	}

	/* the offset typeCallback's body */
	zv::Val resolveType(bool nativeTypesPromoted, zval *varResult, zval *dimResult, zval *offsetGetCall, zval *scope) const
	{
		zv::Val offsetAccessibleType = nativeTypesPromoted ? pt_expression_result_get_native_type(varResult) : pt_expression_result_get_type(varResult);
		if (UNEXPECTED(offsetAccessibleType.isUndef())) return zv::Val();

		zv::Val type;
		bool viaOffsetGet = false;
		if (Z_TYPE_P(offsetGetCall) != IS_NULL) {
			zend_long isArray = typeOpTrinary(offsetAccessibleType.raw(), PT_OP_IS_ARRAY, 0, NULL);
			if (UNEXPECTED(isArray < 0)) return zv::Val();
			if (isArray != PT_TRI_YES) {
				zend_long isArrayAccess = arrayAccessIsSuperTypeOf(offsetAccessibleType.raw());
				if (UNEXPECTED(isArrayAccess < 0)) return zv::Val();
				viaOffsetGet = isArrayAccess == PT_TRI_YES;
			}
		}
		if (viaOffsetGet) {
			if (nativeTypesPromoted) {
				zv::Val methodReflection = pt_mutating_scope_get_method_reflection(Z_OBJ_P(scope), offsetAccessibleType.raw(), pt_adfh_offset_get);
				if (UNEXPECTED(methodReflection.isUndef())) return zv::Val();
				if (methodReflection.isNull()) {
					type = pt_type_new_error_type();
				} else {
					zv::Val variants = pt_extended_method_reflection_call(methodReflection.raw(), PT_MR_GET_VARIANTS);
					if (UNEXPECTED(variants.isUndef())) return zv::Val();
					zv::Val acceptor = combineAcceptors(variants.raw());
					if (UNEXPECTED(acceptor.isUndef())) return zv::Val();
					if (UNEXPECTED(!acceptor.ref().isObject())) {
						zend_throw_error(NULL, "Call to a member function getNativeReturnType() on %s", zend_zval_value_name(acceptor.raw()));
						return zv::Val();
					}
					type = acceptorNativeReturnType(acceptor.raw());
				}
			} else {
				zval methodName;
				ZVAL_STR(&methodName, pt_adfh_offset_get);
				type = pt_method_call_return_type_helper_method_call_return_type(OBJ_PROP_NUM(self, slots::methodCallReturnTypeHelper), scope, offsetAccessibleType.raw(), &methodName, offsetGetCall, NULL, NULL);
				if (UNEXPECTED(type.isUndef())) return zv::Val();
				if (type.isNull()) type = pt_type_new_error_type();
			}
		} else {
			zv::Val dimType = nativeTypesPromoted ? pt_expression_result_get_native_type(dimResult) : pt_expression_result_get_type(dimResult);
			if (UNEXPECTED(dimType.isUndef())) return zv::Val();
			type = pt_type_op(Z_OBJ_P(offsetAccessibleType.raw()), PT_OP_GET_OFFSET_VALUE_TYPE, 1, dimType.raw());
		}
		if (UNEXPECTED(type.isUndef())) return zv::Val();

		// $shortCircuit($type)
		bool containsNullsafe;
		if (UNEXPECTED(!pt_expression_result_contains_nullsafe(varResult, containsNullsafe))) return zv::Val();
		if (containsNullsafe) {
			bool containsNull;
			if (UNEXPECTED(!pt_type_combinator_contains_null(offsetAccessibleType.raw(), containsNull))) return zv::Val();
			if (containsNull) return pt_type_combinator_add_null(type.raw());
		}
		return type;
	}

	/* static fn (): Type => new NeverType() */
	static void appendTypeCallbackBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		(void) captures;
		(void) argc;
		(void) argv;
		zval never;
		if (UNEXPECTED(!pt_never_type_new(&never))) return;
		ZVAL_COPY_VALUE(return_value, &never);
	}

	/* fn (TypeSpecifierContext $context, bool $nativeTypesPromoted): SpecifiedTypes =>
	 * $this->defaultNarrowingHelper->specifyDefaultTypes($expr, $context) —
	 * captures: $this, $expr */
	static void appendSpecifyTypesCallbackBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		if (UNEXPECTED(!requireArguments(argc, 2, pt_adfh_closure_name))) return;
		zv::Val specifiedTypes = pt_default_narrowing_helper_specify_default_types(OBJ_PROP_NUM(Z_OBJ(captures[0]), slots::defaultNarrowingHelper), &captures[1], &argv[0]);
		if (UNEXPECTED(specifiedTypes.isUndef())) return;
		specifiedTypes.intoReturnValue(return_value);
	}

	/* function (bool $nativeTypesPromoted) use ($varResult, $dimResult,
	 * $offsetGetCall, $scope): Type — captures: $this, $varResult, $dimResult,
	 * $offsetGetCall, $scope */
	static void typeCallbackBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		if (UNEXPECTED(!requireArguments(argc, 1, pt_adfh_closure_name))) return;
		zv::Val type = ArrayDimFetchHandler(Z_OBJ(captures[0])).resolveType(zend_is_true(&argv[0]), &captures[1], &captures[2], &captures[3], &captures[4]);
		if (UNEXPECTED(type.isUndef())) return;
		type.intoReturnValue(return_value);
	}

	/* fn (TypeSpecifierContext $context, bool $nativeTypesPromoted): SpecifiedTypes =>
	 * $this->defaultNarrowingHelper->specifyDefaultTypesWithNullsafeFan($expr,
	 * $context, $beforeScope, $nativeTypesPromoted) — captures: $this, $expr,
	 * $beforeScope */
	static void specifyTypesCallbackBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		if (UNEXPECTED(!requireArguments(argc, 2, pt_adfh_closure_name))) return;
		zv::Val specifiedTypes = pt_default_narrowing_helper_specify_default_types_with_nullsafe_fan(OBJ_PROP_NUM(Z_OBJ(captures[0]), slots::defaultNarrowingHelper), &captures[1], &argv[0], &captures[2], zend_is_true(&argv[1]));
		if (UNEXPECTED(specifiedTypes.isUndef())) return;
		specifiedTypes.intoReturnValue(return_value);
	}
};

} // namespace phpstanturbo

using phpstanturbo::ArrayDimFetchHandler;

zv::Val pt_array_dim_fetch_handler_compose_result(zval *handler, zval *nodeScopeResolver, zval *stmt, zval *expr, zval *dimResult, zval *varResult, zval *storage, zval *context, zval *beforeScope)
{
	if (EXPECTED(Z_OBJCE_P(handler) == pt_ce_array_dim_fetch_handler)) return ArrayDimFetchHandler(Z_OBJ_P(handler)).composeResult(nodeScopeResolver, stmt, expr, dimResult, varResult, storage, context, beforeScope);
	zval null;
	ZVAL_NULL(&null);
	zv::Args argv{nodeScopeResolver, stmt, expr, dimResult != NULL ? dimResult : &null, varResult, storage, context, beforeScope};
	return pt_type_call(Z_OBJ_P(handler), PT_LC("composeresult"), 8, argv);
}

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

void pt_register_array_dim_fetch_handler()
{
	pt_adfh_offset_get = zend_string_init_interned(PT_LC("offsetGet"), 1);
	pt_adfh_array_access = zend_string_init_interned(PT_LC("ArrayAccess"), 1);
	pt_adfh_synthetic_site_attribute = zend_string_init_interned(PT_LC("templateArgumentSyntheticSite"), 1);

	reg::Class cls("PHPStan\\Analyser\\ExprHandler\\ArrayDimFetchHandler");
	ptdecl::ArrayDimFetchHandler::declareClass(cls);
	ptdecl::ArrayDimFetchHandler::declareProperties(cls);

	/* the real parameter class names: the DI container autowires the
	 * service by reflecting the constructor */
	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *expressionResultFactory, *defaultNarrowingHelper, *methodThrowPointHelper, *methodCallReturnTypeHelper;
		if (!zp::parse<zp::Obj, zp::Obj, zp::Obj, zp::Obj>(execute_data, expressionResultFactory, defaultNarrowingHelper, methodThrowPointHelper, methodCallReturnTypeHelper)) RETURN_THROWS();
		ArrayDimFetchHandler(Z_OBJ_P(ZEND_THIS)).construct(expressionResultFactory, defaultNarrowingHelper, methodThrowPointHelper, methodCallReturnTypeHelper);
	});

	cls.method<&ArrayDimFetchHandler::supports, zp::Obj>(sigs::supports);

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
		PT_RETURN_VAL(ArrayDimFetchHandler(Z_OBJ_P(ZEND_THIS)).processExpr(nodeScopeResolver, stmt, expr, scope, storage, nodeCallback, context));
	});

	cls.method(sigs::composeResult, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *nodeScopeResolver, *stmt, *expr, *dimResult, *varResult, *storage, *context, *beforeScope;
		ZEND_PARSE_PARAMETERS_START(8, 8)
			Z_PARAM_OBJECT(nodeScopeResolver)
			Z_PARAM_OBJECT(stmt)
			Z_PARAM_OBJECT(expr)
			Z_PARAM_OBJECT_OR_NULL(dimResult)
			Z_PARAM_OBJECT(varResult)
			Z_PARAM_OBJECT(storage)
			Z_PARAM_OBJECT(context)
			Z_PARAM_OBJECT(beforeScope)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(ArrayDimFetchHandler(Z_OBJ_P(ZEND_THIS)).composeResult(nodeScopeResolver, stmt, expr, dimResult, varResult, storage, context, beforeScope));
	});

	cls.shadow(&pt_ce_array_dim_fetch_handler);
	pt_expr_handler_entry_register(&pt_ce_array_dim_fetch_handler, &ArrayDimFetchHandler::processExprEntry);
}

/* }}} */
