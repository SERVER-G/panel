{{-- Pterodactyl - Panel --}}
{{-- Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com> --}}

{{-- This software is licensed under the terms of the MIT license. --}}
{{-- https://opensource.org/licenses/MIT --}}
@extends('layouts.admin')

@section('title')
    Versions
@endsection

@section('content-header')
    <h1>Versions
        <small>You can make versions.</small>
    </h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li><a href="{{ route('admin.version') }}">Version Groups</a></li>
        <li class="active">Versions</li>
    </ol>
@endsection

@section('content')
    <div class="row">
        <div class="col-xs-12 col-md-8">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">Versions</h3>
                </div>
                <div class="box-body table-responsive no-padding">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Name</th>
                                <th>{{ $group->type == 1 ? 'Download URL' : 'Key' }}</th>
                                <th>Filename</th>
                                @if ($group->type == 1)
                                    <th>Action</th>
                                @endif
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="list">
                            @foreach ($versions as $version)
                                <tr>
                                    <td>{{ $version->id }}</td>
                                    <td>{{ $version->name }}</td>
                                    <td>{{ $version->download_url }}</td>
                                    <td><code>{{ $version->filename }}</code></td>
                                    @if ($group->type == 1)
                                        <td>
                                            @if ($version->action == 1)
                                                <span class="label label-default">None</span>
                                            @else
                                                <span class="label label-warning">Unzip</span>
                                            @endif
                                        </td>
                                    @endif
                                    <td>
                                        <button class="btn btn-primary btn-xs" onclick="editVersion({{ $version->id }});">
                                            <i class="fa fa-pencil"></i>
                                        </button>
                                        <button class="btn btn-danger btn-xs" data-action="delete" data-id="{{ $version->id }}">
                                            <i class="fa fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-xs-12 col-md-4">
            <div class="box box-success" id="create-box" {!! old('version_id', 0) == 0 ? '' : 'style="display: none;"' !!}>
                <div class="box-header with-border">
                    <h3 class="box-title">Create Version</h3>
                </div>
                <form method="post" action="{{ route('admin.version.version', $group->id) }}">
                    <div class="box-body">
                        <div class="form-group">
                            <label for="name">Name</label>
                            <input type="text" id="name" name="name" placeholder="Spigot" class="form-control" value="{{ old('name') }}">
                        </div>

                        @if ($group->type == 1)
                            <div class="form-group">
                                <label for="download_url">Download URL</label>
                                <input type="text" id="download_url" name="download_url" placeholder="https://mydomain.tld/spigot-1.8.jar" class="form-control" value="{{ old('download_url') }}">
                            </div>
                            <div class="form-group">
                                <label for="action">Action</label>
                                <select id="action" name="action" class="form-control">
                                    <option value="1">None</option>
                                    <option value="2">Unzip</option>
                                </select>
                            </div>
                        @else
                            <div class="form-group">
                                <label for="key">Key (.jar name)</label>
                                <input type="text" id="key" name="key" placeholder="spigot-1.8.jar" class="form-control" value="{{ old('key') }}">
                                <p class="text-muted small">Jar with this name will be showed with custom name.</p>
                            </div>
                        @endif

                        <div class="form-group" id="filename_select">
                            <label for="filename">Filename</label>
                            <input type="text" id="filename" name="filename" placeholder="server.jar" class="form-control" value="{{ old('filename') }}">
                        </div>
                    </div>
                    <div class="box-footer">
                        {!! csrf_field() !!}
                        <button class="btn btn-success pull-right" type="submit">Create</button>
                    </div>
                </form>
            </div>

            @foreach ($versions as $version)
                <div class="box box-primary" id="edit-box-{{ $version->id }}" {!! old('version_id', 0) == $version->id ? '' : 'style="display: none;"' !!}>
                    <div class="box-header with-border">
                        <h3 class="box-title">Edit Version</h3>
                    </div>
                    <form method="post" action="{{ route('admin.version.version.edit', [$group->id, $version->id]) }}">
                        <div class="box-body">
                            <div class="form-group">
                                <label for="name">Name</label>
                                <input type="text" id="name" name="name" placeholder="Spigot" class="form-control" value="{{ old('name', $version->name) }}">
                            </div>

                            @if ($group->type == 1)
                                <div class="form-group">
                                    <label for="download_url">Download URL</label>
                                    <input type="text" id="download_url" name="download_url" placeholder="https://mydomain.tld/spigot-1.8.jar" class="form-control" value="{{ old('download_url', $version->download_url) }}">
                                </div>
                            @else
                                <div class="form-group">
                                    <label for="key">Key (.jar name)</label>
                                    <input type="text" id="key" name="key" placeholder="spigot-1.8.jar" class="form-control" value="{{ old('key', $version->download_url) }}">
                                    <p class="text-muted small">Jar with this name will be showed with custom name.</p>
                                </div>
                            @endif

                            <div class="form-group" {!! $version->action != 2 ? '' : 'style="display: none;"' !!}>
                                <label for="filename">Filename</label>
                                <input type="text" id="filename" name="filename" placeholder="server.jar" class="form-control" value="{{ old('filename', $version->filename) }}">
                            </div>
                        </div>
                        <div class="box-footer">
                            {!! csrf_field() !!}
                            <input type="hidden" name="version_id" value="{{ $version->id }}">
                            <button class="btn btn-default" type="button" onclick="createVersion({{ $version->id }});">
                                Cancel
                            </button>
                            <button class="btn btn-success pull-right" type="submit">Save</button>
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
        $('#action').on('change', () => {
            if (parseInt($('#action').val()) === 2) {
                $('#filename_select').hide(500);
                $('#filename').val('version.jar.zip');
            } else {
                $('#filename_select').show(500);
                $('#filename').val();
            }
        });

        function editVersion(id) {
            $('#create-box').slideUp(1000);
            $('#edit-box-' + id).slideDown(1000);
        }

        function createVersion(id) {
            $('#edit-box-' + id).slideUp(1000);
            $('#create-box').slideDown(1000);
        }

        $('[data-action="delete"]').click(function (event) {
            event.preventDefault();
            let self = $(this);
            swal({
                title: '',
                type: 'warning',
                text: 'Are you sure you want to delete this version?',
                showCancelButton: true,
                confirmButtonText: 'Delete',
                confirmButtonColor: '#d9534f',
                closeOnConfirm: false,
                showLoaderOnConfirm: true,
                cancelButtonText: 'Cancel',
            }, function () {
                $.ajax({
                    method: 'DELETE',
                    url: '{{ route('admin.version.version', $group->id) }}',
                    headers: {'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')},
                    data: {
                        id: self.data('id')
                    }
                }).done((data) => {
                    self.parent().parent().slideUp();

                    swal({
                        type: 'success',
                        title: 'Success!',
                        text: 'You have successfully deleted this version.'
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
