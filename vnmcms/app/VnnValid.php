<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class VnnValid extends Model
{
    //
    public function ApiReturn($data, $status, $message, $code)
    {
        $return = (object)[];
        $return->status = $status;
        if ($data) {
            $return->errors = $data;
        }
        if ($message) {
            $return->message = $message;
        }
        return response()->json($return, $code ? $code : 200);
    }
}
