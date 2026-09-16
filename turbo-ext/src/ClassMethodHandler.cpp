/*
 * PHPStanTurbo\ClassMethodHandler — native implementation of
 * PHPStan\Analyser\StmtHandler\ClassMethodHandler.
 *
 * A DI service (#[AutowiredService]): the constructor keeps the twin's
 * arginfo so Nette autowires it. processStmt() is registered as the class's
 * statement-handler entry (Engine.h).
 *
 * The gatherer frame the twin pushes around the body walk — called for every
 * node the body emits — is a native closure capturing what the PHP closure
 * captures: $nodeScopeResolver and $methodScope by value, the five gathered
 * lists by reference. MutatingScope, ClassReflection, TypeUtils,
 * ImpurePoint, the statement results, ExpressionResultStorage,
 * VariableLivenessResolver, PropertyHooksProcessor and NodeScopeResolver are
 * called through their direct entries, the other declaration processors
 * through the shared helpers of StmtHandlerCalls.h; the PHP reflection and
 * node classes through the sites below.
 */

#include "support.h"
#include "generated/ClassMethodHandler.h"

namespace slots = ptdecl::ClassMethodHandler::slot;
namespace sigs = ptdecl::ClassMethodHandler::sig;
#include "zv.h"
#include "TypeTraits.h"
#include "Engine.h"
#include "AnalyserValues.h"
#include "StmtHandlerCalls.h"

zend_class_entry *pt_ce_class_method_handler = nullptr;

namespace {

/* {{{ the PHP collaborators (one site each; switch to their direct entries
 * once they are ported) */

pt_method_site pt_cmh_get_doc_comment_site;
pt_method_site pt_cmh_get_text_site;
pt_method_site pt_cmh_parser_node_type_resolve_site;
pt_method_site pt_cmh_get_declaring_class_site;
pt_method_site pt_cmh_get_name_site;
pt_method_site pt_cmh_get_return_node_site;
pt_method_site pt_cmh_get_statement_result_site;
pt_method_site pt_cmh_return_statement_get_scope_site;

/* $param->getDocComment() */
zv::Val getDocComment(zval *param)
{
	return pt_call_method_cached(pt_cmh_get_doc_comment_site, Z_OBJ_P(param), PT_LC("getdoccomment"), 0, NULL);
}

/* $comment->getText() */
zv::Val getText(zval *comment)
{
	return pt_call_method_cached(pt_cmh_get_text_site, Z_OBJ_P(comment), PT_LC("gettext"), 0, NULL);
}

/* ParserNodeTypeToPHPStanType::resolve($type, $classReflection) */
zv::Val resolveParserNodeType(zval *type, zval *classReflection)
{
	zv::Args argv{type, classReflection};
	return pt_call_static_cached(pt_cmh_parser_node_type_resolve_site, PT_CLASS_PARSER_NODE_TYPE_TO_PHPSTAN_TYPE, PT_LC("resolve"), 2, argv);
}

/* $methodReflection->getDeclaringClass() */
zv::Val getDeclaringClass(zval *methodReflection)
{
	return pt_call_method_cached(pt_cmh_get_declaring_class_site, Z_OBJ_P(methodReflection), PT_LC("getdeclaringclass"), 0, NULL);
}

/* $methodReflection->getName() */
zv::Val getMethodName(zval *methodReflection)
{
	return pt_call_method_cached(pt_cmh_get_name_site, Z_OBJ_P(methodReflection), PT_LC("getname"), 0, NULL);
}

/* $returnAfterFinallyNode->getReturnNode() */
zv::Val getReturnNode(zval *node)
{
	return pt_call_method_cached(pt_cmh_get_return_node_site, Z_OBJ_P(node), PT_LC("getreturnnode"), 0, NULL);
}

/* $executionEndNode->getStatementResult() */
zv::Val getStatementResult(zval *node)
{
	return pt_call_method_cached(pt_cmh_get_statement_result_site, Z_OBJ_P(node), PT_LC("getstatementresult"), 0, NULL);
}

/* $returnStatement->getScope() */
zv::Val returnStatementScope(zval *statement)
{
	return pt_call_method_cached(pt_cmh_return_statement_get_scope_site, Z_OBJ_P(statement), PT_LC("getscope"), 0, NULL);
}

/* }}} */

pt_property_site pt_cmh_attr_groups_site;
pt_property_site pt_cmh_params_site;
pt_property_site pt_cmh_return_type_site;
pt_property_site pt_cmh_name_site;
pt_property_site pt_cmh_identifier_name_site;
pt_property_site pt_cmh_attributes_site;
pt_property_site pt_cmh_stmts_site;
pt_property_site pt_cmh_param_flags_site;
pt_property_site pt_cmh_param_hooks_site;
pt_property_site pt_cmh_param_var_site;
pt_property_site pt_cmh_param_type_site;
pt_property_site pt_cmh_variable_name_site;
pt_property_site pt_cmh_property_assign_fetch_site;
pt_property_site pt_cmh_property_fetch_var_site;

/* the impure point's literals, permanent interned strings (module startup) */
zend_string *pt_cmh_property_assign = nullptr;
zend_string *pt_cmh_property_assignment = nullptr;

zend_never_inline ZEND_COLD void memberCallOnNonObject(const char *method, zval *value)
{
	zend_throw_error(NULL, "Call to a member function %s() on %s", method, zend_zval_value_name(value));
}

/* $node->getAttribute($key, $default) of a php-parser node: the attributes
 * array's element when the key exists, $default otherwise (borrowed); NULL =
 * pending exception */
zval *nodeAttribute(zval *node, const char *key, size_t keyLen, zval *defaultValue)
{
	zval *attributes = ptsh::readNodeProperty(pt_cmh_attributes_site, node, PT_LC("attributes"));
	if (UNEXPECTED(attributes == NULL)) return NULL;
	if (UNEXPECTED(Z_TYPE_P(attributes) != IS_ARRAY)) {
		zend_type_error("array_key_exists(): Argument #2 ($array) must be of type array, %s given", zend_zval_value_name(attributes));
		return NULL;
	}
	zval *value = zend_symtable_str_find(Z_ARRVAL_P(attributes), key, keyLen);
	if (value == NULL) return defaultValue;
	ZVAL_DEREF(value);
	return value;
}

/* $array[$key] ?? null (borrowed) */
zval *coalesceItem(zval *array, zval *key)
{
	if (Z_TYPE_P(array) != IS_ARRAY || Z_TYPE_P(key) != IS_STRING) return &EG(uninitialized_zval);
	zval *value = zend_symtable_find(Z_ARRVAL_P(array), Z_STR_P(key));
	if (value == NULL) return &EG(uninitialized_zval);
	ZVAL_DEREF(value);
	return Z_TYPE_P(value) == IS_NULL ? &EG(uninitialized_zval) : value;
}

} // namespace

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\StmtHandler\ClassMethodHandler; UNDEF = pending
 * exception. */
class ClassMethodHandler
{
public:
	explicit ClassMethodHandler(zend_object *self) : self(self) {}

	/* the constructor body: the promoted properties */
	void construct(zval *deprecatedAttributeResolver, zval *phpDocsResolver, zval *propertyHooksProcessor, zval *attributesHandler, zval *parametersProcessor)
	{
		zv::ObjRef object(self);
		object.propAtWrite(slots::deprecatedAttributeResolver, zv::Val::copyOf(zv::Ref(deprecatedAttributeResolver)));
		object.propAtWrite(slots::phpDocsResolver, zv::Val::copyOf(zv::Ref(phpDocsResolver)));
		object.propAtWrite(slots::propertyHooksProcessor, zv::Val::copyOf(zv::Ref(propertyHooksProcessor)));
		object.propAtWrite(slots::attributesHandler, zv::Val::copyOf(zv::Ref(attributesHandler)));
		object.propAtWrite(slots::parametersProcessor, zv::Val::copyOf(zv::Ref(parametersProcessor)));
	}

	/* Mirrors supports(); false = pending exception */
	[[nodiscard]] bool supports(zval *stmt, bool &out) const
	{
		bool error = false;
		out = ptsh::isInstanceOf(stmt, PT_CLASS_CLASS_METHOD_STMT, error);
		return !error;
	}

	/* Mirrors processStmt(). */
	zv::Val processStmt(zval *nodeScopeResolver, zval *stmt, zval *scope, zval *storage, zval *nodeCallback, zval *context) const
	{
		zval *attrGroups = ptsh::readNodeProperty(pt_cmh_attr_groups_site, stmt, PT_LC("attrGroups"));
		if (UNEXPECTED(attrGroups == NULL)) return zv::Val();
		if (UNEXPECTED(!ptsh::processAttributeGroups(OBJ_PROP_NUM(self, slots::attributesHandler), nodeScopeResolver, stmt, attrGroups, scope, storage, nodeCallback))) return zv::Val();
		/* [$templateTypeMap, ..., $phpDocParameterOutTypes, , , , $pureUnlessCallableIsImpureParameters] */
		pt_php_docs phpDocs;
		if (UNEXPECTED(!ptsh::getPhpDocs(OBJ_PROP_NUM(self, slots::phpDocsResolver), scope, stmt, 0x1FFFFu | (1u << 20), phpDocs))) return zv::Val();
		zval *docs[PT_PHP_DOCS_COUNT];
		for (uint32_t index = 0; index < PT_PHP_DOCS_COUNT; index++) docs[index] = &phpDocs.items[index];
		zval *templateTypeMap = docs[0];
		zval *phpDocParameterTypes = docs[1];
		zval *phpDocImmediatelyInvokedCallableParameters = docs[2];
		zval *phpDocClosureThisTypeParameters = docs[3];
		zval *phpDocReturnType = docs[4];
		zval *phpDocThrowType = docs[5];
		zval *deprecatedDescription = docs[6];
		zval *isDeprecated = docs[7];
		zval *isInternal = docs[8];
		zval *isFinal = docs[9];
		zval *isPure = docs[10];
		zval *acceptsNamedArguments = docs[11];
		zval *isReadOnly = docs[12];
		zval *phpDocComment = docs[13];
		zval *asserts = docs[14];
		zval *selfOutType = docs[15];
		zval *phpDocParameterOutTypes = docs[16];
		zval *pureUnlessCallableIsImpureParameters = docs[20];

		zval *params = ptsh::readNodeProperty(pt_cmh_params_site, stmt, PT_LC("params"));
		if (UNEXPECTED(params == NULL)) return zv::Val();
		if (UNEXPECTED(!ptsh::processParams(OBJ_PROP_NUM(self, slots::parametersProcessor), nodeScopeResolver, stmt, params, scope, storage, nodeCallback))) return zv::Val();

		zval *returnType = ptsh::readNodeProperty(pt_cmh_return_type_site, stmt, PT_LC("returnType"));
		if (UNEXPECTED(returnType == NULL)) return zv::Val();
		if (Z_TYPE_P(returnType) != IS_NULL) {
			if (UNEXPECTED(!pt_node_scope_resolver_call_node_callback(nodeScopeResolver, nodeCallback, returnType, scope, storage))) return zv::Val();
		}

		zv::Val attributeIsDeprecated;
		zv::Val attributeDeprecatedDescription;
		if (!zend_is_true(isDeprecated)) {
			if (UNEXPECTED(!ptsh::getDeprecatedAttribute(OBJ_PROP_NUM(self, slots::deprecatedAttributeResolver), scope, stmt, attributeIsDeprecated, attributeDeprecatedDescription))) return zv::Val();
			isDeprecated = attributeIsDeprecated.raw();
			deprecatedDescription = attributeDeprecatedDescription.raw();
		}

		zval falseValue;
		ZVAL_FALSE(&falseValue);
		zval *originalTraitMethodName = nodeAttribute(stmt, PT_LC("originalTraitMethodName"), &EG(uninitialized_zval));
		if (UNEXPECTED(originalTraitMethodName == NULL)) return zv::Val();
		bool isFromTrait = Z_TYPE_P(originalTraitMethodName) == IS_STRING && zend_string_equals_literal(Z_STR_P(originalTraitMethodName), "__construct");
		bool isConstructor = isFromTrait;
		if (!isConstructor) {
			zval *name = ptsh::readNodeProperty(pt_cmh_name_site, stmt, PT_LC("name"));
			if (UNEXPECTED(name == NULL)) return zv::Val();
			if (UNEXPECTED(Z_TYPE_P(name) != IS_OBJECT)) {
				memberCallOnNonObject("toLowerString", name);
				return zv::Val();
			}
			zval *identifier = ptsh::readNodeProperty(pt_cmh_identifier_name_site, name, PT_LC("name"));
			if (UNEXPECTED(identifier == NULL)) return zv::Val();
			isConstructor = Z_TYPE_P(identifier) == IS_STRING && zend_string_equals_literal_ci(Z_STR_P(identifier), "__construct");
		}

		zval enterArgv[20];
		ZVAL_COPY_VALUE(&enterArgv[0], stmt);
		ZVAL_COPY_VALUE(&enterArgv[1], templateTypeMap);
		ZVAL_COPY_VALUE(&enterArgv[2], phpDocParameterTypes);
		ZVAL_COPY_VALUE(&enterArgv[3], phpDocReturnType);
		ZVAL_COPY_VALUE(&enterArgv[4], phpDocThrowType);
		ZVAL_COPY_VALUE(&enterArgv[5], deprecatedDescription);
		ZVAL_COPY_VALUE(&enterArgv[6], isDeprecated);
		ZVAL_COPY_VALUE(&enterArgv[7], isInternal);
		ZVAL_COPY_VALUE(&enterArgv[8], isFinal);
		ZVAL_COPY_VALUE(&enterArgv[9], isPure);
		ZVAL_COPY_VALUE(&enterArgv[10], acceptsNamedArguments);
		ZVAL_COPY_VALUE(&enterArgv[11], asserts);
		ZVAL_COPY_VALUE(&enterArgv[12], selfOutType);
		ZVAL_COPY_VALUE(&enterArgv[13], phpDocComment);
		ZVAL_COPY_VALUE(&enterArgv[14], phpDocParameterOutTypes);
		ZVAL_COPY_VALUE(&enterArgv[15], phpDocImmediatelyInvokedCallableParameters);
		ZVAL_COPY_VALUE(&enterArgv[16], phpDocClosureThisTypeParameters);
		ZVAL_BOOL(&enterArgv[17], isConstructor);
		ZVAL_NULL(&enterArgv[18]);
		ZVAL_COPY_VALUE(&enterArgv[19], pureUnlessCallableIsImpureParameters);
		zv::Val methodScope = pt_mutating_scope_enter_class_method(Z_OBJ_P(scope), enterArgv);
		if (UNEXPECTED(methodScope.isUndef())) return zv::Val();

		bool inClass;
		if (UNEXPECTED(!pt_scope_is_in_class(Z_OBJ_P(scope), inClass))) return zv::Val();
		if (!inClass) {
			pt_throw_should_not_happen();
			return zv::Val();
		}

		zv::Val classReflection = pt_scope_get_class_reflection(Z_OBJ_P(scope));
		if (UNEXPECTED(classReflection.isUndef())) return zv::Val();

		if (isConstructor) {
			params = ptsh::readNodeProperty(pt_cmh_params_site, stmt, PT_LC("params"));
			if (UNEXPECTED(params == NULL)) return zv::Val();
			if (UNEXPECTED(Z_TYPE_P(params) != IS_ARRAY)) {
				zend_error(E_WARNING, "foreach() argument must be of type array|object, %s given", zend_zval_value_name(params));
				if (UNEXPECTED(EG(exception))) return zv::Val();
			} else {
				zv::Arr iterated = zv::Arr::copyOfTable(Z_ARRVAL_P(params));
				for (auto entry : zv::TableRef(iterated.table())) {
					zval *param = entry.value().deref().raw();
					if (UNEXPECTED(!processConstructorParam(nodeScopeResolver, stmt, param, scope, storage, nodeCallback, methodScope, classReflection.raw(), phpDocParameterTypes, isFromTrait, isReadOnly))) return zv::Val();
				}
			}
		}

		zval *virtualAttribute = nodeAttribute(stmt, PT_LC("virtual"), &falseValue);
		if (UNEXPECTED(virtualAttribute == NULL)) return zv::Val();
		if (Z_TYPE_P(virtualAttribute) == IS_FALSE) {
			zv::Val methodReflection = pt_mutating_scope_get_function(Z_OBJ_P(methodScope.raw()));
			if (UNEXPECTED(methodReflection.isUndef())) return zv::Val();
			bool error = false;
			if (!ptsh::isInstanceOf(methodReflection.raw(), PT_CLASS_PHP_METHOD_FROM_PARSER_NODE_REFLECTION, error)) {
				if (!error) pt_throw_should_not_happen();
				return zv::Val();
			}
			zv::Args nodeArgv{classReflection.raw(), methodReflection.raw(), stmt};
			zv::Val inClassMethodNode = pt_type_new(PT_CLASS_IN_CLASS_METHOD_NODE, 3, nodeArgv);
			if (UNEXPECTED(inClassMethodNode.isUndef())) return zv::Val();
			if (UNEXPECTED(!pt_node_scope_resolver_call_node_callback(nodeScopeResolver, nodeCallback, inClassMethodNode.raw(), methodScope.raw(), storage))) return zv::Val();
		}

		zv::Val finalScope = zv::Val::copyOf(zv::Ref(scope));
		zval *stmts = ptsh::readNodeProperty(pt_cmh_stmts_site, stmt, PT_LC("stmts"));
		if (UNEXPECTED(stmts == NULL)) return zv::Val();
		if (Z_TYPE_P(stmts) != IS_NULL) {
			zv::Val gatheredReturnStatements = ptsh::newArrayReference();
			zv::Val gatheredReturnStatementsAfterFinally = ptsh::newArrayReference();
			zv::Val gatheredYieldStatements = ptsh::newArrayReference();
			zv::Val executionEnds = ptsh::newArrayReference();
			zv::Val methodImpurePoints = ptsh::newArrayReference();
			// the body's results live in a per-body storage released right
			// after the MethodReturnStatementsNode rules ran: later asks about
			// body expressions (e.g. class-level rules pricing gathered nodes)
			// go through the on-demand bridge, so keeping the results for the
			// rest of the file would only pin the body's whole result graph
			// (callbacks, scopes, types) at no benefit
			zv::Val bodyStorage = pt_expression_result_storage_duplicate(storage);
			if (UNEXPECTED(bodyStorage.isUndef())) return zv::Val();
			if (UNEXPECTED(!pt_mutating_scope_push_expression_result_storage(Z_OBJ_P(scope), bodyStorage.raw()))) return zv::Val();
			bool walked = walkBody(nodeScopeResolver, stmt, stmts, methodScope.raw(), bodyStorage.raw(), nodeCallback, context, classReflection.raw(), gatheredReturnStatements.raw(), gatheredReturnStatementsAfterFinally.raw(), gatheredYieldStatements.raw(), executionEnds.raw(), methodImpurePoints.raw());
			pt_finally([&]() { (void) pt_mutating_scope_pop_expression_result_storage(Z_OBJ_P(scope)); });
			if (UNEXPECTED(!walked || EG(exception) != NULL)) return zv::Val();

			if (isConstructor) {
				zv::Val constructorScope;
				if (UNEXPECTED(!mergeConstructorScope(Z_REFVAL_P(executionEnds.raw()), Z_REFVAL_P(gatheredReturnStatements.raw()), constructorScope))) return zv::Val();
				if (!constructorScope.isUndef()) {
					finalScope = pt_mutating_scope_remember_constructor_scope(Z_OBJ_P(constructorScope.raw()));
					if (UNEXPECTED(finalScope.isUndef())) return zv::Val();
				}
			}
		}

		zval emptyArray;
		ZVAL_EMPTY_ARRAY(&emptyArray);
		return pt_internal_statement_result_new(finalScope.raw(), false, false, &emptyArray, &emptyArray, &emptyArray);
	}

	/* the statement-handler entry (Engine.h) */
	static zv::Val processStmtEntry(zend_object *handler, zval *nodeScopeResolver, zval *stmt, zval *scope, zval *storage, zval *nodeCallback, zval *context)
	{
		return ClassMethodHandler(handler).processStmt(nodeScopeResolver, stmt, scope, storage, nodeCallback, context);
	}

private:
	zend_object *self;

	/* the constructor loop's body over one parameter; `methodScope` is
	 * replaced by the scope with the property initialized; false = pending
	 * exception */
	[[nodiscard]] bool processConstructorParam(zval *nodeScopeResolver, zval *stmt, zval *param, zval *scope, zval *storage, zval *nodeCallback, zv::Val &methodScope, zval *classReflection, zval *phpDocParameterTypes, bool isFromTrait, zval *isReadOnly) const
	{
		if (UNEXPECTED(Z_TYPE_P(param) != IS_OBJECT)) {
			zend_error(E_WARNING, "Attempt to read property \"flags\" on %s", zend_zval_value_name(param));
			return !EG(exception);
		}
		zval *flags = ptsh::readNodeProperty(pt_cmh_param_flags_site, param, PT_LC("flags"));
		if (UNEXPECTED(flags == NULL)) return false;
		if (Z_TYPE_P(flags) == IS_LONG && Z_LVAL_P(flags) == 0) {
			zval *hooks = ptsh::readNodeProperty(pt_cmh_param_hooks_site, param, PT_LC("hooks"));
			if (UNEXPECTED(hooks == NULL)) return false;
			if (Z_TYPE_P(hooks) == IS_ARRAY && zend_hash_num_elements(Z_ARRVAL_P(hooks)) == 0) return true;
		}

		zval *var = ptsh::readNodeProperty(pt_cmh_param_var_site, param, PT_LC("var"));
		if (UNEXPECTED(var == NULL)) return false;
		bool error = false;
		if (!ptsh::isInstanceOf(var, PT_CLASS_VARIABLE, error)) {
			if (!error) pt_throw_should_not_happen();
			return false;
		}
		zval *name = ptsh::readNodeProperty(pt_cmh_variable_name_site, var, PT_LC("name"));
		if (UNEXPECTED(name == NULL)) return false;
		if (Z_TYPE_P(name) != IS_STRING || Z_STRLEN_P(name) == 0) {
			pt_throw_should_not_happen();
			return false;
		}
		/* $param->var->name, held: the node callbacks run rules */
		zv::Val propertyName = zv::Val::copyOf(zv::Ref(name));

		zv::Val phpDoc = zv::Val::null();
		zv::Val docComment = getDocComment(param);
		if (UNEXPECTED(docComment.isUndef())) return false;
		if (!docComment.isNull()) {
			zv::Val comment = getDocComment(param);
			if (UNEXPECTED(comment.isUndef())) return false;
			if (UNEXPECTED(Z_TYPE_P(comment.raw()) != IS_OBJECT)) {
				memberCallOnNonObject("getText", comment.raw());
				return false;
			}
			phpDoc = getText(comment.raw());
			if (UNEXPECTED(phpDoc.isUndef())) return false;
		}

		zval *flagsValue = ptsh::readNodeProperty(pt_cmh_param_flags_site, param, PT_LC("flags"));
		if (UNEXPECTED(flagsValue == NULL)) return false;
		zv::Val flagsHold = zv::Val::copyOf(zv::Ref(flagsValue));
		zval *type = ptsh::readNodeProperty(pt_cmh_param_type_site, param, PT_LC("type"));
		if (UNEXPECTED(type == NULL)) return false;
		zv::Val nativeType = zv::Val::null();
		if (Z_TYPE_P(type) != IS_NULL) {
			nativeType = resolveParserNodeType(type, classReflection);
			if (UNEXPECTED(nativeType.isUndef())) return false;
		}
		zval *phpDocType = coalesceItem(phpDocParameterTypes, propertyName.raw());
		bool isDeclaredInTrait;
		if (UNEXPECTED(!pt_mutating_scope_is_in_trait(Z_OBJ_P(scope), isDeclaredInTrait))) return false;
		bool isReadonlyClass;
		if (UNEXPECTED(!pt_class_reflection_is_read_only(Z_OBJ_P(classReflection), isReadonlyClass))) return false;
		zval nodeArgv[14];
		ZVAL_COPY_VALUE(&nodeArgv[0], propertyName.raw());
		ZVAL_COPY_VALUE(&nodeArgv[1], flagsHold.raw());
		ZVAL_COPY_VALUE(&nodeArgv[2], nativeType.raw());
		ZVAL_NULL(&nodeArgv[3]);
		ZVAL_COPY_VALUE(&nodeArgv[4], phpDoc.raw());
		ZVAL_COPY_VALUE(&nodeArgv[5], phpDocType);
		ZVAL_TRUE(&nodeArgv[6]);
		ZVAL_BOOL(&nodeArgv[7], isFromTrait);
		ZVAL_COPY_VALUE(&nodeArgv[8], param);
		ZVAL_COPY_VALUE(&nodeArgv[9], isReadOnly);
		ZVAL_BOOL(&nodeArgv[10], isDeclaredInTrait);
		ZVAL_BOOL(&nodeArgv[11], isReadonlyClass);
		ZVAL_FALSE(&nodeArgv[12]);
		ZVAL_COPY_VALUE(&nodeArgv[13], classReflection);
		zv::Val classPropertyNode = pt_type_new(PT_CLASS_CLASS_PROPERTY_NODE, 14, nodeArgv);
		if (UNEXPECTED(classPropertyNode.isUndef())) return false;
		if (UNEXPECTED(!pt_node_scope_resolver_call_node_callback(nodeScopeResolver, nodeCallback, classPropertyNode.raw(), methodScope.raw(), storage))) return false;

		zval *hookType = ptsh::readNodeProperty(pt_cmh_param_type_site, param, PT_LC("type"));
		if (UNEXPECTED(hookType == NULL)) return false;
		zv::Val hookTypeHold = zv::Val::copyOf(zv::Ref(hookType));
		phpDocType = coalesceItem(phpDocParameterTypes, propertyName.raw());
		zval *hooks = ptsh::readNodeProperty(pt_cmh_param_hooks_site, param, PT_LC("hooks"));
		if (UNEXPECTED(hooks == NULL)) return false;
		zv::Val hooksHold = zv::Val::copyOf(zv::Ref(hooks));
		if (UNEXPECTED(!pt_property_hooks_processor_process_property_hooks(OBJ_PROP_NUM(self, slots::propertyHooksProcessor), nodeScopeResolver, stmt, hookTypeHold.raw(), phpDocType, propertyName.raw(), hooksHold.raw(), scope, storage, nodeCallback))) return false;

		zv::Args exprArgv{propertyName.raw()};
		zv::Val initializationExpr = pt_type_new(PT_CLASS_PROPERTY_INITIALIZATION_EXPR, 1, exprArgv);
		if (UNEXPECTED(initializationExpr.isUndef())) return false;
		zval mixedType;
		if (UNEXPECTED(!pt_mixed_type_new(&mixedType))) return false;
		zv::Val type1 = zv::Val::adopt(mixedType);
		zval nativeMixedType;
		if (UNEXPECTED(!pt_mixed_type_new(&nativeMixedType))) return false;
		zv::Val type2 = zv::Val::adopt(nativeMixedType);
		zv::Val assigned = pt_mutating_scope_assign_expression(Z_OBJ_P(methodScope.raw()), Z_OBJ_P(initializationExpr.raw()), type1.raw(), type2.raw());
		if (UNEXPECTED(assigned.isUndef())) return false;
		methodScope = std::move(assigned);
		return true;
	}

	/* the outer try block: the gatherer frame pushed around the body walk,
	 * then the MethodReturnStatementsNode and the liveness node emitted;
	 * false = pending exception */
	[[nodiscard]] static bool walkBody(zval *nodeScopeResolver, zval *stmt, zval *stmts, zval *methodScope, zval *bodyStorage, zval *nodeCallback, zval *context, zval *classReflection, zval *gatheredReturnStatements, zval *gatheredReturnStatementsAfterFinally, zval *gatheredYieldStatements, zval *executionEnds, zval *methodImpurePoints)
	{
		zval captures[7];
		ZVAL_COPY_VALUE(&captures[0], nodeScopeResolver);
		ZVAL_COPY_VALUE(&captures[1], methodScope);
		ZVAL_COPY_VALUE(&captures[2], gatheredReturnStatements);
		ZVAL_COPY_VALUE(&captures[3], gatheredReturnStatementsAfterFinally);
		ZVAL_COPY_VALUE(&captures[4], gatheredYieldStatements);
		ZVAL_COPY_VALUE(&captures[5], executionEnds);
		ZVAL_COPY_VALUE(&captures[6], methodImpurePoints);
		zv::Val gatherer = pt_native_closure_new(&gathererBody, 7, captures, 0b1111100);
		if (UNEXPECTED(!pt_node_scope_resolver_push_node_gatherer(nodeScopeResolver, gatherer.raw()))) return false;

		zv::Val internalStatementResult;
		zv::Val statementResult;
		{
			bool resolveTemplateArguments;
			if (EXPECTED(pt_statement_context_should_resolve_template_arguments(context, resolveTemplateArguments))) {
				zv::Val statementContext = pt_statement_context_create_top_level(resolveTemplateArguments);
				if (EXPECTED(!statementContext.isUndef())) {
					internalStatementResult = pt_node_scope_resolver_process_stmt_nodes_internal(nodeScopeResolver, stmt, stmts, methodScope, bodyStorage, nodeCallback, statementContext.raw());
					if (EXPECTED(!internalStatementResult.isUndef())) {
						statementResult = pt_internal_statement_result_to_public(internalStatementResult.raw());
					}
				}
			}
		}
		pt_finally([&]() { (void) pt_node_scope_resolver_pop_node_gatherer(nodeScopeResolver); });
		if (UNEXPECTED(statementResult.isUndef() || EG(exception) != NULL)) return false;

		zv::Val methodReflection = pt_mutating_scope_get_function(Z_OBJ_P(methodScope));
		if (UNEXPECTED(methodReflection.isUndef())) return false;
		bool error = false;
		if (!ptsh::isInstanceOf(methodReflection.raw(), PT_CLASS_PHP_METHOD_FROM_PARSER_NODE_REFLECTION, error)) {
			if (!error) pt_throw_should_not_happen();
			return false;
		}

		zv::Val impurePointsHold;
		zval *resultImpurePoints = pt_statement_result_impure_points(statementResult.raw(), impurePointsHold);
		if (UNEXPECTED(resultImpurePoints == NULL)) return false;
		zv::Arr impurePoints = zv::Arr::empty();
		if (UNEXPECTED(!pt_callable_array_merge_into(impurePoints, resultImpurePoints) || !pt_callable_array_merge_into(impurePoints, Z_REFVAL_P(methodImpurePoints)))) return false;
		zval nodeArgv[9];
		ZVAL_COPY_VALUE(&nodeArgv[0], stmt);
		ZVAL_COPY_VALUE(&nodeArgv[1], Z_REFVAL_P(gatheredReturnStatements));
		ZVAL_COPY_VALUE(&nodeArgv[2], Z_REFVAL_P(gatheredReturnStatementsAfterFinally));
		ZVAL_COPY_VALUE(&nodeArgv[3], Z_REFVAL_P(gatheredYieldStatements));
		ZVAL_COPY_VALUE(&nodeArgv[4], statementResult.raw());
		ZVAL_COPY_VALUE(&nodeArgv[5], Z_REFVAL_P(executionEnds));
		ZVAL_COPY_VALUE(&nodeArgv[6], impurePoints.raw());
		ZVAL_COPY_VALUE(&nodeArgv[7], classReflection);
		ZVAL_COPY_VALUE(&nodeArgv[8], methodReflection.raw());
		zv::Val returnStatementsNode = pt_type_new(PT_CLASS_METHOD_RETURN_STATEMENTS_NODE, 9, nodeArgv);
		if (UNEXPECTED(returnStatementsNode.isUndef())) return false;
		if (UNEXPECTED(!pt_node_scope_resolver_call_node_callback(nodeScopeResolver, nodeCallback, returnStatementsNode.raw(), methodScope, bodyStorage))) return false;

		zv::Val flowHold;
		zval *variableFlow = pt_internal_statement_result_variable_flow(internalStatementResult.raw(), flowHold);
		if (UNEXPECTED(variableFlow == NULL)) return false;
		zv::Val livenessNode = pt_variable_liveness_resolver_resolve(stmt, variableFlow);
		if (UNEXPECTED(livenessNode.isUndef())) return false;
		return pt_node_scope_resolver_call_node_callback(nodeScopeResolver, nodeCallback, livenessNode.raw(), methodScope, bodyStorage);
	}

	/* the constructor's final scope: the non-terminating execution ends'
	 * scopes merged, then the return statements' walk scopes; UNDEF in
	 * `out` when there is none; false = pending exception */
	[[nodiscard]] static bool mergeConstructorScope(zval *executionEnds, zval *gatheredReturnStatements, zv::Val &out)
	{
		zv::Val finalScope;
		zv::Arr ends = zv::Arr::copyOfTable(Z_ARRVAL_P(executionEnds));
		for (auto entry : zv::TableRef(ends.table())) {
			zval *executionEnd = entry.value().deref().raw();
			zv::Val statementResult = getStatementResult(executionEnd);
			if (UNEXPECTED(statementResult.isUndef())) return false;
			bool isAlwaysTerminating;
			if (UNEXPECTED(!pt_statement_result_is_always_terminating(statementResult.raw(), isAlwaysTerminating))) return false;
			if (isAlwaysTerminating) continue;

			zv::Val endStatementResult = getStatementResult(executionEnd);
			if (UNEXPECTED(endStatementResult.isUndef())) return false;
			zv::Val endScopeHold;
			zval *endScope = pt_statement_result_scope(endStatementResult.raw(), endScopeHold);
			if (UNEXPECTED(endScope == NULL)) return false;
			if (finalScope.isUndef()) {
				finalScope = zv::Val::copyOf(zv::Ref(endScope));
				continue;
			}

			finalScope = pt_mutating_scope_merge_with(Z_OBJ_P(finalScope.raw()), endScope);
			if (UNEXPECTED(finalScope.isUndef())) return false;
		}

		zv::Arr returns = zv::Arr::copyOfTable(Z_ARRVAL_P(gatheredReturnStatements));
		for (auto entry : zv::TableRef(returns.table())) {
			zval *statement = entry.value().deref().raw();
			zv::Val statementScope = returnStatementScope(statement);
			if (UNEXPECTED(statementScope.isUndef())) return false;
			zv::Val walkScope = pt_mutating_scope_to_walk_scope(Z_OBJ_P(statementScope.raw()));
			if (UNEXPECTED(walkScope.isUndef())) return false;
			if (finalScope.isUndef()) {
				finalScope = std::move(walkScope);
				continue;
			}

			finalScope = pt_mutating_scope_merge_with(Z_OBJ_P(finalScope.raw()), walkScope.raw());
			if (UNEXPECTED(finalScope.isUndef())) return false;
		}

		out = std::move(finalScope);
		return true;
	}

	/* static function (Node $node, Scope $scope) use ($nodeScopeResolver,
	 * $methodScope, &$gatheredReturnStatements, &$gatheredReturnStatementsAfterFinally,
	 * &$gatheredYieldStatements, &$executionEnds, &$methodImpurePoints): void —
	 * captures in that order */
	static void gathererBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		(void) return_value;
		if (UNEXPECTED(argc < 2)) {
			zend_throw_error(zend_ce_argument_count_error, "Too few arguments to function PHPStan\\Analyser\\StmtHandler\\ClassMethodHandler::{closure}(), %u passed and exactly 2 expected", argc);
			return;
		}
		zval *node = &argv[0];
		zval *scope = &argv[1];
		if (UNEXPECTED(Z_TYPE_P(scope) != IS_OBJECT)) {
			memberCallOnNonObject("getFunction", scope);
			return;
		}
		zval *methodScope = &captures[1];
		{
			zv::Val function = pt_mutating_scope_get_function(Z_OBJ_P(scope));
			if (UNEXPECTED(function.isUndef())) return;
			zv::Val methodFunction = pt_mutating_scope_get_function(Z_OBJ_P(methodScope));
			if (UNEXPECTED(methodFunction.isUndef())) return;
			if (!zend_is_identical(function.raw(), methodFunction.raw())) return;
		}
		bool inAnonymousFunction;
		if (UNEXPECTED(!pt_mutating_scope_is_in_anonymous_function(Z_OBJ_P(scope), inAnonymousFunction))) return;
		if (inAnonymousFunction) return;

		bool error = false;
		bool isPropertyAssign = ptsh::isInstanceOf(node, PT_CLASS_PROPERTY_ASSIGN_NODE, error);
		if (UNEXPECTED(error)) return;
		if (isPropertyAssign) {
			bool assignsThis;
			if (UNEXPECTED(!isConstructorThisPropertyAssign(&captures[0], node, scope, assignsThis))) return;
			if (assignsThis) return;
			zv::Val impurePoint = pt_impure_point_new(scope, node, pt_cmh_property_assign, pt_cmh_property_assignment, true);
			if (UNEXPECTED(impurePoint.isUndef())) return;
			ptsh::appendToReference(&captures[6], impurePoint.raw());
			return;
		}
		bool isExecutionEnd = ptsh::isInstanceOf(node, PT_CLASS_EXECUTION_END_NODE, error);
		if (UNEXPECTED(error)) return;
		if (isExecutionEnd) {
			ptsh::appendToReference(&captures[5], node);
			return;
		}
		bool isReturnAfterFinally = ptsh::isInstanceOf(node, PT_CLASS_RETURN_AFTER_FINALLY_NODE, error);
		if (UNEXPECTED(error)) return;
		if (isReturnAfterFinally) {
			zv::Val returnNode = getReturnNode(node);
			if (UNEXPECTED(returnNode.isUndef())) return;
			zv::Args statementArgv{scope, returnNode.raw()};
			zv::Val statement = pt_type_new(PT_CLASS_RETURN_STATEMENT, 2, statementArgv);
			if (UNEXPECTED(statement.isUndef())) return;
			ptsh::appendToReference(&captures[3], statement.raw());
			return;
		}
		bool isYield = ptsh::isInstanceOf(node, PT_CLASS_YIELD, error) || ptsh::isInstanceOf(node, PT_CLASS_YIELD_FROM, error);
		if (UNEXPECTED(error)) return;
		if (isYield) {
			ptsh::appendToReference(&captures[4], node);
		}
		bool isReturn = ptsh::isInstanceOf(node, PT_CLASS_RETURN_STMT, error);
		if (!isReturn) return;

		zv::Args statementArgv{scope, node};
		zv::Val statement = pt_type_new(PT_CLASS_RETURN_STATEMENT, 2, statementArgv);
		if (UNEXPECTED(statement.isUndef())) return;
		ptsh::appendToReference(&captures[2], statement.raw());
	}

	/* the gatherer's skip of a constructor's `$this->x = ...` property
	 * assign: the fetch is a PropertyFetch, the scope's function a parsed
	 * method of a class whose constructor it is, and the fetched var's type
	 * contains $this; false = pending exception */
	[[nodiscard]] static bool isConstructorThisPropertyAssign(zval *nodeScopeResolver, zval *node, zval *scope, bool &out)
	{
		out = false;
		zval *propertyFetch = ptsh::readNodeProperty(pt_cmh_property_assign_fetch_site, node, PT_LC("propertyFetch"));
		if (UNEXPECTED(propertyFetch == NULL)) return false;
		bool error = false;
		if (!ptsh::isInstanceOf(propertyFetch, PT_CLASS_PROPERTY_FETCH, error)) return !error;

		{
			zv::Val function = pt_mutating_scope_get_function(Z_OBJ_P(scope));
			if (UNEXPECTED(function.isUndef())) return false;
			if (!ptsh::isInstanceOf(function.raw(), PT_CLASS_PHP_METHOD_FROM_PARSER_NODE_REFLECTION, error)) return !error;
		}

		{
			zv::Val function = pt_mutating_scope_get_function(Z_OBJ_P(scope));
			if (UNEXPECTED(function.isUndef())) return false;
			zv::Val declaringClass = getDeclaringClass(function.raw());
			if (UNEXPECTED(declaringClass.isUndef())) return false;
			bool hasConstructor;
			if (UNEXPECTED(!pt_class_reflection_has_constructor(Z_OBJ_P(declaringClass.raw()), hasConstructor))) return false;
			if (!hasConstructor) return true;
		}

		{
			zv::Val function = pt_mutating_scope_get_function(Z_OBJ_P(scope));
			if (UNEXPECTED(function.isUndef())) return false;
			zv::Val declaringClass = getDeclaringClass(function.raw());
			if (UNEXPECTED(declaringClass.isUndef())) return false;
			zv::Val constructor = pt_class_reflection_get_constructor(Z_OBJ_P(declaringClass.raw()));
			if (UNEXPECTED(constructor.isUndef())) return false;
			if (UNEXPECTED(Z_TYPE_P(constructor.raw()) != IS_OBJECT)) {
				memberCallOnNonObject("getName", constructor.raw());
				return false;
			}
			zv::Val constructorName = pt_type_call(Z_OBJ_P(constructor.raw()), PT_LC("getname"), 0, NULL);
			if (UNEXPECTED(constructorName.isUndef())) return false;
			zv::Val nameFunction = pt_mutating_scope_get_function(Z_OBJ_P(scope));
			if (UNEXPECTED(nameFunction.isUndef())) return false;
			if (UNEXPECTED(Z_TYPE_P(nameFunction.raw()) != IS_OBJECT)) {
				memberCallOnNonObject("getName", nameFunction.raw());
				return false;
			}
			zv::Val functionName = getMethodName(nameFunction.raw());
			if (UNEXPECTED(functionName.isUndef())) return false;
			if (!zend_is_identical(constructorName.raw(), functionName.raw())) return true;
		}

		propertyFetch = ptsh::readNodeProperty(pt_cmh_property_assign_fetch_site, node, PT_LC("propertyFetch"));
		if (UNEXPECTED(propertyFetch == NULL)) return false;
		if (UNEXPECTED(Z_TYPE_P(propertyFetch) != IS_OBJECT)) {
			zend_error(E_WARNING, "Attempt to read property \"var\" on %s", zend_zval_value_name(propertyFetch));
			return !EG(exception);
		}
		zval *var = ptsh::readNodeProperty(pt_cmh_property_fetch_var_site, propertyFetch, PT_LC("var"));
		if (UNEXPECTED(var == NULL)) return false;
		zv::Val varHold = zv::Val::copyOf(zv::Ref(var));
		zv::Val walkScope = pt_mutating_scope_to_walk_scope(Z_OBJ_P(scope));
		if (UNEXPECTED(walkScope.isUndef())) return false;
		zv::Val type = pt_node_scope_resolver_read_scope_state_or_synthetic_type(nodeScopeResolver, varHold.raw(), walkScope.raw());
		if (UNEXPECTED(type.isUndef())) return false;
		zv::Val thisType = pt_type_utils_find_this_type(type.raw());
		if (UNEXPECTED(thisType.isUndef())) return false;
		out = !thisType.isNull();
		return true;
	}
};

} // namespace phpstanturbo

using phpstanturbo::ClassMethodHandler;

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

void pt_register_class_method_handler()
{
	pt_cmh_property_assign = zend_string_init_interned(PT_LC("propertyAssign"), 1);
	pt_cmh_property_assignment = zend_string_init_interned(PT_LC("property assignment"), 1);

	reg::Class cls("PHPStan\\Analyser\\StmtHandler\\ClassMethodHandler");
	ptdecl::ClassMethodHandler::declareClass(cls);
	ptdecl::ClassMethodHandler::declareProperties(cls);

	/* the real parameter class names: the DI container autowires the
	 * service by reflecting the constructor */
	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *deprecatedAttributeResolver, *phpDocsResolver, *propertyHooksProcessor, *attributesHandler, *parametersProcessor;
		if (!zp::parse<zp::Obj, zp::Obj, zp::Obj, zp::Obj, zp::Obj>(execute_data, deprecatedAttributeResolver, phpDocsResolver, propertyHooksProcessor, attributesHandler, parametersProcessor)) RETURN_THROWS();
		ClassMethodHandler(Z_OBJ_P(ZEND_THIS)).construct(deprecatedAttributeResolver, phpDocsResolver, propertyHooksProcessor, attributesHandler, parametersProcessor);
	});

	cls.method<&ClassMethodHandler::supports, zp::Obj>(sigs::supports);

	cls.method(sigs::processStmt, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *nodeScopeResolver, *stmt, *scope, *storage, *nodeCallback, *context;
		ZEND_PARSE_PARAMETERS_START(6, 6)
			Z_PARAM_OBJECT(nodeScopeResolver)
			Z_PARAM_OBJECT(stmt)
			Z_PARAM_OBJECT(scope)
			Z_PARAM_OBJECT(storage)
			Z_PARAM_ZVAL(nodeCallback)
			Z_PARAM_OBJECT(context)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(ClassMethodHandler(Z_OBJ_P(ZEND_THIS)).processStmt(nodeScopeResolver, stmt, scope, storage, nodeCallback, context));
	});

	cls.shadow(&pt_ce_class_method_handler);
	pt_stmt_handler_entry_register(&pt_ce_class_method_handler, &ClassMethodHandler::processStmtEntry);
}

/* }}} */
