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

namespace App\Helpers\Invoice;

/**
 * Class for discount calculations
 */
trait Discounter
{

	public function discount($amount)
	{
		if($this->invoice->discount == 0)
			return 0;

		if($this->invoice->is_amount_discount === true)
			return $this->pro_rata_discount($amount);

		
		return round($amount * ($this->invoice->discount / 100), 2);
		
	}

	public function pro_rata_discount($amount)
	{
		return round(($this->invoice->discount/$this->getSubTotal() * $amount),2);		
	}

}
