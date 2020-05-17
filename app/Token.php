<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Token extends Model
{
    const UPDATED_AT = 'updatedAt';
    const CREATED_AT = 'createdAt';
    protected $guarded = ['id'];
    //
    public static $PROVIDERS = ['google', 'youtube', 'twitter', 'linkedin', 'instagram', 'pinterest', 'facebook', 'sondcloud', 'blogger'];
}
