<?php

namespace App\Models;

use Carbon\Carbon;
use App\Jobs\NotifyUsers;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Activitylog\LogOptions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Artisan;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Foundation\Auth\User as Authenticatable;

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
    protected $hidden = ['password', 'remember_token','updated_at', 'current_streak'];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'password'     => 'hashed',
        'status'       => 'boolean'
    ];

    public function scopeStatus(Builder $q)
    {
        $q->where('status', '=', true);
    }

    public function scopeInterested(Builder $q)
    {
        $q->whereNotIn('id', [1,3]); // selopia and nileema aren't interested
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['designation_id','name','phone_no','birthday','photo_url','status'])
            ->logExcept(['password', 'updated_at'])
            ->useLogName('User')
            ->logAll();
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

    public function accessLogs()
    {
        return $this->hasMany(UserAccessLog::class);
    }

    public function budget()
    {
        return $this->hasOne(UserBudget::class, 'user_id');
    }

    public function designation () {
        return $this->belongsTo(Designation::class);
    }

    public function badges()
    {
        return $this->belongsToMany(Badge::class, 'user_badges', 'user_id');
    }

    public function userBadge()
    {
        return $this->hasMany(UserBadge::class, 'user_id');
    }

    public function events()
    {
        return $this->belongsToMany(Event::class, 'event_participants', 'user_id');
    }

    public function eventInventories()
    {
        return $this->hasMany(EventInventory::class, 'assigned_to');
    }

    public function eventsViewed()
    {
        return $this->belongsToMany(Event::class, 'event_guests', 'user_id');
    }

    public function addedImages()
    {
        return $this->hasMany(EventImage::class, 'added_by');
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

    public function eventsParticipated()
    {
        return $this->hasMany(EventParticipant::class);
    }

    // all treasures belonging to a user
    public function collectedTreasures()
    {
        return $this->hasMany(Treasurer::class, 'user_id')
            ->where('completion_status', '=', true);
    }

    // all treasurer liabilities belonging to one user
    public function userPayables()
    {
        return $this->hasMany(TreasurerLiability::class, 'user_id');
    }

    public function createdExpenses()
    {
        return $this->hasMany(Expense::class, 'created_by');
    }

    public function updatedExpenses()
    {
        return $this->hasMany(Expense::class, 'last_updated_by');
    }

    public function points()
    {
        return $this->hasMany(UserPoint::class);
    }

    public function budgetIncomes()
    {
        return $this->hasMany(UserIncome::class, 'user_id');
    }

    public function budgetExpenses()
    {
        return $this->hasMany(UserExpense::class, 'user_id');
    }

    public function petCares()
    {
        return $this->hasMany(PetCare::class);
    }

    public function stories()
    {
        return $this->hasMany(UserStory::class);
    }

    public function seenStories()
    {
        return $this->hasMany(UserStoryView::class, 'seen_by');
    }

    public static function boot()
    {
        parent::boot();

        static::created(function ($model) {
            Cache::forget('users');
            Cache::forget('userstrue');

            UserBadge::create([
                'user_id'  => $model->id,
                'badge_id' => 1
            ]);

            dispatch(new NotifyUsers(
                null,
                true,
                'pages/accounts/member',
                auth()->user()->name . ' has added a new user.',
                auth()->user()
            ));
        });

        static::updated(function ($model) {
            Artisan::call('cache:clear');

            if ($model->id != auth()->user()->id && $model->status == 0)
            {
                dispatch(new NotifyUsers(
                    null,
                    true,
                    'pages/accounts/member',
                    auth()->user()->name . " deactivated " . $model->name . "'s account.",
                    auth()->user()
                ));
            }
        });

        static::deleted(function ($model) {
            Cache::forget('users');
            Cache::forget('userstrue');
            Cache::forget('user_profile'.$model->id);

            deleteFile($model->photo_url);

            dispatch(new NotifyUsers(
                null,
                true,
                'pages/accounts/member',
                auth()->user()->name . ' has removed '. $model->name .'.',
                auth()->user()
            ));
        });
    }
}
