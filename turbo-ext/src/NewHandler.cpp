/*
 * PHPStanTurbo\NewHandler — native implementation of
 * PHPStan\Analyser\ExprHandler\NewHandler.
 *
 * A DI service (#[AutowiredService]): the constructor keeps the twin's
 * arginfo so Nette autowires it. processExpr() is registered as the class's
 * handler entry (Engine.h). The twin's closures are native closures capturing
 * what the PHP closures capture: the anonymous class's constructor gatherer
 * ($classReflection, &$constructorResult — the by-reference capture a
 * reference zval), the typeCallback ($this, $beforeScope, $expr,
 * $resolvedParametersAcceptor, $classResult, $argsResult), the
 * specifyTypesCallback ($this, $beforeScope, $expr,
 * $resolvedParametersAcceptor), the template-map mapping callback of
 * unresolvedArgumentList() ($site, $frame, $allowUnresolved, $synthetic) and
 * the asserts mapping callback of specifyTypes() ($resolvedParametersAcceptor).
 * The array_map() callback of processExpr() and exactInstantiation()'s
 * $unresolvedArguments closure are created and called in place by the twin,
 * so they are inlined.
 *
 * The analyser collaborators (NodeScopeResolver, MutatingScope,
 * ExpressionResult, ArgsResult, ExpressionContext, StatementContext,
 * StatementResult, VariableFlow(Builder), the method reflections,
 * ClassReflection, SimpleImpurePoint, DynamicReturnTypeStoragePrimer,
 * ImpurePoint, InternalThrowPoint, TemplateArgumentFrame, SpecifiedTypes,
 * DefaultNarrowingHelper, TemplateTypeMap and the Type kernel) are called
 * through their direct entries; the collaborators that stay PHP for now through
 * the cached method sites of CallHandlerSupport.h (ArgumentsHandler,
 * ArgumentsNormalizer, ParametersAcceptorSelector, the parameters acceptors,
 * Assertions) and the block below (the reflection provider's anonymous class
 * reflection, the dynamic return / throw type extensions and their registry,
 * the DI container, PropertyReflectionFinder and the property it finds,
 * MethodReturnStatementsNode, GenericTypeTemplateTraverser, the template
 * types' getters).
 */

#include "support.h"
#include "generated/NewHandler.h"

namespace slots = ptdecl::NewHandler::slot;
namespace sigs = ptdecl::NewHandler::sig;
#include "CallHandlerSupport.h"

zend_class_entry *pt_ce_new_handler = nullptr;

namespace {

using namespace ptcall;

/* {{{ the PHP collaborators only this handler calls (one site each; switch to
 * their direct entries once they are ported) */

pt_method_site pt_nh_get_anonymous_class_reflection_site;
pt_method_site pt_nh_static_return_type_extensions_for_class_site;
pt_method_site pt_nh_return_type_is_static_method_supported_site;
pt_method_site pt_nh_get_type_from_static_method_call_site;
pt_method_site pt_nh_throw_type_is_static_method_supported_site;
pt_method_site pt_nh_get_throw_type_from_static_method_call_site;
pt_method_site pt_nh_container_get_by_type_site;
pt_method_site pt_nh_find_property_reflection_from_node_site;
pt_method_site pt_nh_get_writable_type_site;
pt_method_site pt_nh_node_get_class_reflection_site;
pt_method_site pt_nh_node_get_method_reflection_site;
pt_method_site pt_nh_node_method_reflection_get_name_site;
pt_method_site pt_nh_node_get_statement_result_site;
pt_method_site pt_nh_node_get_impure_points_site;
pt_method_site pt_nh_get_object_type_or_class_string_object_type_site;
pt_method_site pt_nh_name_to_string_site;
pt_method_site pt_nh_template_get_bound_site;
pt_method_site pt_nh_template_get_name_site;
pt_method_site pt_nh_template_get_default_site;
pt_method_site pt_nh_unresolved_get_initial_type_site;
pt_method_site pt_nh_unresolved_with_site_site;

/* $reflectionProvider->getAnonymousClassReflection($classNode, $scope) */
zv::Val getAnonymousClassReflection(zval *reflectionProvider, zval *classNode, zval *scope)
{
	zv::Args argv{classNode, scope};
	return pt_call_method_cached(pt_nh_get_anonymous_class_reflection_site, Z_OBJ_P(reflectionProvider), PT_LC("getanonymousclassreflection"), 2, argv);
}

/* $registry->getDynamicStaticMethodReturnTypeExtensionsForClass($className) */
zv::Val dynamicStaticMethodReturnTypeExtensionsForClass(zval *registry, zval *className)
{
	return pt_call_method_cached(pt_nh_static_return_type_extensions_for_class_site, Z_OBJ_P(registry), PT_LC("getdynamicstaticmethodreturntypeextensionsforclass"), 1, className);
}

/* $extension->isStaticMethodSupported($methodReflection) (a return type /
 * a throw type extension); false = pending exception */
[[nodiscard]] bool isStaticMethodSupported(pt_method_site &site, zval *extension, zval *methodReflection, bool &out)
{
	zv::Val result = pt_call_method_cached(site, Z_OBJ_P(extension), PT_LC("isstaticmethodsupported"), 1, methodReflection);
	if (UNEXPECTED(result.isUndef())) return false;
	out = zend_is_true(result.raw());
	return true;
}

/* $extension->getTypeFromStaticMethodCall($methodReflection, $methodCall, $scope) */
zv::Val getTypeFromStaticMethodCall(zval *extension, zval *methodReflection, zval *methodCall, zval *scope)
{
	zv::Args argv{methodReflection, methodCall, scope};
	return pt_call_method_cached(pt_nh_get_type_from_static_method_call_site, Z_OBJ_P(extension), PT_LC("gettypefromstaticmethodcall"), 3, argv);
}

/* $extension->getThrowTypeFromStaticMethodCall($methodReflection, $methodCall, $scope) */
zv::Val getThrowTypeFromStaticMethodCall(zval *extension, zval *methodReflection, zval *methodCall, zval *scope)
{
	zv::Args argv{methodReflection, methodCall, scope};
	return pt_call_method_cached(pt_nh_get_throw_type_from_static_method_call_site, Z_OBJ_P(extension), PT_LC("getthrowtypefromstaticmethodcall"), 3, argv);
}

/* $this->container->getByType(NodeScopeResolver::class) */
zend_string *pt_nh_node_scope_resolver_class = nullptr;

zv::Val nodeScopeResolverFromContainer(zval *container)
{
	zval className;
	ZVAL_INTERNED_STR(&className, pt_nh_node_scope_resolver_class);
	return pt_call_method_cached(pt_nh_container_get_by_type_site, Z_OBJ_P(container), PT_LC("getbytype"), 1, &className);
}

/* $propertyReflectionFinder->findPropertyReflectionFromNode($node, $scope) */
zv::Val findPropertyReflectionFromNode(zval *finder, zval *node, zval *scope)
{
	zv::Args argv{node, scope};
	return pt_call_method_cached(pt_nh_find_property_reflection_from_node_site, Z_OBJ_P(finder), PT_LC("findpropertyreflectionfromnode"), 2, argv);
}

/* $foundProperty->getWritableType() */
zv::Val getWritableType(zval *foundProperty)
{
	return pt_call_method_cached(pt_nh_get_writable_type_site, Z_OBJ_P(foundProperty), PT_LC("getwritabletype"), 0, NULL);
}

/* MethodReturnStatementsNode's getClassReflection() / getMethodReflection()
 * (and its getName()) / getStatementResult() / getImpurePoints() */
zv::Val nodeGetClassReflection(zval *node)
{
	return pt_call_method_cached(pt_nh_node_get_class_reflection_site, Z_OBJ_P(node), PT_LC("getclassreflection"), 0, NULL);
}

zv::Val nodeGetMethodReflection(zval *node)
{
	return pt_call_method_cached(pt_nh_node_get_method_reflection_site, Z_OBJ_P(node), PT_LC("getmethodreflection"), 0, NULL);
}

zv::Val nodeMethodReflectionGetName(zval *methodReflection)
{
	return pt_call_method_cached(pt_nh_node_method_reflection_get_name_site, Z_OBJ_P(methodReflection), PT_LC("getname"), 0, NULL);
}

zv::Val nodeGetStatementResult(zval *node)
{
	return pt_call_method_cached(pt_nh_node_get_statement_result_site, Z_OBJ_P(node), PT_LC("getstatementresult"), 0, NULL);
}

zv::Val nodeGetImpurePoints(zval *node)
{
	return pt_call_method_cached(pt_nh_node_get_impure_points_site, Z_OBJ_P(node), PT_LC("getimpurepoints"), 0, NULL);
}

/* $type->getObjectTypeOrClassStringObjectType() */
zv::Val getObjectTypeOrClassStringObjectType(zval *type)
{
	return pt_call_method_cached(pt_nh_get_object_type_or_class_string_object_type_site, Z_OBJ_P(type), PT_LC("getobjecttypeorclassstringobjecttype"), 0, NULL);
}

/* $name->toString() */
zv::Val nameToString(zval *name)
{
	return pt_call_method_cached(pt_nh_name_to_string_site, Z_OBJ_P(name), PT_LC("tostring"), 0, NULL);
}

/* $templateType->getBound() / ->getName() / ->getDefault() */
zv::Val templateGetBound(zval *templateType)
{
	return pt_call_method_cached(pt_nh_template_get_bound_site, Z_OBJ_P(templateType), PT_LC("getbound"), 0, NULL);
}

zv::Val templateGetName(zval *templateType)
{
	return pt_call_method_cached(pt_nh_template_get_name_site, Z_OBJ_P(templateType), PT_LC("getname"), 0, NULL);
}

zv::Val templateGetDefault(zval *templateType)
{
	return pt_call_method_cached(pt_nh_template_get_default_site, Z_OBJ_P(templateType), PT_LC("getdefault"), 0, NULL);
}

/* $unresolved->getInitialType() / ->withSite($site, $template) */
zv::Val unresolvedGetInitialType(zval *unresolved)
{
	return pt_call_method_cached(pt_nh_unresolved_get_initial_type_site, Z_OBJ_P(unresolved), PT_LC("getinitialtype"), 0, NULL);
}

zv::Val unresolvedWithSite(zval *unresolved, zval *site, zval *templateType)
{
	zv::Args argv{site, templateType};
	return pt_call_method_cached(pt_nh_unresolved_with_site_site, Z_OBJ_P(unresolved), PT_LC("withsite"), 2, argv);
}

/* }}} */

/* {{{ the PhpParser nodes' properties */

pt_property_site pt_nh_class_site;
pt_property_site pt_nh_args_site;
pt_property_site pt_nh_static_call_args_site;
pt_property_site pt_nh_name_name_site;

zval *exprClass(zval *expr) { return nodeProperty(pt_nh_class_site, expr, PT_LC("class")); }
/* $expr->getArgs() of a New_ that is not a first-class callable (the handler
 * never sees one), and $expr->args: its $args */
zval *exprArgs(zval *expr) { return nodeProperty(pt_nh_args_site, expr, PT_LC("args")); }
/* $methodCall->getArgs() of the synthetic constructor StaticCall (its own
 * site: the New_ site stays monomorphic) */
zval *staticCallArgs(zval *staticCall) { return nodeProperty(pt_nh_static_call_args_site, staticCall, PT_LC("args")); }

/* $name->toLowerString() === $literal: php-parser's strtolower($this->name),
 * ASCII-only like strtolower() since PHP 8.2, compared without the copy;
 * -1 = pending exception */
int nameLowerEquals(zval *name, const char *literal, size_t len)
{
	zval *value = nodeProperty(pt_nh_name_name_site, name, PT_LC("name"));
	if (UNEXPECTED(value == NULL)) return -1;
	if (UNEXPECTED(Z_TYPE_P(value) != IS_STRING)) {
		zend_type_error("strtolower(): Argument #1 ($string) must be of type string, %s given", zend_zval_value_name(value));
		return -1;
	}
	return ZSTR_LEN(Z_STR_P(value)) == len && zend_binary_strcasecmp(ZSTR_VAL(Z_STR_P(value)), len, literal, len) == 0 ? 1 : 0;
}

/* }}} */

/* {{{ small value helpers */

/* the twin's literals, permanent interned strings (module startup) */
zend_string *pt_nh_new = nullptr;
zend_string *pt_nh_instantiation_of_unknown_class = nullptr;
zend_string *pt_nh_throwable = nullptr;
zend_string *pt_nh_synthetic_site_attribute = nullptr;

/* $list[0], with the engine's warning (and null) for a missing key; NULL =
 * pending exception */
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

/* a string argument the twin's typed parameter would check; false = TypeError raised */
[[nodiscard]] bool requireString(zval *value, const char *function, const char *parameter)
{
	if (EXPECTED(Z_TYPE_P(value) == IS_STRING)) return true;
	zend_type_error("%s(): Argument #1 ($%s) must be of type string, %s given", function, parameter, zend_zval_value_name(value));
	return false;
}

/* sprintf('instantiation of class %s', $classReflection->getDisplayName()) */
zend_string *instantiationDescription(zval *displayName)
{
	zend_string *displayNameString = zval_get_string(displayName);
	smart_str description = {NULL, 0};
	smart_str_appends(&description, "instantiation of class ");
	smart_str_append(&description, displayNameString);
	zend_string_release(displayNameString);
	return smart_str_extract(&description);
}

/* [$templateArgumentFrame::SYNTHETIC_SITE_ATTRIBUTE => true] */
zv::Val syntheticSiteAttributes()
{
	zv::Arr attributes = zv::Arr::create(1);
	zval trueZv = {};
	ZVAL_TRUE(&trueZv);
	attributes.set(pt_nh_synthetic_site_attribute, zv::Val::copyOf(zv::Ref(&trueZv)));
	return zv::Val(std::move(attributes));
}

/* }}} */

/* the constructor reflection, class reflection and structural acceptor of
 * processConstructorReflection() */
struct ConstructorReflectionParts
{
	zv::Val constructorReflection = zv::Val::null();
	zv::Val classReflection = zv::Val::null();
	zv::Val parametersAcceptor = zv::Val::null();
};

} // namespace

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\ExprHandler\NewHandler; UNDEF = pending
 * exception. */
class NewHandler
{
public:
	explicit NewHandler(zend_object *self) : self(self) {}

	/* the constructor body: the promoted properties */
	void construct(zval **services, bool implicitThrows) const
	{
		static const uint32_t serviceSlots[9] = {
			slots::reflectionProvider, slots::dynamicStaticMethodThrowTypeExtensions, slots::dynamicReturnTypeExtensionRegistry, slots::propertyReflectionFinder,
			slots::expressionResultFactory, slots::defaultNarrowingHelper, slots::storagePrimer, slots::container, slots::argumentsHandler,
		};
		for (uint32_t i = 0; i < 4; i++) {
			pt_write_slot(self, serviceSlots[i], services[i]);
		}
		zval implicitThrowsZv = {};
		ZVAL_BOOL(&implicitThrowsZv, implicitThrows);
		pt_write_slot(self, slots::implicitThrows, &implicitThrowsZv);
		for (uint32_t i = 4; i < 9; i++) {
			pt_write_slot(self, serviceSlots[i], services[i]);
		}
	}

	/* Mirrors supports(); false = pending exception */
	[[nodiscard]] bool supports(zval *expr, bool &out) const
	{
		zend_class_entry *newCe = pt_class(PT_CLASS_NEW);
		if (UNEXPECTED(newCe == NULL)) return false;
		if (!instanceof_function(Z_OBJCE_P(expr), newCe)) {
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
		ConstructorReflectionParts parts;
		bool isDynamic = false;
		bool hasYield = false;
		zv::Val throwPoints = zv::Val(zv::Arr::empty());
		zv::Val impurePoints = zv::Val(zv::Arr::empty());
		bool isAlwaysTerminating = false;
		zv::Val normalizedExpr = zv::Val::copyOf(zv::Ref(expr));
		zv::Val className = zv::Val::null();
		zv::Val classResult = zv::Val::null();
		/* -1 = null */
		int deferredConstructorImpureIsDynamic = -1;
		zv::Val currentScope = zv::Val::copyOf(zv::Ref(scope));
		zv::Val hold;
		zval *borrowed;

		zval *class_ = exprClass(expr);
		if (UNEXPECTED(class_ == NULL)) return zv::Val();
		int classIsName = isInstanceOf(class_, PT_CLASS_NAME);
		if (UNEXPECTED(classIsName < 0)) return zv::Val();
		int classIsClassStmt = classIsName ? 0 : isInstanceOf(class_, PT_CLASS_CLASS_STMT);
		if (UNEXPECTED(classIsClassStmt < 0)) return zv::Val();
		if (classIsName) {
			className = pt_mutating_scope_resolve_name(Z_OBJ_P(scope), Z_OBJ_P(class_));
			if (UNEXPECTED(className.isUndef())) return zv::Val();

			if (UNEXPECTED(!processConstructorReflection(className.raw(), expr, parts))) return zv::Val();
			deferredConstructorImpureIsDynamic = 0;

			if (!parts.parametersAcceptor.isNull()) {
				zv::Val reordered = reorderNewArguments(parts.parametersAcceptor.raw(), expr);
				if (UNEXPECTED(reordered.isUndef())) return zv::Val();
				if (!reordered.isNull()) normalizedExpr = std::move(reordered);
			}
		} else if (classIsClassStmt) {
			// populates $expr->class->name
			parts.classReflection = getAnonymousClassReflection(OBJ_PROP_NUM(self, slots::reflectionProvider), class_, scope);
			if (UNEXPECTED(parts.classReflection.isUndef())) return zv::Val();
			if (UNEXPECTED(!parts.classReflection.ref().isObject())) {
				zend_throw_error(NULL, "Call to a member function hasConstructor() on %s", zend_zval_value_name(parts.classReflection.raw()));
				return zv::Val();
			}
			bool hasConstructor;
			if (UNEXPECTED(!pt_class_reflection_has_constructor(Z_OBJ_P(parts.classReflection.raw()), hasConstructor))) return zv::Val();
			if (hasConstructor) {
				parts.constructorReflection = pt_class_reflection_get_constructor(Z_OBJ_P(parts.classReflection.raw()));
				if (UNEXPECTED(parts.constructorReflection.isUndef())) return zv::Val();
				// A structural acceptor (names/positions/variadic) drives argument
				// normalization and the throw point - generics are resolved
				// type-driven by processArgs() into $resolvedParametersAcceptor.
				parts.parametersAcceptor = combinedConstructorAcceptor(exprArgs(expr), parts.constructorReflection.raw());
				if (UNEXPECTED(parts.parametersAcceptor.isUndef())) return zv::Val();

				zv::Val declaringClass = pt_extended_method_reflection_call(parts.constructorReflection.raw(), PT_MR_GET_DECLARING_CLASS);
				if (UNEXPECTED(declaringClass.isUndef())) return zv::Val();
				zv::Val declaringClassName = pt_class_reflection_get_name(Z_OBJ_P(declaringClass.raw()));
				if (UNEXPECTED(declaringClassName.isUndef())) return zv::Val();
				zv::Val anonymousClassName = pt_class_reflection_get_name(Z_OBJ_P(parts.classReflection.raw()));
				if (UNEXPECTED(anonymousClassName.isUndef())) return zv::Val();
				if (zend_is_identical(declaringClassName.raw(), anonymousClassName.raw())) {
					zv::Val constructorResult;
					{
						zval reference;
						ZVAL_NEW_REF(&reference, &null);
						constructorResult = zv::Val::adopt(reference);
					}
					zv::Val gatherer;
					{
						zval captures[2];
						ZVAL_COPY_VALUE(&captures[0], parts.classReflection.raw());
						ZVAL_COPY_VALUE(&captures[1], constructorResult.raw());
						gatherer = pt_native_closure_new(&constructorGathererBody, 2, captures, 1u << 1);
					}
					if (UNEXPECTED(!pt_node_scope_resolver_push_node_gatherer(nodeScopeResolver, gatherer.raw()))) return zv::Val();
					gatherer.release();
					(void) processAnonymousClassStatement(nodeScopeResolver, class_, scope, storage, nodeCallback, context);
					pt_finally([&]() { (void) pt_node_scope_resolver_pop_node_gatherer(nodeScopeResolver); });
					if (UNEXPECTED(EG(exception) != NULL)) return zv::Val();

					zval *found = Z_REFVAL_P(constructorResult.raw());
					if (Z_TYPE_P(found) != IS_NULL) {
						zv::Val node = zv::Val::copyOf(zv::Ref(found));
						zv::Val statementResult = nodeGetStatementResult(node.raw());
						if (UNEXPECTED(statementResult.isUndef())) return zv::Val();
						borrowed = pt_statement_result_throw_points(statementResult.raw(), hold);
						if (UNEXPECTED(borrowed == NULL)) return zv::Val();
						zv::Val publicThrowPoints = zv::Val::copyOf(zv::Ref(borrowed));
						if (UNEXPECTED(Z_TYPE_P(publicThrowPoints.raw()) != IS_ARRAY)) {
							zend_type_error("array_map(): Argument #2 ($array) must be of type array, %s given", zend_zval_value_name(publicThrowPoints.raw()));
							return zv::Val();
						}
						zv::Arr mapped = zv::Arr::create(zend_hash_num_elements(Z_ARRVAL_P(publicThrowPoints.raw())));
						for (zv::ArrayEntry entry : zv::ArrRef(publicThrowPoints.raw())) {
							zv::Val internal = pt_internal_throw_point_create_from_public(entry.value().deref().raw(), scope);
							if (UNEXPECTED(internal.isUndef())) return zv::Val();
							if (entry.hasStringKey()) {
								mapped.set(entry.stringKey(), std::move(internal));
							} else {
								mapped.separate();
								zval item = internal.take();
								zend_hash_index_update(mapped.table(), entry.indexKey(), &item);
							}
						}
						throwPoints = zv::Val(std::move(mapped));
						impurePoints = nodeGetImpurePoints(node.raw());
						if (UNEXPECTED(impurePoints.isUndef())) return zv::Val();
					}
				} else {
					if (UNEXPECTED(!processAnonymousClassStatement(nodeScopeResolver, class_, scope, storage, nodeCallback, context))) return zv::Val();
					zend_long hasSideEffects = pt_extended_method_reflection_trinary(parts.constructorReflection.raw(), PT_MR_HAS_SIDE_EFFECTS);
					if (UNEXPECTED(hasSideEffects < 0)) return zv::Val();
					if (hasSideEffects != PT_TRI_NO) {
						zend_long isPure = pt_extended_method_reflection_trinary(parts.constructorReflection.raw(), PT_MR_IS_PURE);
						if (UNEXPECTED(isPure < 0)) return zv::Val();
						bool certain = isPure == PT_TRI_NO;
						zv::Val point = constructorInstantiationImpurePoint(scope, expr, parts.constructorReflection.raw(), certain);
						if (UNEXPECTED(point.isUndef())) return zv::Val();
						appendTo(impurePoints, std::move(point));
					}
				}
			} else {
				if (UNEXPECTED(!processAnonymousClassStatement(nodeScopeResolver, class_, scope, storage, nodeCallback, context))) return zv::Val();
			}

			if (!parts.parametersAcceptor.isNull()) {
				zv::Val reordered = reorderNewArguments(parts.parametersAcceptor.raw(), expr);
				if (UNEXPECTED(reordered.isUndef())) return zv::Val();
				if (!reordered.isNull()) normalizedExpr = std::move(reordered);
			}
		} else {
			isDynamic = true;

			zv::Val classContext = pt_expression_context_enter_deep(context);
			if (UNEXPECTED(classContext.isUndef())) return zv::Val();
			classResult = pt_node_scope_resolver_process_expr_node(nodeScopeResolver, stmt, class_, scope, storage, nodeCallback, classContext.raw());
			if (UNEXPECTED(classResult.isUndef())) return zv::Val();
			borrowed = pt_expression_result_scope(classResult.raw(), hold);
			if (UNEXPECTED(borrowed == NULL)) return zv::Val();
			currentScope = zv::Val::copyOf(zv::Ref(borrowed));
			if (UNEXPECTED(!pt_expression_result_has_yield(classResult.raw(), hasYield))) return zv::Val();
			borrowed = pt_expression_result_throw_points(classResult.raw(), hold);
			if (UNEXPECTED(borrowed == NULL)) return zv::Val();
			throwPoints = zv::Val::copyOf(zv::Ref(borrowed));
			borrowed = pt_expression_result_impure_points(classResult.raw(), hold);
			if (UNEXPECTED(borrowed == NULL)) return zv::Val();
			impurePoints = zv::Val::copyOf(zv::Ref(borrowed));
			if (UNEXPECTED(!pt_expression_result_is_always_terminating(classResult.raw(), isAlwaysTerminating))) return zv::Val();

			// The instantiated object type derives from the class expression - read
			// its already-processed result rather than asking Scope::getType() for
			// the not-yet-stored New_ node, which would re-enter this handler.
			zv::Val classResultType = pt_expression_result_get_type(classResult.raw());
			if (UNEXPECTED(classResultType.isUndef())) return zv::Val();
			zv::Val objectType = getObjectTypeOrClassStringObjectType(classResultType.raw());
			if (UNEXPECTED(objectType.isUndef())) return zv::Val();
			zv::Val objectClasses = pt_type_op(Z_OBJ_P(objectType.raw()), PT_OP_GET_OBJECT_CLASS_NAMES, 0, NULL);
			if (UNEXPECTED(objectClasses.isUndef())) return zv::Val();
			zend_long objectClassCount = countOf(objectClasses.raw());
			if (UNEXPECTED(objectClassCount < 0)) return zv::Val();
			zv::Val additionalThrowPoints;
			if (objectClassCount == 1) {
				zval *objectClass = firstOf(objectClasses.raw(), &null);
				if (UNEXPECTED(objectClass == NULL)) return zv::Val();
				zv::Val objectClassName = pt_type_new(PT_CLASS_NAME, 1, objectClass);
				if (UNEXPECTED(objectClassName.isUndef())) return zv::Val();
				zv::Val attributes = syntheticSiteAttributes();
				zv::Arr noArgs = zv::Arr::empty();
				zv::Args newArgv{objectClassName.raw(), noArgs.raw(), attributes.raw()};
				zv::Val syntheticNew = pt_type_new(PT_CLASS_NEW, 3, newArgv);
				if (UNEXPECTED(syntheticNew.isUndef())) return zv::Val();
				zv::Val noopNodeCallback = pt_type_new(PT_CLASS_NOOP_NODE_CALLBACK, 0, NULL);
				if (UNEXPECTED(noopNodeCallback.isUndef())) return zv::Val();
				zv::Val deepContext = pt_expression_context_enter_deep(context);
				if (UNEXPECTED(deepContext.isUndef())) return zv::Val();
				zv::Val syntheticContext = pt_expression_context_without_template_argument_resolution(deepContext.raw());
				if (UNEXPECTED(syntheticContext.isUndef())) return zv::Val();
				zv::Val objectExprResult = pt_node_scope_resolver_process_expr_node(nodeScopeResolver, stmt, syntheticNew.raw(), currentScope.raw(), storage, noopNodeCallback.raw(), syntheticContext.raw());
				if (UNEXPECTED(objectExprResult.isUndef())) return zv::Val();
				objectClass = firstOf(objectClasses.raw(), &null);
				if (UNEXPECTED(objectClass == NULL)) return zv::Val();
				className = zv::Val::copyOf(zv::Ref(objectClass));
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

			if (UNEXPECTED(Z_TYPE_P(throwPoints.raw()) != IS_ARRAY || Z_TYPE_P(additionalThrowPoints.raw()) != IS_ARRAY)) {
				zend_type_error("array_merge(): Argument #%d must be of type array, %s given", Z_TYPE_P(throwPoints.raw()) != IS_ARRAY ? 1 : 2, zend_zval_value_name(Z_TYPE_P(throwPoints.raw()) != IS_ARRAY ? throwPoints.raw() : additionalThrowPoints.raw()));
				return zv::Val();
			}
			throwPoints = arrayMerge(throwPoints.raw(), additionalThrowPoints.raw());

			if (!className.isNull()) {
				if (UNEXPECTED(!processConstructorReflection(className.raw(), expr, parts))) return zv::Val();
				deferredConstructorImpureIsDynamic = 1;
			} else {
				zv::Val point = pt_impure_point_new(currentScope.raw(), expr, pt_nh_new, pt_nh_instantiation_of_unknown_class, false);
				if (UNEXPECTED(point.isUndef())) return zv::Val();
				appendTo(impurePoints, std::move(point));
			}

			if (!parts.parametersAcceptor.isNull()) {
				zv::Val reordered = reorderNewArguments(parts.parametersAcceptor.raw(), expr);
				if (UNEXPECTED(reordered.isUndef())) return zv::Val();
				if (!reordered.isNull()) normalizedExpr = std::move(reordered);
			}
		}

		zv::Val variants;
		zv::Val namedArgumentsVariants;
		if (!parts.constructorReflection.isNull()) {
			variants = pt_extended_method_reflection_call(parts.constructorReflection.raw(), PT_MR_GET_VARIANTS);
			if (UNEXPECTED(variants.isUndef())) return zv::Val();
			namedArgumentsVariants = pt_extended_method_reflection_call(parts.constructorReflection.raw(), PT_MR_GET_NAMED_ARGUMENTS_VARIANTS);
			if (UNEXPECTED(namedArgumentsVariants.isUndef())) return zv::Val();
		} else {
			variants = zv::Val(zv::Arr::empty());
			namedArgumentsVariants = zv::Val::null();
		}
		zv::Val scopeBeforeArgs = zv::Val::copyOf(zv::Ref(currentScope.raw()));
		zv::Val argsResult;
		{
			zv::Args argv{nodeScopeResolver, stmt, parts.constructorReflection.raw(), &null, variants.raw(), namedArgumentsVariants.raw(), normalizedExpr.raw(), currentScope.raw(), storage, nodeCallback, context};
			argsResult = processArgs(OBJ_PROP_NUM(self, slots::argumentsHandler), 11, argv);
		}
		if (UNEXPECTED(argsResult.isUndef())) return zv::Val();
		borrowed = pt_args_result_resolved_parameters_acceptor(argsResult.raw(), hold);
		if (UNEXPECTED(borrowed == NULL)) return zv::Val();
		zv::Val resolvedParametersAcceptor = zv::Val::copyOf(zv::Ref(borrowed));
		borrowed = pt_args_result_scope(argsResult.raw(), hold);
		if (UNEXPECTED(borrowed == NULL)) return zv::Val();
		currentScope = zv::Val::copyOf(zv::Ref(borrowed));
		{
			zv::Args argv{nodeScopeResolver, stmt, expr, normalizedExpr.raw(), currentScope.raw(), storage, context};
			if (UNEXPECTED(!processDroppedArgs(OBJ_PROP_NUM(self, slots::argumentsHandler), argv))) return zv::Val();
		}
		if (!hasYield && UNEXPECTED(!pt_args_result_has_yield(argsResult.raw(), hasYield))) return zv::Val();
		borrowed = pt_args_result_throw_points(argsResult.raw(), hold);
		if (UNEXPECTED(borrowed == NULL)) return zv::Val();
		throwPoints = arrayMerge(throwPoints.raw(), borrowed);
		borrowed = pt_args_result_impure_points(argsResult.raw(), hold);
		if (UNEXPECTED(borrowed == NULL)) return zv::Val();
		if (UNEXPECTED(Z_TYPE_P(impurePoints.raw()) != IS_ARRAY)) {
			zend_type_error("array_merge(): Argument #1 must be of type array, %s given", zend_zval_value_name(impurePoints.raw()));
			return zv::Val();
		}
		impurePoints = arrayMerge(impurePoints.raw(), borrowed);
		if (deferredConstructorImpureIsDynamic >= 0) {
			// created after the args were processed - the pure-unless-callable-
			// is-impure parameters read an argument's type, which is only
			// available once its result is stored
			zv::Val point = constructorImpurePoint(parts.constructorReflection.raw(), parts.classReflection.raw(), parts.parametersAcceptor.raw(), expr, currentScope.raw(), scopeBeforeArgs.raw(), deferredConstructorImpureIsDynamic == 1);
			if (UNEXPECTED(point.isUndef())) return zv::Val();
			if (!point.isNull()) {
				appendTo(impurePoints, std::move(point));
			}
		}
		if (!isAlwaysTerminating && UNEXPECTED(!pt_args_result_is_always_terminating(argsResult.raw(), isAlwaysTerminating))) return zv::Val();

		// The new-expression type is derived from $resolvedParametersAcceptor - the
		// constructor acceptor processArgs() selected from the arg types gathered on
		// the arg-to-arg evolving scope. When null (native-types-promoted, or
		// on-demand / synthetic pricing), resolveReturnType() re-selects a structural
		// acceptor from the args on the asking scope.
		zv::Val typeCallback = pt_native_closure(&typeCallbackBody, self, beforeScope, expr, resolvedParametersAcceptor.raw(), classResult.raw(), argsResult.raw());
		zv::Val specifyTypesCallback = pt_native_closure(&specifyTypesCallbackBody, self, beforeScope, expr, resolvedParametersAcceptor.raw());

		// Store a preliminary result carrying the type/specify callbacks before the
		// throw-point return type is computed: getConstructorThrowPoint() and the
		// exact-instantiation return type resolution can re-enter on demand.
		pt_expression_result_args resultArgs(currentScope.raw(), beforeScope, expr, hasYield, isAlwaysTerminating, NULL, NULL, typeCallback.raw(), specifyTypesCallback.raw());
		zv::Val preliminaryResult = pt_expression_result_create(OBJ_PROP_NUM(self, slots::expressionResultFactory), resultArgs);
		if (UNEXPECTED(preliminaryResult.isUndef())) return zv::Val();
		if (UNEXPECTED(!pt_node_scope_resolver_store_expression_result(nodeScopeResolver, storage, expr, preliminaryResult.raw()))) return zv::Val();

		if (!parts.constructorReflection.isNull() && !parts.parametersAcceptor.isNull()) {
			if (className.isNull()) {
				zv::Val declaringClass = pt_extended_method_reflection_call(parts.constructorReflection.raw(), PT_MR_GET_DECLARING_CLASS);
				if (UNEXPECTED(declaringClass.isUndef())) return zv::Val();
				className = pt_class_reflection_get_name(Z_OBJ_P(declaringClass.raw()));
				if (UNEXPECTED(className.isUndef())) return zv::Val();
			}
			zv::Val fullyQualified = pt_type_new(PT_CLASS_FULLY_QUALIFIED, 1, className.raw());
			if (UNEXPECTED(fullyQualified.isUndef())) return zv::Val();
			zval *args = exprArgs(expr);
			if (UNEXPECTED(args == NULL)) return zv::Val();
			zv::Val argsHold = zv::Val::copyOf(zv::Ref(args));
			zv::Val constructorThrowPoint = getConstructorThrowPoint(parts.constructorReflection.raw(), parts.parametersAcceptor.raw(), expr, fullyQualified.raw(), argsHold.raw(), currentScope.raw(), context);
			if (UNEXPECTED(constructorThrowPoint.isUndef())) return zv::Val();
			if (!constructorThrowPoint.isNull()) {
				appendTo(throwPoints, std::move(constructorThrowPoint));
			}
		} else {
			bool implicit = parts.classReflection.isNull();
			if (!implicit && isDynamic && parts.constructorReflection.isNull()) {
				bool isFinal;
				if (UNEXPECTED(!pt_class_reflection_is_final(Z_OBJ_P(parts.classReflection.raw()), isFinal))) return zv::Val();
				implicit = !isFinal;
			}
			if (implicit) {
				zv::Val throwPoint = pt_internal_throw_point_create_implicit(currentScope.raw(), expr);
				if (UNEXPECTED(throwPoint.isUndef())) return zv::Val();
				appendTo(throwPoints, std::move(throwPoint));
			}
		}

		bool calleeKnown = !parts.classReflection.isNull();
		if (calleeKnown && isDynamic) {
			bool isFinal;
			if (UNEXPECTED(!pt_class_reflection_is_final(Z_OBJ_P(parts.classReflection.raw()), isFinal))) return zv::Val();
			calleeKnown = isFinal;
		}
		bool invalidateVolatile;
		if (!parts.constructorReflection.isNull()) {
			zv::Val declaringClass = pt_extended_method_reflection_call(parts.constructorReflection.raw(), PT_MR_GET_DECLARING_CLASS);
			if (UNEXPECTED(declaringClass.isUndef())) return zv::Val();
			bool builtin;
			if (UNEXPECTED(!pt_class_reflection_is_builtin(Z_OBJ_P(declaringClass.raw()), builtin))) return zv::Val();
			invalidateVolatile = false;
			if (!builtin) {
				zend_long hasSideEffects = pt_extended_method_reflection_trinary(parts.constructorReflection.raw(), PT_MR_HAS_SIDE_EFFECTS);
				if (UNEXPECTED(hasSideEffects < 0)) return zv::Val();
				invalidateVolatile = hasSideEffects != PT_TRI_NO;
			}
		} else {
			invalidateVolatile = !calleeKnown;
		}
		if (invalidateVolatile) {
			currentScope = pt_mutating_scope_invalidate_volatile_expressions(Z_OBJ_P(currentScope.raw()));
			if (UNEXPECTED(currentScope.isUndef())) return zv::Val();
		}

		zv::Val variableFlow;
		{
			zv::Val classFlow = zv::Val::null();
			if (!classResult.isNull()) {
				classFlow = pt_expression_result_variable_flow(classResult.raw());
				if (UNEXPECTED(classFlow.isUndef())) return zv::Val();
			}
			zv::Val argumentsFlow = pt_variable_flow_builder_arguments(expr, argsResult.raw(), storage);
			if (UNEXPECTED(argumentsFlow.isUndef())) return zv::Val();
			zv::Val throwsFlow = pt_variable_flow_builder_throws(expr, Z_ARRVAL_P(throwPoints.raw()));
			if (UNEXPECTED(throwsFlow.isUndef())) return zv::Val();
			zv::Val exitFlow = zv::Val::null();
			if (isAlwaysTerminating) {
				exitFlow = pt_variable_flow_exit_stop();
				if (UNEXPECTED(exitFlow.isUndef())) return zv::Val();
			}
			zv::Args flows{classFlow.raw(), argumentsFlow.raw(), throwsFlow.raw(), exitFlow.raw()};
			variableFlow = pt_variable_flow_sequence(4, flows);
			if (UNEXPECTED(variableFlow.isUndef())) return zv::Val();
		}

		return pt_expression_result_finalize(preliminaryResult.raw(), currentScope.raw(), hasYield, isAlwaysTerminating, throwPoints.raw(), impurePoints.raw(), variableFlow.raw());
	}

	/* the handler entry (Engine.h) */
	static zv::Val processExprEntry(zend_object *handler, zval *nodeScopeResolver, zval *stmt, zval *expr, zval *scope, zval *storage, zval *nodeCallback, zval *context)
	{
		return NewHandler(handler).processExpr(nodeScopeResolver, stmt, expr, scope, storage, nodeCallback, context);
	}

	/* Mirrors processConstructorReflection() into parts (the twin's
	 * three-element array); false = pending exception */
	[[nodiscard]] bool processConstructorReflection(zval *className, zval *expr, ConstructorReflectionParts &parts) const
	{
		if (UNEXPECTED(!requireString(className, "PHPStan\\Analyser\\ExprHandler\\NewHandler::processConstructorReflection", "className"))) return false;
		parts.constructorReflection = zv::Val::null();
		parts.parametersAcceptor = zv::Val::null();
		parts.classReflection = zv::Val::null();

		zval *reflectionProvider = OBJ_PROP_NUM(self, slots::reflectionProvider);
		bool hasClass;
		if (UNEXPECTED(!pt_reflection_provider_has_class(Z_OBJ_P(reflectionProvider), className, hasClass))) return false;
		if (!hasClass) return true;
		parts.classReflection = pt_reflection_provider_get_class(Z_OBJ_P(reflectionProvider), className);
		if (UNEXPECTED(parts.classReflection.isUndef())) return false;
		bool hasConstructor;
		if (UNEXPECTED(!pt_class_reflection_has_constructor(Z_OBJ_P(parts.classReflection.raw()), hasConstructor))) return false;
		if (!hasConstructor) return true;
		parts.constructorReflection = pt_class_reflection_get_constructor(Z_OBJ_P(parts.classReflection.raw()));
		if (UNEXPECTED(parts.constructorReflection.isUndef())) return false;
		// A structural acceptor (names/positions/variadic) drives argument
		// normalization and the throw point - generics are resolved
		// type-driven by processArgs() into $resolvedParametersAcceptor.
		parts.parametersAcceptor = combinedConstructorAcceptor(exprArgs(expr), parts.constructorReflection.raw());
		return !parts.parametersAcceptor.isUndef();
	}

	/* Mirrors getConstructorImpurePoints() as its only element: null for
	 * the twin's [] ($constructorReflection / $classReflection /
	 * $parametersAcceptor NULL or IS_NULL for null) */
	zv::Val constructorImpurePoint(zval *constructorReflection, zval *classReflection, zval *parametersAcceptor, zval *expr, zval *scope, zval *scopeBeforeArgs, bool isDynamic) const
	{
		if (constructorReflection != NULL && Z_TYPE_P(constructorReflection) == IS_NULL) constructorReflection = NULL;
		if (classReflection != NULL && Z_TYPE_P(classReflection) == IS_NULL) classReflection = NULL;
		if (parametersAcceptor != NULL && Z_TYPE_P(parametersAcceptor) == IS_NULL) parametersAcceptor = NULL;
		if (constructorReflection != NULL) {
			if (parametersAcceptor == NULL) {
				pt_throw_should_not_happen();
				return zv::Val();
			}
			zend_long hasSideEffects = pt_extended_method_reflection_trinary(constructorReflection, PT_MR_HAS_SIDE_EFFECTS);
			if (UNEXPECTED(hasSideEffects < 0)) return zv::Val();
			if (hasSideEffects == PT_TRI_NO) return zv::Val::null();

			zend_long isPure = pt_extended_method_reflection_trinary(constructorReflection, PT_MR_IS_PURE);
			if (UNEXPECTED(isPure < 0)) return zv::Val();
			bool certain = isPure == PT_TRI_NO;
			zval *args = exprArgs(expr);
			if (UNEXPECTED(args == NULL)) return zv::Val();
			bool hasVerdict;
			zend_long verdict;
			if (UNEXPECTED(!pt_simple_impure_point_resolve_verdict(parametersAcceptor, scope, args, hasVerdict, verdict))) return zv::Val();
			if (hasVerdict && verdict == PT_TRI_YES) return zv::Val::null();
			if (hasVerdict && verdict == PT_TRI_NO) certain = true;

			return constructorInstantiationImpurePoint(scopeBeforeArgs, expr, constructorReflection, certain);
		}

		if (classReflection == NULL) {
			return pt_impure_point_new(scopeBeforeArgs, expr, pt_nh_new, pt_nh_instantiation_of_unknown_class, false);
		}

		if (isDynamic) {
			bool isFinal;
			if (UNEXPECTED(!pt_class_reflection_is_final(Z_OBJ_P(classReflection), isFinal))) return zv::Val();
			if (!isFinal) {
				zv::Val displayName = pt_class_reflection_get_display_name(Z_OBJ_P(classReflection), true);
				if (UNEXPECTED(displayName.isUndef())) return zv::Val();
				zend_string *description = instantiationDescription(displayName.raw());
				zv::Val point = pt_impure_point_new(scopeBeforeArgs, expr, pt_nh_new, description, false);
				zend_string_release(description);
				return point;
			}
		}

		return zv::Val::null();
	}

	/* Mirrors getConstructorThrowPoint(). */
	zv::Val getConstructorThrowPoint(zval *constructorReflection, zval *parametersAcceptor, zval *new_, zval *className, zval *args, zval *scope, zval *context) const
	{
		zv::Val constructorName = pt_extended_method_reflection_call(constructorReflection, PT_MR_GET_NAME);
		if (UNEXPECTED(constructorName.isUndef())) return zv::Val();
		zv::Args staticCallArgv{className, constructorName.raw(), args};
		zv::Val methodCall = pt_type_new(PT_CLASS_STATIC_CALL, 3, staticCallArgv);
		if (UNEXPECTED(methodCall.isUndef())) return zv::Val();
		zv::Val normalizedMethodCall = reorderStaticCallArguments(parametersAcceptor, methodCall.raw());
		if (UNEXPECTED(normalizedMethodCall.isUndef())) return zv::Val();
		if (!normalizedMethodCall.isNull()) {
			zv::Val extensions = pt_extensions_collection_get_all(Z_OBJ_P(OBJ_PROP_NUM(self, slots::dynamicStaticMethodThrowTypeExtensions)));
			if (UNEXPECTED(extensions.isUndef())) return zv::Val();
			if (UNEXPECTED(Z_TYPE_P(extensions.raw()) != IS_ARRAY)) {
				zend_error(E_WARNING, "foreach() argument must be of type array|object, %s given", zend_zval_value_name(extensions.raw()));
				if (UNEXPECTED(EG(exception))) return zv::Val();
			} else {
				for (zv::ArrayEntry entry : zv::ArrRef(extensions.raw())) {
					zval *extension = entry.value().deref().raw();
					if (UNEXPECTED(Z_TYPE_P(extension) != IS_OBJECT)) {
						zend_throw_error(NULL, "Call to a member function isStaticMethodSupported() on %s", zend_zval_value_name(extension));
						return zv::Val();
					}
					bool supported;
					if (UNEXPECTED(!isStaticMethodSupported(pt_nh_throw_type_is_static_method_supported_site, extension, constructorReflection, supported))) return zv::Val();
					if (!supported) continue;

					zv::Val throwType = getThrowTypeFromStaticMethodCall(extension, constructorReflection, normalizedMethodCall.raw(), scope);
					if (UNEXPECTED(throwType.isUndef())) return zv::Val();
					if (throwType.isNull()) return zv::Val::null();
					if (UNEXPECTED(!throwType.ref().isObject())) {
						zend_throw_error(NULL, "Call to a member function isVoid() on %s", zend_zval_value_name(throwType.raw()));
						return zv::Val();
					}
					zend_long throwTypeIsVoid = pt_type_op_trinary(Z_OBJ_P(throwType.raw()), PT_OP_IS_VOID, 0, NULL);
					if (UNEXPECTED(throwTypeIsVoid < 0)) return zv::Val();
					if (throwTypeIsVoid == PT_TRI_YES) return zv::Val::null();

					return pt_internal_throw_point_create_explicit(scope, throwType.raw(), new_, false, false);
				}
			}
		}

		zv::Val throwType = pt_extended_method_reflection_call(constructorReflection, PT_MR_GET_THROW_TYPE);
		if (UNEXPECTED(throwType.isUndef())) return zv::Val();
		if (!throwType.isNull()) {
			throwType = pt_extended_method_reflection_call(constructorReflection, PT_MR_GET_THROW_TYPE);
			if (UNEXPECTED(throwType.isUndef())) return zv::Val();
			if (UNEXPECTED(!throwType.ref().isObject())) {
				zend_type_error("PHPStan\\Analyser\\ConditionalTypeResolver::resolveForCall(): Argument #1 ($declaredType) must be of type PHPStan\\Type\\Type, %s given", zend_zval_value_name(throwType.raw()));
				return zv::Val();
			}
			throwType = pt_conditional_type_resolver_resolve_for_call(throwType.raw(), parametersAcceptor, args, scope);
			if (UNEXPECTED(throwType.isUndef())) return zv::Val();
			if (UNEXPECTED(!throwType.ref().isObject())) {
				zend_throw_error(NULL, "Call to a member function isVoid() on %s", zend_zval_value_name(throwType.raw()));
				return zv::Val();
			}
			zend_long isVoid = pt_type_op_trinary(Z_OBJ_P(throwType.raw()), PT_OP_IS_VOID, 0, NULL);
			if (UNEXPECTED(isVoid < 0)) return zv::Val();
			if (isVoid != PT_TRI_YES) {
				return pt_internal_throw_point_create_explicit(scope, throwType.raw(), new_, true, false);
			}
		} else if (zend_is_true(OBJ_PROP_NUM(self, slots::implicitThrows))) {
			bool inThrow;
			if (UNEXPECTED(!pt_expression_context_is_in_throw(context, inThrow))) return zv::Val();
			bool implicit = !inThrow;
			if (!implicit) {
				zv::Val declaringClass = pt_extended_method_reflection_call(constructorReflection, PT_MR_GET_DECLARING_CLASS);
				if (UNEXPECTED(declaringClass.isUndef())) return zv::Val();
				zval throwable;
				ZVAL_INTERNED_STR(&throwable, pt_nh_throwable);
				bool isThrowable;
				if (UNEXPECTED(!pt_class_reflection_is(Z_OBJ_P(declaringClass.raw()), &throwable, isThrowable))) return zv::Val();
				implicit = !isThrowable;
			}
			if (implicit) {
				return pt_internal_throw_point_create_implicit(scope, methodCall.raw());
			}
		}

		return zv::Val::null();
	}

	/* Mirrors resolveReturnType(); $preResolvedAcceptor / $classExprType /
	 * $argsResult NULL or IS_NULL for null */
	zv::Val resolveReturnType(zval *scope, zval *expr, zval *preResolvedAcceptor, zval *classExprType, zval *argsResult, bool allowUnresolved) const
	{
		if (classExprType != NULL && Z_TYPE_P(classExprType) == IS_NULL) classExprType = NULL;
		zval *class_ = exprClass(expr);
		if (UNEXPECTED(class_ == NULL)) return zv::Val();
		int classIsName = isInstanceOf(class_, PT_CLASS_NAME);
		if (UNEXPECTED(classIsName < 0)) return zv::Val();
		if (classIsName) {
			return exactInstantiation(scope, expr, class_, preResolvedAcceptor, argsResult, allowUnresolved);
		}
		int classIsClassStmt = isInstanceOf(class_, PT_CLASS_CLASS_STMT);
		if (UNEXPECTED(classIsClassStmt < 0)) return zv::Val();
		if (classIsClassStmt) {
			zv::Val anonymousClassReflection = getAnonymousClassReflection(OBJ_PROP_NUM(self, slots::reflectionProvider), class_, scope);
			if (UNEXPECTED(anonymousClassReflection.isUndef())) return zv::Val();
			if (UNEXPECTED(!anonymousClassReflection.ref().isObject())) {
				zend_throw_error(NULL, "Call to a member function getName() on %s", zend_zval_value_name(anonymousClassReflection.raw()));
				return zv::Val();
			}
			zv::Val anonymousClassName = pt_class_reflection_get_name(Z_OBJ_P(anonymousClassReflection.raw()));
			if (UNEXPECTED(anonymousClassName.isUndef())) return zv::Val();
			if (UNEXPECTED(!requireString(anonymousClassName.raw(), "PHPStan\\Type\\ObjectType::__construct", "className"))) return zv::Val();
			zval objectType;
			if (UNEXPECTED(!pt_object_type_new(&objectType, Z_STR_P(anonymousClassName.raw())))) return zv::Val();
			return zv::Val::adopt(objectType);
		}

		// the class expression was walked by processExpr; its result's type of
		// the asked flavour is passed in by the typeCallback
		if (classExprType == NULL) {
			pt_throw_should_not_happen();
			return zv::Val();
		}
		return getObjectTypeOrClassStringObjectType(classExprType);
	}

	/* Mirrors exactInstantiation(); $preResolvedAcceptor / $argsResult NULL
	 * or IS_NULL for null */
	zv::Val exactInstantiation(zval *scope, zval *node, zval *className, zval *preResolvedAcceptor, zval *argsResult, bool allowUnresolved) const
	{
		zval null;
		ZVAL_NULL(&null);
		if (preResolvedAcceptor != NULL && Z_TYPE_P(preResolvedAcceptor) == IS_NULL) preResolvedAcceptor = NULL;
		if (argsResult != NULL && Z_TYPE_P(argsResult) == IS_NULL) argsResult = NULL;

		zv::Val resolvedClassName = pt_mutating_scope_resolve_name(Z_OBJ_P(scope), Z_OBJ_P(className));
		if (UNEXPECTED(resolvedClassName.isUndef())) return zv::Val();
		if (UNEXPECTED(!requireString(resolvedClassName.raw(), "PHPStan\\Analyser\\MutatingScope::resolveName", "name"))) return zv::Val();
		int isStaticName = nameLowerEquals(className, PT_LC("static"));
		if (UNEXPECTED(isStaticName < 0)) return zv::Val();
		bool isStatic = isStaticName == 1;

		zval *reflectionProvider = OBJ_PROP_NUM(self, slots::reflectionProvider);
		bool hasClass;
		if (UNEXPECTED(!pt_reflection_provider_has_class(Z_OBJ_P(reflectionProvider), resolvedClassName.raw(), hasClass))) return zv::Val();
		if (!hasClass) {
			if (isStatic) {
				bool inClass;
				if (UNEXPECTED(!pt_scope_is_in_class(Z_OBJ_P(scope), inClass))) return zv::Val();
				if (!inClass) return pt_type_new_error_type();

				zv::Val scopeClassReflection = pt_scope_get_class_reflection(Z_OBJ_P(scope));
				if (UNEXPECTED(scopeClassReflection.isUndef())) return zv::Val();
				zval staticType;
				if (UNEXPECTED(!pt_static_type_new(&staticType, scopeClassReflection.raw()))) return zv::Val();
				return zv::Val::adopt(staticType);
			}
			int isParent = nameLowerEquals(className, PT_LC("parent"));
			if (UNEXPECTED(isParent < 0)) return zv::Val();
			if (isParent) {
				zval nonexistentParent;
				if (UNEXPECTED(!pt_nonexistent_parent_class_type_new(&nonexistentParent))) return zv::Val();
				return zv::Val::adopt(nonexistentParent);
			}

			zval objectType;
			if (UNEXPECTED(!pt_object_type_new(&objectType, Z_STR_P(resolvedClassName.raw())))) return zv::Val();
			return zv::Val::adopt(objectType);
		}

		zv::Val classReflection = pt_reflection_provider_get_class(Z_OBJ_P(reflectionProvider), resolvedClassName.raw());
		if (UNEXPECTED(classReflection.isUndef())) return zv::Val();
		zv::Val nonFinalClassReflection = zv::Val::copyOf(zv::Ref(classReflection.raw()));
		if (!isStatic) {
			classReflection = pt_class_reflection_as_final(Z_OBJ_P(classReflection.raw()));
			if (UNEXPECTED(classReflection.isUndef())) return zv::Val();
		}
		zv::Val constructorMethod;
		{
			bool hasConstructor;
			if (UNEXPECTED(!pt_class_reflection_has_constructor(Z_OBJ_P(classReflection.raw()), hasConstructor))) return zv::Val();
			constructorMethod = hasConstructor
				? pt_class_reflection_get_constructor(Z_OBJ_P(classReflection.raw()))
				: pt_type_new(PT_CLASS_DUMMY_CONSTRUCTOR_REFLECTION, 1, classReflection.raw());
			if (UNEXPECTED(constructorMethod.isUndef())) return zv::Val();
		}

		zv::Val constructorName = pt_extended_method_reflection_call(constructorMethod.raw(), PT_MR_GET_NAME);
		if (UNEXPECTED(constructorName.isUndef())) return zv::Val();
		if (Z_TYPE_P(constructorName.raw()) == IS_STRING && ZSTR_LEN(Z_STR_P(constructorName.raw())) == 0) {
			pt_throw_should_not_happen();
			return zv::Val();
		}

		zv::Arr resolvedTypes = zv::Arr::empty();
		zv::Val methodCall;
		{
			zv::Val methodClassName = pt_type_new(PT_CLASS_NAME, 1, resolvedClassName.raw());
			if (UNEXPECTED(methodClassName.isUndef())) return zv::Val();
			zv::Val name = pt_extended_method_reflection_call(constructorMethod.raw(), PT_MR_GET_NAME);
			if (UNEXPECTED(name.isUndef())) return zv::Val();
			zv::Val identifier = pt_type_new(PT_CLASS_IDENTIFIER, 1, name.raw());
			if (UNEXPECTED(identifier.isUndef())) return zv::Val();
			zval *args = exprArgs(node);
			if (UNEXPECTED(args == NULL)) return zv::Val();
			zv::Args staticCallArgv{methodClassName.raw(), identifier.raw(), args};
			methodCall = pt_type_new(PT_CLASS_STATIC_CALL, 3, staticCallArgv);
			if (UNEXPECTED(methodCall.isUndef())) return zv::Val();
		}

		zv::Val parametersAcceptor;
		if (preResolvedAcceptor != NULL) {
			parametersAcceptor = zv::Val::copyOf(zv::Ref(preResolvedAcceptor));
		} else {
			parametersAcceptor = combinedConstructorAcceptor(staticCallArgs(methodCall.raw()), constructorMethod.raw());
			if (UNEXPECTED(parametersAcceptor.isUndef())) return zv::Val();
		}
		zv::Val normalizedMethodCall = reorderStaticCallArguments(parametersAcceptor.raw(), methodCall.raw());
		if (UNEXPECTED(normalizedMethodCall.isUndef())) return zv::Val();

		if (!normalizedMethodCall.isNull()) {
			// runs lazily in the typeCallback - prime the storage with the argument
			// results so the extensions' Scope::getType() asks about the arguments
			// answer from them instead of re-walking on demand
			pt_primed_storage primed;
			if (UNEXPECTED(!pt_dynamic_return_type_storage_primer_push(OBJ_PROP_NUM(self, slots::storagePrimer), scope, argsResult != NULL ? argsResult : &null, primed))) return zv::Val();
			bool resolved = resolveTypesByExtensions(classReflection.raw(), constructorMethod.raw(), normalizedMethodCall.raw(), scope, resolvedTypes);
			pt_finally([&]() { (void) pt_dynamic_return_type_storage_primer_pop(primed); });
			if (UNEXPECTED(!resolved || EG(exception) != NULL)) return zv::Val();
		}

		if (zend_hash_num_elements(resolvedTypes.table()) > 0) {
			HashTable *resolvedTypesTable = resolvedTypes.table();
			return pt_type_combinator_union(zend_hash_num_elements(resolvedTypesTable), resolvedTypesTable->arPacked);
		}

		// A constructor makes `new` never-returning only when its own return type
		// is (or can resolve to) explicit never; the dynamic static-method return
		// type extensions already ran above, so only the base return type is left
		// to check.
		zv::Val constructorReturnType = acceptorReturnType(parametersAcceptor.raw());
		if (UNEXPECTED(constructorReturnType.isUndef())) return zv::Val();
		if (UNEXPECTED(!constructorReturnType.ref().isObject())) {
			zend_throw_error(NULL, "Call to a member function hasTemplateOrLateResolvableType() on %s", zend_zval_value_name(constructorReturnType.raw()));
			return zv::Val();
		}
		bool mayBeNever = constructorReturnType.ref().instanceOf(pt_ce_never_type);
		if (!mayBeNever) {
			zv::Val hasTemplate = pt_type_op(Z_OBJ_P(constructorReturnType.raw()), PT_OP_HAS_TEMPLATE_OR_LATE_RESOLVABLE_TYPE, 0, NULL);
			if (UNEXPECTED(hasTemplate.isUndef())) return zv::Val();
			mayBeNever = zend_is_true(hasTemplate.raw());
		}
		if (mayBeNever) {
			// $methodCall is a synthetic StaticCall the handler built; price it
			// through the sanctioned on-demand walk (the constructor's own
			// never-returning conditional return type).
			zv::Val methodResult = syntheticTypeOnScope(methodCall.raw(), scope);
			if (UNEXPECTED(methodResult.isUndef())) return zv::Val();
			bool explicitNever;
			if (UNEXPECTED(!isExplicitNever(methodResult.raw(), explicitNever))) return zv::Val();
			if (explicitNever) return methodResult;
		}

		zval objectTypeZv;
		if (isStatic) {
			if (UNEXPECTED(!pt_static_type_new(&objectTypeZv, classReflection.raw()))) return zv::Val();
		} else {
			if (UNEXPECTED(!pt_object_type_new(&objectTypeZv, Z_STR_P(resolvedClassName.raw()), NULL, classReflection.raw()))) return zv::Val();
		}
		zv::Val objectType = zv::Val::adopt(objectTypeZv);
		bool isGeneric;
		if (UNEXPECTED(!pt_class_reflection_is_generic(Z_OBJ_P(classReflection.raw()), isGeneric))) return zv::Val();
		if (!isGeneric) return objectType;

		zv::Val frame = zv::Val::null();
		if (allowUnresolved) {
			frame = pt_mutating_scope_get_current_template_argument_frame(Z_OBJ_P(scope));
			if (UNEXPECTED(frame.isUndef())) return zv::Val();
		}

		if (!allowUnresolved) {
			// Native types use the bounds or defaults, without PHPDoc inference.
			return unresolvedArguments(classReflection.raw(), node, frame.raw(), allowUnresolved, isStatic, resolvedClassName.raw());
		}

		zv::Val assignedToProperty = pt_engine_node_get_attribute(Z_OBJ_P(node), PT_LC("assignedToProperty"));
		if (UNEXPECTED(assignedToProperty.isUndef())) return zv::Val();
		if (!assignedToProperty.isNull()) {
			zv::Val constructorVariants = pt_extended_method_reflection_call(constructorMethod.raw(), PT_MR_GET_VARIANTS);
			if (UNEXPECTED(constructorVariants.isUndef())) return zv::Val();
			zend_long constructorVariantCount = countOf(constructorVariants.raw());
			if (UNEXPECTED(constructorVariantCount < 0)) return zv::Val();
			if (constructorVariantCount == 1) {
				// When the arguments resolve none of the class template types,
				// the declared type of the assigned property is the only source
				// of the type arguments.
				zv::Val propertyType = assignedPropertyType(scope, parametersAcceptor.raw(), classReflection.raw(), nonFinalClassReflection.raw(), assignedToProperty.raw(), isStatic, resolvedClassName.raw());
				if (UNEXPECTED(propertyType.isUndef())) return zv::Val();
				if (!propertyType.isNull()) return propertyType;
			}
		}

		int isDummy = isInstanceOf(constructorMethod.raw(), PT_CLASS_DUMMY_CONSTRUCTOR_REFLECTION);
		if (UNEXPECTED(isDummy < 0)) return zv::Val();
		if (isDummy) {
			return unresolvedArguments(classReflection.raw(), node, frame.raw(), allowUnresolved, isStatic, resolvedClassName.raw());
		}

		{
			zv::Val declaringClass = pt_extended_method_reflection_call(constructorMethod.raw(), PT_MR_GET_DECLARING_CLASS);
			if (UNEXPECTED(declaringClass.isUndef())) return zv::Val();
			zv::Val declaringClassName = pt_class_reflection_get_name(Z_OBJ_P(declaringClass.raw()));
			if (UNEXPECTED(declaringClassName.isUndef())) return zv::Val();
			zv::Val instantiatedClassName = pt_class_reflection_get_name(Z_OBJ_P(classReflection.raw()));
			if (UNEXPECTED(instantiatedClassName.isUndef())) return zv::Val();
			if (!zend_is_identical(declaringClassName.raw(), instantiatedClassName.raw())) {
				return inheritedConstructorInstantiation(scope, node, classReflection.raw(), constructorMethod.raw(), frame.raw(), allowUnresolved, isStatic, resolvedClassName.raw());
			}
		}

		zv::Val resolvedTemplateTypeMap = acceptorResolvedTemplateTypeMap(parametersAcceptor.raw());
		if (UNEXPECTED(resolvedTemplateTypeMap.isUndef())) return zv::Val();
		zv::Val templateTypeMap = pt_class_reflection_get_template_type_map(Z_OBJ_P(classReflection.raw()));
		if (UNEXPECTED(templateTypeMap.isUndef())) return zv::Val();
		zv::Val types = pt_class_reflection_type_map_to_list(Z_OBJ_P(classReflection.raw()), templateTypeMap.raw());
		if (UNEXPECTED(types.isUndef())) return zv::Val();
		zv::Val newGenericType = genericObjectType(resolvedClassName.raw(), types.raw(), classReflection.raw());
		if (UNEXPECTED(newGenericType.isUndef())) return zv::Val();
		if (isStatic) {
			zv::Arr noVariances = zv::Arr::empty();
			zval genericStatic;
			if (UNEXPECTED(!pt_generic_static_type_new(&genericStatic, classReflection.raw(), types.raw(), NULL, noVariances.raw()))) return zv::Val();
			newGenericType = zv::Val::adopt(genericStatic);
		}

		zv::Val hasTemplate = pt_type_op(Z_OBJ_P(newGenericType.raw()), PT_OP_HAS_TEMPLATE_OR_LATE_RESOLVABLE_TYPE, 0, NULL);
		if (UNEXPECTED(hasTemplate.isUndef())) return zv::Val();
		if (!zend_is_true(hasTemplate.raw())) return newGenericType;

		zval allowUnresolvedZv = {};
		ZVAL_BOOL(&allowUnresolvedZv, allowUnresolved);
		zv::Args traverserArgv{resolvedTemplateTypeMap.raw(), node, frame.raw(), &allowUnresolvedZv};
		zv::Val traverser = pt_type_new(PT_CLASS_GENERIC_TYPE_TEMPLATE_TRAVERSER, 4, traverserArgv);
		if (UNEXPECTED(traverser.isUndef())) return zv::Val();
		zval mapped;
		if (UNEXPECTED(!pt_type_traverser_map(&mapped, newGenericType.raw(), traverser.raw()))) return zv::Val();
		return zv::Val::adopt(mapped);
	}

	/* Mirrors unresolvedArgumentList(); $frame NULL or IS_NULL for null */
	static zv::Val unresolvedArgumentList(zval *classReflection, zval *site, zval *frame, bool allowUnresolved)
	{
		if (frame != NULL && Z_TYPE_P(frame) == IS_NULL) frame = NULL;
		if (frame == NULL) {
			zv::Val templateTypeMap = pt_class_reflection_get_template_type_map(Z_OBJ_P(classReflection));
			if (UNEXPECTED(templateTypeMap.isUndef())) return zv::Val();
			zv::Val bounds = pt_template_type_map_resolve_to_bounds(templateTypeMap.raw());
			if (UNEXPECTED(bounds.isUndef())) return zv::Val();
			return pt_class_reflection_type_map_to_list(Z_OBJ_P(classReflection), bounds.raw());
		}

		// a synthetic site (the parent constructor's `new`) always hands out
		// markers - the real site re-keys and resolves them
		zv::Val syntheticAttribute = pt_engine_node_get_attribute(Z_OBJ_P(site), PT_LC("templateArgumentSyntheticSite"));
		if (UNEXPECTED(syntheticAttribute.isUndef())) return zv::Val();
		bool synthetic = Z_TYPE_P(syntheticAttribute.raw()) == IS_TRUE;

		zv::Val templateTypeMap = pt_class_reflection_get_template_type_map(Z_OBJ_P(classReflection));
		if (UNEXPECTED(templateTypeMap.isUndef())) return zv::Val();
		zv::Val mapCallback = pt_native_closure(&unresolvedArgumentMapBody, site, frame, allowUnresolved, synthetic);
		zv::Val mapped = pt_template_type_map_map(templateTypeMap.raw(), mapCallback.raw());
		if (UNEXPECTED(mapped.isUndef())) return zv::Val();
		return pt_class_reflection_type_map_to_list(Z_OBJ_P(classReflection), mapped.raw());
	}

	/* Mirrors rekeyParentTemplateArgument(); $frame NULL or IS_NULL for null */
	static zv::Val rekeyParentTemplateArgument(zval *type, zval *site, zval *templateType, zval *frame, bool allowUnresolved)
	{
		if (frame != NULL && Z_TYPE_P(frame) == IS_NULL) frame = NULL;
		if (frame == NULL) {
			zv::Val initialType = unresolvedGetInitialType(type);
			if (UNEXPECTED(initialType.isUndef()) || !initialType.isNull()) return initialType;
			zv::Val defaultType = templateGetDefault(templateType);
			if (UNEXPECTED(defaultType.isUndef()) || !defaultType.isNull()) return defaultType;
			return templateGetBound(templateType);
		}
		if (allowUnresolved) {
			bool observing;
			if (UNEXPECTED(!pt_template_argument_frame_is_observing(frame, observing))) return zv::Val();
			if (observing) return unresolvedWithSite(type, site, templateType);
		}

		zv::Val templateName = templateGetName(templateType);
		if (UNEXPECTED(templateName.isUndef())) return zv::Val();
		if (UNEXPECTED(!requireString(templateName.raw(), "PHPStan\\Analyser\\Generics\\TemplateArgumentFrame::resolve", "templateName"))) return zv::Val();
		zv::Val resolved = pt_template_argument_frame_resolve(frame, site, Z_STR_P(templateName.raw()));
		if (UNEXPECTED(resolved.isUndef()) || !resolved.isNull()) return resolved;
		zv::Val initialType = unresolvedGetInitialType(type);
		if (UNEXPECTED(initialType.isUndef()) || !initialType.isNull()) return initialType;
		return pt_template_argument_frame_resolve_or_unconstrained(frame, site, templateType);
	}

	/* Mirrors specifyTypes(); $resolvedParametersAcceptor NULL or IS_NULL
	 * for null */
	zv::Val specifyTypes(zval *scope, zval *expr, zval *resolvedParametersAcceptor, zval *context) const
	{
		if (resolvedParametersAcceptor != NULL && Z_TYPE_P(resolvedParametersAcceptor) == IS_NULL) resolvedParametersAcceptor = NULL;
		zval *class_ = exprClass(expr);
		if (UNEXPECTED(class_ == NULL)) return zv::Val();
		int classIsName = isInstanceOf(class_, PT_CLASS_NAME);
		if (UNEXPECTED(classIsName < 0)) return zv::Val();
		zval *reflectionProvider = OBJ_PROP_NUM(self, slots::reflectionProvider);
		bool known = false;
		if (classIsName) {
			zv::Val name = nameToString(class_);
			if (UNEXPECTED(name.isUndef())) return zv::Val();
			if (UNEXPECTED(!pt_reflection_provider_has_class(Z_OBJ_P(reflectionProvider), name.raw(), known))) return zv::Val();
		}
		if (!known) {
			return pt_default_narrowing_helper_specify_default_types(OBJ_PROP_NUM(self, slots::defaultNarrowingHelper), expr, context);
		}

		zv::Val name = nameToString(class_);
		if (UNEXPECTED(name.isUndef())) return zv::Val();
		zv::Val classReflection = pt_reflection_provider_get_class(Z_OBJ_P(reflectionProvider), name.raw());
		if (UNEXPECTED(classReflection.isUndef())) return zv::Val();

		bool hasConstructor;
		if (UNEXPECTED(!pt_class_reflection_has_constructor(Z_OBJ_P(classReflection.raw()), hasConstructor))) return zv::Val();
		if (hasConstructor) {
			zv::Val methodReflection = pt_class_reflection_get_constructor(Z_OBJ_P(classReflection.raw()));
			if (UNEXPECTED(methodReflection.isUndef())) return zv::Val();
			zv::Val asserts = pt_extended_method_reflection_call(methodReflection.raw(), PT_MR_GET_ASSERTS);
			if (UNEXPECTED(asserts.isUndef())) return zv::Val();

			zv::Val all = assertionsGetAll(asserts.raw());
			if (UNEXPECTED(all.isUndef())) return zv::Val();
			bool hasAsserts = !(Z_TYPE_P(all.raw()) == IS_ARRAY && zend_hash_num_elements(Z_ARRVAL_P(all.raw())) == 0);
			if (hasAsserts && resolvedParametersAcceptor != NULL) {
				zv::Val mapCallback = pt_native_closure(&resolveAssertTypeBody, resolvedParametersAcceptor);
				asserts = assertionsMapTypes(asserts.raw(), mapCallback.raw());
				if (UNEXPECTED(asserts.isUndef())) return zv::Val();

				zv::Val specifiedTypes = pt_default_narrowing_helper_specify_types_from_asserts(OBJ_PROP_NUM(self, slots::defaultNarrowingHelper), context, expr, asserts.raw(), resolvedParametersAcceptor, scope);
				if (UNEXPECTED(specifiedTypes.isUndef())) return zv::Val();
				if (!specifiedTypes.isNull()) return specifiedTypes;
			}
		}

		// A known class without (applicable) constructor asserts contributes no
		// narrowing entry, mirroring the old handler's empty return for this path.
		zv::Val empty = pt_specified_types_new();
		if (UNEXPECTED(empty.isUndef())) return zv::Val();
		return pt_specified_types_set_root_expr(Z_OBJ_P(empty.raw()), expr);
	}

private:
	zend_object *self;

	/* ParametersAcceptorSelector::combineVariantsForNormalization($args,
	 * $constructor->getVariants(), $constructor->getNamedArgumentsVariants())
	 * ($args NULL = pending exception of the read) */
	static zv::Val combinedConstructorAcceptor(zval *args, zval *constructorReflection)
	{
		if (UNEXPECTED(args == NULL)) return zv::Val();
		zv::Val argsHold = zv::Val::copyOf(zv::Ref(args));
		zv::Val variants = pt_extended_method_reflection_call(constructorReflection, PT_MR_GET_VARIANTS);
		if (UNEXPECTED(variants.isUndef())) return zv::Val();
		zv::Val namedArgumentsVariants = pt_extended_method_reflection_call(constructorReflection, PT_MR_GET_NAMED_ARGUMENTS_VARIANTS);
		if (UNEXPECTED(namedArgumentsVariants.isUndef())) return zv::Val();
		return combineVariantsForNormalization(argsHold.raw(), variants.raw(), namedArgumentsVariants.raw());
	}

	/* $nodeScopeResolver->processStmtNode($expr->class, $scope, $storage,
	 * $nodeCallback, StatementContext::createTopLevel($context->shouldResolveTemplateArguments()));
	 * false = pending exception */
	[[nodiscard]] static bool processAnonymousClassStatement(zval *nodeScopeResolver, zval *classStmt, zval *scope, zval *storage, zval *nodeCallback, zval *context)
	{
		bool resolveTemplateArguments;
		if (UNEXPECTED(!pt_expression_context_should_resolve_template_arguments(context, resolveTemplateArguments))) return false;
		zv::Val statementContext = pt_statement_context_create_top_level(resolveTemplateArguments);
		if (UNEXPECTED(statementContext.isUndef())) return false;
		return !pt_node_scope_resolver_process_stmt_node(nodeScopeResolver, classStmt, scope, storage, nodeCallback, statementContext.raw()).isUndef();
	}

	/* new ImpurePoint($scope, $expr, 'new', sprintf('instantiation of class
	 * %s', $constructorReflection->getDeclaringClass()->getDisplayName()),
	 * $certain) */
	static zv::Val constructorInstantiationImpurePoint(zval *scope, zval *expr, zval *constructorReflection, bool certain)
	{
		zv::Val declaringClass = pt_extended_method_reflection_call(constructorReflection, PT_MR_GET_DECLARING_CLASS);
		if (UNEXPECTED(declaringClass.isUndef())) return zv::Val();
		zv::Val displayName = pt_class_reflection_get_display_name(Z_OBJ_P(declaringClass.raw()), true);
		if (UNEXPECTED(displayName.isUndef())) return zv::Val();
		zend_string *description = instantiationDescription(displayName.raw());
		zv::Val point = pt_impure_point_new(scope, expr, pt_nh_new, description, certain);
		zend_string_release(description);
		return point;
	}

	/* the extensions loop of exactInstantiation() inside its try, appending
	 * to $resolvedTypes; false = pending exception */
	[[nodiscard]] bool resolveTypesByExtensions(zval *classReflection, zval *constructorMethod, zval *normalizedMethodCall, zval *scope, zv::Arr &resolvedTypes) const
	{
		zv::Val instantiatedClassName = pt_class_reflection_get_name(Z_OBJ_P(classReflection));
		if (UNEXPECTED(instantiatedClassName.isUndef())) return false;
		zv::Val extensions = dynamicStaticMethodReturnTypeExtensionsForClass(OBJ_PROP_NUM(self, slots::dynamicReturnTypeExtensionRegistry), instantiatedClassName.raw());
		if (UNEXPECTED(extensions.isUndef())) return false;
		if (UNEXPECTED(Z_TYPE_P(extensions.raw()) != IS_ARRAY)) {
			zend_error(E_WARNING, "foreach() argument must be of type array|object, %s given", zend_zval_value_name(extensions.raw()));
			return EG(exception) == NULL;
		}
		for (zv::ArrayEntry entry : zv::ArrRef(extensions.raw())) {
			zval *extension = entry.value().deref().raw();
			if (UNEXPECTED(Z_TYPE_P(extension) != IS_OBJECT)) {
				zend_throw_error(NULL, "Call to a member function isStaticMethodSupported() on %s", zend_zval_value_name(extension));
				return false;
			}
			bool supported;
			if (UNEXPECTED(!isStaticMethodSupported(pt_nh_return_type_is_static_method_supported_site, extension, constructorMethod, supported))) return false;
			if (!supported) continue;

			zv::Val resolvedType = getTypeFromStaticMethodCall(extension, constructorMethod, normalizedMethodCall, scope);
			if (UNEXPECTED(resolvedType.isUndef())) return false;
			if (resolvedType.isNull()) continue;

			resolvedTypes.push(std::move(resolvedType));
		}
		return true;
	}

	/* $this->container->getByType(NodeScopeResolver::class)->processSyntheticOnDemand($expr,
	 * $scope)->getTypeOnScope($scope, false) */
	zv::Val syntheticTypeOnScope(zval *expr, zval *scope) const
	{
		zv::Val nodeScopeResolver = nodeScopeResolverFromContainer(OBJ_PROP_NUM(self, slots::container));
		if (UNEXPECTED(nodeScopeResolver.isUndef())) return zv::Val();
		if (UNEXPECTED(!nodeScopeResolver.ref().isObject())) {
			zend_throw_error(NULL, "Call to a member function processSyntheticOnDemand() on %s", zend_zval_value_name(nodeScopeResolver.raw()));
			return zv::Val();
		}
		zv::Val result = pt_node_scope_resolver_process_synthetic_on_demand(nodeScopeResolver.raw(), expr, scope);
		if (UNEXPECTED(result.isUndef())) return zv::Val();
		return pt_expression_result_get_type_on_scope(result.raw(), scope, false);
	}

	/* new GenericObjectType($resolvedClassName, $types, classReflection:
	 * $classReflection->withTypes($types)->asFinal()) */
	static zv::Val genericObjectType(zval *resolvedClassName, zval *types, zval *classReflection)
	{
		zv::Val withTypes = pt_class_reflection_with_types(Z_OBJ_P(classReflection), types);
		if (UNEXPECTED(withTypes.isUndef())) return zv::Val();
		if (UNEXPECTED(!withTypes.ref().isObject())) {
			zend_throw_error(NULL, "Call to a member function asFinal() on %s", zend_zval_value_name(withTypes.raw()));
			return zv::Val();
		}
		zv::Val finalReflection = pt_class_reflection_as_final(Z_OBJ_P(withTypes.raw()));
		if (UNEXPECTED(finalReflection.isUndef())) return zv::Val();
		zval genericObject;
		if (UNEXPECTED(!pt_generic_object_type_new(&genericObject, Z_STR_P(resolvedClassName), types, NULL, finalReflection.raw()))) return zv::Val();
		return zv::Val::adopt(genericObject);
	}

	/* $unresolvedArguments() of exactInstantiation(): the class's arguments
	 * when the constructor says nothing about them */
	static zv::Val unresolvedArguments(zval *classReflection, zval *node, zval *frame, bool allowUnresolved, bool isStatic, zval *resolvedClassName)
	{
		zv::Val types = unresolvedArgumentList(classReflection, node, frame, allowUnresolved);
		if (UNEXPECTED(types.isUndef())) return zv::Val();
		if (isStatic) {
			zv::Arr noVariances = zv::Arr::empty();
			zval genericStatic;
			if (UNEXPECTED(!pt_generic_static_type_new(&genericStatic, classReflection, types.raw(), NULL, noVariances.raw()))) return zv::Val();
			return zv::Val::adopt(genericStatic);
		}

		return genericObjectType(resolvedClassName, types.raw(), classReflection);
	}

	/* the assignedToProperty block of exactInstantiation() inside
	 * `count($constructorVariants) === 1`: the property type, null when the
	 * block does not return */
	zv::Val assignedPropertyType(zval *scope, zval *parametersAcceptor, zval *classReflection, zval *nonFinalClassReflection, zval *assignedToProperty, bool isStatic, zval *resolvedClassName) const
	{
		zv::Val resolvedTemplateTypeMap = acceptorResolvedTemplateTypeMap(parametersAcceptor);
		if (UNEXPECTED(resolvedTemplateTypeMap.isUndef())) return zv::Val();
		if (UNEXPECTED(!resolvedTemplateTypeMap.ref().isObject())) {
			zend_throw_error(NULL, "Call to a member function getType() on %s", zend_zval_value_name(resolvedTemplateTypeMap.raw()));
			return zv::Val();
		}
		bool hasResolvedClassTemplateType = false;
		zv::Val templateTypeMap = pt_class_reflection_get_template_type_map(Z_OBJ_P(classReflection));
		if (UNEXPECTED(templateTypeMap.isUndef())) return zv::Val();
		zv::Val classTemplateTypes = pt_template_type_map_get_types(templateTypeMap.raw());
		if (UNEXPECTED(classTemplateTypes.isUndef())) return zv::Val();
		for (zv::ArrayEntry entry : zv::ArrRef(classTemplateTypes.raw())) {
			zv::Str classTemplateTypeName = entry.hasStringKey()
				? zv::Str::copyOf(entry.stringKey())
				: zv::Str::adopt(zend_long_to_str((zend_long) entry.indexKey()));
			zv::Val resolvedType = pt_template_type_map_get_type(resolvedTemplateTypeMap.raw(), classTemplateTypeName.get());
			if (UNEXPECTED(resolvedType.isUndef())) return zv::Val();
			if (resolvedType.isNull() || resolvedType.ref().instanceOf(pt_ce_error_type)) continue;

			hasResolvedClassTemplateType = true;
			break;
		}
		if (hasResolvedClassTemplateType) return zv::Val::null();

		zv::Val foundProperty = findPropertyReflectionFromNode(OBJ_PROP_NUM(self, slots::propertyReflectionFinder), assignedToProperty, scope);
		if (UNEXPECTED(foundProperty.isUndef())) return zv::Val();
		if (foundProperty.isNull()) return zv::Val::null();

		zval nonFinalObjectTypeZv;
		if (isStatic) {
			if (UNEXPECTED(!pt_static_type_new(&nonFinalObjectTypeZv, nonFinalClassReflection))) return zv::Val();
		} else {
			if (UNEXPECTED(!pt_object_type_new(&nonFinalObjectTypeZv, Z_STR_P(resolvedClassName), NULL, nonFinalClassReflection))) return zv::Val();
		}
		zv::Val nonFinalObjectType = zv::Val::adopt(nonFinalObjectTypeZv);
		zv::Val writableType = getWritableType(foundProperty.raw());
		if (UNEXPECTED(writableType.isUndef())) return zv::Val();
		zv::Args intersectArgv{writableType.raw(), nonFinalObjectType.raw()};
		zv::Val propertyType = pt_type_combinator_intersect(2, intersectArgv);
		if (UNEXPECTED(propertyType.isUndef())) return zv::Val();
		if (!propertyType.ref().instanceOf(pt_ce_never_type)) return propertyType;
		return zv::Val::null();
	}

	/* the `$constructorMethod->getDeclaringClass()->getName() !==
	 * $classReflection->getName()` block of exactInstantiation(): an
	 * inherited generic constructor re-resolved against the parent */
	zv::Val inheritedConstructorInstantiation(zval *scope, zval *node, zval *classReflection, zval *constructorMethod, zval *frame, bool allowUnresolved, bool isStatic, zval *resolvedClassName) const
	{
		zv::Val declaringClass = pt_extended_method_reflection_call(constructorMethod, PT_MR_GET_DECLARING_CLASS);
		if (UNEXPECTED(declaringClass.isUndef())) return zv::Val();
		bool declaringClassIsGeneric;
		if (UNEXPECTED(!pt_class_reflection_is_generic(Z_OBJ_P(declaringClass.raw()), declaringClassIsGeneric))) return zv::Val();
		if (!declaringClassIsGeneric) {
			return unresolvedArguments(classReflection, node, frame, allowUnresolved, isStatic, resolvedClassName);
		}
		zv::Val newType;
		{
			zv::Val templateTypeMap = pt_class_reflection_get_template_type_map(Z_OBJ_P(classReflection));
			if (UNEXPECTED(templateTypeMap.isUndef())) return zv::Val();
			zv::Val types = pt_class_reflection_type_map_to_list(Z_OBJ_P(classReflection), templateTypeMap.raw());
			if (UNEXPECTED(types.isUndef())) return zv::Val();
			zval genericObject;
			if (UNEXPECTED(!pt_generic_object_type_new(&genericObject, Z_STR_P(resolvedClassName), types.raw()))) return zv::Val();
			newType = zv::Val::adopt(genericObject);
		}
		zv::Val ancestorType;
		{
			zv::Val ancestorDeclaringClass = pt_extended_method_reflection_call(constructorMethod, PT_MR_GET_DECLARING_CLASS);
			if (UNEXPECTED(ancestorDeclaringClass.isUndef())) return zv::Val();
			zv::Val ancestorClassName = pt_class_reflection_get_name(Z_OBJ_P(ancestorDeclaringClass.raw()));
			if (UNEXPECTED(ancestorClassName.isUndef())) return zv::Val();
			ancestorType = pt_type_op(Z_OBJ_P(newType.raw()), PT_OP_GET_ANCESTOR_WITH_CLASS_NAME, 1, ancestorClassName.raw());
			if (UNEXPECTED(ancestorType.isUndef())) return zv::Val();
		}
		if (ancestorType.isNull()) {
			return unresolvedArguments(classReflection, node, frame, allowUnresolved, isStatic, resolvedClassName);
		}
		zv::Val ancestorClassReflections = pt_type_op(Z_OBJ_P(ancestorType.raw()), PT_OP_GET_OBJECT_CLASS_REFLECTIONS, 0, NULL);
		if (UNEXPECTED(ancestorClassReflections.isUndef())) return zv::Val();
		zend_long ancestorClassReflectionCount = countOf(ancestorClassReflections.raw());
		if (UNEXPECTED(ancestorClassReflectionCount < 0)) return zv::Val();
		if (ancestorClassReflectionCount != 1) {
			return unresolvedArguments(classReflection, node, frame, allowUnresolved, isStatic, resolvedClassName);
		}

		zv::Val newParentNode;
		{
			zv::Val parentDeclaringClass = pt_extended_method_reflection_call(constructorMethod, PT_MR_GET_DECLARING_CLASS);
			if (UNEXPECTED(parentDeclaringClass.isUndef())) return zv::Val();
			zv::Val parentClassName = pt_class_reflection_get_name(Z_OBJ_P(parentDeclaringClass.raw()));
			if (UNEXPECTED(parentClassName.isUndef())) return zv::Val();
			zv::Val parentName = pt_type_new(PT_CLASS_NAME, 1, parentClassName.raw());
			if (UNEXPECTED(parentName.isUndef())) return zv::Val();
			zval *args = exprArgs(node);
			if (UNEXPECTED(args == NULL)) return zv::Val();
			zv::Val attributes = syntheticSiteAttributes();
			zv::Args newArgv{parentName.raw(), args, attributes.raw()};
			newParentNode = pt_type_new(PT_CLASS_NEW, 3, newArgv);
			if (UNEXPECTED(newParentNode.isUndef())) return zv::Val();
		}
		// the synthetic walk is load-bearing: it re-resolves the parent
		// constructor's template types from the arguments
		zv::Val newParentType = syntheticTypeOnScope(newParentNode.raw(), scope);
		if (UNEXPECTED(newParentType.isUndef())) return zv::Val();
		if (UNEXPECTED(!newParentType.ref().isObject())) {
			zend_throw_error(NULL, "Call to a member function getObjectClassReflections() on %s", zend_zval_value_name(newParentType.raw()));
			return zv::Val();
		}
		zv::Val newParentTypeClassReflections = pt_type_op(Z_OBJ_P(newParentType.raw()), PT_OP_GET_OBJECT_CLASS_REFLECTIONS, 0, NULL);
		if (UNEXPECTED(newParentTypeClassReflections.isUndef())) return zv::Val();
		zend_long newParentTypeClassReflectionCount = countOf(newParentTypeClassReflections.raw());
		if (UNEXPECTED(newParentTypeClassReflectionCount < 0)) return zv::Val();
		if (newParentTypeClassReflectionCount != 1) {
			return unresolvedArguments(classReflection, node, frame, allowUnresolved, isStatic, resolvedClassName);
		}
		zval null;
		ZVAL_NULL(&null);
		zval *newParentTypeClassReflection = firstOf(newParentTypeClassReflections.raw(), &null);
		if (UNEXPECTED(newParentTypeClassReflection == NULL)) return zv::Val();
		zv::Val newParentTypeClassReflectionHold = zv::Val::copyOf(zv::Ref(newParentTypeClassReflection));

		zval *ancestorClassReflection = firstOf(ancestorClassReflections.raw(), &null);
		if (UNEXPECTED(ancestorClassReflection == NULL)) return zv::Val();
		if (UNEXPECTED(Z_TYPE_P(ancestorClassReflection) != IS_OBJECT)) {
			zend_throw_error(NULL, "Call to a member function getActiveTemplateTypeMap() on %s", zend_zval_value_name(ancestorClassReflection));
			return zv::Val();
		}
		zend_class_entry *templateTypeCe = pt_class(PT_CLASS_TEMPLATE_TYPE);
		if (UNEXPECTED(templateTypeCe == NULL)) return zv::Val();
		zv::Arr ancestorMapping = zv::Arr::empty();
		{
			zv::Val activeTemplateTypeMap = pt_class_reflection_get_active_template_type_map(Z_OBJ_P(ancestorClassReflection));
			if (UNEXPECTED(activeTemplateTypeMap.isUndef())) return zv::Val();
			zv::Val activeTypes = pt_template_type_map_get_types(activeTemplateTypeMap.raw());
			if (UNEXPECTED(activeTypes.isUndef())) return zv::Val();
			for (zv::ArrayEntry entry : zv::ArrRef(activeTypes.raw())) {
				zv::Ref templateType = entry.value().deref();
				if (!templateType.instanceOf(templateTypeCe)) continue;
				setByKey(ancestorMapping, entry, zv::Val::copyOf(templateType));
			}
		}

		zv::Arr resolvedTypeMap = zv::Arr::empty();
		if (UNEXPECTED(Z_TYPE_P(newParentTypeClassReflectionHold.raw()) != IS_OBJECT)) {
			zend_throw_error(NULL, "Call to a member function getActiveTemplateTypeMap() on %s", zend_zval_value_name(newParentTypeClassReflectionHold.raw()));
			return zv::Val();
		}
		zv::Val parentActiveTemplateTypeMap = pt_class_reflection_get_active_template_type_map(Z_OBJ_P(newParentTypeClassReflectionHold.raw()));
		if (UNEXPECTED(parentActiveTemplateTypeMap.isUndef())) return zv::Val();
		zv::Val parentTypes = pt_template_type_map_get_types(parentActiveTemplateTypeMap.raw());
		if (UNEXPECTED(parentTypes.isUndef())) return zv::Val();
		for (zv::ArrayEntry entry : zv::ArrRef(parentTypes.raw())) {
			zval *mapped = entry.hasStringKey() ? zend_hash_find(ancestorMapping.table(), entry.stringKey()) : zend_hash_index_find(ancestorMapping.table(), entry.indexKey());
			if (mapped == NULL) continue;

			zv::Val ancestorTemplate = zv::Val::copyOf(zv::Ref(mapped));
			zv::Val type = zv::Val::copyOf(entry.value().deref());
			if (type.ref().instanceOf(pt_ce_unresolved_template_argument_type)) {
				// inferred by the parent constructor under the synthetic node:
				// this node is the site, the child's template the argument
				type = rekeyParentTemplateArgument(type.raw(), node, ancestorTemplate.raw(), frame, allowUnresolved);
				if (UNEXPECTED(type.isUndef())) return zv::Val();
			}
			zv::Val bound = templateGetBound(ancestorTemplate.raw());
			if (UNEXPECTED(bound.isUndef())) return zv::Val();
			if (UNEXPECTED(!bound.ref().isObject())) {
				zend_throw_error(NULL, "Call to a member function isSuperTypeOf() on %s", zend_zval_value_name(bound.raw()));
				return zv::Val();
			}
			zv::Val isSuperType = pt_type_op(Z_OBJ_P(bound.raw()), PT_OP_IS_SUPER_TYPE_OF, 1, type.raw());
			if (UNEXPECTED(isSuperType.isUndef())) return zv::Val();
			zend_long isSuperTypeValue = pt_type_result_trinary(isSuperType.raw());
			if (UNEXPECTED(isSuperTypeValue < 0)) return zv::Val();
			if (isSuperTypeValue != PT_TRI_YES) continue;

			zv::Val templateName = templateGetName(ancestorTemplate.raw());
			if (UNEXPECTED(templateName.isUndef())) return zv::Val();
			if (UNEXPECTED(!requireString(templateName.raw(), "array_key_exists", "key"))) return zv::Val();
			zval *existing = zend_symtable_find(resolvedTypeMap.table(), Z_STR_P(templateName.raw()));
			if (existing == NULL) {
				resolvedTypeMap.set(Z_STR_P(templateName.raw()), std::move(type));
				continue;
			}

			zv::Args unionArgv{existing, type.raw()};
			zv::Val united = pt_type_combinator_union(2, unionArgv);
			if (UNEXPECTED(united.isUndef())) return zv::Val();
			zv::Val againName = templateGetName(ancestorTemplate.raw());
			if (UNEXPECTED(againName.isUndef())) return zv::Val();
			if (UNEXPECTED(!requireString(againName.raw(), "array_key_exists", "key"))) return zv::Val();
			resolvedTypeMap.set(Z_STR_P(againName.raw()), std::move(united));
		}

		zval templateTypeMapZv;
		if (UNEXPECTED(!pt_template_type_map_new(&templateTypeMapZv, resolvedTypeMap.raw()))) return zv::Val();
		zv::Val templateTypeMap = zv::Val::adopt(templateTypeMapZv);
		zv::Val types = pt_class_reflection_type_map_to_list(Z_OBJ_P(classReflection), templateTypeMap.raw());
		if (UNEXPECTED(types.isUndef())) return zv::Val();
		if (isStatic) {
			zv::Arr noVariances = zv::Arr::empty();
			zval genericStatic;
			if (UNEXPECTED(!pt_generic_static_type_new(&genericStatic, classReflection, types.raw(), NULL, noVariances.raw()))) return zv::Val();
			return zv::Val::adopt(genericStatic);
		}

		return genericObjectType(resolvedClassName, types.raw(), classReflection);
	}

	/* $array[$key] = $value keeping the entry's key kind */
	static void setByKey(zv::Arr &array, zv::ArrayEntry &entry, zv::Val value)
	{
		if (entry.hasStringKey()) {
			array.set(entry.stringKey(), std::move(value));
			return;
		}
		array.separate();
		zval item = value.take();
		zend_hash_index_update(array.table(), entry.indexKey(), &item);
	}

	/* static function (Node $node, Scope $scope) use ($classReflection,
	 * &$constructorResult): void — the anonymous class's constructor
	 * gatherer; captures: $classReflection, &$constructorResult */
	static void constructorGathererBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		(void) return_value;
		if (UNEXPECTED(!requireArguments(argc, 2, "PHPStan\\Analyser\\ExprHandler\\NewHandler::{closure}"))) return;
		zval *node = &argv[0];
		int isMethodReturnStatements = isInstanceOf(node, PT_CLASS_METHOD_RETURN_STATEMENTS_NODE);
		if (UNEXPECTED(isMethodReturnStatements < 0) || !isMethodReturnStatements) return;
		zval *constructorResult = &captures[1];
		ZVAL_DEREF(constructorResult);
		if (Z_TYPE_P(constructorResult) != IS_NULL) return;

		zv::Val currentClassReflection = nodeGetClassReflection(node);
		if (UNEXPECTED(currentClassReflection.isUndef())) return;
		zv::Val currentClassName = pt_class_reflection_get_name(Z_OBJ_P(currentClassReflection.raw()));
		if (UNEXPECTED(currentClassName.isUndef())) return;
		zv::Val className = pt_class_reflection_get_name(Z_OBJ(captures[0]));
		if (UNEXPECTED(className.isUndef())) return;
		if (!zend_is_identical(currentClassName.raw(), className.raw())) return;
		bool hasConstructor;
		if (UNEXPECTED(!pt_class_reflection_has_constructor(Z_OBJ_P(currentClassReflection.raw()), hasConstructor))) return;
		if (!hasConstructor) return;
		zv::Val constructor = pt_class_reflection_get_constructor(Z_OBJ_P(currentClassReflection.raw()));
		if (UNEXPECTED(constructor.isUndef())) return;
		zv::Val constructorName = pt_extended_method_reflection_call(constructor.raw(), PT_MR_GET_NAME);
		if (UNEXPECTED(constructorName.isUndef())) return;
		zv::Val methodReflection = nodeGetMethodReflection(node);
		if (UNEXPECTED(methodReflection.isUndef())) return;
		zv::Val methodName = nodeMethodReflectionGetName(methodReflection.raw());
		if (UNEXPECTED(methodName.isUndef())) return;
		if (!zend_is_identical(constructorName.raw(), methodName.raw())) return;

		/* $constructorResult = $node through the reference */
		zval *slot = Z_REFVAL(captures[1]);
		zval old;
		ZVAL_COPY_VALUE(&old, slot);
		ZVAL_COPY(slot, node);
		zval_ptr_dtor(&old);
	}

	/* static function (string $name, Type $type) use ($site, $frame,
	 * $allowUnresolved, $synthetic): Type — captures: $site, $frame,
	 * $allowUnresolved, $synthetic */
	static void unresolvedArgumentMapBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		if (UNEXPECTED(!requireArguments(argc, 2, "PHPStan\\Analyser\\ExprHandler\\NewHandler::{closure}"))) return;
		zend_class_entry *templateTypeCe = pt_class(PT_CLASS_TEMPLATE_TYPE);
		if (UNEXPECTED(templateTypeCe == NULL)) return;
		zval *type = &argv[1];
		if (Z_TYPE_P(type) != IS_OBJECT || !instanceof_function(Z_OBJCE_P(type), templateTypeCe)) {
			ZVAL_COPY(return_value, type);
			return;
		}
		zval *site = &captures[0];
		zval *frame = &captures[1];
		bool allowUnresolved = Z_TYPE(captures[2]) == IS_TRUE;
		bool synthetic = Z_TYPE(captures[3]) == IS_TRUE;
		bool unresolved = synthetic;
		if (!unresolved && allowUnresolved) {
			if (UNEXPECTED(!pt_template_argument_frame_is_observing(frame, unresolved))) return;
		}
		if (unresolved) {
			zval marker;
			if (UNEXPECTED(!pt_unresolved_template_argument_type_new(&marker, site, type, NULL))) return;
			ZVAL_COPY_VALUE(return_value, &marker);
			return;
		}

		zv::Val resolved = pt_template_argument_frame_resolve_or_unconstrained(frame, site, type);
		if (UNEXPECTED(resolved.isUndef())) return;
		resolved.intoReturnValue(return_value);
	}

	/* fn (bool $nativeTypesPromoted): Type => $this->resolveReturnType(
	 * $nativeTypesPromoted ? $beforeScope->doNotTreatPhpDocTypesAsCertain() :
	 * $beforeScope, $expr, $nativeTypesPromoted ? null :
	 * $resolvedParametersAcceptor, $classResult !== null ? ($nativeTypesPromoted
	 * ? $classResult->getNativeType() : $classResult->getType()) : null,
	 * $argsResult, !$nativeTypesPromoted) — captures: $this, $beforeScope,
	 * $expr, $resolvedParametersAcceptor, $classResult, $argsResult */
	static void typeCallbackBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		if (UNEXPECTED(!requireArguments(argc, 1, "PHPStan\\Analyser\\ExprHandler\\NewHandler::{closure}"))) return;
		bool nativeTypesPromoted = zend_is_true(&argv[0]);
		zv::Val promotedScope;
		zval *scope = &captures[1];
		if (nativeTypesPromoted) {
			promotedScope = pt_mutating_scope_do_not_treat_phpdoc_types_as_certain(Z_OBJ(captures[1]));
			if (UNEXPECTED(promotedScope.isUndef())) return;
			scope = promotedScope.raw();
		}
		zv::Val classExprType;
		if (Z_TYPE(captures[4]) != IS_NULL) {
			classExprType = nativeTypesPromoted ? pt_expression_result_get_native_type(&captures[4]) : pt_expression_result_get_type(&captures[4]);
			if (UNEXPECTED(classExprType.isUndef())) return;
		}
		zv::Val type = NewHandler(Z_OBJ(captures[0])).resolveReturnType(scope, &captures[2], nativeTypesPromoted ? NULL : &captures[3], classExprType.isUndef() ? NULL : classExprType.raw(), &captures[5], !nativeTypesPromoted);
		if (UNEXPECTED(type.isUndef())) return;
		type.intoReturnValue(return_value);
	}

	/* fn (TypeSpecifierContext $specifyContext, bool $nativeTypesPromoted): SpecifiedTypes
	 * => $this->specifyTypes($nativeTypesPromoted ? $beforeScope->doNotTreatPhpDocTypesAsCertain()
	 * : $beforeScope, $expr, $resolvedParametersAcceptor, $specifyContext) —
	 * captures: $this, $beforeScope, $expr, $resolvedParametersAcceptor */
	static void specifyTypesCallbackBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		if (UNEXPECTED(!requireArguments(argc, 2, "PHPStan\\Analyser\\ExprHandler\\NewHandler::{closure}"))) return;
		zv::Val promotedScope;
		zval *scope = &captures[1];
		if (zend_is_true(&argv[1])) {
			promotedScope = pt_mutating_scope_do_not_treat_phpdoc_types_as_certain(Z_OBJ(captures[1]));
			if (UNEXPECTED(promotedScope.isUndef())) return;
			scope = promotedScope.raw();
		}
		zv::Val specifiedTypes = NewHandler(Z_OBJ(captures[0])).specifyTypes(scope, &captures[2], &captures[3], &argv[0]);
		if (UNEXPECTED(specifiedTypes.isUndef())) return;
		specifiedTypes.intoReturnValue(return_value);
	}

	/* static fn (Type $type) => TemplateTypeHelper::resolveTemplateTypes($type,
	 * $resolvedParametersAcceptor->getResolvedTemplateTypeMap(), ...,
	 * TemplateTypeVariance::createInvariant()) — captures:
	 * $resolvedParametersAcceptor */
	static void resolveAssertTypeBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		resolveAssertType(captures, argc, argv, return_value, "PHPStan\\Analyser\\ExprHandler\\NewHandler::{closure}");
	}
};

} // namespace phpstanturbo

using phpstanturbo::NewHandler;

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

void pt_register_new_handler()
{
	pt_nh_new = zend_string_init_interned(PT_LC("new"), 1);
	pt_nh_instantiation_of_unknown_class = zend_string_init_interned(PT_LC("instantiation of unknown class"), 1);
	pt_nh_throwable = zend_string_init_interned(PT_LC("Throwable"), 1);
	pt_nh_synthetic_site_attribute = zend_string_init_interned(PT_LC("templateArgumentSyntheticSite"), 1);
	pt_nh_node_scope_resolver_class = zend_string_init_interned(PT_LC("PHPStan\\Analyser\\NodeScopeResolver"), 1);

	reg::Class cls("PHPStan\\Analyser\\ExprHandler\\NewHandler");
	ptdecl::NewHandler::declareClass(cls);
	ptdecl::NewHandler::declareProperties(cls);

	/* the real parameter class names: the DI container autowires the
	 * service by reflecting the constructor */
	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *reflectionProvider, *dynamicStaticMethodThrowTypeExtensions, *dynamicReturnTypeExtensionRegistry, *propertyReflectionFinder, *expressionResultFactory, *defaultNarrowingHelper, *storagePrimer, *container, *argumentsHandler;
		bool implicitThrows;
		ZEND_PARSE_PARAMETERS_START(10, 10)
			Z_PARAM_OBJECT(reflectionProvider)
			Z_PARAM_OBJECT(dynamicStaticMethodThrowTypeExtensions)
			Z_PARAM_OBJECT(dynamicReturnTypeExtensionRegistry)
			Z_PARAM_OBJECT(propertyReflectionFinder)
			Z_PARAM_BOOL(implicitThrows)
			Z_PARAM_OBJECT(expressionResultFactory)
			Z_PARAM_OBJECT(defaultNarrowingHelper)
			Z_PARAM_OBJECT(storagePrimer)
			Z_PARAM_OBJECT(container)
			Z_PARAM_OBJECT(argumentsHandler)
		ZEND_PARSE_PARAMETERS_END();
		zval *services[9] = {reflectionProvider, dynamicStaticMethodThrowTypeExtensions, dynamicReturnTypeExtensionRegistry, propertyReflectionFinder, expressionResultFactory, defaultNarrowingHelper, storagePrimer, container, argumentsHandler};
		NewHandler(Z_OBJ_P(ZEND_THIS)).construct(services, implicitThrows);
	});

	cls.method<&NewHandler::supports, zp::Obj>(sigs::supports);

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
		PT_RETURN_VAL(NewHandler(Z_OBJ_P(ZEND_THIS)).processExpr(nodeScopeResolver, stmt, expr, scope, storage, nodeCallback, context));
	});

	cls.method(sigs::processConstructorReflection, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *className, *expr;
		ZEND_PARSE_PARAMETERS_START(2, 2)
			Z_PARAM_ZVAL(className)
			Z_PARAM_OBJECT(expr)
		ZEND_PARSE_PARAMETERS_END();
		ConstructorReflectionParts parts;
		if (UNEXPECTED(!NewHandler(Z_OBJ_P(ZEND_THIS)).processConstructorReflection(className, expr, parts))) RETURN_THROWS();
		zv::Arr result = zv::Arr::create(3);
		result.push(std::move(parts.constructorReflection));
		result.push(std::move(parts.classReflection));
		result.push(std::move(parts.parametersAcceptor));
		zv::Val(std::move(result)).intoReturnValue(return_value);
	});

	cls.method(sigs::getConstructorImpurePoints, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *constructorReflection, *classReflection, *parametersAcceptor, *expr, *scope, *scopeBeforeArgs;
		bool isDynamic;
		ZEND_PARSE_PARAMETERS_START(7, 7)
			Z_PARAM_OBJECT_OR_NULL(constructorReflection)
			Z_PARAM_OBJECT_OR_NULL(classReflection)
			Z_PARAM_OBJECT_OR_NULL(parametersAcceptor)
			Z_PARAM_OBJECT(expr)
			Z_PARAM_OBJECT(scope)
			Z_PARAM_OBJECT(scopeBeforeArgs)
			Z_PARAM_BOOL(isDynamic)
		ZEND_PARSE_PARAMETERS_END();
		zv::Val point = NewHandler(Z_OBJ_P(ZEND_THIS)).constructorImpurePoint(constructorReflection, classReflection, parametersAcceptor, expr, scope, scopeBeforeArgs, isDynamic);
		if (UNEXPECTED(point.isUndef())) RETURN_THROWS();
		if (point.isNull()) RETURN_EMPTY_ARRAY();
		zv::Arr result = zv::Arr::create(1);
		result.push(std::move(point));
		zv::Val(std::move(result)).intoReturnValue(return_value);
	});

	cls.method(sigs::getConstructorThrowPoint, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *constructorReflection, *parametersAcceptor, *new_, *className, *args, *scope, *context;
		ZEND_PARSE_PARAMETERS_START(7, 7)
			Z_PARAM_OBJECT(constructorReflection)
			Z_PARAM_OBJECT(parametersAcceptor)
			Z_PARAM_OBJECT(new_)
			Z_PARAM_OBJECT(className)
			Z_PARAM_ARRAY(args)
			Z_PARAM_OBJECT(scope)
			Z_PARAM_OBJECT(context)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(NewHandler(Z_OBJ_P(ZEND_THIS)).getConstructorThrowPoint(constructorReflection, parametersAcceptor, new_, className, args, scope, context));
	});

	cls.method(sigs::resolveReturnType, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *scope, *expr, *preResolvedAcceptor, *classExprType, *argsResult = NULL;
		bool allowUnresolved = true;
		ZEND_PARSE_PARAMETERS_START(4, 6)
			Z_PARAM_OBJECT(scope)
			Z_PARAM_OBJECT(expr)
			Z_PARAM_OBJECT_OR_NULL(preResolvedAcceptor)
			Z_PARAM_OBJECT_OR_NULL(classExprType)
			Z_PARAM_OPTIONAL
			Z_PARAM_OBJECT_OR_NULL(argsResult)
			Z_PARAM_BOOL(allowUnresolved)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(NewHandler(Z_OBJ_P(ZEND_THIS)).resolveReturnType(scope, expr, preResolvedAcceptor, classExprType, argsResult, allowUnresolved));
	});

	cls.method(sigs::exactInstantiation, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *scope, *node, *className, *preResolvedAcceptor, *argsResult = NULL;
		bool allowUnresolved = true;
		ZEND_PARSE_PARAMETERS_START(4, 6)
			Z_PARAM_OBJECT(scope)
			Z_PARAM_OBJECT(node)
			Z_PARAM_OBJECT(className)
			Z_PARAM_OBJECT_OR_NULL(preResolvedAcceptor)
			Z_PARAM_OPTIONAL
			Z_PARAM_OBJECT_OR_NULL(argsResult)
			Z_PARAM_BOOL(allowUnresolved)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(NewHandler(Z_OBJ_P(ZEND_THIS)).exactInstantiation(scope, node, className, preResolvedAcceptor, argsResult, allowUnresolved));
	});

	cls.method(sigs::unresolvedArgumentList, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *classReflection, *site, *frame;
		bool allowUnresolved;
		ZEND_PARSE_PARAMETERS_START(4, 4)
			Z_PARAM_OBJECT(classReflection)
			Z_PARAM_OBJECT(site)
			Z_PARAM_OBJECT_OR_NULL(frame)
			Z_PARAM_BOOL(allowUnresolved)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(NewHandler::unresolvedArgumentList(classReflection, site, frame, allowUnresolved));
	});

	cls.method(sigs::rekeyParentTemplateArgument, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *type, *site, *templateType, *frame;
		bool allowUnresolved;
		ZEND_PARSE_PARAMETERS_START(5, 5)
			Z_PARAM_OBJECT(type)
			Z_PARAM_OBJECT(site)
			Z_PARAM_OBJECT(templateType)
			Z_PARAM_OBJECT_OR_NULL(frame)
			Z_PARAM_BOOL(allowUnresolved)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(NewHandler::rekeyParentTemplateArgument(type, site, templateType, frame, allowUnresolved));
	});

	cls.method(sigs::specifyTypes, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *scope, *expr, *resolvedParametersAcceptor, *context;
		ZEND_PARSE_PARAMETERS_START(4, 4)
			Z_PARAM_OBJECT(scope)
			Z_PARAM_OBJECT(expr)
			Z_PARAM_OBJECT_OR_NULL(resolvedParametersAcceptor)
			Z_PARAM_OBJECT(context)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(NewHandler(Z_OBJ_P(ZEND_THIS)).specifyTypes(scope, expr, resolvedParametersAcceptor, context));
	});

	cls.shadow(&pt_ce_new_handler);
	pt_expr_handler_entry_register(&pt_ce_new_handler, &NewHandler::processExprEntry);
}

/* }}} */
