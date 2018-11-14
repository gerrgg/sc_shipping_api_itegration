jQuery(document).ready(function( $ ){
  $('form').on('click', '.item-button', function(){
    var id = $(this).attr('id');
    var $show = $('.' + id + '_show');
    var $type =   $('.' + id + '_type');
    var $qty = $('.' + id + '_qty');
    var $reason = $('.' + id + '_reason');
    var $exchange = $('.' + id + '_exchange');
    var $thumb = $('#' + id + '_thumb');

    // use class 'push' keep track of what is being returned
    ( ! $(this).hasClass('push') ) ? $(this).addClass('push') : $(this).removeClass('push');
    // used for visual confirmation of what is being returned
    ( $thumb.attr('aria-checked') === 'false' ) ? $thumb.attr( 'aria-checked', 'true' ) : $thumb.attr( 'aria-checked', 'false' );
    // $show is the div which holds all the inner divs, allows for closing without losing data
    $show.toggle('fast');

    // Init returns array

    $qty.on( 'blur', 'input[type=tel]', function(){
      value = $(this).val();
      max = $(this).attr('max');

      if( +value > +max ){
        $(this).val( max );
      }

      if( ! $type.hasClass( 'show' ) ){
        $type.addClass( 'show' )
        $type.toggle('fast');
      }

      arr.qty = value;

    });

    $type.on('click', 'input[type=radio]', function(){
      type = $(this).val();
      if ( type === 'return' ) {
        $exchange.hide();
        $reason.toggle('fast');
      } else {
        $reason.hide();
        $exchange.toggle('fast');
      }
      arr.type = type
    });

    $exchange.on('blur', 'input[type=tel]', function(){
      // find the # of items being exchanged
      var max = $qty.find('input[type=tel]').val();
      // result total on blur
      var total = 0;
      // add up all the qtys
      $exchange.find('input[type=tel]').each(function(){
        total += +$(this).val();
      });

      left = max - total;
      // if the exchanges exceed the # of returns
      if( left < 0 ){
        // grab the current input e.g. 18
        input = $(this).val();
        // the input + what is left is the mininum number allowed to be input
        awnser = +input + +left;
        $(this).val( awnser );
        left = awnser;
      }
      // TODO: Show counter
      // $count.html( left );

      arr.exchange = total
      // Maybe add a confirm button at bottom of each item, listing exactly what they are confirming
      // TYPE - ITEM - QTY
      // CONFIRM
    });


    });
    // TODO: Find a way to pass all this data to an array before pushing!
    $('#submit_preview_btn').click(function(){
      console.log( returns );
    });
});
