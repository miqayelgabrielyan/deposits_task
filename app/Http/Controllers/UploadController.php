<?php

namespace App\Http\Controllers;

use Cassandra\Date;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class UploadController extends Controller
{
    public static function upload(Request $request)
    {
        Storage::disk('local')->put($request->file('file')->getClientOriginalName(), file_get_contents($request->file('file')));
    }
}
