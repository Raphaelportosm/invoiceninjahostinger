<div class="-my-2 py-2 overflow-x-auto sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8">
    <div class="flex items-center justify-between">
        <div class="flex items-center">
            <span class="mr-2 text-sm hidden md:block">{{ ctrans('texts.per_page') }}</span>
            <select wire:model="per_page" class="form-select py-1 text-sm">
                <option>5</option>
                <option selected>10</option>
                <option>15</option>
                <option>20</option>
            </select>
        </div>
    </div>
    <div class="align-middle inline-block min-w-full overflow-hidden rounded mt-4">
        <table class="min-w-full shadow rounded border border-gray-200">
            <thead>
                <tr>
                    <th class="px-6 py-3 border-b border-gray-200 bg-gray-50 text-left text-xs leading-4 font-medium text-gray-500 uppercase tracking-wider">
                        <span role="button" wire:click="sortBy('date')" class="cursor-pointer">
                            {{ ctrans('texts.payment_date') }}
                        </span>
                    </th>
                    <th class="px-6 py-3 border-b border-gray-200 bg-gray-50 text-left text-xs leading-4 font-medium text-gray-500 uppercase tracking-wider">
                        <span role="button" wire:click="sortBy('type_id')" class="cursor-pointer">
                            {{ ctrans('texts.payment_type_id') }}
                        </span>
                    </th>
                    <th class="px-6 py-3 border-b border-gray-200 bg-gray-50 text-left text-xs leading-4 font-medium text-gray-500 uppercase tracking-wider">
                        <span role="button" wire:click="sortBy('amount')" class="cursor-pointer">
                            {{ ctrans('texts.amount') }}
                        </span>
                    </th>
                    <th class="px-6 py-3 border-b border-gray-200 bg-gray-50 text-left text-xs leading-4 font-medium text-gray-500 uppercase tracking-wider">
                        <span role="button" wire:click="sortBy('transaction_reference')" class="cursor-pointer">
                            {{ ctrans('texts.transaction_reference') }}
                        </span>
                    </th>
                    <th class="px-6 py-3 border-b border-gray-200 bg-gray-50 text-left text-xs leading-4 font-medium text-gray-500 uppercase tracking-wider">
                        <span role="button" wire:click="sortBy('status_id')" class="cursor-pointer">
                            {{ ctrans('texts.status') }}
                        </span>
                    </th>
                    <th class="px-6 py-3 border-b border-gray-200 bg-gray-50"></th>
                </tr>
            </thead>
            <tbody>
                @foreach($payments as $payment)
                    <tr class="cursor-pointer bg-white group hover:bg-gray-100">
                        <td class="px-6 py-4 whitespace-no-wrap text-sm leading-5 text-gray-500">
                            {{ $payment->formatDate($payment->date, $payment->client->date_format()) }}
                        </td>
                        <td class="px-6 py-4 whitespace-no-wrap text-sm leading-5 text-gray-500">
                            {{ $payment->type->name }}
                        </td>
                        <td class="px-6 py-4 whitespace-no-wrap text-sm leading-5 text-gray-500">
                            {!! \App\Utils\Number::formatMoney($payment->amount, $payment->client) !!}
                        </td>
                        <td class="px-6 py-4 whitespace-no-wrap text-sm leading-5 text-gray-500">
                            {{ $payment->transaction_reference }}
                        </td>
                        <td class="px-6 py-4 whitespace-no-wrap text-sm leading-5 text-gray-500">
                            {!! \App\Models\Payment::badgeForStatus($payment->status_id) !!}
                        </td>
                        <td class="px-6 py-4 whitespace-no-wrap flex items-center justify-end text-sm leading-5 font-medium">
                            <a href="{{ route('client.payments.show', $payment->hashed_id) }}" class="text-blue-600 hover:text-indigo-900 focus:outline-none focus:underline">
                                @lang('texts.view')
                            </a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="flex justify-center md:justify-between mt-6 mb-6">
        <span class="text-gray-700 text-sm hidden md:block">Showing {{ $payments->firstItem() }} to {{ $payments->lastItem() }} out of {{ $payments->total() }}</span>
        {{ $payments->links() }}
    </div>
</div>