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

/* themes/contrib/gin/templates/views/views-view-table.html.twig */
class __TwigTemplate_7243552d89a6250f800955fe7fd49b10 extends Template
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
        // line 35
        $context["classes"] = ["views-table", "views-view-table", ("cols-" . Twig\Extension\CoreExtension::length($this->env->getCharset(),         // line 38
($context["header"] ?? null))), (((($tmp =         // line 39
($context["responsive"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) ? ("responsive-enabled") : ("")), (((($tmp =         // line 40
($context["sticky"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) ? ("sticky-enabled position-sticky") : (""))];
        // line 43
        yield "
";
        // line 44
        $macros["͜macros"] = $this->macros["͜macros"] = $this;
        // line 45
        yield "
";
        // line 87
        yield "
";
        // line 88
        if (((($tmp = ($context["header"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp) && (($tmp = ($context["sticky"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp))) {
            // line 89
            yield "  <table class=\"gin--sticky-table-header syncscroll\" name=\"gin-sticky-header\" hidden>
    ";
            // line 90
            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar($macros["͜macros"]->getTemplateForMacro("macro_table_header", $context, 90, $this->getSourceContext())->macro_table_header(...[($context["header"] ?? null), ($context["fields"] ?? null), ($context["sticky"] ?? null)]));
            yield "
  </table>
";
        }
        // line 93
        yield "<div class=\"gin-table-scroll-wrapper gin-horizontal-scroll-shadow syncscroll\" name=\"gin-sticky-header\">
  <table";
        // line 94
        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, ($context["attributes"] ?? null), "addClass", [($context["classes"] ?? null)], "method", false, false, true, 94), "html", null, true);
        yield ">
    ";
        // line 95
        if ((($tmp = ($context["caption_needed"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 96
            yield "      <caption>
      ";
            // line 97
            if ((($tmp = ($context["caption"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                // line 98
                yield "        ";
                if ((isset($context["canvas_is_preview"]) && $context["canvas_is_preview"]) && \array_key_exists("canvas_uuid", $context)) {
                    if (\array_key_exists("canvas_slot_ids", $context) && \in_array("caption", $context["canvas_slot_ids"], TRUE)) {
                        yield \sprintf('<!-- canvas-slot-%s-%s/%s -->', "start", $context["canvas_uuid"], "caption");
                    } else {
                        yield \sprintf('<!-- canvas-prop-%s-%s/%s -->', "start", $context["canvas_uuid"], "caption");
                    }
                }
                yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["caption"] ?? null), "html", null, true);
                if ((isset($context["canvas_is_preview"]) && $context["canvas_is_preview"]) && \array_key_exists("canvas_uuid", $context)) {
                    if (\array_key_exists("canvas_slot_ids", $context) && \in_array("caption", $context["canvas_slot_ids"], TRUE)) {
                        yield \sprintf('<!-- canvas-slot-%s-%s/%s -->', "end", $context["canvas_uuid"], "caption");
                    } else {
                        yield \sprintf('<!-- canvas-prop-%s-%s/%s -->', "end", $context["canvas_uuid"], "caption");
                    }
                }
                yield "
      ";
            } else {
                // line 100
                yield "        ";
                if ((isset($context["canvas_is_preview"]) && $context["canvas_is_preview"]) && \array_key_exists("canvas_uuid", $context)) {
                    if (\array_key_exists("canvas_slot_ids", $context) && \in_array("title", $context["canvas_slot_ids"], TRUE)) {
                        yield \sprintf('<!-- canvas-slot-%s-%s/%s -->', "start", $context["canvas_uuid"], "title");
                    } else {
                        yield \sprintf('<!-- canvas-prop-%s-%s/%s -->', "start", $context["canvas_uuid"], "title");
                    }
                }
                yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["title"] ?? null), "html", null, true);
                if ((isset($context["canvas_is_preview"]) && $context["canvas_is_preview"]) && \array_key_exists("canvas_uuid", $context)) {
                    if (\array_key_exists("canvas_slot_ids", $context) && \in_array("title", $context["canvas_slot_ids"], TRUE)) {
                        yield \sprintf('<!-- canvas-slot-%s-%s/%s -->', "end", $context["canvas_uuid"], "title");
                    } else {
                        yield \sprintf('<!-- canvas-prop-%s-%s/%s -->', "end", $context["canvas_uuid"], "title");
                    }
                }
                yield "
      ";
            }
            // line 102
            yield "      ";
            if ((!Twig\Extension\CoreExtension::testEmpty(($context["summary_element"] ?? null)))) {
                // line 103
                yield "        ";
                if ((isset($context["canvas_is_preview"]) && $context["canvas_is_preview"]) && \array_key_exists("canvas_uuid", $context)) {
                    if (\array_key_exists("canvas_slot_ids", $context) && \in_array("summary_element", $context["canvas_slot_ids"], TRUE)) {
                        yield \sprintf('<!-- canvas-slot-%s-%s/%s -->', "start", $context["canvas_uuid"], "summary_element");
                    } else {
                        yield \sprintf('<!-- canvas-prop-%s-%s/%s -->', "start", $context["canvas_uuid"], "summary_element");
                    }
                }
                yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["summary_element"] ?? null), "html", null, true);
                if ((isset($context["canvas_is_preview"]) && $context["canvas_is_preview"]) && \array_key_exists("canvas_uuid", $context)) {
                    if (\array_key_exists("canvas_slot_ids", $context) && \in_array("summary_element", $context["canvas_slot_ids"], TRUE)) {
                        yield \sprintf('<!-- canvas-slot-%s-%s/%s -->', "end", $context["canvas_uuid"], "summary_element");
                    } else {
                        yield \sprintf('<!-- canvas-prop-%s-%s/%s -->', "end", $context["canvas_uuid"], "summary_element");
                    }
                }
                yield "
      ";
            }
            // line 105
            yield "      </caption>
    ";
        }
        // line 107
        yield "    ";
        if ((($tmp = ($context["header"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 108
            yield "      ";
            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar($macros["͜macros"]->getTemplateForMacro("macro_table_header", $context, 108, $this->getSourceContext())->macro_table_header(...[($context["header"] ?? null), ($context["fields"] ?? null)]));
            yield "
    ";
        }
        // line 110
        yield "    <tbody>
      ";
        // line 111
        $context['_parent'] = $context;
        $context['_seq'] = CoreExtension::ensureTraversable(($context["rows"] ?? null));
        foreach ($context['_seq'] as $context["_key"] => $context["row"]) {
            // line 112
            yield "        <tr";
            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, $context["row"], "attributes", [], "any", false, false, true, 112), "html", null, true);
            yield ">
          ";
            // line 113
            $context['_parent'] = $context;
            $context['_seq'] = CoreExtension::ensureTraversable(CoreExtension::getAttribute($this->env, $this->source, $context["row"], "columns", [], "any", false, false, true, 113));
            foreach ($context['_seq'] as $context["key"] => $context["column"]) {
                // line 114
                yield "            ";
                if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, $context["column"], "default_classes", [], "any", false, false, true, 114)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                    // line 115
                    yield "              ";
                    // line 116
                    $context["column_classes"] = ["views-field"];
                    // line 120
                    yield "              ";
                    $context['_parent'] = $context;
                    $context['_seq'] = CoreExtension::ensureTraversable(CoreExtension::getAttribute($this->env, $this->source, $context["column"], "fields", [], "any", false, false, true, 120));
                    foreach ($context['_seq'] as $context["_key"] => $context["field"]) {
                        // line 121
                        yield "                ";
                        $context["column_classes"] = Twig\Extension\CoreExtension::merge(($context["column_classes"] ?? null), [("views-field-" . $context["field"])]);
                        // line 122
                        yield "              ";
                    }
                    $_parent = $context['_parent'];
                    unset($context['_seq'], $context['_key'], $context['field'], $context['_parent']);
                    $context = array_intersect_key($context, $_parent);
                    $context += $_parent;
                    // line 123
                    yield "            ";
                }
                // line 124
                yield "            <td";
                yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source, $context["column"], "attributes", [], "any", false, false, true, 124), "addClass", [($context["column_classes"] ?? null)], "method", false, false, true, 124), "html", null, true);
                yield ">";
                // line 125
                if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, $context["column"], "wrapper_element", [], "any", false, false, true, 125)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                    // line 126
                    yield "<";
                    yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, $context["column"], "wrapper_element", [], "any", false, false, true, 126), "html", null, true);
                    yield ">
                ";
                    // line 127
                    $context['_parent'] = $context;
                    $context['_seq'] = CoreExtension::ensureTraversable(CoreExtension::getAttribute($this->env, $this->source, $context["column"], "content", [], "any", false, false, true, 127));
                    foreach ($context['_seq'] as $context["_key"] => $context["content"]) {
                        // line 128
                        yield "                  ";
                        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, $context["content"], "separator", [], "any", false, false, true, 128), "html", null, true);
                        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, $context["content"], "field_output", [], "any", false, false, true, 128), "html", null, true);
                        yield "
                ";
                    }
                    $_parent = $context['_parent'];
                    unset($context['_seq'], $context['_key'], $context['content'], $context['_parent']);
                    $context = array_intersect_key($context, $_parent);
                    $context += $_parent;
                    // line 130
                    yield "                </";
                    yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, $context["column"], "wrapper_element", [], "any", false, false, true, 130), "html", null, true);
                    yield ">";
                } else {
                    // line 132
                    $context['_parent'] = $context;
                    $context['_seq'] = CoreExtension::ensureTraversable(CoreExtension::getAttribute($this->env, $this->source, $context["column"], "content", [], "any", false, false, true, 132));
                    foreach ($context['_seq'] as $context["_key"] => $context["content"]) {
                        // line 133
                        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, $context["content"], "separator", [], "any", false, false, true, 133), "html", null, true);
                        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, $context["content"], "field_output", [], "any", false, false, true, 133), "html", null, true);
                    }
                    $_parent = $context['_parent'];
                    unset($context['_seq'], $context['_key'], $context['content'], $context['_parent']);
                    $context = array_intersect_key($context, $_parent);
                    $context += $_parent;
                }
                // line 136
                yield "            </td>
          ";
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['key'], $context['column'], $context['_parent']);
            $context = array_intersect_key($context, $_parent);
            $context += $_parent;
            // line 138
            yield "        </tr>
      ";
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_key'], $context['row'], $context['_parent']);
        $context = array_intersect_key($context, $_parent);
        $context += $_parent;
        // line 140
        yield "    </tbody>
  </table>
</div>
";
        $this->env->getExtension('\Drupal\Core\Template\TwigExtension')
            ->checkDeprecations($context, ["header", "responsive", "sticky", "_self", "fields", "attributes", "caption_needed", "caption", "title", "summary_element", "rows", "gin_is_sticky"]);        yield from [];
    }

    // line 46
    public function macro_table_header($header = null, $fields = null, $gin_is_sticky = null, ...$varargs): string|Markup
    {
        $macros = $this->macros;
        $context = [
            "header" => $header,
            "fields" => $fields,
            "gin_is_sticky" => $gin_is_sticky,
            "varargs" => $varargs,
        ] + $this->env->getGlobals();

        $blocks = [];

        return ('' === $tmp = implode('', iterator_to_array((function () use (&$context, $macros, $blocks) {
            // line 47
            yield "  <thead>
    <tr>
      ";
            // line 49
            $context['_parent'] = $context;
            $context['_seq'] = CoreExtension::ensureTraversable(($context["header"] ?? null));
            foreach ($context['_seq'] as $context["key"] => $context["column"]) {
                // line 50
                yield "        ";
                if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, $context["column"], "default_classes", [], "any", false, false, true, 50)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                    // line 51
                    yield "          ";
                    // line 52
                    $context["column_classes"] = ["views-field", ("views-field-" . (($_v0 =                     // line 54
($context["fields"] ?? null)) && is_array($_v0) || $_v0 instanceof ArrayAccess && in_array($_v0::class, CoreExtension::ARRAY_LIKE_CLASSES, true) ? ($_v0[(($_v1 = $context["key"]) instanceof \Stringable ? (string) $_v1 : $_v1)] ?? null) : CoreExtension::getAttribute($this->env, $this->source, ($context["fields"] ?? null), $context["key"], [], "array", false, false, true, 54))), ((CoreExtension::inFilter("bulk-form", (($_v2 =                     // line 55
($context["fields"] ?? null)) && is_array($_v2) || $_v2 instanceof ArrayAccess && in_array($_v2::class, CoreExtension::ARRAY_LIKE_CLASSES, true) ? ($_v2[(($_v3 = $context["key"]) instanceof \Stringable ? (string) $_v3 : $_v3)] ?? null) : CoreExtension::getAttribute($this->env, $this->source, ($context["fields"] ?? null), $context["key"], [], "array", false, false, true, 55)))) ? ("gin--sticky-bulk-select") : (""))];
                    // line 58
                    yield "        ";
                }
                // line 59
                yield "        <th";
                yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source, $context["column"], "attributes", [], "any", false, false, true, 59), "addClass", [($context["column_classes"] ?? null)], "method", false, false, true, 59), "setAttribute", ["scope", "col"], "method", false, false, true, 59), "html", null, true);
                yield ">
          ";
                // line 60
                if ((((($tmp = ($context["gin_is_sticky"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp) && CoreExtension::getAttribute($this->env, $this->source, ($context["fields"] ?? null), $context["key"], [], "array", true, true, true, 60)) && CoreExtension::inFilter("bulk-form", (($_v4 = ($context["fields"] ?? null)) && is_array($_v4) || $_v4 instanceof ArrayAccess && in_array($_v4::class, CoreExtension::ARRAY_LIKE_CLASSES, true) ? ($_v4[(($_v5 = $context["key"]) instanceof \Stringable ? (string) $_v5 : $_v5)] ?? null) : CoreExtension::getAttribute($this->env, $this->source, ($context["fields"] ?? null), $context["key"], [], "array", false, false, true, 60))))) {
                    // line 61
                    yield "            <input
              type=\"checkbox\"
              class=\"form-checkbox form-boolean form-boolean--type-checkbox\"
              title=\"";
                    // line 64
                    yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(t("Select all rows in this table"));
                    yield "\"
            />
          ";
                }
                // line 67
                if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, $context["column"], "wrapper_element", [], "any", false, false, true, 67)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                    // line 68
                    yield "<";
                    yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, $context["column"], "wrapper_element", [], "any", false, false, true, 68), "html", null, true);
                    yield ">";
                    // line 69
                    if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, $context["column"], "url", [], "any", false, false, true, 69)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                        // line 70
                        yield "<a href=\"";
                        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, $context["column"], "url", [], "any", false, false, true, 70), "html", null, true);
                        yield "\" title=\"";
                        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, $context["column"], "title", [], "any", false, false, true, 70), "html", null, true);
                        yield "\" rel=\"nofollow\">";
                        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, $context["column"], "content", [], "any", false, false, true, 70), "html", null, true);
                        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, $context["column"], "sort_indicator", [], "any", false, false, true, 70), "html", null, true);
                        yield "</a>";
                    } else {
                        // line 72
                        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, $context["column"], "content", [], "any", false, false, true, 72), "html", null, true);
                        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, $context["column"], "sort_indicator", [], "any", false, false, true, 72), "html", null, true);
                    }
                    // line 74
                    yield "</";
                    yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, $context["column"], "wrapper_element", [], "any", false, false, true, 74), "html", null, true);
                    yield ">";
                } else {
                    // line 76
                    if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, $context["column"], "url", [], "any", false, false, true, 76)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                        // line 77
                        yield "<a href=\"";
                        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, $context["column"], "url", [], "any", false, false, true, 77), "html", null, true);
                        yield "\" title=\"";
                        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, $context["column"], "title", [], "any", false, false, true, 77), "html", null, true);
                        yield "\" rel=\"nofollow\">";
                        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, $context["column"], "content", [], "any", false, false, true, 77), "html", null, true);
                        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, $context["column"], "sort_indicator", [], "any", false, false, true, 77), "html", null, true);
                        yield "</a>";
                    } else {
                        // line 79
                        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, $context["column"], "content", [], "any", false, false, true, 79), "html", null, true);
                        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, $context["column"], "sort_indicator", [], "any", false, false, true, 79), "html", null, true);
                    }
                }
                // line 82
                yield "</th>
      ";
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['key'], $context['column'], $context['_parent']);
            $context = array_intersect_key($context, $_parent);
            $context += $_parent;
            // line 84
            yield "    </tr>
  </thead>
";
            yield from [];
        })(), false))) ? '' : new Markup($tmp, $this->env->getCharset());
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return "themes/contrib/gin/templates/views/views-view-table.html.twig";
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
        return array (  375 => 84,  367 => 82,  362 => 79,  352 => 77,  350 => 76,  345 => 74,  341 => 72,  331 => 70,  329 => 69,  325 => 68,  323 => 67,  317 => 64,  312 => 61,  310 => 60,  305 => 59,  302 => 58,  300 => 55,  299 => 54,  298 => 52,  296 => 51,  293 => 50,  289 => 49,  285 => 47,  271 => 46,  262 => 140,  254 => 138,  246 => 136,  237 => 133,  233 => 132,  228 => 130,  217 => 128,  213 => 127,  208 => 126,  206 => 125,  202 => 124,  199 => 123,  192 => 122,  189 => 121,  184 => 120,  182 => 116,  180 => 115,  177 => 114,  173 => 113,  168 => 112,  164 => 111,  161 => 110,  155 => 108,  152 => 107,  148 => 105,  128 => 103,  125 => 102,  105 => 100,  85 => 98,  83 => 97,  80 => 96,  78 => 95,  74 => 94,  71 => 93,  65 => 90,  62 => 89,  60 => 88,  57 => 87,  54 => 45,  52 => 44,  49 => 43,  47 => 40,  46 => 39,  45 => 38,  44 => 35,);
    }

    public function getSourceContext(): Source
    {
        return new Source("", "themes/contrib/gin/templates/views/views-view-table.html.twig", "/var/www/html/themes/contrib/gin/templates/views/views-view-table.html.twig");
    }
    
    public function ensureSecurityChecked(): void
    {
        if ($this->sandbox->isSandboxed($this->source)) {
            $this->checkSecurity();
        }
    }
    
    public function checkSecurity()
    {
        static $tags = ["set" => 35, "import" => 44, "macro" => 46, "if" => 88, "for" => 111];
        static $filters = ["length" => 38, "escape" => 94, "merge" => 121, "t" => 64];
        static $functions = [];
        static $tests = [];

        try {
            $this->sandbox->checkSecurity(
                [0 => "set", 1 => "import", 2 => "macro", 3 => "if", 4 => "for"],
                [0 => "length", 1 => "escape", 2 => "merge", 3 => "t"],
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
