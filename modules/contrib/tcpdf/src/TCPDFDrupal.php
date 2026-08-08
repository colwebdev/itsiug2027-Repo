<?php

namespace Drupal\tcpdf;

use function is_string;

/**
 * Extends \TCPDF and sets some default values.
 *
 * Do not create a new instance of this class manually.
 * Use tcpdf_get_instance().
 *
 * @see tcpdf_get_instance()
 */
class TCPDFDrupal extends \TCPDF {

  /**
   * Contains HTML or callback parameter.
   */
  protected array $drupalHeader = [
    'html' => NULL,
    'callback' => NULL,
  ];

  /**
   * Contains HTML or callback parameter.
   */
  protected array $drupalFooter = [
    'html' => NULL,
    'callback' => NULL,
  ];

  /**
   * Sets a bunch of commonly used properties in the TCPDF object.
   *
   * The properties set by this function can be safely changed
   * after calling the method. This method also lets the developer change
   * the header or footer of the PDF document without making an own class.
   *
   * @param array $options
   *   Associative array containing basic settings:
   *   - title: (string) Title of the document.
   *   - subject: (string) Subject of the document.
   *   - author: (string) Author of the document.
   *   - logo_path: (string) Path to a logo which is placed in the header.
   *   - keywords: (string) Comma separated list of keywords.
   *   - header: (array) Header configuration with:
   *     - html: (string) HTML code of the header.
   *     - callback: (callable|array) Function that generates the header.
   *   - footer: (array) Footer configuration with:
   *     - html: (string) HTML code of the footer.
   *     - callback: (callable|array) Function that generates the footer.
   */
  // @phpcs:ignore Drupal.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
  public function DrupalInitialize(array $options): void {
    // @phpstan-ignore globalDrupalDependencyInjection.useDependencyInjection
    $site_name = \Drupal::config('system.site')->get('name');
    $title = $options['title'] ?? $site_name;
    $author = $options['author'] ?? $site_name;
    $subject = $options['subject'] ?? $site_name;
    $keywords = $options['keywords'] ?? 'pdf, drupal';
    $this->drupalHeader = $options['header'] ?? $this->drupalHeader;
    $this->drupalFooter = $options['footer'] ?? $this->drupalFooter;

    // Set document information.
    $this->SetCreator(PDF_CREATOR);
    $this->SetAuthor($author);
    $this->SetTitle($title);
    $this->SetSubject($subject);
    $this->SetKeywords($keywords);

    // Set default header data.
    $this->setFooterFont([PDF_FONT_NAME_DATA, '', 6]);

    // Set default monospaced font.
    $this->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

    // Set margins.
    $this->SetMargins(PDF_MARGIN_LEFT, 28, PDF_MARGIN_RIGHT);
    $this->SetHeaderMargin(PDF_MARGIN_HEADER);
    $this->SetFooterMargin(PDF_MARGIN_FOOTER);

    // Set auto page breaks.
    $this->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

    // Set image scale factor.
    $this->setImageScale(PDF_IMAGE_SCALE_RATIO);

    // Set font.
    $this->SetFont('helvetica', '', 8);
    $this->AddPage();
  }

  /**
   * Sets the header of the document.
   */
  // @phpcs:ignore Drupal.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
  public function Header(): void {
    if (!$this->drupalGenRunningSection($this->drupalHeader)) {
      parent::Header();
    }
  }

  /**
   * Sets the footer of the document.
   */
  // @phpcs:ignore Drupal.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
  public function Footer(): void {
    if (!$this->drupalGenRunningSection($this->drupalFooter)) {
      parent::Footer();
    }
  }

  /**
   * Generates a header or footer for the pdf document.
   *
   * @param array $container
   *   An array containing either HTML content or callback information.
   *
   * @return bool
   *   TRUE if the section was generated, FALSE if no content was available.
   *
   * @see DrupalInitialize()
   */
  private function drupalGenRunningSection(array $container): bool {
    if (!empty($container['html'])) {
      $this->writeHTML($container['html']);
      return TRUE;
    }

    if (!empty($container['callback'])) {
      $that = &$this;
      if (is_array($container['callback'])) {
        $function = $container['callback']['function'] ?? NULL;
        if (is_string($function) && function_exists($function)) {
          call_user_func_array($function, [&$that, $container['callback']['context']]);
        }
      }
      elseif (function_exists($container['callback'])) {
        call_user_func($container['callback'], $that);
      }
      return TRUE;
    }
    return FALSE;
  }

}
