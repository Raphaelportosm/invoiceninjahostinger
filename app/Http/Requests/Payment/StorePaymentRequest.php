<?php
/**
 * Invoice Ninja (https://invoiceninja.com)
 *
 * @link https://github.com/invoiceninja/invoiceninja source repository
 *
 * @copyright Copyright (c) 2020. Invoice Ninja LLC (https://invoiceninja.com)
 *
 * @license https://opensource.org/licenses/AAL
 */

namespace App\Http\Requests\Payment;

use App\Http\Requests\Request;
use App\Http\ValidationRules\PaymentAmountsBalanceRule;
use App\Http\ValidationRules\Payment\ValidInvoicesRules;
use App\Http\ValidationRules\ValidCreditsPresentRule;
use App\Http\ValidationRules\ValidPayableInvoicesRule;
use App\Models\Payment;
use App\Utils\Traits\MakesHash;

class StorePaymentRequest extends Request
{
    use MakesHash;

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */

    public function authorize() : bool
    {
        return auth()->user()->can('create', Payment::class);
    }

    protected function prepareForValidation()
    {
        $input = $this->all();

        $invoices_total = 0;
        $credits_total = 0;

        if (isset($input['client_id'])) {
            $input['client_id'] = $this->decodePrimaryKey($input['client_id']);
        }

        if (isset($input['invoices']) && is_array($input['invoices']) !== false) {
            foreach ($input['invoices'] as $key => $value) {
                $input['invoices'][$key]['invoice_id'] = $this->decodePrimaryKey($value['invoice_id']);
                $invoices_total += $value['amount'];
            }

            //if(!isset($input['amount']) || )
        }

        if (isset($input['invoices']) && is_array($input['invoices']) === false) {
            $input['invoices'] = null;
        }

        if (isset($input['credits']) && is_array($input['credits']) !== false) {
            foreach ($input['credits'] as $key => $value) {
                if (array_key_exists('credit_id', $input['credits'][$key])) {
                    $input['credits'][$key]['credit_id'] = $this->decodePrimaryKey($value['credit_id']);
                    $credits_total += $value['amount'];
                }
            }
        }

        if (isset($input['credits']) && is_array($input['credits']) === false) {
            $input['credits'] = null;
        }

        if (!isset($input['amount']) || $input['amount'] == 0) {
            $input['amount'] = $invoices_total - $credits_total;
        }

        $input['is_manual'] = true;
        
        $this->replace($input);
    }

    public function rules()
    {
        $rules = [
            'amount' => 'numeric|required',
            'amount' => [new PaymentAmountsBalanceRule(),new ValidCreditsPresentRule()],
            'date' => 'required',
            'client_id' => 'bail|required|exists:clients,id',
            'invoices.*.invoice_id' => 'required|distinct|exists:invoices,id',
            'invoices.*.invoice_id' => new ValidInvoicesRules($this->all()),
            'invoices.*.amount' => 'required',
            'credits.*.credit_id' => 'required|exists:credits,id',
            'credits.*.amount' => 'required',
            'invoices' => new ValidPayableInvoicesRule(),
            'number' => 'nullable',
        ];

        return $rules;
    }
}
