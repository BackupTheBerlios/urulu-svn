<?xml version="1.0" encoding="ISO-8859-1" ?>
<xsl:stylesheet version="1.0" 
  xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
  <xsl:template name="header">
    <div id="header">
      <div id="logo">
        <a href="/app.php/home"><img src="/img/logo.gif" alt="Startseite" width="170" height="76" /></a>
      </div>
      <div id="msearch">
        <form name="mSearchForm" action="/search" method="post">
          <label for="msearch" style="display: none;">Mitgliedersuche</label>
          <input name="msearch" type="text" disabled="disabled" />
          <input name="msearchSubmit" type="submit" value="Suchen" disabled="disabled" />
          <p>
            <label><input name="msearchArray" value="1" checked="checked" title="Standart" type="radio" disabled="disabled" />Standart</label>
            <label><input name="msearchArray" value="2" title="Erweitert" type="radio" disabled="disabled" />Erweitert</label>
          </p>
        </form>
      </div>
    </div>
  </xsl:template>
</xsl:stylesheet>