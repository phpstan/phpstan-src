/*
 * PHPStanTurbo\TemplateTypeScope — native implementation of
 * PHPStan\Type\Generic\TemplateTypeScope.
 *
 * When the extension is active, PHPStan\Type\Generic\TemplateTypeScope is
 * this class, declared under that name at activation (final, like the
 * twin). The two nullable names are the object's own slots; equals()
 * compares them natively for two native scopes.
 */

#include "support.h"
#include "generated/TemplateTypeScope.h"

namespace slots = ptdecl::TemplateTypeScope::slot;
namespace sigs = ptdecl::TemplateTypeScope::sig;
#include "zv.h"
#include "TypeTraits.h"

zend_class_entry *pt_ce_template_type_scope = NULL;

namespace phpstanturbo {

/* Mirrors PHPStan\Type\Generic\TemplateTypeScope. State lives in the PHP
 * object's $className and $functionName. */
class TemplateTypeScope
{
public:
	explicit TemplateTypeScope(zend_object *self) : self(self) {}

	/* a `?string` slot, borrowed (IS_NULL or IS_STRING); NULL with an
	 * Error pending when uninitialized */
	zval *className() const { return slot(slots::className, "className"); }
	zval *functionName() const { return slot(slots::functionName, "functionName"); }

	/* __construct($className, $functionName) — NULL for null */
	void construct(zend_string *className, zend_string *functionName)
	{
		zv::ObjRef ref(self);
		ref.propAtWrite(slots::className, className != NULL ? zv::Val::string(className) : zv::Val::null());
		ref.propAtWrite(slots::functionName, functionName != NULL ? zv::Val::string(functionName) : zv::Val::null());
	}

	/* new self($className, $functionName) (NULL for null); UNDEF =
	 * pending exception */
	static zv::Val create(zend_string *className, zend_string *functionName)
	{
		zval object;
		if (UNEXPECTED(object_init_ex(&object, pt_ce_template_type_scope) != SUCCESS)) return zv::Val();
		TemplateTypeScope(Z_OBJ(object)).construct(className, functionName);
		return zv::Val::adopt(object);
	}

	static zv::Val createWithAnonymousFunction() { return create(NULL, NULL); }
	static zv::Val createWithFunction(zend_string *functionName) { return create(NULL, functionName); }
	static zv::Val createWithMethod(zend_string *className, zend_string *functionName) { return create(className, functionName); }
	static zv::Val createWithClass(zend_string *className) { return create(className, NULL); }

	/* getClassName() / getFunctionName(); UNDEF = pending exception */
	zv::Val getClassName() const
	{
		zval *value = className();
		return value == NULL ? zv::Val() : zv::Val::copyOf(zv::Ref(value));
	}

	zv::Val getFunctionName() const
	{
		zval *value = functionName();
		return value == NULL ? zv::Val() : zv::Val::copyOf(zv::Ref(value));
	}

	/* equals(): both names identical (=== on ?string); false = pending
	 * exception */
	[[nodiscard]] bool equals(zend_object *other, bool &out) const
	{
		zval *className = this->className();
		if (UNEXPECTED(className == NULL)) return false;
		zval *functionName = this->functionName();
		if (UNEXPECTED(functionName == NULL)) return false;
		TemplateTypeScope otherScope(other);
		zval *otherClassName = otherScope.className();
		if (UNEXPECTED(otherClassName == NULL)) return false;
		zval *otherFunctionName = otherScope.functionName();
		if (UNEXPECTED(otherFunctionName == NULL)) return false;
		out = identical(className, otherClassName) && identical(functionName, otherFunctionName);
		return true;
	}

	/* describe(); an owned string, UNDEF = pending exception */
	zv::Val describe() const
	{
		zval *className = this->className();
		if (UNEXPECTED(className == NULL)) return zv::Val();
		zval *functionName = this->functionName();
		if (UNEXPECTED(functionName == NULL)) return zv::Val();
		bool hasClass = Z_TYPE_P(className) == IS_STRING;
		bool hasFunction = Z_TYPE_P(functionName) == IS_STRING;
		if (!hasClass && !hasFunction) return zv::Val::string(PT_LC("anonymous function"));
		if (!hasClass) return zv::Val::adoptString(zend_strpprintf(0, "function %s()", Z_STRVAL_P(functionName)));
		if (!hasFunction) return zv::Val::adoptString(zend_strpprintf(0, "class %s", Z_STRVAL_P(className)));
		return zv::Val::adoptString(zend_strpprintf(0, "method %s::%s()", Z_STRVAL_P(className), Z_STRVAL_P(functionName)));
	}

private:
	zend_object *self;

	zval *slot(uint32_t index, const char *name) const
	{
		zval *value = OBJ_PROP_NUM(self, index);
		if (UNEXPECTED(Z_TYPE_P(value) != IS_STRING && Z_TYPE_P(value) != IS_NULL)) {
			zend_throw_error(NULL, "Typed property %s::$%s must not be accessed before initialization", ZSTR_VAL(pt_ce_template_type_scope->name), name);
			return NULL;
		}
		return value;
	}

	/* $a === $b on two ?string values */
	static bool identical(zval *a, zval *b)
	{
		if (Z_TYPE_P(a) != Z_TYPE_P(b)) return false;
		return Z_TYPE_P(a) == IS_NULL || zend_string_equals(Z_STR_P(a), Z_STR_P(b));
	}
};

} // namespace phpstanturbo

using phpstanturbo::TemplateTypeScope;

/* {{{ exported helpers */

bool pt_template_type_scope_new(zval *out, zend_string *className, zend_string *functionName)
{
	zv::Val scope = TemplateTypeScope::create(className, functionName);
	if (UNEXPECTED(scope.isUndef())) return false;
	scope.intoReturnValue(out);
	return true;
}

bool pt_template_type_scope_equals(zval *self, zval *other, bool &out)
{
	if (EXPECTED(Z_TYPE_P(self) == IS_OBJECT && Z_OBJCE_P(self) == pt_ce_template_type_scope && Z_TYPE_P(other) == IS_OBJECT && Z_OBJCE_P(other) == pt_ce_template_type_scope)) {
		return TemplateTypeScope(Z_OBJ_P(self)).equals(Z_OBJ_P(other), out);
	}
	if (UNEXPECTED(Z_TYPE_P(self) != IS_OBJECT)) {
		zend_type_error("phpstan_turbo: expected %s, %s given", pt_ce_template_type_scope != NULL ? ZSTR_VAL(pt_ce_template_type_scope->name) : "PHPStan\\Type\\Generic\\TemplateTypeScope", zend_zval_value_name(self));
		return false;
	}
	/* the PHP twin declared next to the native class in the differential
	 * tests: its equals() */
	return pt_type_call_bool(Z_OBJ_P(self), PT_LC("equals"), 1, other, out);
}

bool pt_template_type_scope_is_anonymous(zval *scope, bool &out)
{
	if (UNEXPECTED(Z_TYPE_P(scope) != IS_OBJECT)) {
		zend_type_error("phpstan_turbo: expected %s, %s given", pt_ce_template_type_scope != NULL ? ZSTR_VAL(pt_ce_template_type_scope->name) : "PHPStan\\Type\\Generic\\TemplateTypeScope", zend_zval_value_name(scope));
		return false;
	}
	if (EXPECTED(Z_OBJCE_P(scope) == pt_ce_template_type_scope)) {
		TemplateTypeScope self(Z_OBJ_P(scope));
		zval *className = self.className();
		if (UNEXPECTED(className == NULL)) return false;
		zval *functionName = self.functionName();
		if (UNEXPECTED(functionName == NULL)) return false;
		out = Z_TYPE_P(className) == IS_NULL && Z_TYPE_P(functionName) == IS_NULL;
		return true;
	}
	/* the PHP twin declared next to the native class in the differential
	 * tests: $scope->equals(<its class>::createWithAnonymousFunction()) */
	zv::Val anonymous = pt_type_call_static_ce(Z_OBJCE_P(scope), PT_LC("createwithanonymousfunction"), 0, NULL);
	if (UNEXPECTED(anonymous.isUndef())) return false;
	return pt_template_type_scope_equals(scope, anonymous.raw(), out);
}

/* }}} */

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

#define PT_TTS_THIS TemplateTypeScope(Z_OBJ_P(ZEND_THIS))
#define PT_TTS_CLASS "PHPStan\\Type\\Generic\\TemplateTypeScope"

void pt_register_template_type_scope()
{

	reg::Class cls("PHPStan\\Type\\Generic\\TemplateTypeScope");
	ptdecl::TemplateTypeScope::declareClass(cls);
	/* the promoted slots in the twin's order: "className" (0), "functionName" (1) */
	ptdecl::TemplateTypeScope::declareProperties(cls);

	cls.method(sigs::createWithAnonymousFunction, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		PT_RETURN_VAL(TemplateTypeScope::createWithAnonymousFunction());
	});

	cls.method(sigs::createWithFunction, [](INTERNAL_FUNCTION_PARAMETERS) {
		zend_string *functionName;
		if (!zp::parse<zp::Str>(execute_data, functionName)) RETURN_THROWS();
		PT_RETURN_VAL(TemplateTypeScope::createWithFunction(functionName));
	});

	cls.method(sigs::createWithMethod, [](INTERNAL_FUNCTION_PARAMETERS) {
		zend_string *className, *functionName;
		if (!zp::parse<zp::Str, zp::Str>(execute_data, className, functionName)) RETURN_THROWS();
		PT_RETURN_VAL(TemplateTypeScope::createWithMethod(className, functionName));
	});

	cls.method(sigs::createWithClass, [](INTERNAL_FUNCTION_PARAMETERS) {
		zend_string *className;
		if (!zp::parse<zp::Str>(execute_data, className)) RETURN_THROWS();
		PT_RETURN_VAL(TemplateTypeScope::createWithClass(className));
	});

	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zend_string *className, *functionName;
		if (!zp::parse<zp::StrOrNull, zp::StrOrNull>(execute_data, className, functionName)) RETURN_THROWS();
		PT_TTS_THIS.construct(className, functionName);
	});

	cls.method(sigs::getClassName, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		PT_RETURN_VAL(PT_TTS_THIS.getClassName());
	});

	cls.method(sigs::getFunctionName, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		PT_RETURN_VAL(PT_TTS_THIS.getFunctionName());
	});

	cls.method(sigs::equals, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *other;
		ZEND_PARSE_PARAMETERS_START(1, 1)
			Z_PARAM_OBJECT_OF_CLASS(other, pt_ce_template_type_scope)
		ZEND_PARSE_PARAMETERS_END();
		bool result;
		if (UNEXPECTED(!PT_TTS_THIS.equals(Z_OBJ_P(other), result))) RETURN_THROWS();
		RETURN_BOOL(result);
	});

	cls.method(sigs::describe, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		PT_RETURN_VAL(PT_TTS_THIS.describe());
	});

	cls.shadow(&pt_ce_template_type_scope);
}

/* }}} */
