<?xml version="1.0" encoding="ISO-8859-1" ?>
<xsl:stylesheet version="1.0" 
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
	<xsl:template name="meta_header">
		<title><xsl:value-of select="title" /></title>
		<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
		<meta name="Title" content="Etat Adressdatenbank" />
		<meta name="Author" content="Fistulo, Mistral" />
		<meta name="Copyright" content="(c) 2005 Fistulo und Mistral alle Rechte vorbehalten" />
		<meta http-equiv="Content-Language" content="de-ch" />
		<base href="http://etat.snowgarden.ch" />
		<style type="text/css">
			/* &lt;![CDATA[ */
			@import url("/style/v1.css");
			/* ]]&gt; */
		</style>
		<xsl:if test="meta/css/text()">
		<style type="text/css">
			/* &lt;![CDATA[ */
			<xsl:value-of select="meta/css" />
			/* ]]&gt; */
			</style>
		</xsl:if>
	</xsl:template>
</xsl:stylesheet>