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

/* canvas:image */
class __TwigTemplate_5a3963d16ef695180fc02aaa93ef2b58 extends Template
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
        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar($this->extensions['Drupal\Core\Template\TwigExtension']->attachLibrary("core/components.canvas--image"));
        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar($this->extensions['Drupal\Core\Template\ComponentsTwigExtension']->addAdditionalContext($context, "canvas:image"));
        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar($this->extensions['Drupal\Core\Template\ComponentsTwigExtension']->validateProps($context, "canvas:image"));
        $context["width"] = (((array_key_exists("width", $context) &&  !(null === $context["width"]))) ? ($context["width"]) : ($this->extensions['Drupal\canvas\Twig\CanvasTwigExtension']->getWidth(($context["src"] ?? null))));
        // line 2
        $context["height"] = (((array_key_exists("height", $context) &&  !(null === $context["height"]))) ? ($context["height"]) : ($this->extensions['Drupal\canvas\Twig\CanvasTwigExtension']->getHeight(($context["src"] ?? null))));
        // line 3
        $context["srcset"] = $this->extensions['Drupal\canvas\Twig\CanvasTwigExtension']->toSrcSet(($context["src"] ?? null), ($context["width"] ?? null));
        // line 5
        $context["src"] = $this->extensions['Drupal\Core\Template\TwigExtension']->getFileUrl(($context["src"] ?? null));
        // line 6
        yield "<img
   class=\"";
        // line 7
        yield (string) (((array_key_exists("class", $context) &&  !(null === $context["class"]))) ? ($this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, $context["class"], "html", null, true)) : ("image"));
        yield "\"
   src=\"";
        // line 8
        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["src"] ?? null), "html", null, true);
        yield "\"
   ";
        // line 9
        if (((($tmp = ($context["srcset"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp) &&  !Twig\Extension\CoreExtension::testEmpty(($context["srcset"] ?? null)))) {
            // line 10
            yield "     srcset=\"";
            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["srcset"] ?? null), "html", null, true);
            yield "\"
     sizes=\"";
            // line 11
            yield (string) (((array_key_exists("sizes", $context) &&  !(null === $context["sizes"]))) ? ($this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, $context["sizes"], "html", null, true)) : ("auto 100vw"));
            yield "\"
   ";
        }
        // line 13
        yield "   ";
        if ((($tmp = ($context["alt"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 14
            yield "     alt=\"";
            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["alt"] ?? null), "html", null, true);
            yield "\"
   ";
        }
        // line 16
        yield "   ";
        if ((($tmp = ($context["width"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 17
            yield "     width=\"";
            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["width"] ?? null), "html", null, true);
            yield "\"
   ";
        }
        // line 19
        yield "   ";
        if ((($tmp = ($context["height"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 20
            yield "     height=\"";
            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["height"] ?? null), "html", null, true);
            yield "\"
   ";
        }
        // line 22
        yield "   loading=\"";
        yield (string) (((array_key_exists("loading", $context) &&  !(null === $context["loading"]))) ? ($this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, $context["loading"], "html", null, true)) : ("lazy"));
        yield "\"
   ";
        // line 23
        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["attributes"] ?? null), "html", null, true);
        yield "
/>
";
        $this->env->getExtension('\Drupal\Core\Template\TwigExtension')
            ->checkDeprecations($context, ["src", "class", "sizes", "alt", "loading", "attributes"]);        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return "canvas:image";
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
        return array (  110 => 23,  105 => 22,  99 => 20,  96 => 19,  90 => 17,  87 => 16,  81 => 14,  78 => 13,  73 => 11,  68 => 10,  66 => 9,  62 => 8,  58 => 7,  55 => 6,  53 => 5,  51 => 3,  49 => 2,  44 => 1,);
    }

    public function getSourceContext(): Source
    {
        return new Source("", "canvas:image", "modules/contrib/canvas/components/image/image.twig");
    }
    
    public function ensureSecurityChecked(): void
    {
        if ($this->sandbox->isSandboxed($this->source)) {
            $this->checkSecurity();
        }
    }
    
    public function checkSecurity()
    {
        static $tags = ["set" => 1, "if" => 9];
        static $filters = ["getWidth" => 1, "getHeight" => 2, "toSrcSet" => 3, "escape" => 7];
        static $functions = ["file_url" => 5];
        static $tests = [];

        try {
            $this->sandbox->checkSecurity(
                [0 => "set", 1 => "if"],
                [0 => "getWidth", 1 => "getHeight", 2 => "toSrcSet", 3 => "escape"],
                [0 => "file_url"],
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
