<?php

namespace Drupal\attachinline\Render;

use Drupal\attachinline\Asset\AttachedAssets;
use Drupal\Core\Form\EnforcedResponseException;
use Drupal\Core\Render\AttachmentsInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Render\HtmlResponse;
use Drupal\Core\Render\HtmlResponseAttachmentsProcessor as CoreHtmlResponseAttachmentsProcessor;

class HtmlResponseAttachmentsProcessor extends CoreHtmlResponseAttachmentsProcessor {

  /**
   * {@inheritDoc}
   */
  public function processAttachments(AttachmentsInterface $response) {
    // @todo Convert to assertion once https://www.drupal.org/node/2408013 lands
    if (!$response instanceof HtmlResponse) {
      throw new \InvalidArgumentException('\Drupal\Core\Render\HtmlResponse instance expected.');
    }

    // First, render the actual placeholders; this may cause additional
    // attachments to be added to the response, which the attachment
    // placeholders rendered by renderHtmlResponseAttachmentPlaceholders() will
    // need to include.
    //
    // @todo Exceptions should not be used for code flow control. However, the
    //   Form API does not integrate with the HTTP Kernel based architecture of
    //   Drupal 8. In order to resolve this issue properly it is necessary to
    //   completely separate form submission from rendering.
    //   @see https://www.drupal.org/node/2367555
    try {
      $response = $this->renderPlaceholders($response);
    }
    catch (EnforcedResponseException $e) {
      return $e->getResponse();
    }

    // Get a reference to the attachments.
    $attached = $response->getAttachments();

    // Send a message back if the render array has unsupported #attached types.
    $unsupported_types = array_diff(
      array_keys($attached),
      ['html_head', 'feed', 'html_head_link', 'http_header', 'library', 'html_response_attachment_placeholders', 'placeholders', 'drupalSettings', 'js']
    );
    if (!empty($unsupported_types)) {
      throw new \LogicException(sprintf('You are not allowed to use %s in #attached.', implode(', ', $unsupported_types)));
    }

    // If we don't have any placeholders, there is no need to proceed.
    if (!empty($attached['html_response_attachment_placeholders'])) {
      // Get the placeholders from attached and then remove them.
      $attachment_placeholders = $attached['html_response_attachment_placeholders'];
      unset($attached['html_response_attachment_placeholders']);

      $assets = AttachedAssets::createFromRenderArray(['#attached' => $attached]);
      // Take Ajax page state into account, to allow for something like
      // Turbolinks to be implemented without altering core.
      // @see https://github.com/rails/turbolinks/
      $ajax_page_state = $this->requestStack->getCurrentRequest()->get('ajax_page_state');
      $assets->setAlreadyLoadedLibraries(isset($ajax_page_state) ? explode(',', $ajax_page_state['libraries']) : []);
      $variables = $this->processAssetLibraries($assets, $attachment_placeholders);
      // $variables now contains the markup to load the asset libraries. Update
      // $attached with the final list of libraries and JavaScript settings, so
      // that $response can be updated with those. Then the response object will
      // list the final, processed attachments.
      $attached['library'] = $assets->getLibraries();
      $attached['drupalSettings'] = $assets->getSettings();

      // Since we can only replace content in the HTML head section if there's a
      // placeholder for it, we can safely avoid processing the render array if
      // it's not present.
      if (!empty($attachment_placeholders['head'])) {
        // 'feed' is a special case of 'html_head_link'. We process them into
        // 'html_head_link' entries and merge them.
        if (!empty($attached['feed'])) {
          $attached = BubbleableMetadata::mergeAttachments(
            $attached,
            $this->processFeed($attached['feed'])
          );
          unset($attached['feed']);
        }
        // 'html_head_link' is a special case of 'html_head' which can be present
        // as a head element, but also as a Link: HTTP header depending on
        // settings in the render array. Processing it can add to both the
        // 'html_head' and 'http_header' keys of '#attached', so we must address
        // it before 'html_head'.
        if (!empty($attached['html_head_link'])) {
          // Merge the processed 'html_head_link' into $attached so that its
          // 'html_head' and 'http_header' values are present for further
          // processing.
          $attached = BubbleableMetadata::mergeAttachments(
            $attached,
            $this->processHtmlHeadLink($attached['html_head_link'])
          );
          unset($attached['html_head_link']);
        }

        // Now we can process 'html_head', which contains both 'feed' and
        // 'html_head_link'.
        if (!empty($attached['html_head'])) {
          $variables['head'] = $this->processHtmlHead($attached['html_head']);
        }
      }

      // Now replace the attachment placeholders.
      $this->renderHtmlResponseAttachmentPlaceholders($response, $attachment_placeholders, $variables);
    }

    // Set the HTTP headers and status code on the response if any bubbled.
    if (!empty($attached['http_header'])) {
      $this->setHeaders($response, $attached['http_header']);
    }

    // AttachmentsResponseProcessorInterface mandates that the response it
    // processes contains the final attachment values.
    $response->setAttachments($attached);

    return $response;
  }
}
