<?php

declare(strict_types=1);

namespace Drupal\Tests\sovereignty_checklist\Unit\Service;

use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\sovereignty_checklist\Service\SovereigntyAuditor;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Drupal\sovereignty_checklist\Service\SovereigntyAuditor
 * @group sovereignty_checklist
 */
final class SovereigntyAuditorTest extends TestCase {

  /**
   * The mock logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  private $logger;

  /**
   * The mock config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  private $configFactory;

  /**
   * The mock config object.
   *
   * @var \Drupal\Core\Config\Config|\PHPUnit\Framework\MockObject\MockObject
   */
  private $config;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->logger = $this->createMock(LoggerChannelInterface::class);
    $this->configFactory = $this->createMock(ConfigFactoryInterface::class);
    $this->config = $this->createMock(Config::class);

    $this->configFactory->method('get')
      ->with('sovereignty_checklist.settings')
      ->willReturn($this->config);
  }

  /**
   * Tests the auditRenderedHtml method.
   *
   * @covers ::auditRenderedHtml
   * @dataProvider htmlProvider
   */
  public function testAuditRenderedHtml(string $html, array $allowlist, int $expectedCount): void {
    $this->config->method('get')
      ->with('allowlist_domains')
      ->willReturn($allowlist);

    $auditor = new SovereigntyAuditor($this->logger, $this->configFactory);
    $violations = $auditor->auditRenderedHtml($html);

    $this->assertCount($expectedCount, $violations);
  }

  /**
   * Data provider for testAuditRenderedHtml.
   *
   * @return array
   *   Test cases.
   */
  public static function htmlProvider(): array {
    return [
      'no_external' => [
        '<div><p>Hello</p></div>',
        [],
        0,
      ],
      'local_script' => [
        '<script src="/js/local.js"></script>',
        [],
        0,
      ],
      'external_script_no_allowlist' => [
        '<script src="https://cdn.example.com/lib.js"></script>',
        [],
        1,
      ],
      'external_script_allowlisted' => [
        '<script src="https://cdn.example.com/lib.js"></script>',
        ['example.com'],
        0,
      ],
      'mixed_assets' => [
        '<div>
          <img src="https://tracker.com/pixel.png">
          <iframe src="https://youtube.com/embed/123"></iframe>
          <link href="https://fonts.googleapis.com/css" rel="stylesheet">
          <script src="https://analytics.europa.eu/piwik.js"></script>
        </div>',
        ['europa.eu'],
        3,
      ],
    ];
  }

}
