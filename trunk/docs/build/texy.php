<?php

if ($argc < 2) {
    help();
    exit(0);
}

$filename = $argv[1];
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


echo <<<EOT
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>{$title}</title>
<link href="css/style.css" rel="stylesheet" type="text/css" />
<body>
<div id="contents">
{$html}
</div>
</body>
</html>

EOT;


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
