/**
 * Created by rusakov on 02.02.15.
 */

(function ($) {
  Drupal.behaviors.online_record_admin = {
    attach: function(context, settings) {
      //с реальных данных в фейковые при загрузке страницы
      $('body').ready(function(){
        $('.real-data').each(function (index) {
          var arr = $(this).val().split('|');
          if(arr[0] == 'YES') {
            $('.work-enable#edit-work-'+index).attr('checked', 'checked');
          }
          $('.work-time#edit-times-'+index).val(arr[2]);
        });
      });

      //перехват времени работы
      $(".work-time").keyup(function (e) {
        orSetRealData($(this).attr('data-day-index'));
      });

      //перехват работает или нет
      $('.work-enable').change(function() {
        orSetRealData($(this).attr('data-day-index'));
      });

      //установка данных в реальное поле
      function orSetRealData(index) {
        var enabled = 'NO';
        if ($('input.work-enable[data-day-index=' + index + ']').attr('checked')){
          enabled = 'YES';
        }
        var time = $('input.work-time[data-day-index=' + index + ']').val();
        $('input[data-day='+index+']').val(enabled+'|'+$('fieldset[data-day-index='+index+']').attr('data-day-name')+'|'+time);
       // $('input[data-day='+index+']').val(enabled+'|'+$('fieldset[data-day-index='+index+']').attr('data-day-name')+'|'+time);
      }
    }
  }
})(jQuery);