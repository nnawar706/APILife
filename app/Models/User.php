<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasApiTokens, Notifiable, LogsActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded = ['id'];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = ['password', 'remember_token','updated_at'];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'password'     => 'hashed',
        'status'       => 'boolean'
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['designation_id','name','phone_no','birthday','photo_url','status'])
            ->logExcept(['password', 'updated_at'])
            ->useLogName('User')
            ->logOnlyDirty();
    }

    public function getDescriptionForEvent(string $eventName): string
    {
        return "A user has been {$eventName}";
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

    protected function password(): Attribute
    {
        return Attribute::make(
            set: fn(string $value) => Hash::make($value)
        );
    }

    protected function createdAt(): Attribute
    {
        return Attribute::make(
            get: fn(string $value) => Carbon::parse($value)->format('F d, Y')
        );
    }

    public function designation () {
        return $this->belongsTo(Designation::class);
    }

    public function events()
    {
        return $this->belongsToMany(Event::class, 'event_participants', 'user_id');
    }

    public function leadEvents()
    {
        return $this->hasMany(Event::class, 'lead_user_id');
    }

    public function expenses()
    {
        return $this->hasMany(ExpenseBearer::class, 'user_id');
    }

    public function sponsors()
    {
        return $this->hasMany(ExpenseBearer::class, 'user_id')
            ->where('is_sponsored', '=', true);
    }

    public function payments()
    {
        return $this->hasMany(ExpensePayer::class, 'user_id');
    }

    public function expensePayers()
    {
        return $this->hasMany(ExpensePayer::class);
    }

    public function collectedTreasures()
    {
        return $this->hasMany(Treasurer::class, 'user_id')
            ->where('completion_status', '=', true);
    }

    public static function boot()
    {
        parent::boot();

        static::created(function ($model) {
            Cache::forget('users');
        });

        static::updated(function ($model) {
            clearCache();
        });

        static::deleted(function ($model) {
            Cache::forget('users');
            Cache::forget('user'.$model->id);
            Cache::forget('auth_user'.$model->id);

            deleteFile($model->photo_url);
        });
    }
}
