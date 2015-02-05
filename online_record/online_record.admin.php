<?php
/**
 * Created by PhpStorm.
 * User: rusakov
 * Date: 30.01.15
 * Time: 18:05
 */

function online_record_settings($form, &$form_state){
  $defaults = array();

  $def_hours = array();
  $def_hours[] = 'YES|Monday|9:00-18:00';
  $def_hours[] = 'YES|Tuesday|9:00-18:00';
  $def_hours[] = 'YES|Wednesday|9:00-18:00';
  $def_hours[] = 'YES|Thursday|9:00-18:00';
  $def_hours[] = 'YES|Friday|9:00-18:00';
  $def_hours[] =  'NO|Saturday|9:00-18:00';
  $def_hours[] =  'NO|Sunday|9:00-18:00';

  // Праздники
  $def_hours[] =  'NO|Holiday|9:00-12:00,13:00-18:00';

  $defaults['clocks'] = $def_hours;

  $defaults['or_def_minutes'] = 20;

  $defaults['or_def_working'] = 1;

  $defaults['or_def_lunch'] = '12:00-13:00';

  $default_settings = variable_get('or_default_settings', $defaults);

  $form = array();

  $form['or_def_clock'] = array(
    '#type' => 'fieldset',
    '#title' => t('Default work hours.'),
  );

  $i = 0;
  while ($i < 8) {
    $form['or_def_clock']['day_' . $i] = array(
      '#type' => 'textfield',
      '#default_value' => $default_settings['clocks'][$i],
      '#attributes' => array(
        'class' => array('real-data'),
        'data-day' => array($i)
      ),
    );

    $def_arr = explode('|', $def_hours[$i]);

    $form['or_def_clock']['fake_' . $i] = array(
      '#type' => 'fieldset',
      '#title' => t('Day settings %day.', array('%day' => t($def_arr[1]))),
      '#attributes' => array(
        'data-day-name' => array($def_arr[1]),
        'data-day-index' => array($i)
      ),
    );
    $form['or_def_clock']['fake_' . $i]['work_' . $i] = array(
      '#type' => 'checkbox',
      '#title' => t('Workday'),
      '#attributes' => array(
        'class' => array('work-enable'),
        'data-day-index' => array($i)
      ),
    );
    $form['or_def_clock']['fake_' . $i]['times_' . $i] = array(
      '#type' => 'textfield',
      '#title' => 'Time settings',
      '#attributes' => array(
        'class' => array('work-time'),
        'data-day-index' => array($i)
      ),
    );

    $i++;
  }

  $form['or_def_working'] = array(
    '#type' => 'checkbox',
    '#title' => t('Working by default'),
    '#default_value' => $default_settings['or_def_working']
  );

  $form['or_def_minutes'] = array(
    '#type' => 'textfield',
    '#title' => t('Default minutes for one task'),
    '#default_value' => $default_settings['or_def_minutes']
  );

  $form['or_def_lunch'] = array(
    '#type' => 'textfield',
    '#title' => t('Default lunch interval'),
    '#default_value' => $default_settings['or_def_lunch']
  );

  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => 'Submit'
  );

  $form['#attached']['js'] = array(
    drupal_get_path('module', 'online_record') . '/theme/admin_settings.js',
  );
  $form['#attached']['css'] = array(
    drupal_get_path('module', 'online_record') . '/theme/admin_settings.css',
  );

  return $form;
}

function online_record_settings_validate($form, &$form_state){
  $minutes = (int) $form_state['values']['or_def_minutes'];
  if ($minutes <= 0) {
    form_set_error('or_def_minutes', 'Default minutes for one task must be more than 0.');
  }

  $day = 0;
  $day_names = array();
  $day_names[] = 'Monday';
  $day_names[] = 'Tuesday';
  $day_names[] = 'Wednesday';
  $day_names[] = 'Thursday';
  $day_names[] = 'Friday';
  $day_names[] = 'Saturday';
  $day_names[] = 'Sunday';

  $day_names[] = 'Holiday';
  while($day < 8) {
    //YES|Monday|9:00-12:00,13:00-18:00
    //$one_day = $form_state['values']['day_' . $day];
    $day_arr = explode('|', $form_state['values']['day_' . $day]);
    if (!in_array($day_arr[0], array('YES', 'NO'))){
      form_set_error('work_' . $day, t('Enable or disable day %day', array('%day' => t($day_names[$day]))));
    }

    if (!in_array($day_arr[1], $day_names)) {
      form_set_error('day_' . $day, t('Incorrect day name. Day number %num value:%val. Need set to %need', array('%num' => $day, '%val' => $day_arr[1], '%need' => t($day_names[$day]))));
    }

    $intervals = explode(',', $day_arr[2]);
    foreach ($intervals as $interval) {
      //$interval=9:00-12:00
      if (strlen($interval) > 11) {
        form_set_error('times_' . $day, t('Long interval, 11 symbols maximum. Example 11:30-14:00'));
      }
      else {
        $interval_parts = explode('-', $interval);
        if (count($interval_parts) != 2) {
          form_set_error('times_' . $day, t('Interval must consist of two parts. Example 8:00-10:00'));
        } else {
          foreach ($interval_parts as $interval_part) {
            $ham = explode(':', $interval_part);// 9 and 00
            if ((int)$ham[0] > 24) {
              form_set_error('times_' . $day, t('Value of hours must be in interval between 0-24'));
            }
            if ((int)$ham[1] > 59) {
              form_set_error('times_' . $day, t('Value of minutes must be in interval between 0-59'));
            }
          }

        }
      }
    }
    $day++;
  }
}

function online_record_settings_submit($form, &$form_state){
  //dpm($form_state['values']);
  $settings = array();
  $i = 0;
  while ($i < 8) {
    $settings['clocks'][$i] = $form_state['values']['day_' . $i];
    $i++;
  }
  $settings['or_def_working'] = $form_state['values']['or_def_working'];
  $settings['or_def_minutes'] = $form_state['values']['or_def_minutes'];
  $settings['or_def_lunch'] = $form_state['values']['or_def_lunch'];
  variable_set('or_default_settings', $settings);
  drupal_set_message('Online record settings saved');
}