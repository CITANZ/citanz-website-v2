<?php

namespace Leochenftw\Restful;

interface IRestSerializer
{
    public function serialize($data);
    public function contentType();
    public function active();
}
