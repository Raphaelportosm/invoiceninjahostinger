<?php

namespace App\Http\Livewire;

use App\Models\Invoice;
use App\Utils\Traits\WithSorting;
use Carbon\Carbon;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * @todo: Integrate InvoiceFilters
 */
class InvoicesTable extends Component
{
    use WithPagination, WithSorting;

    public $per_page = 10;

    public $status = [];

    public function statusChange($status)
    {
        if (in_array($status, $this->status)) {
            return $this->status = array_diff($this->status, [$status]);
        }

        array_push($this->status, $status);
    }

    public function render()
    {
        $query = Invoice::query()
            ->where('company_id', auth('contact')->user()->company->id)
            ->orderBy($this->sort_field, $this->sort_asc ? 'asc' : 'desc');

        if (in_array('paid', $this->status)) {
            $query = $query->orWhere('status_id', Invoice::STATUS_PAID);
        }

        if (in_array('unpaid', $this->status)) {
            $query = $query->orWhereIn('status_id', [Invoice::STATUS_SENT, Invoice::STATUS_PARTIAL]);
        }

        if (in_array('overdue', $this->status)) {
            $query = $query->orWhereIn('status_id', [Invoice::STATUS_SENT, Invoice::STATUS_PARTIAL])
                ->where('due_date', '<', Carbon::now())
                ->orWhere('partial_due_date', '<', Carbon::now());
        }

        $query = $query->paginate($this->per_page);

        return render('components.livewire.invoices-table', [
            'invoices' => $query,
        ]);
    }
}
