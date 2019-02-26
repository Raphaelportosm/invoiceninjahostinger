<?php

namespace App\Http\ViewComposers;

use App\Models\Country;
use App\Models\Currency;
use App\Models\PaymentTerm;
use App\Utils\TranslationHelper;
use Cache;
use Illuminate\Support\Str;
use Illuminate\View\View;


class TranslationComposer
{
    /**
     * Bind data to the view.
     *
     * @param View $view
     *
     * @return void
     */
    public function compose(View $view) :void
    {
        $view->with('industries', TranslationHelper::getIndustries());

        $view->with('countries', TranslationHelper::getCountries());

        $view->with('payment_types', TranslationHelper::getPaymentTypes());

        $view->with('languages', TranslationHelper::getLanguages());

        $view->with('currencies', TranslationHelper::getCurrencies());

        $view->with('payment_terms', TranslationHelper::getPaymentTerms());

    }

}
