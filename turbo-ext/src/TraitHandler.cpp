/*
 * PHPStanTurbo\TraitHandler — native implementation of
 * PHPStan\Analyser\StmtHandler\TraitHandler.
 *
 * A DI service (#[AutowiredService]): the constructor keeps the twin's
 * arginfo so Nette autowires it. processStmt() is registered as the class's
 * statement-handler entry (Engine.h); MutatingScope, the ReflectionProvider
 * and the statement results are called through their direct entries, the
 * php-parser Name, ClassReflection::getRequireImplementsTags() and the tags'
 * getType() through the sites below.
 */

#include "support.h"
#include "generated/TraitHandler.h"

namespace slots = ptdecl::TraitHandler::slot;
namespace sigs = ptdecl::TraitHandler::sig;
#include "zv.h"
#include "TypeTraits.h"
#include "Engine.h"
#include "AnalyserValues.h"
#include "StmtHandlerCalls.h"

zend_class_entry *pt_ce_trait_handler = nullptr;

namespace {

pt_property_site pt_th_namespaced_name_site;
pt_property_site pt_th_name_site;

pt_method_site pt_th_get_require_implements_tags_site;
pt_method_site pt_th_tag_get_type_site;

/* the 'trait_exists' literal (module startup) */
zend_string *pt_th_trait_exists = nullptr;

} // namespace

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\StmtHandler\TraitHandler; UNDEF = pending
 * exception. */
class TraitHandler
{
public:
	explicit TraitHandler(zend_object *self) : self(self) {}

	/* the constructor body: the promoted property */
	void construct(zval *reflectionProvider)
	{
		zv::ObjRef(self).propAtWrite(slots::reflectionProvider, zv::Val::copyOf(zv::Ref(reflectionProvider)));
	}

	/* Mirrors supports(); false = pending exception */
	[[nodiscard]] static bool supports(zval *stmt, bool &out)
	{
		bool error = false;
		out = ptsh::isInstanceOf(stmt, PT_CLASS_TRAIT_STMT, error);
		return !error;
	}

	/* Mirrors processStmt(). */
	zv::Val processStmt(zval *stmt, zval *scope) const
	{
		// declaring the trait defines it in global state,
		// so a negative trait_exists() narrowing that may refer to that trait must be forgotten
		zval *name = pt_property_cached(pt_th_namespaced_name_site, Z_OBJ_P(stmt), PT_LC("namespacedName"));
		if (name != NULL) ZVAL_DEREF(name);
		if (name == NULL || Z_TYPE_P(name) == IS_UNDEF || Z_TYPE_P(name) == IS_NULL) {
			name = ptsh::readNodeProperty(pt_th_name_site, stmt, PT_LC("name"));
			if (UNEXPECTED(name == NULL)) return zv::Val();
		}
		zv::Val declaredSymbolName = zv::Val::null();
		bool error = false;
		if (ptsh::isInstanceOf(name, PT_CLASS_NAME, error)) {
			zv::Val nameHold = zv::Val::copyOf(zv::Ref(name));
			declaredSymbolName = pt_name_node_to_string(nameHold.raw());
			if (UNEXPECTED(declaredSymbolName.isUndef())) return zv::Val();
		}
		if (UNEXPECTED(error)) return zv::Val();
		zv::Arr functionNames = zv::Arr::create(1);
		functionNames.push(zv::Val::string(pt_th_trait_exists));
		zv::Val invalidatedScope = pt_mutating_scope_invalidate_existence_check_expressions(Z_OBJ_P(scope), functionNames.raw(), declaredSymbolName.raw());
		if (UNEXPECTED(invalidatedScope.isUndef())) return zv::Val();

		// the interfaces a class using the trait has to implement
		zv::Val dependencies = getRequiredDependencies(stmt, invalidatedScope.raw());
		if (UNEXPECTED(dependencies.isUndef())) return zv::Val();

		zval emptyArray;
		ZVAL_EMPTY_ARRAY(&emptyArray);
		return pt_internal_statement_result_new(invalidatedScope.raw(), false, false, &emptyArray, &emptyArray, &emptyArray, NULL, NULL, -1, dependencies.raw());
	}

	/* the statement-handler entry (Engine.h) */
	static zv::Val processStmtEntry(zend_object *handler, zval *nodeScopeResolver, zval *stmt, zval *scope, zval *storage, zval *nodeCallback, zval *context)
	{
		(void) nodeScopeResolver;
		(void) storage;
		(void) nodeCallback;
		(void) context;
		return TraitHandler(handler).processStmt(stmt, scope);
	}

private:
	zend_object *self;

	/* the twin's $dependencies: the types of the trait's
	 * @phpstan-require-implements tags, null when the trait is not known */
	zv::Val getRequiredDependencies(zval *stmt, zval *scope) const
	{
		zval *namespacedName = pt_property_cached(pt_th_namespaced_name_site, Z_OBJ_P(stmt), PT_LC("namespacedName"));
		if (namespacedName != NULL) ZVAL_DEREF(namespacedName);
		if (namespacedName == NULL || Z_TYPE_P(namespacedName) == IS_UNDEF || Z_TYPE_P(namespacedName) == IS_NULL) return zv::Val::null();
		zv::Val nameHold = zv::Val::copyOf(zv::Ref(namespacedName));
		if (UNEXPECTED(Z_TYPE_P(nameHold.raw()) != IS_OBJECT)) {
			zend_throw_error(NULL, "Call to a member function toString() on %s", zend_zval_value_name(nameHold.raw()));
			return zv::Val();
		}
		zv::Val className = pt_name_node_to_string(nameHold.raw());
		if (UNEXPECTED(className.isUndef())) return zv::Val();
		zval *reflectionProvider = OBJ_PROP_NUM(self, slots::reflectionProvider);
		bool hasClass = false;
		if (UNEXPECTED(!pt_reflection_provider_has_class(Z_OBJ_P(reflectionProvider), className.raw(), hasClass))) return zv::Val();
		if (!hasClass) return zv::Val::null();

		zv::Val traitReflection = pt_reflection_provider_get_class(Z_OBJ_P(reflectionProvider), className.raw());
		if (UNEXPECTED(traitReflection.isUndef())) return zv::Val();
		if (UNEXPECTED(Z_TYPE_P(traitReflection.raw()) != IS_OBJECT)) {
			zend_throw_error(NULL, "Call to a member function getRequireImplementsTags() on %s", zend_zval_value_name(traitReflection.raw()));
			return zv::Val();
		}
		zv::Val tags = pt_call_method_cached(pt_th_get_require_implements_tags_site, Z_OBJ_P(traitReflection.raw()), PT_LC("getrequireimplementstags"), 0, NULL);
		if (UNEXPECTED(tags.isUndef())) return zv::Val();
		zv::Arr requiredTypes = zv::Arr::empty();
		if (Z_TYPE_P(tags.raw()) == IS_ARRAY) {
			for (auto entry : zv::ArrRef(tags.raw())) {
				zval *tag = entry.value().deref().raw();
				if (UNEXPECTED(Z_TYPE_P(tag) != IS_OBJECT)) {
					zend_throw_error(NULL, "Call to a member function getType() on %s", zend_zval_value_name(tag));
					return zv::Val();
				}
				zv::Val type = pt_call_method_cached(pt_th_tag_get_type_site, Z_OBJ_P(tag), PT_LC("gettype"), 0, NULL);
				if (UNEXPECTED(type.isUndef())) return zv::Val();
				requiredTypes.push(std::move(type));
			}
		}
		return pt_dependencies_create_in(scope, requiredTypes.raw());
	}
};

} // namespace phpstanturbo

using phpstanturbo::TraitHandler;

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

PT_MINIT_REGISTRATION(pt_register_trait_handler)
{
	pt_th_trait_exists = zend_string_init_interned(PT_LC("trait_exists"), 1);

	reg::Class cls("PHPStan\\Analyser\\StmtHandler\\TraitHandler");
	ptdecl::TraitHandler::declareClass(cls);
	ptdecl::TraitHandler::declareProperties(cls);

	/* the real parameter class names: the DI container autowires the
	 * service by reflecting the constructor */
	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *reflectionProvider;
		if (!zp::parse<zp::Obj>(execute_data, reflectionProvider)) RETURN_THROWS();
		TraitHandler(Z_OBJ_P(ZEND_THIS)).construct(reflectionProvider);
	});

	cls.method(sigs::supports, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *stmt;
		if (!zp::parse<zp::Obj>(execute_data, stmt)) RETURN_THROWS();
		bool out;
		if (UNEXPECTED(!TraitHandler::supports(stmt, out))) RETURN_THROWS();
		RETURN_BOOL(out);
	});

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
		PT_RETURN_VAL(TraitHandler(Z_OBJ_P(ZEND_THIS)).processStmt(stmt, scope));
	});

	cls.shadow(&pt_ce_trait_handler);
	pt_stmt_handler_entry_register(&pt_ce_trait_handler, &TraitHandler::processStmtEntry);
}

/* }}} */
