<?php

namespace Drupal\attachinline\EventSubscriber;

use Drupal\csp\Csp;
use Drupal\csp\CspEvents;
use Drupal\csp\Event\PolicyAlterEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Act on CSP events.
 */
class CspSubscriber implements EventSubscriberInterface {

  /**
   * An array of hashes keyed by directive name.
   *
   * @var array[]
   */
  protected $directiveHashList = [];

  /**
   * {@inheritDoc}
   */
  public static function getSubscribedEvents() {
    $events[CspEvents::POLICY_ALTER] = [
      // Execute later in case other listeners add 'unsafe-inline'.
      ['onCspAlter', -10],
    ];
    return $events;
  }

  /**
   * Register a hash to be applied to the current request's CSP policies.
   *
   * @param string $directive
   *   The directive name.
   * @param string $hash
   *   The hash value.
   */
  public function registerHash($directive, $hash) {
    $this->directiveHashList[$directive][] = "'" . $hash . "'";
  }

  /**
   * Add hashes to the provided policy.
   *
   * Hashes are only added if the corresponding directive is enabled in module
   * configuration.
   *
   * Only add hashes if the policy does not already include 'unsafe-inline',
   * otherwise non-hashed inline JS may be unexpectedly blocked.
   *
   * @param \Drupal\csp\Event\PolicyAlterEvent $event
   *   The Policy Alter Event.
   */
  public function onCspAlter(PolicyAlterEvent $event) {
    $policy = $event->getPolicy();
    foreach ($this->directiveHashList as $directive => $hashes) {
      if (
        $policy->hasDirective($directive)
        &&
        !in_array(Csp::POLICY_UNSAFE_INLINE, $policy->getDirective($directive))
      ) {
        $policy->appendDirective($directive, $hashes);
      }
    }
  }

}
