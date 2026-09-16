/*
 * PHPStanTurbo\PropertyFetchHandler — native implementation of
 * PHPStan\Analyser\ExprHandler\PropertyFetchHandler.
 *
 * A DI service (#[AutowiredService]): the constructor keeps the twin's
 * arginfo so Nette autowires it. processExpr() is registered as the class's
 * handler entry and composeResult() — which AssignHandler calls across
 * handlers — is exported as pt_property_fetch_handler_compose_result()
 * (Engine.h conventions). The twin's closures are native closures capturing
 * what the PHP closures capture: the issetability descriptor's reflection
 * resolver ($this, $expr), the typeCallback ($this, $expr, $varResult,
 * $nameResult, $beforeScope) and the specifyTypesCallback ($this, $expr,
 * $beforeScope); the $shortCircuit / $resolveProperty closures the
 * typeCallback creates and calls itself are inlined, as is the private
 * propertyFetchType().
 *
 * NodeScopeResolver, MutatingScope, ExpressionResult, ExpressionContext,
 * VariableFlow(Builder), InternalThrowPoint, IssetabilityDescriptor,
 * DefaultNarrowingHelper, PropertyHookThrowPointsResolver, ClassReflection,
 * TypeCombinator and the Type kernel are called through their direct
 * entries; the property reflections and PhpVersion through the readers of
 * PropertyHookThrowPointsResolver.cpp (their slots in place, the getter
 * otherwise); PropertyReflectionFinder stays PHP (one cached method site).
 */

#include "support.h"
#include "generated/PropertyFetchHandler.h"

namespace slots = ptdecl::PropertyFetchHandler::slot;
namespace sigs = ptdecl::PropertyFetchHandler::sig;
#include "CallHandlerSupport.h"

zend_class_entry *pt_ce_property_fetch_handler = nullptr;

namespace {

using namespace ptcall;

constexpr const char *pt_pfh_closure_name = "PHPStan\\Analyser\\ExprHandler\\PropertyFetchHandler::{closure}";

/* {{{ the PHP collaborators (one site each; switch to their direct entries
 * once they are ported) */

pt_method_site pt_pfh_find_property_reflection_from_node_site;

/* $propertyReflectionFinder->findPropertyReflectionFromNode($propertyFetch, $scope) */
zv::Val findPropertyReflectionFromNode(zval *propertyReflectionFinder, zval *propertyFetch, zval *scope)
{
	zv::Args argv{propertyFetch, scope};
	return pt_call_method_cached(pt_pfh_find_property_reflection_from_node_site, Z_OBJ_P(propertyReflectionFinder), PT_LC("findpropertyreflectionfromnode"), 2, argv);
}

/* }}} */

/* {{{ the PhpParser nodes' properties */

pt_property_site pt_pfh_var_site;
pt_property_site pt_pfh_name_site;
pt_property_site pt_pfh_identifier_name_site;

zval *exprVar(zval *expr) { return nodeProperty(pt_pfh_var_site, expr, PT_LC("var")); }
zval *exprName(zval *expr) { return nodeProperty(pt_pfh_name_site, expr, PT_LC("name")); }
/* $identifier->toString() */
zval *identifierName(zval *identifier) { return nodeProperty(pt_pfh_identifier_name_site, identifier, PT_LC("name")); }

/* }}} */

/* the 'get' hook name, a permanent interned string (module startup) */
zend_string *pt_pfh_get = nullptr;

} // namespace

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\ExprHandler\PropertyFetchHandler; UNDEF = pending
 * exception. */
class PropertyFetchHandler
{
public:
	explicit PropertyFetchHandler(zend_object *self) : self(self) {}

	/* the constructor body: the promoted properties */
	void construct(zval *phpVersion, zval *propertyReflectionFinder, zval *expressionResultFactory, zval *propertyHookThrowPointsResolver, zval *defaultNarrowingHelper) const
	{
		pt_write_slot(self, slots::phpVersion, phpVersion);
		pt_write_slot(self, slots::propertyReflectionFinder, propertyReflectionFinder);
		pt_write_slot(self, slots::expressionResultFactory, expressionResultFactory);
		pt_write_slot(self, slots::propertyHookThrowPointsResolver, propertyHookThrowPointsResolver);
		pt_write_slot(self, slots::defaultNarrowingHelper, defaultNarrowingHelper);
	}

	/* Mirrors supports(); false = pending exception */
	[[nodiscard]] bool supports(zval *expr, bool &out) const
	{
		int is = isInstanceOf(expr, PT_CLASS_PROPERTY_FETCH);
		if (UNEXPECTED(is < 0)) return false;
		out = is == 1;
		return true;
	}

	/* Mirrors processExpr(). */
	zv::Val processExpr(zval *nodeScopeResolver, zval *stmt, zval *expr, zval *scope, zval *storage, zval *nodeCallback, zval *context) const
	{
		zval *beforeScope = scope;
		zval *scopeBeforeVar = scope;
		zval *var = exprVar(expr);
		if (UNEXPECTED(var == NULL)) return zv::Val();
		zv::Val varContext = pt_expression_context_enter_deep(context);
		if (UNEXPECTED(varContext.isUndef())) return zv::Val();
		zv::Val varResult = pt_node_scope_resolver_process_expr_node(nodeScopeResolver, stmt, var, scope, storage, nodeCallback, varContext.raw());
		if (UNEXPECTED(varResult.isUndef())) return zv::Val();
		zv::Val nameResult;
		zval *name = exprName(expr);
		if (UNEXPECTED(name == NULL)) return zv::Val();
		int nameIsIdentifier = isIdentifier(name);
		if (UNEXPECTED(nameIsIdentifier < 0)) return zv::Val();
		if (!nameIsIdentifier) {
			zv::Val hold;
			zval *varScope = pt_expression_result_scope(varResult.raw(), hold);
			if (UNEXPECTED(varScope == NULL)) return zv::Val();
			zv::Val nameContext = pt_expression_context_enter_deep(context);
			if (UNEXPECTED(nameContext.isUndef())) return zv::Val();
			nameResult = pt_node_scope_resolver_process_expr_node(nodeScopeResolver, stmt, name, varScope, storage, nodeCallback, nameContext.raw());
			if (UNEXPECTED(nameResult.isUndef())) return zv::Val();
		}

		return composeResult(nodeScopeResolver, expr, varResult.raw(), nameResult.isUndef() ? NULL : nameResult.raw(), scopeBeforeVar, beforeScope);
	}

	/* Mirrors composeResult(); $nameResult NULL (or IS_NULL) for null */
	zv::Val composeResult(zval *nodeScopeResolver, zval *expr, zval *varResult, zval *nameResult, zval *scopeBeforeVar, zval *beforeScope) const
	{
		if (nameResult != NULL && Z_TYPE_P(nameResult) == IS_NULL) nameResult = NULL;

		zv::Val hold;
		bool hasYield;
		if (UNEXPECTED(!pt_expression_result_has_yield(varResult, hasYield))) return zv::Val();
		zval *borrowed = pt_expression_result_throw_points(varResult, hold);
		if (UNEXPECTED(borrowed == NULL)) return zv::Val();
		zv::Val throwPoints = zv::Val::copyOf(zv::Ref(borrowed));
		borrowed = pt_expression_result_impure_points(varResult, hold);
		if (UNEXPECTED(borrowed == NULL)) return zv::Val();
		zv::Val impurePoints = zv::Val::copyOf(zv::Ref(borrowed));
		bool isAlwaysTerminating;
		if (UNEXPECTED(!pt_expression_result_is_always_terminating(varResult, isAlwaysTerminating))) return zv::Val();
		borrowed = pt_expression_result_scope(varResult, hold);
		if (UNEXPECTED(borrowed == NULL)) return zv::Val();
		zv::Val scope = zv::Val::copyOf(zv::Ref(borrowed));

		zval *name = exprName(expr);
		if (UNEXPECTED(name == NULL)) return zv::Val();
		int nameIsIdentifier = isIdentifier(name);
		if (UNEXPECTED(nameIsIdentifier < 0)) return zv::Val();
		bool mayShortCircuit = false;
		if (nameIsIdentifier) {
			bool supportsPropertyHooks;
			if (UNEXPECTED(!pt_php_version_supports_property_hooks(OBJ_PROP_NUM(self, slots::phpVersion), supportsPropertyHooks))) return zv::Val();
			if (supportsPropertyHooks && UNEXPECTED(!addGetHookPoints(nodeScopeResolver, expr, name, varResult, scopeBeforeVar, throwPoints, impurePoints))) return zv::Val();
		} else if (nameResult != NULL) {
			// a fetch that is a link in a nullsafe chain may never run - see
			// MethodCallHandler::processExpr(). Only the dynamic name is skipped
			// with it, so an Identifier name never has to resolve the receiver type.
			if (UNEXPECTED(!pt_expression_result_may_short_circuit(varResult, NULL, mayShortCircuit))) return zv::Val();
			if (!hasYield && UNEXPECTED(!pt_expression_result_has_yield(nameResult, hasYield))) return zv::Val();
			borrowed = pt_expression_result_throw_points(nameResult, hold);
			if (UNEXPECTED(borrowed == NULL)) return zv::Val();
			throwPoints = arrayMerge(throwPoints.raw(), borrowed);
			borrowed = pt_expression_result_impure_points(nameResult, hold);
			if (UNEXPECTED(borrowed == NULL)) return zv::Val();
			impurePoints = arrayMerge(impurePoints.raw(), borrowed);
			if (!isAlwaysTerminating && !mayShortCircuit && UNEXPECTED(!pt_expression_result_is_always_terminating(nameResult, isAlwaysTerminating))) return zv::Val();
			borrowed = pt_expression_result_scope(nameResult, hold);
			if (UNEXPECTED(borrowed == NULL)) return zv::Val();
			scope = zv::Val::copyOf(zv::Ref(borrowed));
			if (mayShortCircuit) {
				// the dynamic name expression was not evaluated in the
				// short-circuited world
				borrowed = pt_expression_result_scope(varResult, hold);
				if (UNEXPECTED(borrowed == NULL)) return zv::Val();
				scope = pt_mutating_scope_merge_with(Z_OBJ_P(scope.raw()), borrowed);
				if (UNEXPECTED(scope.isUndef())) return zv::Val();
			}
			bool supportsPropertyHooks;
			if (UNEXPECTED(!pt_php_version_supports_property_hooks(OBJ_PROP_NUM(self, slots::phpVersion), supportsPropertyHooks))) return zv::Val();
			if (supportsPropertyHooks) {
				zv::Val throwPoint = pt_internal_throw_point_create_implicit(scope.raw(), expr);
				if (UNEXPECTED(throwPoint.isUndef())) return zv::Val();
				appendTo(throwPoints, std::move(throwPoint));
			}
		}

		zv::Val variableFlow;
		{
			zv::Val varFlow = pt_expression_result_variable_flow(varResult);
			if (UNEXPECTED(varFlow.isUndef())) return zv::Val();
			zv::Val nameFlow = zv::Val::null();
			if (nameResult != NULL) {
				nameFlow = pt_expression_result_variable_flow(nameResult);
				if (UNEXPECTED(nameFlow.isUndef())) return zv::Val();
				if (mayShortCircuit) {
					zv::Args choiceArgv{nameFlow.raw(), zv::null};
					zv::Val choice = pt_variable_flow_choice(2, choiceArgv);
					if (UNEXPECTED(choice.isUndef())) return zv::Val();
					nameFlow = std::move(choice);
				}
			}
			zv::Val throwsFlow = pt_variable_flow_builder_throws(expr, Z_ARRVAL_P(throwPoints.raw()));
			if (UNEXPECTED(throwsFlow.isUndef())) return zv::Val();
			zv::Args flows{varFlow.raw(), nameFlow.raw(), throwsFlow.raw()};
			variableFlow = pt_variable_flow_sequence(3, flows);
			if (UNEXPECTED(variableFlow.isUndef())) return zv::Val();
		}
		bool containsNullsafe;
		if (UNEXPECTED(!pt_expression_result_contains_nullsafe(varResult, containsNullsafe))) return zv::Val();
		zv::Val reflectionResolver = pt_native_closure(&reflectionResolverBody, self, expr);
		zv::Val issetabilityDescriptor = pt_issetability_descriptor_property(varResult, reflectionResolver.raw(), expr);
		if (UNEXPECTED(issetabilityDescriptor.isUndef())) return zv::Val();
		zval null = {};
		ZVAL_NULL(&null);
		zv::Val typeCallback = pt_native_closure(&typeCallbackBody, self, expr, varResult, nameResult != NULL ? nameResult : &null, beforeScope);
		zv::Val specifyTypesCallback = pt_native_closure(&specifyTypesCallbackBody, self, expr, beforeScope);

		pt_expression_result_args args(scope.raw(), beforeScope, expr, hasYield, isAlwaysTerminating, throwPoints.raw(), impurePoints.raw(), typeCallback.raw(), specifyTypesCallback.raw());
		args.withVariableFlow(variableFlow.raw()).withContainsNullsafe(containsNullsafe).withIssetabilityDescriptor(issetabilityDescriptor.raw());
		return pt_expression_result_create(OBJ_PROP_NUM(self, slots::expressionResultFactory), args);
	}

	/* the handler entry (Engine.h) */
	static zv::Val processExprEntry(zend_object *handler, zval *nodeScopeResolver, zval *stmt, zval *expr, zval *scope, zval *storage, zval *nodeCallback, zval *context)
	{
		return PropertyFetchHandler(handler).processExpr(nodeScopeResolver, stmt, expr, scope, storage, nodeCallback, context);
	}

private:
	zend_object *self;

	/* the Identifier branch of composeResult() while property hooks are
	 * supported: the get hook's throw points merged into $throwPoints and
	 * its impure points into $impurePoints; false = pending exception */
	[[nodiscard]] bool addGetHookPoints(zval *nodeScopeResolver, zval *expr, zval *name, zval *varResult, zval *scopeBeforeVar, zv::Val &throwPoints, zv::Val &impurePoints) const
	{
		zval *propertyName = identifierName(name);
		if (UNEXPECTED(propertyName == NULL)) return false;
		if (UNEXPECTED(Z_TYPE_P(propertyName) != IS_STRING)) {
			zend_type_error("PhpParser\\Node\\Identifier::toString(): Return value must be of type string, %s returned", zend_zval_value_name(propertyName));
			return false;
		}
		zv::Val propertyHolderType = pt_expression_result_get_type(varResult);
		if (UNEXPECTED(propertyHolderType.isUndef())) return false;
		zv::Val propertyReflection = pt_mutating_scope_get_instance_property_reflection(Z_OBJ_P(scopeBeforeVar), propertyHolderType.raw(), Z_STR_P(propertyName));
		if (UNEXPECTED(propertyReflection.isUndef())) return false;
		if (propertyReflection.isNull()) return true;

		zv::Val propertyDeclaringClass = pt_property_reflection_get_declaring_class(propertyReflection.raw());
		if (UNEXPECTED(propertyDeclaringClass.isUndef())) return false;
		if (UNEXPECTED(!propertyDeclaringClass.ref().isObject())) {
			zend_throw_error(NULL, "Call to a member function hasNativeProperty() on %s", zend_zval_value_name(propertyDeclaringClass.raw()));
			return false;
		}
		bool hasNativeProperty;
		if (UNEXPECTED(!pt_class_reflection_has_native_property(Z_OBJ_P(propertyDeclaringClass.raw()), Z_STR_P(propertyName), hasNativeProperty))) return false;
		if (!hasNativeProperty) return true;
		zv::Val nativeProperty = pt_class_reflection_get_native_property(Z_OBJ_P(propertyDeclaringClass.raw()), Z_STR_P(propertyName));
		if (UNEXPECTED(nativeProperty.isUndef())) return false;
		zv::Val hookThrowPoints = pt_property_hook_throw_points_resolver_get_throw_points_from_property_hook(OBJ_PROP_NUM(self, slots::propertyHookThrowPointsResolver), scopeBeforeVar, expr, nativeProperty.raw(), pt_pfh_get);
		if (UNEXPECTED(hookThrowPoints.isUndef())) return false;
		if (UNEXPECTED(!hookThrowPoints.ref().isArray())) {
			zend_type_error("array_merge(): Argument #2 must be of type array, %s given", zend_zval_value_name(hookThrowPoints.raw()));
			return false;
		}
		throwPoints = arrayMerge(throwPoints.raw(), hookThrowPoints.raw());

		zval get;
		ZVAL_STR(&get, pt_pfh_get);
		zv::Args impureArgs{scopeBeforeVar, expr, nativeProperty.raw(), &get};
		zv::Val hookImpurePoints = pt_type_call(Z_OBJ_P(nodeScopeResolver), PT_LC("getimpurepointsfrompropertyhook"), 4, impureArgs);
		if (UNEXPECTED(hookImpurePoints.isUndef())) return false;
		if (UNEXPECTED(!hookImpurePoints.ref().isArray())) {
			zend_type_error("array_merge(): Argument #2 must be of type array, %s given", zend_zval_value_name(hookImpurePoints.raw()));
			return false;
		}
		impurePoints = arrayMerge(impurePoints.raw(), hookImpurePoints.raw());
		return true;
	}

	/* $resolveProperty($propertyName) of the typeCallback */
	zv::Val resolveProperty(bool nativeTypesPromoted, zval *reflectionScope, zval *receiverType, zval *expr, zend_string *propertyName) const
	{
		zv::Val propertyReflection = pt_mutating_scope_get_instance_property_reflection(Z_OBJ_P(reflectionScope), receiverType, propertyName);
		if (UNEXPECTED(propertyReflection.isUndef())) return zv::Val();
		if (propertyReflection.isNull()) return pt_type_new_error_type();
		if (nativeTypesPromoted) {
			bool hasNativeType;
			if (UNEXPECTED(!pt_property_reflection_has_native_type(propertyReflection.raw(), hasNativeType))) return zv::Val();
			if (!hasNativeType) return pt_type_new_mixed_type();

			return pt_property_reflection_get_native_type(propertyReflection.raw());
		}

		// propertyFetchType()
		bool inWriteExpressionAssign;
		if (UNEXPECTED(!pt_mutating_scope_is_in_write_expression_assign(Z_OBJ_P(reflectionScope), Z_OBJ_P(expr), inWriteExpressionAssign))) return zv::Val();
		if (inWriteExpressionAssign) return pt_property_reflection_get_writable_type(propertyReflection.raw());

		return pt_property_reflection_get_readable_type(propertyReflection.raw());
	}

	/* the typeCallback's body */
	zv::Val resolveType(bool nativeTypesPromoted, zval *expr, zval *varResult, zval *nameResult, zval *beforeScope) const
	{
		zv::Val receiverType = nativeTypesPromoted ? pt_expression_result_get_native_type(varResult) : pt_expression_result_get_type(varResult);
		if (UNEXPECTED(receiverType.isUndef())) return zv::Val();

		zv::Val promotedScope;
		zval *reflectionScope = beforeScope;
		if (nativeTypesPromoted) {
			promotedScope = pt_mutating_scope_do_not_treat_phpdoc_types_as_certain(Z_OBJ_P(beforeScope));
			if (UNEXPECTED(promotedScope.isUndef())) return zv::Val();
			reflectionScope = promotedScope.raw();
		}

		zval *name = exprName(expr);
		if (UNEXPECTED(name == NULL)) return zv::Val();
		int nameIsIdentifier = isIdentifier(name);
		if (UNEXPECTED(nameIsIdentifier < 0)) return zv::Val();
		if (nameIsIdentifier) {
			zval *propertyName = identifierName(name);
			if (UNEXPECTED(propertyName == NULL)) return zv::Val();
			if (UNEXPECTED(Z_TYPE_P(propertyName) != IS_STRING)) {
				zend_type_error("PhpParser\\Node\\Identifier::toString(): Return value must be of type string, %s returned", zend_zval_value_name(propertyName));
				return zv::Val();
			}
			zv::Val type = resolveProperty(nativeTypesPromoted, reflectionScope, receiverType.raw(), expr, Z_STR_P(propertyName));
			if (UNEXPECTED(type.isUndef())) return zv::Val();
			// $shortCircuit($type)
			bool containsNullsafe;
			if (UNEXPECTED(!pt_expression_result_contains_nullsafe(varResult, containsNullsafe))) return zv::Val();
			if (containsNullsafe) {
				bool containsNull;
				if (UNEXPECTED(!pt_type_combinator_contains_null(receiverType.raw(), containsNull))) return zv::Val();
				if (containsNull) return pt_type_combinator_add_null(type.raw());
			}
			return type;
		}

		// dynamic property fetch $obj->$name: resolve each possible name
		// from beforeScope. Every caller walks a non-Identifier name and
		// passes its result.
		if (Z_TYPE_P(nameResult) == IS_NULL) {
			pt_throw_should_not_happen();
			return zv::Val();
		}
		zv::Val nameType = nativeTypesPromoted ? pt_expression_result_get_native_type(nameResult) : pt_expression_result_get_type(nameResult);
		if (UNEXPECTED(nameType.isUndef())) return zv::Val();
		zv::Val constantStrings = getConstantStrings(nameType.raw());
		if (UNEXPECTED(constantStrings.isUndef())) return zv::Val();
		if (UNEXPECTED(Z_TYPE_P(constantStrings.raw()) != IS_ARRAY)) {
			zend_type_error("count(): Argument #1 ($value) must be of type Countable|array, %s given", zend_zval_value_name(constantStrings.raw()));
			return zv::Val();
		}
		if (zend_hash_num_elements(Z_ARRVAL_P(constantStrings.raw())) == 0) return pt_type_new_mixed_type();

		zv::Val iterated = getConstantStrings(nameType.raw());
		if (UNEXPECTED(iterated.isUndef())) return zv::Val();
		if (UNEXPECTED(Z_TYPE_P(iterated.raw()) != IS_ARRAY)) {
			zend_type_error("array_map(): Argument #2 ($array) must be of type array, %s given", zend_zval_value_name(iterated.raw()));
			return zv::Val();
		}
		zv::Arr types = zv::Arr::create(zend_hash_num_elements(Z_ARRVAL_P(iterated.raw())));
		for (auto entry : zv::TableRef(Z_ARRVAL_P(iterated.raw()))) {
			zval *constantString = entry.value().deref().raw();
			if (UNEXPECTED(Z_TYPE_P(constantString) != IS_OBJECT)) {
				zend_throw_error(NULL, "Call to a member function getValue() on %s", zend_zval_value_name(constantString));
				return zv::Val();
			}
			zv::Val value = pt_type_op(Z_OBJ_P(constantString), PT_OP_GET_VALUE, 0, NULL);
			if (UNEXPECTED(value.isUndef())) return zv::Val();
			if (Z_TYPE_P(value.raw()) == IS_STRING && ZSTR_LEN(Z_STR_P(value.raw())) == 0) {
				zv::Val error = pt_type_new_error_type();
				if (UNEXPECTED(error.isUndef())) return zv::Val();
				types.push(std::move(error));
				continue;
			}
			if (UNEXPECTED(Z_TYPE_P(value.raw()) != IS_STRING)) {
				zend_type_error("%s(): Argument #1 ($propertyName) must be of type string, %s given", pt_pfh_closure_name, zend_zval_value_name(value.raw()));
				return zv::Val();
			}
			zv::Val type = resolveProperty(nativeTypesPromoted, reflectionScope, receiverType.raw(), expr, Z_STR_P(value.raw()));
			if (UNEXPECTED(type.isUndef())) return zv::Val();
			types.push(std::move(type));
		}
		HashTable *typesTable = types.table();
		return HT_IS_PACKED(typesTable) && typesTable->nNumUsed == zend_hash_num_elements(typesTable)
			? pt_type_combinator_union(zend_hash_num_elements(typesTable), typesTable->arPacked)
			: pt_type_combinator_union(0, NULL);
	}

	/* fn (MutatingScope $s): ?FoundPropertyReflection =>
	 * $this->propertyReflectionFinder->findPropertyReflectionFromNode($expr, $s)
	 * — captures: $this, $expr */
	static void reflectionResolverBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		if (UNEXPECTED(!requireArguments(argc, 1, pt_pfh_closure_name))) return;
		zv::Val found = findPropertyReflectionFromNode(OBJ_PROP_NUM(Z_OBJ(captures[0]), slots::propertyReflectionFinder), &captures[1], &argv[0]);
		if (UNEXPECTED(found.isUndef())) return;
		found.intoReturnValue(return_value);
	}

	/* function (bool $nativeTypesPromoted) use ($expr, $varResult, $nameResult,
	 * $beforeScope): Type — captures: $this, $expr, $varResult, $nameResult,
	 * $beforeScope */
	static void typeCallbackBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		if (UNEXPECTED(!requireArguments(argc, 1, pt_pfh_closure_name))) return;
		zv::Val type = PropertyFetchHandler(Z_OBJ(captures[0])).resolveType(zend_is_true(&argv[0]), &captures[1], &captures[2], &captures[3], &captures[4]);
		if (UNEXPECTED(type.isUndef())) return;
		type.intoReturnValue(return_value);
	}

	/* fn (TypeSpecifierContext $context, bool $nativeTypesPromoted): SpecifiedTypes =>
	 * $this->defaultNarrowingHelper->specifyDefaultTypesWithNullsafeFan($expr,
	 * $context, $beforeScope, $nativeTypesPromoted) — captures: $this, $expr,
	 * $beforeScope */
	static void specifyTypesCallbackBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		if (UNEXPECTED(!requireArguments(argc, 2, pt_pfh_closure_name))) return;
		zv::Val specifiedTypes = pt_default_narrowing_helper_specify_default_types_with_nullsafe_fan(OBJ_PROP_NUM(Z_OBJ(captures[0]), slots::defaultNarrowingHelper), &captures[1], &argv[0], &captures[2], zend_is_true(&argv[1]));
		if (UNEXPECTED(specifiedTypes.isUndef())) return;
		specifiedTypes.intoReturnValue(return_value);
	}
};

} // namespace phpstanturbo

using phpstanturbo::PropertyFetchHandler;

zv::Val pt_property_fetch_handler_compose_result(zval *handler, zval *nodeScopeResolver, zval *expr, zval *varResult, zval *nameResult, zval *scopeBeforeVar, zval *beforeScope)
{
	if (EXPECTED(Z_OBJCE_P(handler) == pt_ce_property_fetch_handler)) return PropertyFetchHandler(Z_OBJ_P(handler)).composeResult(nodeScopeResolver, expr, varResult, nameResult, scopeBeforeVar, beforeScope);
	zval null;
	ZVAL_NULL(&null);
	zv::Args argv{nodeScopeResolver, expr, varResult, nameResult != NULL ? nameResult : &null, scopeBeforeVar, beforeScope};
	return pt_type_call(Z_OBJ_P(handler), PT_LC("composeresult"), 6, argv);
}

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

void pt_register_property_fetch_handler()
{
	pt_pfh_get = zend_string_init_interned(PT_LC("get"), 1);

	reg::Class cls("PHPStan\\Analyser\\ExprHandler\\PropertyFetchHandler");
	ptdecl::PropertyFetchHandler::declareClass(cls);
	ptdecl::PropertyFetchHandler::declareProperties(cls);

	/* the real parameter class names: the DI container autowires the
	 * service by reflecting the constructor */
	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *phpVersion, *propertyReflectionFinder, *expressionResultFactory, *propertyHookThrowPointsResolver, *defaultNarrowingHelper;
		ZEND_PARSE_PARAMETERS_START(5, 5)
			Z_PARAM_OBJECT(phpVersion)
			Z_PARAM_OBJECT(propertyReflectionFinder)
			Z_PARAM_OBJECT(expressionResultFactory)
			Z_PARAM_OBJECT(propertyHookThrowPointsResolver)
			Z_PARAM_OBJECT(defaultNarrowingHelper)
		ZEND_PARSE_PARAMETERS_END();
		PropertyFetchHandler(Z_OBJ_P(ZEND_THIS)).construct(phpVersion, propertyReflectionFinder, expressionResultFactory, propertyHookThrowPointsResolver, defaultNarrowingHelper);
	});

	cls.method<&PropertyFetchHandler::supports, zp::Obj>(sigs::supports);

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
		PT_RETURN_VAL(PropertyFetchHandler(Z_OBJ_P(ZEND_THIS)).processExpr(nodeScopeResolver, stmt, expr, scope, storage, nodeCallback, context));
	});

	cls.method(sigs::composeResult, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *nodeScopeResolver, *expr, *varResult, *nameResult, *scopeBeforeVar, *beforeScope;
		ZEND_PARSE_PARAMETERS_START(6, 6)
			Z_PARAM_OBJECT(nodeScopeResolver)
			Z_PARAM_OBJECT(expr)
			Z_PARAM_OBJECT(varResult)
			Z_PARAM_OBJECT_OR_NULL(nameResult)
			Z_PARAM_OBJECT(scopeBeforeVar)
			Z_PARAM_OBJECT(beforeScope)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(PropertyFetchHandler(Z_OBJ_P(ZEND_THIS)).composeResult(nodeScopeResolver, expr, varResult, nameResult, scopeBeforeVar, beforeScope));
	});

	cls.shadow(&pt_ce_property_fetch_handler);
	pt_expr_handler_entry_register(&pt_ce_property_fetch_handler, &PropertyFetchHandler::processExprEntry);
}

/* }}} */
