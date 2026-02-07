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
   * The registered auditors.
   *
   * @var \Drupal\sovereignty_checklist\SovereigntyAuditorInterface[]
   */
  private array $auditors = [];

  /**
   * Adds an auditor.
   *
   * @param \Drupal\sovereignty_checklist\SovereigntyAuditorInterface $auditor
   *   The auditor to add.
   */
  public function addAuditor(SovereigntyAuditorInterface $auditor): void {
    $this->auditors[] = $auditor;
  }

  /**
   * Runs all registered auditors and merges violations.
   *
   * @param string $html
   *   Raw HTML (e.g. full page or fragment).
   *
   * @return array<int, array{tag: string, url: string, risk: string}>
   *   Merged list of violations.
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
