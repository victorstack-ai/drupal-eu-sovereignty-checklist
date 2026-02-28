<?php

declare(strict_types=1);

namespace Drupal\sovereignty_checklist\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\sovereignty_checklist\SovereigntyAuditorInterface;

/**
 * Default auditor: scans HTML for external assets.
 *
 * Scans for assets that may leak data outside the EU.
 *
 * Flags CDNs, third-party trackers, and embeds that are not allowlisted.
 */
class SovereigntyAuditor implements SovereigntyAuditorInterface {

  /**
   * Tags and attributes to scan for external URLs.
   *
   * @var array<string, string>
   */
  private const TAG_ATTR = [
    'link' => 'href',
    'script' => 'src',
    'img' => 'src',
    'iframe' => 'src',
  ];

  public function __construct(
    private readonly LoggerChannelInterface $logger,
    private readonly ?ConfigFactoryInterface $configFactory = NULL,
  ) {}

  /**
   * Audits rendered HTML for non-allowlisted external URLs.
   *
   * @param string $html
   *   Raw HTML (e.g. full page or fragment).
   *
   * @return array<int, array{tag: string, url: string, risk: string}>
   *   List of violations.
   */
  public function auditRenderedHtml(string $html): array {
    $violations = [];
    $dom = new \DOMDocument();
    @$dom->loadHTML($html, \LIBXML_NOERROR);

    foreach (self::TAG_ATTR as $tag => $attr) {
      foreach ($dom->getElementsByTagName($tag) as $element) {
        if (!$element->hasAttribute($attr)) {
          continue;
        }
        $url = $element->getAttribute($attr);
        if ($this->isExternal($url) && !$this->isAllowlisted($url)) {
          $violations[] = [
            'tag' => $tag,
            'url' => $url,
            'risk' => 'Data Leak / GDPR Violation',
          ];
        }
      }
    }

    return $violations;
  }

  /**
   * Whether the URL points outside the current host.
   *
   * @param string $url
   *   The URL to check.
   *
   * @return bool
   *   TRUE if external, FALSE otherwise.
   */
  private function isExternal(string $url): bool {
    $url = trim($url);
    if ($url === '' || str_starts_with($url, '#') || str_starts_with($url, '//')) {
      return FALSE;
    }
    if (str_starts_with($url, '/') && !str_starts_with($url, '//')) {
      return FALSE;
    }
    return str_contains($url, '://');
  }

  /**
   * Whether the URL's host is in the allowlist.
   *
   * @param string $url
   *   The URL to check.
   *
   * @return bool
   *   TRUE if allowlisted, FALSE otherwise.
   */
  private function isAllowlisted(string $url): bool {
    $host = parse_url($url, \PHP_URL_HOST);
    if ($host === FALSE || $host === NULL) {
      return FALSE;
    }
    $allowlist = $this->getAllowlistDomains();
    foreach ($allowlist as $domain) {
      if ($host === $domain || str_ends_with('.' . $host, '.' . $domain)) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Allowlisted domains from config (or defaults).
   *
   * @return list<string>
   *   List of allowlisted domains.
   */
  private function getAllowlistDomains(): array {
    if ($this->configFactory === NULL) {
      return ['europa.eu', 'analytics.europa.eu'];
    }
    $config = $this->configFactory->get('sovereignty_checklist.settings');
    $domains = $config->get('allowlist_domains');
    return is_array($domains) ? $domains : [];
  }

}
