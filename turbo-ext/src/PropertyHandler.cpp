/*
 * PHPStanTurbo\PropertyHandler — native implementation of
 * PHPStan\Analyser\StmtHandler\PropertyHandler.
 *
 * A DI service (#[AutowiredService]): the constructor keeps the twin's
 * arginfo so Nette autowires it. processStmt() is registered as the class's
 * statement-handler entry (Engine.h).
 *
 * AttributesHandler, PhpDocsResolver, PropertyHooksProcessor, MutatingScope,
 * ClassReflection, the contexts, the statement results and NodeScopeResolver
 * are called through their direct entries. The per-item clone of the
 * statement takes the item's attributes through NodeAbstract's slot while
 * the node classes inherit getAttributes() / setAttributes();
 * ParserNodeTypeToPHPStanType, VarTag and the php-parser identifier through
 * the sites below.
 */

#include "support.h"
#include "generated/PropertyHandler.h"

namespace slots = ptdecl::PropertyHandler::slot;
namespace sigs = ptdecl::PropertyHandler::sig;
#include "zv.h"
#include "TypeTraits.h"
#include "Engine.h"
#include "AnalyserValues.h"
#include "StmtHandlerCalls.h"

zend_class_entry *pt_ce_property_handler = nullptr;

namespace {

/* {{{ the PHP collaborators (one site each) */

pt_method_site pt_ph_parser_node_type_resolve_site;
pt_method_site pt_ph_var_tag_get_type_site;
pt_method_site pt_ph_get_attributes_fn_site;
pt_method_site pt_ph_get_attributes_call_site;
pt_method_site pt_ph_set_attributes_fn_site;
pt_method_site pt_ph_set_attributes_call_site;

/* ParserNodeTypeToPHPStanType::resolve($type, $classReflection) */
zv::Val resolveParserNodeType(zval *type, zval *classReflection)
{
	zv::Args argv{type, classReflection};
	return pt_call_static_cached(pt_ph_parser_node_type_resolve_site, PT_CLASS_PARSER_NODE_TYPE_TO_PHPSTAN_TYPE, PT_LC("resolve"), 2, argv);
}

/* }}} */

pt_property_site pt_ph_attr_groups_site;
pt_property_site pt_ph_type_site;
pt_property_site pt_ph_props_site;
pt_property_site pt_ph_prop_default_site;
pt_property_site pt_ph_prop_name_site;
pt_property_site pt_ph_flags_site;
pt_property_site pt_ph_hooks_site;
pt_property_site pt_ph_attributes_site;

/* the list() items of getPhpDocs(): [,,,,,,,,,,,,$isReadOnly, $docComment, ,,,$varTags, $isAllowedPrivateMutation] */
constexpr uint32_t PT_PH_DESTRUCTURED_PHP_DOCS = (1u << PT_PHP_DOCS_IS_READ_ONLY) | (1u << PT_PHP_DOCS_DOC_COMMENT) | (1u << PT_PHP_DOCS_VAR_TAGS) | (1u << PT_PHP_DOCS_IS_ALLOWED_PRIVATE_MUTATION);

zend_never_inline ZEND_COLD void memberCallOnNonObject(const char *method, zval *value)
{
	zend_throw_error(NULL, "Call to a member function %s() on %s", method, zend_zval_value_name(value));
}

/* whether the node's class inherits NodeAbstract's method */
bool inheritsNodeAbstractMethod(pt_method_site &site, zend_object *node, const char *lcname, size_t len)
{
	zend_class_entry *nodeAbstractCe = pt_class(PT_CLASS_NODE_ABSTRACT);
	if (UNEXPECTED(nodeAbstractCe == NULL)) {
		zend_clear_exception();
		return false;
	}
	if (EXPECTED(site.ce == node->ce && site.generation == pt_engine_generation && site.fn != NULL)) return site.fn->common.scope == nodeAbstractCe;
	zend_function *fn = (zend_function *) zend_hash_str_find_ptr(&node->ce->function_table, lcname, len);
	if (fn == NULL) return false;
	site = { node->ce, fn, pt_engine_generation };
	return fn->common.scope == nodeAbstractCe;
}

/* $node->getAttributes() */
zv::Val nodeGetAttributes(zval *node)
{
	if (EXPECTED(inheritsNodeAbstractMethod(pt_ph_get_attributes_fn_site, Z_OBJ_P(node), PT_LC("getattributes")))) {
		zval *attributes = pt_property_cached(pt_ph_attributes_site, Z_OBJ_P(node), PT_LC("attributes"));
		if (EXPECTED(attributes != NULL)) {
			ZVAL_DEREF(attributes);
			if (EXPECTED(Z_TYPE_P(attributes) == IS_ARRAY)) return zv::Val::copyOf(zv::Ref(attributes));
		}
	}
	return pt_call_method_cached(pt_ph_get_attributes_call_site, Z_OBJ_P(node), PT_LC("getattributes"), 0, NULL);
}

/* $node->setAttributes($attributes); false = pending exception */
[[nodiscard]] bool nodeSetAttributes(zval *node, zval *attributes)
{
	if (EXPECTED(Z_TYPE_P(attributes) == IS_ARRAY && inheritsNodeAbstractMethod(pt_ph_set_attributes_fn_site, Z_OBJ_P(node), PT_LC("setattributes")))) {
		zval *slot = pt_property_cached(pt_ph_attributes_site, Z_OBJ_P(node), PT_LC("attributes"));
		if (EXPECTED(slot != NULL && Z_TYPE_P(slot) != IS_REFERENCE)) {
			zv::Ref(slot).assign(zv::Val::copyOf(zv::Ref(attributes)));
			return true;
		}
	}
	return !pt_call_method_cached(pt_ph_set_attributes_call_site, Z_OBJ_P(node), PT_LC("setattributes"), 1, attributes).isUndef();
}

/* clone $node */
zv::Val cloneNode(zval *node)
{
	zend_object *object = Z_OBJ_P(node);
	if (UNEXPECTED(object->handlers->clone_obj == NULL)) {
		zend_throw_error(NULL, "Trying to clone an uncloneable object of class %s", ZSTR_VAL(object->ce->name));
		return zv::Val();
	}
	zend_object *clone = object->handlers->clone_obj(object);
	if (UNEXPECTED(EG(exception))) {
		if (clone != NULL) OBJ_RELEASE(clone);
		return zv::Val();
	}
	zval result;
	ZVAL_OBJ(&result, clone);
	return zv::Val::adopt(result);
}

/* $array[$key] of an isset() check: the element when set and not null (borrowed), NULL otherwise */
zval *issetItem(zval *array, zend_string *key)
{
	if (Z_TYPE_P(array) != IS_ARRAY) return NULL;
	zval *value = zend_symtable_find(Z_ARRVAL_P(array), key);
	if (value == NULL) return NULL;
	ZVAL_DEREF(value);
	return Z_TYPE_P(value) == IS_NULL ? NULL : value;
}

} // namespace

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\StmtHandler\PropertyHandler; UNDEF = pending
 * exception. */
class PropertyHandler
{
public:
	explicit PropertyHandler(zend_object *self) : self(self) {}

	/* the constructor body: the promoted properties */
	void construct(zval *phpDocsResolver, zval *propertyHooksProcessor, zval *attributesHandler)
	{
		zv::ObjRef object(self);
		object.propAtWrite(slots::phpDocsResolver, zv::Val::copyOf(zv::Ref(phpDocsResolver)));
		object.propAtWrite(slots::propertyHooksProcessor, zv::Val::copyOf(zv::Ref(propertyHooksProcessor)));
		object.propAtWrite(slots::attributesHandler, zv::Val::copyOf(zv::Ref(attributesHandler)));
	}

	/* Mirrors supports(); false = pending exception */
	[[nodiscard]] bool supports(zval *stmt, bool &out) const
	{
		bool error = false;
		out = ptsh::isInstanceOf(stmt, PT_CLASS_PROPERTY_STMT, error);
		return !error;
	}

	/* Mirrors processStmt(). */
	zv::Val processStmt(zval *nodeScopeResolver, zval *stmt, zval *scope, zval *storage, zval *nodeCallback, zval *context) const
	{
		zval *attrGroups = ptsh::readNodeProperty(pt_ph_attr_groups_site, stmt, PT_LC("attrGroups"));
		if (UNEXPECTED(attrGroups == NULL)) return zv::Val();
		if (UNEXPECTED(!ptsh::processAttributeGroups(OBJ_PROP_NUM(self, slots::attributesHandler), nodeScopeResolver, stmt, attrGroups, scope, storage, nodeCallback))) return zv::Val();

		zv::Val nativePropertyType = zv::Val::null();
		{
			zval *type = ptsh::readNodeProperty(pt_ph_type_site, stmt, PT_LC("type"));
			if (UNEXPECTED(type == NULL)) return zv::Val();
			if (Z_TYPE_P(type) != IS_NULL) {
				zv::Val typeHold = zv::Val::copyOf(zv::Ref(type));
				zv::Val classReflection = pt_scope_get_class_reflection(Z_OBJ_P(scope));
				if (UNEXPECTED(classReflection.isUndef())) return zv::Val();
				nativePropertyType = resolveParserNodeType(typeHold.raw(), classReflection.raw());
				if (UNEXPECTED(nativePropertyType.isUndef())) return zv::Val();
			}
		}

		pt_php_docs docs;
		if (UNEXPECTED(!ptsh::getPhpDocs(OBJ_PROP_NUM(self, slots::phpDocsResolver), scope, stmt, PT_PH_DESTRUCTURED_PHP_DOCS, docs))) return zv::Val();
		zval *isReadOnly = &docs.items[PT_PHP_DOCS_IS_READ_ONLY];
		zval *docComment = &docs.items[PT_PHP_DOCS_DOC_COMMENT];
		zval *varTags = &docs.items[PT_PHP_DOCS_VAR_TAGS];
		zval *isAllowedPrivateMutation = &docs.items[PT_PHP_DOCS_IS_ALLOWED_PRIVATE_MUTATION];

		zv::Val phpDocType = zv::Val::null();
		if (Z_TYPE_P(varTags) == IS_ARRAY) {
			zval *first = zend_hash_index_find(Z_ARRVAL_P(varTags), 0);
			if (first != NULL) ZVAL_DEREF(first);
			if (first != NULL && Z_TYPE_P(first) != IS_NULL && zend_hash_num_elements(Z_ARRVAL_P(varTags)) == 1) {
				phpDocType = varTagType(first);
				if (UNEXPECTED(phpDocType.isUndef())) return zv::Val();
			}
		}

		zval *props = ptsh::readNodeProperty(pt_ph_props_site, stmt, PT_LC("props"));
		if (UNEXPECTED(props == NULL)) return zv::Val();
		zv::Val propertyName;
		if (UNEXPECTED(Z_TYPE_P(props) != IS_ARRAY)) {
			zend_error(E_WARNING, "foreach() argument must be of type array|object, %s given", zend_zval_value_name(props));
			if (UNEXPECTED(EG(exception))) return zv::Val();
		} else {
			/* foreach iterates the array it started with */
			zv::Val iterated = zv::Val::copyOf(zv::Ref(props));
			for (auto entry : zv::ArrRef(iterated.raw())) {
				if (UNEXPECTED(!processProp(nodeScopeResolver, stmt, entry.value().deref().raw(), scope, storage, nodeCallback, context, nativePropertyType.raw(), isReadOnly, docComment, varTags, isAllowedPrivateMutation, phpDocType, propertyName))) return zv::Val();
			}
		}

		zval *hooks = ptsh::readNodeProperty(pt_ph_hooks_site, stmt, PT_LC("hooks"));
		if (UNEXPECTED(hooks == NULL)) return zv::Val();
		bool hasHooks;
		if (Z_TYPE_P(hooks) == IS_ARRAY) {
			hasHooks = zend_hash_num_elements(Z_ARRVAL_P(hooks)) > 0;
		} else {
			/* count() of a non-countable */
			zend_type_error("count(): Argument #1 ($value) must be of type Countable|array, %s given", zend_zval_value_name(hooks));
			return zv::Val();
		}
		if (hasHooks) {
			if (propertyName.isUndef() || propertyName.isNull()) {
				zend_class_entry *ce = pt_class(PT_CLASS_SHOULD_NOT_HAPPEN);
				if (EXPECTED(ce != NULL)) zend_throw_exception(ce, "Property name should be known when analysing hooks.", 0);
				return zv::Val();
			}
			zv::Val hooksHold = zv::Val::copyOf(zv::Ref(hooks));
			zval *type = ptsh::readNodeProperty(pt_ph_type_site, stmt, PT_LC("type"));
			if (UNEXPECTED(type == NULL)) return zv::Val();
			zv::Val typeHold = zv::Val::copyOf(zv::Ref(type));
			if (UNEXPECTED(!pt_property_hooks_processor_process_property_hooks(OBJ_PROP_NUM(self, slots::propertyHooksProcessor), nodeScopeResolver, stmt, typeHold.raw(), phpDocType.raw(), propertyName.raw(), hooksHold.raw(), scope, storage, nodeCallback))) return zv::Val();
		}

		zval *type = ptsh::readNodeProperty(pt_ph_type_site, stmt, PT_LC("type"));
		if (UNEXPECTED(type == NULL)) return zv::Val();
		if (Z_TYPE_P(type) != IS_NULL) {
			zv::Val typeHold = zv::Val::copyOf(zv::Ref(type));
			if (UNEXPECTED(!pt_node_scope_resolver_call_node_callback(nodeScopeResolver, nodeCallback, typeHold.raw(), scope, storage))) return zv::Val();
		}

		zval emptyArray;
		ZVAL_EMPTY_ARRAY(&emptyArray);
		return pt_internal_statement_result_new(scope, false, false, &emptyArray, &emptyArray, &emptyArray);
	}

	/* the statement-handler entry (Engine.h) */
	static zv::Val processStmtEntry(zend_object *handler, zval *nodeScopeResolver, zval *stmt, zval *scope, zval *storage, zval *nodeCallback, zval *context)
	{
		return PropertyHandler(handler).processStmt(nodeScopeResolver, stmt, scope, storage, nodeCallback, context);
	}

private:
	zend_object *self;

	/* $varTag->getType() */
	static zv::Val varTagType(zval *varTag)
	{
		if (UNEXPECTED(Z_TYPE_P(varTag) != IS_OBJECT)) {
			memberCallOnNonObject("getType", varTag);
			return zv::Val();
		}
		return pt_call_method_cached(pt_ph_var_tag_get_type_site, Z_OBJ_P(varTag), PT_LC("gettype"), 0, NULL);
	}

	/* the loop body over one property item; `propertyName` is the twin's
	 * $propertyName, `phpDocType` its $phpDocType; false = pending exception */
	[[nodiscard]] static bool processProp(zval *nodeScopeResolver, zval *stmt, zval *prop, zval *scope, zval *storage, zval *nodeCallback, zval *context, zval *nativePropertyType, zval *isReadOnly, zval *docComment, zval *varTags, zval *isAllowedPrivateMutation, zv::Val &phpDocType, zv::Val &propertyName)
	{
		if (UNEXPECTED(Z_TYPE_P(prop) != IS_OBJECT)) {
			zend_type_error("PHPStan\\Analyser\\NodeScopeResolver::callNodeCallback(): Argument #2 ($node) must be of type PhpParser\\Node, %s given", zend_zval_value_name(prop));
			return false;
		}
		if (UNEXPECTED(!pt_node_scope_resolver_call_node_callback(nodeScopeResolver, nodeCallback, prop, scope, storage))) return false;
		{
			zval *defaultValue = ptsh::readNodeProperty(pt_ph_prop_default_site, prop, PT_LC("default"));
			if (UNEXPECTED(defaultValue == NULL)) return false;
			if (Z_TYPE_P(defaultValue) != IS_NULL) {
				zv::Val defaultHold = zv::Val::copyOf(zv::Ref(defaultValue));
				bool resolveTemplateArguments;
				if (UNEXPECTED(!pt_statement_context_should_resolve_template_arguments(context, resolveTemplateArguments))) return false;
				zv::Val expressionContext = pt_expression_context_create_deep(resolveTemplateArguments);
				if (UNEXPECTED(expressionContext.isUndef())) return false;
				zv::Val result = pt_node_scope_resolver_process_expr_node(nodeScopeResolver, stmt, defaultHold.raw(), scope, storage, nodeCallback, expressionContext.raw());
				if (UNEXPECTED(result.isUndef())) return false;
			}
		}

		bool inClass;
		if (UNEXPECTED(!pt_scope_is_in_class(Z_OBJ_P(scope), inClass))) return false;
		if (!inClass) {
			pt_throw_should_not_happen();
			return false;
		}
		{
			zval *name = ptsh::readNodeProperty(pt_ph_prop_name_site, prop, PT_LC("name"));
			if (UNEXPECTED(name == NULL)) return false;
			if (UNEXPECTED(Z_TYPE_P(name) != IS_OBJECT)) {
				memberCallOnNonObject("toString", name);
				return false;
			}
			propertyName = pt_name_node_to_string(name);
			if (UNEXPECTED(propertyName.isUndef())) return false;
		}

		if (phpDocType.isNull() && Z_TYPE_P(propertyName.raw()) == IS_STRING) {
			zval *varTag = issetItem(varTags, Z_STR_P(propertyName.raw()));
			if (varTag != NULL) {
				phpDocType = varTagType(varTag);
				if (UNEXPECTED(phpDocType.isUndef())) return false;
			}
		}

		zv::Val propStmt = cloneNode(stmt);
		if (UNEXPECTED(propStmt.isUndef())) return false;
		{
			zv::Val attributes = nodeGetAttributes(prop);
			if (UNEXPECTED(attributes.isUndef())) return false;
			if (UNEXPECTED(!nodeSetAttributes(propStmt.raw(), attributes.raw()))) return false;
		}
		if (UNEXPECTED(!pt_engine_node_set_attribute(Z_OBJ_P(propStmt.raw()), PT_LC("originalPropertyStmt"), stmt))) return false;

		zval *flags = ptsh::readNodeProperty(pt_ph_flags_site, stmt, PT_LC("flags"));
		if (UNEXPECTED(flags == NULL)) return false;
		zv::Val flagsHold = zv::Val::copyOf(zv::Ref(flags));
		zval *defaultValue = ptsh::readNodeProperty(pt_ph_prop_default_site, prop, PT_LC("default"));
		if (UNEXPECTED(defaultValue == NULL)) return false;
		zv::Val defaultHold = zv::Val::copyOf(zv::Ref(defaultValue));
		bool isDeclaredInTrait;
		if (UNEXPECTED(!pt_mutating_scope_is_in_trait(Z_OBJ_P(scope), isDeclaredInTrait))) return false;
		zv::Val classReflection = pt_scope_get_class_reflection(Z_OBJ_P(scope));
		if (UNEXPECTED(classReflection.isUndef())) return false;
		if (UNEXPECTED(Z_TYPE_P(classReflection.raw()) != IS_OBJECT)) {
			memberCallOnNonObject("isReadOnly", classReflection.raw());
			return false;
		}
		bool isReadonlyClass;
		if (UNEXPECTED(!pt_class_reflection_is_read_only(Z_OBJ_P(classReflection.raw()), isReadonlyClass))) return false;

		zval nodeArgv[14];
		ZVAL_COPY_VALUE(&nodeArgv[0], propertyName.raw());
		ZVAL_COPY_VALUE(&nodeArgv[1], flagsHold.raw());
		ZVAL_COPY_VALUE(&nodeArgv[2], nativePropertyType);
		ZVAL_COPY_VALUE(&nodeArgv[3], defaultHold.raw());
		ZVAL_COPY_VALUE(&nodeArgv[4], docComment);
		ZVAL_COPY_VALUE(&nodeArgv[5], phpDocType.raw());
		ZVAL_FALSE(&nodeArgv[6]);
		ZVAL_FALSE(&nodeArgv[7]);
		ZVAL_COPY_VALUE(&nodeArgv[8], propStmt.raw());
		ZVAL_COPY_VALUE(&nodeArgv[9], isReadOnly);
		ZVAL_BOOL(&nodeArgv[10], isDeclaredInTrait);
		ZVAL_BOOL(&nodeArgv[11], isReadonlyClass);
		ZVAL_COPY_VALUE(&nodeArgv[12], isAllowedPrivateMutation);
		ZVAL_COPY_VALUE(&nodeArgv[13], classReflection.raw());
		zv::Val classPropertyNode = pt_type_new(PT_CLASS_CLASS_PROPERTY_NODE, 14, nodeArgv);
		if (UNEXPECTED(classPropertyNode.isUndef())) return false;
		return pt_node_scope_resolver_call_node_callback(nodeScopeResolver, nodeCallback, classPropertyNode.raw(), scope, storage);
	}
};

} // namespace phpstanturbo

using phpstanturbo::PropertyHandler;

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

void pt_register_property_handler()
{
	reg::Class cls("PHPStan\\Analyser\\StmtHandler\\PropertyHandler");
	ptdecl::PropertyHandler::declareClass(cls);
	ptdecl::PropertyHandler::declareProperties(cls);

	/* the real parameter class names: the DI container autowires the
	 * service by reflecting the constructor */
	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *phpDocsResolver, *propertyHooksProcessor, *attributesHandler;
		if (!zp::parse<zp::Obj, zp::Obj, zp::Obj>(execute_data, phpDocsResolver, propertyHooksProcessor, attributesHandler)) RETURN_THROWS();
		PropertyHandler(Z_OBJ_P(ZEND_THIS)).construct(phpDocsResolver, propertyHooksProcessor, attributesHandler);
	});

	cls.method<&PropertyHandler::supports, zp::Obj>(sigs::supports);

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
		PT_RETURN_VAL(PropertyHandler(Z_OBJ_P(ZEND_THIS)).processStmt(nodeScopeResolver, stmt, scope, storage, nodeCallback, context));
	});

	cls.shadow(&pt_ce_property_handler);
	pt_stmt_handler_entry_register(&pt_ce_property_handler, &PropertyHandler::processStmtEntry);
}

/* }}} */
