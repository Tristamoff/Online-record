<?php
/**
 * Created by PhpStorm.
 * User: User
 * Date: 30.01.2015
 * Time: 22:32
 */

function online_record_get_days($nid, $record_nid, $plus = 0) {
  if ((int)$nid == 0) {
    print t('Change a specialist');
  } else {
    $resp = array('rasp' => array());
    $start = array();
    $end = array();
    $q = db_select('node', 'n');
    $q->condition('n.nid', $nid);
    $q->leftJoin('field_revision_or_opening_hours', 'oh', 'oh.revision_id=n.vid');
    $q->fields('oh', array('or_opening_hours_value'));
    $res = $q->execute();
    while($rec = $res->fetchAssoc()){
      $day = explode('|', $rec['or_opening_hours_value']);
      $resp['rasp'][$day[1]] = $day;
      $times = explode('-', $day[2]);
      $start_time_arr = explode(':',$times[0]);
      $end_time_arr = explode(':',end($times));
      $start[] = (((int)$start_time_arr[0] * 60) + (int)$start_time_arr[1]);
      $end[] = (((int)$end_time_arr[0] * 60) + (int)$end_time_arr[1]);
    }
    $resp['start'] = min($start);//начало дня, в минутах
    $resp['end'] = max($end);//конец дня, в минутах

    $q = db_select('node', 'n');
    $q->condition('n.nid', $nid);
    $q->leftJoin('field_revision_or_minute_deal', 'om', 'om.revision_id=n.vid');
    $q->fields('om', array('or_minute_deal_value'));
    $resp['minute_deal'] = $q->execute()->fetchField();

    $days = array();

    $plus = (int) $plus;
    if ((date('w') == 1) && ($plus == 0)) {
      $days[] = date('Y-m-d');
    }
    else {
      $days[] = date('Y-m-d', strtotime("last Monday +$plus week"));
    }
    $i = 1;
    while($i < 7){
      $y = $i + (7 * $plus);
      if ((date('w') == 1) && ($plus == 0)) {
        $days[] = date('Y-m-d', strtotime("today +$y day"));
      }
      else {
        $days[] = date('Y-m-d', strtotime("last Monday +$y day"));
      }
      $i++;
    }
    $resp['days'] = $days;

    $traslite = 'Понедельник ';
    $f_day = explode('-', $days[0]);
    $traslite .= (int) $f_day[2];
    $f_month = (int) $f_day[1];
    switch ($f_month) {
      case 1:
        $traslite .= ' января';
        break;
      case 2:
        $traslite .= ' февраля';
        break;
      case 3:
        $traslite .= ' марта';
        break;
      case 4:
        $traslite .= ' апреля';
        break;
      case 5:
        $traslite .= ' мая';
        break;
      case 6:
        $traslite .= ' июня';
        break;
      case 7:
        $traslite .= ' июля';
        break;
      case 8:
        $traslite .= ' августа';
        break;
      case 9:
        $traslite .= ' сентября';
        break;
      case 10:
        $traslite .= ' октября';
        break;
      case 11:
        $traslite .= ' ноября';
        break;
      case 12:
        $traslite .= ' декабря';
        break;
    }
    $resp['translite'] = $traslite;

    //тянем праздники
    $q = db_select('online_record_holidays', 'h')->fields('h', array('date','description'))->execute()->fetchAllAssoc('date');
    $holidays = array();
    foreach ($q as $hol) {
      $holidays[str_replace('_', '-', $hol->date)] = $hol->description;
    }
    $hol_dates = array_keys($holidays);
    foreach ($resp['days'] as $day_num => $day_date) {
      foreach ($hol_dates as $hol_date) {
        if(strpos($day_date, $hol_date)) {
          $i = 0;
          foreach ($resp['rasp'] as $k => $v) {
            if($i == $day_num) {
              $resp['rasp'][$k][0] = 'NO';
            }
            $i++;
          }
        }
      }
    }

    //тянем отпуска рабочих
    $q = db_select('online_record_vacations', 'v')->fields('v', array('dates'))->condition('v.spec_id', $nid)->execute()->fetchAll();
    foreach ($q as $vac) {
      $vac_arr = explode('__', $vac->dates);
      $vac_start = date("U", strtotime($vac_arr[0]));
      $vac_end = date("U", strtotime($vac_arr[1]));
      foreach ($resp['days'] as $day_num => $day_date) {
        $day_u = date("U", strtotime($day_date));
        if (($day_u >= $vac_start) && ($day_u <= $vac_end)) {
          $i = 0;
          foreach ($resp['rasp'] as $k => $v) {
            if($i == $day_num) {
              $resp['rasp'][$k][0] = 'NO';
            }
            $i++;
          }
        }
      }
    }

    $intervals = array();
    foreach ($resp['rasp'] as $day => $data) {
      $ints = explode(',', $data[2]);
      foreach ($ints as $int) {
        $int_arr = explode('-', $int);
        $st_arr = explode(':', $int_arr[0]);
        $end_arr = explode(':', $int_arr[1]);
        $start_min = (int) $st_arr[1] + ((int) $st_arr[0] * 60);
        $end_min = (int) $end_arr[1] + ((int) $end_arr[0] * 60);
        $intervals[$day][] = array($start_min, $end_min);
      }
    }

    $header = array(
      'time' => '',
      'Monday'=> array('data' => 'ПН (' . $resp['days'][0] . ')', 'data-td-num' => 1, 'data-day' => $resp['days'][0]),
      'Tuesday'=> array('data' => 'ВТ (' . $resp['days'][1] . ')', 'data-td-num' => 2, 'data-day' => $resp['days'][1]),
      'Wednesday'=> array('data' => 'СР (' . $resp['days'][2] . ')', 'data-td-num' => 3, 'data-day' => $resp['days'][2]),
      'Thursday'=> array('data' => 'ЧТ (' . $resp['days'][3] . ')', 'data-td-num' => 4, 'data-day' => $resp['days'][3]),
      'Friday'=> array('data' => 'ПТ (' . $resp['days'][4] . ')', 'data-td-num' => 5, 'data-day' => $resp['days'][4]),
      'Saturday'=> array('data' => 'СБ (' . $resp['days'][5] . ')', 'data-td-num' => 6, 'data-day' => $resp['days'][5]),
      'Sunday'=> array('data' => 'ВС (' . $resp['days'][6] . ')', 'data-td-num' => 7, 'data-day' => $resp['days'][6])
    );
    $rows = array();

    $interval = $resp['start'];
    $step = 0;
    $busy = online_record_get_busy($nid, $record_nid, $resp);
    $now_year = (int) date('Y');
    $now_month = (int) date('n');
    $now_day = (int) date('j');
    $def_settings = variable_get('or_default_settings', array());
    $lunch = $def_settings['or_def_lunch'];
    $lunch_arr = explode('-', $lunch);
    $lunch_st = explode(':', $lunch_arr[0]);//часы и минуты
    $lunch_st = ((int) $lunch_st[0] * 60) + (int) $lunch_st[1];
    $lunch_end = explode(':', $lunch_arr[1]);
    $lunch_end = ((int) $lunch_end[0] * 60) + (int) $lunch_end[1];
    $day_rasp = array_values($resp['rasp']);

    while ($interval < $resp['end']) {
      $minutes = $interval % 60;
      $hours = ($interval - $minutes) / 60;
      if (strlen($minutes) == 1) {
        $minutes = '0' . $minutes;
      }

      $int_perm = TRUE;
      if (($interval < $lunch_st) && (($interval  + $resp['minute_deal']) <= $lunch_st)) {
        //интервал до обеда
      } elseif (($interval >= $lunch_end) && (($interval  + $resp['minute_deal']) > $lunch_end)) {
        //интервал после обеда
      } else {
        //интервал зацепил обед
        $int_perm = FALSE;
      }

      if ($int_perm) {
        $rows[$step] = array(
          'time' => array(
            'class' => 'time',
            'data-row-num' => $step,
            'data-time-interval' => $interval,
            'data' => $hours . ':' . $minutes
          ),
        );

        $int_st = $interval;
        $int_end = $interval + $resp['minute_deal'];
        $td_num = 1;
        foreach ($intervals as $day_name => $day_intervals) {
          if ($day_name != 'Holiday') {
            $rows[$step][$day_name]['data-row-num'] = $step;
            $rows[$step][$day_name]['data-td-num'] = $td_num;
            $rows[$step][$day_name]['data-td-coor'] = $step . '_' . $td_num;

            //по умолчанию всё занято
            $rows[$step][$day_name]['data'] = 'X';
            $rows[$step][$day_name]['class'] = 'busy busy-stop';
            $rows[$step][$day_name]['data-date'] = $resp['days'][$td_num - 1] . '_' . $interval . '-' . ($interval + $resp['minute_deal']);

            //освобождаем согласно расписанию
            foreach ($day_intervals as $one_interval) {
              if (((int) $int_st >= (int) $one_interval[0]) && ((int) $int_end <= (int) $one_interval[1])) {
                //выходные
                if ($day_rasp[$td_num-1][0] == 'YES') {
                  $rows[$step][$day_name]['data'] = '';
                  $rows[$step][$day_name]['class'] = 'free';
                }
                //проверяем-нет ли в интервале занятого времени
                //print_r($busy[$resp['days'][$td_num - 1]]);
                if (isset($busy[$resp['days'][$td_num - 1]])) {
                  //в этот день что-то занимали
                  //перебираю все занятые куски в этот день
                  foreach ($busy[$resp['days'][$td_num - 1]] as $busy_ints) {
                    if (
                      (($int_st < $busy_ints[0]) && (($int_end > $busy_ints[0]) && ($int_end <= $busy_ints[1])))//начало интервала в свободное время, конец в занятом
                      ||
                      (($int_end > $busy_ints[1]) && (($int_st >= $busy_ints[0]) && ($int_st < $busy_ints[1])))//начало в занятом участке. конец в свободном
                      ||
                      ((($int_st >= $busy_ints[0]) && ($int_st < $busy_ints[1])) && (($int_end > $busy_ints[0]) && ($int_end <= $busy_ints[1])))//начало и конец внутри занятого участка
                      ||
                      (($int_st <= $busy_ints[0]) && ($int_end >= $busy_ints[1])) //занятый участок внутри интервала
                    /*проверяем праздники*/
                    ) {
                      //этот участок нельзя бронировать
                      //echo "its are busy ".$busy_ints['rec_nid']." \n";
                      if ($busy_ints['rec_nid'] == $record_nid) {
                        //занято текущей заявкой
                        $rows[$step][$day_name]['data'] = 'THIS';
                        $rows[$step][$day_name]['class'] = 'free current-task';
                      }
                      else {
                        //занято другими заявками
                        $rows[$step][$day_name]['data'] = 'BUSY';
                        $rows[$step][$day_name]['class'] = 'busy busy-default';
                      }
                    }
                  }
                }
              }
            }

            //print_r($rows[$step][$day_name]);
            $expired = FALSE;
            if ($resp['days'][$td_num - 1] == date('Y-m-d')) {
              //это сегодня
              $expired = TRUE;
            }
            else {
              $day_dates = explode('-', $resp['days'][$td_num - 1]);

              if ((int) $day_dates[0] < $now_year) {
                //в предыдущем году
                $expired = TRUE;
              }
              else {
                if ((int) $day_dates[1] < $now_month) {
                  //в прошлом месяце
                  $expired = TRUE;
                }
                else if (((int) $day_dates[1] == $now_month) && ((int) $day_dates[2] <= $now_day)) {
                  //в этом месяце сегодня или ранее
                  $expired = TRUE;
                }
              }
            }

            if ($expired) {
              $rows[$step][$day_name]['data'] = 'Expired';
              $rows[$step][$day_name]['class'] = 'busy busy-expired';
            }
          }
          $td_num++;
        }
        $step++;
      }
      $interval = $interval + $resp['minute_deal'];
    }
    $zag = $traslite . ' <span id="to-next-week">>></span>';
    $table = theme('table', array('header' => $header, 'rows' => $rows, 'attributes' => array('class' => array('spec-schedule'))));
    if ($plus > 0) {
        $zag = '<span id="to-prev-week"><<</span>' . $zag;
    }

    print '<div class="or-table-title">' . $zag . '</div>' . $table . '<span id="spec-time">' . $resp['minute_deal'] . '</span>';
  }
}

/**
 * Находит занятые дни.
 */
function online_record_get_busy($spec_nid, $record_nid, $resp) {
  $or = db_or();
  foreach ($resp['days'] as $data) {
    $or->condition('od.or_date_value', db_like($data) . '%', 'LIKE');
  }
  $q = db_select('field_revision_or_date', 'od');
  $q->condition($or);
  $q->fields('od', array('or_date_value'));
  $q->rightJoin('node', 'n', 'n.vid=od.revision_id');
  $q->fields('n', array('nid'));
  $q->leftJoin('field_revision_or_specialist', 'os', 'os.revision_id=n.vid');
  $q->fields('os', array('or_specialist_target_id'));
  $q->condition('os.or_specialist_target_id', $spec_nid);
  $q->condition('n.status', 1);
  $result = $q->execute();
  $busy = array();
  while ($r = $result->fetchAssoc()) {
    $arr_1 = explode('_', $r['or_date_value']);
    if (!isset($busy[$arr_1[0]])) {
      $busy[$arr_1[0]] = array();
    }
    $busy[$arr_1[0]][$arr_1[1]] = explode('-',$arr_1[1]);
    $busy[$arr_1[0]][$arr_1[1]]['rec_nid'] = $r['nid'];
  }
  return $busy;
}

function online_record_get_step_by_date($date) {
  $arr = explode('_', $date);
  echo 'step_';
  $plus = 0;
  while ($plus < 54) {
    $days = array();
    if (date('w') == 1) {
      $days[] = date('Y-m-d');
    }
    else {
      $days[] = date('Y-m-d', strtotime("last Monday +$plus week"));
    }
    $i = 1;
    while($i < 7){
      $y = $i + (7 * $plus);
      if (date('w') == 1) {
        $days[] = date('Y-m-d', strtotime("today +$y day"));
      }
      else {
        $days[] = date('Y-m-d', strtotime("last Monday +$y day"));
      }
      $i++;
    }
    if (in_array($arr[0], $days)) {
      echo '_' . $plus.'_';
      break;
    }
    $plus++;
  }
}