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

/* convene_theme:featured_speakers */
class __TwigTemplate_e1dc0d07a71e5fea22020c8c88efff27 extends Template
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
        // line 1
        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar($this->extensions['Drupal\Core\Template\TwigExtension']->attachLibrary("core/components.convene_theme--featured_speakers"));
        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar($this->extensions['Drupal\Core\Template\ComponentsTwigExtension']->addAdditionalContext($context, "convene_theme:featured_speakers"));
        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar($this->extensions['Drupal\Core\Template\ComponentsTwigExtension']->validateProps($context, "convene_theme:featured_speakers"));
        yield "<section class=\"featured-speakers ";
        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar((((($tmp = ($context["admin_theme"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) ? ("featured-speakers--admin-theme") : ("featured-speakers--themed")));
        yield " ";
        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar((((($tmp = ($context["list_view"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) ? ("featured-speakers--list") : ("")));
        yield "\">
\t<div class=\"featured-speakers__header\">
\t\t<div class=\"featured-speakers__text\">
\t\t\t";
        // line 4
        if ((($tmp = ($context["eyebrow_slot"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 5
            yield "\t\t\t\t<div class=\"featured-speakers__eyebrow\">";
            if ((isset($context["canvas_is_preview"]) && $context["canvas_is_preview"]) && \array_key_exists("canvas_uuid", $context)) {
                if (\array_key_exists("canvas_slot_ids", $context) && \in_array("eyebrow_slot", $context["canvas_slot_ids"], TRUE)) {
                    yield \sprintf('<!-- canvas-slot-%s-%s/%s -->', "start", $context["canvas_uuid"], "eyebrow_slot");
                } else {
                    yield \sprintf('<!-- canvas-prop-%s-%s/%s -->', "start", $context["canvas_uuid"], "eyebrow_slot");
                }
            }
            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["eyebrow_slot"] ?? null), "html", null, true);
            if ((isset($context["canvas_is_preview"]) && $context["canvas_is_preview"]) && \array_key_exists("canvas_uuid", $context)) {
                if (\array_key_exists("canvas_slot_ids", $context) && \in_array("eyebrow_slot", $context["canvas_slot_ids"], TRUE)) {
                    yield \sprintf('<!-- canvas-slot-%s-%s/%s -->', "end", $context["canvas_uuid"], "eyebrow_slot");
                } else {
                    yield \sprintf('<!-- canvas-prop-%s-%s/%s -->', "end", $context["canvas_uuid"], "eyebrow_slot");
                }
            }
            yield "</div>
\t\t\t";
        }
        // line 7
        yield "\t\t\t";
        if ((($tmp = ($context["title_slot"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 8
            yield "\t\t\t\t<div class=\"featured-speakers__title\">";
            if ((isset($context["canvas_is_preview"]) && $context["canvas_is_preview"]) && \array_key_exists("canvas_uuid", $context)) {
                if (\array_key_exists("canvas_slot_ids", $context) && \in_array("title_slot", $context["canvas_slot_ids"], TRUE)) {
                    yield \sprintf('<!-- canvas-slot-%s-%s/%s -->', "start", $context["canvas_uuid"], "title_slot");
                } else {
                    yield \sprintf('<!-- canvas-prop-%s-%s/%s -->', "start", $context["canvas_uuid"], "title_slot");
                }
            }
            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["title_slot"] ?? null), "html", null, true);
            if ((isset($context["canvas_is_preview"]) && $context["canvas_is_preview"]) && \array_key_exists("canvas_uuid", $context)) {
                if (\array_key_exists("canvas_slot_ids", $context) && \in_array("title_slot", $context["canvas_slot_ids"], TRUE)) {
                    yield \sprintf('<!-- canvas-slot-%s-%s/%s -->', "end", $context["canvas_uuid"], "title_slot");
                } else {
                    yield \sprintf('<!-- canvas-prop-%s-%s/%s -->', "end", $context["canvas_uuid"], "title_slot");
                }
            }
            yield "</div>
\t\t\t";
        }
        // line 10
        yield "\t\t\t";
        if ((($tmp = ($context["description_slot"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 11
            yield "\t\t\t\t<div class=\"featured-speakers__description\">";
            if ((isset($context["canvas_is_preview"]) && $context["canvas_is_preview"]) && \array_key_exists("canvas_uuid", $context)) {
                if (\array_key_exists("canvas_slot_ids", $context) && \in_array("description_slot", $context["canvas_slot_ids"], TRUE)) {
                    yield \sprintf('<!-- canvas-slot-%s-%s/%s -->', "start", $context["canvas_uuid"], "description_slot");
                } else {
                    yield \sprintf('<!-- canvas-prop-%s-%s/%s -->', "start", $context["canvas_uuid"], "description_slot");
                }
            }
            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["description_slot"] ?? null), "html", null, true);
            if ((isset($context["canvas_is_preview"]) && $context["canvas_is_preview"]) && \array_key_exists("canvas_uuid", $context)) {
                if (\array_key_exists("canvas_slot_ids", $context) && \in_array("description_slot", $context["canvas_slot_ids"], TRUE)) {
                    yield \sprintf('<!-- canvas-slot-%s-%s/%s -->', "end", $context["canvas_uuid"], "description_slot");
                } else {
                    yield \sprintf('<!-- canvas-prop-%s-%s/%s -->', "end", $context["canvas_uuid"], "description_slot");
                }
            }
            yield "</div>
\t\t\t";
        }
        // line 13
        yield "\t\t</div>
\t</div>

\t<div class=\"featured-speakers__slider-container\">
\t\t<div class=\"featured-speakers__slider\" role=\"region\" aria-label=\"Speakers Slider\">
\t\t\t";
        // line 18
        if ((isset($context["canvas_is_preview"]) && $context["canvas_is_preview"]) && \array_key_exists("canvas_uuid", $context)) {
            if (\array_key_exists("canvas_slot_ids", $context) && \in_array("speakers", $context["canvas_slot_ids"], TRUE)) {
                yield \sprintf('<!-- canvas-slot-%s-%s/%s -->', "start", $context["canvas_uuid"], "speakers");
            } else {
                yield \sprintf('<!-- canvas-prop-%s-%s/%s -->', "start", $context["canvas_uuid"], "speakers");
            }
        }
        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["speakers"] ?? null), "html", null, true);
        if ((isset($context["canvas_is_preview"]) && $context["canvas_is_preview"]) && \array_key_exists("canvas_uuid", $context)) {
            if (\array_key_exists("canvas_slot_ids", $context) && \in_array("speakers", $context["canvas_slot_ids"], TRUE)) {
                yield \sprintf('<!-- canvas-slot-%s-%s/%s -->', "end", $context["canvas_uuid"], "speakers");
            } else {
                yield \sprintf('<!-- canvas-prop-%s-%s/%s -->', "end", $context["canvas_uuid"], "speakers");
            }
        }
        yield "
\t\t</div>
\t</div>

\t";
        // line 22
        if ( !(($tmp = ($context["list_view"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 23
            yield "\t\t<div class=\"featured-speakers__controls\">
\t\t\t<div
\t\t\t\tclass=\"featured-speakers__dots\">";
            // line 26
            yield "\t\t\t</div>
\t\t\t<div class=\"featured-speakers__arrows\">
\t\t\t\t<button class=\"featured-speakers__nav featured-speakers__nav--prev\" aria-label=\"Previous slide\" disabled>
\t\t\t\t\t<svg xmlns=\"http://www.w3.org/2000/svg\" width=\"24\" height=\"24\" viewbox=\"0 0 24 24\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"2\" stroke-linecap=\"round\" stroke-linejoin=\"round\"><path d=\"M19 12H5\"/><path d=\"M12 19l-7-7 7-7\"/></svg>
\t\t\t\t</button>
\t\t\t\t<button class=\"featured-speakers__nav featured-speakers__nav--next\" aria-label=\"Next slide\">
\t\t\t\t\t<svg xmlns=\"http://www.w3.org/2000/svg\" width=\"24\" height=\"24\" viewbox=\"0 0 24 24\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"2\" stroke-linecap=\"round\" stroke-linejoin=\"round\"><path d=\"M5 12h14\"/><path d=\"M12 5l7 7-7 7\"/></svg>
\t\t\t\t</button>
\t\t\t</div>
\t\t</div>
\t";
        }
        // line 37
        yield "
</section>
";
        $this->env->getExtension('\Drupal\Core\Template\TwigExtension')
            ->checkDeprecations($context, ["admin_theme", "list_view", "eyebrow_slot", "title_slot", "description_slot", "speakers"]);        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return "convene_theme:featured_speakers";
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
        return array (  171 => 37,  158 => 26,  154 => 23,  152 => 22,  131 => 18,  124 => 13,  104 => 11,  101 => 10,  81 => 8,  78 => 7,  58 => 5,  56 => 4,  44 => 1,);
    }

    public function getSourceContext(): Source
    {
        return new Source("", "convene_theme:featured_speakers", "themes/contrib/convene_theme/components/featured_speakers/featured_speakers.twig");
    }
    
    public function ensureSecurityChecked(): void
    {
        if ($this->sandbox->isSandboxed($this->source)) {
            $this->checkSecurity();
        }
    }
    
    public function checkSecurity()
    {
        static $tags = ["if" => 4];
        static $filters = ["escape" => 5];
        static $functions = [];
        static $tests = [];

        try {
            $this->sandbox->checkSecurity(
                [0 => "if"],
                [0 => "escape"],
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
