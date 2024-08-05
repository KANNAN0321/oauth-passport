<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class OauthToken extends Model
{
    protected $guarded = [];

    public function hasExpired()
    {
        return now()->gte($this->updated_at->addSeconds($this->expires_in));
    }
}