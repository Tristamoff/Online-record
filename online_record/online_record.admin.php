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

function online_record_holidays_settings($form, &$form_state) {
	
	if(isset($_GET['delete'])) {
		$q = db_delete('online_record_holidays')->condition('hid',(int) $_GET['delete'])->execute();
	}
	
	$form = array();
	$form['#suffix'] = '<div><small>Month.Day Description</small><br />';
	$q = db_select('online_record_holidays', 'h')->fields('h', array('hid', 'date','description'))->execute()->fetchAllAssoc('date');
	$holidays = array();
	foreach ($q as $hol) {
		$form['#suffix'] .= str_replace('_', '.', $hol->date) . ' <b>' . $hol->description . '</b> ' . l(t('Delete'), 'admin/config/content/online_record/holidays', array('query' => array('delete' => $hol->hid))) . '<br />';
	}
	$form['#suffix'] .= '</div>';
	
	$form['holiday'] = array(
		'#type' => 'fieldset',
		'#title' => t('The holiday'),
	);
	$form['holiday']['description'] = array(
		'#title' => t('Name of holiday'),
		'#type' => 'textfield',
	);
	$days = array();
	$i = 1;
	while ($i<=31) {
		$days[$i] = (string) $i;
		$i++;
	}
	$form['holiday']['day'] = array(
		'#title' => t('Day of holiday'),
		'#type' => 'select',
		'#options' => $days,
	);
	$form['holiday']['month'] = array(
		'#title' => t('Month of holiday'),
		'#type' => 'select',
		'#options' => array(
			1  => t('January'),
			2  => t('February'),
			3  => t('March'),
			4  => t('April'),
			5  => t('May'),
			6  => t('June'),
			7  => t('July'),
			8  => t('August'),
			9  => t('September'),
			10 => t('October'),
			11 => t('November'),
			12 => t('December')
		),
	);
	
	$form['submit'] = array(
		'#type' => 'submit',
		'#value' => t('Submit')
	);
	
	return $form;
}

function online_record_holidays_settings_submit($form, &$form_state){
	$day = $form_state['values']['day'];
	$month = $form_state['values']['month'];
	if ((int) $day < 10) {
		$day = '0' . (int) $day;
	}
	if ((int) $month < 10) {
		$month = '0' . (int) $month;
	}
	$q = db_insert('online_record_holidays')
	->fields(array('description' => $form_state['values']['description'], 'date' => $month . '_' . $day))
	->execute();
	drupal_set_message(t('Holiday added'));
}

