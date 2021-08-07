<?php

namespace App\Web\API;
use Leochenftw\Restful\RestfulController;
use Page;
use SilverStripe\CMS\Model\SiteTree;
use Leochenftw\Util;

class SearchAPI extends RestfulController
{
    /**
     * Defines methods that can be called directly
     * @var array
     */
    private static $allowed_actions = [
        'post' => true
    ];

    public function post($request)
    {
        if (!Util::check_csrf($request)) {
            return $this->httpError(400, 'Invalid CSRF token');
        }

        if (!empty($request->postVar('advanced'))) {
            return $this->advanced_search($request);
        }

        return $this->basic_search($request);
    }

    private function highlightkeyword($str, $search = null)
    {
        if (empty($search)) {
            return Util::getWords($str, 50);
        }
        return $this->trim_unwanted(str_ireplace($search, '<span class="search-highlight">' . $search . '</span>', $str));
    }

    private function trim_unwanted($str)
    {
        if (strpos($str, '<span class="search-highlight">') === false) {
            $str    =   explode(' ', $str);
            $str    =   array_slice($str, 0, 50);
            $ellipsis   =   count($str) > 50;
            return implode(' ', $str) . ($ellipsis ? '...' : '');
        }

        $upper  =   strstr($str, '<span class="search-highlight">', true);
        $remain =   strstr($str, '<span class="search-highlight">');
        $main   =   strstr($remain, '</span>', true);
        $lower  =   strstr($remain, '</span>'); // As of PHP 5.3.0

        $upper  =   explode(' ', $upper);
        $upper_ellipsis =   count($upper) > 15;
        $upper  =   array_slice($upper, -15, 15, true);
        $lower  =   explode(' ', $lower);
        $lower_ellipsis =   count($lower) > 25;
        $lower  =   array_slice($lower, 0, 25);

        return ($upper_ellipsis ? '...' : '') . implode(' ', $upper) . $main . implode(' ', $lower) . ($lower_ellipsis ? '...' : '');
    }

    private function parse_result(&$result, $term = null, $title_term = null, $log = true)
    {
        $data   =   [];
        foreach ($result as $item) {
            $data[] =   [
                'title'         =>  $this->highlightkeyword(strip_tags($item->Title), $title_term ? $title_term : $term),
                'content'       =>  $this->highlightkeyword(strip_tags($item->Content), $term),
                'type'          =>  $item->singular_name(),
                'url'           =>  $item->Link() == '/' ? '/' : rtrim($item->Link(), '/')
            ];
        }

        return $data;
    }

    private function basic_search(&$request)
    {
        if ($term = $request->postVar('term')) {
            $filter =   ['SearchFields:Fulltext' => $term];

            $result =   SiteTree::get()->filter($filter);

            if ($result->count() == 0 && count(explode(' ', $term)) > 2) {
                $result =   SiteTree::get()->filterAny(['Title:PartialMatch' => $term, 'Content:PartialMatch' => $term]);
            } else {
                $result =   $result->filterAny(['Title:PartialMatch' => $term, 'Content:PartialMatch' => $term]);
            }

            return $this->parse_result($result, $term);
        }

        return $this->httpError(400, 'Missing search term');
    }

    private function advanced_search(&$request)
    {
        // $count  =   ceil($result->count() / 10);
        // $result =   $result->limit(10, $page * 10);
        //
        // return [
        //     'list'  =>  $this->parse_result($result, !empty($keyword) ? $keyword : null, !empty($title) ? $title : null),
        //     'total' =>  $count
        // ];
    }
}
