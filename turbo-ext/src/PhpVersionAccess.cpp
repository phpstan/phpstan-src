/*
 * Native readers of PHPStan\Php\PhpVersion.
 *
 * PhpVersion stays PHP: getVersionId() returns its $versionId, and every
 * supports*() / deprecates*() query the engine asks is a comparison of it
 * with a constant (`$this->versionId >= 80100`, `< 80000`), spelled next to
 * each query below. Only an object of exactly the final class takes the
 * slot; anything else, or an uninitialized slot, calls the method. The class
 * entry is resolved through the class map without autoloading (an object of
 * an undeclared class cannot exist) and the offset once per request.
 */

#include "support.h"
#include "zv.h"
#include "TypeTraits.h"
#include "Engine.h"

namespace {

/* {{{ PhpVersion */

struct VersionQuery
{
	const char *lcname;
	size_t len;
	const char *name;
	zend_long threshold;
	bool below; /* `$this->versionId < threshold` instead of `>=` */
};

const VersionQuery pt_pva_queries[PT_PHP_VERSION_QUERY_COUNT] = {
	/* PT_PHP_VERSION_SUPPORTS_LEGACY_CONSTRUCTOR */ {PT_LC("supportslegacyconstructor"), "supportsLegacyConstructor", 80000, true},
	/* PT_PHP_VERSION_DEPRECATES_DYNAMIC_PROPERTIES */ {PT_LC("deprecatesdynamicproperties"), "deprecatesDynamicProperties", 80200, false},
	/* PT_PHP_VERSION_SUPPORTS_CALLABLE_INSTANCE_METHODS */ {PT_LC("supportscallableinstancemethods"), "supportsCallableInstanceMethods", 80000, true},
	/* PT_PHP_VERSION_THROWS_ON_STRING_CAST */ {PT_LC("throwsonstringcast"), "throwsOnStringCast", 70400, false},
	/* PT_PHP_VERSION_NON_NUMERIC_STRING_AND_INTEGER_IS_FALSE_ON_LOOSE_COMPARISON */ {PT_LC("nonnumericstringandintegerisfalseonloosecomparison"), "nonNumericStringAndIntegerIsFalseOnLooseComparison", 80000, false},
	/* PT_PHP_VERSION_SUPPORTS_READ_ONLY_PROPERTIES */ {PT_LC("supportsreadonlyproperties"), "supportsReadOnlyProperties", 80100, false},
	/* PT_PHP_VERSION_SUPPORTS_READONLY_PROPERTY_REINITIALIZATION_ON_CLONE */ {PT_LC("supportsreadonlypropertyreinitializationonclone"), "supportsReadonlyPropertyReinitializationOnClone", 80300, false},
	/* PT_PHP_VERSION_SUPPORTS_ASYMMETRIC_VISIBILITY */ {PT_LC("supportsasymmetricvisibility"), "supportsAsymmetricVisibility", 80400, false},
	/* PT_PHP_VERSION_SUPPORTS_ENUMS */ {PT_LC("supportsenums"), "supportsEnums", 80100, false},
	/* PT_PHP_VERSION_SUPPORTS_PROPERTY_HOOKS */ {PT_LC("supportspropertyhooks"), "supportsPropertyHooks", 80400, false},
};

pt_method_site pt_pva_query_sites[PT_PHP_VERSION_QUERY_COUNT];
pt_method_site pt_pva_get_version_id_site;

struct VersionLayout
{
	zend_class_entry *ce;
	uint32_t generation;
	uint32_t versionId;
};

VersionLayout pt_pva_layout = { NULL, 0, 0 };

/* the initialized $versionId of exactly a PhpVersion, NULL otherwise */
inline zval *versionIdOf(zval *phpVersion)
{
	if (UNEXPECTED(Z_TYPE_P(phpVersion) != IS_OBJECT)) return NULL;
	zend_class_entry *ce = Z_OBJCE_P(phpVersion);
	VersionLayout &layout = pt_pva_layout;
	if (UNEXPECTED(layout.ce != ce || layout.generation != pt_engine_generation)) {
		zend_class_entry *expected = pt_class_loaded(PT_CLASS_PHP_VERSION);
		if (expected == NULL || expected != ce) return NULL;
		int32_t offset = pt_instance_prop_offset(ce, PT_LC("versionId"));
		if (UNEXPECTED(offset < 0)) return NULL;
		layout = { ce, pt_engine_generation, (uint32_t) offset };
	}
	zval *value = OBJ_PROP(Z_OBJ_P(phpVersion), layout.versionId);
	return EXPECTED(Z_TYPE_P(value) == IS_LONG) ? value : NULL;
}

/* }}} */

} // namespace

/* {{{ PhpVersion */

bool pt_php_version_answer(zval *phpVersion, pt_php_version_query query, bool &out)
{
	const VersionQuery &q = pt_pva_queries[query];
	zval *versionId = versionIdOf(phpVersion);
	if (EXPECTED(versionId != NULL)) {
		out = q.below ? Z_LVAL_P(versionId) < q.threshold : Z_LVAL_P(versionId) >= q.threshold;
		return true;
	}
	if (UNEXPECTED(Z_TYPE_P(phpVersion) != IS_OBJECT)) {
		zend_throw_error(NULL, "Call to a member function %s() on %s", q.name, zend_zval_value_name(phpVersion));
		return false;
	}
	zv::Val result = pt_call_method_cached(pt_pva_query_sites[query], Z_OBJ_P(phpVersion), q.lcname, q.len, 0, NULL);
	if (UNEXPECTED(result.isUndef())) return false;
	out = zend_is_true(result.raw());
	return true;
}

zv::Val pt_php_version_get_version_id(zval *phpVersion)
{
	zval *versionId = versionIdOf(phpVersion);
	if (EXPECTED(versionId != NULL)) return zv::Val::integer(Z_LVAL_P(versionId));
	if (UNEXPECTED(Z_TYPE_P(phpVersion) != IS_OBJECT)) {
		zend_throw_error(NULL, "Call to a member function getVersionId() on %s", zend_zval_value_name(phpVersion));
		return zv::Val();
	}
	return pt_call_method_cached(pt_pva_get_version_id_site, Z_OBJ_P(phpVersion), PT_LC("getversionid"), 0, NULL);
}

/* }}} */
