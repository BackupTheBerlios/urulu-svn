<?xml version="1.0" encoding="UTF-8" ?>
<xsl:stylesheet version="1.0" xmlns:v="urn:fund-raiser"
  xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
	<xsl:include href="meta_header.xsl" />
	<xsl:include href="header.xsl" />
	<xsl:include href="footer.xsl" />

	<xsl:template match="/application">
		<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="de" lang="de">
			<head>
				<xsl:call-template name="meta_header"/>
			</head>
			<body>
				<a name="top" />
				<xsl:comment><!-- Start Header --></xsl:comment>
				<xsl:call-template name="header"/>
				<xsl:comment><!-- End Header --></xsl:comment>
				<xsl:comment><!-- Start Mainnavigation --></xsl:comment>
				<xsl:comment><!-- Start Menu --></xsl:comment>
				<div id="menu">
					<ul>
						<xsl:for-each select="navigation/menu/page">
						<xsl:if test="visible/text() = 'true'">
						<li><xsl:if test="@current = 'true'"><xsl:attribute name="id">currentmenu</xsl:attribute></xsl:if>
							<a><xsl:attribute name="href"><xsl:value-of select="./url" /></xsl:attribute><xsl:value-of select="./name" /></a>
						</li>
						</xsl:if>
					</xsl:for-each> 
					</ul>
				</div>
				<xsl:comment><!-- End Menu --></xsl:comment>
				<div id="login">
					<ul>
						<li><xsl:value-of select="login/user" /></li>
						<li>
						<xsl:choose>
							<xsl:when test="login/user/text() = 'default'"><a href="/app.php/login">Login</a></xsl:when><xsl:otherwise><a href="/app.php/logout">Abmelden</a></xsl:otherwise>
						</xsl:choose>
						</li>
					</ul>
				</div>
				<xsl:comment><!-- End Mainnavigation --></xsl:comment>
				<xsl:comment><!-- Start Container --></xsl:comment>
				<div id="container">
					<xsl:comment><!-- Start YouAreHere --></xsl:comment>
					<div id="youarehere">
					<ul>
						<xsl:for-each select="navigation/youarehere/page">
						<li><xsl:choose>
							<xsl:when test="position() = 1"><xsl:attribute name="class">first</xsl:attribute></xsl:when>
							<xsl:when test="position() = last()"><xsl:attribute name="class">last</xsl:attribute></xsl:when>
						</xsl:choose>
						<a><xsl:attribute name="href"><xsl:value-of select="url" /></xsl:attribute><xsl:value-of select="name" /></a></li>
						</xsl:for-each>
					</ul>
					</div>
					<xsl:comment><!-- End YouAreHere --></xsl:comment>
					<xsl:comment><!-- Start Subnavigation --></xsl:comment>
					<div id="navigation">
						<xsl:comment><!-- Start Submenu --></xsl:comment>
						<div id="submenu">
							<ul>
							<xsl:for-each select="navigation/menu/page[@current = 'true']/sub/page">
							<xsl:if test="visible = 'true'">
								<li>
									<a><xsl:attribute name="href"><xsl:value-of select="url" /></xsl:attribute><span><xsl:value-of select="name" /></span></a>
								</li>
								<xsl:if test="sub">
								<ul>
								<xsl:for-each select="sub/page">
									<li>
										<a><xsl:attribute name="href"><xsl:value-of select="url" /></xsl:attribute><span><xsl:value-of select="name" /></span></a>
									</li>
								</xsl:for-each>
								</ul>
								</xsl:if>
							</xsl:if>
							</xsl:for-each>
							</ul>
						</div>
						<xsl:comment><!-- End Submenu --></xsl:comment>
					</div>
					<xsl:comment><!-- End  Subnavigation --></xsl:comment>
					<xsl:comment><!-- Start Content --></xsl:comment>
					<div id="content">
						<xsl:value-of select="content" />
					</div>
					<xsl:comment><!-- End Content --></xsl:comment>
					<xsl:comment><!-- Start Footer --></xsl:comment>
					<xsl:call-template name="footer"/>
					<xsl:comment><!-- End Footer --></xsl:comment>
				</div>
				<xsl:comment><!-- End Container --></xsl:comment>
			</body>
		</html>
	</xsl:template>
</xsl:stylesheet>

