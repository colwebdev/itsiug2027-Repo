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

/* convene_theme:testimonial_item */
class __TwigTemplate_11fc8577e96082ef01490cf5104a7b29 extends Template
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
        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar($this->extensions['Drupal\Core\Template\TwigExtension']->attachLibrary("core/components.convene_theme--testimonial_item"));
        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar($this->extensions['Drupal\Core\Template\ComponentsTwigExtension']->addAdditionalContext($context, "convene_theme:testimonial_item"));
        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar($this->extensions['Drupal\Core\Template\ComponentsTwigExtension']->validateProps($context, "convene_theme:testimonial_item"));
        $context["classes"] = ["testimonial-item", (((($tmp =         // line 3
($context["admin_theme"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) ? ("testimonial-item--admin-theme") : ("testimonial-item--themed"))];
        // line 5
        yield "
<div";
        // line 6
        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, ($context["attributes"] ?? null), "addClass", [($context["classes"] ?? null)], "method", false, false, true, 6), "html", null, true);
        yield ">
  <div class=\"testimonial-item__logo\">
    ";
        // line 8
        if ((isset($context["canvas_is_preview"]) && $context["canvas_is_preview"]) && \array_key_exists("canvas_uuid", $context)) {
            if (\array_key_exists("canvas_slot_ids", $context) && \in_array("logo", $context["canvas_slot_ids"], TRUE)) {
                yield \sprintf('<!-- canvas-slot-%s-%s/%s -->', "start", $context["canvas_uuid"], "logo");
            } else {
                yield \sprintf('<!-- canvas-prop-%s-%s/%s -->', "start", $context["canvas_uuid"], "logo");
            }
        }
        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["logo"] ?? null), "html", null, true);
        if ((isset($context["canvas_is_preview"]) && $context["canvas_is_preview"]) && \array_key_exists("canvas_uuid", $context)) {
            if (\array_key_exists("canvas_slot_ids", $context) && \in_array("logo", $context["canvas_slot_ids"], TRUE)) {
                yield \sprintf('<!-- canvas-slot-%s-%s/%s -->', "end", $context["canvas_uuid"], "logo");
            } else {
                yield \sprintf('<!-- canvas-prop-%s-%s/%s -->', "end", $context["canvas_uuid"], "logo");
            }
        }
        yield "
  </div>

  ";
        // line 11
        if ((($tmp = ($context["quote"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 12
            yield "    <blockquote class=\"testimonial-item__quote\">
      ";
            // line 13
            if ((isset($context["canvas_is_preview"]) && $context["canvas_is_preview"]) && \array_key_exists("canvas_uuid", $context)) {
                if (\array_key_exists("canvas_slot_ids", $context) && \in_array("quote", $context["canvas_slot_ids"], TRUE)) {
                    yield \sprintf('<!-- canvas-slot-%s-%s/%s -->', "start", $context["canvas_uuid"], "quote");
                } else {
                    yield \sprintf('<!-- canvas-prop-%s-%s/%s -->', "start", $context["canvas_uuid"], "quote");
                }
            }
            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["quote"] ?? null), "html", null, true);
            if ((isset($context["canvas_is_preview"]) && $context["canvas_is_preview"]) && \array_key_exists("canvas_uuid", $context)) {
                if (\array_key_exists("canvas_slot_ids", $context) && \in_array("quote", $context["canvas_slot_ids"], TRUE)) {
                    yield \sprintf('<!-- canvas-slot-%s-%s/%s -->', "end", $context["canvas_uuid"], "quote");
                } else {
                    yield \sprintf('<!-- canvas-prop-%s-%s/%s -->', "end", $context["canvas_uuid"], "quote");
                }
            }
            yield "
    </blockquote>
  ";
        }
        // line 16
        yield "
  <div class=\"testimonial-item__author\">
    <div class=\"testimonial-item__author-media\">
      ";
        // line 19
        if ((isset($context["canvas_is_preview"]) && $context["canvas_is_preview"]) && \array_key_exists("canvas_uuid", $context)) {
            if (\array_key_exists("canvas_slot_ids", $context) && \in_array("author_media", $context["canvas_slot_ids"], TRUE)) {
                yield \sprintf('<!-- canvas-slot-%s-%s/%s -->', "start", $context["canvas_uuid"], "author_media");
            } else {
                yield \sprintf('<!-- canvas-prop-%s-%s/%s -->', "start", $context["canvas_uuid"], "author_media");
            }
        }
        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["author_media"] ?? null), "html", null, true);
        if ((isset($context["canvas_is_preview"]) && $context["canvas_is_preview"]) && \array_key_exists("canvas_uuid", $context)) {
            if (\array_key_exists("canvas_slot_ids", $context) && \in_array("author_media", $context["canvas_slot_ids"], TRUE)) {
                yield \sprintf('<!-- canvas-slot-%s-%s/%s -->', "end", $context["canvas_uuid"], "author_media");
            } else {
                yield \sprintf('<!-- canvas-prop-%s-%s/%s -->', "end", $context["canvas_uuid"], "author_media");
            }
        }
        yield "
    </div>
    <div class=\"testimonial-item__author-info\">
      ";
        // line 22
        if ((($tmp = ($context["author_name"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 23
            yield "        <div class=\"testimonial-item__author-name\">";
            if ((isset($context["canvas_is_preview"]) && $context["canvas_is_preview"]) && \array_key_exists("canvas_uuid", $context)) {
                if (\array_key_exists("canvas_slot_ids", $context) && \in_array("author_name", $context["canvas_slot_ids"], TRUE)) {
                    yield \sprintf('<!-- canvas-slot-%s-%s/%s -->', "start", $context["canvas_uuid"], "author_name");
                } else {
                    yield \sprintf('<!-- canvas-prop-%s-%s/%s -->', "start", $context["canvas_uuid"], "author_name");
                }
            }
            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["author_name"] ?? null), "html", null, true);
            if ((isset($context["canvas_is_preview"]) && $context["canvas_is_preview"]) && \array_key_exists("canvas_uuid", $context)) {
                if (\array_key_exists("canvas_slot_ids", $context) && \in_array("author_name", $context["canvas_slot_ids"], TRUE)) {
                    yield \sprintf('<!-- canvas-slot-%s-%s/%s -->', "end", $context["canvas_uuid"], "author_name");
                } else {
                    yield \sprintf('<!-- canvas-prop-%s-%s/%s -->', "end", $context["canvas_uuid"], "author_name");
                }
            }
            yield "</div>
      ";
        }
        // line 25
        yield "      ";
        if ((($tmp = ($context["author_role"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 26
            yield "        <div class=\"testimonial-item__author-role\">";
            if ((isset($context["canvas_is_preview"]) && $context["canvas_is_preview"]) && \array_key_exists("canvas_uuid", $context)) {
                if (\array_key_exists("canvas_slot_ids", $context) && \in_array("author_role", $context["canvas_slot_ids"], TRUE)) {
                    yield \sprintf('<!-- canvas-slot-%s-%s/%s -->', "start", $context["canvas_uuid"], "author_role");
                } else {
                    yield \sprintf('<!-- canvas-prop-%s-%s/%s -->', "start", $context["canvas_uuid"], "author_role");
                }
            }
            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["author_role"] ?? null), "html", null, true);
            if ((isset($context["canvas_is_preview"]) && $context["canvas_is_preview"]) && \array_key_exists("canvas_uuid", $context)) {
                if (\array_key_exists("canvas_slot_ids", $context) && \in_array("author_role", $context["canvas_slot_ids"], TRUE)) {
                    yield \sprintf('<!-- canvas-slot-%s-%s/%s -->', "end", $context["canvas_uuid"], "author_role");
                } else {
                    yield \sprintf('<!-- canvas-prop-%s-%s/%s -->', "end", $context["canvas_uuid"], "author_role");
                }
            }
            yield "</div>
      ";
        }
        // line 28
        yield "    </div>
  </div>
</div>
";
        $this->env->getExtension('\Drupal\Core\Template\TwigExtension')
            ->checkDeprecations($context, ["admin_theme", "attributes", "logo", "quote", "author_media", "author_name", "author_role"]);        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return "convene_theme:testimonial_item";
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
        return array (  173 => 28,  153 => 26,  150 => 25,  130 => 23,  128 => 22,  108 => 19,  103 => 16,  83 => 13,  80 => 12,  78 => 11,  58 => 8,  53 => 6,  50 => 5,  48 => 3,  44 => 1,);
    }

    public function getSourceContext(): Source
    {
        return new Source("", "convene_theme:testimonial_item", "themes/contrib/convene_theme/components/testimonial_item/testimonial_item.twig");
    }
    
    public function ensureSecurityChecked(): void
    {
        if ($this->sandbox->isSandboxed($this->source)) {
            $this->checkSecurity();
        }
    }
    
    public function checkSecurity()
    {
        static $tags = ["set" => 1, "if" => 11];
        static $filters = ["escape" => 6];
        static $functions = [];
        static $tests = [];

        try {
            $this->sandbox->checkSecurity(
                [0 => "set", 1 => "if"],
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
