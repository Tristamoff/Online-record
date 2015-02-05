/**
 * Created by User on 30.01.2015.
 */

(function ($) {

    Drupal.behaviors.online_record_calendar = {
        attach: function(context, settings) {

            $('body').ready(function(){
                if ($('body').hasClass('page-node-edit')) {
                  //Страница редактирования заявки
                  $.ajax({
                    url: '/online_record/get_step_by_date/' + $('#edit-or-date-und-0-value').val(),
                    success: function(e){
                      //проверяем дату заявки-открываем нужную неделю. По умолчанию подсвечиваем голубым цветом ранее выбранное время.
                      var step = e.replace('step__', '').replace('_', '');
                      var record_nid = $('#for-calendar').attr('data-nid');
                      getSpecTable($('#edit-or-specialist-und').val(), step, record_nid, 'edit');
                    }
                  });
                }
            });

            function getSpecTable(spec_nid, step, record_nid, mode){
                var c_tasks = parseInt($('#edit-or-tasks-count-und-0-value').val());
                if(c_tasks > 0) {
                    $.ajax({
                        url: '/online_record/get_dates/' + spec_nid + '/' + record_nid + '/' + step,
                        success: function(e){
                            if (mode == 'add') {
                                $('#edit-or-date-und-0-value').val('');
                            }
                            $('#for-calendar').html(e);
                            $('#for-calendar').attr('data-plus', step);
                        }
                    });
                }
            }

            //запрос на следующую неделю
            $("#to-next-week").live({
                click: function(){
                    var step = parseInt($('#for-calendar').attr('data-plus'));
                    step++;
                    var record_nid = $('#for-calendar').attr('data-nid');
                    getSpecTable($('#edit-or-specialist-und').val(), step, record_nid, 'add');
                }
            });
            $("#to-prev-week").live({
                click: function(){
                    var step = parseInt($('#for-calendar').attr('data-plus'));
                    step--;
                    var record_nid = $('#for-calendar').attr('data-nid');
                    getSpecTable($('#edit-or-specialist-und').val(), step, record_nid, 'add');
                }
            });

            // запрос на расписание и доступные дни, выбор специалиста
            $('#edit-or-specialist-und').change(function(){
                var record_nid = $('#for-calendar').attr('data-nid');
                getSpecTable($(this).attr('value'), $('#for-calendar').attr('data-plus'), record_nid, 'add');
            });

            //уведомление о количестве дел
            if ($("#edit-or-tasks-count-und-0-value").val() == '') {
                $('#task-count-info').html('Укажите количество дел');
            } else {
                $('#task-count-info').html('');
            }
            $('#edit-or-tasks-count-und-0-value').keyup(function(){
                if ($("#edit-or-tasks-count-und-0-value").val() == '') {
                    $('#task-count-info').html('Укажите количество дел');
                } else {
                    $('#task-count-info').html('');
                }
            });

            //только цифры в количестве дней
            $("#edit-or-tasks-count-und-0-value").keydown(function (e) {
                // Allow: backspace, delete, tab, escape, enter and .
                if ($.inArray(e.keyCode, [46, 8, 9, 27, 13, 110, 190]) !== -1 ||
                        // Allow: Ctrl+A
                    (e.keyCode == 65 && e.ctrlKey === true) ||
                        // Allow: home, end, left, right, down, up
                    (e.keyCode >= 35 && e.keyCode <= 40)) {
                    // let it happen, don't do anything
                    return;
                }
                // Ensure that it is a number and stop the keypress
                if ((e.shiftKey || (e.keyCode < 48 || e.keyCode > 57)) && (e.keyCode < 96 || e.keyCode > 105)) {
                    e.preventDefault();
                }
            });

            //бронирование дней
            $(".spec-schedule td").live({
                hover: function () {
                    var ok = 'no';
                    if (!$(this).hasClass('time')) {
                        var count_task = parseInt($('#edit-or-tasks-count-und-0-value').val());
                        var total_intervals = $('.spec-schedule tr').length - 1;
                        if (count_task >= total_intervals) {
                            //хочет занять весь день
                            var td_num = parseInt($(this).attr('data-td-num'));//номер дня недели
                            $('.spec-schedule td[data-td-num=' + td_num + '].free').addClass('or-bg-green');
                        }
                        else if ((count_task < total_intervals) && (count_task > 0)) {
                            ok = 'yes';
                            if ($(this).hasClass('free')) {
                                //это место для записи свободно
                                var td_num = parseInt($(this).attr('data-td-num'));
                                var tr_num = parseInt($(this).attr('data-row-num'));
                                var i = tr_num;
                                var selectors = new Array();
                                while (i < (tr_num + count_task)) {
                                    if ($('.spec-schedule td[data-row-num='+i+'][data-td-num='+td_num+']').hasClass('free')) {
                                        selectors[selectors.length] = '.spec-schedule td[data-row-num='+i+'][data-td-num='+td_num+']';
                                    }
                                    else {
                                        ok = 'no';
                                    }
                                    i++;
                                }
                                $.each(selectors, function(index, value) {
                                    if (ok == 'yes') {
                                        $(value).addClass('or-bg-green');
                                    } else {
                                        $(value).addClass('or-bg-red');
                                        return false;
                                    }
                                });
                            }
                            else{
                                ok = 'no'
                            }
                        }
                    }
                    //$('#task-count-info').html(ok);
                },
                mouseleave: function() {
                    $(".spec-schedule td").removeClass('or-bg-green');
                    $(".spec-schedule td").removeClass('or-bg-red');
                },
                click: function() {
                    var start = 0;
                    var end = 0;
                    $('.spec-schedule td.or-bg-green').each(function (index) {
                        var rowN = $(this).attr('data-row-num');
                        var timestamp = $('.spec-schedule td.time[data-row-num=' + rowN + ']').attr('data-time-interval');
                        if (start == 0) {
                            start = parseInt(timestamp);
                        }
                        end = parseInt(timestamp) + parseInt($('#spec-time').html());
                    });
                    if ((start > 0) && (end > 0)) {
                        //можно забронировать эти часы
                        $(".spec-schedule td").removeClass('or-bg-changed');
                        $('#edit-or-date-und-0-value').val($('.spec-schedule th[data-td-num=' + $(this).attr('data-td-num') + ']').attr('data-day') + '_' + start + '-' + end);
                        $('.spec-schedule td.or-bg-green').each(function (index) {
                            $(this).removeClass('or-bg-green');
                            $(this).addClass('or-bg-changed');
                        });
                    }
                }
            });
        }
    }

})(jQuery);