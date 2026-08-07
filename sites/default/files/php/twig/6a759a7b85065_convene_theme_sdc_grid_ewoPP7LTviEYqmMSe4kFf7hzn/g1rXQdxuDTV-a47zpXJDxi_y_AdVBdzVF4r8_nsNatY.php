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

/* convene_theme:sdc_grid */
class __TwigTemplate_38568a7ba8829adceb57d44fda3ebb05 extends Template
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
            'slot_1' => [$this, 'block_slot_1'],
            'slot_2' => [$this, 'block_slot_2'],
            'slot_3' => [$this, 'block_slot_3'],
            'slot_4' => [$this, 'block_slot_4'],
        ];
        $this->sandbox = $this->extensions[SandboxExtension::class];
    }

    protected function doDisplay(array $context, array $blocks = []): iterable
    {
        $macros = $this->macros;
        // line 1
        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar($this->extensions['Drupal\Core\Template\TwigExtension']->attachLibrary("core/components.convene_theme--sdc_grid"));
        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar($this->extensions['Drupal\Core\Template\ComponentsTwigExtension']->addAdditionalContext($context, "convene_theme:sdc_grid"));
        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar($this->extensions['Drupal\Core\Template\ComponentsTwigExtension']->validateProps($context, "convene_theme:sdc_grid"));
        if ((($context["columns"] ?? null) == "33-33-33")) {
            // line 2
            yield "\t";
            $context["columns"] = 3;
        }
        // line 4
        $context["grid_styles"] = [];
        // line 5
        yield "
";
        // line 6
        if (((($tmp = ($context["margin_top"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp) && (($context["margin_top"] ?? null) != "0"))) {
            // line 7
            yield "\t";
            $context["grid_styles"] = Twig\Extension\CoreExtension::merge(($context["grid_styles"] ?? null), [("--sdc-margin-top: " . ($context["margin_top"] ?? null))]);
        }
        // line 9
        if (((($tmp = ($context["margin_right"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp) && (($context["margin_right"] ?? null) != "0"))) {
            // line 10
            yield "\t";
            $context["grid_styles"] = Twig\Extension\CoreExtension::merge(($context["grid_styles"] ?? null), [("--sdc-margin-right: " . ($context["margin_right"] ?? null))]);
        }
        // line 12
        if (((($tmp = ($context["margin_bottom"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp) && (($context["margin_bottom"] ?? null) != "0"))) {
            // line 13
            yield "\t";
            $context["grid_styles"] = Twig\Extension\CoreExtension::merge(($context["grid_styles"] ?? null), [("--sdc-margin-bottom: " . ($context["margin_bottom"] ?? null))]);
        }
        // line 15
        if (((($tmp = ($context["margin_left"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp) && (($context["margin_left"] ?? null) != "0"))) {
            // line 16
            yield "\t";
            $context["grid_styles"] = Twig\Extension\CoreExtension::merge(($context["grid_styles"] ?? null), [("--sdc-margin-left: " . ($context["margin_left"] ?? null))]);
        }
        // line 18
        yield "
";
        // line 19
        if (((($tmp = ($context["padding_top"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp) && (($context["padding_top"] ?? null) != "0"))) {
            // line 20
            yield "\t";
            $context["grid_styles"] = Twig\Extension\CoreExtension::merge(($context["grid_styles"] ?? null), [("--sdc-padding-top: " . ($context["padding_top"] ?? null))]);
        }
        // line 22
        if (((($tmp = ($context["padding_right"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp) && (($context["padding_right"] ?? null) != "0"))) {
            // line 23
            yield "\t";
            $context["grid_styles"] = Twig\Extension\CoreExtension::merge(($context["grid_styles"] ?? null), [("--sdc-padding-right: " . ($context["padding_right"] ?? null))]);
        }
        // line 25
        if (((($tmp = ($context["padding_bottom"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp) && (($context["padding_bottom"] ?? null) != "0"))) {
            // line 26
            yield "\t";
            $context["grid_styles"] = Twig\Extension\CoreExtension::merge(($context["grid_styles"] ?? null), [("--sdc-padding-bottom: " . ($context["padding_bottom"] ?? null))]);
        }
        // line 28
        if (((($tmp = ($context["padding_left"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp) && (($context["padding_left"] ?? null) != "0"))) {
            // line 29
            yield "\t";
            $context["grid_styles"] = Twig\Extension\CoreExtension::merge(($context["grid_styles"] ?? null), [("--sdc-padding-left: " . ($context["padding_left"] ?? null))]);
        }
        // line 31
        yield "
";
        // line 32
        if ((($tmp = ($context["gap"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 33
            yield "\t";
            $context["grid_styles"] = Twig\Extension\CoreExtension::merge(($context["grid_styles"] ?? null), [("--sdc-gap: " . ($context["gap"] ?? null))]);
        }
        // line 35
        if ((($tmp = ($context["columns"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 36
            yield "\t";
            $context["grid_styles"] = Twig\Extension\CoreExtension::merge(($context["grid_styles"] ?? null), [(("grid-template-columns: repeat(" . ($context["columns"] ?? null)) . ", 1fr)")]);
        }
        // line 38
        yield "
";
        // line 39
        $context["column_count"] = $this->extensions['Twig\Extension\CoreExtension']->formatNumber(((array_key_exists("columns", $context)) ? (Twig\Extension\CoreExtension::default(($context["columns"] ?? null), "1")) : ("1")), 0, "", "");
        // line 40
        yield "
<div class=\"sdc-grid\" ";
        // line 41
        if ((($tmp = ($context["grid_styles"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            yield " style=\"";
            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, Twig\Extension\CoreExtension::join(($context["grid_styles"] ?? null), "; "), "html", null, true);
            yield "\" ";
        }
        yield ">
\t<div class=\"sdc-grid__item\"> ";
        // line 42
        yield from $this->unwrap()->yieldBlock('slot_1', $context, $blocks);
        // line 43
        yield "\t\t</div>

\t\t";
        // line 45
        if ((($context["column_count"] ?? null) >= 2)) {
            // line 46
            yield "\t\t\t<div class=\"sdc-grid__item\"> ";
            yield from $this->unwrap()->yieldBlock('slot_2', $context, $blocks);
            // line 47
            yield "\t\t\t\t</div>
\t\t\t";
        }
        // line 49
        yield "
\t\t\t";
        // line 50
        if ((($context["column_count"] ?? null) >= 3)) {
            // line 51
            yield "\t\t\t\t<div class=\"sdc-grid__item\"> ";
            yield from $this->unwrap()->yieldBlock('slot_3', $context, $blocks);
            // line 52
            yield "\t\t\t\t\t</div>
\t\t\t\t";
        }
        // line 54
        yield "
\t\t\t\t";
        // line 55
        if ((($context["column_count"] ?? null) == 4)) {
            // line 56
            yield "\t\t\t\t\t<div class=\"sdc-grid__item\"> ";
            yield from $this->unwrap()->yieldBlock('slot_4', $context, $blocks);
            // line 57
            yield "\t\t\t\t\t\t</div>
\t\t\t\t\t";
        }
        // line 59
        yield "\t\t\t\t</div>
";
        $this->env->getExtension('\Drupal\Core\Template\TwigExtension')
            ->checkDeprecations($context, ["columns", "margin_top", "margin_right", "margin_bottom", "margin_left", "padding_top", "padding_right", "padding_bottom", "padding_left", "gap"]);        yield from [];
    }

    // line 42
    /**
     * @return iterable<null|scalar|\Stringable>
     */
    public function block_slot_1(array $context, array $blocks = []): iterable
    {
        $macros = $this->macros;
        yield from [];
    }

    // line 46
    /**
     * @return iterable<null|scalar|\Stringable>
     */
    public function block_slot_2(array $context, array $blocks = []): iterable
    {
        $macros = $this->macros;
        yield from [];
    }

    // line 51
    /**
     * @return iterable<null|scalar|\Stringable>
     */
    public function block_slot_3(array $context, array $blocks = []): iterable
    {
        $macros = $this->macros;
        yield from [];
    }

    // line 56
    /**
     * @return iterable<null|scalar|\Stringable>
     */
    public function block_slot_4(array $context, array $blocks = []): iterable
    {
        $macros = $this->macros;
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return "convene_theme:sdc_grid";
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
        return array (  220 => 56,  210 => 51,  200 => 46,  190 => 42,  183 => 59,  179 => 57,  176 => 56,  174 => 55,  171 => 54,  167 => 52,  164 => 51,  162 => 50,  159 => 49,  155 => 47,  152 => 46,  150 => 45,  146 => 43,  144 => 42,  136 => 41,  133 => 40,  131 => 39,  128 => 38,  124 => 36,  122 => 35,  118 => 33,  116 => 32,  113 => 31,  109 => 29,  107 => 28,  103 => 26,  101 => 25,  97 => 23,  95 => 22,  91 => 20,  89 => 19,  86 => 18,  82 => 16,  80 => 15,  76 => 13,  74 => 12,  70 => 10,  68 => 9,  64 => 7,  62 => 6,  59 => 5,  57 => 4,  53 => 2,  48 => 1,);
    }

    public function getSourceContext(): Source
    {
        return new Source("", "convene_theme:sdc_grid", "themes/contrib/convene_theme/components/sdc_grid/sdc_grid.twig");
    }
    
    public function ensureSecurityChecked(): void
    {
        if ($this->sandbox->isSandboxed($this->source)) {
            $this->checkSecurity();
        }
    }
    
    public function checkSecurity()
    {
        static $tags = ["if" => 1, "set" => 2, "block" => 42];
        static $filters = ["merge" => 7, "number_format" => 39, "default" => 39, "escape" => 41, "join" => 41];
        static $functions = [];
        static $tests = [];

        try {
            $this->sandbox->checkSecurity(
                [0 => "if", 1 => "set", 2 => "block"],
                [0 => "merge", 1 => "number_format", 2 => "default", 3 => "escape", 4 => "join"],
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
