<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class PasswordReset extends Model
{
    protected $fillable = ['email', 'token'];

    public function getIsExpiradoAttribute(){
        return $this->updated_at < Carbon::now()->subHour() ;
    }
}
