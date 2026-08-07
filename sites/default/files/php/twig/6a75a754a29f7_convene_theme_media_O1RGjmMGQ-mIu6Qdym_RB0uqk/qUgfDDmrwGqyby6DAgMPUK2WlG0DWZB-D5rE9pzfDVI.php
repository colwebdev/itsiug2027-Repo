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

/* convene_theme:media */
class __TwigTemplate_03eaab3c420e2d4c3c9bbc8db9c23494 extends Template
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
        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar($this->extensions['Drupal\Core\Template\TwigExtension']->attachLibrary("core/components.convene_theme--media"));
        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar($this->extensions['Drupal\Core\Template\ComponentsTwigExtension']->addAdditionalContext($context, "convene_theme:media"));
        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar($this->extensions['Drupal\Core\Template\ComponentsTwigExtension']->validateProps($context, "convene_theme:media"));
        if ((($tmp = ($context["image"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 2
            yield "<picture class=\"media-picture\" style=\"--aspect-ratio: ";
            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ((array_key_exists("aspect_ratio", $context)) ? (Twig\Extension\CoreExtension::default(($context["aspect_ratio"] ?? null), "auto")) : ("auto")), "html", null, true);
            yield ";\">
    ";
            // line 3
            yield from $this->load("canvas:image", 3)->unwrap()->yield(CoreExtension::toArray(["src" => CoreExtension::getAttribute($this->env, $this->source,             // line 4
($context["image"] ?? null), "src", [], "any", false, false, true, 4), "alt" => Twig\Extension\CoreExtension::default(((            // line 5
array_key_exists("alt_tag", $context)) ? (Twig\Extension\CoreExtension::default(($context["alt_tag"] ?? null), CoreExtension::getAttribute($this->env, $this->source, ($context["image"] ?? null), "alt", [], "any", false, false, true, 5))) : (CoreExtension::getAttribute($this->env, $this->source, ($context["image"] ?? null), "alt", [], "any", false, false, true, 5))), ""), "width" => CoreExtension::getAttribute($this->env, $this->source,             // line 6
($context["image"] ?? null), "width", [], "any", false, false, true, 6), "height" => CoreExtension::getAttribute($this->env, $this->source,             // line 7
($context["image"] ?? null), "height", [], "any", false, false, true, 7), "loading" => ((            // line 8
array_key_exists("loading", $context)) ? (Twig\Extension\CoreExtension::default(($context["loading"] ?? null), "lazy")) : ("lazy")), "class" => "image", "attributes" =>             // line 10
($context["attributes"] ?? null)]));
            // line 12
            yield "</picture>
";
        }
        $this->env->getExtension('\Drupal\Core\Template\TwigExtension')
            ->checkDeprecations($context, ["image", "aspect_ratio", "alt_tag", "loading", "attributes"]);        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return "convene_theme:media";
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
        return array (  62 => 12,  60 => 10,  59 => 8,  58 => 7,  57 => 6,  56 => 5,  55 => 4,  54 => 3,  49 => 2,  44 => 1,);
    }

    public function getSourceContext(): Source
    {
        return new Source("", "convene_theme:media", "themes/contrib/convene_theme/components/media/media.twig");
    }
    
    public function ensureSecurityChecked(): void
    {
        if ($this->sandbox->isSandboxed($this->source)) {
            $this->checkSecurity();
        }
    }
    
    public function checkSecurity()
    {
        static $tags = ["if" => 1, "include" => 3];
        static $filters = ["escape" => 2, "default" => 2];
        static $functions = [];
        static $tests = [];

        try {
            $this->sandbox->checkSecurity(
                [0 => "if", 1 => "include"],
                [0 => "escape", 1 => "default"],
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
