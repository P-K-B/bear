<html>
   <body>

     <style>
     table, th, td {
         border: 1px solid black;
     }
     </style>


     <form name = 'Form'>
              Max Rows: <input type = 'text' id = 'show' value="10" /> <br />

           </form>

      <script language = "javascript" type = "text/javascript">
      var update_loop = setInterval(ajaxFunction, 1000);



         <!--
            //Browser Support Code
            function ajaxFunction(){
               var ajaxRequest;  // The variable that makes Ajax possible!

               try {
                  // Opera 8.0+, Firefox, Safari
                  ajaxRequest = new XMLHttpRequest();
               }catch (e) {
                  // Internet Explorer Browsers
                  try {
                     ajaxRequest = new ActiveXObject("Msxml2.XMLHTTP");
                  }catch (e) {
                     try{
                        ajaxRequest = new ActiveXObject("Microsoft.XMLHTTP");
                     }catch (e){
                        // Something went wrong
                        alert("Your browser broke!");
                        return false;
                     }
                  }
               }

               // Create a function that will receive data
               // sent from the server and will update
               // div section in the same page.

               ajaxRequest.onreadystatechange = function(){
                  if(ajaxRequest.readyState == 4){
                     var ajaxDisplay = document.getElementById('ajaxDiv');
                     ajaxDisplay.innerHTML = ajaxRequest.responseText;
                  }
               }

               // Now get the value from user and pass it to
               // server script.

               var show = document.getElementById('show').value;
               var queryString = "?show=" + show ;
               ajaxRequest.open("GET", "load.php"+ queryString, true);
               ajaxRequest.send(null);
               // ajaxFunction();
            }
         //-->
      </script>




      <div id = 'ajaxDiv'></div>
   </body>
</html>
