<!DOCTYPE html>
<html>
  <head>
    <meta charset="UTF-8">
    <title>7.2_p5.js_Creating_HTML_elements_with_JavaScript </title>
    <script src="libraries/p5.js" type="text/javascript"></script>

    <script src="libraries/p5.dom.js" type="text/javascript"></script>
    <script src="libraries/p5.sound.js" type="text/javascript"></script>

    <script src="sketch.js" type="text/javascript"></script>

    <!--<style> body {padding: 0; margin: 0;} canvas {vertical-align: top;} </style>-->
  </head>
  <body>
    <h1>
      <?php
         function Connect(){
             $this->connection = new mysqli($this->hostname.$this->port, $this->username, $this->password);
             if ($this->connection->connect_errno) die("Unable to connect to MySQL server:".$this->connection->connect_errno.$this->connection->connect_error);
             $this->connection->query("SET NAMES 'utf8'");
             if ($this->connection && $this->debug) echo "Connected to MySQL server.\n";
             $this->connection->query("SET CHARACTER SET 'utf8'");
             if ($this->connection && $this->debug) echo "Connected to MySQL server.\n";
             $this->connection->query("SET SESSION collation_connection = 'utf8_general_ci'");
             if ($this->connection && $this->debug) echo "Connected to MySQL server.\n";
         }
         ?>
      <?php
      $this->Connect();
      ?>
      Monitore by PHP
    </h1>

  </body>
</html>
