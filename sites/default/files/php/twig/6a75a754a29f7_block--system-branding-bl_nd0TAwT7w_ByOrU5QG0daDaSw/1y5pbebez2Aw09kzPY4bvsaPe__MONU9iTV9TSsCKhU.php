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

/* themes/contrib/convene_theme/templates/blocks/block--system-branding-block.html.twig */
class __TwigTemplate_ecf3e7a53fb7b155a81d1f2dff7763da extends Template
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

        $this->blocks = [
            'content' => [$this, 'block_content'],
        ];
        $this->sandbox = $this->extensions[SandboxExtension::class];
    }

    protected function doGetParent(array $context): bool|string|Template|TemplateWrapper
    {
        // line 1
        return "block.html.twig";
    }

    protected function doDisplay(array $context, array $blocks = []): iterable
    {
        $macros = $this->macros;
        $this->parent = $this->load("block.html.twig", 1);
        yield from $this->parent->unwrap()->yield($context, array_merge($this->blocks, $blocks));
        $this->env->getExtension('\Drupal\Core\Template\TwigExtension')
            ->checkDeprecations($context, ["site_logo", "site_name", "site_slogan"]);    }

    // line 18
    /**
     * @return iterable<null|scalar|\Stringable>
     */
    public function block_content(array $context, array $blocks = []): iterable
    {
        $macros = $this->macros;
        // line 19
        yield "\t<div class=\"site-branding";
        if (((($tmp = ($context["site_logo"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp) ||  !(($tmp = ($context["site_logo"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp))) {
            yield " has-logo";
        }
        if ((($tmp = ($context["site_name"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            yield " has-name";
        }
        if ((($tmp = ($context["site_slogan"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            yield " has-slogan";
        }
        yield "\">
\t\t";
        // line 20
        if (((($tmp = ($context["site_logo"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp) ||  !(($tmp = ($context["site_logo"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp))) {
            // line 21
            yield "\t\t\t<div class=\"site-branding__logo\">
\t\t\t\t<a href=\"";
            // line 22
            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar($this->extensions['Drupal\Core\Template\TwigExtension']->getPath("<front>"));
            yield "\" rel=\"home\">
\t\t\t\t\t";
            // line 23
            if ((($tmp = ($context["site_logo"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                // line 24
                yield "\t\t\t\t\t\t<img src=\"";
                yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["site_logo"] ?? null), "html", null, true);
                yield "\" alt=\"";
                yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(t("Home"));
                yield "\" fetchpriority=\"high\"/>
\t\t\t\t\t";
            } else {
                // line 26
                yield "\t\t\t\t\t\t<img src=\"/themes/contrib/convene_theme/assets/svg/Logo-wide-1.png\" alt=\"";
                yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(t("Home"));
                yield "\" fetchpriority=\"high\"/>
\t\t\t\t\t";
            }
            // line 28
            yield "\t\t\t\t</a>
\t\t\t</div>
\t\t";
        }
        // line 31
        yield "\t\t";
        if ((($tmp = ($context["site_name"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 32
            yield "\t\t\t<div class=\"site-branding__name\">
\t\t\t\t<a href=\"";
            // line 33
            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar($this->extensions['Drupal\Core\Template\TwigExtension']->getPath("<front>"));
            yield "\" rel=\"home\">";
            if ((isset($context["canvas_is_preview"]) && $context["canvas_is_preview"]) && \array_key_exists("canvas_uuid", $context)) {
                if (\array_key_exists("canvas_slot_ids", $context) && \in_array("site_name", $context["canvas_slot_ids"], TRUE)) {
                    yield \sprintf('<!-- canvas-slot-%s-%s/%s -->', "start", $context["canvas_uuid"], "site_name");
                } else {
                    yield \sprintf('<!-- canvas-prop-%s-%s/%s -->', "start", $context["canvas_uuid"], "site_name");
                }
            }
            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["site_name"] ?? null), "html", null, true);
            if ((isset($context["canvas_is_preview"]) && $context["canvas_is_preview"]) && \array_key_exists("canvas_uuid", $context)) {
                if (\array_key_exists("canvas_slot_ids", $context) && \in_array("site_name", $context["canvas_slot_ids"], TRUE)) {
                    yield \sprintf('<!-- canvas-slot-%s-%s/%s -->', "end", $context["canvas_uuid"], "site_name");
                } else {
                    yield \sprintf('<!-- canvas-prop-%s-%s/%s -->', "end", $context["canvas_uuid"], "site_name");
                }
            }
            yield "</a>
\t\t\t</div>
\t\t";
        }
        // line 36
        yield "\t\t";
        if ((($tmp = ($context["site_slogan"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 37
            yield "\t\t\t<div class=\"site-branding__slogan\">";
            if ((isset($context["canvas_is_preview"]) && $context["canvas_is_preview"]) && \array_key_exists("canvas_uuid", $context)) {
                if (\array_key_exists("canvas_slot_ids", $context) && \in_array("site_slogan", $context["canvas_slot_ids"], TRUE)) {
                    yield \sprintf('<!-- canvas-slot-%s-%s/%s -->', "start", $context["canvas_uuid"], "site_slogan");
                } else {
                    yield \sprintf('<!-- canvas-prop-%s-%s/%s -->', "start", $context["canvas_uuid"], "site_slogan");
                }
            }
            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["site_slogan"] ?? null), "html", null, true);
            if ((isset($context["canvas_is_preview"]) && $context["canvas_is_preview"]) && \array_key_exists("canvas_uuid", $context)) {
                if (\array_key_exists("canvas_slot_ids", $context) && \in_array("site_slogan", $context["canvas_slot_ids"], TRUE)) {
                    yield \sprintf('<!-- canvas-slot-%s-%s/%s -->', "end", $context["canvas_uuid"], "site_slogan");
                } else {
                    yield \sprintf('<!-- canvas-prop-%s-%s/%s -->', "end", $context["canvas_uuid"], "site_slogan");
                }
            }
            yield "</div>
\t\t";
        }
        // line 39
        yield "\t</div>
";
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return "themes/contrib/convene_theme/templates/blocks/block--system-branding-block.html.twig";
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
        return array (  155 => 39,  135 => 37,  132 => 36,  110 => 33,  107 => 32,  104 => 31,  99 => 28,  93 => 26,  85 => 24,  83 => 23,  79 => 22,  76 => 21,  74 => 20,  61 => 19,  54 => 18,  42 => 1,);
    }

    public function getSourceContext(): Source
    {
        return new Source("", "themes/contrib/convene_theme/templates/blocks/block--system-branding-block.html.twig", "/var/www/html/themes/contrib/convene_theme/templates/blocks/block--system-branding-block.html.twig");
    }
    
    public function ensureSecurityChecked(): void
    {
        if ($this->sandbox->isSandboxed($this->source)) {
            $this->checkSecurity();
        }
    }
    
    public function checkSecurity()
    {
        static $tags = ["extends" => 1, "if" => 19];
        static $filters = ["escape" => 24, "t" => 24];
        static $functions = ["path" => 22];
        static $tests = [];

        try {
            $this->sandbox->checkSecurity(
                [0 => "extends", 1 => "if"],
                [0 => "escape", 1 => "t"],
                [0 => "path"],
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
