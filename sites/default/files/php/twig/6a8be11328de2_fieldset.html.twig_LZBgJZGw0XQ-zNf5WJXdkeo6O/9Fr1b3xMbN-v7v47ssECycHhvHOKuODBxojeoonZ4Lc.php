<?php

use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Extension\CoreExtension;
use Twig\Extension\SandboxExtension;
use Twig\Markup;
use Twig\Sandbox\SecurityError;
use Twig\Sandbox\SecurityNotAllowedTagError;
use Twig\Sandbox\SecurityNotAllowedFilterError;
use Twig\Sandbox\SecurityNotAllowedFunctionError;
use Twig\Sandbox\SecurityNotAllowedTestError;
use Twig\Source;
use Twig\Template;
use Twig\TemplateWrapper;

/* themes/contrib/gin/templates/form/fieldset.html.twig */
class __TwigTemplate_7f34851dde93cd8b3f8233923a85807a extends Template
{
    private Source $source;
    /**
     * @var array<string, Template>
     */
    private array $macros = [];

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->parent = false;

        $this->blocks = [
        ];
        $this->sandbox = $this->extensions[SandboxExtension::class];
    }

    protected function doDisplay(array $context, array $blocks = []): iterable
    {
        $macros = $this->macros;
        // line 29
        $context["show_description_toggle"] = ((($tmp = ($context["description_toggle"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp) && (($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["description"] ?? null), "content", [], "any", false, false, true, 29)) && $tmp instanceof Markup ? (string) $tmp : $tmp));
        // line 31
        $context["classes"] = ["fieldset", (((($tmp = CoreExtension::getAttribute($this->env, $this->source,         // line 33
($context["attributes"] ?? null), "hasClass", ["fieldgroup"], "method", false, false, true, 33)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) ? ("fieldset--group") : ("")), "js-form-item", "form-item", "js-form-wrapper", "form-wrapper", (((($tmp =         // line 38
($context["show_description_toggle"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) ? ("help-icon__description-container") : (""))];
        // line 42
        $context["wrapper_classes"] = ["fieldset__wrapper", (((($tmp = CoreExtension::getAttribute($this->env, $this->source,         // line 44
($context["attributes"] ?? null), "hasClass", ["fieldgroup"], "method", false, false, true, 44)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) ? ("fieldset__wrapper--group") : (""))];
        // line 48
        $context["legend_span_classes"] = ["fieldset__label", (((($tmp = CoreExtension::getAttribute($this->env, $this->source,         // line 50
($context["attributes"] ?? null), "hasClass", ["fieldgroup"], "method", false, false, true, 50)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) ? ("fieldset__label--group") : ("")), (((($tmp =         // line 51
($context["required"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) ? ("js-form-required") : ("")), (((($tmp =         // line 52
($context["required"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) ? ("form-required") : (""))];
        // line 56
        $context["legend_classes"] = ["fieldset__legend", ((((($tmp = CoreExtension::getAttribute($this->env, $this->source,         // line 58
($context["attributes"] ?? null), "hasClass", ["fieldgroup"], "method", false, false, true, 58)) && $tmp instanceof Markup ? (string) $tmp : $tmp) &&  !(($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["attributes"] ?? null), "hasClass", ["form-composite"], "method", false, false, true, 58)) && $tmp instanceof Markup ? (string) $tmp : $tmp))) ? ("fieldset__legend--group") : ("")), (((($tmp = CoreExtension::getAttribute($this->env, $this->source,         // line 59
($context["attributes"] ?? null), "hasClass", ["form-composite"], "method", false, false, true, 59)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) ? ("fieldset__legend--composite") : ("")), (((        // line 60
($context["title_display"] ?? null) == "invisible")) ? ("fieldset__legend--invisible") : ("fieldset__legend--visible"))];
        // line 64
        $context["description_classes"] = ["fieldset__description", (((        // line 66
($context["show_description_toggle"] ?? null) == "invisible")) ? ("visually-hidden") : (""))];
        // line 69
        yield "<fieldset";
        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, ($context["attributes"] ?? null), "addClass", [($context["classes"] ?? null)], "method", false, false, true, 69), "html", null, true);
        yield ">
  ";
        // line 70
        if ((($tmp = ($context["show_description_toggle"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 71
            yield "    <div class=\"help-icon\">
  ";
        }
        // line 73
        yield "  ";
        // line 74
        yield "  ";
        if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["legend"] ?? null), "title", [], "any", false, false, true, 74)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 75
            yield "    <legend";
            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source, ($context["legend"] ?? null), "attributes", [], "any", false, false, true, 75), "addClass", [($context["legend_classes"] ?? null)], "method", false, false, true, 75), "html", null, true);
            yield ">
      <span";
            // line 76
            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source, ($context["legend_span"] ?? null), "attributes", [], "any", false, false, true, 76), "addClass", [($context["legend_span_classes"] ?? null)], "method", false, false, true, 76), "html", null, true);
            yield ">";
            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, ($context["legend"] ?? null), "title", [], "any", false, false, true, 76), "html", null, true);
            yield "</span>
    </legend>
  ";
        }
        // line 79
        yield "  ";
        if ((($tmp = ($context["show_description_toggle"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 80
            yield "      ";
            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, $this->extensions['Drupal\Core\Template\TwigExtension']->attachLibrary("gin/gin_description_toggle"), "html", null, true);
            yield "
      <button class=\"help-icon__description-toggle\"></button>
    </div>
  ";
        }
        // line 84
        yield "  <div";
        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, ($context["content_attributes"] ?? null), "addClass", [($context["wrapper_classes"] ?? null)], "method", false, false, true, 84), "html", null, true);
        yield ">
    ";
        // line 85
        if (((($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["description"] ?? null), "content", [], "any", false, false, true, 85)) && $tmp instanceof Markup ? (string) $tmp : $tmp) && ((($context["description_display"] ?? null) == "before") || (($context["description_display_toggle"] ?? null) == "before")))) {
            // line 86
            yield "      <div";
            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source, ($context["description"] ?? null), "attributes", [], "any", false, false, true, 86), "addClass", [($context["description_classes"] ?? null)], "method", false, false, true, 86), "html", null, true);
            yield ">";
            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, ($context["description"] ?? null), "content", [], "any", false, false, true, 86), "html", null, true);
            yield "</div>
    ";
        }
        // line 88
        yield "    ";
        if ((($tmp = ($context["inline_items"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 89
            yield "    <div class=\"container-inline\">
      ";
        }
        // line 91
        yield "
      ";
        // line 92
        if ((($tmp = ($context["prefix"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 93
            yield "        <span class=\"fieldset__prefix\">";
            if ((isset($context["canvas_is_preview"]) && $context["canvas_is_preview"]) && \array_key_exists("canvas_uuid", $context)) {
                if (\array_key_exists("canvas_slot_ids", $context) && \in_array("prefix", $context["canvas_slot_ids"], TRUE)) {
                    yield \sprintf('<!-- canvas-slot-%s-%s/%s -->', "start", $context["canvas_uuid"], "prefix");
                } else {
                    yield \sprintf('<!-- canvas-prop-%s-%s/%s -->', "start", $context["canvas_uuid"], "prefix");
                }
            }
            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["prefix"] ?? null), "html", null, true);
            if ((isset($context["canvas_is_preview"]) && $context["canvas_is_preview"]) && \array_key_exists("canvas_uuid", $context)) {
                if (\array_key_exists("canvas_slot_ids", $context) && \in_array("prefix", $context["canvas_slot_ids"], TRUE)) {
                    yield \sprintf('<!-- canvas-slot-%s-%s/%s -->', "end", $context["canvas_uuid"], "prefix");
                } else {
                    yield \sprintf('<!-- canvas-prop-%s-%s/%s -->', "end", $context["canvas_uuid"], "prefix");
                }
            }
            yield "</span>
      ";
        }
        // line 95
        yield "      ";
        if ((isset($context["canvas_is_preview"]) && $context["canvas_is_preview"]) && \array_key_exists("canvas_uuid", $context)) {
            if (\array_key_exists("canvas_slot_ids", $context) && \in_array("children", $context["canvas_slot_ids"], TRUE)) {
                yield \sprintf('<!-- canvas-slot-%s-%s/%s -->', "start", $context["canvas_uuid"], "children");
            } else {
                yield \sprintf('<!-- canvas-prop-%s-%s/%s -->', "start", $context["canvas_uuid"], "children");
            }
        }
        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["children"] ?? null), "html", null, true);
        if ((isset($context["canvas_is_preview"]) && $context["canvas_is_preview"]) && \array_key_exists("canvas_uuid", $context)) {
            if (\array_key_exists("canvas_slot_ids", $context) && \in_array("children", $context["canvas_slot_ids"], TRUE)) {
                yield \sprintf('<!-- canvas-slot-%s-%s/%s -->', "end", $context["canvas_uuid"], "children");
            } else {
                yield \sprintf('<!-- canvas-prop-%s-%s/%s -->', "end", $context["canvas_uuid"], "children");
            }
        }
        yield "
      ";
        // line 96
        if ((($tmp = ($context["suffix"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 97
            yield "        <span class=\"fieldset__suffix\">";
            if ((isset($context["canvas_is_preview"]) && $context["canvas_is_preview"]) && \array_key_exists("canvas_uuid", $context)) {
                if (\array_key_exists("canvas_slot_ids", $context) && \in_array("suffix", $context["canvas_slot_ids"], TRUE)) {
                    yield \sprintf('<!-- canvas-slot-%s-%s/%s -->', "start", $context["canvas_uuid"], "suffix");
                } else {
                    yield \sprintf('<!-- canvas-prop-%s-%s/%s -->', "start", $context["canvas_uuid"], "suffix");
                }
            }
            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["suffix"] ?? null), "html", null, true);
            if ((isset($context["canvas_is_preview"]) && $context["canvas_is_preview"]) && \array_key_exists("canvas_uuid", $context)) {
                if (\array_key_exists("canvas_slot_ids", $context) && \in_array("suffix", $context["canvas_slot_ids"], TRUE)) {
                    yield \sprintf('<!-- canvas-slot-%s-%s/%s -->', "end", $context["canvas_uuid"], "suffix");
                } else {
                    yield \sprintf('<!-- canvas-prop-%s-%s/%s -->', "end", $context["canvas_uuid"], "suffix");
                }
            }
            yield "</span>
      ";
        }
        // line 99
        yield "      ";
        if ((($tmp = ($context["errors"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 100
            yield "        <div class=\"fieldset__error-message\">
          ";
            // line 101
            if ((isset($context["canvas_is_preview"]) && $context["canvas_is_preview"]) && \array_key_exists("canvas_uuid", $context)) {
                if (\array_key_exists("canvas_slot_ids", $context) && \in_array("errors", $context["canvas_slot_ids"], TRUE)) {
                    yield \sprintf('<!-- canvas-slot-%s-%s/%s -->', "start", $context["canvas_uuid"], "errors");
                } else {
                    yield \sprintf('<!-- canvas-prop-%s-%s/%s -->', "start", $context["canvas_uuid"], "errors");
                }
            }
            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["errors"] ?? null), "html", null, true);
            if ((isset($context["canvas_is_preview"]) && $context["canvas_is_preview"]) && \array_key_exists("canvas_uuid", $context)) {
                if (\array_key_exists("canvas_slot_ids", $context) && \in_array("errors", $context["canvas_slot_ids"], TRUE)) {
                    yield \sprintf('<!-- canvas-slot-%s-%s/%s -->', "end", $context["canvas_uuid"], "errors");
                } else {
                    yield \sprintf('<!-- canvas-prop-%s-%s/%s -->', "end", $context["canvas_uuid"], "errors");
                }
            }
            yield "
        </div>
      ";
        }
        // line 104
        yield "      ";
        if ((((($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["description"] ?? null), "content", [], "any", false, false, true, 104)) && $tmp instanceof Markup ? (string) $tmp : $tmp) && CoreExtension::inFilter(($context["description_display"] ?? null), ["after", "invisible"])) && (($context["description_display_toggle"] ?? null) != "before"))) {
            // line 105
            yield "        <div";
            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source, ($context["description"] ?? null), "attributes", [], "any", false, false, true, 105), "addClass", [($context["description_classes"] ?? null)], "method", false, false, true, 105), "html", null, true);
            yield ">";
            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, ($context["description"] ?? null), "content", [], "any", false, false, true, 105), "html", null, true);
            yield "</div>
      ";
        }
        // line 107
        yield "
      ";
        // line 108
        if ((($tmp = ($context["inline_items"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 109
            yield "    </div>
    ";
        }
        // line 111
        yield "  </div>
</fieldset>
";
        $this->env->getExtension('\Drupal\Core\Template\TwigExtension')
            ->checkDeprecations($context, ["description_toggle", "description", "attributes", "required", "title_display", "legend", "legend_span", "content_attributes", "description_display", "description_display_toggle", "inline_items", "prefix", "children", "suffix", "errors"]);        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return "themes/contrib/gin/templates/form/fieldset.html.twig";
    }

    /**
     * @codeCoverageIgnore
     */
    public function isTraitable(): bool
    {
        return false;
    }

    /**
     * @codeCoverageIgnore
     */
    public function getDebugInfo(): array
    {
        return array (  240 => 111,  236 => 109,  234 => 108,  231 => 107,  223 => 105,  220 => 104,  200 => 101,  197 => 100,  194 => 99,  174 => 97,  172 => 96,  153 => 95,  133 => 93,  131 => 92,  128 => 91,  124 => 89,  121 => 88,  113 => 86,  111 => 85,  106 => 84,  98 => 80,  95 => 79,  87 => 76,  82 => 75,  79 => 74,  77 => 73,  73 => 71,  71 => 70,  66 => 69,  64 => 66,  63 => 64,  61 => 60,  60 => 59,  59 => 58,  58 => 56,  56 => 52,  55 => 51,  54 => 50,  53 => 48,  51 => 44,  50 => 42,  48 => 38,  47 => 33,  46 => 31,  44 => 29,);
    }

    public function getSourceContext(): Source
    {
        return new Source("", "themes/contrib/gin/templates/form/fieldset.html.twig", "/home/itsiugor/public_html/themes/contrib/gin/templates/form/fieldset.html.twig");
    }
    
    public function ensureSecurityChecked(): void
    {
        if ($this->sandbox->isSandboxed($this->source)) {
            $this->checkSecurity();
        }
    }
    
    public function checkSecurity()
    {
        static $tags = ["set" => 29, "if" => 70];
        static $filters = ["escape" => 69];
        static $functions = ["attach_library" => 80];
        static $tests = [];

        try {
            $this->sandbox->checkSecurity(
                [0 => "set", 1 => "if"],
                [0 => "escape"],
                [0 => "attach_library"],
                [],
                $this->source
            );
        } catch (SecurityError $e) {
            $e->setSourceContext($this->source);

            if ($e instanceof SecurityNotAllowedTagError && isset($tags[$e->getTagName()])) {
                $e->setTemplateLine($tags[$e->getTagName()]);
            } elseif ($e instanceof SecurityNotAllowedFilterError && isset($filters[$e->getFilterName()])) {
                $e->setTemplateLine($filters[$e->getFilterName()]);
            } elseif ($e instanceof SecurityNotAllowedFunctionError && isset($functions[$e->getFunctionName()])) {
                $e->setTemplateLine($functions[$e->getFunctionName()]);
            } elseif ($e instanceof SecurityNotAllowedTestError && isset($tests[$e->getTestName()])) {
                $e->setTemplateLine($tests[$e->getTestName()]);
            }

            throw $e;
        }

    }
}
