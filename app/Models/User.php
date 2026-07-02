<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;
use App\Models\Finance\Account;
use App\Models\Finance\Category;
use App\Models\Finance\DailyCut;
use App\Models\Finance\Movement;
use App\Models\Finance\Person;
use App\Models\Finance\PlannerSetting;
use Illuminate\Database\Eloquent\Relations\HasOne;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'dashboard_layout',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'dashboard_layout' => 'array',
        ];
    }

    public function isFinanceOwner(): bool
    {
        $ownerEmail = trim((string) config('finance.owner_email'));

        return $ownerEmail !== '' && strcasecmp($this->email, $ownerEmail) === 0;
    }

    public function financeAccounts(): HasMany
    {
        return $this->hasMany(Account::class);
    }

    public function financeCategories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    public function financePeople(): HasMany
    {
        return $this->hasMany(Person::class);
    }

    public function financeMovements(): HasMany
    {
        return $this->hasMany(Movement::class);
    }

    public function financeDailyCuts(): HasMany
    {
        return $this->hasMany(DailyCut::class);
    }

    public function financePlannerSetting(): HasOne
    {
        return $this->hasOne(PlannerSetting::class);
    }
}
