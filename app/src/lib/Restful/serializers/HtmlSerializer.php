<?php

namespace Leochenftw\Restful;
use SilverStripe\View\ViewableData;
use SilverStripe\View\ArrayData;
use SilverStripe\ORM\ArrayList;

class HtmlSerializer extends ViewableData implements IRestSerializer
{
    /**
     * @config
     */
    private static $is_active = true;

    private $contentType = "text/html";

    public function serialize($data)
    {
        $list = $this->recursive($data, 1);
        return $this->renderWith(['Result', 'Controller'], ['Data' => ArrayList::create($list)]);
    }

    public function contentType()
    {
        return $this->contentType;
    }

    private function recursive($data, $level)
    {
        $list = [];
        if(is_array($data)) {
            foreach ($data as $key => $value) {
                if(is_array($value)) {
                    $list[] = ArrayData::create(['Key' => $key, 'Value' => '', 'Heading' => true, 'Level' => $level]);
                    $list = array_merge($list, $this->recursive($value, $level+1));
                } else {
                    $list[] = ArrayData::create(['Key' => $key, 'Value' => $value, 'Level' => $level]);
                }
            }
        }
        return $list;
    }

    public function active()
    {
        return static::$is_active;
    }
}
