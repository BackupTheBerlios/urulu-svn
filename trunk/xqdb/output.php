<?PHP
/* Outputfunktion für die Scripte
   string $xml: XML-Output des Scripts
 */
function output($output) {

 	header("Content-Type: text/xml");

  /* Falls die Seite als HTML ausgegeben werden soll */
  if (isset($_SESSION['variables']['extention']) and $_SESSION['variables']['extention'] == "html") {
    echo "type='html'";
  }

  echo $output;
}
?>