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

/* themes/contrib/convene_theme/templates/navigation/menu.html.twig */
class __TwigTemplate_75e4b62b5d5e6281dd4dd878106ea62b extends Template
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
        // line 21
        $macros["menus"] = $this->macros["menus"] = $this;
        // line 22
        yield "
";
        // line 27
        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar($macros["menus"]->getTemplateForMacro("macro_menu_links", $context, 27, $this->getSourceContext())->macro_menu_links(...[($context["items"] ?? null), ($context["attributes"] ?? null), 0, ((array_key_exists("auth_route", $context)) ? (Twig\Extension\CoreExtension::default(($context["auth_route"] ?? null), "itsiug_registration.access")) : ("itsiug_registration.access")), ((array_key_exists("auth_label", $context)) ? (Twig\Extension\CoreExtension::default(($context["auth_label"] ?? null), "Login")) : ("Login"))]));
        yield "

";
        $this->env->getExtension('\Drupal\Core\Template\TwigExtension')
            ->checkDeprecations($context, ["_self", "items", "attributes", "auth_route", "auth_label", "menu_level"]);        yield from [];
    }

    // line 29
    public function macro_menu_links($items = null, $attributes = null, $menu_level = null, $auth_route = null, $auth_label = null, ...$varargs): string|Markup
    {
        $macros = $this->macros;
        $context = [
            "items" => $items,
            "attributes" => $attributes,
            "menu_level" => $menu_level,
            "auth_route" => $auth_route,
            "auth_label" => $auth_label,
            "varargs" => $varargs,
        ] + $this->env->getGlobals();

        $blocks = [];

        return ('' === $tmp = implode('', iterator_to_array((function () use (&$context, $macros, $blocks) {
            // line 30
            yield "  ";
            $macros["menus"] = $this;
            // line 31
            yield "  ";
            if ((($tmp = ($context["items"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                // line 32
                yield "    ";
                if ((($context["menu_level"] ?? null) == 0)) {
                    // line 33
                    yield "      <ul";
                    yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["attributes"] ?? null), "html", null, true);
                    yield ">
    ";
                } else {
                    // line 35
                    yield "      <ul>
    ";
                }
                // line 37
                yield "    ";
                $context['_parent'] = $context;
                $context['_seq'] = CoreExtension::ensureTraversable(($context["items"] ?? null));
                foreach ($context['_seq'] as $context["_key"] => $context["item"]) {
                    // line 38
                    yield "      ";
                    $context["is_register_cta"] = (Twig\Extension\CoreExtension::lower($this->env->getCharset(), CoreExtension::getAttribute($this->env, $this->source, $context["item"], "title", [], "any", false, false, true, 38)) == "register");
                    // line 39
                    yield "      ";
                    // line 40
                    $context["classes"] = ["menu-item", (((($tmp = CoreExtension::getAttribute($this->env, $this->source,                     // line 42
$context["item"], "is_expanded", [], "any", false, false, true, 42)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) ? ("menu-item--expanded") : ("")), (((($tmp = CoreExtension::getAttribute($this->env, $this->source,                     // line 43
$context["item"], "is_collapsed", [], "any", false, false, true, 43)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) ? ("menu-item--collapsed") : ("")), (((($tmp = CoreExtension::getAttribute($this->env, $this->source,                     // line 44
$context["item"], "in_active_trail", [], "any", false, false, true, 44)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) ? ("menu-item--active-trail") : ("")), (((($tmp = CoreExtension::getAttribute($this->env, $this->source,                     // line 45
$context["item"], "below", [], "any", false, false, true, 45)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) ? ("has-submenu") : (""))];
                    // line 48
                    yield "      ";
                    $context["li_attributes"] = CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source, $context["item"], "attributes", [], "any", false, false, true, 48), "addClass", [($context["classes"] ?? null)], "method", false, false, true, 48);
                    // line 49
                    yield "      ";
                    if ((($tmp = ($context["is_register_cta"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                        // line 50
                        yield "        ";
                        $context["li_attributes"] = CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source, ($context["li_attributes"] ?? null), "addClass", ["menu-item--auth-actions"], "method", false, false, true, 50), "setAttribute", ["style", "display:flex;align-items:center;gap:12px;"], "method", false, false, true, 50);
                        // line 51
                        yield "      ";
                    }
                    // line 52
                    yield "      <li";
                    yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["li_attributes"] ?? null), "html", null, true);
                    yield ">
        ";
                    // line 53
                    yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, $this->extensions['Drupal\Core\Template\TwigExtension']->getLink(CoreExtension::getAttribute($this->env, $this->source, $context["item"], "title", [], "any", false, false, true, 53), CoreExtension::getAttribute($this->env, $this->source, $context["item"], "url", [], "any", false, false, true, 53)), "html", null, true);
                    yield "
        ";
                    // line 54
                    if ((($tmp = ($context["is_register_cta"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                        // line 55
                        yield "          <a href=\"";
                        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar($this->extensions['Drupal\Core\Template\TwigExtension']->getPath(($context["auth_route"] ?? null)));
                        yield "\">";
                        if ((isset($context["canvas_is_preview"]) && $context["canvas_is_preview"]) && \array_key_exists("canvas_uuid", $context)) {
                            if (\array_key_exists("canvas_slot_ids", $context) && \in_array("auth_label", $context["canvas_slot_ids"], TRUE)) {
                                yield \sprintf('<!-- canvas-slot-%s-%s/%s -->', "start", $context["canvas_uuid"], "auth_label");
                            } else {
                                yield \sprintf('<!-- canvas-prop-%s-%s/%s -->', "start", $context["canvas_uuid"], "auth_label");
                            }
                        }
                        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["auth_label"] ?? null), "html", null, true);
                        if ((isset($context["canvas_is_preview"]) && $context["canvas_is_preview"]) && \array_key_exists("canvas_uuid", $context)) {
                            if (\array_key_exists("canvas_slot_ids", $context) && \in_array("auth_label", $context["canvas_slot_ids"], TRUE)) {
                                yield \sprintf('<!-- canvas-slot-%s-%s/%s -->', "end", $context["canvas_uuid"], "auth_label");
                            } else {
                                yield \sprintf('<!-- canvas-prop-%s-%s/%s -->', "end", $context["canvas_uuid"], "auth_label");
                            }
                        }
                        yield "</a>
        ";
                    }
                    // line 57
                    yield "        ";
                    if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, $context["item"], "below", [], "any", false, false, true, 57)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                        // line 58
                        yield "          ";
                        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar($macros["menus"]->getTemplateForMacro("macro_menu_links", $context, 58, $this->getSourceContext())->macro_menu_links(...[CoreExtension::getAttribute($this->env, $this->source, $context["item"], "below", [], "any", false, false, true, 58), ($context["attributes"] ?? null), (($context["menu_level"] ?? null) + 1), ($context["auth_route"] ?? null), ($context["auth_label"] ?? null)]));
                        yield "
        ";
                    }
                    // line 60
                    yield "      </li>
    ";
                }
                $_parent = $context['_parent'];
                unset($context['_seq'], $context['_key'], $context['item'], $context['_parent']);
                $context = array_intersect_key($context, $_parent);
                $context += $_parent;
                // line 62
                yield "    </ul>
  ";
            }
            yield from [];
        })(), false))) ? '' : new Markup($tmp, $this->env->getCharset());
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return "themes/contrib/convene_theme/templates/navigation/menu.html.twig";
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
        return array (  171 => 62,  163 => 60,  157 => 58,  154 => 57,  132 => 55,  130 => 54,  126 => 53,  121 => 52,  118 => 51,  115 => 50,  112 => 49,  109 => 48,  107 => 45,  106 => 44,  105 => 43,  104 => 42,  103 => 40,  101 => 39,  98 => 38,  93 => 37,  89 => 35,  83 => 33,  80 => 32,  77 => 31,  74 => 30,  58 => 29,  49 => 27,  46 => 22,  44 => 21,);
    }

    public function getSourceContext(): Source
    {
        return new Source("", "themes/contrib/convene_theme/templates/navigation/menu.html.twig", "/home/itsiugor/public_html/themes/contrib/convene_theme/templates/navigation/menu.html.twig");
    }
    
    public function ensureSecurityChecked(): void
    {
        if ($this->sandbox->isSandboxed($this->source)) {
            $this->checkSecurity();
        }
    }
    
    public function checkSecurity()
    {
        static $tags = ["import" => 21, "macro" => 29, "if" => 31, "for" => 37, "set" => 38];
        static $filters = ["default" => 27, "escape" => 33, "lower" => 38];
        static $functions = ["link" => 53, "path" => 55];
        static $tests = [];

        try {
            $this->sandbox->checkSecurity(
                [0 => "import", 1 => "macro", 2 => "if", 3 => "for", 4 => "set"],
                [0 => "default", 1 => "escape", 2 => "lower"],
                [0 => "link", 1 => "path"],
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
