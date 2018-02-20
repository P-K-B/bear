
<html>
<head>
<link type="text/css" rel="stylesheet" href="css/style.css">
<script src='jquery.js'>
$( document ).ready( function()
{
    var targets = $( '[rel~=tooltip]' ),
        target  = false,
        tooltip = false,
        title   = false;

    targets.bind( 'mouseenter', function()
    {
        target  = $( this );
        tip     = target.attr( 'title' );
        tooltip = $( '<div id="tooltip"></div>' );

        if( !tip || tip == '' )
            return false;

        target.removeAttr( 'title' );
        tooltip.css( 'opacity', 0 )
               .html( tip )
               .appendTo( 'body' );

        var init_tooltip = function()
        {
            if( $( window ).width() < tooltip.outerWidth() * 1.5 )
                tooltip.css( 'max-width', $( window ).width() / 2 );
            else
                tooltip.css( 'max-width', 340 );

            var pos_left = target.offset().left + ( target.outerWidth() / 2 ) - ( tooltip.outerWidth() / 2 ),
                pos_top  = target.offset().top - tooltip.outerHeight() - 20;

            if( pos_left < 0 )
            {
                pos_left = target.offset().left + target.outerWidth() / 2 - 20;
                tooltip.addClass( 'left' );
            }
            else
                tooltip.removeClass( 'left' );

            if( pos_left + tooltip.outerWidth() > $( window ).width() )
            {
                pos_left = target.offset().left - tooltip.outerWidth() + target.outerWidth() / 2 + 20;
                tooltip.addClass( 'right' );
            }
            else
                tooltip.removeClass( 'right' );

            if( pos_top < 0 )
            {
                var pos_top  = target.offset().top + target.outerHeight();
                tooltip.addClass( 'top' );
            }
            else
                tooltip.removeClass( 'top' );

            tooltip.css( { left: pos_left, top: pos_top } )
                   .animate( { top: '+=10', opacity: 1 }, 50 );
        };

        init_tooltip();
        $( window ).resize( init_tooltip );

        var remove_tooltip = function()
        {
            tooltip.animate( { top: '-=10', opacity: 0 }, 50, function()
            {
                $( this ).remove();
            });

            target.attr( 'title', tip );
        };

        target.bind( 'mouseleave', remove_tooltip );
        tooltip.bind( 'click', remove_tooltip );
    });
});

</script>
</head>
<meta http-equiv="refresh" content="300">
    <body>

        <form name='Form'>
            Max Rows: <input type='text' id='show' value="20" /> <br />
            Update Time: <input type='text' id='time' value="1" /> <br />
        </form>

        <script language="javascript" type="text/javascript">
            var time = document.getElementById('time').value * 1000;
            var update_loop = setInterval(ShowFunction, time);
            //Browser Support Code
            function ShowFunction() {
                var Request; // The variable that makes Ajax possible!
                  try {
                      // Opera 8.0+, Firefox, Safari
                      Request = new XMLHttpRequest();
                  } catch (e) {
                      // Internet Explorer Browsers
                      try {
                          Request = new ActiveXObject("Msxml2.XMLHTTP");
                      } catch (e) {
                          try {
                              Request = new ActiveXObject("Microsoft.XMLHTTP");
                          } catch (e) {
                              // Something went wrong
                              alert("Your browser broke!");
                              return false;
                          }
                      }
                }
                // Create a function that will receive data
                // sent from the server and will update
                // div section in the same page.
                Request.onreadystatechange = function() {
                    if (Request.readyState == 4) {
                        var ajaxDisplay = document.getElementById('ajaxDiv');
                        ajaxDisplay.innerHTML = Request.responseText;
                    }
                }
                // Now get the value from user and pass it to
                // server script.
                var show = document.getElementById('show').value;
                var queryString = "?show=" + show;
                Request.open("GET", "main.php" + queryString, true);
                Request.send(null);
            }
            ShowFunction();
        </script>
        <div id='ajaxDiv'></div>
    </body>
</html>
