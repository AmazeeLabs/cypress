<?php

namespace Drupal\cypress;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class EventSubscriber implements EventSubscriberInterface {

  /**
   * @var \Symfony\Component\HttpFoundation\Session\SessionInterface
   */
  protected $session;

  /**
   * EventSubscriber constructor.
   *
   * @param \Symfony\Component\HttpFoundation\Session\SessionInterface $session
   */
  public function __construct(SessionInterface $session) {
    $this->session = $session;
  }

  /**
   * Write all "X-CYPRESS-*" headers to the session.
   */
  public function onRequest(GetResponseEvent $event) {
    /** @var \Symfony\Component\HttpFoundation\Session\Session $session */
    $request = $event->getRequest();
    foreach (['USER', 'LANGUAGE', 'WORKSPACE', 'TOOLBAR'] as $key) {
      if ($request->headers->has('X-CYPRESS-' . $key)) {
        $this->session->set('CYPRESS_' . $key, $request->headers->get('X-CYPRESS-' . $key));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // For this example I am using KernelEvents constants (see below a full list).
    $events[KernelEvents::REQUEST][] = ['onRequest'];
    return $events;
  }

}
