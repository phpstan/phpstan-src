/*
 * Native readers of PHPStan\PhpDoc\ResolvedPhpDocBlock's memoized answers.
 *
 * The resolved PHPDoc block stays PHP; its getters resolve a tag family from
 * the PHPDoc node once and keep it in a private slot, whose "not resolved
 * yet" state is a sentinel: false for the tag lists and the self-out tag, a
 * bool for the return / throws / deprecated tags (is_bool()), null for the
 * bool flags (??=), 'notLoaded' for isPure(). A reader answers from the slot
 * once it holds a resolved value — what the getter returns from then on — and
 * from the constructor-written slots of the plain getters; anything else
 * (an unresolved memo, an uninitialized slot, any other class) calls the
 * getter, which resolves, memoizes and raises every error the twin raises.
 * Only an object of exactly the final class (resolved through the class map
 * without autoloading) is read; the offsets are resolved once per request.
 */

#include "support.h"
#include "zv.h"
#include "TypeTraits.h"
#include "Engine.h"

namespace {

/* the sentinel a memo slot holds until its getter resolved it */
enum MemoKind : uint8_t
{
	PT_RPD_PLAIN,       /* a constructor-written slot, any initialized value */
	PT_RPD_ARRAY_FALSE, /* false until resolved to an array */
	PT_RPD_TAG_FALSE,   /* false until resolved to a tag or null */
	PT_RPD_TAG_BOOL,    /* a bool until resolved to a tag or null (is_bool()) */
	PT_RPD_BOOL_NULL,   /* null until resolved to a bool (??=) */
	PT_RPD_PURE,        /* 'notLoaded' until resolved to a bool or null */
	PT_RPD_HAS_PHP_DOC_STRING, /* the doc string is not the empty one */
};

struct MemberInfo
{
	const char *property;
	const char *lcname;
	size_t len;
	const char *name;
	MemoKind kind;
};

const MemberInfo pt_rpd_members[PT_RPD_MEMBER_COUNT] = {
	/* PT_RPD_GET_VAR_TAGS */ {"varTags", PT_LC("getvartags"), "getVarTags", PT_RPD_ARRAY_FALSE},
	/* PT_RPD_GET_METHOD_TAGS */ {"methodTags", PT_LC("getmethodtags"), "getMethodTags", PT_RPD_ARRAY_FALSE},
	/* PT_RPD_GET_PROPERTY_TAGS */ {"propertyTags", PT_LC("getpropertytags"), "getPropertyTags", PT_RPD_ARRAY_FALSE},
	/* PT_RPD_GET_TEMPLATE_TAGS */ {"templateTags", PT_LC("gettemplatetags"), "getTemplateTags", PT_RPD_PLAIN},
	/* PT_RPD_GET_EXTENDS_TAGS */ {"extendsTags", PT_LC("getextendstags"), "getExtendsTags", PT_RPD_ARRAY_FALSE},
	/* PT_RPD_GET_IMPLEMENTS_TAGS */ {"implementsTags", PT_LC("getimplementstags"), "getImplementsTags", PT_RPD_ARRAY_FALSE},
	/* PT_RPD_GET_USES_TAGS */ {"usesTags", PT_LC("getusestags"), "getUsesTags", PT_RPD_ARRAY_FALSE},
	/* PT_RPD_GET_PARAM_TAGS */ {"paramTags", PT_LC("getparamtags"), "getParamTags", PT_RPD_ARRAY_FALSE},
	/* PT_RPD_GET_PARAM_OUT_TAGS */ {"paramOutTags", PT_LC("getparamouttags"), "getParamOutTags", PT_RPD_ARRAY_FALSE},
	/* PT_RPD_GET_PARAMS_IMMEDIATELY_INVOKED_CALLABLE */ {"paramsImmediatelyInvokedCallable", PT_LC("getparamsimmediatelyinvokedcallable"), "getParamsImmediatelyInvokedCallable", PT_RPD_ARRAY_FALSE},
	/* PT_RPD_GET_PARAMS_PURE_UNLESS_CALLABLE_IS_IMPURE */ {"paramsPureUnlessCallableIsImpure", PT_LC("getparamspureunlesscallableisimpure"), "getParamsPureUnlessCallableIsImpure", PT_RPD_ARRAY_FALSE},
	/* PT_RPD_GET_PARAM_CLOSURE_THIS_TAGS */ {"paramClosureThisTags", PT_LC("getparamclosurethistags"), "getParamClosureThisTags", PT_RPD_ARRAY_FALSE},
	/* PT_RPD_GET_RETURN_TAG */ {"returnTag", PT_LC("getreturntag"), "getReturnTag", PT_RPD_TAG_BOOL},
	/* PT_RPD_GET_THROWS_TAG */ {"throwsTag", PT_LC("getthrowstag"), "getThrowsTag", PT_RPD_TAG_BOOL},
	/* PT_RPD_GET_MIXIN_TAGS */ {"mixinTags", PT_LC("getmixintags"), "getMixinTags", PT_RPD_ARRAY_FALSE},
	/* PT_RPD_GET_REQUIRE_EXTENDS_TAGS */ {"requireExtendsTags", PT_LC("getrequireextendstags"), "getRequireExtendsTags", PT_RPD_ARRAY_FALSE},
	/* PT_RPD_GET_REQUIRE_IMPLEMENTS_TAGS */ {"requireImplementsTags", PT_LC("getrequireimplementstags"), "getRequireImplementsTags", PT_RPD_ARRAY_FALSE},
	/* PT_RPD_GET_SEALED_TAGS */ {"sealedTypeTags", PT_LC("getsealedtags"), "getSealedTags", PT_RPD_ARRAY_FALSE},
	/* PT_RPD_GET_TYPE_ALIAS_TAGS */ {"typeAliasTags", PT_LC("gettypealiastags"), "getTypeAliasTags", PT_RPD_ARRAY_FALSE},
	/* PT_RPD_GET_TYPE_ALIAS_IMPORT_TAGS */ {"typeAliasImportTags", PT_LC("gettypealiasimporttags"), "getTypeAliasImportTags", PT_RPD_ARRAY_FALSE},
	/* PT_RPD_GET_ASSERT_TAGS */ {"assertTags", PT_LC("getasserttags"), "getAssertTags", PT_RPD_ARRAY_FALSE},
	/* PT_RPD_GET_SELF_OUT_TAG */ {"selfOutTypeTag", PT_LC("getselfouttag"), "getSelfOutTag", PT_RPD_TAG_FALSE},
	/* PT_RPD_GET_DEPRECATED_TAG */ {"deprecatedTag", PT_LC("getdeprecatedtag"), "getDeprecatedTag", PT_RPD_TAG_BOOL},
	/* PT_RPD_IS_DEPRECATED */ {"isDeprecated", PT_LC("isdeprecated"), "isDeprecated", PT_RPD_BOOL_NULL},
	/* PT_RPD_IS_NOT_DEPRECATED */ {"isNotDeprecated", PT_LC("isnotdeprecated"), "isNotDeprecated", PT_RPD_BOOL_NULL},
	/* PT_RPD_IS_INTERNAL */ {"isInternal", PT_LC("isinternal"), "isInternal", PT_RPD_BOOL_NULL},
	/* PT_RPD_IS_FINAL */ {"isFinal", PT_LC("isfinal"), "isFinal", PT_RPD_BOOL_NULL},
	/* PT_RPD_HAS_CONSISTENT_CONSTRUCTOR */ {"hasConsistentConstructor", PT_LC("hasconsistentconstructor"), "hasConsistentConstructor", PT_RPD_BOOL_NULL},
	/* PT_RPD_ACCEPTS_NAMED_ARGUMENTS */ {"acceptsNamedArguments", PT_LC("acceptsnamedarguments"), "acceptsNamedArguments", PT_RPD_BOOL_NULL},
	/* PT_RPD_GET_TEMPLATE_TYPE_MAP */ {"templateTypeMap", PT_LC("gettemplatetypemap"), "getTemplateTypeMap", PT_RPD_PLAIN},
	/* PT_RPD_IS_PURE */ {"isPure", PT_LC("ispure"), "isPure", PT_RPD_PURE},
	/* PT_RPD_ARE_ALL_METHODS_PURE */ {"areAllMethodsPure", PT_LC("areallmethodspure"), "areAllMethodsPure", PT_RPD_BOOL_NULL},
	/* PT_RPD_ARE_ALL_METHODS_IMPURE */ {"areAllMethodsImpure", PT_LC("areallmethodsimpure"), "areAllMethodsImpure", PT_RPD_BOOL_NULL},
	/* PT_RPD_IS_READ_ONLY */ {"isReadOnly", PT_LC("isreadonly"), "isReadOnly", PT_RPD_BOOL_NULL},
	/* PT_RPD_IS_IMMUTABLE */ {"isImmutable", PT_LC("isimmutable"), "isImmutable", PT_RPD_BOOL_NULL},
	/* PT_RPD_IS_ALLOWED_PRIVATE_MUTATION */ {"isAllowedPrivateMutation", PT_LC("isallowedprivatemutation"), "isAllowedPrivateMutation", PT_RPD_BOOL_NULL},
	/* PT_RPD_HAS_PHP_DOC_STRING */ {"phpDocString", PT_LC("hasphpdocstring"), "hasPhpDocString", PT_RPD_HAS_PHP_DOC_STRING},
	/* PT_RPD_GET_PHP_DOC_STRING */ {"phpDocString", PT_LC("getphpdocstring"), "getPhpDocString", PT_RPD_PLAIN},
	/* PT_RPD_GET_FILENAME */ {"filename", PT_LC("getfilename"), "getFilename", PT_RPD_PLAIN},
	/* PT_RPD_GET_NULLABLE_NAME_SCOPE */ {"nameScope", PT_LC("getnullablenamescope"), "getNullableNameScope", PT_RPD_PLAIN},
};

struct Layout
{
	zend_class_entry *ce;
	uint32_t generation;
	uint32_t classCount; /* EG(class_table) size when the class was not declared yet */
	bool usable;
	uint32_t offsets[PT_RPD_MEMBER_COUNT];
};

Layout pt_rpd_layout = { NULL, 0, 0, false, {} };
pt_method_site pt_rpd_sites[PT_RPD_MEMBER_COUNT];

zend_never_inline bool resolveLayout(zend_class_entry *candidate)
{
	Layout &layout = pt_rpd_layout;
	uint32_t classCount = zend_hash_num_elements(EG(class_table));
	if (layout.generation == pt_engine_generation) {
		if (layout.ce != NULL) return layout.ce == candidate;
		/* an undeclared class is retried once more classes exist */
		if (!layout.usable || layout.classCount == classCount) return false;
	}
	layout.generation = pt_engine_generation;
	layout.classCount = classCount;
	layout.ce = NULL;
	layout.usable = true;
	zend_class_entry *ce = pt_class_loaded(PT_CLASS_RESOLVED_PHP_DOC_BLOCK);
	if (ce == NULL) {
		if (UNEXPECTED(EG(exception) != NULL)) layout.usable = false;
		return false;
	}
	for (uint32_t i = 0; i < PT_RPD_MEMBER_COUNT; i++) {
		const char *property = pt_rpd_members[i].property;
		int32_t offset = pt_instance_prop_offset(ce, property, strlen(property));
		if (UNEXPECTED(offset < 0)) {
			/* not the twin these readers know: every call takes the getters */
			layout.usable = false;
			return false;
		}
		layout.offsets[i] = (uint32_t) offset;
	}
	layout.ce = ce;
	return ce == candidate;
}

inline bool isResolvedPhpDocBlock(zend_class_entry *ce)
{
	const Layout &layout = pt_rpd_layout;
	if (EXPECTED(layout.ce == ce && layout.generation == pt_engine_generation && ce != NULL)) return true;
	return resolveLayout(ce);
}

/* the answer the slot holds for the member, NULL while the getter has to
 * resolve it (borrowed; `computed` receives a value the reader derives) */
inline zval *resolvedAnswer(zend_object *block, pt_resolved_php_doc_member member, zval *computed)
{
	zval *value = OBJ_PROP(block, pt_rpd_layout.offsets[member]);
	switch (pt_rpd_members[member].kind) {
		case PT_RPD_PLAIN:
			return Z_TYPE_P(value) != IS_UNDEF ? value : NULL;
		case PT_RPD_ARRAY_FALSE:
			return Z_TYPE_P(value) == IS_ARRAY ? value : NULL;
		case PT_RPD_TAG_FALSE:
			return Z_TYPE_P(value) == IS_OBJECT || Z_TYPE_P(value) == IS_NULL ? value : NULL;
		case PT_RPD_TAG_BOOL:
			return Z_TYPE_P(value) == IS_OBJECT || Z_TYPE_P(value) == IS_NULL ? value : NULL;
		case PT_RPD_BOOL_NULL:
			return Z_TYPE_P(value) == IS_TRUE || Z_TYPE_P(value) == IS_FALSE ? value : NULL;
		case PT_RPD_PURE:
			return Z_TYPE_P(value) == IS_TRUE || Z_TYPE_P(value) == IS_FALSE || Z_TYPE_P(value) == IS_NULL ? value : NULL;
		case PT_RPD_HAS_PHP_DOC_STRING:
			if (Z_TYPE_P(value) != IS_STRING) return NULL;
			/* $this->phpDocString !== self::EMPTY_DOC_STRING */
			ZVAL_BOOL(computed, !zend_string_equals_literal(Z_STR_P(value), "/** */"));
			return computed;
	}
	return NULL;
}

} // namespace

zv::Val pt_resolved_php_doc_block_call(zval *block, pt_resolved_php_doc_member member)
{
	if (UNEXPECTED(Z_TYPE_P(block) != IS_OBJECT)) {
		zend_throw_error(NULL, "Call to a member function %s() on %s", pt_rpd_members[member].name, zend_zval_value_name(block));
		return zv::Val();
	}
	zend_object *object = Z_OBJ_P(block);
	if (EXPECTED(isResolvedPhpDocBlock(object->ce))) {
		zval computed = {};
		zval *answer = resolvedAnswer(object, member, &computed);
		if (EXPECTED(answer != NULL)) return zv::Val::copyOf(zv::Ref(answer));
	}
	const MemberInfo &info = pt_rpd_members[member];
	return pt_call_method_cached(pt_rpd_sites[member], object, info.lcname, info.len, 0, NULL);
}

bool pt_resolved_php_doc_block_bool(zval *block, pt_resolved_php_doc_member member, bool &out)
{
	if (EXPECTED(Z_TYPE_P(block) == IS_OBJECT) && EXPECTED(isResolvedPhpDocBlock(Z_OBJCE_P(block)))) {
		zval computed;
		zval *answer = resolvedAnswer(Z_OBJ_P(block), member, &computed);
		if (EXPECTED(answer != NULL)) {
			out = zend_is_true(answer);
			return true;
		}
	}
	zv::Val result = pt_resolved_php_doc_block_call(block, member);
	if (UNEXPECTED(result.isUndef())) return false;
	out = zend_is_true(result.raw());
	return true;
}
