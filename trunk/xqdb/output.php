<?PHP
/* Outputfunktion für die Scripte
   string $xml: XML-Output des Scripts
 */
function output($output) {

    /* Falls die Seite als HTML ausgegeben werden soll */
    
    /* Eine Weiche einbauen, fals die XSLT Extension nicht geladen ist -> 
    http://koders.com/php/fidB0434D36F01703F9ACAB5E21065E315FE44D2C57.aspx?s=xslt_process
    http://koders.com/php/fid78371B5F37DAE258E3258B91CC791A712CBF2AAC.aspx?s=xslt_process
    */
    if (isset($_SESSION['variables']['extention']) and $_SESSION['variables']['extention'] == "html") {
        if (version_compare(phpversion(), "5.0", ">")) {
            $processor = new XSLTProcessor();
            
            $xmlDom = new DOMDocument();
            $xslDom = new DOMDocument();        
            
            $xmlDom->loadXML($output);
            $xslDom->load(BIN_DIR . "xslt/index.xsl");
            
            $processor->importstylesheet($xslDom);
            /* Transformiert den Output
            :TODO: Fehlerabfrage der Resultates
            :ATTENTION: eventuell werden die im XSL-File includeten Dateien falsch eingebunden*/
            $result = $processor->transformtoxml($xmlDom);
        } else {
            /* PHP4 XSLT Prozessor, ist auch unter PHP5 erreichbar, nur langsamer */
            $processor = xslt_create();
            xslt_set_base($processor, "file://" . BIN_DIR . "xslt/");
        	$processor_arguments = array('/_xml' => $output);
        	/* Transformiert den Output */
        	$result = xslt_process($xh, 'arg:/_xml', $this->xsltfile, NULL, $processor_arguments);
        	if(!$result && xslt_errno($processor)>0){
    			$result = sprintf("Kann XSLT Dokument nicht umarbeiten [%d]: %s", xslt_errno($processor), xslt_error($processor));
    		}
    		xslt_free($processor);
        }
        /* Gibt das HTML-Dokument aus */
        echo $result;
    } else {
        header("Content-Type: text/xml");
        echo $output;
    }
}
?>