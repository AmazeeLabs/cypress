<?php

namespace Drupal\cypress\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Removes X-Drupal-Assertion-* headers since Cypress chokes on them.
 */
class RemoveErrorHeadersSubscriber implements EventSubscriberInterface {

  /**
   * Filter already registered headers.
   *
   * Remove all occurrences of X-Drupal-Assertion-* to make sure Cypress
   * doesn't exit with an parse error as soon as it receives the header.
   *
   * @param \Symfony\Component\HttpKernel\Event\FilterResponseEvent $event
   */
  public function onResponse(FilterResponseEvent $event) {
    if (DRUPAL_TEST_IN_CHILD_SITE) {
      $prefix = 'X-Drupal-Assertion-';
      $count = 0;
      foreach (headers_list() as $header) {
        if (substr($header, 0, strlen($prefix)) === $prefix) {
          header_remove($prefix . $count++);
        }
      }
    }
  }

  /**
   * {@inheritDoc}
   */
  public static function getSubscribedEvents() {
    return [
      KernelEvents::RESPONSE => [['onResponse']],
    ];
  }

}
