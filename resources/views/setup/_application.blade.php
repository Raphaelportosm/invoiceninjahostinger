<div class="bg-white shadow overflow-hidden rounded-lg mt-8">
    <div class="px-4 py-5 border-b border-gray-200 sm:px-6">
        <h3 class="text-lg leading-6 font-medium text-gray-900">
            Application settings
        </h3>
        <p class="mt-1 max-w-2xl text-sm leading-5 text-gray-500">
            Let's store basic information about your Invoice Ninja!
        </p>
    </div>
    <div>
        <dl>
            <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 sm:flex sm:items-center">
                <dt class="text-sm leading-5 font-medium text-gray-500">
                    {{ ctrans('texts.url') }}*
                </dt>
                <dd class="text-sm leading-5 text-gray-900 sm:mt-0 sm:col-span-2">
                    <input type="text" class="input" name="url" required value="{{ old('url') }}">
                </dd>
            </div>
            <div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 sm:flex sm:items-center">
                <dt class="text-sm leading-5 font-medium text-gray-500">
                    {{ ctrans('texts.https') }}
                </dt>
                <dd class="mt-1 text-sm leading-5 text-gray-900 sm:mt-0 sm:col-span-2">
                    <input type="checkbox" class="form-checkbox mr-1" name="https" {{ old('https') ? 'checked': '' }}>
                    <span>{{ ctrans('texts.require') }}</span>
                </dd>
            </div>
            <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 sm:flex sm:items-center">
                <dt class="text-sm leading-5 font-medium text-gray-500">
                    {{ ctrans('texts.debug') }}
                </dt>
                <dd class="mt-1 text-sm leading-5 text-gray-900 sm:mt-0 sm:col-span-2">
                    <input type="checkbox" class="form-checkbox mr-1" name="debug" {{ old('debug') ? 'checked': '' }}>
                    <span>{{ ctrans('texts.enable') }}</span>
                </dd>
            </div>
            <div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 sm:flex sm:items-center">
                <dt class="text-sm leading-5 font-medium text-gray-500">
                    {{ ctrans('texts.reports') }}
                </dt>
                <dd class="mt-1 text-sm leading-5 text-gray-900 sm:mt-0 sm:col-span-2">
                    <input type="checkbox" class="form-checkbox mr-1" name="send_logs" {{ old('send_logs' ? 'checked': '') }}>
                    <span>{{ ctrans('texts.send_fail_logs_to_our_server') }}</span>
                </dd>
            </div>
        </dl>
    </div>
</div>