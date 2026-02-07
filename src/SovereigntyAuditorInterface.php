<?php

declare(strict_types=1);

namespace Drupal\sovereignty_checklist;

/**
 * Interface for services that audit content for sovereignty violations.
 *
 * Other modules can register custom auditors via the sovereignty_checklist.auditor tag.
 */
interface SovereigntyAuditorInterface {

  /**
   * Audits HTML for non-allowlisted external URLs (CDNs, trackers, embeds).
   *
   * @param string $html
   *   Raw HTML (e.g. full page or fragment).
   * @return array<int, array{tag: string, url: string, risk: string}>
   *   List of violations.
   */
  public function auditRenderedHtml(string $html): array;

}
