<?php
/**
 * Invoice Ninja (https://invoiceninja.com)
 *
 * @link https://github.com/invoiceninja/invoiceninja source repository
 *
 * @copyright Copyright (c) 2019. Invoice Ninja LLC (https://invoiceninja.com)
 *
 * @license https://opensource.org/licenses/AAL
 */

namespace App\Models;

use App\Events\Invoice\InvoiceWasUpdated;
use App\Models\Currency;
use App\Models\Filterable;
use App\Models\PaymentTerm;
use App\Utils\Number;
use App\Utils\Traits\MakesDates;
use App\Utils\Traits\MakesInvoiceValues;
use App\Utils\Traits\NumberFormatter;
use App\Helpers\Invoice\InvoiceSum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Laracasts\Presenter\PresentableTrait;

class Invoice extends BaseModel
{
    use SoftDeletes;
    use Filterable;
    use NumberFormatter;
    use MakesDates;
    use PresentableTrait;
    use MakesInvoiceValues;
    
    protected $presenter = 'App\Models\Presenters\InvoicePresenter';

    protected $hidden = [
        'id',
        'private_notes',
        'user_id',
        'client_id',
        'company_id',
        'backup',
        'settings',
    ];

    protected $fillable = [
        'invoice_number',
        'discount',
        'po_number',
        'invoice_date',
        'due_date',
        'terms',
        'public_notes',
        'private_notes',
        'invoice_type_id',
        'tax_name1',
        'tax_rate1',
        'tax_name2',
        'tax_rate2',
        'tax_name3',
        'tax_rate3',
        'is_amount_discount',
        'footer',
        'partial',
        'partial_due_date',
        'custom_value1',
        'custom_value2',
        'custom_value3',
        'custom_value4',
        'line_items',
        'settings',
        'client_id',
        'footer',
    ];

    protected $casts = [
        'settings' => 'object',
        'line_items' => 'object',
        'updated_at' => 'timestamp',
        'created_at' => 'timestamp',
        'deleted_at' => 'timestamp',
    ];

    protected $with = [
        'company',
        'client',
    ];

    protected $appends = [
        'hashed_id',
        'status'
    ];

    const STATUS_DRAFT = 1;
    const STATUS_SENT = 2;
    const STATUS_PARTIAL = 3;
    const STATUS_PAID = 4;
    const STATUS_CANCELLED = 5;

    const STATUS_OVERDUE = -1;
    const STATUS_UNPAID = -2;
    const STATUS_REVERSED = -3;

    
    public function getStatusAttribute()
    {

        if($this->status_id == Invoice::STATUS_SENT && $this->due_date > Carbon::now())
            return Invoice::STATUS_UNPAID;
        else if($this->status_id == Invoice::STATUS_PARTIAL && $this->partial_due_date > Carbon::now())
            return Invoice::STATUS_UNPAID;
        else if($this->status_id == Invoice::STATUS_SENT && $this->due_date < Carbon::now())
            return Invoice::STATUS_OVERDUE;
        else if($this->status_id == Invoice::STATUS_PARTIAL && $this->partial_due_date < Carbon::now())
            return Invoice::STATUS_OVERDUE;
        else
            return $this->status_id;

    }
    
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function invitations()
    {
        return $this->hasMany(InvoiceInvitation::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function documents()
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    public function payments()
    {
        return $this->morphToMany(Payment::class, 'paymentable');
    }

    public function company_ledger()
    {
        return $this->morphMany(CompanyLedger::class, 'company_ledgerable');
    }

    /* ---------------- */
    /* Settings getters */
    /* ---------------- */

    /**
     * If True, prevents an invoice from being 
     * modified once it has been marked as sent
     * 
     * @return boolean isLocked
     */
    public function isLocked() : bool
    {
        return $this->client->getSetting('lock_sent_invoices');
    }

    /**
     * Determines if invoice overdue.
     *
     * @param      float    $balance   The balance
     * @param      date.    $due_date  The due date
     *
     * @return     boolean  True if overdue, False otherwise.
     */
    public static function isOverdue($balance, $due_date)
    {
        if (! $this->formatValue($balance,2) > 0 || ! $due_date) {
            return false;
        }

        // it isn't considered overdue until the end of the day
        return strtotime($this->createClientDate(date(), $this->client->timezone()->name)) > (strtotime($due_date) + (60 * 60 * 24));
    }

    public function markViewed() :void
    {
        $this->last_viewed = Carbon::now()->format('Y-m-d H:i');
        $this->save();
    }
    
    public function isPayable() : bool
    {

        if($this->status_id == Invoice::STATUS_SENT && $this->due_date > Carbon::now())
            return true;
        else if($this->status_id == Invoice::STATUS_PARTIAL && $this->partial_due_date > Carbon::now())
            return true;
        else if($this->status_id == Invoice::STATUS_SENT && $this->due_date < Carbon::now())
            return true;
        else if($this->status_id == Invoice::STATUS_PARTIAL && $this->partial_due_date < Carbon::now())
            return true;
        else
            return false;
    }

    public static function badgeForStatus(int $status)
    {
        switch ($status) {
            case Invoice::STATUS_DRAFT:
                return '<h5><span class="badge badge-light">'.ctrans('texts.draft').'</span></h5>';
                break;
            case Invoice::STATUS_SENT:
                return '<h5><span class="badge badge-primary">'.ctrans('texts.sent').'</span></h5>';
                break;
            case Invoice::STATUS_PARTIAL:
                return '<h5><span class="badge badge-primary">'.ctrans('texts.partial').'</span></h5>';
                break;
            case Invoice::STATUS_PAID:
                return '<h5><span class="badge badge-success">'.ctrans('texts.paid').'</span></h5>';
                break;
            case Invoice::STATUS_CANCELLED:
                return '<h5><span class="badge badge-secondary">'.ctrans('texts.cancelled').'</span></h5>';
                break;
            case Invoice::STATUS_OVERDUE:
                return '<h5><span class="badge badge-danger">'.ctrans('texts.overdue').'</span></h5>';
                break;
            case Invoice::STATUS_UNPAID:
                return '<h5><span class="badge badge-warning">'.ctrans('texts.unpaid').'</span></h5>';
                break;      
            case Invoice::STATUS_REVERSED:
                return '<h5><span class="badge badge-info">'.ctrans('texts.reversed').'</span></h5>';
                break;           
            default:
                # code...
                break;
        }
    }

    /**
     * Returns the template for the invoice
     * 
     * @return string Either the template view, OR the template HTML string
     * @todo  this needs attention, invoice->settings needs clarification
     */
    public function design() :string
    {
        if(property_exists($this->settings,'design'))
            return File::exists(resource_path($this->settings->design)) ? File::get(resource_path($this->settings->design)) : File::get(resource_path('views/pdf/design1.blade.php'));
        else
            return File::get(resource_path('views/pdf/design1.blade.php'));
    }

    /**
     * Access the invoice calculator object
     * 
     * @return object The invoice calculator object getters
     */
    public function calc()
    {

        $invoice_calc = new InvoiceSum($this, $this->settings);
        
        return $invoice_calc->build();

    }

    /** TODO// DOCUMENT THIS FUNCTIONALITY */
    public function pdf_url()
    {
        $public_path = 'storage/' . $this->client->client_hash . '/invoices/'. $this->invoice_number . '.pdf';

        $storage_path = 'public/' . $this->client->client_hash . '/invoices/'. $this->invoice_number . '.pdf';

        if(!Storage::exists($storage_path)) {
            event(new InvoiceWasUpdated($this));
        }

        return $public_path;
    }

    /**
     * @param bool $save
     */
    public function updatePaidStatus($paid = false, $save = true) : bool
    {
        $status_id = false;
        if ($paid && $this->balance == 0) {
            $status_id = self::STATUS_PAID;
        } elseif ($paid && $this->balance > 0 && $this->balance < $this->amount) {
            $status_id = self::STATUS_PARTIAL;
        } elseif ($this->hasPartial() && $this->balance > 0) {
            $status_id = ($this->balance == $this->amount ? self::STATUS_SENT : self::STATUS_PARTIAL);
        }

        if ($status_id && $status_id != $this->status_id) {
            $this->status_id = $status_id;
            if ($save) {
                $this->save();
            }
        }
    }

    /**
     * @return bool
     */
    public function hasPartial() : bool
    {
        return ($this->partial && $this->partial > 0) === true;
    }

    /**
     * @return bool
     */
    public function isPartial() : bool
    {
        return $this->status_id >= self::STATUS_PARTIAL;
    }

    /**
     * Clear partial fields
     * @return void 
     */
    public function clearPartial() : void
    {
        $this->partial = null;
        $this->partial_due_date = null;
        $this->save();
    }

    /**
     * @param float $balance_adjustment
     */
    public function updateBalance($balance_adjustment)
    {

        if ($this->is_deleted) 
            return;
        
        $balance_adjustment = floatval($balance_adjustment);
        
        $this->balance = $this->balance + $balance_adjustment;

        if($this->balance == 0)
            $this->status_id = self::STATUS_PAID;

        $this->save();
    }

    public function setDueDate()
    {

        $this->due_date = Carbon::now()->addDays(PaymentTerm::find($this->company->settings->payment_terms_id)->num_days);
        $this->save();
    }

    public function setStatus($status)
    {
        $this->status_id = $status;
        $this->save();
    }
}