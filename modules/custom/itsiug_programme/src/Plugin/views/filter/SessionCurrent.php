<?php

declare(strict_types=1);

namespace Drupal\itsiug_programme\Plugin\views\filter;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\itsiug_programme\ProgrammeTime;
use Drupal\views\Attribute\ViewsFilter;
use Drupal\views\Plugin\views\filter\FilterPluginBase;
use Drupal\views\Views;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Filters sessions by their position relative to the current time.
 */
#[ViewsFilter("itsiug_session_current")]
class SessionCurrent extends FilterPluginBase {

  /**
   * The field holding the session start and end times.
   */
  protected const FIELD_NAME = 'field_session_times';

  protected ProgrammeTime $programmeTime;

  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = new static($configuration, $plugin_id, $plugin_definition);
    $instance->programmeTime = $container->get('itsiug_programme.time');
    $instance->entityTypeManager = $container->get('entity_type.manager');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['operator']['default'] = '=';
    $options['value']['default'] = 'now';
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function canExpose() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function operatorForm(&$form, FormStateInterface $form_state) {
    $form['operator'] = [];
  }

  /**
   * The selectable modes.
   */
  protected function modeOptions(): array {
    return [
      'now' => $this->t('On now — started and not yet finished'),
      'next' => $this->t('Up next — the next sessions due to start'),
      'rest_of_day' => $this->t('Still to come today'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function valueForm(&$form, FormStateInterface $form_state) {
    $form['value'] = [
      '#type' => 'radios',
      '#title' => $this->t('Sessions to show'),
      '#options' => $this->modeOptions(),
      '#default_value' => $this->value ?: 'now',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function adminSummary() {
    $options = $this->modeOptions();
    return (string) ($options[$this->value] ?? $this->value);
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $alias = $this->joinSessionTimes();
    $start = "$alias." . self::FIELD_NAME . '_value';
    $end = "$alias." . self::FIELD_NAME . '_end_value';
    $now = $this->programmeTime->getStorageNow();

    switch ($this->value) {
      case 'next':
        $next_start = $this->findNextStart($now);
        if ($next_start === NULL) {
          // Nothing left to start: return an empty result set.
          $this->query->addWhereExpression($this->options['group'], '1 = 0');
          return;
        }
        $this->query->addWhere($this->options['group'], $start, $next_start, '=');
        break;

      case 'rest_of_day':
        [, $day_end] = $this->programmeTime->getStorageDayBounds();
        $this->query->addWhere($this->options['group'], $start, $now, '>');
        $this->query->addWhere($this->options['group'], $start, $day_end, '<=');
        break;

      case 'now':
      default:
        $this->query->addWhere($this->options['group'], $start, $now, '<=');
        $this->query->addWhere($this->options['group'], $end, $now, '>=');
        break;
    }
  }

  /**
   * Joins the session times field table onto the query.
   *
   * @return string
   *   The table alias.
   */
  protected function joinSessionTimes(): string {
    $table = 'node__' . self::FIELD_NAME;
    $join = Views::pluginManager('join')->createInstance('standard', [
      'table' => $table,
      'field' => 'entity_id',
      'left_table' => $this->query->ensureTable('node_field_data'),
      'left_field' => 'nid',
      'type' => 'INNER',
      'extra' => [
        ['field' => 'deleted', 'value' => 0, 'numeric' => TRUE],
      ],
    ]);
    return $this->query->ensureTable($table, NULL, $join) ?: $table;
  }

  /**
   * Finds the earliest session start time after the given moment.
   */
  protected function findNextStart(string $now): ?string {
    $storage = $this->entityTypeManager->getStorage('node');
    $ids = $storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('type', 'session')
      ->condition('status', 1)
      ->condition(self::FIELD_NAME . '.value', $now, '>')
      ->sort(self::FIELD_NAME . '.value', 'ASC')
      ->range(0, 1)
      ->execute();

    if (!$ids) {
      return NULL;
    }
    $node = $storage->load(reset($ids));
    return $node?->get(self::FIELD_NAME)->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    // The result changes as the clock moves; render caching must not freeze it.
    return 0;
  }

}
