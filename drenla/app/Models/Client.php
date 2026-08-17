<?php

namespace App\Models;

use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Clients authenticate into the portal via a magic-link token (`portal_access_token`),
 * not a password — this model implements Authenticatable purely so the `client` guard
 * (config/auth.php) can drive normal session login/logout. See collaboration-notes.md
 * "Portal auth approach" decision (2026-07-02, CO).
 */
class Client extends Model implements AuthenticatableContract
{
    use Authenticatable, HasFactory;

    protected $fillable = [
        'name',
        'company_name',
        'email',
        'phone',
        'status',
        'portal_access_enabled',
        'portal_access_token',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'portal_access_enabled' => 'boolean',
        ];
    }

    public function proposals()
    {
        return $this->hasMany(Proposal::class);
    }

    public function projects()
    {
        return $this->hasMany(Project::class);
    }

    public function financeDocuments()
    {
        return $this->hasMany(FinanceDocument::class);
    }

    public function inquiries()
    {
        return $this->hasMany(Inquiry::class);
    }

    public function contacts()
    {
        return $this->hasMany(ClientContact::class);
    }

    public function leadNotes()
    {
        return $this->morphMany(LeadNote::class, 'notable')->latest('id');
    }

    /** Issue a fresh portal access token, invalidating any previously issued link. */
    public function regeneratePortalToken(): string
    {
        $this->portal_access_token = Str::random(40);
        $this->save();

        return $this->portal_access_token;
    }

    /** The magic-link URL staff copy/send to the client. Null until a token exists. */
    public function getPortalAccessUrlAttribute(): ?string
    {
        if (! $this->portal_access_token) {
            return null;
        }

        return route('portal.access', $this->portal_access_token);
    }
}
