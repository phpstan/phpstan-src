/*
 * PHPStanTurbo\VarAnnotationProcessor — native implementation of
 * PHPStan\Analyser\VarAnnotationProcessor.
 *
 * The DI service the assignment, foreach, global and static-variable
 * handlers ask for the inline `@var` tags above a statement. Most statements
 * carry no doc comment: the native body reads the scope's function through
 * its direct entry and the node's `comments` attribute through
 * pt_engine_node_get_comments(), and returns the scope without a call. For a doc comment the scope
 * is asked through the MutatingScope direct entries and the collaborators
 * that stay PHP (FileTypeMapper, ResolvedPhpDocBlock, VarTag, the function
 * reflection, Comment::getText()) through cached method sites. Native callers
 * use pt_var_annotation_processor_process_var_annotation().
 */

#include "support.h"
#include "generated/VarAnnotationProcessor.h"

namespace slots = ptdecl::VarAnnotationProcessor::slot;
namespace sigs = ptdecl::VarAnnotationProcessor::sig;
#include "zv.h"
#include "TypeTraits.h"
#include "Engine.h"

#include <cstring>

zend_class_entry *pt_ce_var_annotation_processor = nullptr;

namespace {

/* {{{ PHP collaborators that are not ported yet */

pt_method_site pt_vap_assign_variable_site;
pt_method_site pt_vap_function_get_name_site;
pt_method_site pt_vap_get_text_site;
pt_method_site pt_vap_get_resolved_php_doc_site;
pt_method_site pt_vap_get_var_tags_site;
pt_method_site pt_vap_var_tag_get_type_site;

/* $object->method(...$argv) of a value the twin calls a method on: the
 * engine's Error for a non-object; UNDEF = pending exception */
zv::Val callOn(pt_method_site &site, zval *object, const char *lcname, size_t len, const char *name, uint32_t argc = 0, zval *argv = NULL)
{
	if (UNEXPECTED(Z_TYPE_P(object) != IS_OBJECT)) {
		zend_throw_error(NULL, "Call to a member function %s() on %s", name, zend_zval_value_name(object));
		return zv::Val();
	}
	return pt_call_method_cached(site, Z_OBJ_P(object), lcname, len, argc, argv);
}

/* }}} */

/* isset($array[$key]) of a list<string> value: the element, NULL when unset
 * or null */
zval *findKey(HashTable *array, zval *key)
{
	zval *found;
	switch (Z_TYPE_P(key)) {
		case IS_STRING:
			found = zend_symtable_find(array, Z_STR_P(key));
			break;
		case IS_LONG:
			found = zend_hash_index_find(array, (zend_ulong) Z_LVAL_P(key));
			break;
		case IS_NULL:
			found = zend_hash_find(array, ZSTR_EMPTY_ALLOC());
			break;
		case IS_FALSE:
			found = zend_hash_index_find(array, 0);
			break;
		case IS_TRUE:
			found = zend_hash_index_find(array, 1);
			break;
		default:
			found = NULL;
			break;
	}
	if (found == NULL) return NULL;
	ZVAL_DEREF(found);
	return Z_TYPE_P(found) == IS_NULL ? NULL : found;
}

} // namespace

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\VarAnnotationProcessor. */
class VarAnnotationProcessor
{
public:
	explicit VarAnnotationProcessor(zend_object *self) : self(self) {}

	/* Mirrors __construct(): the promoted property */
	static void construct(zend_object *object, zval *fileTypeMapper)
	{
		pt_write_slot(object, slots::fileTypeMapper, fileTypeMapper);
	}

	/* Mirrors processVarAnnotation(); the by-reference $changed as the
	 * caller's reference (NULL when not passed) and/or a native flag (NULL
	 * when not wanted). The resulting scope, UNDEF = pending exception */
	zv::Val processVarAnnotation(zval *scope, HashTable *variableNames, zval *node, zval *changedRef, bool *changedFlag) const
	{
		zv::Val function = pt_mutating_scope_get_function(Z_OBJ_P(scope));
		if (UNEXPECTED(function.isUndef())) return zv::Val();

		zv::Val comments = pt_engine_node_get_comments(Z_OBJ_P(node));
		if (UNEXPECTED(comments.isUndef())) return zv::Val();
		if (UNEXPECTED(Z_TYPE_P(comments.raw()) != IS_ARRAY)) {
			zend_type_error("%s::getComments(): Return value must be of type array, %s returned", ZSTR_VAL(Z_OBJCE_P(node)->name), zend_zval_value_name(comments.raw()));
			return zv::Val();
		}

		zv::Val varTags;
		if (zend_hash_num_elements(Z_ARRVAL_P(comments.raw())) > 0) {
			zend_class_entry *docCe = NULL;
			for (auto entry : zv::TableRef(Z_ARRVAL_P(comments.raw()))) {
				zval *comment = entry.value().deref().raw();
				if (Z_TYPE_P(comment) != IS_OBJECT) continue;
				if (docCe == NULL) {
					docCe = pt_class_loaded(PT_CLASS_DOC_COMMENT);
					if (docCe == NULL) {
						if (UNEXPECTED(EG(exception))) return zv::Val();
						continue;
					}
				}
				if (!instanceof_function(Z_OBJCE_P(comment), docCe)) continue;

				/* $this->fileTypeMapper is fetched before the arguments */
				zval *fileTypeMapper = OBJ_PROP_NUM(self, slots::fileTypeMapper);
				if (UNEXPECTED(Z_TYPE_P(fileTypeMapper) == IS_UNDEF)) {
					zend_throw_error(NULL, "Typed property %s::$fileTypeMapper must not be accessed before initialization", ZSTR_VAL(self->ce->name));
					return zv::Val();
				}
				zval getResolvedPhpDocArgs[5];
				for (zval &arg : getResolvedPhpDocArgs) {
					ZVAL_NULL(&arg);
				}
				zv::Val file = pt_mutating_scope_get_file(Z_OBJ_P(scope));
				if (UNEXPECTED(file.isUndef())) return zv::Val();
				zv::Val className;
				bool isInClass;
				if (UNEXPECTED(!pt_mutating_scope_is_in_class(Z_OBJ_P(scope), isInClass))) return zv::Val();
				if (isInClass) {
					zv::Val classReflection = pt_mutating_scope_get_class_reflection(Z_OBJ_P(scope));
					if (UNEXPECTED(classReflection.isUndef())) return zv::Val();
					className = reflectionName(classReflection.raw());
					if (UNEXPECTED(className.isUndef())) return zv::Val();
				}
				zv::Val traitName;
				bool isInTrait;
				if (UNEXPECTED(!pt_mutating_scope_is_in_trait(Z_OBJ_P(scope), isInTrait))) return zv::Val();
				if (isInTrait) {
					zv::Val traitReflection = pt_mutating_scope_get_trait_reflection(Z_OBJ_P(scope));
					if (UNEXPECTED(traitReflection.isUndef())) return zv::Val();
					traitName = reflectionName(traitReflection.raw());
					if (UNEXPECTED(traitName.isUndef())) return zv::Val();
				}
				zv::Val functionName;
				if (Z_TYPE_P(function.raw()) != IS_NULL) {
					functionName = callOn(pt_vap_function_get_name_site, function.raw(), PT_LC("getname"), "getName");
					if (UNEXPECTED(functionName.isUndef())) return zv::Val();
				}
				zv::Val text = pt_call_method_cached(pt_vap_get_text_site, Z_OBJ_P(comment), PT_LC("gettext"), 0, NULL);
				if (UNEXPECTED(text.isUndef())) return zv::Val();

				ZVAL_COPY_VALUE(&getResolvedPhpDocArgs[0], file.raw());
				if (!className.isUndef()) ZVAL_COPY_VALUE(&getResolvedPhpDocArgs[1], className.raw());
				if (!traitName.isUndef()) ZVAL_COPY_VALUE(&getResolvedPhpDocArgs[2], traitName.raw());
				if (!functionName.isUndef()) ZVAL_COPY_VALUE(&getResolvedPhpDocArgs[3], functionName.raw());
				ZVAL_COPY_VALUE(&getResolvedPhpDocArgs[4], text.raw());
				zv::Val resolvedPhpDoc = callOn(pt_vap_get_resolved_php_doc_site, fileTypeMapper, PT_LC("getresolvedphpdoc"), "getResolvedPhpDoc", 5, getResolvedPhpDocArgs);
				if (UNEXPECTED(resolvedPhpDoc.isUndef())) return zv::Val();
				zv::Val docVarTags = callOn(pt_vap_get_var_tags_site, resolvedPhpDoc.raw(), PT_LC("getvartags"), "getVarTags");
				if (UNEXPECTED(docVarTags.isUndef())) return zv::Val();
				if (Z_TYPE_P(docVarTags.raw()) != IS_ARRAY) continue;
				for (auto tag : zv::TableRef(Z_ARRVAL_P(docVarTags.raw()))) {
					if (varTags.isUndef()) {
						varTags = zv::Val(zv::Arr::create(0));
					}
					zval *value = tag.value().deref().raw();
					Z_TRY_ADDREF_P(value);
					if (tag.stringKeyOrNull() != NULL) {
						zend_hash_update(Z_ARRVAL_P(varTags.raw()), tag.stringKeyOrNull(), value);
					} else {
						zend_hash_index_update(Z_ARRVAL_P(varTags.raw()), tag.indexKey(), value);
					}
				}
			}
		}

		zv::Val result = zv::Val::copyOf(zv::Ref(scope));
		if (varTags.isUndef() || zend_hash_num_elements(Z_ARRVAL_P(varTags.raw())) == 0) return result;
		HashTable *tags = Z_ARRVAL_P(varTags.raw());

		for (auto entry : zv::TableRef(variableNames)) {
			zval *variableName = entry.value().deref().raw();
			zval *varTag = findKey(tags, variableName);
			if (varTag == NULL) continue;
			if (UNEXPECTED(!assign(result, variableName, varTag, changedRef, changedFlag))) return zv::Val();
		}

		if (zend_hash_num_elements(variableNames) == 1 && zend_hash_num_elements(tags) == 1) {
			zval *varTag = zend_hash_index_find(tags, 0);
			if (varTag != NULL) {
				ZVAL_DEREF(varTag);
			}
			if (varTag != NULL && Z_TYPE_P(varTag) != IS_NULL) {
				zval *variableName = zend_hash_index_find(variableNames, 0);
				if (variableName == NULL) {
					zend_error(E_WARNING, "Undefined array key 0");
					if (UNEXPECTED(EG(exception))) return zv::Val();
					variableName = &EG(uninitialized_zval);
				} else {
					ZVAL_DEREF(variableName);
				}
				if (UNEXPECTED(!assign(result, variableName, varTag, changedRef, changedFlag))) return zv::Val();
			}
		}

		return result;
	}

private:
	zend_object *self;

	/* $reflection->getName() of a class or trait reflection */
	static zv::Val reflectionName(zval *reflection)
	{
		if (UNEXPECTED(Z_TYPE_P(reflection) != IS_OBJECT)) {
			zend_throw_error(NULL, "Call to a member function getName() on %s", zend_zval_value_name(reflection));
			return zv::Val();
		}
		return pt_class_reflection_get_name(Z_OBJ_P(reflection));
	}

	/* $variableType = $varTag->getType(); $changed = true;
	 * $scope = $scope->assignVariable($variableName, $variableType, new
	 * MixedType(), TrinaryLogic::createYes()); false = pending exception */
	[[nodiscard]] static bool assign(zv::Val &scope, zval *variableName, zval *varTag, zval *changedRef, bool *changedFlag)
	{
		zv::Val variableType = callOn(pt_vap_var_tag_get_type_site, varTag, PT_LC("gettype"), "getType");
		if (UNEXPECTED(variableType.isUndef())) return false;
		if (changedFlag != NULL) {
			*changedFlag = true;
		}
		if (changedRef != NULL) {
			ZEND_TRY_ASSIGN_REF_TRUE(changedRef);
			if (UNEXPECTED(EG(exception))) return false;
		}
		zv::Val mixed = pt_type_new_mixed_type();
		if (UNEXPECTED(mixed.isUndef())) return false;
		zv::Val next;
		if (EXPECTED(Z_TYPE_P(variableName) == IS_STRING && Z_TYPE_P(scope.raw()) == IS_OBJECT)) {
			next = pt_mutating_scope_assign_variable(Z_OBJ_P(scope.raw()), Z_STR_P(variableName), variableType.raw(), mixed.raw(), pt_trinary_singleton(PT_TRI_YES));
		} else {
			/* a non-string name: the method's TypeError */
			zval args[4];
			ZVAL_COPY_VALUE(&args[0], variableName);
			ZVAL_COPY_VALUE(&args[1], variableType.raw());
			ZVAL_COPY_VALUE(&args[2], mixed.raw());
			ZVAL_COPY_VALUE(&args[3], pt_trinary_singleton(PT_TRI_YES));
			next = callOn(pt_vap_assign_variable_site, scope.raw(), PT_LC("assignvariable"), "assignVariable", 4, args);
		}
		if (UNEXPECTED(next.isUndef())) return false;
		scope = std::move(next);
		return true;
	}
};

} // namespace phpstanturbo

using phpstanturbo::VarAnnotationProcessor;

/* {{{ exported helpers: the shadowing class for native callers */

zv::Val pt_var_annotation_processor_process_var_annotation(zval *processor, zval *scope, zval *variableNames, zval *node, bool *changed)
{
	if (EXPECTED(Z_OBJCE_P(processor) == pt_ce_var_annotation_processor)) {
		return VarAnnotationProcessor(Z_OBJ_P(processor)).processVarAnnotation(scope, Z_ARRVAL_P(variableNames), node, NULL, changed);
	}
	zval args[4];
	ZVAL_COPY_VALUE(&args[0], scope);
	ZVAL_COPY_VALUE(&args[1], variableNames);
	ZVAL_COPY_VALUE(&args[2], node);
	ZVAL_NEW_REF(&args[3], &EG(uninitialized_zval));
	ZVAL_BOOL(Z_REFVAL(args[3]), changed != NULL && *changed);
	zv::Val result = pt_type_call(Z_OBJ_P(processor), PT_LC("processvarannotation"), 4, args);
	if (changed != NULL) {
		*changed = zend_is_true(Z_REFVAL(args[3]));
	}
	zval_ptr_dtor(&args[3]);
	return result;
}

/* }}} */

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

void pt_register_var_annotation_processor()
{
	reg::Class cls("PHPStan\\Analyser\\VarAnnotationProcessor");
	ptdecl::VarAnnotationProcessor::declareClass(cls);
	ptdecl::VarAnnotationProcessor::declareProperties(cls);

	/* the DI service's constructor: the generated arginfo names the twin's
	 * parameter class exactly */
	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *fileTypeMapper;
		if (!zp::parse<zp::Obj>(execute_data, fileTypeMapper)) RETURN_THROWS();
		VarAnnotationProcessor::construct(Z_OBJ_P(ZEND_THIS), fileTypeMapper);
	});

	cls.method(sigs::processVarAnnotation, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *scope, *node, *changed = NULL;
		HashTable *variableNames;
		ZEND_PARSE_PARAMETERS_START(3, 4)
			Z_PARAM_OBJECT(scope)
			Z_PARAM_ARRAY_HT(variableNames)
			Z_PARAM_OBJECT(node)
			Z_PARAM_OPTIONAL
			Z_PARAM_ZVAL(changed)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(VarAnnotationProcessor(Z_OBJ_P(ZEND_THIS)).processVarAnnotation(scope, variableNames, node, changed != NULL && Z_ISREF_P(changed) ? changed : NULL, NULL));
	});

	cls.shadow(&pt_ce_var_annotation_processor);
}

/* }}} */
