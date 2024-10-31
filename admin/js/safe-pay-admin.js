(function($) {
  'use strict';

  $(document).ready(function() {

    let public_key = $('#SP_publickey').val();
    let private_key = $('#SP_privatekey').val();
    let bild = $('#SP_bild').val();

    const ISSET_KEY = issetKey();

    function downloadTextFile(fileContent, fileName) {
      let fileLink = window.document.createElement('a');
      fileLink.href = window.URL.createObjectURL(
          new Blob([fileContent], {type: 'text/plain'}));
      fileLink.download = fileName;
      document.body.appendChild(fileLink);
      fileLink.click();
      document.body.removeChild(fileLink);
    }

    function issetKey() {
      if (public_key !== '' && public_key !== null && public_key !==
          undefined) return true;
      if (private_key !== '' && private_key !== null && private_key !==
          undefined) return true;
      if (bild !== '' && bild !== null && bild !== undefined) return true;

      return false;
    }

    function generateKey() {
      let data = {
        'action': 'generate_account',
        'security': ajax_object.ajax_nonce,
      };
      $.ajax({
        url: ajax_object.ajax_url,
        data: data,
        cache: false,
        beforeSend: function() {
          $('.safepay-settings__loading').show();
        },
        success: generateKeyResult,
        dataType: 'json',
      });

      function generateKeyResult(data) {
        let bild = getAccountAddressFromPublicKey(data.publicKey);
        $('#SP_publickey').val(data.publicKey);
        $('#SP_privatekey').val(data.privateKey);
        $('#SP_bild').val(bild);
        $('#SP_last_block').val(data.lastBlock);
        $('#SP_last_block_test').val(data.lastBlockTest);
        $('.safepay-settings__loading').hide();

        editKeysOn();
      }
    }

    function editKeysOn() {

      $('.safepay-settings__message').slideDown('fast');
      $('.safepay-settings__save').show();
      $('.safepay-settings__download').hide();
    }

    function editKeysOff() {

      $('.safepay-settings__message').slideUp('fast');
      $('.safepay-settings__save').hide();
      $('.safepay-settings__download').show();
    }

    if (ISSET_KEY === true) {
      $('.safepay-settings__download').show();
    } else {
      $('.safepay-settings__download').hide();
    }

    $('#SP_generate').click(function(e) {
      e.preventDefault();
      if (ISSET_KEY === true) {
        if (confirm(SPL_Admin.new_key)) {
          generateKey();
        }
      } else {
        generateKey();
      }
    });

    $('#SP_publickey, #SP_privatekey, #SP_bild').on('input, keyup', function() {
      if ($('#SP_publickey').val() !== public_key
          || $('#SP_privatekey').val() !== private_key
          || $('#SP_bild').val() !== bild
      ) {
        editKeysOn();
      } else {
        editKeysOff();
      }
    });

    $('.safepay-settings__download').click(function(e) {
      e.preventDefault();
      let text = 'PublicKey: ' + public_key + '\r\n';
      text += 'PrivateKey: ' + private_key + '\r\n';
      text += 'Address: ' + bild;
      downloadTextFile(text, 'keys');
    });

    $('.safepay-table__add-server').click(function(e) {
      e.preventDefault();
      let sp_form = $(this).parents('.safepay-table__form');
      let data = {
        'action': 'server_add',
        'security': ajax_object.ajax_nonce,
        'URL_SERVER': sp_form.find('input[name="URL_SERVER"]').val(),
        'TYPE_SERVER': sp_form.find('input[name="TYPE_SERVER"]').val(),
      };
      $.ajax({
        url: ajax_object.ajax_url,
        method: 'post',
        data: data,
        success: function(data) {
          $('.safepay-table__result').
              html('<div class="safepay-table__result--' + data.status + '">' +
                  data.message + '</div>');
          if (data.status === 'success') {
            let html = '';
            html += '<tr class="safepay-table__row" id="safepay-table-server-' +
                data.server['id'] + '">';
            html += '<td class="safepay-table__col">' + data.server['id'] +
                '</td>';
            html += '<td class="safepay-table__col">' + data.server['url'] +
                '</td>';
            html += '<td class="safepay-table__col">' + data.server['type'] +
                '</td>';
            html += '<td class="safepay-table__col"></td>';
            html += '<td class="safepay-table__col">';
            html += '<a class="safepay-table__del-server" href="#" data-server_id="' +
                data.server['id'] + '">' + SPL_Admin.delete + '</a>';
            html += '</td>';
            html += '</tr>';
            $('.safepay-table__row--form').before(html);
            sp_form[0].reset();
          }
        },
        dataType: 'json',
      });
    });
    $('body').on('click', '.safepay-table__del-server', function(e) {
      e.preventDefault();
      let server_id = $(this).data('server_id');
      let data = {
        'action': 'server_del',
        'security': ajax_object.ajax_nonce,
        'server_id': server_id,
      };
      $.ajax({
        url: ajax_object.ajax_url,
        method: 'post',
        data: data,
        success: function(data) {
          $('.safepay-table__result').
              html('<div class="safepay-table__result--' + data.status + '">' +
                  data.message + '</div>');
          $('#safepay-table-server-' + server_id).remove();
        },
        dataType: 'json',
      });
    });
  });

})(jQuery);
