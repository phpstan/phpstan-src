/*
 * PHPStanTurbo\VariableHandler — native implementation of
 * PHPStan\Analyser\ExprHandler\VariableHandler.
 *
 * A DI service (#[AutowiredService]): the constructor keeps the twin's
 * arginfo so Nette autowires it. processExpr() is registered as the class's
 * handler entry and composeResult() — which AssignHandler calls across
 * handlers — is exported as pt_variable_handler_compose_result() (Engine.h
 * conventions). The twin's closures are native closures capturing what the
 * PHP closures capture: the typeCallback ($this, $expr, $nameResult,
 * $nameArgResult, $nodeScopeResolver, $beforeScope), the
 * specifyTypesCallback ($this, $expr), and inside the typeCallback the
 * literal's `static fn (): Type => $constantString` and the identical-type
 * callback handed to IdenticalNarrowingHelper::specifyIdentical().
 *
 * MutatingScope, ExpressionResult, ExpressionContext, VariableFlow,
 * SpecifiedTypes, TypeSpecifierContext, TypeCombinator, ImpurePoint and
 * IssetabilityDescriptor are called through their direct entries (the
 * ExpressionResult slot getters are the inline readers of AnalyserValues.h);
 * the collaborators that stay PHP for now (NodeScopeResolver, the narrowing
 * helpers, InitializerExprTypeResolver) through the cached method sites in the
 * block below, one helper each.
 */

#include "support.h"
#include "generated/VariableHandler.h"
#include "generated/TypeResult.h"

namespace slots = ptdecl::VariableHandler::slot;
namespace sigs = ptdecl::VariableHandler::sig;
#include "zv.h"
#include "TypeTraits.h"
#include "TypeOps.h"
#include "Engine.h"
#include "AnalyserValues.h"

zend_class_entry *pt_ce_variable_handler = nullptr;

namespace {

/* {{{ the PHP collaborators (one site each; switch to their direct entries
 * once they are ported) */

pt_method_site pt_vh_process_expr_node_site;
pt_method_site pt_vh_capture_first_arg_result_site;
pt_method_site pt_vh_specify_default_types_site;
pt_method_site pt_vh_specify_identical_site;
pt_method_site pt_vh_resolve_identical_type_site;
pt_method_site pt_vh_get_constant_strings_site;
pt_property_site pt_vh_name_site;

/* $nodeScopeResolver->processExprNode($stmt, $expr, $scope, $storage,
 * $nodeCallback, $context) */
zv::Val processExprNode(zval *nodeScopeResolver, zval *stmt, zval *expr, zval *scope, zval *storage, zval *nodeCallback, zval *context)
{
	zv::Args argv{stmt, expr, scope, storage, nodeCallback, context};
	return pt_call_method_cached(pt_vh_process_expr_node_site, Z_OBJ_P(nodeScopeResolver), PT_LC("processexprnode"), 6, argv);
}

/* $identicalNarrowingHelper->captureFirstArgResult($side, $storage) */
zv::Val captureFirstArgResult(zval *identicalNarrowingHelper, zval *side, zval *storage)
{
	zv::Args argv{side, storage};
	return pt_call_method_cached(pt_vh_capture_first_arg_result_site, Z_OBJ_P(identicalNarrowingHelper), PT_LC("capturefirstargresult"), 2, argv);
}

/* $defaultNarrowingHelper->specifyDefaultTypes($expr, $context) */
zv::Val specifyDefaultTypes(zval *defaultNarrowingHelper, zval *expr, zval *context)
{
	zv::Args argv{expr, context};
	return pt_call_method_cached(pt_vh_specify_default_types_site, Z_OBJ_P(defaultNarrowingHelper), PT_LC("specifydefaulttypes"), 2, argv);
}

/* $identicalNarrowingHelper->specifyIdentical(...) with its ten arguments */
zv::Val specifyIdentical(zval *identicalNarrowingHelper, zval *argv)
{
	return pt_call_method_cached(pt_vh_specify_identical_site, Z_OBJ_P(identicalNarrowingHelper), PT_LC("specifyidentical"), 10, argv);
}

/* $initializerExprTypeResolver->resolveIdenticalType($leftType, $rightType) */
zv::Val resolveIdenticalType(zval *initializerExprTypeResolver, zval *leftType, zval *rightType)
{
	zv::Args argv{leftType, rightType};
	return pt_call_method_cached(pt_vh_resolve_identical_type_site, Z_OBJ_P(initializerExprTypeResolver), PT_LC("resolveidenticaltype"), 2, argv);
}

/* the superglobal impure point's 'superglobal' / 'access to superglobal
 * variable' literals, permanent interned strings (module startup) */
zend_string *pt_vh_superglobal_identifier = nullptr;
zend_string *pt_vh_superglobal_description = nullptr;

/* $type->getConstantStrings() */
zv::Val getConstantStrings(zval *type)
{
	return pt_call_method_cached(pt_vh_get_constant_strings_site, Z_OBJ_P(type), PT_LC("getconstantstrings"), 0, NULL);
}

/* }}} */

/* $expr->name of a Variable (dereferenced; an undefined zval when the class
 * declares no such property — the twin would read null with a warning) */
zval *variableName(zval *expr)
{
	static zval undefined;
	zval *name = pt_property_cached(pt_vh_name_site, Z_OBJ_P(expr), PT_LC("name"));
	if (UNEXPECTED(name == NULL)) {
		ZVAL_UNDEF(&undefined);
		return &undefined;
	}
	ZVAL_DEREF(name);
	return name;
}

/* $result->type of a TypeResult (the slot of the native class, the property
 * otherwise); UNDEF = pending exception */
zv::Val typeResultType(zval *result)
{
	if (EXPECTED(Z_TYPE_P(result) == IS_OBJECT && Z_OBJCE_P(result) == pt_ce_type_result)) return zv::Val::copyOf(zv::Ref(OBJ_PROP_NUM(Z_OBJ_P(result), ptdecl::TypeResult::slot::type)));
	if (UNEXPECTED(Z_TYPE_P(result) != IS_OBJECT)) {
		zend_throw_error(NULL, "Attempt to read property \"type\" on %s", zend_zval_value_name(result));
		return zv::Val();
	}
	zval rv;
	ZVAL_UNDEF(&rv);
	zval *type = zend_read_property(Z_OBJCE_P(result), Z_OBJ_P(result), PT_LC("type"), 0, &rv);
	if (UNEXPECTED(EG(exception))) {
		zval_ptr_dtor(&rv);
		return zv::Val();
	}
	if (type == &rv) return zv::Val::adopt(rv);
	return zv::Val::copyOf(zv::Ref(type));
}

} // namespace

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\ExprHandler\VariableHandler; UNDEF = pending
 * exception. */
class VariableHandler
{
public:
	explicit VariableHandler(zend_object *self) : self(self) {}

	/* the constructor body: the promoted properties */
	void construct(zval *expressionResultFactory, zval *defaultNarrowingHelper, zval *identicalNarrowingHelper, zval *initializerExprTypeResolver)
	{
		zv::ObjRef object(self);
		object.propAtWrite(slots::expressionResultFactory, zv::Val::copyOf(zv::Ref(expressionResultFactory)));
		object.propAtWrite(slots::defaultNarrowingHelper, zv::Val::copyOf(zv::Ref(defaultNarrowingHelper)));
		object.propAtWrite(slots::identicalNarrowingHelper, zv::Val::copyOf(zv::Ref(identicalNarrowingHelper)));
		object.propAtWrite(slots::initializerExprTypeResolver, zv::Val::copyOf(zv::Ref(initializerExprTypeResolver)));
	}

	/* Mirrors supports(); false = pending exception */
	[[nodiscard]] bool supports(zval *expr, bool &out) const
	{
		zend_class_entry *variableCe = pt_class(PT_CLASS_VARIABLE);
		if (UNEXPECTED(variableCe == NULL)) return false;
		out = instanceof_function(Z_OBJCE_P(expr), variableCe);
		return true;
	}

	/* Mirrors processExpr(). */
	zv::Val processExpr(zval *nodeScopeResolver, zval *stmt, zval *expr, zval *scope, zval *storage, zval *nodeCallback, zval *context) const
	{
		zval *beforeScope = scope;
		zv::Val nameResult;
		zval *name = variableName(expr);
		if (Z_TYPE_P(name) != IS_STRING) {
			zv::Val deepContext = pt_expression_context_enter_deep(context);
			if (UNEXPECTED(deepContext.isUndef())) return zv::Val();
			nameResult = processExprNode(nodeScopeResolver, stmt, name, scope, storage, nodeCallback, deepContext.raw());
			if (UNEXPECTED(nameResult.isUndef())) return zv::Val();
		}

		return composeResult(nodeScopeResolver, expr, nameResult.isUndef() ? NULL : nameResult.raw(), storage, beforeScope, context);
	}

	/* Mirrors composeResult(); $nameResult / $context NULL (or IS_NULL) for
	 * null */
	zv::Val composeResult(zval *nodeScopeResolver, zval *expr, zval *nameResult, zval *storage, zval *beforeScope, zval *context) const
	{
		if (nameResult != NULL && Z_TYPE_P(nameResult) == IS_NULL) nameResult = NULL;
		if (context != NULL && Z_TYPE_P(context) == IS_NULL) context = NULL;

		/* the name result's scope and points are borrowed from its slots (the
		 * holds keep a foreign result's getter values alive) */
		zv::Val scopeHold, throwPointsHold, impurePointsHold;
		zval *scope = beforeScope;
		bool hasYield = false;
		zval *throwPoints = NULL;
		zval *impurePoints = NULL;
		bool isAlwaysTerminating = false;
		zv::Val variableFlow = zv::Val::null();
		zval *name = variableName(expr);
		if (Z_TYPE_P(name) == IS_STRING) {
			bool unsetTarget = false;
			if (context != NULL && UNEXPECTED(!pt_expression_context_is_unset_target(context, unsetTarget))) return zv::Val();
			if (unsetTarget) {
				variableFlow = pt_variable_flow_mention(Z_STR_P(name));
			} else {
				zv::Val targetId = zv::Val::null();
				bool container = false;
				if (context != NULL) {
					zv::Val target = pt_expression_context_get_value_flow_target(context);
					if (UNEXPECTED(target.isUndef())) return zv::Val();
					if (!target.isNull()) {
						targetId = variableWriteId(target.raw());
						if (UNEXPECTED(targetId.isUndef())) return zv::Val();
					}
					if (UNEXPECTED(!pt_expression_context_is_array_dim_fetch_root(context, container))) return zv::Val();
				}
				variableFlow = pt_variable_flow_read(Z_STR_P(name), targetId.isNull() ? NULL : targetId.raw(), container, NULL);
			}
			if (UNEXPECTED(variableFlow.isUndef())) return zv::Val();
			if (pt_is_superglobal_name(Z_STR_P(name))) {
				zv::Val impurePoint = pt_impure_point_new(scope, expr, pt_vh_superglobal_identifier, pt_vh_superglobal_description, true);
				if (UNEXPECTED(impurePoint.isUndef())) return zv::Val();
				zv::Arr points = zv::Arr::create(1);
				points.push(std::move(impurePoint));
				impurePointsHold = zv::Val(std::move(points));
				impurePoints = impurePointsHold.raw();
			}
		} else if (nameResult != NULL) {
			zv::Val nameType = pt_expression_result_get_type(nameResult);
			if (UNEXPECTED(nameType.isUndef())) return zv::Val();
			zv::Val names = getConstantStrings(nameType.raw());
			if (UNEXPECTED(names.isUndef())) return zv::Val();
			zv::Val nameFlow = pt_expression_result_variable_flow(nameResult);
			if (UNEXPECTED(nameFlow.isUndef())) return zv::Val();
			zv::Val namesFlow;
			if (Z_TYPE_P(names.raw()) == IS_ARRAY && zend_hash_num_elements(Z_ARRVAL_P(names.raw())) == 0) {
				namesFlow = pt_variable_flow_all_read_all();
			} else {
				namesFlow = readsOf(names.raw());
			}
			if (UNEXPECTED(namesFlow.isUndef())) return zv::Val();
			zv::Args flows{nameFlow.raw(), namesFlow.raw()};
			variableFlow = pt_variable_flow_sequence(2, flows);
			if (UNEXPECTED(variableFlow.isUndef())) return zv::Val();
			if (UNEXPECTED(!pt_expression_result_has_yield(nameResult, hasYield))) return zv::Val();
			throwPoints = pt_expression_result_throw_points(nameResult, throwPointsHold);
			if (UNEXPECTED(throwPoints == NULL)) return zv::Val();
			impurePoints = pt_expression_result_impure_points(nameResult, impurePointsHold);
			if (UNEXPECTED(impurePoints == NULL)) return zv::Val();
			if (UNEXPECTED(!pt_expression_result_is_always_terminating(nameResult, isAlwaysTerminating))) return zv::Val();
			scope = pt_expression_result_scope(nameResult, scopeHold);
			if (UNEXPECTED(scope == NULL)) return zv::Val();
		}

		zv::Val issetabilityDescriptor = zv::Val::null();
		if (Z_TYPE_P(name) == IS_STRING) {
			issetabilityDescriptor = pt_issetability_descriptor_variable(Z_STR_P(name));
			if (UNEXPECTED(issetabilityDescriptor.isUndef())) return zv::Val();
		}
		zv::Val nameArgResult = zv::Val::null();
		if (Z_TYPE_P(name) != IS_STRING) {
			nameArgResult = captureFirstArgResult(OBJ_PROP_NUM(self, slots::identicalNarrowingHelper), name, storage);
			if (UNEXPECTED(nameArgResult.isUndef())) return zv::Val();
		}
		zval null = {};
		ZVAL_NULL(&null);
		zv::Val typeCallback = pt_native_closure(&typeCallbackBody, self, expr, nameResult != NULL ? nameResult : &null, nameArgResult.raw(), nodeScopeResolver, beforeScope);
		zv::Val specifyTypesCallback = pt_native_closure(&specifyTypesCallbackBody, self, expr);

		pt_expression_result_args args(scope, beforeScope, expr, hasYield, isAlwaysTerminating, throwPoints, impurePoints, typeCallback.raw(), specifyTypesCallback.raw());
		args.withVariableFlow(variableFlow.raw()).withIssetabilityDescriptor(issetabilityDescriptor.raw());
		return pt_expression_result_create(OBJ_PROP_NUM(self, slots::expressionResultFactory), args);
	}

	/* the handler entry (Engine.h) */
	static zv::Val processExprEntry(zend_object *handler, zval *nodeScopeResolver, zval *stmt, zval *expr, zval *scope, zval *storage, zval *nodeCallback, zval *context)
	{
		return VariableHandler(handler).processExpr(nodeScopeResolver, stmt, expr, scope, storage, nodeCallback, context);
	}

private:
	zend_object *self;

	/* $write->getId() of a VariableWrite: the slot of the PHP class, the
	 * method otherwise */
	static zv::Val variableWriteId(zval *write)
	{
		bool error = false;
		const pt_variable_write_slots *writeSlots = pt_variable_write_slots_of(Z_OBJ_P(write), error);
		if (writeSlots != NULL) return zv::Val::copyOf(zv::ObjRef(write).propAtOffset(writeSlots->id));
		if (UNEXPECTED(error)) return zv::Val();
		return pt_type_call(Z_OBJ_P(write), PT_LC("getid"), 0, NULL);
	}

	/* VariableFlow::sequence(...array_map(static fn ($name) =>
	 * VariableFlow::read($name->getValue()), $names)) */
	static zv::Val readsOf(zval *names)
	{
		if (UNEXPECTED(Z_TYPE_P(names) != IS_ARRAY)) {
			zend_type_error("array_map(): Argument #2 ($array) must be of type array, %s given", zend_zval_value_name(names));
			return zv::Val();
		}
		HashTable *table = Z_ARRVAL_P(names);
		uint32_t count = zend_hash_num_elements(table);
		zval *reads = (zval *) safe_emalloc(count, sizeof(zval), 0);
		uint32_t built = 0;
		zv::Val result;
		bool failed = false;
		for (auto entry : zv::TableRef(table)) {
			zv::Ref constantString = entry.value().deref();
			zv::Val value = constantString.isObject() ? pt_type_op(constantString.asObject(), PT_OP_GET_VALUE, 0, NULL) : zv::Val();
			if (UNEXPECTED(!constantString.isObject())) {
				zend_throw_error(NULL, "Call to a member function getValue() on %s", zend_zval_value_name(constantString.raw()));
			}
			if (UNEXPECTED(value.isUndef())) {
				failed = true;
				break;
			}
			if (UNEXPECTED(!value.ref().isString())) {
				zend_type_error("PHPStan\\Analyser\\VariableFlow::read(): Argument #1 ($name) must be of type string, %s given", zend_zval_value_name(value.raw()));
				failed = true;
				break;
			}
			zv::Val read = pt_variable_flow_read(Z_STR_P(value.raw()), NULL, false, NULL);
			if (UNEXPECTED(read.isUndef())) {
				failed = true;
				break;
			}
			reads[built++] = read.take();
		}
		if (!failed) {
			result = pt_variable_flow_sequence(built, reads);
		}
		for (uint32_t i = 0; i < built; i++) {
			zval_ptr_dtor(&reads[i]);
		}
		efree(reads);
		return result;
	}

	/* function (bool $nativeTypesPromoted) use ($expr, $nameResult,
	 * $nameArgResult, $nodeScopeResolver, $beforeScope): Type — captures:
	 * $this, $expr, $nameResult, $nameArgResult, $nodeScopeResolver,
	 * $beforeScope */
	static void typeCallbackBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		if (UNEXPECTED(argc < 1)) {
			zend_throw_error(zend_ce_argument_count_error, "Too few arguments to function PHPStan\\Analyser\\ExprHandler\\VariableHandler::{closure}(), %u passed and exactly 1 expected", argc);
			return;
		}
		zv::Val type = resolveType(captures, zend_is_true(&argv[0]));
		if (UNEXPECTED(type.isUndef())) return;
		type.intoReturnValue(return_value);
	}

	static zv::Val resolveType(zval *captures, bool nativeTypesPromoted)
	{
		zval *handler = &captures[0];
		zval *expr = &captures[1];
		zval *nameResult = &captures[2];
		zval *nameArgResult = &captures[3];
		zval *nodeScopeResolver = &captures[4];
		zval *beforeScope = &captures[5];

		zv::Val readScopeValue;
		zval *readScope = beforeScope;
		if (nativeTypesPromoted) {
			readScopeValue = pt_mutating_scope_do_not_treat_phpdoc_types_as_certain(Z_OBJ_P(beforeScope));
			if (UNEXPECTED(readScopeValue.isUndef())) return zv::Val();
			readScope = readScopeValue.raw();
		}
		zval *name = variableName(expr);
		if (Z_TYPE_P(name) == IS_STRING) {
			zv::Val has = pt_mutating_scope_has_variable_type(Z_OBJ_P(readScope), Z_STR_P(name));
			if (UNEXPECTED(has.isUndef())) return zv::Val();
			zend_long hasValue = pt_type_trinary_value(has.raw());
			if (UNEXPECTED(hasValue < 0)) return zv::Val();
			if (hasValue == PT_TRI_NO) return pt_type_new_error_type();

			return pt_mutating_scope_get_variable_type(Z_OBJ_P(readScope), Z_STR_P(name));
		}

		// this branch is only reached when $expr->name is an Expr, which is
		// exactly when the caller (processExpr) set $nameResult
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
			zend_error(E_WARNING, "foreach() argument must be of type array|object, %s given", zend_zval_value_name(iterated.raw()));
			if (UNEXPECTED(EG(exception))) return zv::Val();
			return pt_type_combinator_union(0, NULL);
		}
		zv::Arr types = zv::Arr::create(zend_hash_num_elements(Z_ARRVAL_P(iterated.raw())));
		for (auto entry : zv::TableRef(Z_ARRVAL_P(iterated.raw()))) {
			zval *constantString = entry.value().deref().raw();
			zv::Val type = literalVariableType(handler, expr, nameResult, nameArgResult, nodeScopeResolver, readScope, name, nameType.raw(), constantString);
			if (UNEXPECTED(type.isUndef())) return zv::Val();
			types.push(std::move(type));
		}

		HashTable *typesTable = types.table();
		return HT_IS_PACKED(typesTable) && typesTable->nNumUsed == zend_hash_num_elements(typesTable)
			? pt_type_combinator_union(zend_hash_num_elements(typesTable), typesTable->arPacked)
			: pt_type_combinator_union(0, NULL);
	}

	/* the foreach body over one constant string of the name's type: the
	 * variable's type on the read scope narrowed by `name === 'str'` */
	static zv::Val literalVariableType(zval *handler, zval *expr, zval *nameResult, zval *nameArgResult, zval *nodeScopeResolver, zval *readScope, zval *name, zval *nameType, zval *constantString)
	{
		(void) expr;
		zend_object *handlerObject = Z_OBJ_P(handler);
		if (UNEXPECTED(Z_TYPE_P(constantString) != IS_OBJECT)) {
			zend_throw_error(NULL, "Call to a member function getValue() on %s", zend_zval_value_name(constantString));
			return zv::Val();
		}
		// "name === 'str'" composed from the name expression's walk
		// result - no synthetic Identical walk; the literal side is a
		// result the scalar handler would have produced
		zv::Val value = pt_type_op(Z_OBJ_P(constantString), PT_OP_GET_VALUE, 0, NULL);
		if (UNEXPECTED(value.isUndef())) return zv::Val();
		zv::Val literalExpr = pt_type_new(PT_CLASS_SCALAR_STRING, 1, value.raw());
		if (UNEXPECTED(literalExpr.isUndef())) return zv::Val();
		zv::Val literalTypeCallback = pt_native_closure(&literalTypeCallbackBody, constantString);
		zv::Val literalSpecifyTypesCallback = pt_specified_types_empty_specify_callback();
		if (UNEXPECTED(literalSpecifyTypesCallback.isUndef())) return zv::Val();
		pt_expression_result_args literalArgs(readScope, readScope, literalExpr.raw(), false, false, NULL, NULL, literalTypeCallback.raw(), literalSpecifyTypesCallback.raw());
		zv::Val literalResult = pt_expression_result_create(OBJ_PROP_NUM(handlerObject, slots::expressionResultFactory), literalArgs);
		if (UNEXPECTED(literalResult.isUndef())) return zv::Val();

		/* the singleton, borrowed: the context registry holds it */
		zend_object *truthy = pt_type_specifier_context_create_truthy();
		if (UNEXPECTED(truthy == NULL)) return zv::Val();
		zv::Val identicalTypeCallback = pt_native_closure(&identicalTypeCallbackBody, handlerObject, nameType, constantString);
		zval null = {};
		ZVAL_NULL(&null);
		zv::Args specifyArgv{nodeScopeResolver, name, literalExpr.raw(), nameResult, literalResult.raw(), truthy, readScope, nameArgResult, &null, identicalTypeCallback.raw()};
		zv::Val specifiedTypes = specifyIdentical(OBJ_PROP_NUM(handlerObject, slots::identicalNarrowingHelper), specifyArgv);
		if (UNEXPECTED(specifiedTypes.isUndef())) return zv::Val();
		if (specifiedTypes.isNull()) {
			specifiedTypes = pt_specified_types_new();
			if (UNEXPECTED(specifiedTypes.isUndef())) return zv::Val();
		}
		zv::Val variableScope = pt_mutating_scope_apply_specified_types(Z_OBJ_P(readScope), specifiedTypes.raw());
		if (UNEXPECTED(variableScope.isUndef())) return zv::Val();
		if (UNEXPECTED(!variableScope.ref().isObject())) {
			zend_throw_error(NULL, "Call to a member function hasVariableType() on %s", zend_zval_value_name(variableScope.raw()));
			return zv::Val();
		}

		zv::Val hasName = pt_type_op(Z_OBJ_P(constantString), PT_OP_GET_VALUE, 0, NULL);
		if (UNEXPECTED(hasName.isUndef())) return zv::Val();
		if (UNEXPECTED(!hasName.ref().isString())) {
			zend_type_error("PHPStan\\Analyser\\MutatingScope::hasVariableType(): Argument #1 ($variableName) must be of type string, %s given", zend_zval_value_name(hasName.raw()));
			return zv::Val();
		}
		zv::Val has = pt_mutating_scope_has_variable_type(Z_OBJ_P(variableScope.raw()), Z_STR_P(hasName.raw()));
		if (UNEXPECTED(has.isUndef())) return zv::Val();
		zend_long hasValue = pt_type_trinary_value(has.raw());
		if (UNEXPECTED(hasValue < 0)) return zv::Val();
		if (hasValue == PT_TRI_NO) return pt_type_new_error_type();

		zv::Val getName = pt_type_op(Z_OBJ_P(constantString), PT_OP_GET_VALUE, 0, NULL);
		if (UNEXPECTED(getName.isUndef())) return zv::Val();
		if (UNEXPECTED(!getName.ref().isString())) {
			zend_type_error("PHPStan\\Analyser\\MutatingScope::getVariableType(): Argument #1 ($variableName) must be of type string, %s given", zend_zval_value_name(getName.raw()));
			return zv::Val();
		}
		return pt_mutating_scope_get_variable_type(Z_OBJ_P(variableScope.raw()), Z_STR_P(getName.raw()));
	}

	/* static fn (): Type => $constantString — captures: $constantString */
	static void literalTypeCallbackBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		(void) argc;
		(void) argv;
		ZVAL_COPY(return_value, &captures[0]);
	}

	/* fn (): Type => $this->initializerExprTypeResolver->resolveIdenticalType($nameType,
	 * $constantString)->type — captures: $this, $nameType, $constantString */
	static void identicalTypeCallbackBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		(void) argc;
		(void) argv;
		zv::Val result = resolveIdenticalType(OBJ_PROP_NUM(Z_OBJ(captures[0]), slots::initializerExprTypeResolver), &captures[1], &captures[2]);
		if (UNEXPECTED(result.isUndef())) return;
		zv::Val type = typeResultType(result.raw());
		if (UNEXPECTED(type.isUndef())) return;
		type.intoReturnValue(return_value);
	}

	/* fn (TypeSpecifierContext $context, bool $nativeTypesPromoted): SpecifiedTypes =>
	 * $this->defaultNarrowingHelper->specifyDefaultTypes($expr, $context) —
	 * captures: $this, $expr */
	static void specifyTypesCallbackBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		if (UNEXPECTED(argc < 2)) {
			zend_throw_error(zend_ce_argument_count_error, "Too few arguments to function PHPStan\\Analyser\\ExprHandler\\VariableHandler::{closure}(), %u passed and exactly 2 expected", argc);
			return;
		}
		zv::Val specifiedTypes = specifyDefaultTypes(OBJ_PROP_NUM(Z_OBJ(captures[0]), slots::defaultNarrowingHelper), &captures[1], &argv[0]);
		if (UNEXPECTED(specifiedTypes.isUndef())) return;
		specifiedTypes.intoReturnValue(return_value);
	}
};

} // namespace phpstanturbo

using phpstanturbo::VariableHandler;

zv::Val pt_variable_handler_compose_result(zval *handler, zval *nodeScopeResolver, zval *expr, zval *nameResult, zval *storage, zval *beforeScope, zval *context)
{
	if (EXPECTED(Z_OBJCE_P(handler) == pt_ce_variable_handler)) return VariableHandler(Z_OBJ_P(handler)).composeResult(nodeScopeResolver, expr, nameResult, storage, beforeScope, context);
	zval null;
	ZVAL_NULL(&null);
	zv::Args argv{nodeScopeResolver, expr, nameResult != NULL ? nameResult : &null, storage, beforeScope, context != NULL ? context : &null};
	return pt_type_call(Z_OBJ_P(handler), PT_LC("composeresult"), 6, argv);
}

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

void pt_register_variable_handler()
{
	pt_vh_superglobal_identifier = zend_string_init_interned(PT_LC("superglobal"), 1);
	pt_vh_superglobal_description = zend_string_init_interned(PT_LC("access to superglobal variable"), 1);

	reg::Class cls("PHPStan\\Analyser\\ExprHandler\\VariableHandler");
	ptdecl::VariableHandler::declareClass(cls);
	ptdecl::VariableHandler::declareProperties(cls);

	/* the real parameter class names: the DI container autowires the
	 * service by reflecting the constructor */
	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *expressionResultFactory, *defaultNarrowingHelper, *identicalNarrowingHelper, *initializerExprTypeResolver;
		if (!zp::parse<zp::Obj, zp::Obj, zp::Obj, zp::Obj>(execute_data, expressionResultFactory, defaultNarrowingHelper, identicalNarrowingHelper, initializerExprTypeResolver)) RETURN_THROWS();
		VariableHandler(Z_OBJ_P(ZEND_THIS)).construct(expressionResultFactory, defaultNarrowingHelper, identicalNarrowingHelper, initializerExprTypeResolver);
	});

	cls.method<&VariableHandler::supports, zp::Obj>(sigs::supports);

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
		PT_RETURN_VAL(VariableHandler(Z_OBJ_P(ZEND_THIS)).processExpr(nodeScopeResolver, stmt, expr, scope, storage, nodeCallback, context));
	});

	cls.method(sigs::composeResult, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *nodeScopeResolver, *expr, *nameResult, *storage, *beforeScope, *context = NULL;
		ZEND_PARSE_PARAMETERS_START(5, 6)
			Z_PARAM_OBJECT(nodeScopeResolver)
			Z_PARAM_OBJECT(expr)
			Z_PARAM_OBJECT_OR_NULL(nameResult)
			Z_PARAM_OBJECT(storage)
			Z_PARAM_OBJECT(beforeScope)
			Z_PARAM_OPTIONAL
			Z_PARAM_OBJECT_OR_NULL(context)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(VariableHandler(Z_OBJ_P(ZEND_THIS)).composeResult(nodeScopeResolver, expr, nameResult, storage, beforeScope, context));
	});

	cls.shadow(&pt_ce_variable_handler);
	pt_expr_handler_entry_register(&pt_ce_variable_handler, &VariableHandler::processExprEntry);
}

/* }}} */
