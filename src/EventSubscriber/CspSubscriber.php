<?php

namespace Drupal\attachinline\EventSubscriber;

use Drupal\Component\Utility\Crypt;
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
   * A nonce value for the current request.
   *
   * @var string|null
   */
  protected $nonce = NULL;

  /**
   * An array of directives to enable nonce for.
   *
   * @var string[]
   */
  protected $directiveNonceList = [];

  /**
   * {@inheritDoc}
   */
  public static function getSubscribedEvents() {
    if (!class_exists(CspEvents::class)) {
      return [];
    }

    $events[CspEvents::POLICY_ALTER] = [
      // Execute later in case other listeners add 'unsafe-inline'.
      ['onCspPolicyAlter', -10],
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
  public function registerHash(string $directive, string $hash) {
    $this->directiveHashList[$directive][] = "'" . $hash . "'";
  }

  /**
   * Retrieve the nonce value for the current request.
   *
   * The nonce will be generated when first requested.
   *
   * @return string
   *   The nonce value.
   */
  public function getNonce() {
    if (!$this->nonce) {
      // Nonce should be at least 128 bits.
      // @see https://www.w3.org/TR/CSP/#security-nonces
      $this->nonce = Crypt::randomBytesBase64(16);
    }

    return $this->nonce;
  }

  /**
   * Enable nonce for a directive.
   *
   * @param string $directive
   *   The directive to enable nonce for.
   */
  public function registerNonce(string $directive) {
    $this->directiveNonceList[] = $directive;
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
  public function onCspPolicyAlter(PolicyAlterEvent $event) {
    $policy = $event->getPolicy();

    foreach ($this->directiveHashList as $directive => $hashes) {
      static::fallbackAwareAppendIfEnabled($policy, $directive, $hashes);
    }

    foreach ($this->directiveNonceList as $directive) {
      static::fallbackAwareAppendIfEnabled($policy, $directive, ["'nonce-" . $this->getNonce() . "'"]);
    }
  }

  /**
   * Append to a directive if it or a fallback directive is enabled.
   *
   * If the specified directive is not enabled but one of its fallback
   * directives is, it will be initialized with the same value as the fallback
   * before appending the new value.
   *
   * If none of the specified directive's fallbacks are enabled, the directive
   * will not be enabled.
   *
   * @param \Drupal\csp\Csp $policy
   *   The policy to alter.
   * @param string $directive
   *   The directive name.
   * @param array $value
   *   The directive value.
   *
   * @see Csp::fallbackAwareAppendIfEnabled()
   */
  protected static function fallbackAwareAppendIfEnabled(Csp $policy, string $directive, array $value) {
    if ($policy->hasDirective($directive)) {
      if (!in_array(Csp::POLICY_UNSAFE_INLINE, $policy->getDirective($directive))) {
        $policy->appendDirective($directive, $value);
      }
      return;
    }

    // Duplicate the closest enabled fallback directive.
    foreach ($policy::getDirectiveFallbackList($directive) as $fallback) {
      if ($policy->hasDirective($fallback)) {
        $fallbackValue = $policy->getDirective($fallback);
        // Don't make any modifications if closest enabled fallback uses
        // 'unsafe-inline'.
        if (in_array(Csp::POLICY_UNSAFE_INLINE, $fallbackValue)) {
          return;
        }
        $policy->setDirective($directive, $fallbackValue);
        $policy->appendDirective($directive, $value);
        return;
      }
    }
  }

}
