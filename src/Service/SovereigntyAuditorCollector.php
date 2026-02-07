<?php

declare(strict_types=1);

namespace Drupal\sovereignty_checklist\Service;

use Drupal\sovereignty_checklist\SovereigntyAuditorInterface;

/**
 * Collects sovereignty auditors so other modules can register custom ones.
 *
 * Tag services with sovereignty_checklist.auditor to add auditors.
 */
final class SovereigntyAuditorCollector {

  /**
   * @var \Drupal\sovereignty_checklist\SovereigntyAuditorInterface[]
   */
  private array $auditors = [];

  public function addAuditor(SovereigntyAuditorInterface $auditor): void {
    $this->auditors[] = $auditor;
  }

  /**
   * Runs all registered auditors and merges violations.
   *
   * @return array<int, array{tag: string, url: string, risk: string}>
   */
  public function auditRenderedHtml(string $html): array {
    $violations = [];
    foreach ($this->auditors as $auditor) {
      foreach ($auditor->auditRenderedHtml($html) as $v) {
        $violations[] = $v;
      }
    }
    return $violations;
  }

}
