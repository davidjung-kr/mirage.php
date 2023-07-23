
<?php
require_once(__DIR__.DIRECTORY_SEPARATOR."mirage.php");

/**
 * Mirage\Template Test
 */
$tpl = new Mirage\Template("test.html");

$tpl->addItems(array(
        "mirage_test_title1"=>"Mirage template library"
    ,   "mirage_test_title2"=>"Introduce"
    ,   "mirage_test_title3"=>"You should know..."
    ,   "mirage_test_title4"=>"See also"
    ,   "mirage_test_title5" =>"MetaData Parser test"
    ,   "mirage_test_anchor_href" => "https://github.com/davidjung-kr/mirage.php"
    ,   "mirage_test_anchor_innerhtml" => "here"
));

$tpl->addListItems(Mirage\ListType::ORDERED, "mirage_test_ol", array(
        "<a href='oops'>HTML special characters will change to entity by default.</a>"
    ,   "If you wnat change it then check out Mirage\TemplateOption->\$needEncodeHTMLEntity"
));

$tpl->addListItems(Mirage\ListType::UNORDERED, "mirage_test_ul", array(
        array( 
            "innerHTML"=>"https://github.com/davidjung-kr"
        ,   "id"=>"mirage-url"
        ,   "style"=>"color:blue;")
    ,   "https://tistory.github.io/document-tistory-skin"
));

/**
 * Mirage\MetaDataParser Test
 */
$meta = new Mirage\MetaDataParser();
$meta->fromFile("test.xml");
$tpl->addListItems(Mirage\ListType::UNORDERED, "mirage_metadataparser_test_ul", $meta->getAuthor());

$tpl->applyAllItemsToHTML();
$tpl->render();
?>