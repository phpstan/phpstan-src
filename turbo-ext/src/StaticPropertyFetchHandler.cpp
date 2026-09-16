/*
 * PHPStanTurbo\StaticPropertyFetchHandler — native implementation of
 * PHPStan\Analyser\ExprHandler\StaticPropertyFetchHandler.
 *
 * A DI service (#[AutowiredService]): the constructor keeps the twin's
 * arginfo so Nette autowires it. processExpr() is registered as the class's
 * handler entry and composeResult() — which AssignHandler calls across
 * handlers — is exported as pt_static_property_fetch_handler_compose_result()
 * (Engine.h conventions). The twin's closures are native closures capturing
 * what the PHP closures capture: the issetability descriptor's reflection
 * resolver ($this, $expr), the typeCallback ($this, $expr, $classResult,
 * $nameResult, $beforeScope) and the specifyTypesCallback ($this, $expr); the
 * $shortCircuit / $resolveProperty closures the typeCallback creates and
 * calls itself are inlined, as is the private propertyFetchType().
 *
 * NodeScopeResolver, MutatingScope, ExpressionResult, ExpressionContext,
 * VariableFlow(Builder), ImpurePoint, IssetabilityDescriptor,
 * DefaultNarrowingHelper, TypeCombinator and the Type kernel are called
 * through their direct entries; the property reflections through the readers
 * of PropertyHookThrowPointsResolver.cpp; PropertyReflectionFinder stays PHP
 * (one cached method site).
 */

#include "support.h"
#include "generated/StaticPropertyFetchHandler.h"

namespace slots = ptdecl::StaticPropertyFetchHandler::slot;
namespace sigs = ptdecl::StaticPropertyFetchHandler::sig;
#include "CallHandlerSupport.h"

zend_class_entry *pt_ce_static_property_fetch_handler = nullptr;

namespace {

using namespace ptcall;

constexpr const char *pt_spfh_closure_name = "PHPStan\\Analyser\\ExprHandler\\StaticPropertyFetchHandler::{closure}";

/* {{{ the PHP collaborators (one site each; switch to their direct entries
 * once they are ported) */

pt_method_site pt_spfh_find_property_reflection_from_node_site;

/* $propertyReflectionFinder->findPropertyReflectionFromNode($propertyFetch, $scope) */
zv::Val findPropertyReflectionFromNode(zval *propertyReflectionFinder, zval *propertyFetch, zval *scope)
{
	zv::Args argv{propertyFetch, scope};
	return pt_call_method_cached(pt_spfh_find_property_reflection_from_node_site, Z_OBJ_P(propertyReflectionFinder), PT_LC("findpropertyreflectionfromnode"), 2, argv);
}

/* }}} */

/* {{{ the PhpParser nodes' properties */

pt_property_site pt_spfh_class_site;
pt_property_site pt_spfh_name_site;
pt_property_site pt_spfh_identifier_name_site;

zval *exprClass(zval *expr) { return nodeProperty(pt_spfh_class_site, expr, PT_LC("class")); }
zval *exprName(zval *expr) { return nodeProperty(pt_spfh_name_site, expr, PT_LC("name")); }
/* $identifier->toString() */
zval *identifierName(zval *identifier) { return nodeProperty(pt_spfh_identifier_name_site, identifier, PT_LC("name")); }

/* }}} */

/* the static property access impure point's literals, permanent interned
 * strings (module startup) */
zend_string *pt_spfh_static_property_access = nullptr;
zend_string *pt_spfh_static_property_access_description = nullptr;

} // namespace

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\ExprHandler\StaticPropertyFetchHandler; UNDEF =
 * pending exception. */
class StaticPropertyFetchHandler
{
public:
	explicit StaticPropertyFetchHandler(zend_object *self) : self(self) {}

	/* the constructor body: the promoted properties */
	void construct(zval *propertyReflectionFinder, zval *expressionResultFactory, zval *defaultNarrowingHelper) const
	{
		pt_write_slot(self, slots::propertyReflectionFinder, propertyReflectionFinder);
		pt_write_slot(self, slots::expressionResultFactory, expressionResultFactory);
		pt_write_slot(self, slots::defaultNarrowingHelper, defaultNarrowingHelper);
	}

	/* Mirrors supports(); false = pending exception */
	[[nodiscard]] bool supports(zval *expr, bool &out) const
	{
		int is = isInstanceOf(expr, PT_CLASS_STATIC_PROPERTY_FETCH);
		if (UNEXPECTED(is < 0)) return false;
		out = is == 1;
		return true;
	}

	/* Mirrors processExpr(). */
	zv::Val processExpr(zval *nodeScopeResolver, zval *stmt, zval *expr, zval *scope, zval *storage, zval *nodeCallback, zval *context) const
	{
		zval *beforeScope = scope;
		zv::Val currentScope = zv::Val::copyOf(zv::Ref(scope));
		zv::Val classResult;
		zval *class_ = exprClass(expr);
		if (UNEXPECTED(class_ == NULL)) return zv::Val();
		int classIsExpr = isInstanceOf(class_, PT_CLASS_EXPR);
		if (UNEXPECTED(classIsExpr < 0)) return zv::Val();
		if (classIsExpr) {
			zv::Val classContext = pt_expression_context_enter_deep(context);
			if (UNEXPECTED(classContext.isUndef())) return zv::Val();
			classResult = pt_node_scope_resolver_process_expr_node(nodeScopeResolver, stmt, class_, scope, storage, nodeCallback, classContext.raw());
			if (UNEXPECTED(classResult.isUndef())) return zv::Val();
			zv::Val hold;
			zval *classScope = pt_expression_result_scope(classResult.raw(), hold);
			if (UNEXPECTED(classScope == NULL)) return zv::Val();
			currentScope = zv::Val::copyOf(zv::Ref(classScope));
		}
		zv::Val nameResult;
		zval *name = exprName(expr);
		if (UNEXPECTED(name == NULL)) return zv::Val();
		int nameIsVarLikeIdentifier = isInstanceOf(name, PT_CLASS_VAR_LIKE_IDENTIFIER);
		if (UNEXPECTED(nameIsVarLikeIdentifier < 0)) return zv::Val();
		if (!nameIsVarLikeIdentifier) {
			zv::Val nameContext = pt_expression_context_enter_deep(context);
			if (UNEXPECTED(nameContext.isUndef())) return zv::Val();
			nameResult = pt_node_scope_resolver_process_expr_node(nodeScopeResolver, stmt, name, currentScope.raw(), storage, nodeCallback, nameContext.raw());
			if (UNEXPECTED(nameResult.isUndef())) return zv::Val();
		}

		return composeResult(expr, classResult.isUndef() ? NULL : classResult.raw(), nameResult.isUndef() ? NULL : nameResult.raw(), beforeScope);
	}

	/* Mirrors composeResult(); $classResult / $nameResult NULL (or IS_NULL)
	 * for null */
	zv::Val composeResult(zval *expr, zval *classResult, zval *nameResult, zval *beforeScope) const
	{
		if (classResult != NULL && Z_TYPE_P(classResult) == IS_NULL) classResult = NULL;
		if (nameResult != NULL && Z_TYPE_P(nameResult) == IS_NULL) nameResult = NULL;

		zv::Val scope = zv::Val::copyOf(zv::Ref(beforeScope));
		bool hasYield = false;
		zv::Val throwPoints = zv::Val(zv::Arr::empty());
		zv::Val impurePoints;
		{
			zv::Val impurePoint = pt_impure_point_new(scope.raw(), expr, pt_spfh_static_property_access, pt_spfh_static_property_access_description, true);
			if (UNEXPECTED(impurePoint.isUndef())) return zv::Val();
			zv::Arr points = zv::Arr::create(1);
			points.push(std::move(impurePoint));
			impurePoints = zv::Val(std::move(points));
		}
		bool isAlwaysTerminating = false;
		zv::Val hold;
		if (classResult != NULL) {
			if (UNEXPECTED(!pt_expression_result_has_yield(classResult, hasYield))) return zv::Val();
			zval *borrowed = pt_expression_result_throw_points(classResult, hold);
			if (UNEXPECTED(borrowed == NULL)) return zv::Val();
			throwPoints = zv::Val::copyOf(zv::Ref(borrowed));
			borrowed = pt_expression_result_impure_points(classResult, hold);
			if (UNEXPECTED(borrowed == NULL)) return zv::Val();
			impurePoints = zv::Val::copyOf(zv::Ref(borrowed));
			if (UNEXPECTED(!pt_expression_result_is_always_terminating(classResult, isAlwaysTerminating))) return zv::Val();
			borrowed = pt_expression_result_scope(classResult, hold);
			if (UNEXPECTED(borrowed == NULL)) return zv::Val();
			scope = zv::Val::copyOf(zv::Ref(borrowed));
		}
		bool mayShortCircuit = false;
		if (nameResult != NULL) {
			// `$a?->b::$$name` is a link in a nullsafe chain and may never run - see
			// MethodCallHandler::processExpr(). Only the dynamic name is skipped with
			// it, so a plain `::$name` never has to resolve the class expression type.
			if (classResult != NULL && UNEXPECTED(!pt_expression_result_may_short_circuit(classResult, NULL, mayShortCircuit))) return zv::Val();
			if (!hasYield && UNEXPECTED(!pt_expression_result_has_yield(nameResult, hasYield))) return zv::Val();
			zval *borrowed = pt_expression_result_throw_points(nameResult, hold);
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
				borrowed = pt_expression_result_scope(classResult, hold);
				if (UNEXPECTED(borrowed == NULL)) return zv::Val();
				scope = pt_mutating_scope_merge_with(Z_OBJ_P(scope.raw()), borrowed);
				if (UNEXPECTED(scope.isUndef())) return zv::Val();
			}
		}

		zv::Val variableFlow;
		{
			zv::Val classFlow = zv::Val::null();
			if (classResult != NULL) {
				classFlow = pt_expression_result_variable_flow(classResult);
				if (UNEXPECTED(classFlow.isUndef())) return zv::Val();
			}
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
			zv::Args flows{classFlow.raw(), nameFlow.raw(), throwsFlow.raw()};
			variableFlow = pt_variable_flow_sequence(3, flows);
			if (UNEXPECTED(variableFlow.isUndef())) return zv::Val();
		}
		bool containsNullsafe = false;
		if (classResult != NULL && UNEXPECTED(!pt_expression_result_contains_nullsafe(classResult, containsNullsafe))) return zv::Val();
		zv::Val reflectionResolver = pt_native_closure(&reflectionResolverBody, self, expr);
		zv::Val issetabilityDescriptor = pt_issetability_descriptor_property(classResult, reflectionResolver.raw(), expr);
		if (UNEXPECTED(issetabilityDescriptor.isUndef())) return zv::Val();
		zval null;
		ZVAL_NULL(&null);
		zv::Val typeCallback = pt_native_closure(&typeCallbackBody, self, expr, classResult != NULL ? classResult : &null, nameResult != NULL ? nameResult : &null, beforeScope);
		zv::Val specifyTypesCallback = pt_native_closure(&specifyTypesCallbackBody, self, expr);

		pt_expression_result_args args(scope.raw(), beforeScope, expr, hasYield, isAlwaysTerminating, throwPoints.raw(), impurePoints.raw(), typeCallback.raw(), specifyTypesCallback.raw());
		args.withVariableFlow(variableFlow.raw()).withContainsNullsafe(containsNullsafe).withIssetabilityDescriptor(issetabilityDescriptor.raw());
		return pt_expression_result_create(OBJ_PROP_NUM(self, slots::expressionResultFactory), args);
	}

	/* the handler entry (Engine.h) */
	static zv::Val processExprEntry(zend_object *handler, zval *nodeScopeResolver, zval *stmt, zval *expr, zval *scope, zval *storage, zval *nodeCallback, zval *context)
	{
		return StaticPropertyFetchHandler(handler).processExpr(nodeScopeResolver, stmt, expr, scope, storage, nodeCallback, context);
	}

private:
	zend_object *self;

	/* $resolveProperty($propertyName) of the typeCallback */
	static zv::Val resolveProperty(bool nativeTypesPromoted, zval *reflectionScope, zval *fetchedOnType, zval *expr, zend_string *propertyName)
	{
		zv::Val propertyReflection = pt_mutating_scope_get_static_property_reflection(Z_OBJ_P(reflectionScope), fetchedOnType, propertyName);
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
	static zv::Val resolveType(bool nativeTypesPromoted, zval *expr, zval *classResult, zval *nameResult, zval *beforeScope)
	{
		zv::Val classType;
		if (Z_TYPE_P(classResult) != IS_NULL) {
			classType = nativeTypesPromoted ? pt_expression_result_get_native_type(classResult) : pt_expression_result_get_type(classResult);
			if (UNEXPECTED(classType.isUndef())) return zv::Val();
		}

		// the property's class/visibility/assign context is lexical, so it
		// comes from beforeScope
		zval *reflectionScope = beforeScope;
		zv::Val staticPropertyFetchedOnType;
		zval *class_ = exprClass(expr);
		if (UNEXPECTED(class_ == NULL)) return zv::Val();
		int classIsName = isInstanceOf(class_, PT_CLASS_NAME);
		if (UNEXPECTED(classIsName < 0)) return zv::Val();
		if (classIsName) {
			staticPropertyFetchedOnType = pt_mutating_scope_resolve_type_by_name(Z_OBJ_P(reflectionScope), Z_OBJ_P(class_));
			if (UNEXPECTED(staticPropertyFetchedOnType.isUndef())) return zv::Val();
		} else {
			// every caller walks a non-Name class and passes its result
			if (classType.isUndef()) {
				pt_throw_should_not_happen();
				return zv::Val();
			}
			zv::Val withoutNull = pt_type_combinator_remove_null(classType.raw());
			if (UNEXPECTED(withoutNull.isUndef())) return zv::Val();
			staticPropertyFetchedOnType = pt_type_call(Z_OBJ_P(withoutNull.raw()), PT_LC("getobjecttypeorclassstringobjecttype"), 0, NULL);
			if (UNEXPECTED(staticPropertyFetchedOnType.isUndef())) return zv::Val();
		}

		zval *name = exprName(expr);
		if (UNEXPECTED(name == NULL)) return zv::Val();
		int nameIsVarLikeIdentifier = isInstanceOf(name, PT_CLASS_VAR_LIKE_IDENTIFIER);
		if (UNEXPECTED(nameIsVarLikeIdentifier < 0)) return zv::Val();
		if (nameIsVarLikeIdentifier) {
			zval *propertyName = identifierName(name);
			if (UNEXPECTED(propertyName == NULL)) return zv::Val();
			if (UNEXPECTED(Z_TYPE_P(propertyName) != IS_STRING)) {
				zend_type_error("PhpParser\\Node\\Identifier::toString(): Return value must be of type string, %s returned", zend_zval_value_name(propertyName));
				return zv::Val();
			}
			zv::Val type = resolveProperty(nativeTypesPromoted, reflectionScope, staticPropertyFetchedOnType.raw(), expr, Z_STR_P(propertyName));
			if (UNEXPECTED(type.isUndef())) return zv::Val();
			// $shortCircuit($type)
			if (Z_TYPE_P(classResult) != IS_NULL) {
				bool containsNullsafe;
				if (UNEXPECTED(!pt_expression_result_contains_nullsafe(classResult, containsNullsafe))) return zv::Val();
				if (containsNullsafe && !classType.isUndef()) {
					bool containsNull;
					if (UNEXPECTED(!pt_type_combinator_contains_null(classType.raw(), containsNull))) return zv::Val();
					if (containsNull) return pt_type_combinator_add_null(type.raw());
				}
			}
			return type;
		}

		// dynamic static property fetch Foo::${$name}: resolve each possible
		// name from beforeScope; every caller walks a non-VarLikeIdentifier
		// name and passes its result
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
				zend_type_error("%s(): Argument #1 ($propertyName) must be of type string, %s given", pt_spfh_closure_name, zend_zval_value_name(value.raw()));
				return zv::Val();
			}
			zv::Val type = resolveProperty(nativeTypesPromoted, reflectionScope, staticPropertyFetchedOnType.raw(), expr, Z_STR_P(value.raw()));
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
		if (UNEXPECTED(!requireArguments(argc, 1, pt_spfh_closure_name))) return;
		zv::Val found = findPropertyReflectionFromNode(OBJ_PROP_NUM(Z_OBJ(captures[0]), slots::propertyReflectionFinder), &captures[1], &argv[0]);
		if (UNEXPECTED(found.isUndef())) return;
		found.intoReturnValue(return_value);
	}

	/* function (bool $nativeTypesPromoted) use ($expr, $classResult, $nameResult,
	 * $beforeScope): Type — captures: $this, $expr, $classResult, $nameResult,
	 * $beforeScope */
	static void typeCallbackBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		if (UNEXPECTED(!requireArguments(argc, 1, pt_spfh_closure_name))) return;
		zv::Val type = resolveType(zend_is_true(&argv[0]), &captures[1], &captures[2], &captures[3], &captures[4]);
		if (UNEXPECTED(type.isUndef())) return;
		type.intoReturnValue(return_value);
	}

	/* fn (TypeSpecifierContext $context, bool $nativeTypesPromoted): SpecifiedTypes =>
	 * $this->defaultNarrowingHelper->specifyDefaultTypes($expr, $context) —
	 * captures: $this, $expr */
	static void specifyTypesCallbackBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		if (UNEXPECTED(!requireArguments(argc, 2, pt_spfh_closure_name))) return;
		zv::Val specifiedTypes = pt_default_narrowing_helper_specify_default_types(OBJ_PROP_NUM(Z_OBJ(captures[0]), slots::defaultNarrowingHelper), &captures[1], &argv[0]);
		if (UNEXPECTED(specifiedTypes.isUndef())) return;
		specifiedTypes.intoReturnValue(return_value);
	}
};

} // namespace phpstanturbo

using phpstanturbo::StaticPropertyFetchHandler;

zv::Val pt_static_property_fetch_handler_compose_result(zval *handler, zval *expr, zval *classResult, zval *nameResult, zval *beforeScope)
{
	if (EXPECTED(Z_OBJCE_P(handler) == pt_ce_static_property_fetch_handler)) return StaticPropertyFetchHandler(Z_OBJ_P(handler)).composeResult(expr, classResult, nameResult, beforeScope);
	zval null;
	ZVAL_NULL(&null);
	zv::Args argv{expr, classResult != NULL ? classResult : &null, nameResult != NULL ? nameResult : &null, beforeScope};
	return pt_type_call(Z_OBJ_P(handler), PT_LC("composeresult"), 4, argv);
}

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

void pt_register_static_property_fetch_handler()
{
	pt_spfh_static_property_access = zend_string_init_interned(PT_LC("staticPropertyAccess"), 1);
	pt_spfh_static_property_access_description = zend_string_init_interned(PT_LC("static property access"), 1);

	reg::Class cls("PHPStan\\Analyser\\ExprHandler\\StaticPropertyFetchHandler");
	ptdecl::StaticPropertyFetchHandler::declareClass(cls);
	ptdecl::StaticPropertyFetchHandler::declareProperties(cls);

	/* the real parameter class names: the DI container autowires the
	 * service by reflecting the constructor */
	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *propertyReflectionFinder, *expressionResultFactory, *defaultNarrowingHelper;
		if (!zp::parse<zp::Obj, zp::Obj, zp::Obj>(execute_data, propertyReflectionFinder, expressionResultFactory, defaultNarrowingHelper)) RETURN_THROWS();
		StaticPropertyFetchHandler(Z_OBJ_P(ZEND_THIS)).construct(propertyReflectionFinder, expressionResultFactory, defaultNarrowingHelper);
	});

	cls.method<&StaticPropertyFetchHandler::supports, zp::Obj>(sigs::supports);

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
		PT_RETURN_VAL(StaticPropertyFetchHandler(Z_OBJ_P(ZEND_THIS)).processExpr(nodeScopeResolver, stmt, expr, scope, storage, nodeCallback, context));
	});

	cls.method(sigs::composeResult, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *expr, *classResult, *nameResult, *beforeScope;
		if (!zp::parse<zp::Obj, zp::ObjOrNull, zp::ObjOrNull, zp::Obj>(execute_data, expr, classResult, nameResult, beforeScope)) RETURN_THROWS();
		PT_RETURN_VAL(StaticPropertyFetchHandler(Z_OBJ_P(ZEND_THIS)).composeResult(expr, classResult, nameResult, beforeScope));
	});

	cls.shadow(&pt_ce_static_property_fetch_handler);
	pt_expr_handler_entry_register(&pt_ce_static_property_fetch_handler, &StaticPropertyFetchHandler::processExprEntry);
}

/* }}} */
