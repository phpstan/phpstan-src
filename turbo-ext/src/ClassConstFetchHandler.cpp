/*
 * PHPStanTurbo\ClassConstFetchHandler — native implementation of
 * PHPStan\Analyser\ExprHandler\ClassConstFetchHandler.
 *
 * A DI service (#[AutowiredService]): the constructor keeps the twin's
 * arginfo so Nette autowires it. processExpr() is registered as the class's
 * handler entry (Engine.h). The twin's closures are native closures capturing
 * what the PHP closures capture: the typeCallback ($this, $expr, $classResult,
 * $classReflection) and the specifyTypesCallback ($this, $expr); the
 * class-type callback it hands to InitializerExprTypeResolver ($classResult,
 * $nativeTypesPromoted) is a pt_ietr_get_type over a stack capture array (the
 * resolver calls it synchronously).
 *
 * NodeScopeResolver, MutatingScope, ExpressionResult, ExpressionContext,
 * VariableFlow, DefaultNarrowingHelper and InitializerExprTypeResolver are
 * called through their direct entries.
 */

#include "support.h"
#include "generated/ClassConstFetchHandler.h"

namespace slots = ptdecl::ClassConstFetchHandler::slot;
namespace sigs = ptdecl::ClassConstFetchHandler::sig;
#include "CallHandlerSupport.h"

zend_class_entry *pt_ce_class_const_fetch_handler = nullptr;

namespace {

using namespace ptcall;

constexpr const char *pt_ccfh_closure_name = "PHPStan\\Analyser\\ExprHandler\\ClassConstFetchHandler::{closure}";

/* {{{ the PHP collaborators (one site each; switch to their direct entries
 * once they are ported) */

/* $initializerExprTypeResolver->getClassConstFetchTypeByReflection($class,
 * $constantName, $classReflection, $getTypeCallback) */
zv::Val getClassConstFetchTypeByReflection(zval *initializerExprTypeResolver, zval *class_, zval *constantName, zval *classReflection, const pt_ietr_get_type &getTypeCallback)
{
	return pt_initializer_expr_type_resolver_get_class_const_fetch_type_by_reflection(initializerExprTypeResolver, class_, constantName, classReflection, getTypeCallback);
}

/* }}} */

/* {{{ the PhpParser nodes' properties */

pt_property_site pt_ccfh_class_site;
pt_property_site pt_ccfh_name_site;
pt_property_site pt_ccfh_identifier_name_site;

zval *exprClass(zval *expr) { return nodeProperty(pt_ccfh_class_site, expr, PT_LC("class")); }
zval *exprName(zval *expr) { return nodeProperty(pt_ccfh_name_site, expr, PT_LC("name")); }
zval *identifierName(zval *identifier) { return nodeProperty(pt_ccfh_identifier_name_site, identifier, PT_LC("name")); }

/* }}} */

} // namespace

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\ExprHandler\ClassConstFetchHandler; UNDEF =
 * pending exception. */
class ClassConstFetchHandler
{
public:
	explicit ClassConstFetchHandler(zend_object *self) : self(self) {}

	/* the constructor body: the promoted properties */
	void construct(zval *initializerExprTypeResolver, zval *expressionResultFactory, zval *defaultNarrowingHelper) const
	{
		pt_write_slot(self, slots::initializerExprTypeResolver, initializerExprTypeResolver);
		pt_write_slot(self, slots::expressionResultFactory, expressionResultFactory);
		pt_write_slot(self, slots::defaultNarrowingHelper, defaultNarrowingHelper);
	}

	/* Mirrors supports(); false = pending exception */
	[[nodiscard]] bool supports(zval *expr, bool &out) const
	{
		int is = isInstanceOf(expr, PT_CLASS_CLASS_CONST_FETCH);
		if (UNEXPECTED(is < 0)) return false;
		out = is == 1;
		return true;
	}

	/* Mirrors processExpr(). */
	zv::Val processExpr(zval *nodeScopeResolver, zval *stmt, zval *expr, zval *scopeArg, zval *storage, zval *nodeCallback, zval *context) const
	{
		zval *beforeScope = scopeArg;
		zv::Val scope = zv::Val::copyOf(zv::Ref(scopeArg));
		bool hasYield = false;
		zv::Val throwPoints = zv::Val(zv::Arr::empty());
		zv::Val impurePoints = zv::Val(zv::Arr::empty());
		bool isAlwaysTerminating = false;

		zv::Val classResult = zv::Val::null();
		zv::Val nameResult = zv::Val::null();
		zv::Val hold;
		zval *class_ = exprClass(expr);
		if (UNEXPECTED(class_ == NULL)) return zv::Val();
		int classIsExpr = isInstanceOf(class_, PT_CLASS_EXPR);
		if (UNEXPECTED(classIsExpr < 0)) return zv::Val();
		if (classIsExpr) {
			zv::Val classContext = pt_expression_context_enter_deep(context);
			if (UNEXPECTED(classContext.isUndef())) return zv::Val();
			classResult = pt_node_scope_resolver_process_expr_node(nodeScopeResolver, stmt, class_, scope.raw(), storage, nodeCallback, classContext.raw());
			if (UNEXPECTED(classResult.isUndef())) return zv::Val();
			zval *borrowed = pt_expression_result_scope(classResult.raw(), hold);
			if (UNEXPECTED(borrowed == NULL)) return zv::Val();
			scope = zv::Val::copyOf(zv::Ref(borrowed));
			if (UNEXPECTED(!pt_expression_result_has_yield(classResult.raw(), hasYield))) return zv::Val();
			borrowed = pt_expression_result_throw_points(classResult.raw(), hold);
			if (UNEXPECTED(borrowed == NULL)) return zv::Val();
			throwPoints = zv::Val::copyOf(zv::Ref(borrowed));
			borrowed = pt_expression_result_impure_points(classResult.raw(), hold);
			if (UNEXPECTED(borrowed == NULL)) return zv::Val();
			impurePoints = zv::Val::copyOf(zv::Ref(borrowed));
			if (UNEXPECTED(!pt_expression_result_is_always_terminating(classResult.raw(), isAlwaysTerminating))) return zv::Val();
		} else {
			if (UNEXPECTED(!pt_node_scope_resolver_call_node_callback(nodeScopeResolver, nodeCallback, class_, scope.raw(), storage))) return zv::Val();
		}

		zval *name = exprName(expr);
		if (UNEXPECTED(name == NULL)) return zv::Val();
		int nameIsIdentifier = isIdentifier(name);
		if (UNEXPECTED(nameIsIdentifier < 0)) return zv::Val();
		if (nameIsIdentifier) {
			if (UNEXPECTED(!pt_node_scope_resolver_call_node_callback(nodeScopeResolver, nodeCallback, name, scope.raw(), storage))) return zv::Val();
		} else {
			zv::Val nameContext = pt_expression_context_enter_deep(context);
			if (UNEXPECTED(nameContext.isUndef())) return zv::Val();
			nameResult = pt_node_scope_resolver_process_expr_node(nodeScopeResolver, stmt, name, scope.raw(), storage, nodeCallback, nameContext.raw());
			if (UNEXPECTED(nameResult.isUndef())) return zv::Val();
			zval *borrowed = pt_expression_result_scope(nameResult.raw(), hold);
			if (UNEXPECTED(borrowed == NULL)) return zv::Val();
			scope = zv::Val::copyOf(zv::Ref(borrowed));
			if (!hasYield && UNEXPECTED(!pt_expression_result_has_yield(nameResult.raw(), hasYield))) return zv::Val();
			borrowed = pt_expression_result_throw_points(nameResult.raw(), hold);
			if (UNEXPECTED(borrowed == NULL)) return zv::Val();
			throwPoints = arrayMerge(throwPoints.raw(), borrowed);
			borrowed = pt_expression_result_impure_points(nameResult.raw(), hold);
			if (UNEXPECTED(borrowed == NULL)) return zv::Val();
			impurePoints = arrayMerge(impurePoints.raw(), borrowed);
			if (!isAlwaysTerminating && UNEXPECTED(!pt_expression_result_is_always_terminating(nameResult.raw(), isAlwaysTerminating))) return zv::Val();
		}

		// the enclosing class is lexical - fixed at this node - so resolve it
		// once here instead of reading it off the callback's scope
		zv::Val classReflection = zv::Val::null();
		bool inClass;
		if (UNEXPECTED(!pt_mutating_scope_is_in_class(Z_OBJ_P(beforeScope), inClass))) return zv::Val();
		if (inClass) {
			classReflection = pt_mutating_scope_get_class_reflection(Z_OBJ_P(beforeScope));
			if (UNEXPECTED(classReflection.isUndef())) return zv::Val();
		}

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
			zv::Args flows{classFlow.raw(), nameFlow.raw()};
			variableFlow = pt_variable_flow_sequence(2, flows);
			if (UNEXPECTED(variableFlow.isUndef())) return zv::Val();
		}
		zv::Val typeCallback = pt_native_closure(&typeCallbackBody, self, expr, classResult.raw(), classReflection.raw());
		zv::Val specifyTypesCallback = pt_native_closure(&specifyTypesCallbackBody, self, expr);

		pt_expression_result_args args(scope.raw(), beforeScope, expr, hasYield, isAlwaysTerminating, throwPoints.raw(), impurePoints.raw(), typeCallback.raw(), specifyTypesCallback.raw());
		args.withVariableFlow(variableFlow.raw());
		return pt_expression_result_create(OBJ_PROP_NUM(self, slots::expressionResultFactory), args);
	}

	/* the handler entry (Engine.h) */
	static zv::Val processExprEntry(zend_object *handler, zval *nodeScopeResolver, zval *stmt, zval *expr, zval *scope, zval *storage, zval *nodeCallback, zval *context)
	{
		return ClassConstFetchHandler(handler).processExpr(nodeScopeResolver, stmt, expr, scope, storage, nodeCallback, context);
	}

private:
	zend_object *self;

	/* function (bool $nativeTypesPromoted) use ($expr, $classResult,
	 * $classReflection): Type — captures: $this, $expr, $classResult,
	 * $classReflection */
	static void typeCallbackBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		if (UNEXPECTED(!requireArguments(argc, 1, pt_ccfh_closure_name))) return;
		zval *expr = &captures[1];
		zval *name = exprName(expr);
		if (UNEXPECTED(name == NULL)) return;
		int nameIsIdentifier = isIdentifier(name);
		if (UNEXPECTED(nameIsIdentifier < 0)) return;
		if (!nameIsIdentifier) {
			zv::Val mixed = pt_type_new_mixed_type();
			if (UNEXPECTED(mixed.isUndef())) return;
			mixed.intoReturnValue(return_value);
			return;
		}

		zval *class_ = exprClass(expr);
		if (UNEXPECTED(class_ == NULL)) return;
		zval *constantName = identifierName(name);
		if (UNEXPECTED(constantName == NULL)) return;
		// the class-type callback's captures ($classResult,
		// $nativeTypesPromoted), borrowed: the resolver calls it synchronously
		zval classTypeCaptures[2];
		ZVAL_COPY_VALUE(&classTypeCaptures[0], &captures[2]);
		ZVAL_BOOL(&classTypeCaptures[1], zend_is_true(&argv[0]));
		pt_ietr_get_type getTypeCallback{&classTypeCallback, classTypeCaptures, &classTypeCallable};
		zv::Val type = getClassConstFetchTypeByReflection(OBJ_PROP_NUM(Z_OBJ(captures[0]), slots::initializerExprTypeResolver), class_, constantName, &captures[3], getTypeCallback);
		if (UNEXPECTED(type.isUndef())) return;
		type.intoReturnValue(return_value);
	}

	/* static function (Expr $e) use ($classResult, $nativeTypesPromoted): Type
	 * — data: the captures $classResult, $nativeTypesPromoted */
	static zv::Val classTypeCallback(void *data, zval *e)
	{
		(void) e;
		zval *captures = static_cast<zval *>(data);
		zval *classResult = &captures[0];
		if (Z_TYPE_P(classResult) == IS_NULL) {
			pt_throw_should_not_happen();
			return zv::Val();
		}
		return Z_TYPE(captures[1]) == IS_TRUE ? pt_expression_result_get_native_type(classResult) : pt_expression_result_get_type(classResult);
	}

	/* the callback as a PHP callable that outlives the call: the closure over
	 * copies of the captures */
	static zv::Val classTypeCallable(void *data)
	{
		return pt_native_closure_new(&classTypeCallbackBody, 2, static_cast<zval *>(data));
	}

	/* the same closure called from PHP — captures: $classResult,
	 * $nativeTypesPromoted */
	static void classTypeCallbackBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		if (UNEXPECTED(!requireArguments(argc, 1, pt_ccfh_closure_name))) return;
		zv::Val type = classTypeCallback(captures, &argv[0]);
		if (UNEXPECTED(type.isUndef())) return;
		type.intoReturnValue(return_value);
	}

	/* fn (TypeSpecifierContext $context, bool $nativeTypesPromoted) =>
	 * $this->defaultNarrowingHelper->specifyDefaultTypes($expr, $context) —
	 * captures: $this, $expr */
	static void specifyTypesCallbackBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		if (UNEXPECTED(!requireArguments(argc, 2, pt_ccfh_closure_name))) return;
		zv::Val specifiedTypes = pt_default_narrowing_helper_specify_default_types(OBJ_PROP_NUM(Z_OBJ(captures[0]), slots::defaultNarrowingHelper), &captures[1], &argv[0]);
		if (UNEXPECTED(specifiedTypes.isUndef())) return;
		specifiedTypes.intoReturnValue(return_value);
	}
};

} // namespace phpstanturbo

using phpstanturbo::ClassConstFetchHandler;

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

void pt_register_class_const_fetch_handler()
{
	reg::Class cls("PHPStan\\Analyser\\ExprHandler\\ClassConstFetchHandler");
	ptdecl::ClassConstFetchHandler::declareClass(cls);
	ptdecl::ClassConstFetchHandler::declareProperties(cls);

	/* the real parameter class names: the DI container autowires the
	 * service by reflecting the constructor */
	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *initializerExprTypeResolver, *expressionResultFactory, *defaultNarrowingHelper;
		if (!zp::parse<zp::Obj, zp::Obj, zp::Obj>(execute_data, initializerExprTypeResolver, expressionResultFactory, defaultNarrowingHelper)) RETURN_THROWS();
		ClassConstFetchHandler(Z_OBJ_P(ZEND_THIS)).construct(initializerExprTypeResolver, expressionResultFactory, defaultNarrowingHelper);
	});

	cls.method<&ClassConstFetchHandler::supports, zp::Obj>(sigs::supports);

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
		PT_RETURN_VAL(ClassConstFetchHandler(Z_OBJ_P(ZEND_THIS)).processExpr(nodeScopeResolver, stmt, expr, scope, storage, nodeCallback, context));
	});

	cls.shadow(&pt_ce_class_const_fetch_handler);
	pt_expr_handler_entry_register(&pt_ce_class_const_fetch_handler, &ClassConstFetchHandler::processExprEntry);
}

/* }}} */
