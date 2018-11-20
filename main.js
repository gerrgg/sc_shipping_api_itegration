jQuery(document).ready(function( $ ){
  // TODO: Refactor, refactor, refactor!
  var $submit = $( 'button.button' );
  var $check = $('#check_return');
  var $confirm_box = $( '#return-data' );

  $('form').on('click', '.return-product', function(){
    var id = $(this).attr('id');
    var qty = $(this).attr( 'data-qty' );
    var $image = $(this).find('div').find('div.return-product-img');
    var $form = $('.' + id + '_hidden-return-form' );
    var src = $image.find( 'img' ).attr('src');

    // allows form to easily pop in and out;
    // TODO: Add logic to allow for exhanges!
    if( ! $form.hasClass( 'show' ) ) {
      $form.addClass( 'show' );
      $image.attr( 'aria-checked', 'true' );

      $item_confirm_box = create_confirm_box( id, src );

      $confirm_box.append( $item_confirm_box );

      if( qty > 1  ){
        $form.append( create_return_all_form( id, qty ) );
      } else {
        $('#' + id + '_return_quantity').html( qty );
        var hidden_awnser = $('<input/>', {
                              type: 'hidden',
                              name: id + '[how_many]',
                              value: qty
                            } );
        $form.append( create_return_reason_form( id ), hidden_awnser );
      }



      $('.return-all-radio').change(function(){
        var awnser = this.value;
        if( awnser == 'yes' ){
          if( ! $( '#' + id + '_reason_form' ).length ) $form.append( create_return_reason_form( id ) );
          // if they say yes, then create a hidden input with value of the order qty
          var hidden_awnser = $('<input/>', {
                                type: 'hidden',
                                id: id + '_how_many_hidden',
                                name: id + '[how_many]',
                                value: qty
                              } );
          $form.append( hidden_awnser );
          $('#' + id + '_return_quantity').html( qty );
          $('#' + id + '_how_many').remove();
        } else {
          $form.append( create_how_many_form( id ) );
          $('#' + id + '_reason_form').remove();
          $('#' + id + '_how_many_hidden').remove();
        }
      });

      $form.on( 'blur', '#' + id + '_how_many_input', function(){
        var given_qty = this.value;
        ( given_qty > qty ) ? $(this).val( qty ) : $('#' + id + '_return_quantity').html( given_qty );
        // if already exists in DOM, do not make.
        if( ! $( '#' + id + '_reason_form' ).length && given_qty != '' ) $form.append( create_return_reason_form( id ) );
      } );

      $form.on( 'change', '#' + id + '_reason_select', function(){
        var awnser = this.value;
        if( awnser.length > 0 && awnser != 'Please select a reason for return' ){
          $('#' + id + '_return_reason').html( awnser );
        }
      } );

    } else {
      $form.removeClass( 'show' );
      $image.attr( 'aria-checked', 'false' );
      $item_confirm_box.remove();
      $form.html('');
      $('#' + id + '_ready').remove();

    }

  });

    $check.click(function(){
      var checked = this.checked;
      console.log( $confirm_box.text().length );
      if ( $confirm_box.text().length ){
        (checked) ? $submit.prop('disabled', false ) : $submit.prop('disabled', true );
      }
    });


  function create_confirm_box( id, src ){
    $box = $( '<div/>', {
      id: id + '_confirm_box',
      class: 'form-group confirm-box',
    } );

    // TODO: maybe do grid system
    $img = $('<img/>', { src: src, class: 'thumbnail', style: 'height: 50px; padding-right: 10px;' });

    $awnser_box = $( '<div/>' );
    $qty_box = create_label_awnser( id, 'quantity' );
    $reason_box = create_label_awnser( id, 'reason' );

    $awnser_box.append( $qty_box, $reason_box );

    $box.append( $img, $awnser_box );
    return $box;
  }

  function create_label_awnser( id, key ){
    $label_box = $('<p/>', {
      class: 'label-awnser'
    });
    $label_box.append( uc_first(key) + ':', $('<span/>', {
      id: id + '_return_' + key,
    }));
    return $label_box;
  }

  function uc_first(string) {
    return string.charAt(0).toUpperCase() + string.slice(1);
  }

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
