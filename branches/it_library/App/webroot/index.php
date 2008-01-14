<?PHP

include_once '../../Qee.php';

if (0)// 或者用这样的方式
{
	define( 'ITLIB_DIR', '/www/phplib' );
	include_once ITLIB_DIR . '/Lib/Qee.php';
}



Qee::import(dirname(__FILE__).'/../');
Qee::import(dirname(__FILE__).'/../My');


//Qee::import(realpath('../Forum'));	// itcom.Forum
Qee::init();
Qee::loadAppInf('../_config.php');

Qee::runMVC();

echo '<hr />';

$nav = array(
'home' => url('home'),
'search word' => url('home', 'search', 'word'),
'test list page 2' => url('test', 'list', 2)
);

foreach($nav as $title => $link)
{
	printf("<a href=\"%s\" title=\"%s\">%s</a>\n", $link, $title, $title);
}
echo "\n";
echo '<hr>';
//phpinfo(INFO_VARIABLES);
echo '<hr>';
echo "<pre>\n";
	
//echo "\nservers: ",	print_r($_SERVER, true), "\n";
echo "\nincluded_files: ",	print_r(get_included_files(), true), "\n";