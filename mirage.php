<?php // declare(strict_types=1); // STILL NOT USE FOR PHP5.*
namespace Mirage;

/**
 * Mirage : A template library with deadly simple.
 * Get inspired from Tistory skin template.
 * 
 * @author  David Jung (https://github.com/davidjung-kr)
 * @link    https://github.com/davidjung-kr/mirage.php
 * @license https://www.gnu.org/licenses/old-licenses/lgpl-2.1.html LGPL-2.1
 * @see     https://tistory.github.io/document-tistory-skin (KOR)Tistory skin guide
 */

/** Template file read size. */
define("MRG_MAX_TEMPLATE_FILESIZE_KB", 256);

/** About template processing */
class Template {
    /** @var bool */   private $needEncodeHTML;
    /** @var array */  private $itArr;
    /** @var array */  private $ulArr;
    /** @var array */  private $olArr;
    /** @var string */ private $tagHead;
    /** @var string */ private $tagTail;
    /** @var string */ private $html = "";
    /** `true` when stacked on array of items or list items.
     * @var bool
     */
    private $stacked;
    /** Get HTML contents.
     * @return string */
    public function getHTML() { return $this->html; }

    /** Return `true` when items or list items not empty.
     * @return string */
    public function isStacked() { return  $this->stacked; }

    /**
     * Construct
     * 
     * @param string $path Template file path.
     * @param TemplateOption $opt Detail options of template processing.
     */
    function __construct($path=null, $opt=null) {
        $this->openFromFile($path);
        if ($opt == null || $opt instanceof TemplateOption==false) {
            $opt = new TemplateOption();
        }
        $this->needEncodeHTML = $opt->getNeedEncodeHTMLEntity();
        $this->tagHead = $opt->getTagHead();
        $this->tagTail = $opt->getTagTail();
        $this->itArr = array();
        $this->ulArr = array();
        $this->olArr = array();
        $this->stacked = false;
    }

    /**
     * Open and read template file.
     * 
     * Use read file size with `MRG_MAX_KB_FILESIZE`.
     * Default size is 256 KB.
     * 
     * @param string $path Template file path.
     * @throws \Exception Throw it if $path invalid.
     */
    public function openFromFile($path) {
        if (file_exists($path)==false) {
            throw new \Exception("Can't find a file");
        }
        $tplSize = filesize($path);
        $tplSize = $tplSize>0 ? round($tplSize/1024):$tplSize;
        if ($tplSize > MRG_MAX_TEMPLATE_FILESIZE_KB) {
            throw new \Exception("Out of max file size.");
        }
        $this->html = $path==null ? "":file_get_contents($path);
    }
    
    /**
     * Remove all of blank space characters from HTML contents.
     */
    public function updateHTMLForRemoveBlankSpace() {
        $this->html = $this->removeAllOfBlankSpaceCharacters($this->html);
    }

    /**
     * Just print all html contents;
     */
    public function render() {
        echo($this->html);
    }

    /**
     * Add items.
     * 
     * @param array $map array of items.
     * 
     * @return bool success or not. 
     */
    public function addItems($map) {
        if (is_array($map)==false || count($map)<=0) {
            return false;
        }
        if ($this->needEncodeHTML) {
            $map = $this->encodeSpecialcharsToHTMLEntity($map);
        }
        array_push($this->itArr, $map);
        $this->stacked = true;
        return true;
    }

    /**
     * Add list item (ul or ol).
     * 
     * @param int|Mirage\ListType $listType Choose bewteen `<ol>` and `<ul>`.
     * @param string $tagName Target element tag name.
     * @param array $listItems List items.
     * 
     * @return bool success or not. 
     */
    public function addListItems($listType, $tagName, $listItems) {
        if (is_string($tagName)==false || is_array($listItems)==false || count($listItems)<=0) {
            return false;
        }

        if ($this->needEncodeHTML) {
            $listItems = $this->encodeSpecialcharsToHTMLEntity($listItems);
        }

        switch($listType) {
        case ListType::ORDERED:
            $this->olArr[$tagName] = $listItems;
            break;
        case ListType::UNORDERED:
            $this->ulArr[$tagName] = $listItems;
            break;
        default:
            Log::printWarning("$listType value is wrong => {$listType}");
            return false;
        }
        $this->stacked = true;
        return true;
    }

    /**
     * Apply all of items to HTMl contents.
     */
    public function applyAllItemsToHTML() {
        $this->updateAndClearItems();
        $dom = new \DOMDocument();
        @$dom->loadHTML($this->html);
        foreach($this->ulArr as $tagName => $items) {
            $this->updateAndClearListItems($dom, "ul", $tagName, $items);
        }
        foreach($this->olArr as $tagName => $items) {
            $this->updateAndClearListItems($dom, "ol", $tagName, $items);
        }
        $this->updateStackStatus();
        $this->decodeHTMLEntityToChars();
    }

    private function updateAndClearItems () {
        while(true) {
            $map = array_pop($this->itArr);
            if ($map==null || is_array($map)==false) {
                break;
            }

            foreach($map as $k => $v) {
                if (is_string($v)) {
                    $this->html = str_replace($this->tagHead.$k.$this->tagTail, $v, $this->html);
                } else {
                    Log::printWarning("Item of element '{$k}' is not string type. Type:".gettype($v));
                }
                
            }
        }
        $this->updateStackStatus();
    }

    private function updateAndClearListItems(&$dom, $listTagName, $tagName, $items) {
        if ($dom==null) {
            throw new \Exception("$dom is null.");
        } else if ($dom instanceof \DOMDocument == false){
            throw new \Exception("$dom type's wrong.");
        }

        $nodes = $dom->getElementsByTagName($tagName);
        if ($nodes==false || count($nodes)<=0) {
            Log::printWarning("Can't find tag => {$tagName}");
            return;
        }
        $oldNode = $nodes->item(0);
        $ul = $dom->createElement($listTagName);
        while(true) {
            $v = array_shift($items);
            if ($v==null || (is_string($v)==false && is_array($v)==false) ) {
                break;
            }

            // li이 string인 경우
            if (is_string($v)) {
                $ul->insertBefore(
                    $dom->createElement("li", $v)
                );
                continue;
            }

            // li이 arr인 경우
            if (array_key_exists("innerHTML", $v)==false) {
                continue;
            }
            
            $innerHTML = trim($v["innerHTML"]);
            if (strlen($innerHTML)<=0) {
                continue;
            }

            unset($v["innerHTML"]);
            $li = $dom->createElement("li", $innerHTML);
            foreach($v as $keyOfAttr => $valOfAttr) {
                $li->setAttribute($keyOfAttr, $valOfAttr);
                $ul->insertBefore($li);
            }
        }
        $oldNode->parentNode->replaceChild($ul, $oldNode);
        $this->html = $dom->saveHTML();
        $this->updateStackStatus();
    }

    private function updateStackStatus() {
        return count($this->itArr)>0 || count($this->ulArr)>0 || count($this->olArr)>0;
    }

    private function removeAllOfBlankSpaceCharacters($str) {
        $search = array('/\>[^\S ]+/s', '/[^\S ]+\</s', '/(\s)+/s', '/<!--(.|\s)*?-->/' );
        $replace = array('>', '<', '\\1', '');
        $str = preg_replace($search, $replace, $str);
        return $str;
    }

    /**
     * Encode special characters to HTML entity.
     * 
     * @param array Array or map of string.
     */
    private function encodeSpecialcharsToHTMLEntity($map) {
        $newMap = $map;
        foreach($newMap as $k => $v) {
            if (is_string($v)) {
                $newMap[$k] = htmlspecialchars($v);
            } else if (is_array($v)) {
                $this->encodeSpecialcharsToHTMLEntity($v);
            } else {
                $newMap[$k] = $v;
            }
        }
        return $newMap;
    }

    /** Change unicode characters of HTML entity to characters. */
    private function decodeHTMLEntityToChars() {
        $matches = array();
        $findCount = preg_match_all("(&#\d{5};)", $this->html, $matches);
        if ($findCount<=0 || $findCount==false) {
            return;
        }
        $targets = array_unique($matches[0]);
        foreach($targets as $target) {
            $this->html = str_replace($target, html_entity_decode($target), $this->html);
        }
    }
}

/** TemplateOption */
class TemplateOption {
    private $needEncodeHTMLEntity;
    private $tagHead;
    private $tagTail;

    /**
     * @param string $needEncodeHTMLEntity Call `htmlspecialchars` to innerHTML string when add item or elements.
     * @param string $tagHead A head string for replace item.
     * @param string $tagHead A tail string for replace item.
     * 
     * @see htmlspecialchars(https://www.php.net/manual/en/function.htmlspecialchars.php)
     */
    function __construct($needEncodeHTMLEntity=true, $tagHead="[##_", $tagTail="_##]") {
        $this->needEncodeHTMLEntity = (
            ($needEncodeHTMLEntity==null) || is_bool($needEncodeHTMLEntity)==false
        ) ? true:$needEncodeHTMLEntity;
        $this->tagHead = (
            ($tagHead==null) || is_string($tagHead)==false|| strlen($tagHead)<=0
        ) ? "[##_":$tagHead;
        $this->tagTail = (
            ($tagTail==null) || is_string($tagTail)==false || strlen($tagTail)<=0
        ) ? "_##]":$tagTail;
    }
    public function getNeedEncodeHTMLEntity() { return $this->needEncodeHTMLEntity; }
    public function getTagHead() { return $this->tagHead; }
    public function getTagTail() { return $this->tagTail; }
}

/** Defined about List item of HTML element. */
class ListType {
    /** It's mean `<ul>` */
    const UNORDERED = 0;
    /** It's mean `<ol>` */
    const ORDERED = 1;
}

/**
 * Read skin infomaiton from XML file.
 * 
 * @see https://tistory.github.io/document-tistory-skin/common/index.xml.html
 */
class MetaDataParser {
    private $informationArr;
    private $authorArr;
    private $commentMessageArr;
    private $trackbackMessageArr;
    private $treeArr;
    private $recentEntries;
    private $recentComments;
    private $recentTrackbacks;
    private $itemsOnGuestbook;
    private $tagsInCloud;
    private $sortInCloud;
    private $expandComment;
    private $expandTrackback;
    private $lengthOfRecentNotice;
    private $lengthOfRecentEntry;
    private $lengthOfRecentComment;
    private $lengthOfRecentTrackback;
    private $lengthOfLink;
    private $showListOnCategory;
    private $showListOnArchive;
    private $contentWidth;

    function __construct() {
        $this->informationArr = array();
        $this->authorArr = array();
        $this->commentMessageArr = array();
        $this->trackbackMessageArr = array();
        $this->treeArr = array();
        $this->recentEntries = "";
        $this->recentComments = "";
        $this->recentTrackbacks = "";
        $this->itemsOnGuestbook = "";
        $this->tagsInCloud = "";
        $this->sortInCloud = "";
        $this->expandComment = "";
        $this->expandTrackback = "";
        $this->lengthOfRecentNotice = "";
        $this->lengthOfRecentEntry = "";
        $this->lengthOfRecentComment = "";
        $this->lengthOfRecentTrackback = "";
        $this->lengthOfLink = "";
        $this->showListOnCategory = "";
        $this->showListOnArchive = "";
        $this->contentWidth = "";
    }

    public function fromFile($path) {
        $xmlStr = file_get_contents($path);
        if ($xmlStr==false) {

        }
        $xmlObj = new \SimpleXMLElement($xmlStr);
        $this->parsingInfomation($xmlObj);
        $this->parsingAuthor($xmlObj);
        $this->parsingDefault($xmlObj);
    }

    /** 제작자 : 스킨 정보에서 표시할 제작자 정보
     * @return array */
    public function getAuthor() { return $this->authorArr; }
    /** 표시되는 이름
     * @return string */
    public function getAuthorName() { return $this->authorArr["name"]; }
    /** 제작자 웹사이트 주소
     * @return string */
    public function getAuthorHomepage() { return $this->authorArr["homepage"]; }
    /** 연락할 이메일 주소
     * @return string */
    public function getAuthorEmail() { return $this->authorArr["email"]; }
    /** 기본 정보 : 스킨 목록, 상세보기에서 표시되는 정보
     * @return array */
    public function getInformation() { return $this->informationArr; }
    /** 표시되는 이름
     * @return string */
    public function getInformationName() { return $this->informationArr["name"]; }
    /** 스킨 버전
     * @return string */
    public function getInformationVersion() { return $this->informationArr["version"]; }
    /** 스킨 상세 설명
     * @return string */
    public function getInformationDescription() { return $this->informationArr["description"]; }
    /** 저작권
     * @return string */
    public function getInformationLicense() { return $this->informationArr["license"]; }
    /** @return array */
    public function getCommentMessage() { return $this->commentMessageArr; }
    /** @return string */
    public function getCommentMessageNone() { return $this->commentMessageArr["none"]; }
    /** @return string */
    public function getCommentMessageSingle() { return $this->commentMessageArr["single"]; }
    /** @return array */
    public function getTrackbackMessage() { return $this->trackbackMessageArr; }
    /** @return string */
    public function getTrackbackMessageNone() { return $this->trackbackMessageArr["none"]; }
    /** @return string */
    public function getTrackbackMessageSingle() { return $this->trackbackMessageArr["single"]; }
    /** 카테고리
     * @return array */
    public function getTree() { return $this->treeArr; }
    /** 카테고리 글자색
     * @return string */
    public function getTreeColor() { return $this->treeArr["color"]; }
    /** 카테고리 배경색
     * @return string */
    public function getTreeBgColor() { return $this->treeArr["bgColor"]; }
    /** 선택시 글자색
     * @return string */
    public function getTreeActiveColor() { return $this->treeArr["activeColor"]; }
    /** 선택시 배경색
     * @return string */
    public function getTreeActiveBgColor() { return $this->treeArr["activeBgColor"]; }
    /** 표현될 카테고리 글자 수
     * @return string */
    public function getTreeLabelLength() { return $this->treeArr["labelLength"]; }
    /** 카테고리 글 수 표현(0:숨김, 1:보임)
     * @return string */
    public function getTreeShowValue() { return $this->treeArr["showValue"]; }
    /** 사이드바의 최근글 표시 갯수
     * @return string */
    public function getRecentEntries() { return $this->recentEntries; }
    /** 사이드바의 최근 댓글 표시 갯수
     * @return string */
    public function getRecentComments() { return $this->recentComments; }
    /** 사이드바의 최근 트랙백 표시 갯수
     * @return string */
    public function getRecentTrackbacks() { return $this->recentTrackbacks; }
    /** 한페이지에 표시될 방명록 갯수 *
     * @return string */
    public function getItemsOnGuestbook() { return $this->itemsOnGuestbook; }
    /** 사이드바에 표시될 태그 갯수
     * @return string */
    public function getTagsInCloud() { return $this->tagsInCloud; }
    /** 태그 클라우드 표현 방식 (1:인기도순, 2:이름순, 3:랜덤)
     * @return string */
    public function getSortInCloud() { return $this->sortInCloud; }
    /** 댓글영역 표현 방식 (0:감추기, 1:펼치기)
     * @return string */
    public function getExpandComment() { return $this->expandComment; }
    /** 트랙백영역 표현 방식 (0:감추기, 1:펼치기)
     * @return string */
    public function getExpandTrackback() { return $this->expandTrackback; }
    /** 최근 공지 표현될 글자수
     * @return string */
    public function getLengthOfRecentNotice() { return $this->lengthOfRecentNotice; }
    /** 최근 글 표현될 글자수
     * @return string */
    public function getLengthOfRecentEntry() { return $this->lengthOfRecentEntry; }
    /** 최근 댓글에 표현될 글자수
     * @return string */
    public function getLengthOfRecentComment() { return $this->lengthOfRecentComment; }
    /** 최근 트랙백에 표현될 글자수
     * @return string */
    public function getLengthOfRecentTrackback() { return $this->lengthOfRecentTrackback; }
    /** 링크에 표현될 글자수
     * @return string */
    public function getLengthOfLink() { return $this->lengthOfLink; }
    /** 커버 미사용 홈에서 글 목록 표현(0:내용만, 1:목록만, 2: 내용+목록)
     * @return string */
    public function getShowListOnCategory() { return $this->showListOnCategory; }
    /** @return string */
    public function getShowListOnArchive() { return $this->showListOnArchive; }
    /** 콘텐츠영역 가로 사이즈, 이 사이즈에 맞춰 에디터의 위지윅이 제대로 구현된다.
     * @return string */
    public function getContentWidth() { return $this->contentWidth; }

    private function parsingInfomation($xmlObj) {
        $information = $xmlObj[0]->information[0];
        $this->informationArr = array(
              "name" => $this->castToStr($information->name[0])
            , "version" => $this->castToStr($information->version[0])
            , "description" => $this->castToStr($information->description[0])
            , "license" => $this->castToStr($information->license[0])
        );
    }

    private function parsingAuthor($xmlObj) {
        $author = $xmlObj[0]->author[0];
        $this->authorArr = array(
            "name" => $this->castToStr($author->name[0])
          , "homepage" => $this->castToStr($author->homepage[0])
          , "email" => $this->castToStr($author->email[0])
        );
    }

    private function parsingDefault($xmlObj) {
        $default = $xmlObj[0]->default[0];
        $this->recentEntries = $this->castToStr($default->recentEntries[0]);
        $this->recentComments = $this->castToStr($default->recentComments[0]);
        $this->recentTrackbacks = $this->castToStr($default->recentTrackbacks[0]);
        $this->itemsOnGuestbook = $this->castToStr($default->itemsOnGuestbook[0]);
        $this->tagsInCloud = $this->castToStr($default->tagsInCloud[0]);
        $this->sortInCloud = $this->castToStr($default->sortInCloud[0]);
        $this->expandComment = $this->castToStr($default->expandComment[0]);
        $this->expandTrackback = $this->castToStr($default->expandTrackback[0]);
        $this->lengthOfRecentNotice = $this->castToStr($default->lengthOfRecentNotice[0]);
        $this->lengthOfRecentEntry = $this->castToStr($default->lengthOfRecentEntry[0]);
        $this->lengthOfRecentComment = $this->castToStr($default->lengthOfRecentComment[0]);
        $this->lengthOfRecentTrackback = $this->castToStr($default->lengthOfRecentTrackback[0]);
        $this->lengthOfLink = $this->castToStr($default->lengthOfLink[0]);
        $this->showListOnCategory = $this->castToStr($default->showListOnCategory[0]);
        $this->showListOnArchive = $this->castToStr($default->showListOnArchive[0]);
        $this->contentWidth = $this->castToStr($default->contentWidth[0]);

        $commentMessageArr = $default->commentMessage[0];
        $this->commentMessageArr = array(
              "none" => $this->castToStr($commentMessageArr->none[0])
            , "single" => $this->castToStr($commentMessageArr->single[0])
        );

        $trackbackMessageArr = $default->trackbackMessage[0];
        $this->trackbackMessageArr = array(
              "none" => $this->castToStr($trackbackMessageArr->none[0])
            , "single" => $this->castToStr($trackbackMessageArr->single[0])
        );

        $treeArr = $default->tree[0];
        $this->treeArr = array(
            "color" => $this->castToStr($treeArr->color[0])
          , "bgColor" => $this->castToStr($treeArr->bgColor[0])
          , "activeColor" => $this->castToStr($treeArr->activeColor[0])
          , "activeBgColor" => $this->castToStr($treeArr->activeBgColor[0])
          , "labelLength" => $this->castToStr($treeArr->labelLength[0])
          , "showValue" => $this->castToStr($treeArr->showValue[0])
        );
    }

    private function castToStr($o) {
        $s = "";
        if (is_array($o)) {
            $s = implode("", $o);
        } else if ($o!=null) {
            $s = (string)$o;
        }
        return trim($s);
    }
}

/** Print warning message. */
class Log {
    public static function printWarning($msg="") {        
        trigger_error($msg, E_USER_WARNING);
    }
}
?>