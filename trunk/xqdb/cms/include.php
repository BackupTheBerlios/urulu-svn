<?PHP
  define("CMS_BASE", realpath(dirname(__FILE__)) . "/");
  require_once(CMS_BASE . "renderURL.php");
  require_once(CMS_BASE . "binLogin.php");
  require_once(CMS_BASE . "logout.php");
  require_once(CMS_BASE . "getCurrentUser.php");
?>