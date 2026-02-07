<?php

declare(strict_types=1);

namespace Drupal\sovereignty_checklist\EventSubscriber;

use Drupal\sovereignty_checklist\Service\SovereigntyAuditorCollector;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Runs sovereignty audit on HTML responses and logs violations.
 *
 * Implements the flow: Request → Page Render → Sovereignty Check → Log Violation.
 */
final class SovereigntyResponseSubscriber implements EventSubscriberInterface {

  public function __construct(
    private readonly SovereigntyAuditorCollector $auditorCollector,
    private readonly \Psr\Log\LoggerInterface $logger,
  ) {}

  public static function getSubscribedEvents(): array {
    return [
      KernelEvents::RESPONSE => ['onResponse', -100],
    ];
  }

  public function onResponse(ResponseEvent $event): void {
    $response = $event->getResponse();
    $contentType = $response->headers->get('Content-Type', '');
    if (strpos($contentType, 'text/html') === FALSE) {
      return;
    }
    $html = (string) $response->getContent();
    if ($html === '') {
      return;
    }
    $violations = $this->auditorCollector->auditRenderedHtml($html);
    if ($violations !== []) {
      foreach ($violations as $v) {
        $this->logger->warning('Sovereignty violation: @tag @url — @risk', [
          '@tag' => $v['tag'],
          '@url' => $v['url'],
          '@risk' => $v['risk'],
        ]);
      }
    }
  }

}
