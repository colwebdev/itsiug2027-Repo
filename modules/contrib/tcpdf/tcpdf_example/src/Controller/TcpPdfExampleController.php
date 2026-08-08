<?php

declare(strict_types=1);

namespace Drupal\tcpdf_example\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Url;

final class TcpPdfExampleController extends ControllerBase {

  /**
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected RendererInterface $renderer;

  public function __construct(RendererInterface $renderer) {
    $this->renderer = $renderer;
  }

  public function downloadPdf(string $example_name): never {
    if ($example_name === 'simple') {
      $pdf = $this->generateSimplePdf();
    }
    else {
      throw new \InvalidArgumentException('Invalid example name.');
    }

    // Tell the browser that this is not an HTML file to show, but a PDF file
    // to download.
    header('Content-Type: application/pdf');
    header('Content-Length: ' . strlen($pdf));
    header('Content-Disposition: attachment; filename="mydocument.pdf"');
    print $pdf;
    exit;
  }

  public function exampleContents(): array {
    $page = [];

    $page['example_pdf_link'] = [
      '#title' => $this->t('Basic pdf'),
      '#type' => 'link',
      '#url' => Url::fromRoute('tcpdf_example.download_pdf', [
        'example_name' => 'simple',
      ]),
    ];

    return $page;
  }

  /**
   * Generates a PDF file using TCPDF module.
   *
   * @return string
   *   Binary string of the generated PDF.
   */
  protected function generateSimplePdf(): string {
    // Get the content we want to convert into PDF.
    $html_template = [
      '#theme' => 'tcpdf_example_basic_html',
    ];
    $html = $this->renderer->render($html_template);

    // Never make an instance of TCPDF or TCPDFDrupal classes manually.
    // Use tcpdf_get_instance() instead.
    $tcpdf = tcpdf_get_instance();
    /*
     * DrupalInitialize() is an extra method added to TCPDFDrupal
     * that initializes some TCPDF variables (like font types), and makes
     * possible to change the default header or footer without creating
     * a new class.
     */
    $tcpdf->DrupalInitialize([
      'footer' => [
        'html' => 'This is a test!! <em>Bottom of the page</em>',
      ],
      'header' => [
        'callback' => [
          'function' => 'tcpdf_example_default_header',
          // You can pass extra data to your callback.
          'context' => [
            'welcome_message' => 'Hello, tcpdf example!',
          ],
        ],
      ],
    ]);
    // Insert the content. Note that DrupalInitialize automatically
    // adds the first page to the PDF document.
    $tcpdf->writeHTML((string) $html);

    return $tcpdf->Output('', 'S');
  }

}
