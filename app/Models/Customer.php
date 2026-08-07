<?php

namespace App\Models;

use App\Enums\CustomerType;
use App\Enums\InvoiceType;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'type',
        'company_name',
        'ice',
        'if',
        'patente',
        'ville',
        'first_name',
        'last_name',
        'phone',
        'email',
        'address',
        'notes',
        'notify_email',
        'notify_whatsapp',
    ];

    /**
     * Computed attributes appended to array/JSON output so the frontend and
     * invoice PDF get a ready-to-display customer name for both types.
     *
     * @var list<string>
     */
    protected $appends = ['display_name'];

    protected function casts(): array
    {
        return [
            'type' => CustomerType::class,
            'notify_email' => 'boolean',
            'notify_whatsapp' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        // The raison sociale + fiscal identifiers only apply to companies. Clear
        // them on save for anyone who isn't a company so a client re-classified
        // from entreprise to particulier never keeps stale fiscal data (which
        // would otherwise resurface if they were switched back).
        static::saving(function (self $customer): void {
            if ($customer->type !== CustomerType::Company) {
                $customer->company_name = null;
                $customer->ice = null;
                $customer->if = null;
                $customer->patente = null;
                $customer->ville = null;
            }
        });
    }

    /**
     * The name to show for this customer: the raison sociale for companies,
     * otherwise the person's full name.
     */
    protected function displayName(): Attribute
    {
        return Attribute::get(function (): string {
            if ($this->type === CustomerType::Company && $this->company_name) {
                return $this->company_name;
            }

            return trim("{$this->first_name} {$this->last_name}");
        });
    }

    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * Factures only. Devis live in the same table but are a separate kind of
     * document — {@see hasFactures()} for why the two are not interchangeable.
     */
    public function factures(): HasMany
    {
        return $this->invoices()->where('type', InvoiceType::Invoice->value);
    }

    /**
     * Whether a facture is attached to this client, soft-deleted ones included.
     *
     * InvoiceService assigns the fiscal number when the row is created, before
     * the status is set to draft, so even a facture still in brouillon has
     * already burned a number in the sequence. Deleting its client would orphan
     * a numbered fiscal document, which is why CustomerPolicy blocks it. Devis
     * are numbered on their own sequence and are not fiscal documents, so they
     * never stand in the way of deleting a client.
     *
     * Prefers a `factures_count` loaded via withCount so a listing can check a
     * whole page of clients without a query each.
     */
    public function hasFactures(): bool
    {
        if (! is_null($this->factures_count)) {
            return $this->factures_count > 0;
        }

        return $this->factures()->withTrashed()->exists();
    }

    public function setPhoneAttribute(string $value): void
    {
        $phone = preg_replace('/[\s\-().]+/', '', $value);

        if (str_starts_with($phone, '+212')) {
            $this->attributes['phone'] = $phone;
        } elseif (str_starts_with($phone, '00212')) {
            $this->attributes['phone'] = '+' . substr($phone, 2);
        } elseif (str_starts_with($phone, '0')) {
            $this->attributes['phone'] = '+212' . substr($phone, 1);
        } else {
            // Bare national digits (e.g. "612345678"). The Form Request for
            // customer create/update must validate the format before this runs.
            $this->attributes['phone'] = '+212' . $phone;
        }
    }
}
