<?php

declare(strict_types=1);

namespace Drupal\itsiug_programme;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Session\AccountInterface;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Supplies "now" for the programme, with a testable override.
 *
 * Users with the "override programme time" permission may pass
 * ?programme_time=2027-09-14T10:30 to preview the programme at another moment.
 */
final class ProgrammeTime {

  public const OVERRIDE_PARAMETER = 'programme_time';

  public function __construct(
    protected TimeInterface $time,
    protected RequestStack $requestStack,
    protected AccountInterface $currentUser,
    protected ConfigFactoryInterface $configFactory,
  ) {}

  /**
   * The current moment, in the site's timezone.
   */
  public function getNow(): DrupalDateTime {
    $override = $this->getOverride();
    if ($override) {
      return $override;
    }
    $now = DrupalDateTime::createFromTimestamp($this->time->getRequestTime());
    $now->setTimezone(new \DateTimeZone($this->getSiteTimezone()));
    return $now;
  }

  /**
   * The current moment formatted for comparison against stored date values.
   */
  public function getStorageNow(): string {
    $now = $this->getNow();
    $now->setTimezone(new \DateTimeZone(DateTimeItemInterface::STORAGE_TIMEZONE));
    return $now->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT);
  }

  /**
   * Start and end of the current day, formatted for stored date values.
   *
   * @return string[]
   *   The day's first and last moment, keyed 0 and 1.
   */
  public function getStorageDayBounds(): array {
    $timezone = new \DateTimeZone($this->getSiteTimezone());
    $storage_timezone = new \DateTimeZone(DateTimeItemInterface::STORAGE_TIMEZONE);

    $start = clone $this->getNow();
    $start->setTime(0, 0, 0);
    $end = clone $start;
    $end->setTime(23, 59, 59);

    $start->setTimezone($storage_timezone);
    $end->setTimezone($storage_timezone);

    return [
      $start->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT),
      $end->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT),
    ];
  }

  /**
   * Whether the current request is previewing a different moment.
   */
  public function isOverridden(): bool {
    return $this->getOverride() !== NULL;
  }

  /**
   * Reads and validates the time override from the request.
   */
  protected function getOverride(): ?DrupalDateTime {
    if (!$this->currentUser->hasPermission('override programme time')) {
      return NULL;
    }
    $request = $this->requestStack->getCurrentRequest();
    $value = $request?->query->get(self::OVERRIDE_PARAMETER);
    if (!is_string($value) || $value === '') {
      return NULL;
    }
    try {
      $override = new DrupalDateTime($value, new \DateTimeZone($this->getSiteTimezone()));
    }
    catch (\Exception) {
      return NULL;
    }
    return $override->hasErrors() ? NULL : $override;
  }

  /**
   * The site default timezone.
   */
  protected function getSiteTimezone(): string {
    return $this->configFactory->get('system.date')->get('timezone.default') ?: date_default_timezone_get();
  }

}
