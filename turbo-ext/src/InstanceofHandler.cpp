/*
 * PHPStanTurbo\InstanceofHandler — native implementation of
 * PHPStan\Analyser\ExprHandler\InstanceofHandler.
 *
 * A DI service (#[AutowiredService]): the constructor keeps the twin's
 * arginfo so Nette autowires it. processExpr() is registered as the class's
 * handler entry (Engine.h). The twin's closures are native closures capturing
 * what the PHP closures capture: the typeCallback ($expr, $exprResult,
 * $classResult, $isInTrait, $nameClassType) and the specifyTypesCallback
 * ($this, $expr, $exprResult, $classResult, $nameNarrowType, $beforeScope).
 *
 * NodeScopeResolver, MutatingScope, ExpressionResult, ExpressionContext,
 * VariableFlow, SpecifiedTypes, TypeSpecifierContext, DefaultNarrowingHelper,
 * ClassReflection, TypeUtils, TypeCombinator and the Type kernel are called
 * through their direct entries; ClassReflection::getParentClass() and
 * Type::toObjectTypeForInstanceofCheck() by name (no direct entry), the
 * ClassNameToObjectTypeResult's public readonly properties through property
 * sites.
 */

#include "support.h"
#include "generated/InstanceofHandler.h"

namespace slots = ptdecl::InstanceofHandler::slot;
namespace sigs = ptdecl::InstanceofHandler::sig;
#include "CallHandlerSupport.h"

zend_class_entry *pt_ce_instanceof_handler = nullptr;

namespace {

using namespace ptcall;

constexpr const char *pt_ioh_closure_name = "PHPStan\\Analyser\\ExprHandler\\InstanceofHandler::{closure}";

/* {{{ the PhpParser nodes' properties */

pt_property_site pt_ioh_expr_site;
pt_property_site pt_ioh_class_site;
pt_property_site pt_ioh_name_name_site;
pt_property_site pt_ioh_result_type_site;
pt_property_site pt_ioh_result_uncertainty_site;

zval *exprExpr(zval *expr) { return nodeProperty(pt_ioh_expr_site, expr, PT_LC("expr")); }
zval *exprClass(zval *expr) { return nodeProperty(pt_ioh_class_site, expr, PT_LC("class")); }
/* $name->toString() / (string) $name */
zval *nameString(zval *name) { return nodeProperty(pt_ioh_name_name_site, name, PT_LC("name")); }

/* }}} */

/* {{{ small value helpers */

/* new BooleanType() / new ConstantBooleanType($value) */
zv::Val newBooleanType()
{
	zval out;
	if (UNEXPECTED(!pt_boolean_type_new(&out))) return zv::Val();
	return zv::Val::adopt(out);
}

zv::Val newConstantBooleanType(bool value)
{
	zval out;
	if (UNEXPECTED(!pt_constant_boolean_type_new(&out, value))) return zv::Val();
	return zv::Val::adopt(out);
}

/* new ObjectType($className) */
zv::Val newObjectType(zval *className)
{
	if (UNEXPECTED(Z_TYPE_P(className) != IS_STRING)) {
		zend_type_error("PHPStan\\Type\\ObjectType::__construct(): Argument #1 ($className) must be of type string, %s given", zend_zval_value_name(className));
		return zv::Val();
	}
	zval out;
	if (UNEXPECTED(!pt_object_type_new(&out, Z_STR_P(className)))) return zv::Val();
	return zv::Val::adopt(out);
}

/* new StaticType($classReflection) */
zv::Val newStaticType(zval *classReflection)
{
	zval out;
	if (UNEXPECTED(!pt_static_type_new(&out, classReflection))) return zv::Val();
	return zv::Val::adopt(out);
}

/* the TypeSpecifierContext argument's flag; false = pending exception */
[[nodiscard]] bool contextFlag(zval *context, bool (*read)(zend_object *, bool &), const char *method, bool &out)
{
	if (UNEXPECTED(Z_TYPE_P(context) != IS_OBJECT)) {
		zend_throw_error(NULL, "Call to a member function %s() on %s", method, zend_zval_value_name(context));
		return false;
	}
	return read(Z_OBJ_P(context), out);
}

/* $type->isSuperTypeOf($other) as a PT_TRI_* value; -1 = pending exception */
zend_long isSuperTypeOfValue(zval *type, zval *other)
{
	if (UNEXPECTED(Z_TYPE_P(type) != IS_OBJECT)) {
		zend_throw_error(NULL, "Call to a member function isSuperTypeOf() on %s", zend_zval_value_name(type));
		return -1;
	}
	zv::Val result = pt_type_op(Z_OBJ_P(type), PT_OP_IS_SUPER_TYPE_OF, 1, other);
	if (UNEXPECTED(result.isUndef())) return -1;
	return pt_type_result_trinary(result.raw());
}

/* $classType->isSuperTypeOf(new MixedType())->yes(); false = pending exception */
[[nodiscard]] bool acceptsMixed(zval *classType, bool &out)
{
	zv::Val mixed = pt_type_new_mixed_type();
	if (UNEXPECTED(mixed.isUndef())) return false;
	zend_long value = isSuperTypeOfValue(classType, mixed.raw());
	if (UNEXPECTED(value < 0)) return false;
	out = value == PT_TRI_YES;
	return true;
}

/* $classNameType->toObjectTypeForInstanceofCheck(): its ->type (owned) and
 * ->uncertainty; false = pending exception */
[[nodiscard]] bool objectTypeForInstanceofCheck(zval *classNameType, zv::Val &type, bool &uncertainty)
{
	if (UNEXPECTED(Z_TYPE_P(classNameType) != IS_OBJECT)) {
		zend_throw_error(NULL, "Call to a member function toObjectTypeForInstanceofCheck() on %s", zend_zval_value_name(classNameType));
		return false;
	}
	zv::Val result = pt_type_call(Z_OBJ_P(classNameType), PT_LC("toobjecttypeforinstanceofcheck"), 0, NULL);
	if (UNEXPECTED(result.isUndef())) return false;
	if (UNEXPECTED(!result.ref().isObject())) {
		zend_throw_error(NULL, "Attempt to read property \"type\" on %s", zend_zval_value_name(result.raw()));
		return false;
	}
	zval *typeSlot = nodeProperty(pt_ioh_result_type_site, result.raw(), PT_LC("type"));
	if (UNEXPECTED(typeSlot == NULL)) return false;
	type = zv::Val::copyOf(zv::Ref(typeSlot));
	zval *uncertaintySlot = nodeProperty(pt_ioh_result_uncertainty_site, result.raw(), PT_LC("uncertainty"));
	if (UNEXPECTED(uncertaintySlot == NULL)) return false;
	uncertainty = zend_is_true(uncertaintySlot);
	return true;
}

/* $specifiedTypes->setRootExpr($expr) */
zv::Val setRootExpr(zv::Val &specifiedTypes, zval *expr)
{
	if (UNEXPECTED(specifiedTypes.isUndef())) return zv::Val();
	if (UNEXPECTED(!specifiedTypes.ref().isObject())) {
		zend_throw_error(NULL, "Call to a member function setRootExpr() on %s", zend_zval_value_name(specifiedTypes.raw()));
		return zv::Val();
	}
	return pt_specified_types_set_root_expr(Z_OBJ_P(specifiedTypes.raw()), expr);
}

/* }}} */

} // namespace

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\ExprHandler\InstanceofHandler; UNDEF = pending
 * exception. */
class InstanceofHandler
{
public:
	explicit InstanceofHandler(zend_object *self) : self(self) {}

	/* the constructor body: the promoted properties */
	void construct(zval *expressionResultFactory, zval *defaultNarrowingHelper) const
	{
		pt_write_slot(self, slots::expressionResultFactory, expressionResultFactory);
		pt_write_slot(self, slots::defaultNarrowingHelper, defaultNarrowingHelper);
	}

	/* Mirrors supports(); false = pending exception */
	[[nodiscard]] bool supports(zval *expr, bool &out) const
	{
		int is = isInstanceOf(expr, PT_CLASS_INSTANCEOF_EXPR);
		if (UNEXPECTED(is < 0)) return false;
		out = is == 1;
		return true;
	}

	/* Mirrors processExpr(). */
	zv::Val processExpr(zval *nodeScopeResolver, zval *stmt, zval *expr, zval *scopeArg, zval *storage, zval *nodeCallback, zval *context) const
	{
		zval *beforeScope = scopeArg;
		zval *subject = exprExpr(expr);
		if (UNEXPECTED(subject == NULL)) return zv::Val();
		zv::Val exprResult;
		{
			zv::Val deepContext = pt_expression_context_enter_deep(context);
			if (UNEXPECTED(deepContext.isUndef())) return zv::Val();
			exprResult = pt_node_scope_resolver_process_expr_node(nodeScopeResolver, stmt, subject, scopeArg, storage, nodeCallback, deepContext.raw());
			if (UNEXPECTED(exprResult.isUndef())) return zv::Val();
		}
		zv::Val hold;
		bool hasYield;
		if (UNEXPECTED(!pt_expression_result_has_yield(exprResult.raw(), hasYield))) return zv::Val();
		zval *borrowed = pt_expression_result_throw_points(exprResult.raw(), hold);
		if (UNEXPECTED(borrowed == NULL)) return zv::Val();
		zv::Val throwPoints = zv::Val::copyOf(zv::Ref(borrowed));
		borrowed = pt_expression_result_impure_points(exprResult.raw(), hold);
		if (UNEXPECTED(borrowed == NULL)) return zv::Val();
		zv::Val impurePoints = zv::Val::copyOf(zv::Ref(borrowed));
		bool isAlwaysTerminating;
		if (UNEXPECTED(!pt_expression_result_is_always_terminating(exprResult.raw(), isAlwaysTerminating))) return zv::Val();
		borrowed = pt_expression_result_scope(exprResult.raw(), hold);
		if (UNEXPECTED(borrowed == NULL)) return zv::Val();
		zv::Val scope = zv::Val::copyOf(zv::Ref(borrowed));

		zval *classNode = exprClass(expr);
		if (UNEXPECTED(classNode == NULL)) return zv::Val();
		int classIsName = isInstanceOf(classNode, PT_CLASS_NAME);
		if (UNEXPECTED(classIsName < 0)) return zv::Val();
		zv::Val classResult;
		if (!classIsName) {
			zv::Val deepContext = pt_expression_context_enter_deep(context);
			if (UNEXPECTED(deepContext.isUndef())) return zv::Val();
			classResult = pt_node_scope_resolver_process_expr_node(nodeScopeResolver, stmt, classNode, scope.raw(), storage, nodeCallback, deepContext.raw());
			if (UNEXPECTED(classResult.isUndef())) return zv::Val();
			borrowed = pt_expression_result_scope(classResult.raw(), hold);
			if (UNEXPECTED(borrowed == NULL)) return zv::Val();
			scope = zv::Val::copyOf(zv::Ref(borrowed));
			if (!hasYield && UNEXPECTED(!pt_expression_result_has_yield(classResult.raw(), hasYield))) return zv::Val();
			borrowed = pt_expression_result_throw_points(classResult.raw(), hold);
			if (UNEXPECTED(borrowed == NULL)) return zv::Val();
			throwPoints = arrayMerge(throwPoints.raw(), borrowed);
			borrowed = pt_expression_result_impure_points(classResult.raw(), hold);
			if (UNEXPECTED(borrowed == NULL)) return zv::Val();
			impurePoints = arrayMerge(impurePoints.raw(), borrowed);
			if (!isAlwaysTerminating && UNEXPECTED(!pt_expression_result_is_always_terminating(classResult.raw(), isAlwaysTerminating))) return zv::Val();
		}

		// a class side written as a Name is lexical: resolve the boolean-result
		// class type and the narrowing type once here
		bool isInTrait;
		if (UNEXPECTED(!pt_mutating_scope_is_in_trait(Z_OBJ_P(beforeScope), isInTrait))) return zv::Val();
		zv::Val nameClassType = zv::Val::null();
		zv::Val nameNarrowType = zv::Val::null();
		classNode = exprClass(expr);
		if (UNEXPECTED(classNode == NULL)) return zv::Val();
		classIsName = isInstanceOf(classNode, PT_CLASS_NAME);
		if (UNEXPECTED(classIsName < 0)) return zv::Val();
		if (classIsName) {
			if (UNEXPECTED(!resolveNameTypes(beforeScope, classNode, nameClassType, nameNarrowType))) return zv::Val();
		}

		zv::Val variableFlow;
		{
			zv::Val exprFlow = pt_expression_result_variable_flow(exprResult.raw());
			if (UNEXPECTED(exprFlow.isUndef())) return zv::Val();
			zv::Val classFlow = zv::Val::null();
			if (!classResult.isUndef()) {
				classFlow = pt_expression_result_variable_flow(classResult.raw());
				if (UNEXPECTED(classFlow.isUndef())) return zv::Val();
			}
			zv::Args flows{exprFlow.raw(), classFlow.raw()};
			variableFlow = pt_variable_flow_sequence(2, flows);
			if (UNEXPECTED(variableFlow.isUndef())) return zv::Val();
		}

		zval null;
		ZVAL_NULL(&null);
		zval *classResultCapture = classResult.isUndef() ? &null : classResult.raw();
		zv::Val typeCallback = pt_native_closure(&typeCallbackBody, expr, exprResult.raw(), classResultCapture, isInTrait, nameClassType.raw());
		zv::Val specifyTypesCallback = pt_native_closure(&specifyTypesCallbackBody, self, expr, exprResult.raw(), classResultCapture, nameNarrowType.raw(), beforeScope);

		pt_expression_result_args args(scope.raw(), beforeScope, expr, hasYield, isAlwaysTerminating, throwPoints.raw(), impurePoints.raw(), typeCallback.raw(), specifyTypesCallback.raw());
		args.withVariableFlow(variableFlow.raw());
		return pt_expression_result_create(OBJ_PROP_NUM(self, slots::expressionResultFactory), args);
	}

	/* the handler entry (Engine.h) */
	static zv::Val processExprEntry(zend_object *handler, zval *nodeScopeResolver, zval *stmt, zval *expr, zval *scope, zval *storage, zval *nodeCallback, zval *context)
	{
		return InstanceofHandler(handler).processExpr(nodeScopeResolver, stmt, expr, scope, storage, nodeCallback, context);
	}

private:
	zend_object *self;

	/* the Name branch of processExpr(): $nameClassType and $nameNarrowType;
	 * false = pending exception */
	[[nodiscard]] static bool resolveNameTypes(zval *beforeScope, zval *classNode, zv::Val &nameClassType, zv::Val &nameNarrowType)
	{
		zval *className = nameString(classNode);
		if (UNEXPECTED(className == NULL)) return false;
		if (UNEXPECTED(Z_TYPE_P(className) != IS_STRING)) {
			zend_type_error("PhpParser\\Node\\Name::toString(): Return value must be of type string, %s returned", zend_zval_value_name(className));
			return false;
		}
		zend_string *name = Z_STR_P(className);
		bool isStatic = zend_string_equals_literal_ci(name, "static");
		bool isInClass = false;
		if (isStatic) {
			if (UNEXPECTED(!pt_mutating_scope_is_in_class(Z_OBJ_P(beforeScope), isInClass))) return false;
		}
		if (isStatic && isInClass) {
			zv::Val classReflection = pt_mutating_scope_get_class_reflection(Z_OBJ_P(beforeScope));
			if (UNEXPECTED(classReflection.isUndef())) return false;
			nameClassType = newStaticType(classReflection.raw());
		} else {
			zv::Val resolved = pt_mutating_scope_resolve_name(Z_OBJ_P(beforeScope), Z_OBJ_P(classNode));
			if (UNEXPECTED(resolved.isUndef())) return false;
			nameClassType = newObjectType(resolved.raw());
		}
		if (UNEXPECTED(nameClassType.isUndef())) return false;

		// (string) $expr->class, lowercased
		if (zend_string_equals_literal_ci(name, "self")) {
			if (UNEXPECTED(!pt_mutating_scope_is_in_class(Z_OBJ_P(beforeScope), isInClass))) return false;
			if (isInClass) {
				zv::Val classReflection = pt_mutating_scope_get_class_reflection(Z_OBJ_P(beforeScope));
				if (UNEXPECTED(classReflection.isUndef())) return false;
				if (UNEXPECTED(!classReflection.ref().isObject())) {
					zend_throw_error(NULL, "Call to a member function getName() on %s", zend_zval_value_name(classReflection.raw()));
					return false;
				}
				zv::Val reflectionName = pt_class_reflection_get_name(Z_OBJ_P(classReflection.raw()));
				if (UNEXPECTED(reflectionName.isUndef())) return false;
				nameNarrowType = newObjectType(reflectionName.raw());
				return !nameNarrowType.isUndef();
			}
		} else if (isStatic) {
			if (isInClass) {
				zv::Val classReflection = pt_mutating_scope_get_class_reflection(Z_OBJ_P(beforeScope));
				if (UNEXPECTED(classReflection.isUndef())) return false;
				nameNarrowType = newStaticType(classReflection.raw());
				return !nameNarrowType.isUndef();
			}
		} else if (zend_string_equals_literal_ci(name, "parent")) {
			if (UNEXPECTED(!pt_mutating_scope_is_in_class(Z_OBJ_P(beforeScope), isInClass))) return false;
			if (isInClass) {
				zv::Val parentClass = parentClassOf(beforeScope);
				if (UNEXPECTED(parentClass.isUndef())) return false;
				if (!parentClass.isNull()) {
					// $beforeScope->getClassReflection()->getParentClass()->getName()
					parentClass = parentClassOf(beforeScope);
					if (UNEXPECTED(parentClass.isUndef())) return false;
					if (UNEXPECTED(!parentClass.ref().isObject())) {
						zend_throw_error(NULL, "Call to a member function getName() on %s", zend_zval_value_name(parentClass.raw()));
						return false;
					}
					zv::Val parentName = pt_class_reflection_get_name(Z_OBJ_P(parentClass.raw()));
					if (UNEXPECTED(parentName.isUndef())) return false;
					nameNarrowType = newObjectType(parentName.raw());
					return !nameNarrowType.isUndef();
				}
			}
			zval out;
			if (UNEXPECTED(!pt_nonexistent_parent_class_type_new(&out))) return false;
			nameNarrowType = zv::Val::adopt(out);
			return true;
		}
		nameNarrowType = newObjectType(className);
		return !nameNarrowType.isUndef();
	}

	/* $beforeScope->getClassReflection()->getParentClass() */
	static zv::Val parentClassOf(zval *beforeScope)
	{
		zv::Val classReflection = pt_mutating_scope_get_class_reflection(Z_OBJ_P(beforeScope));
		if (UNEXPECTED(classReflection.isUndef())) return zv::Val();
		if (UNEXPECTED(!classReflection.ref().isObject())) {
			zend_throw_error(NULL, "Call to a member function getParentClass() on %s", zend_zval_value_name(classReflection.raw()));
			return zv::Val();
		}
		return pt_type_call(Z_OBJ_P(classReflection.raw()), PT_LC("getparentclass"), 0, NULL);
	}

	/* static function (bool $nativeTypesPromoted) use ($expr, $exprResult,
	 * $classResult, $isInTrait, $nameClassType): Type — captures: $expr,
	 * $exprResult, $classResult, $isInTrait, $nameClassType */
	static zv::Val resolveType(zval *captures, bool nativeTypesPromoted)
	{
		zval *expr = &captures[0];
		zval *exprResult = &captures[1];
		zval *classResult = &captures[2];
		bool isInTrait = Z_TYPE(captures[3]) == IS_TRUE;
		zval *nameClassType = &captures[4];

		zv::Val expressionType = nativeTypesPromoted ? pt_expression_result_get_native_type(exprResult) : pt_expression_result_get_type(exprResult);
		if (UNEXPECTED(expressionType.isUndef())) return zv::Val();
		if (isInTrait) {
			zv::Val thisType = pt_type_utils_find_this_type(expressionType.raw());
			if (UNEXPECTED(thisType.isUndef())) return zv::Val();
			if (!thisType.isNull()) return newBooleanType();
		}
		if (expressionType.ref().instanceOf(pt_ce_never_type)) return newConstantBooleanType(false);

		bool uncertainty = false;
		zv::Val classType;
		zval *classNode = exprClass(expr);
		if (UNEXPECTED(classNode == NULL)) return zv::Val();
		int classIsName = isInstanceOf(classNode, PT_CLASS_NAME);
		if (UNEXPECTED(classIsName < 0)) return zv::Val();
		if (classIsName) {
			if (UNEXPECTED(Z_TYPE_P(nameClassType) == IS_NULL)) {
				pt_throw_should_not_happen();
				return zv::Val();
			}
			classType = zv::Val::copyOf(zv::Ref(nameClassType));
		} else {
			if (UNEXPECTED(Z_TYPE_P(classResult) == IS_NULL)) {
				pt_throw_should_not_happen();
				return zv::Val();
			}
			zv::Val classNameType = nativeTypesPromoted ? pt_expression_result_get_native_type(classResult) : pt_expression_result_get_type(classResult);
			if (UNEXPECTED(classNameType.isUndef())) return zv::Val();
			if (UNEXPECTED(!objectTypeForInstanceofCheck(classNameType.raw(), classType, uncertainty))) return zv::Val();
		}

		bool mixedYes;
		if (UNEXPECTED(!acceptsMixed(classType.raw(), mixedYes))) return zv::Val();
		if (mixedYes) return newBooleanType();

		zend_long isSuperType = isSuperTypeOfValue(classType.raw(), expressionType.raw());
		if (UNEXPECTED(isSuperType < 0)) return zv::Val();
		if (isSuperType == PT_TRI_NO) return newConstantBooleanType(false);
		if (isSuperType == PT_TRI_YES && !uncertainty) return newConstantBooleanType(true);

		return newBooleanType();
	}

	static void typeCallbackBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		if (UNEXPECTED(!requireArguments(argc, 1, pt_ioh_closure_name))) return;
		zv::Val type = resolveType(captures, zend_is_true(&argv[0]));
		if (UNEXPECTED(type.isUndef())) return;
		type.intoReturnValue(return_value);
	}

	/* function (TypeSpecifierContext $context, bool $nativeTypesPromoted) use
	 * ($expr, $exprResult, $classResult, $nameNarrowType, $beforeScope):
	 * SpecifiedTypes — captures: $this, $expr, $exprResult, $classResult,
	 * $nameNarrowType, $beforeScope */
	static zv::Val specifyTypes(zval *captures, zval *context, bool nativeTypesPromoted)
	{
		zval *defaultNarrowingHelper = OBJ_PROP_NUM(Z_OBJ(captures[0]), slots::defaultNarrowingHelper);
		zval *expr = &captures[1];
		zval *exprResult = &captures[2];
		zval *classResult = &captures[3];
		zval *nameNarrowType = &captures[4];
		zval *beforeScope = &captures[5];

		zv::Val promotedScope;
		zval *s = beforeScope;
		if (nativeTypesPromoted) {
			promotedScope = pt_mutating_scope_do_not_treat_phpdoc_types_as_certain(Z_OBJ_P(beforeScope));
			if (UNEXPECTED(promotedScope.isUndef())) return zv::Val();
			s = promotedScope.raw();
		}
		zval *exprNode = exprExpr(expr);
		if (UNEXPECTED(exprNode == NULL)) return zv::Val();
		zval *classNode = exprClass(expr);
		if (UNEXPECTED(classNode == NULL)) return zv::Val();
		int classIsName = isInstanceOf(classNode, PT_CLASS_NAME);
		if (UNEXPECTED(classIsName < 0)) return zv::Val();
		if (classIsName) {
			if (UNEXPECTED(Z_TYPE_P(nameNarrowType) == IS_NULL)) {
				pt_throw_should_not_happen();
				return zv::Val();
			}
			zv::Val subjectTypes = pt_default_narrowing_helper_create_subject_types(defaultNarrowingHelper, s, exprNode, exprResult, nameNarrowType, context);
			return setRootExpr(subjectTypes, expr);
		}

		if (UNEXPECTED(Z_TYPE_P(classResult) == IS_NULL)) {
			pt_throw_should_not_happen();
			return zv::Val();
		}
		zv::Val classNameType = pt_expression_result_get_type_on_scope(classResult, s, nativeTypesPromoted);
		if (UNEXPECTED(classNameType.isUndef())) return zv::Val();
		zv::Val type;
		bool uncertainty;
		if (UNEXPECTED(!objectTypeForInstanceofCheck(classNameType.raw(), type, uncertainty))) return zv::Val();

		bool mixedYes;
		if (UNEXPECTED(!acceptsMixed(type.raw(), mixedYes))) return zv::Val();
		if (!mixedYes) {
			bool isTrueContext;
			if (UNEXPECTED(!contextFlag(context, pt_type_specifier_context_true, "true", isTrueContext))) return zv::Val();
			if (isTrueContext) {
				zv::Val objectWithoutClass = pt_type_new_object_without_class_type();
				if (UNEXPECTED(objectWithoutClass.isUndef())) return zv::Val();
				zv::Args intersectArgv{type.raw(), objectWithoutClass.raw()};
				type = pt_type_combinator_intersect(2, intersectArgv);
				if (UNEXPECTED(type.isUndef())) return zv::Val();
				zv::Val subjectTypes = pt_default_narrowing_helper_create_subject_types(defaultNarrowingHelper, s, exprNode, exprResult, type.raw(), context);
				return setRootExpr(subjectTypes, expr);
			}
			bool isFalseContext;
			if (UNEXPECTED(!contextFlag(context, pt_type_specifier_context_false, "false", isFalseContext))) return zv::Val();
			if (isFalseContext && !uncertainty) {
				zv::Val exprType = pt_expression_result_get_type_on_scope(exprResult, s, nativeTypesPromoted);
				if (UNEXPECTED(exprType.isUndef())) return zv::Val();
				zend_long isSuperType = isSuperTypeOfValue(type.raw(), exprType.raw());
				if (UNEXPECTED(isSuperType < 0)) return zv::Val();
				if (isSuperType != PT_TRI_YES) {
					zv::Val subjectTypes = pt_default_narrowing_helper_create_subject_types(defaultNarrowingHelper, s, exprNode, exprResult, type.raw(), context);
					return setRootExpr(subjectTypes, expr);
				}
			}
		}
		bool isTrueContext;
		if (UNEXPECTED(!contextFlag(context, pt_type_specifier_context_true, "true", isTrueContext))) return zv::Val();
		if (isTrueContext) {
			zv::Val objectWithoutClass = pt_type_new_object_without_class_type();
			if (UNEXPECTED(objectWithoutClass.isUndef())) return zv::Val();
			zv::Val subjectTypes = pt_default_narrowing_helper_create_subject_types(defaultNarrowingHelper, s, exprNode, exprResult, objectWithoutClass.raw(), context);
			return setRootExpr(subjectTypes, exprNode);
		}

		return pt_specified_types_new_with_root_expr(NULL, NULL, expr);
	}

	static void specifyTypesCallbackBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		if (UNEXPECTED(!requireArguments(argc, 2, pt_ioh_closure_name))) return;
		zv::Val specifiedTypes = specifyTypes(captures, &argv[0], zend_is_true(&argv[1]));
		if (UNEXPECTED(specifiedTypes.isUndef())) return;
		specifiedTypes.intoReturnValue(return_value);
	}
};

} // namespace phpstanturbo

using phpstanturbo::InstanceofHandler;

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

PT_MINIT_REGISTRATION(pt_register_instanceof_handler)
{
	reg::Class cls("PHPStan\\Analyser\\ExprHandler\\InstanceofHandler");
	ptdecl::InstanceofHandler::declareClass(cls);
	ptdecl::InstanceofHandler::declareProperties(cls);

	/* the real parameter class names: the DI container autowires the
	 * service by reflecting the constructor */
	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *expressionResultFactory, *defaultNarrowingHelper;
		if (!zp::parse<zp::Obj, zp::Obj>(execute_data, expressionResultFactory, defaultNarrowingHelper)) RETURN_THROWS();
		InstanceofHandler(Z_OBJ_P(ZEND_THIS)).construct(expressionResultFactory, defaultNarrowingHelper);
	});

	cls.method<&InstanceofHandler::supports, zp::Obj>(sigs::supports);

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
		PT_RETURN_VAL(InstanceofHandler(Z_OBJ_P(ZEND_THIS)).processExpr(nodeScopeResolver, stmt, expr, scope, storage, nodeCallback, context));
	});

	cls.shadow(&pt_ce_instanceof_handler);
	pt_expr_handler_entry_register(&pt_ce_instanceof_handler, &InstanceofHandler::processExprEntry);
}

/* }}} */
