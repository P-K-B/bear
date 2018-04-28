<html>
<head></head>
<title>Static Dropdown List</title>
<body bgcolor="pink">
Employee List :
<select name=sel>
  <option value="Select">Select</option>}
  <option value="Vineet">Vineet Saini</option>
  <option value="Sumit">Sumit Sharma</option>
  <option value="Dorilal">Dorilal Agarwal</option>
  <option value="Omveer">Omveer Singh</option>
  <option value="Rohtash">Rohtash Kumar</option>
  <option value="Maneesh">Maneesh Tewatia</option>
  <option value="Priyanka">Priyanka Sachan</option>
  <option value="Neha">Neha Saini</option>
</select>
<?php  if (isset($_POST['sel'])) {
    alert($_POST['sel']);
}?>

</body>
<html>
