# mirage.php
* A template library with deadly simple
* Get inspired from [Tistory skin template(티스토리 스킨 가이드;ko_KR)](https://tistory.github.io/document-tistory-skin)
* Tested on `PHP 5.5` only ☠
## License
* LGPL-2.1
## Example
### Template
```.php
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
$tpl->applyAllItemsToHTML();
$tpl->render();
```

### Meta data parser
* Check out [Meta info xml specs from Tistory offical docs(스킨 정보 파일;ko_KR)](https://tistory.github.io/document-tistory-skin/common/index.xml.html).
```.php
$meta = new Mirage\MetaDataParser();
$meta->fromFile("test.xml");
print_r($meta->getAuthor());
```
