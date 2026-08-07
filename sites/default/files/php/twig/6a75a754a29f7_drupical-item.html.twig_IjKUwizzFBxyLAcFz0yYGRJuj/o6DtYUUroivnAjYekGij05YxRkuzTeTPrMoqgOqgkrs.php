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

/* @drupical/drupical-item.html.twig */
class __TwigTemplate_f56843943a22d14ade3cce1921735ada extends Template
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
        // line 11
        yield "<tr class=\"event ";
        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar((((($tmp = ($context["featured"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) ? ("event-featured") : ("")));
        yield "\">
  <td class=\"event-info\">
    <a href=\"";
        // line 13
        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, ($context["event"] ?? null), "url", [], "any", false, false, true, 13), "html", null, true);
        yield "\" class=\"event-title\" target=\"_blank\" rel=\"noopener\">";
        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, ($context["event"] ?? null), "title", [], "any", false, false, true, 13), "html", null, true);
        yield "</a>
    ";
        // line 14
        if ((( !(($tmp = ($context["featured"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp) && (($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["event"] ?? null), "type", [], "any", false, false, true, 14)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) || (($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["event"] ?? null), "location", [], "any", false, false, true, 14)) && $tmp instanceof Markup ? (string) $tmp : $tmp))) {
            // line 15
            yield "      <div class=\"event-details\">
        ";
            // line 16
            if (( !(($tmp = ($context["featured"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp) && (($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["event"] ?? null), "type", [], "any", false, false, true, 16)) && $tmp instanceof Markup ? (string) $tmp : $tmp))) {
                // line 17
                yield "          ";
                yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, ($context["event"] ?? null), "type", [], "any", false, false, true, 17), "html", null, true);
                yield "
        ";
            }
            // line 19
            yield "        ";
            if ((( !(($tmp = ($context["featured"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp) && (($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["event"] ?? null), "type", [], "any", false, false, true, 19)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) && (($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["event"] ?? null), "location", [], "any", false, false, true, 19)) && $tmp instanceof Markup ? (string) $tmp : $tmp))) {
                // line 20
                yield "          <span class=\"event-separator\"> · </span>
        ";
            }
            // line 22
            yield "        ";
            if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["event"] ?? null), "location", [], "any", false, false, true, 22)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                // line 23
                yield "          ";
                yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, ($context["event"] ?? null), "location", [], "any", false, false, true, 23), "html", null, true);
                yield "
        ";
            }
            // line 25
            yield "      </div>
    ";
        }
        // line 27
        yield "  </td>
  <td class=\"event-meta\">
    ";
        // line 29
        $context["start_date"] = $this->env->getFilter('format_date')->getCallable()(CoreExtension::getAttribute($this->env, $this->source, ($context["event"] ?? null), "dateStartTimestamp", [], "any", false, false, true, 29), "custom", "Y-m-d");
        // line 30
        yield "    ";
        $context["end_date"] = $this->env->getFilter('format_date')->getCallable()(CoreExtension::getAttribute($this->env, $this->source, ($context["event"] ?? null), "dateEndTimestamp", [], "any", false, false, true, 30), "custom", "Y-m-d");
        // line 31
        yield "    ";
        if ((($context["start_date"] ?? null) != ($context["end_date"] ?? null))) {
            // line 32
            yield "      <span class=\"event-date\">";
            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, $this->env->getFilter('format_date')->getCallable()(CoreExtension::getAttribute($this->env, $this->source, ($context["event"] ?? null), "dateStartTimestamp", [], "any", false, false, true, 32), "custom", "M j"), "html", null, true);
            yield " - ";
            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, $this->env->getFilter('format_date')->getCallable()(CoreExtension::getAttribute($this->env, $this->source, ($context["event"] ?? null), "dateEndTimestamp", [], "any", false, false, true, 32), "custom", "M j, Y"), "html", null, true);
            yield "</span>
    ";
        } else {
            // line 34
            yield "      <span class=\"event-date\">";
            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, $this->env->getFilter('format_date')->getCallable()(CoreExtension::getAttribute($this->env, $this->source, ($context["event"] ?? null), "dateStartTimestamp", [], "any", false, false, true, 34), "custom", "M j, Y"), "html", null, true);
            yield "</span>
      <span class=\"event-time\">";
            // line 35
            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, $this->env->getFilter('format_date')->getCallable()(CoreExtension::getAttribute($this->env, $this->source, ($context["event"] ?? null), "dateStartTimestamp", [], "any", false, false, true, 35), "custom", "g:i a T"), "html", null, true);
            yield "</span>
    ";
        }
        // line 37
        yield "  </td>
</tr>
";
        $this->env->getExtension('\Drupal\Core\Template\TwigExtension')
            ->checkDeprecations($context, ["featured", "event"]);        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return "@drupical/drupical-item.html.twig";
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
        return array (  119 => 37,  114 => 35,  109 => 34,  101 => 32,  98 => 31,  95 => 30,  93 => 29,  89 => 27,  85 => 25,  79 => 23,  76 => 22,  72 => 20,  69 => 19,  63 => 17,  61 => 16,  58 => 15,  56 => 14,  50 => 13,  44 => 11,);
    }

    public function getSourceContext(): Source
    {
        return new Source("", "@drupical/drupical-item.html.twig", "/var/www/html/modules/contrib/drupical/templates/drupical-item.html.twig");
    }
    
    public function ensureSecurityChecked(): void
    {
        if ($this->sandbox->isSandboxed($this->source)) {
            $this->checkSecurity();
        }
    }
    
    public function checkSecurity()
    {
        static $tags = ["if" => 14, "set" => 29];
        static $filters = ["escape" => 13, "format_date" => 29];
        static $functions = [];
        static $tests = [];

        try {
            $this->sandbox->checkSecurity(
                [0 => "if", 1 => "set"],
                [0 => "escape", 1 => "format_date"],
                [],
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
