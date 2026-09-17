/*
 * PHPStanTurbo\StaticCallHandler — native implementation of
 * PHPStan\Analyser\ExprHandler\StaticCallHandler.
 *
 * A DI service (#[AutowiredService]): the constructor keeps the twin's
 * arginfo so Nette autowires it. processExpr() is registered as the class's
 * handler entry (Engine.h). The twin's closures are native closures capturing
 * what the PHP closures capture: the Closure::bind() scope factory handed to
 * processArgs() ($expr, $storage; its inner $readArgType closure, created and
 * called only inside it, is inlined), the typeCallback ($this, $beforeScope,
 * $expr, $classResult, $nameResult, $resolvedParametersAcceptor, $argsResult;
 * none for the early-terminating `new NeverType(true)` one), the
 * specifyTypesCallback ($this, $beforeScope, $expr, $normalizedExpr,
 * $classResult, $resolvedParametersAcceptor, $argsResult), the
 * createTypesCallback ($this, $expr, $classResult, $beforeScope) and the
 * asserts mapping callback of specifyTypes() ($resolvedParametersAcceptor).
 * The closures resolveReturnType() creates and calls itself are inlined.
 *
 * The analyser collaborators (NodeScopeResolver, MutatingScope,
 * ExpressionResult, ArgsResult, ExpressionContext, ExpressionResultStorage,
 * VariableFlow(Builder), the method reflections, ClassReflection,
 * SimpleImpurePoint, DynamicReturnTypeStoragePrimer, ImpurePoint,
 * InternalThrowPoint, TemplateArgumentFrame, SpecifiedTypes,
 * TypeSpecifierContext, TypeSpecifier, DefaultNarrowingHelper,
 * EarlyTerminatingCallHelper, MethodCallReturnTypeHelper,
 * MethodThrowPointHelper and the Type kernel) are called through their
 * direct entries; the collaborators that stay PHP for now through the cached
 * method sites of CallHandlerSupport.h (ArgumentsHandler,
 * ArgumentsNormalizer, ParametersAcceptorSelector, the parameters acceptors,
 * Assertions) and the block below (the extensions, php-parser's
 * Name::toLowerString(), the native reflection adapters).
 */

#include "support.h"
#include "generated/StaticCallHandler.h"

namespace slots = ptdecl::StaticCallHandler::slot;
namespace sigs = ptdecl::StaticCallHandler::sig;
#include "CallHandlerSupport.h"

zend_class_entry *pt_ce_static_call_handler = nullptr;

namespace {

using namespace ptcall;

/* {{{ the PHP collaborators only this handler calls (one site each; switch to
 * their direct entries once they are ported) */

pt_method_site pt_sch_is_static_method_supported_site;
pt_method_site pt_sch_extension_specify_types_site;
pt_method_site pt_sch_name_to_lower_string_site;
pt_method_site pt_sch_get_class_string_object_type_site;
pt_method_site pt_sch_get_object_type_or_class_string_object_type_site;
pt_method_site pt_sch_get_static_object_type_site;
pt_method_site pt_sch_native_get_properties_site;
pt_method_site pt_sch_property_is_promoted_site;
pt_method_site pt_sch_property_get_declaring_class_site;
pt_method_site pt_sch_property_declaring_class_get_name_site;
pt_method_site pt_sch_property_get_name_site;

/* $extension->isStaticMethodSupported($methodReflection, $normalizedExpr, $context) */
bool extensionIsStaticMethodSupported(zval *extension, zval *methodReflection, zval *normalizedExpr, zval *context, bool &out)
{
	zv::Args argv{methodReflection, normalizedExpr, context};
	zv::Val result = pt_call_method_cached(pt_sch_is_static_method_supported_site, Z_OBJ_P(extension), PT_LC("isstaticmethodsupported"), 3, argv);
	if (UNEXPECTED(result.isUndef())) return false;
	out = zend_is_true(result.raw());
	return true;
}

/* $extension->specifyTypes($methodReflection, $normalizedExpr, $scope, $context) */
zv::Val extensionSpecifyTypes(zval *extension, zval *methodReflection, zval *normalizedExpr, zval *scope, zval *context)
{
	zv::Args argv{methodReflection, normalizedExpr, scope, context};
	return pt_call_method_cached(pt_sch_extension_specify_types_site, Z_OBJ_P(extension), PT_LC("specifytypes"), 4, argv);
}

/* $name->toLowerString() */
zv::Val nameToLowerString(zval *name)
{
	return pt_call_method_cached(pt_sch_name_to_lower_string_site, Z_OBJ_P(name), PT_LC("tolowerstring"), 0, NULL);
}

/* $type->getClassStringObjectType() / ->getObjectTypeOrClassStringObjectType() */
zv::Val getClassStringObjectType(zval *type)
{
	return pt_call_method_cached(pt_sch_get_class_string_object_type_site, Z_OBJ_P(type), PT_LC("getclassstringobjecttype"), 0, NULL);
}

zv::Val getObjectTypeOrClassStringObjectType(zval *type)
{
	return pt_call_method_cached(pt_sch_get_object_type_or_class_string_object_type_site, Z_OBJ_P(type), PT_LC("getobjecttypeorclassstringobjecttype"), 0, NULL);
}

/* $staticType->getStaticObjectType() */
zv::Val getStaticObjectType(zval *staticType)
{
	return pt_call_method_cached(pt_sch_get_static_object_type_site, Z_OBJ_P(staticType), PT_LC("getstaticobjecttype"), 0, NULL);
}

/* $type->getMethod($methodName, $scope) */
zv::Val typeGetMethod(zval *type, zval *methodName, zval *scope)
{
	zv::Args argv{methodName, scope};
	return pt_type_op(Z_OBJ_P(type), PT_OP_GET_METHOD, 2, argv);
}

/* $nativeReflection->getProperties($filter) */
zv::Val nativeReflectionGetProperties(zval *nativeReflection, zend_long filter)
{
	zval filterZv;
	ZVAL_LONG(&filterZv, filter);
	return pt_call_method_cached(pt_sch_native_get_properties_site, Z_OBJ_P(nativeReflection), PT_LC("getproperties"), 1, &filterZv);
}

/* $property->isPromoted() / ->getDeclaringClass() / ->getName(), and the
 * declaring class's getName() */
zv::Val propertyIsPromoted(zval *property)
{
	return pt_call_method_cached(pt_sch_property_is_promoted_site, Z_OBJ_P(property), PT_LC("ispromoted"), 0, NULL);
}

zv::Val propertyGetDeclaringClass(zval *property)
{
	return pt_call_method_cached(pt_sch_property_get_declaring_class_site, Z_OBJ_P(property), PT_LC("getdeclaringclass"), 0, NULL);
}

zv::Val propertyDeclaringClassGetName(zval *declaringClass)
{
	return pt_call_method_cached(pt_sch_property_declaring_class_get_name_site, Z_OBJ_P(declaringClass), PT_LC("getname"), 0, NULL);
}

zv::Val propertyGetName(zval *property)
{
	return pt_call_method_cached(pt_sch_property_get_name_site, Z_OBJ_P(property), PT_LC("getname"), 0, NULL);
}

/* }}} */

/* {{{ the PhpParser nodes' properties */

pt_property_site pt_sch_class_site;
pt_property_site pt_sch_name_site;
pt_property_site pt_sch_args_site;
pt_property_site pt_sch_identifier_name_site;
pt_property_site pt_sch_arg_value_site;

zval *exprClass(zval *expr) { return nodeProperty(pt_sch_class_site, expr, PT_LC("class")); }
zval *exprName(zval *expr) { return nodeProperty(pt_sch_name_site, expr, PT_LC("name")); }
/* $expr->getArgs() of a StaticCall that is not a first-class callable (the
 * handler never sees one), and $expr->args: its $args */
zval *exprArgs(zval *expr) { return nodeProperty(pt_sch_args_site, expr, PT_LC("args")); }
/* $identifier->name / ->toString() */
zval *identifierName(zval *identifier) { return nodeProperty(pt_sch_identifier_name_site, identifier, PT_LC("name")); }
/* $arg->value */
zval *argValue(zval *arg) { return nodeProperty(pt_sch_arg_value_site, arg, PT_LC("value")); }

/* }}} */

/* {{{ small value helpers */

/* the twin's literals, permanent interned strings (module startup) */
zend_string *pt_sch_method_call = nullptr;
zend_string *pt_sch_unknown_method = nullptr;
zend_string *pt_sch_this = nullptr;
zend_string *pt_sch_static = nullptr;

/* $list[0] of a count-1 list, with the engine's warning (and null) for a
 * missing key; NULL = pending exception */
zval *firstOf(zval *list, zval *null)
{
	zval *first = zend_hash_index_find(Z_ARRVAL_P(list), 0);
	if (EXPECTED(first != NULL)) return first;
	zend_error(E_WARNING, "Undefined array key 0");
	if (UNEXPECTED(EG(exception))) return NULL;
	return null;
}

/* count($value) of an array result; -1 = pending TypeError */
zend_long countOf(zval *value)
{
	if (UNEXPECTED(Z_TYPE_P(value) != IS_ARRAY)) {
		zend_type_error("count(): Argument #1 ($value) must be of type Countable|array, %s given", zend_zval_value_name(value));
		return -1;
	}
	return zend_hash_num_elements(Z_ARRVAL_P(value));
}

/* isset($args[$index]) of a StaticCall's args: the argument or NULL */
zval *argAt(zval *args, zend_ulong index)
{
	zval *arg = zend_hash_index_find(Z_ARRVAL_P(args), index);
	if (arg == NULL) return NULL;
	ZVAL_DEREF(arg);
	return Z_TYPE_P(arg) == IS_NULL ? NULL : arg;
}

/* new Variable('this'); UNDEF = pending exception */
zv::Val newThisVariable()
{
	zval name;
	ZVAL_INTERNED_STR(&name, pt_sch_this);
	return pt_type_new(PT_CLASS_VARIABLE, 1, &name);
}

/* $trinary->yes() / ->maybe() / ->no() of a method reflection member; -1 =
 * pending exception */
zend_long reflectionTrinary(zval *methodReflection, pt_method_reflection_member member)
{
	return pt_extended_method_reflection_trinary(methodReflection, member);
}

/* $methodReflection->getName() === '__construct'; false = pending exception */
[[nodiscard]] bool isConstructorName(zval *methodReflection, bool &out)
{
	zv::Val methodName = pt_extended_method_reflection_call(methodReflection, PT_MR_GET_NAME);
	if (UNEXPECTED(methodName.isUndef())) return false;
	out = Z_TYPE_P(methodName.raw()) == IS_STRING && zend_string_equals_literal(Z_STR_P(methodName.raw()), "__construct");
	return true;
}

/* $methodReflection->isStatic() (coerced); false = pending exception */
[[nodiscard]] bool reflectionIsStatic(zval *methodReflection, bool &out)
{
	zv::Val isStatic = pt_extended_method_reflection_call(methodReflection, PT_MR_IS_STATIC);
	if (UNEXPECTED(isStatic.isUndef())) return false;
	out = zend_is_true(isStatic.raw());
	return true;
}

/* $scope->isInClass() && $scope->getClassReflection()->is($methodReflection->getDeclaringClass()->getName());
 * false = pending exception */
[[nodiscard]] bool scopeClassIsDeclaringClass(zval *scope, zval *methodReflection, bool &out)
{
	out = false;
	bool inClass;
	if (UNEXPECTED(!pt_scope_is_in_class(Z_OBJ_P(scope), inClass))) return false;
	if (!inClass) return true;
	zv::Val classReflection = pt_scope_get_class_reflection(Z_OBJ_P(scope));
	if (UNEXPECTED(classReflection.isUndef())) return false;
	if (UNEXPECTED(!classReflection.ref().isObject())) {
		zend_throw_error(NULL, "Call to a member function is() on %s", zend_zval_value_name(classReflection.raw()));
		return false;
	}
	zv::Val declaringClass = pt_extended_method_reflection_call(methodReflection, PT_MR_GET_DECLARING_CLASS);
	if (UNEXPECTED(declaringClass.isUndef())) return false;
	zv::Val declaringClassName = pt_class_reflection_get_name(Z_OBJ_P(declaringClass.raw()));
	if (UNEXPECTED(declaringClassName.isUndef())) return false;
	if (UNEXPECTED(!declaringClassName.ref().isString())) {
		zend_type_error("PHPStan\\Reflection\\ClassReflection::is(): Argument #1 ($className) must be of type string, %s given", zend_zval_value_name(declaringClassName.raw()));
		return false;
	}
	return pt_class_reflection_is(Z_OBJ_P(classReflection.raw()), declaringClassName.raw(), out);
}

/* }}} */

} // namespace

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\ExprHandler\StaticCallHandler; UNDEF = pending
 * exception. */
class StaticCallHandler
{
public:
	explicit StaticCallHandler(zend_object *self) : self(self) {}

	/* the constructor body: the promoted properties */
	void construct(zval **services, bool rememberPossiblyImpureFunctionValues) const
	{
		static const uint32_t serviceSlots[9] = {
			slots::methodCallReturnTypeHelper, slots::methodThrowPointHelper, slots::reflectionProvider,
			slots::expressionResultFactory, slots::typeSpecifier, slots::defaultNarrowingHelper, slots::storagePrimer,
			slots::earlyTerminatingHelper, slots::argumentsHandler,
		};
		for (uint32_t i = 0; i < 3; i++) {
			pt_write_slot(self, serviceSlots[i], services[i]);
		}
		zval remember = {};
		ZVAL_BOOL(&remember, rememberPossiblyImpureFunctionValues);
		pt_write_slot(self, slots::rememberPossiblyImpureFunctionValues, &remember);
		for (uint32_t i = 3; i < 9; i++) {
			pt_write_slot(self, serviceSlots[i], services[i]);
		}
	}

	/* Mirrors supports(); false = pending exception */
	[[nodiscard]] bool supports(zval *expr, bool &out) const
	{
		zend_class_entry *staticCallCe = pt_class(PT_CLASS_STATIC_CALL);
		if (UNEXPECTED(staticCallCe == NULL)) return false;
		if (!instanceof_function(Z_OBJCE_P(expr), staticCallCe)) {
			out = false;
			return true;
		}
		bool firstClassCallable;
		if (UNEXPECTED(!pt_call_like_is_first_class_callable(Z_OBJ_P(expr), firstClassCallable))) return false;
		out = !firstClassCallable;
		return true;
	}

	/* Mirrors processExpr(). */
	zv::Val processExpr(zval *nodeScopeResolver, zval *stmt, zval *expr, zval *scope, zval *storage, zval *nodeCallback, zval *context) const
	{
		zval null;
		ZVAL_NULL(&null);
		zval *beforeScope = scope;
		bool hasYield = false;
		zv::Val throwPoints = zv::Val(zv::Arr::empty());
		zv::Val impurePoints = zv::Val(zv::Arr::empty());
		bool isAlwaysTerminating = false;
		bool containsNullsafe = false;
		zv::Val classResult = zv::Val::null();
		zv::Val nameResult = zv::Val::null();
		zv::Val currentScope = zv::Val::copyOf(zv::Ref(scope));
		zv::Val hold;
		zval *borrowed;

		zval *class_ = exprClass(expr);
		if (UNEXPECTED(class_ == NULL)) return zv::Val();
		int classIsExpr = isInstanceOf(class_, PT_CLASS_EXPR);
		if (UNEXPECTED(classIsExpr < 0)) return zv::Val();
		if (classIsExpr) {
			zv::Val classContext = pt_expression_context_enter_deep(context);
			if (UNEXPECTED(classContext.isUndef())) return zv::Val();
			classResult = pt_node_scope_resolver_process_expr_node(nodeScopeResolver, stmt, class_, scope, storage, nodeCallback, classContext.raw());
			if (UNEXPECTED(classResult.isUndef())) return zv::Val();
			if (UNEXPECTED(!pt_expression_result_has_yield(classResult.raw(), hasYield))) return zv::Val();
			borrowed = pt_expression_result_throw_points(classResult.raw(), hold);
			if (UNEXPECTED(borrowed == NULL)) return zv::Val();
			throwPoints = arrayMerge(throwPoints.raw(), borrowed);
			borrowed = pt_expression_result_impure_points(classResult.raw(), hold);
			if (UNEXPECTED(borrowed == NULL)) return zv::Val();
			impurePoints = arrayMerge(impurePoints.raw(), borrowed);
			if (UNEXPECTED(!pt_expression_result_is_always_terminating(classResult.raw(), isAlwaysTerminating))) return zv::Val();

			borrowed = pt_expression_result_scope(classResult.raw(), hold);
			if (UNEXPECTED(borrowed == NULL)) return zv::Val();
			currentScope = zv::Val::copyOf(zv::Ref(borrowed));
			if (UNEXPECTED(!pt_expression_result_contains_nullsafe(classResult.raw(), containsNullsafe))) return zv::Val();
		}

		zval *name = exprName(expr);
		if (UNEXPECTED(name == NULL)) return zv::Val();
		int nameIsIdentifier = isIdentifier(name);
		if (UNEXPECTED(nameIsIdentifier < 0)) return zv::Val();
		int classIsName = isInstanceOf(class_, PT_CLASS_NAME);
		if (UNEXPECTED(classIsName < 0)) return zv::Val();

		// `$a?->b::c()` is a link in a nullsafe chain and may never run: the chain
		// short-circuits to null before the arguments are evaluated and before any
		// of the call's effects happen. See MethodCallHandler::processExpr().
		bool mayShortCircuit = false;
		if (!classResult.isNull() && containsNullsafe && UNEXPECTED(!pt_expression_result_may_short_circuit(classResult.raw(), NULL, mayShortCircuit))) return zv::Val();
		// A static call configured as early-terminating never returns: give it an
		// explicit never so the statement's exit point follows from the result type,
		// instead of NodeScopeResolver re-deriving it via Scope::getType().
		bool isEarlyTerminating = false;
		if (nameIsIdentifier && !mayShortCircuit) {
			zv::Val earlyTerminatingClassType = classIsName
				? pt_mutating_scope_resolve_type_by_name(Z_OBJ_P(currentScope.raw()), Z_OBJ_P(class_))
				: pt_expression_result_get_type(classResult.raw());
			if (UNEXPECTED(earlyTerminatingClassType.isUndef())) return zv::Val();
			zval *methodName = identifierName(name);
			if (UNEXPECTED(methodName == NULL)) return zv::Val();
			if (UNEXPECTED(!pt_early_terminating_call_helper_is_early_terminating_method_call(OBJ_PROP_NUM(self, slots::earlyTerminatingHelper), methodName, earlyTerminatingClassType.raw(), isEarlyTerminating))) return zv::Val();
		}
		isAlwaysTerminating = isAlwaysTerminating || isEarlyTerminating;

		zv::Val parametersAcceptor = zv::Val::null();
		zv::Val variants = zv::Val(zv::Arr::empty());
		zv::Val namedArgumentsVariants = zv::Val::null();
		zv::Val methodReflection = zv::Val::null();
		zv::Val closureBindScopeFactory = zv::Val::null();
		if (nameIsIdentifier) {
			if (classIsName) {
				// the acceptor selected here feeds the call's return type - a
				// STATIC method called through an explicit class name binds
				// `static` to that class, so select from the demoted type
				zval *methodName = identifierName(name);
				if (UNEXPECTED(methodName == NULL)) return zv::Val();
				zv::Val methodNameHold = zv::Val::copyOf(zv::Ref(methodName));
				zv::Val classType = resolveTypeByNameWithLateStaticBinding(currentScope.raw(), class_, methodNameHold.raw());
				if (UNEXPECTED(classType.isUndef())) return zv::Val();
				zend_long hasMethod = pt_type_op_trinary(Z_OBJ_P(classType.raw()), PT_OP_HAS_METHOD, 1, methodNameHold.raw());
				if (UNEXPECTED(hasMethod < 0)) return zv::Val();
				if (hasMethod == PT_TRI_YES) {
					methodReflection = typeGetMethod(classType.raw(), methodNameHold.raw(), currentScope.raw());
					if (UNEXPECTED(methodReflection.isUndef())) return zv::Val();
					variants = pt_extended_method_reflection_call(methodReflection.raw(), PT_MR_GET_VARIANTS);
					if (UNEXPECTED(variants.isUndef())) return zv::Val();
					namedArgumentsVariants = pt_extended_method_reflection_call(methodReflection.raw(), PT_MR_GET_NAMED_ARGUMENTS_VARIANTS);
					if (UNEXPECTED(namedArgumentsVariants.isUndef())) return zv::Val();
					// A structural acceptor (names/positions/variadic) drives argument
					// normalization, the impure point and the throw point - generics are
					// resolved type-driven by processArgs() into $resolvedParametersAcceptor.
					zval *args = exprArgs(expr);
					if (UNEXPECTED(args == NULL)) return zv::Val();
					parametersAcceptor = combineVariantsForNormalization(args, variants.raw(), namedArgumentsVariants.raw());
					if (UNEXPECTED(parametersAcceptor.isUndef())) return zv::Val();

					zv::Val declaringClass = pt_extended_method_reflection_call(methodReflection.raw(), PT_MR_GET_DECLARING_CLASS);
					if (UNEXPECTED(declaringClass.isUndef())) return zv::Val();
					zv::Val declaringClassName = pt_class_reflection_get_name(Z_OBJ_P(declaringClass.raw()));
					if (UNEXPECTED(declaringClassName.isUndef())) return zv::Val();
					if (
						Z_TYPE_P(declaringClassName.raw()) == IS_STRING
						&& zend_string_equals_literal(Z_STR_P(declaringClassName.raw()), "Closure")
					) {
						if (UNEXPECTED(Z_TYPE_P(methodNameHold.raw()) != IS_STRING)) {
							zend_type_error("strtolower(): Argument #1 ($string) must be of type string, %s given", zend_zval_value_name(methodNameHold.raw()));
							return zv::Val();
						}
						if (zend_string_equals_literal_ci(Z_STR_P(methodNameHold.raw()), "bind")) {
							closureBindScopeFactory = pt_native_closure(&closureBindScopeFactoryBody, expr, storage);
						}
					}
				} else {
					zv::Val throwPoint = pt_internal_throw_point_create_implicit(currentScope.raw(), expr);
					if (UNEXPECTED(throwPoint.isUndef())) return zv::Val();
					appendTo(throwPoints, std::move(throwPoint));
				}
			} else if (classIsExpr) {
				// the class expr was processed above as the receiver; read its
				// already-computed result instead of re-walking via Scope::getType().
				// A nullsafe receiver's null is the chain short-circuit, not a
				// callee - strip it before the reflection lookup, like the
				// return-type resolution does.
				zv::Val classResultType = pt_expression_result_get_type(classResult.raw());
				if (UNEXPECTED(classResultType.isUndef())) return zv::Val();
				zv::Val withoutNull = pt_type_combinator_remove_null(classResultType.raw());
				if (UNEXPECTED(withoutNull.isUndef())) return zv::Val();
				zv::Val classType = getObjectTypeOrClassStringObjectType(withoutNull.raw());
				if (UNEXPECTED(classType.isUndef())) return zv::Val();
				zval *methodName = identifierName(name);
				if (UNEXPECTED(methodName == NULL)) return zv::Val();
				if (UNEXPECTED(Z_TYPE_P(methodName) != IS_STRING)) {
					zend_type_error("PHPStan\\Analyser\\MutatingScope::getMethodReflection(): Argument #2 ($methodName) must be of type string, %s given", zend_zval_value_name(methodName));
					return zv::Val();
				}
				methodReflection = pt_mutating_scope_get_method_reflection(Z_OBJ_P(currentScope.raw()), classType.raw(), Z_STR_P(methodName));
				if (UNEXPECTED(methodReflection.isUndef())) return zv::Val();
				if (!methodReflection.isNull()) {
					variants = pt_extended_method_reflection_call(methodReflection.raw(), PT_MR_GET_VARIANTS);
					if (UNEXPECTED(variants.isUndef())) return zv::Val();
					namedArgumentsVariants = pt_extended_method_reflection_call(methodReflection.raw(), PT_MR_GET_NAMED_ARGUMENTS_VARIANTS);
					if (UNEXPECTED(namedArgumentsVariants.isUndef())) return zv::Val();
					zval *args = exprArgs(expr);
					if (UNEXPECTED(args == NULL)) return zv::Val();
					parametersAcceptor = combineVariantsForNormalization(args, variants.raw(), namedArgumentsVariants.raw());
					if (UNEXPECTED(parametersAcceptor.isUndef())) return zv::Val();
				}
			}
		} else {
			zv::Val nameContext = pt_expression_context_enter_deep(context);
			if (UNEXPECTED(nameContext.isUndef())) return zv::Val();
			nameResult = pt_node_scope_resolver_process_expr_node(nodeScopeResolver, stmt, name, currentScope.raw(), storage, nodeCallback, nameContext.raw());
			if (UNEXPECTED(nameResult.isUndef())) return zv::Val();
			if (!hasYield && UNEXPECTED(!pt_expression_result_has_yield(nameResult.raw(), hasYield))) return zv::Val();
			borrowed = pt_expression_result_throw_points(nameResult.raw(), hold);
			if (UNEXPECTED(borrowed == NULL)) return zv::Val();
			throwPoints = arrayMerge(throwPoints.raw(), borrowed);
			borrowed = pt_expression_result_impure_points(nameResult.raw(), hold);
			if (UNEXPECTED(borrowed == NULL)) return zv::Val();
			impurePoints = arrayMerge(impurePoints.raw(), borrowed);
			if (!isAlwaysTerminating && UNEXPECTED(!pt_expression_result_is_always_terminating(nameResult.raw(), isAlwaysTerminating))) return zv::Val();
			borrowed = pt_expression_result_scope(nameResult.raw(), hold);
			if (UNEXPECTED(borrowed == NULL)) return zv::Val();
			currentScope = zv::Val::copyOf(zv::Ref(borrowed));
		}

		if (classIsExpr) {
			// the class expr was processed above as the receiver; read its
			// already-computed result instead of re-walking via Scope::getType().
			zv::Val classResultType = pt_expression_result_get_type(classResult.raw());
			if (UNEXPECTED(classResultType.isUndef())) return zv::Val();
			zv::Val objectClasses = pt_type_op(Z_OBJ_P(classResultType.raw()), PT_OP_GET_OBJECT_CLASS_NAMES, 0, NULL);
			if (UNEXPECTED(objectClasses.isUndef())) return zv::Val();
			zend_long objectClassCount = countOf(objectClasses.raw());
			if (UNEXPECTED(objectClassCount < 0)) return zv::Val();
			if (objectClassCount != 1) {
				// the receiver may be a class-string instead of an object - the
				// instantiated type is what `new` would produce, read from the
				// same result instead of walking a synthetic New_ node
				zv::Val again = pt_expression_result_get_type(classResult.raw());
				if (UNEXPECTED(again.isUndef())) return zv::Val();
				zv::Val objectType = getObjectTypeOrClassStringObjectType(again.raw());
				if (UNEXPECTED(objectType.isUndef())) return zv::Val();
				objectClasses = pt_type_op(Z_OBJ_P(objectType.raw()), PT_OP_GET_OBJECT_CLASS_NAMES, 0, NULL);
				if (UNEXPECTED(objectClasses.isUndef())) return zv::Val();
				objectClassCount = countOf(objectClasses.raw());
				if (UNEXPECTED(objectClassCount < 0)) return zv::Val();
			}
			zv::Val additionalThrowPoints;
			if (objectClassCount == 1) {
				zval *objectClass = firstOf(objectClasses.raw(), &null);
				if (UNEXPECTED(objectClass == NULL)) return zv::Val();
				zv::Val objectClassName = pt_type_new(PT_CLASS_NAME, 1, objectClass);
				if (UNEXPECTED(objectClassName.isUndef())) return zv::Val();
				zval *currentName = exprName(expr);
				if (UNEXPECTED(currentName == NULL)) return zv::Val();
				zv::Arr noArgs = zv::Arr::empty();
				zv::Args staticCallArgv{objectClassName.raw(), currentName, noArgs.raw()};
				zv::Val syntheticCall = pt_type_new(PT_CLASS_STATIC_CALL, 3, staticCallArgv);
				if (UNEXPECTED(syntheticCall.isUndef())) return zv::Val();
				zv::Val noopNodeCallback = pt_type_new(PT_CLASS_NOOP_NODE_CALLBACK, 0, NULL);
				if (UNEXPECTED(noopNodeCallback.isUndef())) return zv::Val();
				zv::Val deepContext = pt_expression_context_enter_deep(context);
				if (UNEXPECTED(deepContext.isUndef())) return zv::Val();
				zv::Val syntheticContext = pt_expression_context_without_template_argument_resolution(deepContext.raw());
				if (UNEXPECTED(syntheticContext.isUndef())) return zv::Val();
				zv::Val objectExprResult = pt_node_scope_resolver_process_expr_node(nodeScopeResolver, stmt, syntheticCall.raw(), currentScope.raw(), storage, noopNodeCallback.raw(), syntheticContext.raw());
				if (UNEXPECTED(objectExprResult.isUndef())) return zv::Val();
				borrowed = pt_expression_result_throw_points(objectExprResult.raw(), hold);
				if (UNEXPECTED(borrowed == NULL)) return zv::Val();
				additionalThrowPoints = zv::Val::copyOf(zv::Ref(borrowed));
			} else {
				zv::Val throwPoint = pt_internal_throw_point_create_implicit(currentScope.raw(), expr);
				if (UNEXPECTED(throwPoint.isUndef())) return zv::Val();
				zv::Arr list = zv::Arr::create(1);
				list.push(std::move(throwPoint));
				additionalThrowPoints = zv::Val(std::move(list));
			}
			if (UNEXPECTED(Z_TYPE_P(additionalThrowPoints.raw()) != IS_ARRAY)) {
				zend_error(E_WARNING, "foreach() argument must be of type array|object, %s given", zend_zval_value_name(additionalThrowPoints.raw()));
				if (UNEXPECTED(EG(exception))) return zv::Val();
			} else {
				for (zv::ArrayEntry entry : zv::ArrRef(additionalThrowPoints.raw())) {
					appendTo(throwPoints, zv::Val::copyOf(entry.value().deref()));
				}
			}
		}

		zv::Val normalizedExpr = zv::Val::copyOf(zv::Ref(expr));
		if (!parametersAcceptor.isNull()) {
			zv::Val reordered = reorderStaticCallArguments(parametersAcceptor.raw(), expr);
			if (UNEXPECTED(reordered.isUndef())) return zv::Val();
			if (!reordered.isNull()) normalizedExpr = std::move(reordered);
			zv::Val returnType = acceptorReturnType(parametersAcceptor.raw());
			if (UNEXPECTED(returnType.isUndef())) return zv::Val();
			if (!isAlwaysTerminating && !mayShortCircuit) {
				bool explicitNever;
				if (UNEXPECTED(!isExplicitNever(returnType.raw(), explicitNever))) return zv::Val();
				isAlwaysTerminating = explicitNever;
			}
		}
		zv::Val scopeBeforeArgs = zv::Val::copyOf(zv::Ref(currentScope.raw()));
		zv::Val currentContext = zv::Val::copyOf(zv::Ref(context));
		if (!parametersAcceptor.isNull()) {
			zv::Val inAssignRightSideExpr = pt_expression_context_get_in_assign_right_side_expr(context);
			if (UNEXPECTED(inAssignRightSideExpr.isUndef())) return zv::Val();
			if (Z_TYPE_P(inAssignRightSideExpr.raw()) == IS_OBJECT && Z_OBJ_P(inAssignRightSideExpr.raw()) == Z_OBJ_P(expr)) {
				currentContext = pt_expression_context_enter_assign_right_side_call_args(context, parametersAcceptor.raw());
				if (UNEXPECTED(currentContext.isUndef())) return zv::Val();
			}
		}
		zv::Val argsResult;
		{
			zv::Args argv{nodeScopeResolver, stmt, methodReflection.raw(), &null, variants.raw(), namedArgumentsVariants.raw(), normalizedExpr.raw(), currentScope.raw(), storage, nodeCallback, currentContext.raw(), closureBindScopeFactory.raw()};
			argsResult = processArgs(OBJ_PROP_NUM(self, slots::argumentsHandler), 12, argv);
		}
		if (UNEXPECTED(argsResult.isUndef())) return zv::Val();
		borrowed = pt_args_result_resolved_parameters_acceptor(argsResult.raw(), hold);
		if (UNEXPECTED(borrowed == NULL)) return zv::Val();
		zv::Val resolvedParametersAcceptor = zv::Val::copyOf(zv::Ref(borrowed));
		borrowed = pt_args_result_scope(argsResult.raw(), hold);
		if (UNEXPECTED(borrowed == NULL)) return zv::Val();
		currentScope = zv::Val::copyOf(zv::Ref(borrowed));
		{
			zv::Args argv{nodeScopeResolver, stmt, expr, normalizedExpr.raw(), currentScope.raw(), storage, currentContext.raw()};
			if (UNEXPECTED(!processDroppedArgs(OBJ_PROP_NUM(self, slots::argumentsHandler), argv))) return zv::Val();
		}

		if (!methodReflection.isNull()) {
			// created after the args were processed - the pure-unless-callable-
			// is-impure parameters read an argument's type, which is only
			// available once its result is stored
			zval *args = exprArgs(expr);
			if (UNEXPECTED(args == NULL)) return zv::Val();
			pt_simple_impure_point_data impurePoint;
			if (UNEXPECTED(!pt_simple_impure_point_resolve(methodReflection.raw(), parametersAcceptor.raw(), currentScope.raw(), args, impurePoint))) return zv::Val();
			if (impurePoint.exists) {
				zv::Val point = pt_impure_point_new(scopeBeforeArgs.raw(), expr, impurePoint.identifier, impurePoint.description, impurePoint.certain);
				zend_string_release(impurePoint.description);
				if (UNEXPECTED(point.isUndef())) return zv::Val();
				appendTo(impurePoints, std::move(point));
			}
		} else {
			zv::Val point = pt_impure_point_new(scopeBeforeArgs.raw(), expr, pt_sch_method_call, pt_sch_unknown_method, false);
			if (UNEXPECTED(point.isUndef())) return zv::Val();
			appendTo(impurePoints, std::move(point));
		}
		zv::Val scopeFunction = pt_mutating_scope_get_function(Z_OBJ_P(currentScope.raw()));
		if (UNEXPECTED(scopeFunction.isUndef())) return zv::Val();

		// The early structural check above only sees the unresolved acceptor return
		// type; a conditional-return never only resolves to never once the actual
		// argument types are folded in by the type-driven resolved acceptor.
		if (!resolvedParametersAcceptor.isNull() && !mayShortCircuit) {
			zv::Val resolvedReturnType = resolvedAcceptorReturnType(resolvedParametersAcceptor.raw());
			if (UNEXPECTED(resolvedReturnType.isUndef())) return zv::Val();
			if (!isAlwaysTerminating) {
				bool explicitNever;
				if (UNEXPECTED(!isExplicitNever(resolvedReturnType.raw(), explicitNever))) return zv::Val();
				isAlwaysTerminating = explicitNever;
			}
		}

		// The return type is derived from $resolvedParametersAcceptor - the acceptor
		// processArgs() selected from the arg types gathered on the arg-to-arg
		// evolving scope (type-driven, generics resolved). When null
		// (native-types-promoted, or on-demand / synthetic pricing) the acceptor is
		// re-derived from the already-processed argument results on the asking scope.
		zv::Val typeCallback = isEarlyTerminating
			? pt_native_closure(&earlyTerminatingTypeCallbackBody)
			: pt_native_closure(&typeCallbackBody, self, beforeScope, expr, classResult.raw(), nameResult.raw(), resolvedParametersAcceptor.raw(), argsResult.raw());
		zv::Val specifyTypesCallback = pt_native_closure(&specifyTypesCallbackBody, self, beforeScope, expr, normalizedExpr.raw(), classResult.raw(), resolvedParametersAcceptor.raw(), methodReflection.raw(), argsResult.raw());
		// A type constraint on a (narrowable, i.e. non-side-effecting) static call
		// narrows the call itself - the inside-out equivalent of createForExpr's
		// StaticCall purity gate + tail entry. An impure call narrows to nothing.
		zv::Val createTypesCallback = pt_native_closure(&createTypesCallbackBody, self, expr, classResult.raw(), beforeScope);

		// Store a preliminary result carrying the type/specify callbacks before the
		// throw point is computed: the method throw point resolves the return type
		// through dynamic static-method return type extensions, which can narrow
		// this very call on demand. finalize() below completes it with the
		// resolved scope and throw/impure points.
		pt_expression_result_args resultArgs(currentScope.raw(), beforeScope, expr, hasYield, isAlwaysTerminating, NULL, NULL, typeCallback.raw(), specifyTypesCallback.raw());
		resultArgs.withContainsNullsafe(containsNullsafe).withCreateTypesCallback(createTypesCallback.raw()).withArgsResult(argsResult.raw());
		zv::Val preliminaryResult = pt_expression_result_create(OBJ_PROP_NUM(self, slots::expressionResultFactory), resultArgs);
		if (UNEXPECTED(preliminaryResult.isUndef())) return zv::Val();
		if (UNEXPECTED(!pt_node_scope_resolver_store_expression_result(nodeScopeResolver, storage, expr, preliminaryResult.raw()))) return zv::Val();

		if (!methodReflection.isNull()) {
			// Resolve the call's return type through the stored preliminary result so
			// the memoized value seeds the final result below.
			zv::Val staticCallReturnType = pt_expression_result_get_keep_void_type(preliminaryResult.raw(), false);
			if (UNEXPECTED(staticCallReturnType.isUndef())) return zv::Val();
			zv::Val methodThrowPoint = pt_method_throw_point_helper_get_throw_point(OBJ_PROP_NUM(self, slots::methodThrowPointHelper), methodReflection.raw(), parametersAcceptor.raw(), normalizedExpr.raw(), currentScope.raw(), currentContext.raw(), staticCallReturnType.raw());
			if (UNEXPECTED(methodThrowPoint.isUndef())) return zv::Val();
			if (!methodThrowPoint.isNull()) {
				appendTo(throwPoints, std::move(methodThrowPoint));
			}
		}

		if (classIsName && !methodReflection.isNull()) {
			bool invalidate = false;
			{
				bool isStatic;
				if (UNEXPECTED(!reflectionIsStatic(methodReflection.raw(), isStatic))) return zv::Val();
				if (!isStatic && UNEXPECTED(!isConstructorName(methodReflection.raw(), invalidate))) return zv::Val();
			}
			if (!invalidate) {
				zend_long hasSideEffects = reflectionTrinary(methodReflection.raw(), PT_MR_HAS_SIDE_EFFECTS);
				if (UNEXPECTED(hasSideEffects < 0)) return zv::Val();
				invalidate = hasSideEffects == PT_TRI_YES;
			}
			if (invalidate && UNEXPECTED(!scopeClassIsDeclaringClass(currentScope.raw(), methodReflection.raw(), invalidate))) return zv::Val();
			if (invalidate) {
				// a static method never receives $this, so property fetches on it survive
				zv::Val thisVariable = newThisVariable();
				if (UNEXPECTED(thisVariable.isUndef())) return zv::Val();
				zv::Val declaringClass = pt_extended_method_reflection_call(methodReflection.raw(), PT_MR_GET_DECLARING_CLASS);
				if (UNEXPECTED(declaringClass.isUndef())) return zv::Val();
				bool isStatic;
				if (UNEXPECTED(!reflectionIsStatic(methodReflection.raw(), isStatic))) return zv::Val();
				currentScope = pt_mutating_scope_invalidate_expression(Z_OBJ_P(currentScope.raw()), thisVariable.raw(), true, declaringClass.raw(), isStatic);
				if (UNEXPECTED(currentScope.isUndef())) return zv::Val();
			} else if (zend_is_true(OBJ_PROP_NUM(self, slots::rememberPossiblyImpureFunctionValues))) {
				bool remember;
				if (UNEXPECTED(!scopeClassIsDeclaringClass(currentScope.raw(), methodReflection.raw(), remember))) return zv::Val();
				if (remember) {
					zend_long hasSideEffects = reflectionTrinary(methodReflection.raw(), PT_MR_HAS_SIDE_EFFECTS);
					if (UNEXPECTED(hasSideEffects < 0)) return zv::Val();
					remember = hasSideEffects == PT_TRI_MAYBE;
				}
				if (remember) {
					zv::Val declaringClass = pt_extended_method_reflection_call(methodReflection.raw(), PT_MR_GET_DECLARING_CLASS);
					if (UNEXPECTED(declaringClass.isUndef())) return zv::Val();
					bool builtin;
					if (UNEXPECTED(!pt_class_reflection_is_builtin(Z_OBJ_P(declaringClass.raw()), builtin))) return zv::Val();
					remember = !builtin;
				}
				if (remember) {
					// the remembered call value is generic-sensitive: resolve it from the
					// type-driven acceptor processArgs() selected, falling back to the
					// structural acceptor.
					zval *acceptorForGenerics = resolvedParametersAcceptor.isNull() ? parametersAcceptor.raw() : resolvedParametersAcceptor.raw();
					zv::Val thisVariable = newThisVariable();
					if (UNEXPECTED(thisVariable.isUndef())) return zv::Val();
					zv::Val description = possiblyImpureCallDescription(methodReflection.raw());
					if (UNEXPECTED(description.isUndef())) return zv::Val();
					zv::Args exprArgv{normalizedExpr.raw(), thisVariable.raw(), description.raw()};
					zv::Val possiblyImpureCallExpr = pt_type_new(PT_CLASS_POSSIBLY_IMPURE_CALL_EXPR, 3, exprArgv);
					if (UNEXPECTED(possiblyImpureCallExpr.isUndef())) return zv::Val();
					zv::Val rememberedType = pt_template_argument_frame_return_type_of_call(acceptorForGenerics, currentScope.raw(), expr);
					if (UNEXPECTED(rememberedType.isUndef())) return zv::Val();
					zv::Val mixed = pt_type_new_mixed_type();
					if (UNEXPECTED(mixed.isUndef())) return zv::Val();
					currentScope = pt_mutating_scope_assign_expression(Z_OBJ_P(currentScope.raw()), Z_OBJ_P(possiblyImpureCallExpr.raw()), rememberedType.raw(), mixed.raw());
					if (UNEXPECTED(currentScope.isUndef())) return zv::Val();
				}
			}
		}

		if (classIsName && !methodReflection.isNull()) {
			bool initializeProperties;
			bool isStatic;
			if (UNEXPECTED(!reflectionIsStatic(methodReflection.raw(), isStatic))) return zv::Val();
			initializeProperties = !isStatic;
			if (initializeProperties && UNEXPECTED(!isConstructorName(methodReflection.raw(), initializeProperties))) return zv::Val();
			if (initializeProperties) {
				int functionIsMethod = isInstanceOf(scopeFunction.raw(), PT_CLASS_METHOD_REFLECTION);
				if (UNEXPECTED(functionIsMethod < 0)) return zv::Val();
				initializeProperties = functionIsMethod == 1;
			}
			if (initializeProperties) {
				bool functionIsStatic;
				if (UNEXPECTED(!reflectionIsStatic(scopeFunction.raw(), functionIsStatic))) return zv::Val();
				initializeProperties = !functionIsStatic;
			}
			if (initializeProperties && UNEXPECTED(!pt_scope_is_in_class(Z_OBJ_P(currentScope.raw()), initializeProperties))) return zv::Val();
			if (initializeProperties) {
				zv::Val classReflection = pt_scope_get_class_reflection(Z_OBJ_P(currentScope.raw()));
				if (UNEXPECTED(classReflection.isUndef())) return zv::Val();
				if (UNEXPECTED(!classReflection.ref().isObject())) {
					zend_throw_error(NULL, "Call to a member function isSubclassOfClass() on %s", zend_zval_value_name(classReflection.raw()));
					return zv::Val();
				}
				zv::Val declaringClass = pt_extended_method_reflection_call(methodReflection.raw(), PT_MR_GET_DECLARING_CLASS);
				if (UNEXPECTED(declaringClass.isUndef())) return zv::Val();
				if (UNEXPECTED(!pt_class_reflection_is_subclass_of_class(Z_OBJ_P(classReflection.raw()), declaringClass.raw(), initializeProperties))) return zv::Val();
			}
			if (initializeProperties) {
				currentScope = initializePromotedProperties(currentScope.raw(), methodReflection.raw());
				if (UNEXPECTED(currentScope.isUndef())) return zv::Val();
			}
		}

		bool invalidateVolatile = methodReflection.isNull();
		if (!invalidateVolatile) {
			zv::Val declaringClass = pt_extended_method_reflection_call(methodReflection.raw(), PT_MR_GET_DECLARING_CLASS);
			if (UNEXPECTED(declaringClass.isUndef())) return zv::Val();
			bool builtin;
			if (UNEXPECTED(!pt_class_reflection_is_builtin(Z_OBJ_P(declaringClass.raw()), builtin))) return zv::Val();
			if (!builtin) {
				zend_long hasSideEffects = reflectionTrinary(methodReflection.raw(), PT_MR_HAS_SIDE_EFFECTS);
				if (UNEXPECTED(hasSideEffects < 0)) return zv::Val();
				invalidateVolatile = hasSideEffects != PT_TRI_NO;
			}
		}
		if (invalidateVolatile) {
			currentScope = pt_mutating_scope_invalidate_volatile_expressions(Z_OBJ_P(currentScope.raw()));
			if (UNEXPECTED(currentScope.isUndef())) return zv::Val();
		}

		if (!hasYield && UNEXPECTED(!pt_args_result_has_yield(argsResult.raw(), hasYield))) return zv::Val();
		borrowed = pt_args_result_throw_points(argsResult.raw(), hold);
		if (UNEXPECTED(borrowed == NULL)) return zv::Val();
		throwPoints = arrayMerge(throwPoints.raw(), borrowed);
		borrowed = pt_args_result_impure_points(argsResult.raw(), hold);
		if (UNEXPECTED(borrowed == NULL)) return zv::Val();
		impurePoints = arrayMerge(impurePoints.raw(), borrowed);
		if (!isAlwaysTerminating && !mayShortCircuit && UNEXPECTED(!pt_args_result_is_always_terminating(argsResult.raw(), isAlwaysTerminating))) return zv::Val();

		zv::Val variableFlow;
		{
			zv::Val classFlow = zv::Val::null();
			if (!classResult.isNull()) {
				classFlow = pt_expression_result_variable_flow(classResult.raw());
				if (UNEXPECTED(classFlow.isUndef())) return zv::Val();
			}
			zv::Val nameFlow = zv::Val::null();
			if (!nameResult.isNull()) {
				nameFlow = pt_expression_result_variable_flow(nameResult.raw());
				if (UNEXPECTED(nameFlow.isUndef())) return zv::Val();
			}
			zv::Val argumentsFlow = pt_variable_flow_builder_arguments(expr, argsResult.raw(), storage);
			if (UNEXPECTED(argumentsFlow.isUndef())) return zv::Val();
			if (mayShortCircuit) {
				// the short-circuited world evaluates none of the arguments
				zv::Args choiceArgv{argumentsFlow.raw(), zv::null};
				zv::Val choice = pt_variable_flow_choice(2, choiceArgv);
				if (UNEXPECTED(choice.isUndef())) return zv::Val();
				argumentsFlow = std::move(choice);
			}
			zv::Val throwsFlow = pt_variable_flow_builder_throws(expr, Z_ARRVAL_P(throwPoints.raw()));
			if (UNEXPECTED(throwsFlow.isUndef())) return zv::Val();
			zv::Val exitFlow = zv::Val::null();
			if (isAlwaysTerminating) {
				exitFlow = pt_variable_flow_exit_stop();
				if (UNEXPECTED(exitFlow.isUndef())) return zv::Val();
			}
			zv::Args flows{classFlow.raw(), nameFlow.raw(), argumentsFlow.raw(), throwsFlow.raw(), exitFlow.raw()};
			variableFlow = pt_variable_flow_sequence(5, flows);
			if (UNEXPECTED(variableFlow.isUndef())) return zv::Val();
		}

		// the call's scope effects (@param-out, invalidations) only happened in the
		// world where the chain did not short-circuit
		if (mayShortCircuit) {
			currentScope = pt_mutating_scope_merge_with(Z_OBJ_P(currentScope.raw()), scopeBeforeArgs.raw());
			if (UNEXPECTED(currentScope.isUndef())) return zv::Val();
		}

		return pt_expression_result_finalize(preliminaryResult.raw(), currentScope.raw(), hasYield, isAlwaysTerminating, throwPoints.raw(), impurePoints.raw(), variableFlow.raw());
	}

	/* the handler entry (Engine.h) */
	static zv::Val processExprEntry(zend_object *handler, zval *nodeScopeResolver, zval *stmt, zval *expr, zval *scope, zval *storage, zval *nodeCallback, zval *context)
	{
		return StaticCallHandler(handler).processExpr(nodeScopeResolver, stmt, expr, scope, storage, nodeCallback, context);
	}

	/* Mirrors resolveReturnType(); $classResult / $nameResult /
	 * $preResolvedAcceptor / $argsResult NULL or IS_NULL for null */
	zv::Val resolveReturnType(zval *reflectionScope, bool nativeTypesPromoted, zval *expr, zval *classResult, zval *nameResult, zval *preResolvedAcceptor, zval *argsResult) const
	{
		if (classResult != NULL && Z_TYPE_P(classResult) == IS_NULL) classResult = NULL;
		if (nameResult != NULL && Z_TYPE_P(nameResult) == IS_NULL) nameResult = NULL;
		zv::Val classType;
		if (classResult != NULL) {
			classType = nativeTypesPromoted ? pt_expression_result_get_native_type(classResult) : pt_expression_result_get_type(classResult);
			if (UNEXPECTED(classType.isUndef())) return zv::Val();
		}
		zval *classTypeOrNull = classType.isUndef() ? NULL : classType.raw();

		zval *name = exprName(expr);
		if (UNEXPECTED(name == NULL)) return zv::Val();
		int nameIsIdentifier = isIdentifier(name);
		if (UNEXPECTED(nameIsIdentifier < 0)) return zv::Val();
		if (nameIsIdentifier) {
			/* $expr->name->toString() */
			zval *methodName = identifierName(name);
			if (UNEXPECTED(methodName == NULL)) return zv::Val();
			zv::Val type = resolveStaticMethod(reflectionScope, nativeTypesPromoted, classTypeOrNull, expr, preResolvedAcceptor, argsResult, methodName, expr);
			if (UNEXPECTED(type.isUndef())) return zv::Val();
			// a call on a nullsafe chain whose class-receiver is currently nullable
			// short-circuits to null - the class result carries whether the chain
			// contains a ?-> (a plain nullable receiver does not propagate).
			zval *class_ = exprClass(expr);
			if (UNEXPECTED(class_ == NULL)) return zv::Val();
			int classIsExpr = isInstanceOf(class_, PT_CLASS_EXPR);
			if (UNEXPECTED(classIsExpr < 0)) return zv::Val();
			if (!classIsExpr || classResult == NULL) return type;
			bool containsNullsafe;
			if (UNEXPECTED(!pt_expression_result_contains_nullsafe(classResult, containsNullsafe))) return zv::Val();
			if (!containsNullsafe || classTypeOrNull == NULL) return type;
			bool containsNull;
			if (UNEXPECTED(!pt_type_combinator_contains_null(classTypeOrNull, containsNull))) return zv::Val();
			if (containsNull) return pt_type_combinator_add_null(type.raw());
			return type;
		}

		// dynamic static call Foo::{$name}(): resolve each possible name on the
		// reflection scope. The asking scope is not narrowed per name, so such
		// calls can be less precise.
		if (nameResult == NULL) {
			pt_throw_should_not_happen();
			return zv::Val();
		}

		zv::Val nameType = nativeTypesPromoted ? pt_expression_result_get_native_type(nameResult) : pt_expression_result_get_type(nameResult);
		if (UNEXPECTED(nameType.isUndef())) return zv::Val();
		zv::Val constantStrings = getConstantStrings(nameType.raw());
		if (UNEXPECTED(constantStrings.isUndef())) return zv::Val();
		zend_long constantStringCount = countOf(constantStrings.raw());
		if (UNEXPECTED(constantStringCount < 0)) return zv::Val();
		if (constantStringCount == 0) return pt_type_new_mixed_type();

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
			zval *class_ = exprClass(expr);
			if (UNEXPECTED(class_ == NULL)) return zv::Val();
			zv::Val identifier = pt_type_new(PT_CLASS_IDENTIFIER, 1, value.raw());
			if (UNEXPECTED(identifier.isUndef())) return zv::Val();
			zval *args = exprArgs(expr);
			if (UNEXPECTED(args == NULL)) return zv::Val();
			zv::Args staticCallArgv{class_, identifier.raw(), args};
			zv::Val staticCall = pt_type_new(PT_CLASS_STATIC_CALL, 3, staticCallArgv);
			if (UNEXPECTED(staticCall.isUndef())) return zv::Val();
			if (UNEXPECTED(Z_TYPE_P(value.raw()) != IS_STRING)) {
				zend_type_error("PHPStan\\Analyser\\ExprHandler\\StaticCallHandler::{closure}(): Argument #1 ($methodName) must be of type string, %s given", zend_zval_value_name(value.raw()));
				return zv::Val();
			}
			zv::Val type = resolveStaticMethod(reflectionScope, nativeTypesPromoted, classTypeOrNull, expr, preResolvedAcceptor, argsResult, value.raw(), staticCall.raw());
			if (UNEXPECTED(type.isUndef())) return zv::Val();
			types.push(std::move(type));
		}
		HashTable *typesTable = types.table();
		return pt_type_combinator_union(zend_hash_num_elements(typesTable), typesTable->arPacked);
	}

	/* Mirrors specifyTypes(); $classResult / $resolvedParametersAcceptor /
	 * $argsResult NULL or IS_NULL for null */
	zv::Val specifyTypes(zval *scope, zval *expr, zval *normalizedExpr, zval *classResult, zval *resolvedParametersAcceptor, zval *walkMethodReflection, zval *context, zval *argsResult) const
	{
		if (classResult != NULL && Z_TYPE_P(classResult) == IS_NULL) classResult = NULL;
		if (resolvedParametersAcceptor != NULL && Z_TYPE_P(resolvedParametersAcceptor) == IS_NULL) resolvedParametersAcceptor = NULL;
		if (argsResult != NULL && Z_TYPE_P(argsResult) == IS_NULL) argsResult = NULL;
		zval null;
		ZVAL_NULL(&null);

		zval *name = exprName(expr);
		if (UNEXPECTED(name == NULL)) return zv::Val();
		int nameIsIdentifier = isIdentifier(name);
		if (UNEXPECTED(nameIsIdentifier < 0)) return zv::Val();
		if (!nameIsIdentifier) return pt_default_narrowing_helper_specify_default_types(OBJ_PROP_NUM(self, slots::defaultNarrowingHelper), expr, context);

		zv::Val calleeType = calleeTypeOf(scope, expr, classResult);
		if (UNEXPECTED(calleeType.isUndef())) return zv::Val();

		zval *methodName = identifierName(name);
		if (UNEXPECTED(methodName == NULL)) return zv::Val();
		if (UNEXPECTED(Z_TYPE_P(methodName) != IS_STRING)) {
			zend_type_error("PHPStan\\Analyser\\MutatingScope::getMethodReflection(): Argument #2 ($methodName) must be of type string, %s given", zend_zval_value_name(methodName));
			return zv::Val();
		}
		zv::Val staticMethodReflection = pt_mutating_scope_get_method_reflection(Z_OBJ_P(scope), calleeType.raw(), Z_STR_P(methodName));
		if (UNEXPECTED(staticMethodReflection.isUndef())) return zv::Val();
		// see MethodCallHandler::specifyTypes() - `$a?->b::c()` short-circuits too,
		// so the branches admitting its null get no callee-derived narrowing
		bool mayHaveBeenSkipped;
		if (UNEXPECTED(!pt_default_narrowing_helper_call_may_have_been_skipped(OBJ_PROP_NUM(self, slots::defaultNarrowingHelper), classResult, calleeType.raw(), context, mayHaveBeenSkipped))) return zv::Val();
		if (!staticMethodReflection.isNull() && !mayHaveBeenSkipped) {
			zval *args = exprArgs(expr);
			if (UNEXPECTED(args == NULL)) return zv::Val();
			zv::Val argsHold = zv::Val::copyOf(zv::Ref(args));

			zv::Val referencedClasses = pt_type_op(Z_OBJ_P(calleeType.raw()), PT_OP_GET_OBJECT_CLASS_NAMES, 0, NULL);
			if (UNEXPECTED(referencedClasses.isUndef())) return zv::Val();
			zend_long referencedClassCount = countOf(referencedClasses.raw());
			if (UNEXPECTED(referencedClassCount < 0)) return zv::Val();
			if (referencedClassCount == 1) {
				zval *className = firstOf(referencedClasses.raw(), &null);
				if (UNEXPECTED(className == NULL)) return zv::Val();
				zval *reflectionProvider = OBJ_PROP_NUM(self, slots::reflectionProvider);
				bool hasClass;
				if (UNEXPECTED(!pt_reflection_provider_has_class(Z_OBJ_P(reflectionProvider), className, hasClass))) return zv::Val();
				if (hasClass) {
					className = firstOf(referencedClasses.raw(), &null);
					if (UNEXPECTED(className == NULL)) return zv::Val();
					zv::Val staticMethodClassReflection = pt_reflection_provider_get_class(Z_OBJ_P(reflectionProvider), className);
					if (UNEXPECTED(staticMethodClassReflection.isUndef())) return zv::Val();
					// runs lazily at narrowing-apply time - prime the storage with the
					// argument results, see MethodCallHandler::specifyTypes()
					pt_primed_storage primed;
					if (UNEXPECTED(!pt_dynamic_return_type_storage_primer_push(OBJ_PROP_NUM(self, slots::storagePrimer), scope, argsResult != NULL ? argsResult : &null, primed))) return zv::Val();
					zv::Val extensionResult = specifyTypesByExtensions(staticMethodClassReflection.raw(), staticMethodReflection.raw(), normalizedExpr, scope, context);
					pt_finally([&]() { (void) pt_dynamic_return_type_storage_primer_pop(primed); });
					if (UNEXPECTED(extensionResult.isUndef() || EG(exception) != NULL)) return zv::Val();
					if (!extensionResult.isNull()) return extensionResult;
				}
			}

			if (zend_hash_num_elements(Z_ARRVAL_P(argsHold.raw())) > 0 && resolvedParametersAcceptor != NULL) {
				zv::Val specifiedTypes = pt_default_narrowing_helper_specify_types_from_conditional_return_type(OBJ_PROP_NUM(self, slots::defaultNarrowingHelper), context, expr, resolvedParametersAcceptor, scope);
				if (UNEXPECTED(specifiedTypes.isUndef())) return zv::Val();
				if (!specifiedTypes.isNull()) return specifiedTypes;
			}

			// see MethodCallHandler::specifyTypes() - `$obj::m()` resolves its callee
			// from the receiver type of the asking scope, so a native-types-promoted
			// ask reads the assertions off the walk's reflection to keep them paired
			// with the walk's acceptor
			bool nativeTypesPromoted;
			if (UNEXPECTED(!pt_mutating_scope_native_types_promoted(Z_OBJ_P(scope), nativeTypesPromoted))) return zv::Val();
			zval *assertsReflection = staticMethodReflection.raw();
			if (nativeTypesPromoted && walkMethodReflection != NULL && Z_TYPE_P(walkMethodReflection) != IS_NULL) assertsReflection = walkMethodReflection;
			zv::Val assertions = pt_extended_method_reflection_call(assertsReflection, PT_MR_GET_ASSERTS);
			if (UNEXPECTED(assertions.isUndef())) return zv::Val();
			zv::Val all = assertionsGetAll(assertions.raw());
			if (UNEXPECTED(all.isUndef())) return zv::Val();
			bool hasAsserts = !(Z_TYPE_P(all.raw()) == IS_ARRAY && zend_hash_num_elements(Z_ARRVAL_P(all.raw())) == 0);
			if (hasAsserts && resolvedParametersAcceptor != NULL) {
				zv::Val mapCallback = pt_native_closure(&resolveAssertTypeBody, resolvedParametersAcceptor);
				zv::Val asserts = assertionsMapTypes(assertions.raw(), mapCallback.raw());
				if (UNEXPECTED(asserts.isUndef())) return zv::Val();
				zv::Val specifiedTypes = pt_default_narrowing_helper_specify_types_from_asserts(OBJ_PROP_NUM(self, slots::defaultNarrowingHelper), context, expr, asserts.raw(), resolvedParametersAcceptor, scope);
				if (UNEXPECTED(specifiedTypes.isUndef())) return zv::Val();
				if (!specifiedTypes.isNull()) {
					// see MethodCallHandler::specifyTypes() - the call's own key gets
					// the purity gate, the asserts narrow their subjects regardless
					zv::Val defaultNarrowing = defaultStaticCallNarrowing(scope, expr, classResult, context);
					if (UNEXPECTED(defaultNarrowing.isUndef())) return zv::Val();
					zv::Val united = pt_specified_types_union_with(Z_OBJ_P(specifiedTypes.raw()), defaultNarrowing.raw());
					if (UNEXPECTED(united.isUndef())) return zv::Val();
					zv::Val rootExpr = pt_specified_types_get_root_expr(Z_OBJ_P(specifiedTypes.raw()));
					if (UNEXPECTED(rootExpr.isUndef())) return zv::Val();
					return pt_specified_types_set_root_expr(Z_OBJ_P(united.raw()), rootExpr.raw());
				}
			}
		}

		return defaultStaticCallNarrowing(scope, expr, classResult, context);
	}

	/* Mirrors defaultStaticCallNarrowing(). */
	zv::Val defaultStaticCallNarrowing(zval *scope, zval *expr, zval *classResult, zval *context) const
	{
		bool narrowable;
		if (UNEXPECTED(!isStaticCallNarrowable(scope, expr, classResult, narrowable))) return zv::Val();
		if (!narrowable) {
			zv::Val empty = pt_specified_types_new();
			if (UNEXPECTED(empty.isUndef())) return zv::Val();
			return pt_specified_types_set_root_expr(Z_OBJ_P(empty.raw()), expr);
		}

		return pt_default_narrowing_helper_specify_default_types(OBJ_PROP_NUM(self, slots::defaultNarrowingHelper), expr, context);
	}

	/* Mirrors isStaticCallNarrowable(); $classResult NULL or IS_NULL for
	 * null; false = pending exception */
	[[nodiscard]] bool isStaticCallNarrowable(zval *scope, zval *expr, zval *classResult, bool &out) const
	{
		if (classResult != NULL && Z_TYPE_P(classResult) == IS_NULL) classResult = NULL;
		zval *name = exprName(expr);
		if (UNEXPECTED(name == NULL)) return false;
		int nameIsIdentifier = isIdentifier(name);
		if (UNEXPECTED(nameIsIdentifier < 0)) return false;
		if (!nameIsIdentifier) {
			out = true;
			return true;
		}

		zv::Val calleeType = calleeTypeOf(scope, expr, classResult);
		if (UNEXPECTED(calleeType.isUndef())) return false;

		/* $expr->name->toString() */
		zval *methodName = identifierName(name);
		if (UNEXPECTED(methodName == NULL)) return false;
		if (UNEXPECTED(Z_TYPE_P(methodName) != IS_STRING)) {
			zend_type_error("PHPStan\\Analyser\\MutatingScope::getMethodReflection(): Argument #2 ($methodName) must be of type string, %s given", zend_zval_value_name(methodName));
			return false;
		}
		zv::Val methodReflection = pt_mutating_scope_get_method_reflection(Z_OBJ_P(scope), calleeType.raw(), Z_STR_P(methodName));
		if (UNEXPECTED(methodReflection.isUndef())) return false;
		if (methodReflection.isNull()) {
			out = false;
			return true;
		}

		zend_long hasSideEffects = reflectionTrinary(methodReflection.raw(), PT_MR_HAS_SIDE_EFFECTS);
		if (UNEXPECTED(hasSideEffects < 0)) return false;
		if (hasSideEffects == PT_TRI_YES) {
			out = false;
			return true;
		}

		out = zend_is_true(OBJ_PROP_NUM(self, slots::rememberPossiblyImpureFunctionValues)) || hasSideEffects == PT_TRI_NO;
		return true;
	}

	/* Mirrors resolveTypeByNameWithLateStaticBinding(). */
	static zv::Val resolveTypeByNameWithLateStaticBinding(zval *scope, zval *class_, zval *methodName)
	{
		zv::Val classType = pt_mutating_scope_resolve_type_by_name(Z_OBJ_P(scope), Z_OBJ_P(class_));
		if (UNEXPECTED(classType.isUndef())) return zv::Val();

		if (!classType.ref().instanceOf(pt_ce_static_type)) return classType;
		zv::Val lowerName = nameToLowerString(class_);
		if (UNEXPECTED(lowerName.isUndef())) return zv::Val();
		if (
			Z_TYPE_P(lowerName.raw()) == IS_STRING
			&& (
				zend_string_equals_literal(Z_STR_P(lowerName.raw()), "self")
				|| zend_string_equals_literal(Z_STR_P(lowerName.raw()), "static")
				|| zend_string_equals_literal(Z_STR_P(lowerName.raw()), "parent")
			)
		) {
			return classType;
		}

		if (UNEXPECTED(Z_TYPE_P(methodName) != IS_STRING)) {
			zend_type_error("PHPStan\\Analyser\\MutatingScope::getMethodReflection(): Argument #2 ($methodName) must be of type string, %s given", zend_zval_value_name(methodName));
			return zv::Val();
		}
		zv::Val methodReflectionCandidate = pt_mutating_scope_get_method_reflection(Z_OBJ_P(scope), classType.raw(), Z_STR_P(methodName));
		if (UNEXPECTED(methodReflectionCandidate.isUndef())) return zv::Val();
		if (methodReflectionCandidate.isNull()) return classType;
		bool isStatic;
		if (UNEXPECTED(!reflectionIsStatic(methodReflectionCandidate.raw(), isStatic))) return zv::Val();
		if (isStatic) {
			return getStaticObjectType(classType.raw());
		}

		return classType;
	}

private:
	zend_object *self;

	/* $expr->class instanceof Name ? $scope->resolveTypeByName($expr->class)
	 * : $classResult->getTypeOnScope($scope, $scope->nativeTypesPromoted)
	 * (ShouldNotHappenException for a missing class result) */
	static zv::Val calleeTypeOf(zval *scope, zval *expr, zval *classResult)
	{
		zval *class_ = exprClass(expr);
		if (UNEXPECTED(class_ == NULL)) return zv::Val();
		int classIsName = isInstanceOf(class_, PT_CLASS_NAME);
		if (UNEXPECTED(classIsName < 0)) return zv::Val();
		if (classIsName) return pt_mutating_scope_resolve_type_by_name(Z_OBJ_P(scope), Z_OBJ_P(class_));

		// the class expr was processed during processExpr; its result is
		// always captured for an expression class
		if (classResult == NULL) {
			pt_throw_should_not_happen();
			return zv::Val();
		}
		bool nativeTypesPromoted;
		if (UNEXPECTED(!pt_mutating_scope_native_types_promoted(Z_OBJ_P(scope), nativeTypesPromoted))) return zv::Val();
		return pt_expression_result_get_type_on_scope(classResult, scope, nativeTypesPromoted);
	}

	/* $resolveStaticMethod($methodName, $staticCall) of resolveReturnType()
	 * ($classType NULL for null) */
	zv::Val resolveStaticMethod(zval *reflectionScope, bool nativeTypesPromoted, zval *classType, zval *expr, zval *preResolvedAcceptor, zval *argsResult, zval *methodName, zval *staticCall) const
	{
		zval *class_ = exprClass(expr);
		if (UNEXPECTED(class_ == NULL)) return zv::Val();
		int classIsName = isInstanceOf(class_, PT_CLASS_NAME);
		if (UNEXPECTED(classIsName < 0)) return zv::Val();
		if (nativeTypesPromoted) {
			zv::Val staticMethodCalledOnType;
			if (classIsName) {
				staticMethodCalledOnType = resolveTypeByNameWithLateStaticBinding(reflectionScope, class_, methodName);
				if (UNEXPECTED(staticMethodCalledOnType.isUndef())) return zv::Val();
			} else {
				if (classType == NULL) {
					pt_throw_should_not_happen();
					return zv::Val();
				}
				staticMethodCalledOnType = zv::Val::copyOf(zv::Ref(classType));
			}
			zv::Val methodReflection = pt_mutating_scope_get_method_reflection(Z_OBJ_P(reflectionScope), staticMethodCalledOnType.raw(), Z_STR_P(methodName));
			if (UNEXPECTED(methodReflection.isUndef())) return zv::Val();
			if (methodReflection.isNull()) return pt_type_new_error_type();

			zv::Val variants = pt_extended_method_reflection_call(methodReflection.raw(), PT_MR_GET_VARIANTS);
			if (UNEXPECTED(variants.isUndef())) return zv::Val();
			zv::Val acceptor = combineAcceptors(variants.raw());
			if (UNEXPECTED(acceptor.isUndef())) return zv::Val();
			return acceptorNativeReturnType(acceptor.raw());
		}

		zv::Val staticMethodCalledOnType;
		if (classIsName) {
			staticMethodCalledOnType = resolveTypeByNameWithLateStaticBinding(reflectionScope, class_, methodName);
			if (UNEXPECTED(staticMethodCalledOnType.isUndef())) return zv::Val();
		} else {
			if (classType == NULL) {
				pt_throw_should_not_happen();
				return zv::Val();
			}
			zv::Val withoutNull = pt_type_combinator_remove_null(classType);
			if (UNEXPECTED(withoutNull.isUndef())) return zv::Val();
			staticMethodCalledOnType = getObjectTypeOrClassStringObjectType(withoutNull.raw());
			if (UNEXPECTED(staticMethodCalledOnType.isUndef())) return zv::Val();
		}

		zv::Val type = pt_method_call_return_type_helper_method_call_return_type(OBJ_PROP_NUM(self, slots::methodCallReturnTypeHelper), reflectionScope, staticMethodCalledOnType.raw(), methodName, staticCall, preResolvedAcceptor, argsResult);
		if (UNEXPECTED(type.isUndef())) return zv::Val();
		if (type.isNull()) return pt_type_new_error_type();
		return type;
	}

	/* the extensions loop of specifyTypes() inside its try: the first
	 * supporting extension's answer, null when none supports the call */
	zv::Val specifyTypesByExtensions(zval *staticMethodClassReflection, zval *staticMethodReflection, zval *normalizedExpr, zval *scope, zval *context) const
	{
		zv::Val className = pt_class_reflection_get_name(Z_OBJ_P(staticMethodClassReflection));
		if (UNEXPECTED(className.isUndef())) return zv::Val();
		zv::Val extensions = pt_type_specifier_get_static_method_type_specifying_extensions_for_class(Z_OBJ_P(OBJ_PROP_NUM(self, slots::typeSpecifier)), className.raw());
		if (UNEXPECTED(extensions.isUndef())) return zv::Val();
		if (UNEXPECTED(Z_TYPE_P(extensions.raw()) != IS_ARRAY)) {
			zend_error(E_WARNING, "foreach() argument must be of type array|object, %s given", zend_zval_value_name(extensions.raw()));
			if (UNEXPECTED(EG(exception))) return zv::Val();
			return zv::Val::null();
		}
		for (auto entry : zv::TableRef(Z_ARRVAL_P(extensions.raw()))) {
			zval *extension = entry.value().deref().raw();
			if (UNEXPECTED(Z_TYPE_P(extension) != IS_OBJECT)) {
				zend_throw_error(NULL, "Call to a member function isStaticMethodSupported() on %s", zend_zval_value_name(extension));
				return zv::Val();
			}
			bool supported;
			if (UNEXPECTED(!extensionIsStaticMethodSupported(extension, staticMethodReflection, normalizedExpr, context, supported))) return zv::Val();
			if (!supported) continue;

			return extensionSpecifyTypes(extension, staticMethodReflection, normalizedExpr, scope, context);
		}
		return zv::Val::null();
	}

	/* the promoted-properties loop of processExpr(): $thisType =
	 * $scope->getVariableType('this'), then assignInitializedProperty() for
	 * each public / protected property the constructor's declaring class
	 * promotes itself */
	static zv::Val initializePromotedProperties(zval *scope, zval *methodReflection)
	{
		zv::Val currentScope = zv::Val::copyOf(zv::Ref(scope));
		zv::Val thisType = pt_mutating_scope_get_variable_type(Z_OBJ_P(scope), pt_sch_this);
		if (UNEXPECTED(thisType.isUndef())) return zv::Val();
		zv::Val methodClassReflection = pt_extended_method_reflection_call(methodReflection, PT_MR_GET_DECLARING_CLASS);
		if (UNEXPECTED(methodClassReflection.isUndef())) return zv::Val();
		zv::Val nativeReflection = pt_class_reflection_get_native_reflection(Z_OBJ_P(methodClassReflection.raw()));
		if (UNEXPECTED(nativeReflection.isUndef())) return zv::Val();
		if (UNEXPECTED(!nativeReflection.ref().isObject())) {
			zend_throw_error(NULL, "Call to a member function getProperties() on %s", zend_zval_value_name(nativeReflection.raw()));
			return zv::Val();
		}
		/* ReflectionProperty::IS_PUBLIC | ReflectionProperty::IS_PROTECTED */
		zv::Val properties = nativeReflectionGetProperties(nativeReflection.raw(), ZEND_ACC_PUBLIC | ZEND_ACC_PROTECTED);
		if (UNEXPECTED(properties.isUndef())) return zv::Val();
		if (UNEXPECTED(Z_TYPE_P(properties.raw()) != IS_ARRAY)) {
			zend_error(E_WARNING, "foreach() argument must be of type array|object, %s given", zend_zval_value_name(properties.raw()));
			if (UNEXPECTED(EG(exception))) return zv::Val();
			return currentScope;
		}
		for (zv::ArrayEntry entry : zv::ArrRef(properties.raw())) {
			zval *property = entry.value().deref().raw();
			if (UNEXPECTED(Z_TYPE_P(property) != IS_OBJECT)) {
				zend_throw_error(NULL, "Call to a member function isPromoted() on %s", zend_zval_value_name(property));
				return zv::Val();
			}
			zv::Val isPromoted = propertyIsPromoted(property);
			if (UNEXPECTED(isPromoted.isUndef())) return zv::Val();
			if (!zend_is_true(isPromoted.raw())) continue;
			zv::Val declaringClass = propertyGetDeclaringClass(property);
			if (UNEXPECTED(declaringClass.isUndef())) return zv::Val();
			if (UNEXPECTED(!declaringClass.ref().isObject())) {
				zend_throw_error(NULL, "Call to a member function getName() on %s", zend_zval_value_name(declaringClass.raw()));
				return zv::Val();
			}
			zv::Val declaringClassName = propertyDeclaringClassGetName(declaringClass.raw());
			if (UNEXPECTED(declaringClassName.isUndef())) return zv::Val();
			zv::Val methodClassName = pt_class_reflection_get_name(Z_OBJ_P(methodClassReflection.raw()));
			if (UNEXPECTED(methodClassName.isUndef())) return zv::Val();
			if (!zend_is_identical(declaringClassName.raw(), methodClassName.raw())) continue;

			zv::Val propertyName = propertyGetName(property);
			if (UNEXPECTED(propertyName.isUndef())) return zv::Val();
			if (UNEXPECTED(!propertyName.ref().isString())) {
				zend_type_error("PHPStan\\Analyser\\MutatingScope::assignInitializedProperty(): Argument #2 ($propertyName) must be of type string, %s given", zend_zval_value_name(propertyName.raw()));
				return zv::Val();
			}
			currentScope = pt_mutating_scope_assign_initialized_property(Z_OBJ_P(currentScope.raw()), thisType.raw(), Z_STR_P(propertyName.raw()));
			if (UNEXPECTED(currentScope.isUndef())) return zv::Val();
		}
		return currentScope;
	}

	/* static function (MutatingScope $boundScope) use ($expr, $storage):
	 * MutatingScope — the Closure::bind() scope factory; captures: $expr,
	 * $storage */
	static void closureBindScopeFactoryBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		if (UNEXPECTED(!requireArguments(argc, 1, "PHPStan\\Analyser\\ExprHandler\\StaticCallHandler::{closure}"))) return;
		zval *boundScope = &argv[0];
		if (UNEXPECTED(Z_TYPE_P(boundScope) != IS_OBJECT || !instanceof_function(Z_OBJCE_P(boundScope), pt_ce_mutating_scope))) {
			zend_type_error("PHPStan\\Analyser\\ExprHandler\\StaticCallHandler::{closure}(): Argument #1 ($boundScope) must be of type PHPStan\\Analyser\\MutatingScope, %s given", zend_zval_value_name(boundScope));
			return;
		}
		zval *expr = &captures[0];
		zval *storage = &captures[1];

		zv::Val thisType = zv::Val::null();
		zv::Val nativeThisType = zv::Val::null();
		zval *args = exprArgs(expr);
		if (UNEXPECTED(args == NULL)) return;
		zval *secondArg = argAt(args, 1);
		if (secondArg != NULL) {
			zval *value = argValue(secondArg);
			if (UNEXPECTED(value == NULL)) return;
			zv::Val argType = readArgType(boundScope, storage, value, false);
			if (UNEXPECTED(argType.isUndef())) return;
			zend_long isNull = pt_type_op_trinary(Z_OBJ_P(argType.raw()), PT_OP_IS_NULL, 0, NULL);
			if (UNEXPECTED(isNull < 0)) return;
			if (isNull != PT_TRI_YES) {
				thisType = std::move(argType);
			}

			zv::Val nativeArgType = readArgType(boundScope, storage, value, true);
			if (UNEXPECTED(nativeArgType.isUndef())) return;
			isNull = pt_type_op_trinary(Z_OBJ_P(nativeArgType.raw()), PT_OP_IS_NULL, 0, NULL);
			if (UNEXPECTED(isNull < 0)) return;
			if (isNull != PT_TRI_YES) {
				nativeThisType = std::move(nativeArgType);
			}
		}
		zv::Val scopeClasses;
		{
			zv::Arr list = zv::Arr::create(1);
			zval staticName;
			ZVAL_INTERNED_STR(&staticName, pt_sch_static);
			list.push(zv::Ref(&staticName));
			scopeClasses = zv::Val(std::move(list));
		}
		zval *thirdArg = argAt(args, 2);
		if (thirdArg != NULL) {
			zval *value = argValue(thirdArg);
			if (UNEXPECTED(value == NULL)) return;
			zv::Val argValueType = readArgType(boundScope, storage, value, false);
			if (UNEXPECTED(argValueType.isUndef())) return;

			zv::Val directClassNames = pt_type_op(Z_OBJ_P(argValueType.raw()), PT_OP_GET_OBJECT_CLASS_NAMES, 0, NULL);
			if (UNEXPECTED(directClassNames.isUndef())) return;
			zend_long directClassCount = countOf(directClassNames.raw());
			if (UNEXPECTED(directClassCount < 0)) return;
			if (directClassCount > 0) {
				scopeClasses = zv::Val::copyOf(zv::Ref(directClassNames.raw()));
				zv::Arr thisTypes = zv::Arr::create((uint32_t) directClassCount);
				for (zv::ArrayEntry entry : zv::ArrRef(directClassNames.raw())) {
					zval *directClassName = entry.value().deref().raw();
					if (UNEXPECTED(Z_TYPE_P(directClassName) != IS_STRING)) {
						zend_type_error("PHPStan\\Type\\ObjectType::__construct(): Argument #1 ($className) must be of type string, %s given", zend_zval_value_name(directClassName));
						return;
					}
					zval objectType;
					if (UNEXPECTED(!pt_object_type_new(&objectType, Z_STR_P(directClassName)))) return;
					thisTypes.push(zv::Val::adopt(objectType));
				}
				HashTable *thisTypesTable = thisTypes.table();
				thisType = pt_type_combinator_union(zend_hash_num_elements(thisTypesTable), thisTypesTable->arPacked);
				if (UNEXPECTED(thisType.isUndef())) return;
			} else {
				thisType = getClassStringObjectType(argValueType.raw());
				if (UNEXPECTED(thisType.isUndef())) return;
				zv::Val classNames = pt_type_op(Z_OBJ_P(thisType.raw()), PT_OP_GET_OBJECT_CLASS_NAMES, 0, NULL);
				if (UNEXPECTED(classNames.isUndef())) return;
				scopeClasses = std::move(classNames);
			}
		}
		zv::Val bound = pt_mutating_scope_enter_closure_bind(Z_OBJ_P(boundScope), thisType.raw(), nativeThisType.raw(), scopeClasses.raw());
		if (UNEXPECTED(bound.isUndef())) return;
		bound.intoReturnValue(return_value);
	}

	/* $readArgType($argValue, $useNativeTypes) of the scope factory: the
	 * stored argument result's type on the bound scope, mixed for a missing
	 * result */
	static zv::Val readArgType(zval *boundScope, zval *storage, zval *value, bool useNativeTypes)
	{
		zv::Val argResult = pt_expression_result_storage_find(storage, value);
		if (UNEXPECTED(argResult.isUndef())) return zv::Val();
		if (argResult.isNull()) return pt_type_new_mixed_type();
		return pt_expression_result_get_type_on_scope(argResult.raw(), boundScope, useNativeTypes);
	}

	/* static fn (bool $nativeTypesPromoted): Type => new NeverType(true) */
	static void earlyTerminatingTypeCallbackBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		(void) captures;
		(void) argv;
		if (UNEXPECTED(!requireArguments(argc, 1, "PHPStan\\Analyser\\ExprHandler\\StaticCallHandler::{closure}"))) return;
		zval never;
		if (UNEXPECTED(!pt_never_type_new(&never, true))) return;
		ZVAL_COPY_VALUE(return_value, &never);
	}

	/* fn (bool $nativeTypesPromoted): Type => $this->resolveReturnType($beforeScope,
	 * $nativeTypesPromoted, $expr, $classResult, $nameResult, $nativeTypesPromoted ?
	 * null : $resolvedParametersAcceptor, $argsResult) — captures: $this,
	 * $beforeScope, $expr, $classResult, $nameResult, $resolvedParametersAcceptor,
	 * $argsResult */
	static void typeCallbackBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		if (UNEXPECTED(!requireArguments(argc, 1, "PHPStan\\Analyser\\ExprHandler\\StaticCallHandler::{closure}"))) return;
		bool nativeTypesPromoted = zend_is_true(&argv[0]);
		zv::Val type = StaticCallHandler(Z_OBJ(captures[0])).resolveReturnType(&captures[1], nativeTypesPromoted, &captures[2], &captures[3], &captures[4], nativeTypesPromoted ? NULL : &captures[5], &captures[6]);
		if (UNEXPECTED(type.isUndef())) return;
		type.intoReturnValue(return_value);
	}

	/* fn (TypeSpecifierContext $specifyContext, bool $nativeTypesPromoted): SpecifiedTypes
	 * => $this->specifyTypes($nativeTypesPromoted ? $beforeScope->doNotTreatPhpDocTypesAsCertain()
	 * : $beforeScope, $expr, $normalizedExpr, $classResult, $resolvedParametersAcceptor,
	 * $walkMethodReflection, $specifyContext, $argsResult) — captures: $this,
	 * $beforeScope, $expr, $normalizedExpr, $classResult, $resolvedParametersAcceptor,
	 * $walkMethodReflection, $argsResult */
	static void specifyTypesCallbackBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		if (UNEXPECTED(!requireArguments(argc, 2, "PHPStan\\Analyser\\ExprHandler\\StaticCallHandler::{closure}"))) return;
		zv::Val promotedScope;
		zval *scope = &captures[1];
		if (zend_is_true(&argv[1])) {
			promotedScope = pt_mutating_scope_do_not_treat_phpdoc_types_as_certain(Z_OBJ(captures[1]));
			if (UNEXPECTED(promotedScope.isUndef())) return;
			scope = promotedScope.raw();
		}
		zv::Val specifiedTypes = StaticCallHandler(Z_OBJ(captures[0])).specifyTypes(scope, &captures[2], &captures[3], &captures[4], &captures[5], &captures[6], &argv[0], &captures[7]);
		if (UNEXPECTED(specifiedTypes.isUndef())) return;
		specifiedTypes.intoReturnValue(return_value);
	}

	/* function (Type $type, TypeSpecifierContext $createContext, bool
	 * $nativeTypesPromoted) use ($expr, $classResult, $beforeScope): SpecifiedTypes
	 * — captures: $this, $expr, $classResult, $beforeScope */
	static void createTypesCallbackBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		if (UNEXPECTED(!requireArguments(argc, 3, "PHPStan\\Analyser\\ExprHandler\\StaticCallHandler::{closure}"))) return;
		StaticCallHandler handler(Z_OBJ(captures[0]));
		zval *expr = &captures[1];
		zval *classResult = &captures[2];
		zv::Val promotedScope;
		zval *s = &captures[3];
		if (zend_is_true(&argv[2])) {
			promotedScope = pt_mutating_scope_do_not_treat_phpdoc_types_as_certain(Z_OBJ(captures[3]));
			if (UNEXPECTED(promotedScope.isUndef())) return;
			s = promotedScope.raw();
		}
		bool narrowable;
		if (UNEXPECTED(!handler.isStaticCallNarrowable(s, expr, classResult, narrowable))) return;
		zv::Val specifiedTypes = narrowable
			? pt_default_narrowing_helper_create_subject_types(OBJ_PROP_NUM(Z_OBJ(captures[0]), slots::defaultNarrowingHelper), s, expr, NULL, &argv[0], &argv[1])
			: pt_specified_types_new();
		if (UNEXPECTED(specifiedTypes.isUndef())) return;
		specifiedTypes.intoReturnValue(return_value);
	}

	/* static fn (Type $type) => TemplateTypeHelper::resolveTemplateTypes($type,
	 * $resolvedParametersAcceptor->getResolvedTemplateTypeMap(), ...,
	 * TemplateTypeVariance::createInvariant()) — captures:
	 * $resolvedParametersAcceptor */
	static void resolveAssertTypeBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		resolveAssertType(captures, argc, argv, return_value, "PHPStan\\Analyser\\ExprHandler\\StaticCallHandler::{closure}");
	}
};

} // namespace phpstanturbo

using phpstanturbo::StaticCallHandler;

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

void pt_register_static_call_handler()
{
	pt_sch_method_call = zend_string_init_interned(PT_LC("methodCall"), 1);
	pt_sch_unknown_method = zend_string_init_interned(PT_LC("call to unknown method"), 1);
	pt_sch_this = zend_string_init_interned(PT_LC("this"), 1);
	pt_sch_static = zend_string_init_interned(PT_LC("static"), 1);

	reg::Class cls("PHPStan\\Analyser\\ExprHandler\\StaticCallHandler");
	ptdecl::StaticCallHandler::declareClass(cls);
	ptdecl::StaticCallHandler::declareProperties(cls);

	/* the real parameter class names: the DI container autowires the
	 * service by reflecting the constructor */
	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *methodCallReturnTypeHelper, *methodThrowPointHelper, *reflectionProvider, *expressionResultFactory, *typeSpecifier, *defaultNarrowingHelper, *storagePrimer, *earlyTerminatingHelper, *argumentsHandler;
		bool rememberPossiblyImpureFunctionValues;
		ZEND_PARSE_PARAMETERS_START(10, 10)
			Z_PARAM_OBJECT(methodCallReturnTypeHelper)
			Z_PARAM_OBJECT(methodThrowPointHelper)
			Z_PARAM_OBJECT(reflectionProvider)
			Z_PARAM_BOOL(rememberPossiblyImpureFunctionValues)
			Z_PARAM_OBJECT(expressionResultFactory)
			Z_PARAM_OBJECT(typeSpecifier)
			Z_PARAM_OBJECT(defaultNarrowingHelper)
			Z_PARAM_OBJECT(storagePrimer)
			Z_PARAM_OBJECT(earlyTerminatingHelper)
			Z_PARAM_OBJECT(argumentsHandler)
		ZEND_PARSE_PARAMETERS_END();
		zval *services[9] = {methodCallReturnTypeHelper, methodThrowPointHelper, reflectionProvider, expressionResultFactory, typeSpecifier, defaultNarrowingHelper, storagePrimer, earlyTerminatingHelper, argumentsHandler};
		StaticCallHandler(Z_OBJ_P(ZEND_THIS)).construct(services, rememberPossiblyImpureFunctionValues);
	});

	cls.method<&StaticCallHandler::supports, zp::Obj>(sigs::supports);

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
		PT_RETURN_VAL(StaticCallHandler(Z_OBJ_P(ZEND_THIS)).processExpr(nodeScopeResolver, stmt, expr, scope, storage, nodeCallback, context));
	});

	cls.method(sigs::resolveReturnType, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *reflectionScope, *expr, *classResult, *nameResult, *preResolvedAcceptor, *argsResult;
		bool nativeTypesPromoted;
		ZEND_PARSE_PARAMETERS_START(7, 7)
			Z_PARAM_OBJECT(reflectionScope)
			Z_PARAM_BOOL(nativeTypesPromoted)
			Z_PARAM_OBJECT(expr)
			Z_PARAM_OBJECT_OR_NULL(classResult)
			Z_PARAM_OBJECT_OR_NULL(nameResult)
			Z_PARAM_OBJECT_OR_NULL(preResolvedAcceptor)
			Z_PARAM_OBJECT_OR_NULL(argsResult)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(StaticCallHandler(Z_OBJ_P(ZEND_THIS)).resolveReturnType(reflectionScope, nativeTypesPromoted, expr, classResult, nameResult, preResolvedAcceptor, argsResult));
	});

	cls.method(sigs::specifyTypes, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *scope, *expr, *normalizedExpr, *classResult, *resolvedParametersAcceptor, *walkMethodReflection, *context, *argsResult = NULL;
		ZEND_PARSE_PARAMETERS_START(7, 8)
			Z_PARAM_OBJECT(scope)
			Z_PARAM_OBJECT(expr)
			Z_PARAM_OBJECT(normalizedExpr)
			Z_PARAM_OBJECT_OR_NULL(classResult)
			Z_PARAM_OBJECT_OR_NULL(resolvedParametersAcceptor)
			Z_PARAM_OBJECT_OR_NULL(walkMethodReflection)
			Z_PARAM_OBJECT(context)
			Z_PARAM_OPTIONAL
			Z_PARAM_OBJECT_OR_NULL(argsResult)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(StaticCallHandler(Z_OBJ_P(ZEND_THIS)).specifyTypes(scope, expr, normalizedExpr, classResult, resolvedParametersAcceptor, walkMethodReflection, context, argsResult));
	});

	cls.method(sigs::defaultStaticCallNarrowing, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *scope, *expr, *classResult = NULL, *context;
		ZEND_PARSE_PARAMETERS_START(4, 4)
			Z_PARAM_OBJECT(scope)
			Z_PARAM_OBJECT(expr)
			Z_PARAM_OBJECT_OR_NULL(classResult)
			Z_PARAM_OBJECT(context)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(StaticCallHandler(Z_OBJ_P(ZEND_THIS)).defaultStaticCallNarrowing(scope, expr, classResult, context));
	});

	cls.method(sigs::isStaticCallNarrowable, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *scope, *expr, *classResult = NULL;
		ZEND_PARSE_PARAMETERS_START(3, 3)
			Z_PARAM_OBJECT(scope)
			Z_PARAM_OBJECT(expr)
			Z_PARAM_OBJECT_OR_NULL(classResult)
		ZEND_PARSE_PARAMETERS_END();
		bool out;
		if (UNEXPECTED(!StaticCallHandler(Z_OBJ_P(ZEND_THIS)).isStaticCallNarrowable(scope, expr, classResult, out))) RETURN_THROWS();
		RETURN_BOOL(out);
	});

	cls.method(sigs::resolveTypeByNameWithLateStaticBinding, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *scope, *class_;
		zend_string *methodName;
		ZEND_PARSE_PARAMETERS_START(3, 3)
			Z_PARAM_OBJECT(scope)
			Z_PARAM_OBJECT(class_)
			Z_PARAM_STR(methodName)
		ZEND_PARSE_PARAMETERS_END();
		zval methodNameZv;
		ZVAL_STR(&methodNameZv, methodName);
		PT_RETURN_VAL(StaticCallHandler::resolveTypeByNameWithLateStaticBinding(scope, class_, &methodNameZv));
	});

	cls.shadow(&pt_ce_static_call_handler);
	pt_expr_handler_entry_register(&pt_ce_static_call_handler, &StaticCallHandler::processExprEntry);
}

/* }}} */
