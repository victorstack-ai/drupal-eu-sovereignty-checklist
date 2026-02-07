<?php

declare(strict_types=1);

namespace Drupal\sovereignty_checklist\EventSubscriber;

use Drupal\sovereignty_checklist\Service\SovereigntyAuditorCollector;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Runs sovereignty audit on HTML responses and logs violations.
 *
 * Implements the flow: Request → Page Render → Sovereignty Check → Log.
 */
final class SovereigntyResponseSubscriber implements EventSubscriberInterface {

  /**
   * Constructs a new SovereigntyResponseSubscriber object.
   *
   * @param \Drupal\sovereignty_checklist\Service\SovereigntyAuditorCollector $auditorCollector
   *   The auditor collector.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger service.
   */
  public function __construct(
    private readonly SovereigntyAuditorCollector $auditorCollector,
    private readonly LoggerInterface $logger,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      KernelEvents::RESPONSE => ['onResponse', -100],
    ];
  }

  /**
   * Runs the audit on the response.
   *
   * @param \Symfony\Component\HttpKernel\Event\ResponseEvent $event
   *   The response event.
   */
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
