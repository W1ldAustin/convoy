<?php

namespace Convoy\Models;

use Convoy\Casts\NullableEncrypter;
use Convoy\Casts\MebibytesToAndFromBytes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Node extends Model
{
    use HasFactory;

    /**
     * The constants for generating temporary Coterm (a noVNC reverse proxy) session tokens
     */
    public const COTERM_TOKEN_ID_LENGTH = 16;
    public const COTERM_TOKEN_LENGTH = 64;

    /**
     * The attributes excluded from the model's JSON form.
     */
    protected $hidden = [
        'token_id', 'secret', 'coterm_token_id', 'coterm_token',
    ];

    /**
     * Cast values to correct type.
     */
    protected $casts = [
        'memory' => MebibytesToAndFromBytes::class,
        'disk' => MebibytesToAndFromBytes::class,
        'secret' => 'encrypted',
        'coterm_enabled' => 'boolean',
        'coterm_tls_enabled' => 'boolean',
        'coterm_token' => NullableEncrypter::class,
    ];

    /**
     * Fields that aren't mass assignable
     */
    protected $guarded = ['id', 'created_at', 'updated_at'];

    public static $validationRules = [
        'location_id' => 'required|integer|exists:locations,id',
        'name' => 'required|string|max:191',
        'cluster' => 'required|string|max:191',
        'fqdn' => 'required|string|max:191',
        'token_id' => 'required|string|max:191',
        'secret' => 'required|string|max:191',
        'port' => 'required|integer|min:1|max:65535',
        'memory' => 'required|integer',
        'memory_overallocate' => 'required|integer',
        'disk' => 'required|integer',
        'disk_overallocate' => 'required|integer',
        'vm_storage' => ['required', 'string', 'max:191', 'regex:/^\S*$/u'],
        'backup_storage' => ['required', 'string', 'max:191', 'regex:/^\S*$/u'],
        'iso_storage' => ['required', 'string', 'max:191', 'regex:/^\S*$/u'],
        'network' => ['required', 'string', 'max:191', 'regex:/^\S*$/u'],
        'coterm_id' => 'sometimes|nullable|integer|exists:coterms,id',
    ];

    /**
     * Get the connection address to use when making calls to this node's assigned Coterm endpoint.
     */
    public function getCotermConnectionAddress(): string
    {
        return sprintf('%s://%s:%s', $this->coterm_tls_enabled ? 'https' : 'http', $this->coterm_fqdn, $this->coterm_port);
    }

    /**
     * Gets the servers associated with a node.
     */
    public function servers(): HasMany
    {
        return $this->hasMany(Server::class);
    }

    /**
     * Gets the address pools allocated to a node.
     */
    public function addressPools(): BelongsToMany
    {
        return $this->belongsToMany(
            AddressPool::class,
            'address_pool_to_node',
            'node_id',
            'address_pool_id'
        );
    }

    /**
     * Gets all the addresses associated with a node from the address pool(s) allocated to a node.
     */
    public function addresses(): HasManyThrough
    {
        return $this->hasManyThrough(
            Address::class,
            AddressPoolToNode::class,
            'node_id',
            'address_pool_id',
            'id',
            'address_pool_id'
        );
    }

    /**
     * Gets the template groups associated with a node. This is not the same as TEMPLATES.
     */
    public function templateGroups(): HasMany
    {
        return $this->hasMany(TemplateGroup::class);
    }

    /**
     * Gets the ISOs downloaded on a node.
     */
    public function isos(): HasMany
    {
        return $this->hasMany(ISO::class);
    }

    /**
     * Gets the location associated with a node.
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * Gets the instance of Coterm that's connected with this node.
     */
    public function coterm(): BelongsTo
    {
        return $this->belongsTo(Coterm::class);
    }

    /**
     * Gets the total disk used from adding up all the associated servers' disk sizes.
     */
    public function getDiskAllocatedAttribute(): int
    {
        return $this->servers->sum('disk');
    }

    /**
     * Gets the total memory used from adding up all the associated servers' allocated memory.
     */
    public function getMemoryAllocatedAttribute(): int
    {
        return $this->servers->sum('memory');
    }

    /**
     * The column Laravel should look at for route model binding.
     */
    public function getRouteKeyName(): string
    {
        return 'id';
    }
}
