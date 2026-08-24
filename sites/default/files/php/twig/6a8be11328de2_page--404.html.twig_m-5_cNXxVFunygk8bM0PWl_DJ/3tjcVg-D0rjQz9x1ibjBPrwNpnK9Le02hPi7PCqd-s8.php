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

/* themes/contrib/convene_theme/templates/layout/page--404.html.twig */
class __TwigTemplate_4280ba80e532bad3ab882c682bf72c9c extends Template
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
        yield "<div class=\"layout-container\">

\t<main role=\"main\">
\t\t<a id=\"main-content\" tabindex=\"-1\"></a>

\t\t<div class=\"custom-404-page\">
\t\t\t<style>
\t\t\t\t.custom-404-page {
\t\t\t\t\tdisplay: flex;
\t\t\t\t\talign-items: center;
\t\t\t\t\tjustify-content: center;
\t\t\t\t\tpadding: 6rem 1rem;
\t\t\t\t\tbackground: #030113;
\t\t\t\t\tbackground-image: radial-gradient(circle at 50% 0%, #1b163d 0%, #030113 70%);
\t\t\t\t\tmin-height: calc(100vh - 200px);
                    color: #fff;
                    font-family: \x27Inter\x27, system-ui, -apple-system, sans-serif;
\t\t\t\t}
\t\t\t\t.custom-404-card {
\t\t\t\t\tbackground: rgba(255, 255, 255, 0.02);
\t\t\t\t\tborder: 1px solid rgba(255, 255, 255, 0.05);
\t\t\t\t\tbackdrop-filter: blur(24px);
\t\t\t\t\t-webkit-backdrop-filter: blur(24px);
\t\t\t\t\tborder-radius: 2rem;
\t\t\t\t\tpadding: 4rem 2rem;
\t\t\t\t\tmax-width: 900px;
\t\t\t\t\twidth: 100%;
\t\t\t\t\tdisplay: flex;
\t\t\t\t\tflex-direction: column;
\t\t\t\t\talign-items: center;
\t\t\t\t\ttext-align: center;
\t\t\t\t\tbox-shadow: 0 30px 60px rgba(0,0,0,0.4);
                    position: relative;
                    overflow: hidden;
\t\t\t\t}
                .custom-404-card::before {
                    content: \x27\x27;
                    position: absolute;
                    top: -50%;
                    left: -50%;
                    width: 200%;
                    height: 200%;
                    background: radial-gradient(circle at center, rgba(100, 49, 241, 0.1) 0%, transparent 40%);
                    pointer-events: none;
                    z-index: 0;
                }

                .custom-404-image {
                  display:none;
                }
\t\t\t\t.custom-404-image {
\t\t\t\t\twidth: 100%;
\t\t\t\t\tmax-width: 600px;
\t\t\t\t\theight: auto;
\t\t\t\t\tborder-radius: 1.5rem;
\t\t\t\t\tmargin-bottom: 3rem;
\t\t\t\t\tbox-shadow: 0 20px 50px rgba(0,0,0,0.5);
                    position: relative;
                    z-index: 1;
                    border: 1px solid rgba(255,255,255,0.05);
\t\t\t\t}
                .custom-404-content-inner {
                    position: relative;
                    z-index: 1;
                }
\t\t\t\t.custom-404-code {
\t\t\t\t\tfont-size: clamp(5rem, 10vw, 8rem);
\t\t\t\t\tfont-weight: 800;
\t\t\t\t\tline-height: 1;
\t\t\t\t\tmargin: 0 0 1rem 0;
\t\t\t\t\tbackground: linear-gradient(135deg, #ffffff 0%, #1ebdb6 100%);
\t\t\t\t\t-webkit-background-clip: text;
\t\t\t\t\t-webkit-text-fill-color: transparent;
\t\t\t\t\tletter-spacing: -0.03em;
\t\t\t\t}
\t\t\t\t.custom-404-title {
\t\t\t\t\tfont-size: clamp(1.5rem, 3vw, 2.25rem);
\t\t\t\t\tfont-weight: 600;
\t\t\t\t\tcolor: #ffffff;
\t\t\t\t\tmargin: 0 0 1.5rem 0;
\t\t\t\t}
\t\t\t\t.custom-404-desc {
\t\t\t\t\tfont-size: 1.125rem;
\t\t\t\t\tcolor: rgba(255, 255, 255, 0.7);
\t\t\t\t\tmax-width: 600px;
\t\t\t\t\tmargin: 0 auto 3rem auto;
\t\t\t\t\tline-height: 1.6;
\t\t\t\t}
\t\t\t\t.custom-404-action {
\t\t\t\t\tdisplay: inline-flex;
\t\t\t\t\talign-items: center;
\t\t\t\t\tjustify-content: center;
\t\t\t\t\tpadding: 1.125rem 2.5rem;
\t\t\t\t\tbackground: #6431f1;
\t\t\t\t\tcolor: #ffffff;
\t\t\t\t\ttext-decoration: none;
\t\t\t\t\tborder-radius: 50px;
\t\t\t\t\tfont-weight: 600;
\t\t\t\t\tfont-size: 1.125rem;
\t\t\t\t\ttransition: all 0.3s ease;
\t\t\t\t\tborder: 1px solid rgba(255,255,255,0.1);
\t\t\t\t}
\t\t\t\t.custom-404-action:hover {
\t\t\t\t\tbackground: #5027c2;
\t\t\t\t\ttransform: translateY(-2px);
\t\t\t\t\tbox-shadow: 0 10px 25px rgba(100, 49, 241, 0.4);
                    color: #fff;
\t\t\t\t}
                .custom-404-action svg {
                    margin-right: 0.75rem;
                }
\t\t\t</style>
\t\t\t
\t\t\t<div class=\"custom-404-card\">
\t\t\t\t
                <div class=\"custom-404-content-inner\">
                    <h1 class=\"custom-404-code\">404</h1>
                    <h2 class=\"custom-404-title\">Looks like you\x27re lost.</h2>
                    <p class=\"custom-404-desc\">The venue or event you are looking for doesn\x27t exist anymore, or the link might be broken.</p>
                    
                    <a href=\"";
        // line 121
        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar($this->extensions['Drupal\Core\Template\TwigExtension']->getUrl("<front>"));
        yield "\" class=\"custom-404-action\">
                        <svg width=\"20\" height=\"20\" viewBox=\"0 0 24 24\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"2\" stroke-linecap=\"round\" stroke-linejoin=\"round\">
                            <path d=\"M19 12H5M12 19l-7-7 7-7\"/>
                        </svg>
                        Return to Homepage
                    </a>
                </div>
\t\t\t</div>
\t\t</div>

\t</main>
</div>
";
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return "themes/contrib/convene_theme/templates/layout/page--404.html.twig";
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
        return array (  166 => 121,  44 => 1,);
    }

    public function getSourceContext(): Source
    {
        return new Source("", "themes/contrib/convene_theme/templates/layout/page--404.html.twig", "/home/itsiugor/public_html/themes/contrib/convene_theme/templates/layout/page--404.html.twig");
    }
    
    public function ensureSecurityChecked(): void
    {
        if ($this->sandbox->isSandboxed($this->source)) {
            $this->checkSecurity();
        }
    }
    
    public function checkSecurity()
    {
        static $tags = [];
        static $filters = [];
        static $functions = ["url" => 121];
        static $tests = [];

        try {
            $this->sandbox->checkSecurity(
                [],
                [],
                [0 => "url"],
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
