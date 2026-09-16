/*
 * PHPStanTurbo\PhpDocsResolver — native implementation of
 * PHPStan\Analyser\PhpDocsResolver.
 *
 * A DI service (#[AutowiredService]): the constructor keeps the twin's
 * arginfo so Nette autowires it. getPhpDocs() — asked by ClassMethodHandler,
 * FunctionHandler, PropertyHandler, PropertyHooksProcessor and
 * PhpClassReflectionExtension for every declaration — is exported as
 * pt_php_docs_resolver_get_php_docs(), which hands the 21 values over
 * without building the twin's array; the PHP method builds it.
 *
 * FileTypeMapper, PhpDocInheritanceResolver, ResolvedPhpDocBlock, the PHPDoc
 * tags and Assertions stay PHP (the PHPDoc infrastructure) and are called
 * through the cached sites below. MutatingScope, ClassReflection,
 * TemplateTypeMap, TemplateTypeHelper, TypeTraverser, TypeCombinator and the
 * Type family are called through their direct entries; transformStaticType()'s
 * TypeTraverser callback is a native closure. The php-parser getters the twin
 * calls (getDocComment()->getText(), getParams(), getReturnType()) read the
 * node's properties while the node's class inherits the vendored method,
 * and call it otherwise.
 */

#include "support.h"
#include "generated/PhpDocsResolver.h"

namespace slots = ptdecl::PhpDocsResolver::slot;
namespace sigs = ptdecl::PhpDocsResolver::sig;
#include "zv.h"
#include "TypeTraits.h"
#include "TypeOps.h"
#include "Engine.h"
#include "StmtHandlerCalls.h"

zend_class_entry *pt_ce_php_docs_resolver = nullptr;

namespace {

/* {{{ the PHP collaborators (one site each) */

pt_method_site pt_pdr_get_resolved_php_doc_site;
pt_method_site pt_pdr_resolve_php_doc_for_method_site;
pt_method_site pt_pdr_get_var_tags_site;
pt_method_site pt_pdr_var_tag_get_type_site;
pt_method_site pt_pdr_get_template_type_map_site;
pt_method_site pt_pdr_get_params_immediately_invoked_callable_site;
pt_method_site pt_pdr_get_param_tags_site;
pt_method_site pt_pdr_param_tag_get_type_site;
pt_method_site pt_pdr_get_param_closure_this_tags_site;
pt_method_site pt_pdr_param_closure_this_tag_get_type_site;
pt_method_site pt_pdr_get_param_out_tags_site;
pt_method_site pt_pdr_param_out_tag_get_type_site;
pt_method_site pt_pdr_get_return_tag_site;
pt_method_site pt_pdr_return_tag_get_type_site;
pt_method_site pt_pdr_return_tag_is_explicit_site;
pt_method_site pt_pdr_get_throws_tag_site;
pt_method_site pt_pdr_throws_tag_get_type_site;
pt_method_site pt_pdr_get_deprecated_tag_site;
pt_method_site pt_pdr_deprecated_tag_get_message_site;
pt_method_site pt_pdr_is_deprecated_site;
pt_method_site pt_pdr_is_internal_site;
pt_method_site pt_pdr_is_final_site;
pt_method_site pt_pdr_is_pure_site;
pt_method_site pt_pdr_is_allowed_private_mutation_site;
pt_method_site pt_pdr_accepts_named_arguments_site;
pt_method_site pt_pdr_is_read_only_site;
pt_method_site pt_pdr_create_assertions_site;
pt_method_site pt_pdr_create_empty_assertions_site;
pt_method_site pt_pdr_get_self_out_tag_site;
pt_method_site pt_pdr_self_out_tag_get_type_site;
pt_method_site pt_pdr_get_params_pure_unless_callable_is_impure_site;
pt_method_site pt_pdr_are_all_methods_pure_site;
pt_method_site pt_pdr_are_all_methods_impure_site;

/* $object->method() of a PHP collaborator */
inline zv::Val call0(pt_method_site &site, zval *object, const char *lcname, size_t len)
{
	return pt_call_method_cached(site, Z_OBJ_P(object), lcname, len, 0, NULL);
}

/* $fileTypeMapper->getResolvedPhpDoc($file, $class, $trait, $functionName, $docComment) */
zv::Val getResolvedPhpDoc(zval *fileTypeMapper, zval *file, zval *className, zval *traitName, zval *functionName, zval *docComment)
{
	zv::Args argv{file, className, traitName, functionName, docComment};
	return pt_call_method_cached(pt_pdr_get_resolved_php_doc_site, Z_OBJ_P(fileTypeMapper), PT_LC("getresolvedphpdoc"), 5, argv);
}

/* $phpDocInheritanceResolver->resolvePhpDocForMethod($classReflection,
 * $methodName, $resolvedPhpDoc, $positionalParameterNames) */
zv::Val resolvePhpDocForMethod(zval *phpDocInheritanceResolver, zval *classReflection, zval *methodName, zval *resolvedPhpDoc, zval *positionalParameterNames)
{
	zv::Args argv{classReflection, methodName, resolvedPhpDoc, positionalParameterNames};
	return pt_call_method_cached(pt_pdr_resolve_php_doc_for_method_site, Z_OBJ_P(phpDocInheritanceResolver), PT_LC("resolvephpdocformethod"), 4, argv);
}

/* Assertions::createFromResolvedPhpDocBlock($resolvedPhpDoc) / Assertions::createEmpty() */
zv::Val createAssertions(zval *resolvedPhpDoc)
{
	return pt_call_static_cached(pt_pdr_create_assertions_site, PT_CLASS_ASSERTIONS, PT_LC("createfromresolvedphpdocblock"), 1, resolvedPhpDoc);
}

zv::Val createEmptyAssertions()
{
	return pt_call_static_cached(pt_pdr_create_empty_assertions_site, PT_CLASS_ASSERTIONS, PT_LC("createempty"), 0, NULL);
}

/* }}} */

pt_property_site pt_pdr_name_site;
pt_property_site pt_pdr_identifier_name_site;
pt_property_site pt_pdr_params_site;
pt_property_site pt_pdr_param_flags_site;
pt_property_site pt_pdr_param_var_site;
pt_property_site pt_pdr_variable_name_site;
pt_property_site pt_pdr_return_type_site;
pt_property_site pt_pdr_comment_text_site;

/* the per-class resolutions of the php-parser getters */
pt_method_site pt_pdr_get_doc_comment_fn_site;
pt_method_site pt_pdr_get_doc_comment_call_site;
pt_method_site pt_pdr_comment_get_text_fn_site;
pt_method_site pt_pdr_comment_get_text_call_site;
pt_method_site pt_pdr_get_params_fn_site;
pt_method_site pt_pdr_get_params_call_site;
pt_method_site pt_pdr_get_return_type_fn_site;
pt_method_site pt_pdr_get_return_type_call_site;
pt_method_site pt_pdr_to_string_site;

/* the '__construct' literal (module startup) */
zend_string *pt_pdr_construct = nullptr;

zend_never_inline ZEND_COLD void memberCallOnNonObject(const char *method, zval *value)
{
	zend_throw_error(NULL, "Call to a member function %s() on %s", method, zend_zval_value_name(value));
}

/* the method a class resolves the name to (cached per site), NULL when it
 * has none (nothing thrown) */
zend_function *resolveMethod(pt_method_site &site, zend_class_entry *ce, const char *lcname, size_t len)
{
	if (EXPECTED(site.ce == ce && site.generation == pt_engine_generation && site.fn != NULL)) return site.fn;
	zend_function *fn = (zend_function *) zend_hash_str_find_ptr(&ce->function_table, lcname, len);
	if (fn != NULL) site = { ce, fn, pt_engine_generation };
	return fn;
}

/* whether the object's class inherits the method from exactly `declaring` */
bool inheritsMethod(pt_method_site &site, zend_object *object, const char *lcname, size_t len, zend_class_entry *declaring)
{
	if (UNEXPECTED(declaring == NULL)) return false;
	zend_function *fn = resolveMethod(site, object->ce, lcname, len);
	return fn != NULL && fn->common.scope == declaring;
}

/* $comment->getText() */
zv::Val commentText(zval *comment)
{
	zend_class_entry *docCe = pt_class(PT_CLASS_DOC_COMMENT);
	if (UNEXPECTED(docCe == NULL)) return zv::Val();
	/* Comment::getText(): return $this->text; */
	if (EXPECTED(docCe->parent != NULL && inheritsMethod(pt_pdr_comment_get_text_fn_site, Z_OBJ_P(comment), PT_LC("gettext"), docCe->parent))) {
		zval *text = pt_property_cached(pt_pdr_comment_text_site, Z_OBJ_P(comment), PT_LC("text"));
		if (EXPECTED(text != NULL)) {
			ZVAL_DEREF(text);
			if (EXPECTED(Z_TYPE_P(text) == IS_STRING)) return zv::Val::copyOf(zv::Ref(text));
		}
	}
	return call0(pt_pdr_comment_get_text_call_site, comment, PT_LC("gettext"));
}

/* $node->getParams() */
zv::Val nodeParams(zval *node)
{
	zend_class_entry *classMethodCe = pt_class(PT_CLASS_CLASS_METHOD_STMT);
	if (UNEXPECTED(classMethodCe == NULL)) return zv::Val();
	if (EXPECTED(inheritsMethod(pt_pdr_get_params_fn_site, Z_OBJ_P(node), PT_LC("getparams"), classMethodCe))) {
		zval *params = pt_property_cached(pt_pdr_params_site, Z_OBJ_P(node), PT_LC("params"));
		if (EXPECTED(params != NULL)) {
			ZVAL_DEREF(params);
			if (EXPECTED(Z_TYPE_P(params) == IS_ARRAY)) return zv::Val::copyOf(zv::Ref(params));
		}
	}
	return call0(pt_pdr_get_params_call_site, node, PT_LC("getparams"));
}

/* $node->getReturnType() of a FunctionLike */
zv::Val nodeReturnType(zval *node)
{
	zend_class_entry *classMethodCe = pt_class(PT_CLASS_CLASS_METHOD_STMT);
	if (UNEXPECTED(classMethodCe == NULL)) return zv::Val();
	zend_class_entry *functionCe = pt_class(PT_CLASS_FUNCTION_STMT);
	if (UNEXPECTED(functionCe == NULL)) return zv::Val();
	zend_function *fn = resolveMethod(pt_pdr_get_return_type_fn_site, Z_OBJCE_P(node), PT_LC("getreturntype"));
	if (EXPECTED(fn != NULL && (fn->common.scope == classMethodCe || fn->common.scope == functionCe))) {
		/* ClassMethod / Function_::getReturnType(): return $this->returnType; */
		zval *returnType = pt_property_cached(pt_pdr_return_type_site, Z_OBJ_P(node), PT_LC("returnType"));
		if (EXPECTED(returnType != NULL && Z_TYPE_P(returnType) != IS_UNDEF)) return zv::Val::copyOf(zv::Ref(returnType).deref());
	}
	return call0(pt_pdr_get_return_type_call_site, node, PT_LC("getreturntype"));
}

/* $identifier->name of an Identifier node (dereferenced), the twin's
 * warning for a missing property; NULL = pending exception */
zval *identifierName(zval *identifier, const char *property)
{
	if (UNEXPECTED(Z_TYPE_P(identifier) != IS_OBJECT)) {
		zend_error(E_WARNING, "Attempt to read property \"%s\" on %s", property, zend_zval_value_name(identifier));
		if (UNEXPECTED(EG(exception))) return NULL;
		return &EG(uninitialized_zval);
	}
	return ptsh::readNodeProperty(pt_pdr_identifier_name_site, identifier, PT_LC("name"));
}

/* $node->name (dereferenced; NULL = pending exception) */
zval *nodeName(zval *node)
{
	return ptsh::readNodeProperty(pt_pdr_name_site, node, PT_LC("name"));
}

/* $identifier->toString() / ->toLowerString() === '__construct' of an
 * Identifier (Identifier::toString() returns $this->name) */
zv::Val identifierToString(zval *identifier)
{
	if (UNEXPECTED(Z_TYPE_P(identifier) != IS_OBJECT)) {
		memberCallOnNonObject("toString", identifier);
		return zv::Val();
	}
	return call0(pt_pdr_to_string_site, identifier, PT_LC("tostring"));
}

} // namespace

/* $node->getDocComment()?->getText() */
zv::Val pt_node_doc_comment_text(zval *node)
{
	zend_class_entry *nodeAbstractCe = pt_class(PT_CLASS_NODE_ABSTRACT);
	if (UNEXPECTED(nodeAbstractCe == NULL)) return zv::Val();
	zend_class_entry *docCe = pt_class(PT_CLASS_DOC_COMMENT);
	if (UNEXPECTED(docCe == NULL)) return zv::Val();
	if (EXPECTED(inheritsMethod(pt_pdr_get_doc_comment_fn_site, Z_OBJ_P(node), PT_LC("getdoccomment"), nodeAbstractCe))) {
		/* NodeAbstract::getDocComment(): the last Comment\Doc of getComments() */
		zv::Val comments = pt_engine_node_get_comments(Z_OBJ_P(node));
		if (UNEXPECTED(comments.isUndef())) return zv::Val();
		if (EXPECTED(Z_TYPE_P(comments.raw()) == IS_ARRAY)) {
			HashTable *table = Z_ARRVAL_P(comments.raw());
			for (zend_long i = (zend_long) zend_hash_num_elements(table) - 1; i >= 0; i--) {
				zval *comment = zend_hash_index_find(table, (zend_ulong) i);
				if (UNEXPECTED(comment == NULL)) {
					zend_error(E_WARNING, "Undefined array key " ZEND_LONG_FMT, i);
					if (UNEXPECTED(EG(exception))) return zv::Val();
					continue;
				}
				ZVAL_DEREF(comment);
				if (Z_TYPE_P(comment) == IS_OBJECT && instanceof_function(Z_OBJCE_P(comment), docCe)) {
					zval hold;
					ZVAL_COPY(&hold, comment);
					zv::Val owned = zv::Val::adopt(hold);
					return commentText(owned.raw());
				}
			}
			return zv::Val::null();
		}
	}
	zv::Val docComment = call0(pt_pdr_get_doc_comment_call_site, node, PT_LC("getdoccomment"));
	if (UNEXPECTED(docComment.isUndef()) || docComment.isNull()) return docComment;
	if (UNEXPECTED(Z_TYPE_P(docComment.raw()) != IS_OBJECT)) {
		memberCallOnNonObject("getText", docComment.raw());
		return zv::Val();
	}
	return commentText(docComment.raw());
}

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\PhpDocsResolver; false / UNDEF = pending
 * exception. */
class PhpDocsResolver
{
public:
	explicit PhpDocsResolver(zend_object *self) : self(self) {}

	/* the constructor body: the promoted properties */
	void construct(zval *fileTypeMapper, zval *phpDocInheritanceResolver)
	{
		zv::ObjRef object(self);
		object.propAtWrite(slots::fileTypeMapper, zv::Val::copyOf(zv::Ref(fileTypeMapper)));
		object.propAtWrite(slots::phpDocInheritanceResolver, zv::Val::copyOf(zv::Ref(phpDocInheritanceResolver)));
	}

	/* Mirrors getPhpDocs(): the list's items into out.items */
	[[nodiscard]] bool getPhpDocs(zval *scope, zval *node, pt_php_docs &out) const
	{
		zval *items = out.items;
		zv::Arr phpDocParameterTypes = zv::Arr::empty();
		zv::Arr phpDocImmediatelyInvokedCallableParameters = zv::Arr::empty();
		zv::Arr phpDocClosureThisTypeParameters = zv::Arr::empty();
		zv::Val phpDocReturnType = zv::Val::null();
		zv::Val phpDocThrowType = zv::Val::null();
		zv::Val deprecatedDescription = zv::Val::null();
		zv::Val isDeprecated = zv::Val::boolean(false);
		zv::Val isInternal = zv::Val::boolean(false);
		zv::Val isFinal = zv::Val::boolean(false);
		zv::Val isPure = zv::Val::null();
		zv::Val isAllowedPrivateMutation = zv::Val::boolean(false);
		zv::Val acceptsNamedArguments = zv::Val::boolean(true);
		zv::Val asserts;
		zv::Val selfOutType = zv::Val::null();
		zv::Arr phpDocParameterOutTypes = zv::Arr::empty();
		zv::Arr phpDocPureUnlessCallableIsImpureParameters = zv::Arr::empty();
		zv::Val templateTypeMap;

		{
			zval empty;
			if (UNEXPECTED(!pt_template_type_map_empty(&empty))) return false;
			templateTypeMap = zv::Val::adopt(empty);
		}

		bool inClass;
		if (UNEXPECTED(!pt_scope_is_in_class(Z_OBJ_P(scope), inClass))) return false;
		bool isReadOnly = false;
		/* $scope->getClassReflection(), read once: the scope's context holds it */
		zv::Val classReflection;
		if (inClass) {
			classReflection = pt_scope_get_class_reflection(Z_OBJ_P(scope));
			if (UNEXPECTED(classReflection.isUndef())) return false;
			if (UNEXPECTED(Z_TYPE_P(classReflection.raw()) != IS_OBJECT)) {
				memberCallOnNonObject("isImmutable", classReflection.raw());
				return false;
			}
			if (UNEXPECTED(!pt_class_reflection_is_immutable(Z_OBJ_P(classReflection.raw()), isReadOnly))) return false;
		}

		zv::Val docComment = pt_node_doc_comment_text(node);
		if (UNEXPECTED(docComment.isUndef())) return false;

		zv::Val file = pt_mutating_scope_get_file(Z_OBJ_P(scope));
		if (UNEXPECTED(file.isUndef())) return false;
		zv::Val className = zv::Val::null();
		if (inClass) {
			className = pt_class_reflection_get_name(Z_OBJ_P(classReflection.raw()));
			if (UNEXPECTED(className.isUndef())) return false;
		}
		zv::Val traitName = zv::Val::null();
		{
			bool inTrait;
			if (UNEXPECTED(!pt_mutating_scope_is_in_trait(Z_OBJ_P(scope), inTrait))) return false;
			if (inTrait) {
				zv::Val traitReflection = pt_mutating_scope_get_trait_reflection(Z_OBJ_P(scope));
				if (UNEXPECTED(traitReflection.isUndef())) return false;
				if (UNEXPECTED(Z_TYPE_P(traitReflection.raw()) != IS_OBJECT)) {
					memberCallOnNonObject("getName", traitReflection.raw());
					return false;
				}
				traitName = pt_class_reflection_get_name(Z_OBJ_P(traitReflection.raw()));
				if (UNEXPECTED(traitName.isUndef())) return false;
			}
		}
		zv::Val resolvedPhpDoc = zv::Val::null();
		zv::Val functionName = zv::Val::null();

		bool error = false;
		bool isClassMethod = ptsh::isInstanceOf(node, PT_CLASS_CLASS_METHOD_STMT, error);
		if (UNEXPECTED(error)) return false;
		if (isClassMethod) {
			if (UNEXPECTED(!resolveClassMethodPhpDoc(scope, node, inClass, classReflection, file.raw(), className.raw(), traitName.raw(), docComment.raw(), phpDocParameterTypes, functionName, resolvedPhpDoc))) return false;
		} else if (ptsh::isInstanceOf(node, PT_CLASS_FUNCTION_STMT, error)) {
			/* trim($scope->getNamespace() . '\\' . $node->name->name, '\\') */
			zv::Val ns = pt_mutating_scope_get_namespace(Z_OBJ_P(scope));
			if (UNEXPECTED(ns.isUndef())) return false;
			zval *name = nodeName(node);
			if (UNEXPECTED(name == NULL)) return false;
			zval *identifier = identifierName(name, "name");
			if (UNEXPECTED(identifier == NULL)) return false;
			zend_string *nsString = zval_try_get_string(ns.raw());
			if (UNEXPECTED(nsString == NULL)) return false;
			zv::Str nsOwned = zv::Str::adopt(nsString);
			zend_string *identifierString = zval_try_get_string(identifier);
			if (UNEXPECTED(identifierString == NULL)) return false;
			zv::Str identifierOwned = zv::Str::adopt(identifierString);
			size_t len = ZSTR_LEN(nsString) + 1 + ZSTR_LEN(identifierString);
			zend_string *joined = zend_string_alloc(len, 0);
			memcpy(ZSTR_VAL(joined), ZSTR_VAL(nsString), ZSTR_LEN(nsString));
			ZSTR_VAL(joined)[ZSTR_LEN(nsString)] = '\\';
			memcpy(ZSTR_VAL(joined) + ZSTR_LEN(nsString) + 1, ZSTR_VAL(identifierString), ZSTR_LEN(identifierString));
			ZSTR_VAL(joined)[len] = '\0';
			const char *start = ZSTR_VAL(joined);
			const char *end = start + len;
			while (start < end && *start == '\\') start++;
			while (end > start && end[-1] == '\\') end--;
			if (start == ZSTR_VAL(joined) && end == ZSTR_VAL(joined) + len) {
				functionName = zv::Val::adoptString(joined);
			} else {
				functionName = zv::Val::string(start, (size_t) (end - start));
				zend_string_release(joined);
			}
		} else if (UNEXPECTED(error)) {
			return false;
		} else if (ptsh::isInstanceOf(node, PT_CLASS_PROPERTY_HOOK, error)) {
			zv::Val propertyName = pt_engine_node_get_attribute(Z_OBJ_P(node), PT_LC("propertyName"));
			if (UNEXPECTED(propertyName.isUndef())) return false;
			if (!propertyName.isNull()) {
				zval *name = nodeName(node);
				if (UNEXPECTED(name == NULL)) return false;
				zv::Val hookName = identifierToString(name);
				if (UNEXPECTED(hookName.isUndef())) return false;
				zend_string *propertyNameString = zval_try_get_string(propertyName.raw());
				if (UNEXPECTED(propertyNameString == NULL)) return false;
				zend_string *hookNameString = zval_try_get_string(hookName.raw());
				if (UNEXPECTED(hookNameString == NULL)) {
					zend_string_release(propertyNameString);
					return false;
				}
				functionName = zv::Val::adoptString(zend_strpprintf(0, "$%s::%s", ZSTR_VAL(propertyNameString), ZSTR_VAL(hookNameString)));
				zend_string_release(propertyNameString);
				zend_string_release(hookNameString);
			}
		} else if (UNEXPECTED(error)) {
			return false;
		}

		if (!docComment.isNull() && resolvedPhpDoc.isNull()) {
			resolvedPhpDoc = getResolvedPhpDoc(OBJ_PROP_NUM(self, slots::fileTypeMapper), file.raw(), className.raw(), traitName.raw(), functionName.raw(), docComment.raw());
			if (UNEXPECTED(resolvedPhpDoc.isUndef())) return false;
		}

		bool isFunctionLike = ptsh::isInstanceOf(node, PT_CLASS_FUNCTION_LIKE, error);
		if (UNEXPECTED(error)) return false;

		zv::Val varTags = zv::Val(zv::Arr::empty());
		if (!resolvedPhpDoc.isNull()) {
			zval *doc = resolvedPhpDoc.raw();
			if (UNEXPECTED(Z_TYPE_P(doc) != IS_OBJECT)) {
				memberCallOnNonObject("getTemplateTypeMap", doc);
				return false;
			}
			templateTypeMap = call0(pt_pdr_get_template_type_map_site, doc, PT_LC("gettemplatetypemap"));
			if (UNEXPECTED(templateTypeMap.isUndef())) return false;
			{
				zv::Val value = call0(pt_pdr_get_params_immediately_invoked_callable_site, doc, PT_LC("getparamsimmediatelyinvokedcallable"));
				if (UNEXPECTED(value.isUndef())) return false;
				phpDocImmediatelyInvokedCallableParameters = zv::Arr::adoptVal(std::move(value));
			}
			if (UNEXPECTED(!collectTagTypes(call0(pt_pdr_get_param_tags_site, doc, PT_LC("getparamtags")), pt_pdr_param_tag_get_type_site, true, scope, classReflection, phpDocParameterTypes))) return false;
			if (UNEXPECTED(!collectTagTypes(call0(pt_pdr_get_param_closure_this_tags_site, doc, PT_LC("getparamclosurethistags")), pt_pdr_param_closure_this_tag_get_type_site, true, scope, classReflection, phpDocClosureThisTypeParameters))) return false;
			if (UNEXPECTED(!collectTagTypes(call0(pt_pdr_get_param_out_tags_site, doc, PT_LC("getparamouttags")), pt_pdr_param_out_tag_get_type_site, false, scope, classReflection, phpDocParameterOutTypes))) return false;
			if (isFunctionLike) {
				zv::Val returnTypeNode = nodeReturnType(node);
				if (UNEXPECTED(returnTypeNode.isUndef())) return false;
				zv::Val nativeReturnType = pt_mutating_scope_get_function_type(Z_OBJ_P(scope), returnTypeNode.raw(), false, false);
				if (UNEXPECTED(nativeReturnType.isUndef())) return false;
				phpDocReturnType = getPhpDocReturnType(doc, nativeReturnType.raw());
				if (UNEXPECTED(phpDocReturnType.isUndef())) return false;
				if (!phpDocReturnType.isNull()) {
					bool stillInClass;
					if (UNEXPECTED(!pt_scope_is_in_class(Z_OBJ_P(scope), stillInClass))) return false;
					if (stillInClass) {
						phpDocReturnType = transformStaticType(scope, classReflection, phpDocReturnType.raw());
						if (UNEXPECTED(phpDocReturnType.isUndef())) return false;
					}
				}
			}
			phpDocThrowType = tagType(pt_pdr_get_throws_tag_site, PT_LC("getthrowstag"), doc, pt_pdr_throws_tag_get_type_site, PT_LC("gettype"));
			if (UNEXPECTED(phpDocThrowType.isUndef())) return false;
			deprecatedDescription = tagType(pt_pdr_get_deprecated_tag_site, PT_LC("getdeprecatedtag"), doc, pt_pdr_deprecated_tag_get_message_site, PT_LC("getmessage"));
			if (UNEXPECTED(deprecatedDescription.isUndef())) return false;
			isDeprecated = call0(pt_pdr_is_deprecated_site, doc, PT_LC("isdeprecated"));
			if (UNEXPECTED(isDeprecated.isUndef())) return false;
			isInternal = call0(pt_pdr_is_internal_site, doc, PT_LC("isinternal"));
			if (UNEXPECTED(isInternal.isUndef())) return false;
			isFinal = call0(pt_pdr_is_final_site, doc, PT_LC("isfinal"));
			if (UNEXPECTED(isFinal.isUndef())) return false;
			isPure = call0(pt_pdr_is_pure_site, doc, PT_LC("ispure"));
			if (UNEXPECTED(isPure.isUndef())) return false;
			isAllowedPrivateMutation = call0(pt_pdr_is_allowed_private_mutation_site, doc, PT_LC("isallowedprivatemutation"));
			if (UNEXPECTED(isAllowedPrivateMutation.isUndef())) return false;
			acceptsNamedArguments = call0(pt_pdr_accepts_named_arguments_site, doc, PT_LC("acceptsnamedarguments"));
			if (UNEXPECTED(acceptsNamedArguments.isUndef())) return false;
			if (!isReadOnly) {
				zv::Val readOnly = call0(pt_pdr_is_read_only_site, doc, PT_LC("isreadonly"));
				if (UNEXPECTED(readOnly.isUndef())) return false;
				isReadOnly = zend_is_true(readOnly.raw());
			}
			asserts = createAssertions(doc);
			if (UNEXPECTED(asserts.isUndef())) return false;
			selfOutType = tagType(pt_pdr_get_self_out_tag_site, PT_LC("getselfouttag"), doc, pt_pdr_self_out_tag_get_type_site, PT_LC("gettype"));
			if (UNEXPECTED(selfOutType.isUndef())) return false;
			varTags = call0(pt_pdr_get_var_tags_site, doc, PT_LC("getvartags"));
			if (UNEXPECTED(varTags.isUndef())) return false;
			{
				zv::Val value = call0(pt_pdr_get_params_pure_unless_callable_is_impure_site, doc, PT_LC("getparamspureunlesscallableisimpure"));
				if (UNEXPECTED(value.isUndef())) return false;
				phpDocPureUnlessCallableIsImpureParameters = zv::Arr::adoptVal(std::move(value));
			}
		} else {
			asserts = createEmptyAssertions();
			if (UNEXPECTED(asserts.isUndef())) return false;
		}

		if (zend_is_true(acceptsNamedArguments.raw())) {
			bool stillInClass;
			if (UNEXPECTED(!pt_scope_is_in_class(Z_OBJ_P(scope), stillInClass))) return false;
			if (stillInClass) {
				bool accepts;
				if (UNEXPECTED(!classReflectionOf(scope, classReflection, "acceptsNamedArguments"))) return false;
				if (UNEXPECTED(!pt_class_reflection_accepts_named_arguments(Z_OBJ_P(classReflection.raw()), accepts))) return false;
				acceptsNamedArguments = zv::Val::boolean(accepts);
			}
		}

		if (isPure.isNull() && isFunctionLike) {
			bool stillInClass;
			if (UNEXPECTED(!pt_scope_is_in_class(Z_OBJ_P(scope), stillInClass))) return false;
			if (stillInClass && UNEXPECTED(!resolveClassPurity(scope, node, classReflection, functionName.raw(), phpDocReturnType.raw(), isPure))) return false;
		}

		moveInto(items[PT_PHP_DOCS_TEMPLATE_TYPE_MAP], templateTypeMap);
		moveInto(items[PT_PHP_DOCS_PARAMETER_TYPES], phpDocParameterTypes);
		moveInto(items[PT_PHP_DOCS_IMMEDIATELY_INVOKED_CALLABLE_PARAMETERS], phpDocImmediatelyInvokedCallableParameters);
		moveInto(items[PT_PHP_DOCS_CLOSURE_THIS_TYPE_PARAMETERS], phpDocClosureThisTypeParameters);
		moveInto(items[PT_PHP_DOCS_RETURN_TYPE], phpDocReturnType);
		moveInto(items[PT_PHP_DOCS_THROW_TYPE], phpDocThrowType);
		moveInto(items[PT_PHP_DOCS_DEPRECATED_DESCRIPTION], deprecatedDescription);
		moveInto(items[PT_PHP_DOCS_IS_DEPRECATED], isDeprecated);
		moveInto(items[PT_PHP_DOCS_IS_INTERNAL], isInternal);
		moveInto(items[PT_PHP_DOCS_IS_FINAL], isFinal);
		moveInto(items[PT_PHP_DOCS_IS_PURE], isPure);
		moveInto(items[PT_PHP_DOCS_ACCEPTS_NAMED_ARGUMENTS], acceptsNamedArguments);
		ZVAL_BOOL(&items[PT_PHP_DOCS_IS_READ_ONLY], isReadOnly);
		moveInto(items[PT_PHP_DOCS_DOC_COMMENT], docComment);
		moveInto(items[PT_PHP_DOCS_ASSERTS], asserts);
		moveInto(items[PT_PHP_DOCS_SELF_OUT_TYPE], selfOutType);
		moveInto(items[PT_PHP_DOCS_PARAMETER_OUT_TYPES], phpDocParameterOutTypes);
		moveInto(items[PT_PHP_DOCS_VAR_TAGS], varTags);
		moveInto(items[PT_PHP_DOCS_IS_ALLOWED_PRIVATE_MUTATION], isAllowedPrivateMutation);
		moveInto(items[PT_PHP_DOCS_RESOLVED_PHP_DOC], resolvedPhpDoc);
		moveInto(items[PT_PHP_DOCS_PURE_UNLESS_CALLABLE_IS_IMPURE_PARAMETERS], phpDocPureUnlessCallableIsImpureParameters);
		return true;
	}

private:
	zend_object *self;

	static void moveInto(zval &item, zv::Val &value)
	{
		zval_ptr_dtor(&item);
		item = value.take();
	}

	/* $scope->getClassReflection() into `classReflection` unless already
	 * read, checked to be an object for the method the twin calls on it */
	[[nodiscard]] static bool classReflectionOf(zval *scope, zv::Val &classReflection, const char *method)
	{
		if (classReflection.isUndef()) {
			classReflection = pt_scope_get_class_reflection(Z_OBJ_P(scope));
			if (UNEXPECTED(classReflection.isUndef())) return false;
		}
		if (UNEXPECTED(Z_TYPE_P(classReflection.raw()) != IS_OBJECT)) {
			memberCallOnNonObject(method, classReflection.raw());
			return false;
		}
		return true;
	}

	/* the ClassMethod branch: $functionName, the constructor's promoted
	 * parameter @var types and the inherited $resolvedPhpDoc */
	[[nodiscard]] bool resolveClassMethodPhpDoc(zval *scope, zval *node, bool inClass, zv::Val &classReflection, zval *file, zval *className, zval *traitName, zval *docComment, zv::Arr &phpDocParameterTypes, zv::Val &functionName, zv::Val &resolvedPhpDoc) const
	{
		if (!inClass) {
			pt_throw_should_not_happen();
			return false;
		}
		zval *name = nodeName(node);
		if (UNEXPECTED(name == NULL)) return false;
		{
			zval *identifier = identifierName(name, "name");
			if (UNEXPECTED(identifier == NULL)) return false;
			functionName = zv::Val::copyOf(zv::Ref(identifier));
		}

		/* array_map(static function (Node\Param $param): string { ... }, $node->getParams()) */
		zv::Val params = nodeParams(node);
		if (UNEXPECTED(params.isUndef())) return false;
		zv::Val positionalParameterNames = positionalNames(params.raw());
		if (UNEXPECTED(positionalParameterNames.isUndef())) return false;

		zv::Val currentResolvedPhpDoc = zv::Val::null();
		if (Z_TYPE_P(docComment) != IS_NULL) {
			name = nodeName(node);
			if (UNEXPECTED(name == NULL)) return false;
			zval *identifier = identifierName(name, "name");
			if (UNEXPECTED(identifier == NULL)) return false;
			zv::Val methodName = zv::Val::copyOf(zv::Ref(identifier));
			currentResolvedPhpDoc = getResolvedPhpDoc(OBJ_PROP_NUM(self, slots::fileTypeMapper), file, className, traitName, methodName.raw(), docComment);
			if (UNEXPECTED(currentResolvedPhpDoc.isUndef())) return false;
		}
		zv::Val methodNameForInheritance = pt_engine_node_get_attribute(Z_OBJ_P(node), PT_LC("originalTraitMethodName"));
		if (UNEXPECTED(methodNameForInheritance.isUndef())) return false;
		if (methodNameForInheritance.isNull()) {
			name = nodeName(node);
			if (UNEXPECTED(name == NULL)) return false;
			zval *identifier = identifierName(name, "name");
			if (UNEXPECTED(identifier == NULL)) return false;
			methodNameForInheritance = zv::Val::copyOf(zv::Ref(identifier));
		}
		if (UNEXPECTED(!classReflectionOf(scope, classReflection, "getName"))) return false;
		resolvedPhpDoc = resolvePhpDocForMethod(OBJ_PROP_NUM(self, slots::phpDocInheritanceResolver), classReflection.raw(), methodNameForInheritance.raw(), currentResolvedPhpDoc.raw(), positionalParameterNames.raw());
		if (UNEXPECTED(resolvedPhpDoc.isUndef())) return false;

		name = nodeName(node);
		if (UNEXPECTED(name == NULL)) return false;
		if (UNEXPECTED(Z_TYPE_P(name) != IS_OBJECT)) {
			memberCallOnNonObject("toLowerString", name);
			return false;
		}
		zval *identifier = identifierName(name, "name");
		if (UNEXPECTED(identifier == NULL)) return false;
		if (Z_TYPE_P(identifier) != IS_STRING || !zend_string_equals_literal_ci(Z_STR_P(identifier), "__construct")) return true;

		zval *nodeParamsValue = ptsh::readNodeProperty(pt_pdr_params_site, node, PT_LC("params"));
		if (UNEXPECTED(nodeParamsValue == NULL)) return false;
		if (UNEXPECTED(Z_TYPE_P(nodeParamsValue) != IS_ARRAY)) {
			zend_error(E_WARNING, "foreach() argument must be of type array|object, %s given", zend_zval_value_name(nodeParamsValue));
			return !EG(exception);
		}
		zv::Arr iterated = zv::Arr::copyOfTable(Z_ARRVAL_P(nodeParamsValue));
		for (auto entry : zv::TableRef(iterated.table())) {
			if (UNEXPECTED(!constructorParamVarType(entry.value().deref().raw(), file, className, traitName, phpDocParameterTypes))) return false;
		}
		return true;
	}

	/* the positional parameter names: each param's $var->name, the twin's
	 * ShouldNotHappenException for anything else */
	static zv::Val positionalNames(zval *params)
	{
		if (UNEXPECTED(Z_TYPE_P(params) != IS_ARRAY)) {
			zend_type_error("array_map(): Argument #2 ($array) must be of type array, %s given", zend_zval_value_name(params));
			return zv::Val();
		}
		zend_class_entry *paramCe = pt_class(PT_CLASS_PARAM);
		if (UNEXPECTED(paramCe == NULL)) return zv::Val();
		zend_class_entry *variableCe = pt_class(PT_CLASS_VARIABLE);
		if (UNEXPECTED(variableCe == NULL)) return zv::Val();
		HashTable *source = Z_ARRVAL_P(params);
		if (zend_hash_num_elements(source) == 0) return zv::Val(zv::Arr::empty());
		/* array_map() over one array keeps its keys */
		zv::Arr names = zv::Arr::create(zend_hash_num_elements(source));
		for (auto entry : zv::TableRef(source)) {
			zval *param = entry.value().deref().raw();
			if (UNEXPECTED(Z_TYPE_P(param) != IS_OBJECT || !instanceof_function(Z_OBJCE_P(param), paramCe))) {
				zend_type_error("PHPStan\\Analyser\\PhpDocsResolver::{closure}(): Argument #1 ($param) must be of type PhpParser\\Node\\Param, %s given", zend_zval_value_name(param));
				return zv::Val();
			}
			zval *var = ptsh::readNodeProperty(pt_pdr_param_var_site, param, PT_LC("var"));
			if (UNEXPECTED(var == NULL)) return zv::Val();
			if (Z_TYPE_P(var) != IS_OBJECT || !instanceof_function(Z_OBJCE_P(var), variableCe)) {
				pt_throw_should_not_happen();
				return zv::Val();
			}
			zval *name = ptsh::readNodeProperty(pt_pdr_variable_name_site, var, PT_LC("name"));
			if (UNEXPECTED(name == NULL)) return zv::Val();
			if (Z_TYPE_P(name) != IS_STRING) {
				pt_throw_should_not_happen();
				return zv::Val();
			}
			names.separate();
			Z_ADDREF_P(name);
			zend_string *key = entry.stringKeyOrNull();
			if (key != NULL) {
				zend_hash_update(names.table(), key, name);
			} else {
				zend_hash_index_update(names.table(), entry.indexKey(), name);
			}
		}
		return zv::Val(std::move(names));
	}

	/* the constructor loop's body: a promoted parameter's @var type */
	[[nodiscard]] bool constructorParamVarType(zval *param, zval *file, zval *className, zval *traitName, zv::Arr &phpDocParameterTypes) const
	{
		if (UNEXPECTED(Z_TYPE_P(param) != IS_OBJECT)) {
			zend_error(E_WARNING, "Attempt to read property \"flags\" on %s", zend_zval_value_name(param));
			if (UNEXPECTED(EG(exception))) return false;
			/* null === 0 is false: the loop goes on to getDocComment() */
			memberCallOnNonObject("getDocComment", param);
			return false;
		}
		zval *flags = ptsh::readNodeProperty(pt_pdr_param_flags_site, param, PT_LC("flags"));
		if (UNEXPECTED(flags == NULL)) return false;
		if (Z_TYPE_P(flags) == IS_LONG && Z_LVAL_P(flags) == 0) return true;

		zv::Val paramDocComment = pt_node_doc_comment_text(param);
		if (UNEXPECTED(paramDocComment.isUndef())) return false;
		if (paramDocComment.isNull()) return true;

		zend_class_entry *variableCe = pt_class(PT_CLASS_VARIABLE);
		if (UNEXPECTED(variableCe == NULL)) return false;
		zval *var = ptsh::readNodeProperty(pt_pdr_param_var_site, param, PT_LC("var"));
		if (UNEXPECTED(var == NULL)) return false;
		if (Z_TYPE_P(var) != IS_OBJECT || !instanceof_function(Z_OBJCE_P(var), variableCe)) {
			pt_throw_should_not_happen();
			return false;
		}
		zval *varName = ptsh::readNodeProperty(pt_pdr_variable_name_site, var, PT_LC("name"));
		if (UNEXPECTED(varName == NULL)) return false;
		if (Z_TYPE_P(varName) != IS_STRING) {
			pt_throw_should_not_happen();
			return false;
		}
		zv::Val parameterName = zv::Val::copyOf(zv::Ref(varName));

		zval functionName;
		ZVAL_INTERNED_STR(&functionName, pt_pdr_construct);
		zv::Val paramPhpDoc = getResolvedPhpDoc(OBJ_PROP_NUM(self, slots::fileTypeMapper), file, className, traitName, &functionName, paramDocComment.raw());
		if (UNEXPECTED(paramPhpDoc.isUndef())) return false;
		if (UNEXPECTED(Z_TYPE_P(paramPhpDoc.raw()) != IS_OBJECT)) {
			memberCallOnNonObject("getVarTags", paramPhpDoc.raw());
			return false;
		}
		zv::Val varTags = call0(pt_pdr_get_var_tags_site, paramPhpDoc.raw(), PT_LC("getvartags"));
		if (UNEXPECTED(varTags.isUndef())) return false;

		zval *varTag = NULL;
		if (EXPECTED(Z_TYPE_P(varTags.raw()) == IS_ARRAY)) {
			HashTable *tags = Z_ARRVAL_P(varTags.raw());
			zval *first = zend_hash_index_find(tags, 0);
			if (first != NULL) ZVAL_DEREF(first);
			if (first != NULL && Z_TYPE_P(first) != IS_NULL && zend_hash_num_elements(tags) == 1) {
				varTag = first;
			} else {
				zval *named = zend_symtable_find(tags, Z_STR_P(parameterName.raw()));
				if (named != NULL) ZVAL_DEREF(named);
				if (named != NULL && Z_TYPE_P(named) != IS_NULL) varTag = named;
			}
		}
		if (varTag == NULL) return true;
		if (UNEXPECTED(Z_TYPE_P(varTag) != IS_OBJECT)) {
			memberCallOnNonObject("getType", varTag);
			return false;
		}
		zv::Val phpDocType = call0(pt_pdr_var_tag_get_type_site, varTag, PT_LC("gettype"));
		if (UNEXPECTED(phpDocType.isUndef())) return false;
		phpDocParameterTypes.set(Z_STR_P(parameterName.raw()), std::move(phpDocType));
		return true;
	}

	/* foreach ($tags as $paramName => $tag) — $types[$paramName] =
	 * $tag->getType(); for the @param / @param-closure-this tags (`paramTypes`)
	 * an existing key is kept and a static type transformed in a class */
	[[nodiscard]] bool collectTagTypes(zv::Val tags, pt_method_site &getTypeSite, bool paramTypes, zval *scope, zv::Val &classReflection, zv::Arr &types) const
	{
		if (UNEXPECTED(tags.isUndef())) return false;
		if (UNEXPECTED(Z_TYPE_P(tags.raw()) != IS_ARRAY)) {
			zend_error(E_WARNING, "foreach() argument must be of type array|object, %s given", zend_zval_value_name(tags.raw()));
			return !EG(exception);
		}
		for (auto entry : zv::ArrRef(tags.raw())) {
			zend_string *key = entry.stringKeyOrNull();
			zend_ulong index = entry.indexKey();
			if (paramTypes) {
				/* array_key_exists($paramName, $types) */
				bool exists = key != NULL ? zend_hash_exists(types.table(), key) : zend_hash_index_exists(types.table(), index);
				if (exists) continue;
			}
			zval *tag = entry.value().deref().raw();
			if (UNEXPECTED(Z_TYPE_P(tag) != IS_OBJECT)) {
				memberCallOnNonObject("getType", tag);
				return false;
			}
			zv::Val type = call0(getTypeSite, tag, PT_LC("gettype"));
			if (UNEXPECTED(type.isUndef())) return false;
			if (paramTypes) {
				bool inClass;
				if (UNEXPECTED(!pt_scope_is_in_class(Z_OBJ_P(scope), inClass))) return false;
				if (inClass) {
					type = transformStaticType(scope, classReflection, type.raw());
					if (UNEXPECTED(type.isUndef())) return false;
				}
			}
			types.separate();
			zval stored = type.take();
			if (key != NULL) {
				zend_hash_update(types.table(), key, &stored);
			} else {
				zend_hash_index_update(types.table(), index, &stored);
			}
		}
		return true;
	}

	/* $doc->getXTag() !== null ? $doc->getXTag()->getY() : null (the getter
	 * memoizes, so it is asked once) */
	static zv::Val tagType(pt_method_site &tagSite, const char *tagLcname, size_t tagLen, zval *doc, pt_method_site &valueSite, const char *valueLcname, size_t valueLen)
	{
		zv::Val tag = pt_call_method_cached(tagSite, Z_OBJ_P(doc), tagLcname, tagLen, 0, NULL);
		if (UNEXPECTED(tag.isUndef()) || tag.isNull()) return tag;
		if (UNEXPECTED(Z_TYPE_P(tag.raw()) != IS_OBJECT)) {
			memberCallOnNonObject(valueLcname, tag.raw());
			return zv::Val();
		}
		return pt_call_method_cached(valueSite, Z_OBJ_P(tag.raw()), valueLcname, valueLen, 0, NULL);
	}

	/* Mirrors getPhpDocReturnType(). */
	static zv::Val getPhpDocReturnType(zval *resolvedPhpDoc, zval *nativeReturnType)
	{
		zv::Val returnTag = call0(pt_pdr_get_return_tag_site, resolvedPhpDoc, PT_LC("getreturntag"));
		if (UNEXPECTED(returnTag.isUndef()) || returnTag.isNull()) return returnTag;
		if (UNEXPECTED(Z_TYPE_P(returnTag.raw()) != IS_OBJECT)) {
			memberCallOnNonObject("getType", returnTag.raw());
			return zv::Val();
		}
		zv::Val phpDocReturnType = call0(pt_pdr_return_tag_get_type_site, returnTag.raw(), PT_LC("gettype"));
		if (UNEXPECTED(phpDocReturnType.isUndef())) return zv::Val();
		zv::Val isExplicit = call0(pt_pdr_return_tag_is_explicit_site, returnTag.raw(), PT_LC("isexplicit"));
		if (UNEXPECTED(isExplicit.isUndef())) return zv::Val();
		if (zend_is_true(isExplicit.raw())) return phpDocReturnType;

		if (UNEXPECTED(Z_TYPE_P(nativeReturnType) != IS_OBJECT)) {
			memberCallOnNonObject("isSuperTypeOf", nativeReturnType);
			return zv::Val();
		}
		{
			zv::Val bounds = pt_type_template_type_helper_resolve_to_bounds(phpDocReturnType.raw());
			if (UNEXPECTED(bounds.isUndef())) return zv::Val();
			zv::Val result = pt_type_op(Z_OBJ_P(nativeReturnType), PT_OP_IS_SUPER_TYPE_OF, 1, bounds.raw());
			if (UNEXPECTED(result.isUndef())) return zv::Val();
			zend_long trinary = pt_type_result_trinary(result.raw());
			if (UNEXPECTED(trinary < 0)) return zv::Val();
			if (trinary == PT_TRI_YES) return phpDocReturnType;
		}

		if (Z_TYPE_P(phpDocReturnType.raw()) == IS_OBJECT && instanceof_function(Z_OBJCE_P(phpDocReturnType.raw()), pt_ce_union_type)) {
			zv::Val innerTypes = pt_union_type_get_types(Z_OBJ_P(phpDocReturnType.raw()));
			if (UNEXPECTED(innerTypes.isUndef())) return zv::Val();
			if (UNEXPECTED(Z_TYPE_P(innerTypes.raw()) != IS_ARRAY)) {
				zend_error(E_WARNING, "foreach() argument must be of type array|object, %s given", zend_zval_value_name(innerTypes.raw()));
				if (UNEXPECTED(EG(exception))) return zv::Val();
				return zv::Val::null();
			}
			zv::Arr types = zv::Arr::empty();
			for (auto entry : zv::ArrRef(innerTypes.raw())) {
				zval *innerType = entry.value().deref().raw();
				zv::Val result = pt_type_op(Z_OBJ_P(nativeReturnType), PT_OP_IS_SUPER_TYPE_OF, 1, innerType);
				if (UNEXPECTED(result.isUndef())) return zv::Val();
				zend_long trinary = pt_type_result_trinary(result.raw());
				if (UNEXPECTED(trinary < 0)) return zv::Val();
				if (trinary != PT_TRI_YES) continue;
				types.push(zv::Ref(innerType));
			}
			if (zend_hash_num_elements(types.table()) == 0) return zv::Val::null();
			return pt_type_combinator_call_spread(PT_LC("union"), types.table());
		}

		return zv::Val::null();
	}

	/* Mirrors transformStaticType($scope->getClassReflection(), $type). */
	static zv::Val transformStaticType(zval *scope, zv::Val &classReflection, zval *type)
	{
		if (UNEXPECTED(!classReflectionOf(scope, classReflection, "isFinal"))) return zv::Val();
		zv::Val callback = pt_native_closure(&transformStaticTypeBody, classReflection.raw());
		return pt_type_traverser_map_of(type, callback.raw());
	}

	/* static function (Type $type, callable $traverse) use ($declaringClass): Type */
	static void transformStaticTypeBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		if (UNEXPECTED(argc < 2)) {
			zend_throw_error(zend_ce_argument_count_error, "Too few arguments to function PHPStan\\Analyser\\PhpDocsResolver::{closure}(), %u passed and exactly 2 expected", argc);
			return;
		}
		zval *type = &argv[0];
		zval *traverse = &argv[1];
		zval *declaringClass = &captures[0];
		if (Z_TYPE_P(type) == IS_OBJECT && instanceof_function(Z_OBJCE_P(type), pt_ce_static_type)) {
			zv::Val changedType = pt_type_op(Z_OBJ_P(type), PT_OP_CHANGE_BASE_CLASS, 1, declaringClass);
			if (UNEXPECTED(changedType.isUndef())) return;
			bool isFinal;
			if (UNEXPECTED(!pt_class_reflection_is_final(Z_OBJ_P(declaringClass), isFinal))) return;
			if (isFinal && !instanceof_function(Z_OBJCE_P(type), pt_ce_this_type)) {
				if (UNEXPECTED(Z_TYPE_P(changedType.raw()) != IS_OBJECT)) {
					memberCallOnNonObject("getStaticObjectType", changedType.raw());
					return;
				}
				changedType = pt_type_call(Z_OBJ_P(changedType.raw()), PT_LC("getstaticobjecttype"), 0, NULL);
				if (UNEXPECTED(changedType.isUndef())) return;
			}
			zv::Val traversed = pt_type_call_callable(traverse, 1, changedType.raw());
			if (UNEXPECTED(traversed.isUndef())) return;
			traversed.intoReturnValue(return_value);
			return;
		}
		zv::Val traversed = pt_type_call_callable(traverse, 1, type);
		if (UNEXPECTED(traversed.isUndef())) return;
		traversed.intoReturnValue(return_value);
	}

	/* the class-level purity of a method without its own @phpstan-pure /
	 * @phpstan-impure */
	[[nodiscard]] static bool resolveClassPurity(zval *scope, zval *node, zv::Val &classReflection, zval *functionName, zval *phpDocReturnType, zv::Val &isPure)
	{
		// a set hook has no return type node of its own, but it always returns
		// void - the class-level @phpstan-pure must not make it pure
		bool error = false;
		bool isSetHook = ptsh::isInstanceOf(node, PT_CLASS_PROPERTY_HOOK, error);
		if (UNEXPECTED(error)) return false;
		if (isSetHook) {
			zval *hookName = nodeName(node);
			if (UNEXPECTED(hookName == NULL)) return false;
			zval *hookNameString = identifierName(hookName, "name");
			if (UNEXPECTED(hookNameString == NULL)) return false;
			/* $node->name->toLowerString() === 'set' */
			isSetHook = Z_TYPE_P(hookNameString) == IS_STRING && zend_string_equals_literal_ci(Z_STR_P(hookNameString), "set");
		}

		if (UNEXPECTED(!classReflectionOf(scope, classReflection, "getResolvedPhpDoc"))) return false;
		zv::Val classResolvedPhpDoc = pt_class_reflection_get_resolved_php_doc(Z_OBJ_P(classReflection.raw()));
		if (UNEXPECTED(classResolvedPhpDoc.isUndef())) return false;
		if (classResolvedPhpDoc.isNull()) return true;
		if (UNEXPECTED(Z_TYPE_P(classResolvedPhpDoc.raw()) != IS_OBJECT)) {
			memberCallOnNonObject("areAllMethodsPure", classResolvedPhpDoc.raw());
			return false;
		}
		zv::Val allPure = call0(pt_pdr_are_all_methods_pure_site, classResolvedPhpDoc.raw(), PT_LC("areallmethodspure"));
		if (UNEXPECTED(allPure.isUndef())) return false;
		if (zend_is_true(allPure.raw())) {
			/* strtolower($functionName ?? '') === '__construct' */
			bool isConstructor = false;
			if (Z_TYPE_P(functionName) != IS_NULL) {
				zend_string *name = zval_try_get_string(functionName);
				if (UNEXPECTED(name == NULL)) return false;
				isConstructor = zend_string_equals_literal_ci(name, "__construct");
				zend_string_release(name);
			}
			if (isConstructor) {
				isPure = zv::Val::boolean(true);
				return true;
			}
			if (isSetHook) return true;
			bool phpDocIsVoid = false;
			if (Z_TYPE_P(phpDocReturnType) != IS_NULL) {
				zend_long isVoid = pt_type_op_trinary(Z_OBJ_P(phpDocReturnType), PT_OP_IS_VOID, 0, NULL);
				if (UNEXPECTED(isVoid < 0)) return false;
				phpDocIsVoid = isVoid == PT_TRI_YES;
			}
			if (phpDocIsVoid) return true;
			zv::Val returnTypeNode = nodeReturnType(node);
			if (UNEXPECTED(returnTypeNode.isUndef())) return false;
			zv::Val nativeReturnType = pt_mutating_scope_get_function_type(Z_OBJ_P(scope), returnTypeNode.raw(), false, false);
			if (UNEXPECTED(nativeReturnType.isUndef())) return false;
			if (UNEXPECTED(Z_TYPE_P(nativeReturnType.raw()) != IS_OBJECT)) {
				memberCallOnNonObject("isVoid", nativeReturnType.raw());
				return false;
			}
			zend_long isVoid = pt_type_op_trinary(Z_OBJ_P(nativeReturnType.raw()), PT_OP_IS_VOID, 0, NULL);
			if (UNEXPECTED(isVoid < 0)) return false;
			if (isVoid != PT_TRI_YES) isPure = zv::Val::boolean(true);
			return true;
		}
		zv::Val allImpure = call0(pt_pdr_are_all_methods_impure_site, classResolvedPhpDoc.raw(), PT_LC("areallmethodsimpure"));
		if (UNEXPECTED(allImpure.isUndef())) return false;
		if (zend_is_true(allImpure.raw())) isPure = zv::Val::boolean(false);
		return true;
	}
};

} // namespace phpstanturbo

using phpstanturbo::PhpDocsResolver;

bool pt_php_docs_resolver_get_php_docs(zval *resolver, zval *scope, zval *node, uint32_t destructured, pt_php_docs &out)
{
	if (EXPECTED(Z_OBJCE_P(resolver) == pt_ce_php_docs_resolver)) return PhpDocsResolver(Z_OBJ_P(resolver)).getPhpDocs(scope, node, out);
	zv::Args argv{scope, node};
	zv::Val phpDocs = pt_type_call(Z_OBJ_P(resolver), PT_LC("getphpdocs"), 2, argv);
	if (UNEXPECTED(phpDocs.isUndef())) return false;
	for (uint32_t index = 0; index < PT_PHP_DOCS_COUNT; index++) {
		zval *item = NULL;
		if (EXPECTED(Z_TYPE_P(phpDocs.raw()) == IS_ARRAY)) {
			item = zend_hash_index_find(Z_ARRVAL_P(phpDocs.raw()), index);
			if (item == NULL && (destructured & (1u << index)) != 0) {
				zend_error(E_WARNING, "Undefined array key %u", index);
				if (UNEXPECTED(EG(exception))) return false;
			}
		}
		if (item != NULL) {
			ZVAL_COPY_DEREF(&out.items[index], item);
		} else if ((destructured & (1u << index)) != 0) {
			ZVAL_NULL(&out.items[index]);
		}
	}
	return true;
}

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

void pt_register_php_docs_resolver()
{
	pt_pdr_construct = zend_string_init_interned(PT_LC("__construct"), 1);

	reg::Class cls("PHPStan\\Analyser\\PhpDocsResolver");
	ptdecl::PhpDocsResolver::declareClass(cls);
	ptdecl::PhpDocsResolver::declareProperties(cls);

	/* the real parameter class names: the DI container autowires the
	 * service by reflecting the constructor */
	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *fileTypeMapper, *phpDocInheritanceResolver;
		if (!zp::parse<zp::Obj, zp::Obj>(execute_data, fileTypeMapper, phpDocInheritanceResolver)) RETURN_THROWS();
		PhpDocsResolver(Z_OBJ_P(ZEND_THIS)).construct(fileTypeMapper, phpDocInheritanceResolver);
	});

	cls.method(sigs::getPhpDocs, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *scope, *node;
		if (!zp::parse<zp::Obj, zp::Obj>(execute_data, scope, node)) RETURN_THROWS();
		pt_php_docs docs;
		if (UNEXPECTED(!PhpDocsResolver(Z_OBJ_P(ZEND_THIS)).getPhpDocs(scope, node, docs))) RETURN_THROWS();
		HashTable *list = zend_new_array(PT_PHP_DOCS_COUNT);
		zend_hash_real_init_packed(list);
		ZEND_HASH_FILL_PACKED(list) {
			for (zval &item : docs.items) {
				ZEND_HASH_FILL_SET(&item);
				ZVAL_UNDEF(&item);
				ZEND_HASH_FILL_NEXT();
			}
		} ZEND_HASH_FILL_END();
		RETURN_ARR(list);
	});

	cls.shadow(&pt_ce_php_docs_resolver);
}

/* }}} */
