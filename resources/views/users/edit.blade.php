@extends('header')

@section('content')
    @parent
    @include('accounts.nav', ['selected' => ACCOUNT_USER_MANAGEMENT])

    {!! Former::open($url)->autocomplete('off')->method($method)->addClass('warn-on-exit user-form')->rules(array(
        'first_name' => 'required',
        'last_name' => 'required',
        'email' => 'required|email',
    )); !!}

    @if ($user)
        {!! Former::populate($user) !!}
        {{ Former::populateField('is_admin', intval($user->is_admin)) }}
    @endif

    <div style="display:none">
        {!! Former::text('action') !!}
    </div>

    <div class="panel panel-default">
        <div class="panel-heading">
            <h3 class="panel-title">{!! trans('texts.user_details') !!}</h3>
        </div>
        <div class="panel-body form-padding-right">

            {!! Former::text('first_name') !!}
            {!! Former::text('last_name') !!}
            {!! Former::text('email') !!}

        </div>
    </div>

    <div class="panel panel-default">
        <div class="panel-heading">
            <h3 class="panel-title">{!! trans('texts.permissions') !!}</h3>
        </div>
        <div class="panel-body form-padding-right">

            @if ( ! Utils::hasFeature(FEATURE_USER_PERMISSIONS))
                <div class="alert alert-warning">{{ trans('texts.upgrade_for_permissions') }}</div>
                <script type="text/javascript">
                    $(function() {
                        $('input[type=checkbox]').prop('disabled', true);
                    })
                </script>
            @endif

            {!! Former::checkbox('is_admin')
                ->label('&nbsp;')
                ->value(1)
                ->text(trans('texts.administrator'))
                ->help(trans('texts.administrator_help')) !!}

            <div class="panel-body">
                <table class="table table-striped data-table" >
                    <thead>
                    <th></th>
                    <th>{!! Former::checkbox('create')
                                  ->text( trans('texts.create') )
                                  ->value('create_')
                                  ->label('&nbsp;')
                                  ->id('create_all') !!}</th>
                    <th>{!! Former::checkbox('view')
                                  ->text( trans('texts.view') )
                                  ->value('view_')
                                  ->label('&nbsp;')
                                  ->id('view_all') !!}</th>
                    <th>{!! Former::checkbox('edit')
                                  ->text( trans('texts.edit') )
                                  ->value('edit_')
                                  ->label('&nbsp;')
                                  ->id('edit_all') !!}</th>
                    </thead>
                    <tbody>
                    @foreach (json_decode(PERMISSION_ENTITIES,1) as $permissionEntity)

                        <?php
                        if($user)
                            $permissions = json_decode($user->permissions,1);
                        else
                            $permissions = [];
                        ?>

                        <tr>
                            <td>{{ ucfirst($permissionEntity) }}</td>
                            <td>{!! Former::checkbox('permissions[create_' . $permissionEntity . ']')
                                  ->label('&nbsp;')
                                  ->value('create_' . $permissionEntity . '')
                                  ->id('create_' . $permissionEntity . '')
                                  ->check(is_array($permissions) && in_array('create_' . $permissionEntity, $permissions, FALSE) ? true : false) !!}</td>
                            <td>{!! Former::checkbox('permissions[view_' . $permissionEntity . ']')
                                  ->label('&nbsp;')
                                  ->value('view_' . $permissionEntity . '')
                                  ->id('view_' . $permissionEntity . '')
                                  ->check(is_array($permissions) && in_array('view_' . $permissionEntity, $permissions, FALSE) ? true : false) !!}</td>
                            <td>{!! Former::checkbox('permissions[edit_' . $permissionEntity . ']')
                                  ->label('&nbsp;')
                                  ->value('edit_' . $permissionEntity . '')
                                  ->id('edit_' . $permissionEntity . '')
                                  ->check(is_array($permissions) && in_array('edit_' . $permissionEntity, $permissions, FALSE) ? true : false) !!}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>

        </div>
    </div>

    <center class="buttons">
        {!! Button::normal(trans('texts.cancel'))->asLinkTo(URL::to('/settings/user_management'))->appendIcon(Icon::create('remove-circle'))->large() !!}
        {!! ($user) ? Button::success(trans('texts.save'))->withAttributes(['onclick' => 'submitAction("save")'])->large()->appendIcon(Icon::create('floppy-disk')) : false !!}
        {!! (! $user || ! $user->confirmed) ? Button::info(trans($user ? 'texts.resend_invite' : 'texts.send_invite'))->withAttributes(['onclick' => 'submitAction("email")'])->large()->appendIcon(Icon::create('send')) : false !!}
    </center>

    {!! Former::close() !!}

    <script type="text/javascript">

        function submitAction(value) {
            $('#action').val(value);
            $('.user-form').submit();
        }

    </script>

@stop

@section('onReady')

    $('#first_name').focus();

    /*
    *
    * Iterate over all permission checkboxes and ensure VIEW/EDIT
    * combinations are enabled/disabled depending on VIEW state.
    *
    */

    $("input[type='checkbox'][id^='view_']").each(function() {

        var entity = $(this).attr('id')
                            .replace('create_',"")
                            .replace('view_',"")
                            .replace('edit_',"")
                            .replace(']',"")
        .replace('[',""); //get entity name

        $('#edit_' + entity).prop('disabled', !$('#view_' + entity).is(':checked')); //set state of edit checkbox

    });

    /*
    *
    * Checks state of View/Edit checkbox, will enable/disable check/uncheck
    * dependent on state of VIEW permission.
    *
    */

    $("input[type='checkbox'][id^='view_']").change(function(){

        var entity = $(this).attr('id')
                            .replace('create_',"")
                            .replace('view_',"")
                            .replace('edit_',"")
                            .replace(']',"")
                            .replace('[',""); //get entity name

    $('#edit_' + entity).prop('disabled', !$('#view_' + entity).is(':checked')); //set state of edit checkbox

        if(!$('#view_' + entity).is(':checked')) {
            $('#edit_' + entity).prop('checked', false); //remove checkbox value from edit dependant on View state.
        }

    });

    $('#create_all, #view_all, #edit_all').change(function(){

        var checked = $(this).is(':checked');
        var permission_type = $(this).val();

        $("input[type='checkbox'][id^=" + permission_type + "]").each(function() {

            var entity = $(this).attr('id')
                                .replace('create_',"")
                                .replace('view_',"")
                                .replace('edit_',"")
                                .replace(']',"")
                                .replace('[',""); //get entity name

            $('#' + permission_type + entity).prop('checked', checked); //set state of edit checkbox

            if(!$('#view_' + entity).is(':checked')) {
                $('#edit_' + entity).prop('checked', false); //remove checkbox value from edit dependant on View state.
            }

            $('#edit_' + entity).prop('disabled', !$('#view_' + entity).is(':checked')); //set state of edit checkbox


        });


    });


@stop
