<?php

namespace Drupal\calendar_view\Plugin\views\pager;

use Drupal\calendar_view\Plugin\views\style\CalendarViewInterface;
use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\pager\None as BasePager;
use Drupal\views\ViewExecutable;

/**
 * Defines a common class for CalendarView style plugins.
 */
abstract class CalendarViewPagerBase extends BasePager implements CalendarViewPagerInterface {

  /**
   * {@inheritDoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['label_format'] = ['default' => $this->getDefaultLabelFormat()];
    $options['use_previous_next'] = ['default' => TRUE];
    $options['display_reset'] = ['default' => TRUE];

    return $options;
  }

  /**
   * Provide the default form for setting options.
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['offset']['#access'] = FALSE;

    $form['label_format'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Date label format'),
      '#description' => $this->t('Use any valid PHP date format.') . '<br>' .
      $this->t('Example: `F Y` for `December 2032` or `m` for `12`.'),
      '#default_value' => $this->options['label_format'] ?? 'F',
    ];

    $form['use_previous_next'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use "Previous/Next" label instead of the date format'),
      '#default_value' => $this->options['use_previous_next'] ?? TRUE,
    ];

    $form['display_reset'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display "Back to today" reset button'),
      '#default_value' => $this->options['display_reset'] ?? TRUE,
    ];
  }

  /**
   * Force pager display.
   */
  public function usePager() {
    return TRUE;
  }

  /**
   * Perform any needed actions just before rendering.
   */
  public function preRender(&$result) {
    // Allow other plugins to use/alter timestamp.
    $this->view->calendar_timestamp = $this->getCalendarTimestamp();
  }

  /**
   * {@inheritdoc}
   */
  public function render($input) {
    // Hide if something wrong in calendar style.
    if (isset($this->view->calendar_error)) {
      return;
    }

    $selected_timestamp = $this->getCalendarTimestamp();

    $now = new \DateTime();
    $now->setTimestamp($selected_timestamp);

    $previous = $this->getDatetimePrevious($now);
    $next = $this->getDatetimeNext($now);

    

    $input['previous'] = $previous->getTimestamp();

    $input['current'] = $now->getTimestamp();
    $input['next'] = $next->getTimestamp();



    $date_formatter = \Drupal::service('date.formatter');
    $date_format = 'custom';
    $date_pattern = $this->options['label_format'] ?? 'F';

    return [
      '#theme' => $this->themeFunctions(),
      '#element' => Html::getUniqueId($this->getPluginId()),
      '#tags' => [
        0 => NULL,
        1 => $date_formatter->format($input['previous'], $date_format, $date_pattern),
        2 => $date_formatter->format($input['current'], $date_format, $date_pattern),
        3 => $date_formatter->format($input['next'], $date_format, $date_pattern),
      ],
      '#parameters' => $input + [
        'date_format' => $date_format,
        'date_pattern' => $date_pattern,
        'use_previous_next' => $this->options['use_previous_next'] ?? TRUE,
        'display_reset' => $this->options['display_reset'] ?? TRUE,
        'pager_type' => $this->getPluginId(),
      ],
      '#view' => $this->view,
      '#route_name' => !empty($this->view->live_preview) ? '<current>' : '<none>',
    ];
  }

  /**
   * Get the default format for the navigation links' label.
   *
   * @return string
   *   A PHP datetime format as  a string.
   */
  public function getDefaultLabelFormat(): string {
    return 'F';
  }

  /**
   * Retrieve the calendar date from the CalendarView style plugin.
   *
   * Returns the current time by default.
   *
   * @return string
   *   A UNIX timestamp.
   */
  public function getCalendarTimestamp(): string {
    if (!$this->view instanceof ViewExecutable) {
      return date('U');
    }

    $style = $this->view->getStyle();
    if (!$style instanceof CalendarViewInterface) {
      return date('U');
    }

    return $style->getCalendarTimestamp();
  }

  /**
   * Get the previous link datetime.
   *
   * @param \Datetime $now
   *   A given datetime object representing the current timestamp.
   *
   * @return \Datetime
   *   A datetime object representing the previous link's timestamp.
   */
  public function getDatetimePrevious(\Datetime $now): \Datetime {
    $previous = clone $now;
    $month = $now->format('m');
    $year = $now->format('Y');
    
    // Cas spécial : passage de janvier à décembre
    if ($month == '01') {
        $month = '12';
        $year--;
    } else {
        $month--;
    }
    
    $previous->setDate($year, $month, '01');
    $previous->modify('last day of this month');
    $previous->setTime(23, 59, 59);
    
    return $previous;
}



  /**
   * Get the next link datetime.
   *
   * @param \Datetime $now
   *   A given datetime object representing the current timestamp.
   *
   * @return \Datetime
   *   A datetime object representing the next link's timestamp.
   */
  public function getDatetimeNext(\Datetime $now): \Datetime {
    $date = clone $now;
    $date->modify('+1 month');
    $date->modify('first day of this month');
    $date->setTime(0, 0, 0);
    return $date;
  }

}