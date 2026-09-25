/*
 * PHPStanTurbo\ConstFetchHandler — native implementation of
 * PHPStan\Analyser\ExprHandler\ConstFetchHandler.
 *
 * A DI service (#[AutowiredService]): the constructor keeps the twin's
 * arginfo so Nette autowires it. processExpr() is registered as the class's
 * handler entry (Engine.h). The twin's closures are native closures capturing
 * what the PHP closures capture: the typeCallback ($this, $expr, $scope) and
 * the specifyTypesCallback ($this, $expr).
 *
 * NodeScopeResolver, MutatingScope, ExpressionResult, DefaultNarrowingHelper
 * and the Type kernel are called through their direct entries; ConstantResolver
 * stays PHP for now (the cached method sites in the block below) — the true /
 * false / null literals, the bulk of the constant fetches, never reach it.
 */

#include "support.h"
#include "generated/ConstFetchHandler.h"

namespace slots = ptdecl::ConstFetchHandler::slot;
namespace sigs = ptdecl::ConstFetchHandler::sig;
#include "CallHandlerSupport.h"

zend_class_entry *pt_ce_const_fetch_handler = nullptr;

namespace {

using namespace ptcall;

constexpr const char *pt_cfh_closure_name = "PHPStan\\Analyser\\ExprHandler\\ConstFetchHandler::{closure}";

/* {{{ the PHP collaborators (one site each; switch to their direct entries
 * once they are ported) */

pt_method_site pt_cfh_resolve_constant_type_site;
pt_method_site pt_cfh_resolve_constant_site;

/* $constantResolver->resolveConstantType($constantName, $constantType) */
zv::Val resolveConstantType(zval *constantResolver, zval *constantName, zval *constantType)
{
	zv::Args argv{constantName, constantType};
	return pt_call_method_cached(pt_cfh_resolve_constant_type_site, Z_OBJ_P(constantResolver), PT_LC("resolveconstanttype"), 2, argv);
}

/* $constantResolver->resolveConstant($name, $scope) */
zv::Val resolveConstant(zval *constantResolver, zval *name, zval *scope)
{
	zv::Args argv{name, scope};
	return pt_call_method_cached(pt_cfh_resolve_constant_site, Z_OBJ_P(constantResolver), PT_LC("resolveconstant"), 2, argv);
}

/* }}} */

/* {{{ the PhpParser nodes' properties */

pt_property_site pt_cfh_name_site;
pt_property_site pt_cfh_name_name_site;

zval *exprName(zval *expr) { return nodeProperty(pt_cfh_name_site, expr, PT_LC("name")); }
/* $name->toString() / (string) $name */
zval *nameString(zval *name) { return nodeProperty(pt_cfh_name_name_site, name, PT_LC("name")); }

/* $name->isFullyQualified() (NameNodeAccess.cpp); false = pending exception */
[[nodiscard]] bool isFullyQualified(zval *name, bool &out) { return pt_name_node_is_fully_qualified(name, out); }

/* }}} */

} // namespace

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\ExprHandler\ConstFetchHandler; UNDEF = pending
 * exception. */
class ConstFetchHandler
{
public:
	explicit ConstFetchHandler(zend_object *self) : self(self) {}

	/* the constructor body: the promoted properties */
	void construct(zval *constantResolver, zval *expressionResultFactory, zval *defaultNarrowingHelper) const
	{
		pt_write_slot(self, slots::constantResolver, constantResolver);
		pt_write_slot(self, slots::expressionResultFactory, expressionResultFactory);
		pt_write_slot(self, slots::defaultNarrowingHelper, defaultNarrowingHelper);
	}

	/* Mirrors supports(); false = pending exception */
	[[nodiscard]] bool supports(zval *expr, bool &out) const
	{
		int is = isInstanceOf(expr, PT_CLASS_CONST_FETCH);
		if (UNEXPECTED(is < 0)) return false;
		out = is == 1;
		return true;
	}

	/* Mirrors processExpr(). */
	zv::Val processExpr(zval *nodeScopeResolver, zval *stmt, zval *expr, zval *scope, zval *storage, zval *nodeCallback, zval *context) const
	{
		(void) stmt;
		(void) context;
		zval *name = exprName(expr);
		if (UNEXPECTED(name == NULL)) return zv::Val();
		if (UNEXPECTED(!pt_node_scope_resolver_call_node_callback(nodeScopeResolver, nodeCallback, name, scope, storage))) return zv::Val();

		zv::Val typeCallback = pt_native_closure(&typeCallbackBody, self, expr, scope);
		zv::Val specifyTypesCallback = pt_native_closure(&specifyTypesCallbackBody, self, expr);
		pt_expression_result_args args(scope, scope, expr, false, false, NULL, NULL, typeCallback.raw(), specifyTypesCallback.raw());
		return pt_expression_result_create(OBJ_PROP_NUM(self, slots::expressionResultFactory), args);
	}

	/* the handler entry (Engine.h) */
	static zv::Val processExprEntry(zend_object *handler, zval *nodeScopeResolver, zval *stmt, zval *expr, zval *scope, zval *storage, zval *nodeCallback, zval *context)
	{
		return ConstFetchHandler(handler).processExpr(nodeScopeResolver, stmt, expr, scope, storage, nodeCallback, context);
	}

private:
	zend_object *self;

	/* the typeCallback's body */
	zv::Val resolveType(zval *expr, zval *scope) const
	{
		zval *name = exprName(expr);
		if (UNEXPECTED(name == NULL)) return zv::Val();
		zval *constName = nameString(name);
		if (UNEXPECTED(constName == NULL)) return zv::Val();
		if (EXPECTED(Z_TYPE_P(constName) == IS_STRING)) {
			zend_string *lowered = Z_STR_P(constName);
			if (zend_string_equals_literal_ci(lowered, "true")) {
				zval type;
				if (UNEXPECTED(!pt_constant_boolean_type_new(&type, true))) return zv::Val();
				return zv::Val::adopt(type);
			}
			if (zend_string_equals_literal_ci(lowered, "false")) {
				zval type;
				if (UNEXPECTED(!pt_constant_boolean_type_new(&type, false))) return zv::Val();
				return zv::Val::adopt(type);
			}
			if (zend_string_equals_literal_ci(lowered, "null")) {
				zval type;
				if (UNEXPECTED(!pt_null_type_new(&type))) return zv::Val();
				return zv::Val::adopt(type);
			}
		}

		zv::Val namespacedName = zv::Val::null();
		bool fullyQualified;
		if (UNEXPECTED(!isFullyQualified(name, fullyQualified))) return zv::Val();
		if (!fullyQualified) {
			zv::Val namespace_ = pt_mutating_scope_get_namespace(Z_OBJ_P(scope));
			if (UNEXPECTED(namespace_.isUndef())) return zv::Val();
			if (!namespace_.isNull()) {
				zv::Val parts[2];
				parts[0] = pt_mutating_scope_get_namespace(Z_OBJ_P(scope));
				if (UNEXPECTED(parts[0].isUndef())) return zv::Val();
				name = exprName(expr);
				if (UNEXPECTED(name == NULL)) return zv::Val();
				zval *nameValue = nameString(name);
				if (UNEXPECTED(nameValue == NULL)) return zv::Val();
				parts[1] = zv::Val::copyOf(zv::Ref(nameValue));
				zv::Arr partsArray = zv::Arr::create(2);
				partsArray.push(std::move(parts[0]));
				partsArray.push(std::move(parts[1]));
				namespacedName = pt_name_node_new(PT_CLASS_FULLY_QUALIFIED, partsArray.raw());
				if (UNEXPECTED(namespacedName.isUndef())) return zv::Val();
			}
		}
		zv::Val globalName;
		{
			name = exprName(expr);
			if (UNEXPECTED(name == NULL)) return zv::Val();
			zval *nameValue = nameString(name);
			if (UNEXPECTED(nameValue == NULL)) return zv::Val();
			globalName = pt_name_node_new(PT_CLASS_FULLY_QUALIFIED, nameValue);
			if (UNEXPECTED(globalName.isUndef())) return zv::Val();
		}

		zval *constantResolver = OBJ_PROP_NUM(self, slots::constantResolver);
		for (zval *candidate : { namespacedName.raw(), globalName.raw() }) {
			if (Z_TYPE_P(candidate) == IS_NULL) continue;
			zv::Val constFetch = pt_type_new(PT_CLASS_CONST_FETCH, 1, candidate);
			if (UNEXPECTED(constFetch.isUndef())) return zv::Val();
			zend_long has = pt_mutating_scope_has_expression_type(Z_OBJ_P(scope), constFetch.raw());
			if (UNEXPECTED(has < 0)) return zv::Val();
			if (has == PT_TRI_YES) {
				zval *candidateName = nameString(candidate);
				if (UNEXPECTED(candidateName == NULL)) return zv::Val();
				zv::Val candidateNameHold = zv::Val::copyOf(zv::Ref(candidateName));
				zv::Val trackedType = pt_mutating_scope_get_tracked_expression_type(Z_OBJ_P(scope), Z_OBJ_P(constFetch.raw()));
				if (UNEXPECTED(trackedType.isUndef())) return zv::Val();
				return resolveConstantType(constantResolver, candidateNameHold.raw(), trackedType.raw());
			}
		}

		name = exprName(expr);
		if (UNEXPECTED(name == NULL)) return zv::Val();
		zv::Val constantType = resolveConstant(constantResolver, name, scope);
		if (UNEXPECTED(constantType.isUndef())) return zv::Val();
		if (!constantType.isNull()) return constantType;

		return pt_type_new_error_type();
	}

	/* function (bool $nativeTypesPromoted) use ($expr, $scope): Type —
	 * captures: $this, $expr, $scope */
	static void typeCallbackBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		(void) argv;
		if (UNEXPECTED(!requireArguments(argc, 1, pt_cfh_closure_name))) return;
		zv::Val type = ConstFetchHandler(Z_OBJ(captures[0])).resolveType(&captures[1], &captures[2]);
		if (UNEXPECTED(type.isUndef())) return;
		type.intoReturnValue(return_value);
	}

	/* fn (TypeSpecifierContext $context, bool $nativeTypesPromoted) =>
	 * $this->defaultNarrowingHelper->specifyDefaultTypes($expr, $context) —
	 * captures: $this, $expr */
	static void specifyTypesCallbackBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		if (UNEXPECTED(!requireArguments(argc, 2, pt_cfh_closure_name))) return;
		zv::Val specifiedTypes = pt_default_narrowing_helper_specify_default_types(OBJ_PROP_NUM(Z_OBJ(captures[0]), slots::defaultNarrowingHelper), &captures[1], &argv[0]);
		if (UNEXPECTED(specifiedTypes.isUndef())) return;
		specifiedTypes.intoReturnValue(return_value);
	}
};

} // namespace phpstanturbo

using phpstanturbo::ConstFetchHandler;

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

PT_MINIT_REGISTRATION(pt_register_const_fetch_handler)
{
	reg::Class cls("PHPStan\\Analyser\\ExprHandler\\ConstFetchHandler");
	ptdecl::ConstFetchHandler::declareClass(cls);
	ptdecl::ConstFetchHandler::declareProperties(cls);

	/* the real parameter class names: the DI container autowires the
	 * service by reflecting the constructor */
	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *constantResolver, *expressionResultFactory, *defaultNarrowingHelper;
		if (!zp::parse<zp::Obj, zp::Obj, zp::Obj>(execute_data, constantResolver, expressionResultFactory, defaultNarrowingHelper)) RETURN_THROWS();
		ConstFetchHandler(Z_OBJ_P(ZEND_THIS)).construct(constantResolver, expressionResultFactory, defaultNarrowingHelper);
	});

	cls.method<&ConstFetchHandler::supports, zp::Obj>(sigs::supports);

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
		PT_RETURN_VAL(ConstFetchHandler(Z_OBJ_P(ZEND_THIS)).processExpr(nodeScopeResolver, stmt, expr, scope, storage, nodeCallback, context));
	});

	cls.shadow(&pt_ce_const_fetch_handler);
	pt_expr_handler_entry_register(&pt_ce_const_fetch_handler, &ConstFetchHandler::processExprEntry);
}

/* }}} */
