jQuery(document).ready(function( $ ){
  $('form').on('click', '.return-product', function(){
    var id = $(this).attr('id');
    var qty = $(this).attr( 'data-qty' );
    var $image = $(this).find('div').find('div.return-product-img');
    var $ready_confirmation = $('.return-confrimation');
    var $form = $('.' + id + '_hidden-return-form' );

    // allows form to easily pop in and out;
    if( ! $form.hasClass( 'show' ) ) {
      $form.addClass( 'show' );
      $image.attr( 'aria-checked', 'true' );

      if( qty > 1  ){
        $form.append( create_return_all_form( id, qty ) );
      } else {
        $form.append( create_return_reason_form( id ) );
      }



      $('.return-all-radio').change(function(){
        var awnser = this.value;
        if( awnser == 'yes' ){
          $form.append( create_return_reason_form( id ) );
          $('#' + id + '_how_many').remove();
        } else {
          $form.append( create_how_many_form( id ) );
          $('#' + id + '_reason_form').remove();
        }
      });

      $form.on( 'blur', '#' + id + '_how_many_input', function(){
        var given_qty = this.value;
        if( given_qty > qty ) $(this).val( qty );
        // if already exists in DOM, do not make.
        if( ! $( '#' + id + '_reason_form' ).length && given_qty != '' ) $form.append( create_return_reason_form( id ) );
      } );

      // $form.on( 'change', '#' + id + '_reason_select', function(){
      //   var awnser = this.value;
      //   if( awnser.length > 0 && awnser != 'Please select a reason for return' ){
      //     if( ! $('#' + id + '_ready').length ){
      //       $ready = $( '<input/>', {
      //         id: id + '_ready',
      //         type: 'hidden',
      //         name: id + '[ready]',
      //         value: 'yes'
      //       } )
      //       $ready_confirmation.append( $ready );
      //     } else {
      //       $ready.remove();
      //     }
      //   }
      // } );

    } else {
      $form.removeClass( 'show' );
      $image.attr( 'aria-checked', 'false' );
      $form.html('');
      $('#' + id + '_ready').remove();
    }

  });

  var $submit = $( 'button.button' );
  var $check = $('#check_return');

  $check.click(function(){
    var checked = this.checked;
    (checked) ? $submit.prop('disabled', false ) : $submit.prop('disabled', true );
  })


  function create_return_reason_form( id ){
    var $reason_form = $( '<div/>', {
      id: id + '_reason_form',
      class: 'form-group'
    } );
    var $select = create_return_reason_select( id );
    $reason_form.append( '<h4>Why are your returning this item?</h4>', $select );
    return $reason_form;
  }

  function create_return_reason_select( id ){
    var reasons = [
      'Please select a reason for return',
      'No longer needed',
      'Innaccurate website description',
      'Defective Item',
      'Better Price Available',
      'Product damaged',
      'Item arrived too late',
      'Missing or broken parts',
      'Product and shipping box damaged',
      'Wrong item sent',
      'Received an extra item ( No refund needed )',
      'Didnt approve purchase'
    ];

    var $select = $( '<select/>', {
      id: id + '_reason_select',
      name: id + '[return_reason]',
    });

    for( var i = 0; i < reasons.length; i++ ){
      $option = $('<option/>', {
        value: reasons[i]
      });
      $option.append( reasons[i] );
      $select.append( $option );
    }

    return $select;
  }

  function create_how_many_form( id ){
    $how_many = $( '<div/>', {
      id: id + '_how_many',
      class: 'form-group'
    } );
    $input = $( '<input/>', {
      id: id + '_how_many_input',
      type: 'tel',
      name: id + '[how_many]',
    } );
    $how_many.append( '<h4>How many are you returning?</h4>', $input );
    return $how_many;
  }

  function create_return_all_form( id, qty ){
    $return_all = $('<div/>', {
      class: 'return_all_form form-group'
    });

    $yes_btn = create_radio_button( id, 'yes', 'Yes' );
    $no_btn = create_radio_button( id, 'no', 'No' );
    $return_all.append( '<h4>Are you returning all '+ qty +' of them?</h4>', $yes_btn, $no_btn );
    return $return_all
  }

  function create_radio_button( id, name, text ){
    $div = $('<div/>');
    $button = $('<input/>', {
      id: id + '_' + name,
      type: 'radio',
      name: id + '[return_all]',
      value: name,
      class: 'return-all-radio'
    });
    $label = $('<label/>', {
      for: id + '_' + name,
      style: 'display: inline-block'
    });
    $label.append( text );
    $div.append($button, $label);
    return $div;
  }
});
