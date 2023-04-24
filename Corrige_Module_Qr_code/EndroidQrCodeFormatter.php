<?php

namespace Drupal\endroid_qr_code\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'endroid_qr_code_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "endroid_qr_code_formatter",
 *   label = @Translation("QR Code"),
 *   field_types = {
 *     "string",
 *     "endroid_qr_code"
 *   }
 * )
 */
class EndroidQrCodeFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'width' => 200,
      'height' => 150,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = [];

    $elements['width'] = [
      '#title' => $this->t('Width'),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('width'),
      '#required' => TRUE,
      '#size' => 5,
      '#field_suffix' => 'px',
    ];

    $elements['height'] = [
      '#title' => $this->t('Height'),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('height'),
      '#required' => TRUE,
      '#size' => 5,
      '#field_suffix' => 'px',
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $summary[] = $this->t('Width: @widthpx', ['@width' => $this->getSetting('width')]);
    $summary[] = $this->t('Height: @heightpx', ['@height' => $this->getSetting('height')]);
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    foreach ($items as $delta => $item) {
      $value = $item->value;
      if (!empty($value)) {
        $url = 'https://chart.googleapis.com/chart?chs=' . $this->getSetting('width') . 'x' . $this->getSetting('height') . '&cht=qr&chl=' . urlencode($value);
        $elements[$delta] = [
          '#theme' => 'image',
          '#uri' => $url,
          '#width' => $this->getSetting('width'),
          '#height' => $this->getSetting('height'),
          '#alt' => $this->t('QR code for @value', ['@value' => $value]),
        ];
      }
    }
    return $elements;
  }

}
