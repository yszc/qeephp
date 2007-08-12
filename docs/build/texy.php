<?php

array_shift($argv);

if ($argc < 1) {
    help();
    exit(0);
}

$filename = $argv[0];
array_shift($argv);
if (!is_readable($filename)) {
    echo "Can't open {$filename}.\n\n";
    help();
    exit(0);
}

$text = file_get_contents($filename);

require(dirname(__FILE__) . '/fshl/fshl.php');
require(dirname(__FILE__) . '/texy/texy/texy.compact.php');
$texy = new Texy();
$texy->handler = new myHandler;

$html = $texy->process($text);
$title = $texy->headingModule->title;
unset($text);

$opts = array();
foreach ($argv as $arg) {
    $pos = strpos($arg, '=');

    if ($pos !== false) {
        $value = substr($arg, $pos + 1);
        $arg = substr($arg, 0, $pos);
    } else {
        $value = true;
    }

    $opts[$arg] = $value;
}

if (!empty($opts['--output'])) {
    $output = $opts['--output'];
    ob_start();
}

echo <<<EOT
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>{$title}</title>

EOT;

if (!empty($opts['--inline-css'])) {
    echo <<<EOT

<style type="text/css">
body { margin: 0; padding: 0; color: #000; background-color: #fff; font: 12px Verdana, Arial, Helvetica, sans-serif; }
tr.odd td, tr.even td { padding: 0.3em; }
h1, h2, h3, h4, h5, h6 { margin-bottom: 0.5em; }
h1 { text-align: center; font-size: 2.2em; }
h2 { font-size: 1.6em; color: #633; }
h3 { margin-top: 2.0em; }
h3, h4, h5, h6 { font-size: 1.0em; }
p { margin-top: 0.5em; margin-bottom: 0.9em; }
a { text-decoration: none; font-weight: bold; }
a:link { color: #39c; }
a:visited { color: #369; }
a:hover { color: #39c; text-decoration: underline; }
pre { background-color: #eee; padding: 0.75em 1.5em; font-size: 12px; border: 1px solid #ddd; line-height: 140%; font-family: "Courier New", Courier, monospace; }
table { font-size: 1em; }
hr { border-bottom: 1px solid white; border-top: 1px dotted #ccc; margin-bottom: 10px; }
#contents { padding: 10px 20px 16px 20px; }
.author { text-align: center; margin-bottom: 20px; color: #666; }
image { padding: 6px; }
/* P H P */
.php-keyword1 {color:blue; font-weight:bold;}
.php-keyword2 {color:blue; }
.php-var {color:#f30; font-weight:bold;}
.php-num {color:red;}
.php-quote {color:#843; font-weight:bold;}
.php-vquote {color:#fa0;}
.php-comment {color:#696;}
/* H T M L */
.html-tag {color:#598527; font-weight:bold;}
.html-tagin {color:#89A315}
.html-quote {color:#598527; font-weight:bold;}
.html-comment {color:#999; background-color:#F1FAE4;}
.html-entity {color:#89A315}
/* C S S */
.css-class {color:#004A80; }
.css-id {color:#7DA7D9; font-weight:bold; }
.css-def {color:#5674B9;}
.css-property {color:#003663; font-weight:bold; }
.css-value {color:#448CCB;}
.css-color {color:#0076A3;}
.css-comment { background-color:#E5F8FF; color:#999; }
/* C P P */
.cpp-keywords1 {color:blue; font-weight:bold;}
.cpp-num {color:red;}
.cpp-quote {color:brown; font-weight:bold;}
.cpp-comment {color:green;}
.cpp-preproc {color:grey;}
/* J A V A */
.java-keywords1 {color:blue; font-weight:bold;}
.java-num {color:red;}
.java-quote {color:brown; font-weight:bold;}
.java-comment {color:green;}
.java-preproc {color:grey;}
/* J a v a S c r i p t */
.js-out {color:#898993;}
.js-keywords1 {color:#575757; font-weight:bold;}
.js-num {color:#575757;}
.js-quote {color:#575757; font-weight:bold;}
.js-comment {color:#898993; background-color:#F4F4F4;}
/* S Q L */
.sql-keyword1 {color: #DD0000; font-weight: bold;}
.sql-keyword2 {color: #DD2222;}
.sql-keyword3 {color: #0000FF; font-weight: bold;}
.sql-value {color: #5674B9;}
.sql-comment {color: #FFAA00;}
.sql-num {color:red;}
.sql-option {color: #004A80; font-weight: bold;}
/* P y t h o n */
.py-keyword1 {color: #0033CC; font-weight: bold;}
.py-keyword2 {color: #CE3333; font-weight: bold;}
.py-keyword3 {color: #660066; font-weight: bold;}
.py-number {color: #993300;}
.py-docstring {color: #E86A18;}
.py-quote {color: #878787; font-weight: bold;}
.py-comment {color: #009900; font-style: italic;}
/* T E X Y */
.texy-hlead {color:#44B; font-weight:bold;}
.texy-hbody {background-color:#eeF;color:#44B; }
.texy-hr {color:#B44; }
.texy-code {color:#666;}
.texy-html {color:#6a6;}
.texy-text {color:#66a;}
.texy-err {background-color:red; color:white;}
/* C O M M O N */
.normal {color:black;}
.xlang {color:red; font-weight:bold;}
.count {color:black; background-color:#FFF;}
</style>

EOT;

} else {
    echo '<link href="css/style.css" rel="stylesheet" type="text/css" />';
    echo "\n";
}

echo <<<EOT
<body>
<div id="contents">
{$html}
</div>
</body>
</html>

EOT;

if (!empty($opts['--output'])) {
    $filename = $opts['--output'];
    file_put_contents($filename, ob_get_clean());
}


function help()
{
    echo <<<EOT
Convert text to html use texy.

texy <input filename>


EOT;
}


// this is user callback object for processing Texy events
class myHandler
{

    /**
     * User handler for code block
     *
     * @param TexyBlockParser
     * @param string  block type
     * @param string  text to highlight
     * @param string  language
     * @param TexyModifier modifier
     * @return TexyHtml
     */
    public function block($parser, $blocktype, $content, $lang, $modifier)
    {
        if ($blocktype !== 'block/code')
            return Texy::PROCEED;

        $texy = $parser->texy;

        $lang = strtoupper($lang);
        if ($lang == 'JAVASCRIPT') $lang = 'JS';
        if (!in_array(
                $lang,
                array('CPP', 'CSS', 'HTML', 'JAVA', 'PHP', 'JS', 'SQL'))
           ) return Texy::PROCEED;

        $parser = new fshlParser('HTML_UTF8', P_TAB_INDENT);

        $content = $texy->blockModule->outdent($content);
        $content = $parser->highlightString($lang, $content);
        $content = $texy->protect($content);

        $elPre = TexyHtml::el('pre');
        if ($modifier) $modifier->decorate($texy, $elPre);
        $elPre['class'] = strtolower($lang);

        $elCode = $elPre->add('code', $content);

        return $elPre;
    }

}
