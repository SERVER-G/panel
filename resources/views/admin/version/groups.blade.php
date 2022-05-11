{{-- Pterodactyl - Panel --}}
{{-- Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com> --}}

{{-- This software is licensed under the terms of the MIT license. --}}
{{-- https://opensource.org/licenses/MIT --}}
@extends('layouts.admin')

@section('title')
    Version Groups
@endsection

@section('content-header')
    <h1>Version Groups
        <small>You can make groups to versions.</small>
    </h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li class="active">Version Groups</li>
    </ol>
@endsection

@section('content')
    <div class="row">
        <div class="col-xs-12 col-md-8">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">Groups</h3>
                </div>
                <div class="box-body table-responsive no-padding">
                    <table class="table table-hover">
                        <tbody>
                            <tr>
                                <th>#</th>
                                <th>Name</th>
                                <th>Eggs</th>
                                <th>Type</th>
                                <th>Folder</th>
                                <th>Actions</th>
                            </tr>
                            @foreach ($groups as $group)
                                <tr>
                                    <td>{{ $group->id }}</td>
                                    <td>{{ $group->name }}</td>
                                    <td data-toggle="tooltip" data-placement="top" data-container="body" title="
                                        @php ($i = 0)
                                    @foreach (unserialize($group->eggs) as $egg)
                                    @foreach($eggs as $item)
                                    @if ($item->id == $egg)
                                    {{ $item->name }}{{ count(unserialize($group->eggs)) - 1 > $i ? ',' : '' }}
                                    @endif
                                    @endforeach
                                    @php ($i++)
                                    @endforeach
                                        ">
                                        @php ($i = 0)
                                        @foreach (unserialize($group->eggs) as $egg)
                                            @if ($i < 3)
                                                @foreach($eggs as $item)
                                                    @if ($item->id == $egg)
                                                        <code>{{ $item->name }}</code>
                                                    @endif
                                                @endforeach
                                            @endif
                                            @php ($i++)
                                        @endforeach
                                    </td>
                                    <td>
                                        @if ($group->type == 1)
                                            <span class="label label-success">Download</span>
                                        @elseif ($group->type == 2)
                                            <span class="label label-primary">Directory Sync</span>
                                        @elseif ($group->type == 4)
                                            <span class="label label-info">Eggs</span>
                                        @else
                                            <span class="label label-warning">Directory Sync (Custom names)</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($group->type != 1 && $group->type != 4)
                                            <code>{{ $group->folder }}</code>
                                        @else
                                            <span class="label label-warning"><i class="fa fa-remove"></i></span>
                                        @endif
                                    </td>
                                    <td>
                                        <button class="btn btn-primary btn-xs" onclick="editGroup({{ $group->id }});">
                                            <i class="fa fa-pencil"></i>
                                        </button>
                                        <button class="btn btn-danger btn-xs" data-action="delete" data-id="{{ $group->id }}">
                                            <i class="fa fa-trash"></i>
                                        </button>
                                        @if ($group->type != 2 && $group->type != 4)
                                            <a class="btn btn-info btn-xs" href="{{ route('admin.version.versions', $group->id) }}">Manage</a>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-xs-12 col-md-4">
            <div class="box box-info">
                <div class="box-header with-border">
                    <h3 class="box-title">Help</h3>
                </div>
                <div class="box-body">
                    <ul style="list-style-type: square;">
                        <li><b>Download:</b> Download files (.jar, .zip) from external url.</li>
                        <li><b>Directory Sync:</b> Load versions from a directory</li>
                        <li><b>Directory Sync (Custom names):</b> load versions from a directory with custom names. Only
                            the added ones will be visible.
                        </li>
                        <li><b>Eggs:</b> Users can select all available egg</li>
                    </ul>
                </div>
            </div>
            <div class="box box-success" id="create-box" {!! old('group_id', 0) == 0 ? '' : 'style="display: none;"' !!}>
                <div class="box-header with-border">
                    <h3 class="box-title">Create Group</h3>
                </div>
                <form method="post" action="{{ route('admin.version.group') }}">
                    <div class="box-body">
                        <div class="form-group">
                            <label for="name">Name</label>
                            <input type="text" id="name" name="name" placeholder="Spigot" class="form-control" value="{{ old('name') }}">
                        </div>
                        <div class="form-group">
                            <label for="eggs">Eggs</label>
                            <select id="eggs" name="eggs[]" class="form-control eggs" multiple>
                                @foreach ($eggs as $egg)
                                    <option value="{{ $egg->id }}" {{ in_array($egg->id, old('eggs', [])) ? 'selected' : '' }}>{{ $egg->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <hr>
                        <div class="form-group">
                            <label for="type">Type</label>
                            <select class="form-control" name="type" id="type">
                                <option value="1" {{ old('type') == 1 ? 'selected' : '' }}>Download</option>
                                <option value="2" {{ old('type') == 2 ? 'selected' : '' }}>Directory Sync</option>
                                <option value="3" {{ old('type') == 3 ? 'selected' : '' }}>
                                    Directory Sync (Custom names)
                                </option>
                                <option value="4" {{ old('type') == 4 ? 'selected' : '' }}>Eggs</option>
                            </select>
                        </div>
                        <div class="form-group" id="folder_select" style="display: none;">
                            <label for="folder">Folder</label>
                            <input type="text" name="folder" id="folder" class="form-control" placeholder="/srv/minecraft-versions" value="{{ old('folder') }}">
                        </div>
                    </div>
                    <div class="box-footer">
                        {!! csrf_field() !!}
                        <button class="btn btn-success pull-right" type="submit">Create</button>
                    </div>
                </form>
            </div>

            @foreach ($groups as $group)
                <div class="box box-primary" id="edit-box-{{ $group->id }}" {!! old('group_id', 0) == $group->id ? '' : 'style="display: none;"' !!}>
                    <div class="box-header with-border">
                        <h3 class="box-title">Edit Group</h3>
                    </div>
                    <form method="post" action="{{ route('admin.version.group.edit', $group->id) }}">
                        <div class="box-body">
                            <div class="form-group">
                                <label for="name">Name</label>
                                <input type="text" name="name" placeholder="Spigot" class="form-control" value="{{ old('name', $group->name) }}">
                            </div>
                            <div class="form-group">
                                <label for="eggs">Eggs</label>
                                <select name="eggs[]" class="form-control eggs" multiple>
                                    @foreach ($eggs as $egg)
                                        <option value="{{ $egg->id }}" {{ in_array($egg->id, old('eggs', unserialize($group->eggs))) ? 'selected' : '' }}>{{ $egg->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            @if (in_array($group->type, [2, 3]))
                                <div class="form-group">
                                    <label for="folder">Folder</label>
                                    <input type="text" name="folder" class="form-control" placeholder="/srv/minecraft-versions" value="{{ old('folder', $group->folder) }}">
                                </div>
                            @endif
                        </div>
                        <div class="box-footer">
                            {!! csrf_field() !!}
                            <input type="hidden" name="group_id" value="{{ $group->id }}">
                            <button class="btn btn-default" type="button" onclick="createGroup({{ $group->id }});">Cancel</button>
                            <button class="btn btn-success pull-right" type="submit">Edit</button>
                        </div>
                    </form>
                </div>
            @endforeach
        </div>
    </div>
@endsection

@section('footer-scripts')
    @parent
    <script>
        $('.eggs').select2({
            placeholder: 'Please select eggs'
        });

        $('[data-toggle="tooltip"]').tooltip();

        function changeType() {
            let folderSelector = $('#folder_select');

            parseInt($('#type').val()) !== 1 && parseInt($('#type').val()) !== 4 ? folderSelector.show(500) : folderSelector.hide(500);
        }

        function editGroup(id) {
            $('#create-box').slideUp(1000);
            $('#edit-box-' + id).slideDown(1000);
        }

        function createGroup(id) {
            $('#edit-box-' + id).slideUp(1000);
            $('#create-box').slideDown(1000);
        }

        $('#type').on('change', () => {
            changeType();
        });

        $(document).ready(() => {
            changeType();
        });

        $('[data-action="delete"]').click(function (event) {
            event.preventDefault();
            let self = $(this);
            swal({
                title: '',
                type: 'warning',
                text: 'Are you sure you want to delete this group?',
                showCancelButton: true,
                confirmButtonText: 'Delete',
                confirmButtonColor: '#d9534f',
                closeOnConfirm: false,
                showLoaderOnConfirm: true,
                cancelButtonText: 'Cancel',
            }, function () {
                $.ajax({
                    method: 'DELETE',
                    url: '{{ route('admin.version.group') }}',
                    headers: {'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')},
                    data: {
                        id: self.data('id')
                    }
                }).done((data) => {
                    self.parent().parent().slideUp();

                    swal({
                        type: 'success',
                        title: 'Success!',
                        text: 'You have successfully deleted this group.'
                    });
                }).fail((jqXHR) => {
                    swal({
                        type: 'error',
                        title: 'Ooops!',
                        text: (typeof jqXHR.responseJSON.error !== 'undefined') ? jqXHR.responseJSON.error : 'A system error has occurred! Please try again later...'
                    });
                });
            });
        });
    </script>
@endsection
