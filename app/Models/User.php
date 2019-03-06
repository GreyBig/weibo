<?php

namespace App\Models;

use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable  // Authenticatable授权相关功能的引用
{
    use Notifiable;  // 消息通知相关功能引用

    /**
     * 指明要进行数据库交互的数据库表名称
     * protected $table = 'users';
     */

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($user) {
            $user->activation_token = str_random(30);
        });
    }
    
    public function gravatar($size = '100')
    {
        $hash = md5(strtolower(trim($this->attributes['email']))); // 通过 $this->attributes['email'] 获取到用户的邮箱
        return "http://www.gravatar.com/avatar/$hash?s=$size";
        // 视图中通过$user->gravatar();方式进行调用, 传入参数可指定图片大小
    }
}
