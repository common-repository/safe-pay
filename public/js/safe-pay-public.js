(function($) {
  'use strict';

  $(document).ready(function() {

    const USER_OS_LINK = get_user_os();

    const PLUGIN_URL = get_plugin_url();

    let invoice_end = get_invoice_end();

    $('.safe-pay-payment-form__submit').click(function(e) {
      e.preventDefault();
      let all_recipient = $(this).
          parents('.safe-pay-payment-form').
          find('input[name="ALL_RECIPIENT"]').
          val();

      if ($(this).data('invoice_status') === false) {
        open_validate_pay();
      } else {
        open_bank_list(all_recipient);
      }
    });
    $('body').on('click', '.safe-pay-popup, .safe-pay-popup__exit', function() {
      $('.safe-pay-popup').hide();
    });
    $('body').on('click', '.safe-pay-popup__block', function(e) {
      e.stopPropagation();
    });
    $('body').on('click', '.safe-pay-popup__recipient', function() {
      if (!$(this).hasClass('safe-pay-popup__recipient--active')) {
        $('.safe-pay-popup__recipient--active').
            removeClass('safe-pay-popup__recipient--active');
        $(this).addClass('safe-pay-popup__recipient--active');
        $('.safe-pay-popup__button--send').
            attr('href', $(this).data(USER_OS_LINK.toLowerCase())).
            attr('data-recipient', $(this).data('attribute'));
      }
    });
    $('body').on('click', '.safe-pay-popup__button--send', function(e) {
      if (!$(this).hasClass('safe-pay-popup__button--disabled')) {
        let sp_form = $('.safe-pay-payment-form');
        let data = {
          'action': 'send_invoice',
          'security': ajax_object.ajax_nonce,
          'recipient': $(this).attr('data-recipient'),
          'order_date': sp_form.find('input[name="order_date"]').val(),
          'order_num': sp_form.find('input[name="order_num"]').val(),
          'userPhone': sp_form.find('input[name="userPhone"]').val(),
          'curr': sp_form.find('input[name="curr"]').val(),
          'sum': sp_form.find('input[name="sum"]').val(),
          'expire': sp_form.find('input[name="expire"]').val(),
          'title': sp_form.find('input[name="title"]').val(),
          'description': sp_form.find('input[name="description"]').val(),
        };
        $.ajax({
          url: ajax_object.ajax_url,
          type: 'POST',
          data: data,
          cache: false,
          beforeSend: function() {
            window.onbeforeunload = function() {
              return 'Данные не сохранены. Точно перейти?';
            };
            $('.safe-pay-popup__button--send').
                removeClass('safe-pay-popup__button--error').
                addClass('safe-pay-popup__button--disabled').
                text(SPL_Public.send_order);
            $('body').
                append(
                    '<div id="before_send_SP" style="position: fixed;width: 100%;height:100%;z-index: 99999999999999;top:0;left:0;"></div>');
          },
          success: function(d) {
            if (d.STATUS === 'OK') {
              window.onbeforeunload = function() {
              };
              location.reload();
            } else {
              $('#before_send_SP').remove();
              $('.safe-pay-popup__button--send').
                  removeClass('safe-pay-popup__button--disabled').
                  addClass('safe-pay-popup__button--error').
                  text(SPL_Public.send_error);
            }
          },
          error: function(xhr, ajaxOptions, thrownError) {
            console.log(thrownError + '\r\n' + xhr.statusText + '\r\n' +
                xhr.responseText);
          },
          dataType: 'json',
        });
      } else {
        e.preventDefault();
      }
    });

    $('body').on('click', '.safe-pay-popup__button--check', function(e) {
      e.preventDefault();

      if (!$(this).hasClass('safe-pay-popup__button--disabled')) {

        let sign = $('.safe-pay-payment-form__submit').
            data('invoice_signature');
        let data = {
          'action': 'check_pay',
          'security': ajax_object.ajax_nonce,
          'signature': sign,
        };
        if (sign !== undefined && sign !== '' && sign.length > 0) {
          $.ajax({
            method: 'post',
            url: ajax_object.ajax_url,
            data: data,
            cache: false,
            beforeSend: function() {
              $('.safe-pay-popup__result').html('').hide();
              $('.safe-pay-popup__loader').show();
              $('.safe-pay-popup__button--check').
                  addClass('safe-pay-popup__button--disabled');
            },
            success: function(data) {
              setTimeout(function() {
                $('.safe-pay-popup__loader').hide();
                let html_result = '';
                if (data === false) {
                  html_result += '<img src="' + PLUGIN_URL +
                      'public/img/false.png">';
                  html_result += '<p>' + SPL_Public.check_pay_false + '</p>';
                } else {
                  html_result += '<img src="' + PLUGIN_URL +
                      'public/img/true.png">';
                  html_result += '<p>' + SPL_Public.check_pay_true + '</p>';
                  setTimeout(function() {
                    location.reload();
                  }, 1000);
                }
                $('.safe-pay-popup__result').html(html_result).fadeIn('slow');
                $('.safe-pay-popup__button--check').
                    removeClass('safe-pay-popup__button--disabled');
              }, 300);
            },
            error: function(xhr, ajaxOptions, thrownError) {
              console.log(thrownError + '\r\n' + xhr.statusText + '\r\n' +
                  xhr.responseText);
            },
            dataType: 'json',
          });
        }
      }
    });

    $('body').on('click', '.safe-pay-popup__button--qr', function(e) {
      e.preventDefault();

      if (!$(this).hasClass('safe-pay-popup__button--disabled')) {

        let sp_form = $('.safe-pay-payment-form');
        let data = {
          'action': 'qr_pay',
          'security': ajax_object.ajax_nonce,
          'recipient': $('.safe-pay-popup__button--send').
              attr('data-recipient'),
          'order_date': sp_form.find('input[name="order_date"]').val(),
          'order_num': sp_form.find('input[name="order_num"]').val(),
          'userPhone': sp_form.find('input[name="userPhone"]').val(),
          'curr': sp_form.find('input[name="curr"]').val(),
          'sum': sp_form.find('input[name="sum"]').val(),
          'expire': sp_form.find('input[name="expire"]').val(),
          'title': sp_form.find('input[name="title"]').val(),
          'description': sp_form.find('input[name="description"]').val(),
        };
        $.ajax({
          method: 'post',
          url: ajax_object.ajax_url,
          data: data,
          cache: false,
          beforeSend: function() {
            window.onbeforeunload = function() {
              return 'Данные не сохранены. Точно перейти?';
            };

            $('.safe-pay-popup__button--qr').
                addClass('safe-pay-popup__button--disabled').
                text(SPL_Public.send_order);

            $('body').
                append(
                    '<div id="before_send_SP" style="position: fixed;width: 100%;height:100%;z-index: 99999999999999;top:0;left:0;"></div>');
          },
          success: function(data) {
            window.onbeforeunload = function() {
            };
            $('#before_send_SP').remove();
            $('.safe-pay-popup__button--qr').
                removeClass('safe-pay-popup__button--disabled');
            if (data === false) {
              $('.safe-pay-popup__button--qr').
                  addClass('safe-pay-popup__button--error').
                  text(SPL_Public.send_error);
            } else {
              $('.safe-pay-popup').remove();
              open_qr_pay(data);
            }
          },
          error: function(xhr, ajaxOptions, thrownError) {
            console.log(thrownError + '\r\n' + xhr.statusText + '\r\n' +
                xhr.responseText);
          },
          dataType: 'json',
        });
      }
    });

    $('body').on('click', '.safe-pay-popup__button--back', function(e) {
      e.preventDefault();
      $('.safe-pay-popup').remove();
      let all_recipient = $('.safe-pay-payment-form').
          find('input[name="ALL_RECIPIENT"]').
          val();
      open_bank_list(all_recipient);
    });

    function get_user_os() {

      let user_agent = window.navigator.userAgent;
      if (user_agent.indexOf('Android') !== -1 ||
          user_agent.indexOf('android') !== -1) return 'APP_ANDROID';
      if (user_agent.indexOf('IPhone') !== -1 ||
          user_agent.indexOf('IPad') !== -1) return 'APP_IOS';
      return 'PAY_URL';

    }

    function open_bank_list(all_recipient) {
      let safe_pay_popup = $('.safe-pay-popup');
      if (safe_pay_popup.hasClass('safe-pay-popup')) {
        safe_pay_popup.show();
      } else {
        let popup_block = '';
        let obj_recipient = jQuery.parseJSON(all_recipient);
        let i = 0, obj_current = {};
        popup_block += '<div class="safe-pay-popup">';
        popup_block += '<div class="safe-pay-popup__block">';
        popup_block += '<div class="safe-pay-popup__exit">x</div>';
        popup_block += '<h3 class="safe-pay-popup__title">' +
            SPL_Public.change_bank + '</h3>';
        popup_block += '<div class="safe-pay-popup__recipients">';
        obj_recipient.forEach(function(e) {
          let activ_link = '';
          if (i === 0) {
            obj_current = e;
            activ_link = ' safe-pay-popup__recipient--active';
          }
          popup_block += '<div class="safe-pay-popup__recipient' + activ_link +
              '" data-attribute="' + e.ATTRIBUTE + '" data-app_android="' +
              e.APP_ANDROID + '" data-app_ios="' + e.APP_IOS +
              '" data-pay_url="' + e.PAY_URL + '">';
          popup_block += '<img src="' + PLUGIN_URL + e.PICTURE_URL + '" alt="' +
              e.NAME + '" title="' + e.NAME + '">';
          popup_block += '</div>';
          i++;
        });
        popup_block += '</div>';
        popup_block += '<p class="safe-pay-popup__vote">' + SPL_Public.vote +
            '</p>';

        let phone = $('.safe-pay-payment-form').
            find('input[name="userPhone"]').
            val();
        phone = phone.replace(/\D+/g, '');
        if (phone.length < 10) {
          popup_block += '<div class="safe-pay-popup__phone">';
          popup_block += '<p>' + SPL_Public.phone_desc + '</p>';
          popup_block += '<input type="tel" name="new_phone" placeholder="' +
              SPL_Public.phone_placeholder + '">';
          popup_block += '</div>';
        }

        popup_block += '<a class="safe-pay-popup__button safe-pay-popup__button--send" href="' +
            obj_current[USER_OS_LINK] +
            '" target="_blank" data-recipient="' +
            obj_current['ATTRIBUTE'] + '">' + SPL_Public.link_bank + '</a>';
        if (SPL_Public.qr_status == true) {
          popup_block += '<a class="safe-pay-popup__button safe-pay-popup__button--qr" href="javascript:">' +
              SPL_Public.qr_button + '</a>';
        }
        popup_block += '<p class="safe-pay-popup__desc">' +
            SPL_Public.instruction + '</p>';
        popup_block += '</div>';
        popup_block += '</div>';
        $('body').prepend(popup_block);

        if ($('.safe-pay-popup__phone').hasClass('safe-pay-popup__phone')) {
          $('.safe-pay-popup__button--send').
              addClass('safe-pay-popup__button--disabled');

          $('.safe-pay-popup__phone input').
              on('change input keyup', function() {
                let phone = $(this).val();
                phone = phone.replace(/\D+/g, '');
                if (phone.length >= 10) {
                  $('.safe-pay-popup__button--send').
                      removeClass('safe-pay-popup__button--disabled');
                  $('.safe-pay-payment-form').
                      find('input[name="userPhone"]').
                      val(phone.substr(-10));
                } else {
                  $('.safe-pay-popup__button--send').
                      addClass('safe-pay-popup__button--disabled');
                  $('.safe-pay-payment-form').
                      find('input[name="userPhone"]').
                      val('');
                }
              });
        }
      }
    }

    function open_validate_pay() {
      let safe_pay_popup = $('.safe-pay-popup');
      if (safe_pay_popup.hasClass('safe-pay-popup')) {
        safe_pay_popup.show();
        $('.safe-pay-popup__button--check').click();
      } else {
        let popup_block = '';
        popup_block += '<div class="safe-pay-popup">';
        popup_block += '<div class="safe-pay-popup__block">';
        popup_block += '<div class="safe-pay-popup__exit">x</div>';
        popup_block += '<h3 class="safe-pay-popup__title">' +
            SPL_Public.wait_pay + '</h3>';
        popup_block += '<div class="safe-pay-popup__loader">';
        popup_block += '<img src="' + PLUGIN_URL + 'public/img/loader.gif">';
        popup_block += '</div>';
        popup_block += '<div class="safe-pay-popup__result"></div>';
        popup_block += '<a class="safe-pay-popup__button safe-pay-popup__button--check" href="#">' +
            SPL_Public.check_pay + '</a>';
        popup_block += '<p class="safe-pay-popup__desc">' +
            SPL_Public.check_pay_message + '</p>';
        popup_block += '</div>';
        popup_block += '</div>';

        $('body').prepend(popup_block);
        $('.safe-pay-popup__button--check').click();
      }
    }

    function open_qr_pay(qr_url) {
      let popup_block = '';
      popup_block += '<div class="safe-pay-popup">';
      popup_block += '<div class="safe-pay-popup__block">';
      popup_block += '<div class="safe-pay-popup__exit">x</div>';
      popup_block += '<h3 class="safe-pay-popup__title">' +
          SPL_Public.qr_pay + '</h3>';
      popup_block += '<div class="safe-pay-popup__qr">';
      popup_block += '<img src="' + qr_url + '">';
      popup_block += '<p class="safe-pay-popup__desc">' +
          SPL_Public.qr_pay_message_true + '</p>';
      popup_block += '</div>';
      popup_block += '<p class="safe-pay-popup__desc">' +
          SPL_Public.qr_pay_message_false + '</p>';
      popup_block += '<a class="safe-pay-popup__button safe-pay-popup__button--back" href="javascript:">' +
          SPL_Public.qr_button_back + '</a>';
      popup_block += '</div>';
      popup_block += '</div>';

      $('body').prepend(popup_block);
    }

    function get_plugin_url() {
      if ($('.safe-pay-payment-form').hasClass('safe-pay-payment-form')) {
        return $('.safe-pay-payment-form input[name="plugin_dir"]').val();
      } else {
        return false;
      }
    }

    function get_invoice_end() {
      return $('.safe-pay-payment__invoice_end').data('invoice_end');
    }

    function timer() {
      setInterval(function() {
        --invoice_end;
        let result_time = [], h, m;
        result_time.push(correct_end_time(invoice_end % 60));
        m = Math.floor(invoice_end / 60);
        if (m > 60) {
          result_time.push(correct_end_time(m % 60));
          h = Math.floor(m / 60);
          if (h > 24) {
            result_time.push(correct_end_time(h % 24));
            result_time.push(correct_end_time(Math.floor(h / 24)));
          } else {
            result_time.push(correct_end_time(h));
          }
        } else {
          result_time.push(correct_end_time(m));
        }
        result_time.reverse();
        let result_time_view = result_time.join(':');
        $('.safe-pay-payment__invoice_end').text(result_time_view);
      }, 1000);
    }

    function correct_end_time(time_number) {
      return ('0' + time_number).substr(-2);
    }

    if ($('.safe-pay-payment__invoice_end').
        hasClass('safe-pay-payment__invoice_end')) {
      timer();
    }
  });

})(jQuery);
