/*
 * PHPStanTurbo\GetTemplateTypeType — native implementation of
 * PHPStan\Type\Helper\GetTemplateTypeType.
 *
 * State is the twin's three promoted constructor parameters, declared typed
 * slots in the twin's declaration order, followed by the `private ?Type
 * $result` its LateResolvableTypeTrait declares (the registrar declares
 * that slot). The two traits the twin is composed of come from the shared
 * registrars in TypeTraits.cpp, run after the class's own methods so the
 * class body wins over the traits exactly as in PHP: every Type method the
 * class does not declare itself resolves the type first (getResult(),
 * memoized) and forwards to the resolved type.
 */

#include "TypeTraits.h"
#include "generated/GetTemplateTypeType.h"

namespace slots = ptdecl::GetTemplateTypeType::slot;
namespace sigs = ptdecl::GetTemplateTypeType::sig;

zend_class_entry *pt_ce_get_template_type_type = nullptr;

namespace phpstanturbo {

/* Mirrors PHPStan\Type\Helper\GetTemplateTypeType. State lives in the PHP
 * object's slots. */
class GetTemplateTypeType
{
public:
	explicit GetTemplateTypeType(zend_object *self) : self(self) {}

	/* __construct(private Type $type, private string $ancestorClassName,
	 * private string $templateTypeName); all borrowed */
	void construct(zval *type, zend_string *ancestorClassName, zend_string *templateTypeName)
	{
		zv::ObjRef ref(self);
		ref.propAtWrite(slots::type, zv::Val::copyOf(zv::Ref(type)));
		ref.propAtWrite(slots::ancestorClassName, zv::Val::string(ancestorClassName));
		ref.propAtWrite(slots::templateTypeName, zv::Val::string(templateTypeName));
	}

	/* new self($type, $ancestorClassName, $templateTypeName) — the twin's
	 * `Type $type` parameter checked; UNDEF = pending exception */
	static zv::Val create(zval *type, zend_string *ancestorClassName, zend_string *templateTypeName)
	{
		if (UNEXPECTED(!checkType(type))) return zv::Val();
		zval object;
		if (UNEXPECTED(object_init_ex(&object, pt_ce_get_template_type_type) != SUCCESS)) return zv::Val();
		GetTemplateTypeType(Z_OBJ(object)).construct(type, ancestorClassName, templateTypeName);
		return zv::Val::adopt(object);
	}

	/* the slots (borrowed); NULL with an Error pending when the constructor
	 * never ran */
	[[nodiscard]] zval *type() const { return slot(self, slots::type, IS_OBJECT, "type"); }
	zval *ancestorClassName() const { return slot(self, slots::ancestorClassName, IS_STRING, "ancestorClassName"); }
	zval *templateTypeName() const { return slot(self, slots::templateTypeName, IS_STRING, "templateTypeName"); }

	/* $this->type->getReferencedClasses() */
	zv::Val getReferencedClasses() const
	{
		zval *t = type();
		if (UNEXPECTED(t == NULL)) return zv::Val();
		return callArray(t, PT_LC("getreferencedclasses"), 0, NULL);
	}

	/* $this->type->getReferencedTemplateTypes($positionVariance) */
	zv::Val getReferencedTemplateTypes(zval *positionVariance) const
	{
		zval *t = type();
		if (UNEXPECTED(t == NULL)) return zv::Val();
		return callArray(t, PT_LC("getreferencedtemplatetypes"), 1, positionVariance);
	}

	/* $type instanceof self && $this->type->equals($type->type); false
	 * with an exception pending */
	bool equals(zval *other, bool &out) const
	{
		if (!instanceof_function(Z_OBJCE_P(other), pt_ce_get_template_type_type)) {
			out = false;
			return true;
		}
		zval *t = type();
		if (UNEXPECTED(t == NULL)) return false;
		zval *theirType = slot(Z_OBJ_P(other), slots::type, IS_OBJECT, "type");
		if (UNEXPECTED(theirType == NULL)) return false;
		return pt_type_op_bool(Z_OBJ_P(t), PT_OP_EQUALS, 1, theirType, out);
	}

	/* sprintf('template-type<%s, %s, %s>', $this->type->describe($level), $this->ancestorClassName, $this->templateTypeName) */
	zv::Val describe(zval *level) const
	{
		zval *t = type();
		if (UNEXPECTED(t == NULL)) return zv::Val();
		zv::Val description = pt_type_op(Z_OBJ_P(t), PT_OP_DESCRIBE, 1, level);
		if (UNEXPECTED(description.isUndef())) return zv::Val();
		if (UNEXPECTED(!zv::Ref(description.raw()).isString())) {
			zend_type_error("phpstan_turbo: %s::describe() must return a string", ZSTR_VAL(Z_OBJCE_P(t)->name));
			return zv::Val();
		}
		zval *ancestor = ancestorClassName();
		if (UNEXPECTED(ancestor == NULL)) return zv::Val();
		zval *name = templateTypeName();
		if (UNEXPECTED(name == NULL)) return zv::Val();
		return zv::Val::adoptString(zend_strpprintf(0, "template-type<%s, %s, %s>", ZSTR_VAL(Z_STR_P(description.raw())), ZSTR_VAL(Z_STR_P(ancestor)), ZSTR_VAL(Z_STR_P(name))));
	}

	/* !TypeUtils::containsTemplateType($this->type); false = pending
	 * exception */
	[[nodiscard]] bool isResolvable(bool &out) const
	{
		zval *t = type();
		if (UNEXPECTED(t == NULL)) return false;
		bool contains;
		if (UNEXPECTED(!pt_type_utils_contains_template_type(t, contains))) return false;
		out = !contains;
		return true;
	}

	/* $this->type->getTemplateType($this->ancestorClassName, $this->templateTypeName) */
	zv::Val getResult() const
	{
		zval *t = type();
		if (UNEXPECTED(t == NULL)) return zv::Val();
		zval *ancestor = ancestorClassName();
		if (UNEXPECTED(ancestor == NULL)) return zv::Val();
		zval *name = templateTypeName();
		if (UNEXPECTED(name == NULL)) return zv::Val();
		zv::Args args{ancestor, name};
		return pt_type_call(Z_OBJ_P(t), PT_LC("gettemplatetype"), 2, args);
	}

	/* new self($cb($this->type), ...) when the callback changed the type,
	 * $this otherwise; UNDEF = pending exception */
	zv::Val traverse(zend_fcall_info *fci, zend_fcall_info_cache *fcc) const
	{
		zval *t = type();
		if (UNEXPECTED(t == NULL)) return zv::Val();
		zval newType;
		if (UNEXPECTED(!pt_call_fci(fci, fcc, 1, t, &newType))) return zv::Val();
		return traversed(zv::Val::adopt(newType));
	}

	/* $this for a $right of another class, else traverse() with $right's
	 * type as the callback's second argument */
	zv::Val traverseSimultaneously(zval *right, zend_fcall_info *fci, zend_fcall_info_cache *fcc) const
	{
		if (!instanceof_function(Z_OBJCE_P(right), pt_ce_get_template_type_type)) return thisValue();
		zval *t = type();
		if (UNEXPECTED(t == NULL)) return zv::Val();
		zval *rightType = slot(Z_OBJ_P(right), slots::type, IS_OBJECT, "type");
		if (UNEXPECTED(rightType == NULL)) return zv::Val();
		zv::Args args{t, rightType};
		zval newType;
		if (UNEXPECTED(!pt_call_fci(fci, fcc, 2, args, &newType))) return zv::Val();
		return traversed(zv::Val::adopt(newType));
	}

	/* new GenericTypeNode(new IdentifierTypeNode('template-type'), [$this->type->toPhpDocNode(),
	 * new IdentifierTypeNode($this->ancestorClassName), new ConstTypeNode(new
	 * ConstExprStringNode($this->templateTypeName, ConstExprStringNode::SINGLE_QUOTED))]) */
	zv::Val toPhpDocNode() const
	{
		zval *t = type();
		if (UNEXPECTED(t == NULL)) return zv::Val();
		zv::Val identifier = pt_type_new_identifier_type_node(PT_LC("template-type"));
		if (UNEXPECTED(identifier.isUndef())) return zv::Val();
		zv::Val typeNode = pt_type_call(Z_OBJ_P(t), PT_LC("tophpdocnode"), 0, NULL);
		if (UNEXPECTED(typeNode.isUndef())) return zv::Val();
		zval *ancestor = ancestorClassName();
		if (UNEXPECTED(ancestor == NULL)) return zv::Val();
		zv::Val ancestorNode = pt_type_new_identifier_type_node(ZSTR_VAL(Z_STR_P(ancestor)), ZSTR_LEN(Z_STR_P(ancestor)));
		if (UNEXPECTED(ancestorNode.isUndef())) return zv::Val();
		zval *name = templateTypeName();
		if (UNEXPECTED(name == NULL)) return zv::Val();
		zend_class_entry *constExprStringNodeCe = pt_class(PT_CLASS_CONST_EXPR_STRING_NODE);
		if (UNEXPECTED(constExprStringNodeCe == NULL)) return zv::Val();
		zval *singleQuoted = classConstant(constExprStringNodeCe, PT_LC("SINGLE_QUOTED"));
		if (UNEXPECTED(singleQuoted == NULL)) return zv::Val();
		zv::Args constExprArgs{name, singleQuoted};
		zv::Val constExpr = pt_type_new(PT_CLASS_CONST_EXPR_STRING_NODE, 2, constExprArgs);
		if (UNEXPECTED(constExpr.isUndef())) return zv::Val();
		zv::Val constTypeNode = pt_type_new(PT_CLASS_CONST_TYPE_NODE, 1, constExpr.raw());
		if (UNEXPECTED(constTypeNode.isUndef())) return zv::Val();
		zv::Arr genericTypes = zv::Arr::create(3);
		genericTypes.push(std::move(typeNode));
		genericTypes.push(std::move(ancestorNode));
		genericTypes.push(std::move(constTypeNode));
		zv::Args args{identifier.raw(), genericTypes.raw()};
		return pt_type_new(PT_CLASS_GENERIC_TYPE_NODE, 2, args);
	}

private:
	zend_object *self;

	zv::Val thisValue() const { return pt_this_value(self); }

	/* the tail of traverse()/traverseSimultaneously(): `$this->type === $type`
	 * hands $this back, anything else a new instance over the callback's
	 * result */
	zv::Val traversed(zv::Val newType) const
	{
		zval *t = type();
		if (UNEXPECTED(t == NULL)) return zv::Val();
		if (zv::Ref(newType.raw()).isObject() && Z_OBJ_P(newType.raw()) == Z_OBJ_P(t)) return thisValue();
		zval *ancestor = ancestorClassName();
		if (UNEXPECTED(ancestor == NULL)) return zv::Val();
		zval *name = templateTypeName();
		if (UNEXPECTED(name == NULL)) return zv::Val();
		return create(newType.raw(), Z_STR_P(ancestor), Z_STR_P(name));
	}

	static zval *slot(zend_object *object, uint32_t index, int expectedType, const char *name)
	{
		zval *p = OBJ_PROP_NUM(object, index);
		if (UNEXPECTED(Z_TYPE_P(p) != expectedType)) {
			zend_throw_error(NULL, "Typed property %s::$%s must not be accessed before initialization", ZSTR_VAL(pt_ce_get_template_type_type->name), name);
			return NULL;
		}
		return p;
	}

	/* the twin's `Type $type` parameter check; false with a TypeError
	 * pending */
	static bool checkType(zval *type)
	{
		bool isType;
		if (UNEXPECTED(!pt_type_instanceof(type, PT_CLASS_TYPE, isType))) return false;
		if (EXPECTED(isType)) return true;
		zend_argument_type_error(1, "must be of type %s, %s given", ptcls::type, zend_zval_value_name(type));
		return false;
	}

	/* $type->method(...$args) requiring an array result; UNDEF = pending
	 * exception */
	static zv::Val callArray(zval *type, const char *lcname, size_t len, uint32_t argc, zval *argv)
	{
		zv::Val result = pt_type_call(Z_OBJ_P(type), lcname, len, argc, argv);
		if (UNEXPECTED(result.isUndef())) return zv::Val();
		if (UNEXPECTED(!zv::Ref(result.raw()).isArray())) {
			zend_type_error("phpstan_turbo: %s::%s() must return an array", ZSTR_VAL(Z_OBJCE_P(type)->name), lcname);
			return zv::Val();
		}
		return result;
	}

	/* a class constant's value (borrowed, evaluated); NULL with an Error
	 * pending when missing */
	[[nodiscard]] static zval *classConstant(zend_class_entry *ce, const char *name, size_t len)
	{
		zend_class_constant *constant = (zend_class_constant *) zend_hash_str_find_ptr(&ce->constants_table, name, len);
		if (UNEXPECTED(constant == NULL)) {
			zend_throw_error(NULL, "phpstan_turbo: %s::%s not found", ZSTR_VAL(ce->name), name);
			return NULL;
		}
		if (UNEXPECTED(Z_TYPE(constant->value) == IS_CONSTANT_AST && zval_update_constant_ex(&constant->value, ce) != SUCCESS)) return NULL;
		return &constant->value;
	}
};

} // namespace phpstanturbo

using phpstanturbo::GetTemplateTypeType;

zv::Val pt_get_template_type_type_new(zval *type, zend_string *ancestorClassName, zend_string *templateTypeName)
{
	return GetTemplateTypeType::create(type, ancestorClassName, templateTypeName);
}

/* {{{ engine ABI glue: parameter parsing + registration */

#define PT_THIS GetTemplateTypeType(Z_OBJ_P(ZEND_THIS))

PT_MINIT_REGISTRATION(pt_register_get_template_type_type)
{
	reg::Class cls("PHPStan\\Type\\Helper\\GetTemplateTypeType");
	ptdecl::GetTemplateTypeType::declareClass(cls);
	/* the slots must stay in this order (PT_GTTT_PROP_*); the trait
	 * registrar declares $result after them */
	ptdecl::GetTemplateTypeType::declareProperties(cls);

	cls.method<&GetTemplateTypeType::construct, zp::TypeObj, zp::Str, zp::Str>(sigs::__construct);

	cls.method<&GetTemplateTypeType::getReferencedClasses>(sigs::getReferencedClasses);
	cls.op<PT_OP_GET_REFERENCED_CLASSES, &GetTemplateTypeType::getReferencedClasses>();

	cls.method<&GetTemplateTypeType::getReferencedTemplateTypes, zp::Obj>(sigs::getReferencedTemplateTypes);

	cls.method<&GetTemplateTypeType::equals, zp::TypeObj>(sigs::equals);

	cls.method<&GetTemplateTypeType::describe, zp::Obj>(sigs::describe);

	cls.method<&GetTemplateTypeType::isResolvable>(sigs::isResolvable);

	cls.method<&GetTemplateTypeType::getResult>(sigs::getResult);

	cls.method(sigs::traverse, [](INTERNAL_FUNCTION_PARAMETERS) {
		zend_fcall_info fci;
		zend_fcall_info_cache fcc;
		ZEND_PARSE_PARAMETERS_START(1, 1)
			Z_PARAM_FUNC(fci, fcc)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(PT_THIS.traverse(&fci, &fcc));
	});

	cls.method(sigs::traverseSimultaneously, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *right;
		zend_fcall_info fci;
		zend_fcall_info_cache fcc;
		ZEND_PARSE_PARAMETERS_START(2, 2)
			Z_PARAM_OBJECT(right)
			Z_PARAM_FUNC(fci, fcc)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(PT_THIS.traverseSimultaneously(right, &fci, &fcc));
	});

	cls.method<&GetTemplateTypeType::toPhpDocNode>(sigs::toPhpDocNode);

	/* the traits, in the twin's `use` order; the class body above wins over
	 * every name it declares */
	ptdecl::GetTemplateTypeType::registerTraits(cls);

	cls.shadow(&pt_ce_get_template_type_type);
}

/* }}} */
