@extends('layouts.app')

@section('content')
    <div class="page-content">
        <div class="content">
            <div class="page-header">
                <div class="add-item d-flex">
                    <div class="page-title">
                        <h4>Roles And Permission</h4>
                        <h6>Roles and permissions management</h6>
                    </div>
                </div>
            </div>
    
            <div class="accordion" id="rolesAccordion">
                @foreach ($roles as $role)
                    <div class="card">
                        <form id="form-role-{{ $role->id }}" data-role-id="{{ $role->id }}">
                            @csrf
                            <div class="card-header" id="role-{{ $role->id }}-heading">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h2 class="mb-0 card-full">
                                            <button class="btn btn-collapse" type="button" data-toggle="collapse"
                                                data-target="#role-{{ $role->id }}-collapse"
                                                aria-expanded="{{ $loop->first ? 'true' : 'false' }}"
                                                aria-controls="role-{{ $role->id }}-collapse">
                                                {{ ucwords($role->name) }}
                                            </button>
                                        </h2>
                                    </div>
                                    {{--<div class="col-md-6">
                                        <div class="checkbox-section ml-auto">
                                            <div class="row">
                                                <div class="col-md-6"></div>
                                                <div class="col-3">
                                                    <div class="checkbox-group mr-4">
                                                        <h6>Backend</h6>
                                                        <input type="checkbox" class="backend-checkbox" name="permissions[]"
                                                            id="{{ $role->id . '_' . 'backend' }}" value="backend"
                                                            {{ $role->permissions->contains('name', 'backend') ? 'checked' : '' }}>
                                                    </div>
                                                </div>
                                                <div class="col-3">
                                                    <div class="checkbox-group">
                                                        <h6>Shield</h6>
                                                        <input type="checkbox" class="backend-checkbox" name="permissions[]"
                                                            id="{{ $role->id . '_' . 'pos' }}" value="pos"
                                                            {{ $role->permissions->contains('name', 'pos') ? 'checked' : '' }}>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>--}}
                                </div>
    
    
                            </div>
    
                            <div id="role-{{ $role->id }}-collapse" class="collapse {{ $loop->first ? 'show' : '' }}"
                                aria-labelledby="role-{{ $role->id }}-heading" data-parent="#rolesAccordion">
                                <div class="card-body">
                                    <input type="hidden" name="role_id" value="{{ $role->id }}">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Module Name</th>
                                                <th>View</th>
                                                <th>Add</th>
                                                <th>Update</th>
                                                <th>Delete</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($modules as $module)
                                                <tr>
                                                    <th scope="row">{{ $module->name }}</th>
                                                    <td>
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox" name="permissions[]"
                                                                id="{{ $role->id . 'view_' . $module->slug }}"
                                                                value="{{ 'view ' . $module->slug }}"
                                                                {{ $role->permissions->contains('name', 'view ' . $module->slug) ? 'checked' : '' }}>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox" name="permissions[]"
                                                                id="{{ $role->id . 'create_' . $module->slug }}"
                                                                value="{{ 'create ' . $module->slug }}"
                                                                {{ $role->permissions->contains('name', 'create ' . $module->slug) ? 'checked' : '' }}>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox" name="permissions[]"
                                                                id="{{ $role->id . 'edit_' . $module->slug }}"
                                                                value="{{ 'edit ' . $module->slug }}"
                                                                {{ $role->permissions->contains('name', 'edit ' . $module->slug) ? 'checked' : '' }}>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox" name="permissions[]"
                                                                id="{{ $role->id . 'delete_' . $module->slug }}"
                                                                value="{{ 'delete ' . $module->slug }}"
                                                                {{ $role->permissions->contains('name', 'delete ' . $module->slug) ? 'checked' : '' }}>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                    <button type="button" class="btn btn-primary mt-2 btn-save"
                                        data-role-id="{{ $role->id }}">Save</button>
                                </div>
                            </div>
                        </form>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
<script>
    $(document).ready(function() {
        $('.collapse').on('shown.bs.collapse', function() {
            $(this).parent().find('.btn-collapse').addClass('active');
        });

        $('.collapse').on('hidden.bs.collapse', function() {
            $(this).parent().find('.btn-collapse').removeClass('active');
        });

        $('.btn-collapse').click(function() {
            // Remove active class from all buttons
            $('.btn-collapse').removeClass('active');
            // Add active class to the clicked button
            $(this).addClass('active');
        });

        // AJAX Form Submission
        $('.btn-save').click(function() {
            var roleId = $(this).data('role-id');
            var formData = $('#form-role-' + roleId).serialize();

            $.ajax({
                url: "{{ route('roles-and-permissions.store') }}",
                type: 'POST',
                data: formData,
                success: function(response) {
                    alert('Permissions saved successfully!');
                },
                error: function(xhr, status, error) {
                    console.error(xhr.responseText);
                    alert('Error saving permissions!');
                }
            });
        });
    });
</script>

@endsection
