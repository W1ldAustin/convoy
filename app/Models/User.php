<?php

namespace Convoy\Models;

use Eloquent;
use Illuminate\Support\Str;
use Convoy\Enums\Api\ApiKeyType;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Collection;
use Laravel\Sanctum\NewAccessToken;
use Illuminate\Auth\Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;

/**
 * @mixin Eloquent
 */
class User extends Model implements AuthenticatableContract, AuthorizableContract
{
    use HasApiTokens, HasFactory, Notifiable, Authenticatable, Authorizable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'root_admin',
    ];

    /**
     * Rules verifying that the data being stored matches the expectations of the database.
     */
    public static $validationRules = [
        'email' => 'required|email|between:1,191|unique:users,email',
        'name' => 'required|string|between:1,191',
        'password' => ['sometimes', 'min:8', 'max:191', 'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[!@#\$%\^&\*])(?=.{8,})/u', 'string'],
        'root_admin' => 'boolean',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'email_verified_at',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'two_factor_confirmed_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'root_admin' => 'boolean',
    ];

    public function toReactObject(): array
    {
        return Collection::make($this->toArray())->except(['id'])->toArray();
    }

    public function createToken(string $name, ApiKeyType $type, array $abilities = ['*'])
    {
        $token = $this->tokens()->create([
            'type' => $type,
            'name' => $name,
            'token' => hash('sha256', $plainTextToken = Str::random(40)),
            'abilities' => $abilities,
        ]);

        return new NewAccessToken($token, $token->getKey().'|'.$plainTextToken);
    }

    public function servers(): HasMany
    {
        return $this->hasMany(Server::class);
    }

    public function getRouteKeyName(): string
    {
        return 'id';
    }


    protected static function boot()
    {
        parent::boot();

        static::creating(function (User $user) {
            $user->uuid = Str::uuid()->toString();
        });
    }
}
