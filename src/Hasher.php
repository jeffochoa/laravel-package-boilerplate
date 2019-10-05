<?php

namespace Square1\ResponseCache;

use Illuminate\Http\Request;

class Hasher
{
    public function make(Request $request) : string
    {
        return md5($request->url());
    }
}
