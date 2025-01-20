<div class="row mb-3">
    <div class="col-md-12">
        <form id="filterForm" method="GET">
            <div class="row">
                <div class="col-md-3">
                    <select name="guard_id" id="guard_id" class="form-control">
                        <option value="" disabled selected>Select Guard</option>
                        @foreach($securityGuards as $securityGuard)
                            <option value="{{ $securityGuard->id }}">{{ $securityGuard->first_name .' '.$securityGuard->sure_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="client_id" id="client_id" class="form-control">
                        <option value="" disabled selected>Select Client</option>
                        @foreach($clients as $client)
                            <option value="{{ $client->id }}">{{ $client->client_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="client_site_id" id="client_site_id" class="form-control">
                        <option value="" disabled selected>Select Client Site</option>
                        @foreach($clientSites as $clientSite)
                            <option value="{{ $clientSite->id }}">{{ $clientSite->location_code }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="button" id="searchBtn" class="btn btn-primary">Search</button>
                </div>
            </div>
        </form>
    </div>
</div>
<div class="card">
    <div class="card-body">
        <table id="list-view" class="table table-bordered dt-responsive  nowrap w-100">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Guard Name</th>
                    <th>Client Name</th>
                    <th>Guard Type</th>
                    <th>Date</th>
                    <th>Start Time</th>
                    <th>End Time</th>
                    @canany(['edit guard roaster', 'delete guard roaster'])
                    <th>Action</th>
                    @endcanany
                </tr>
            </thead>
            <tbody>
            </tbody>
        </table>
    </div>
</div>

<x-include-plugins :plugins="['sweetAlert', 'chosen']"></x-include-plugins>

<script>
     $(function(){
        $('#guard_id').chosen({
            width: '100%',
            placeholder_text_multiple: 'Select Guard'
        });
        $('#client_id').chosen({
            width: '100%',
            placeholder_text_multiple: 'Select Client'
        });
        $('#client_site_id').chosen({
            width: '100%',
            placeholder_text_multiple: 'Select Client Site'
        });
    });
    $(document).ready(function() {
        let guardRoasterTable = $('#list-view').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('get-guard-roster-list') }}",
                type: "POST",
                data: function(d) {
                    d._token = "{{ csrf_token() }}";
                    d.guard_id = $('[name="guard_id"]').val();
                    d.client_id = $('[name="client_id"]').val();
                    d.client_site_id = $('[name="client_site_id"]').val();
                    return d;
                },
                dataSrc: function(json) {
                    return Object.values(json.data);
                }
            },
            columns: [
                { 
                    data: null, 
                    render: function(data, type, row, meta) {
                        return meta.row + 1 + meta.settings._iDisplayStart;
                    }
                },
                { data: 'user.first_name' },
                { data: 'client.client_name' },
                { data: 'guardType', render: function(data) { return data ? data : 'N/A'; } },
                { data: 'date' },
                {
                    data: 'start_time',
                    render: function(data) {
                        if (data) {
                            return moment(data, 'HH:mm:ss').format('hh:mmA');
                        }
                        return '';
                    }
                },
                {
                    data: 'end_time',
                    render: function(data) {
                        if (data) {
                            return moment(data, 'HH:mm:ss').format('hh:mmA');
                        }
                        return '';
                    }
                },
                {
                    data: null,
                    render: function(data, type, row) {
                        var actions = '<div class="action-buttons">';
                        
                        @can('edit guard roaster')
                        actions += `<a class="btn btn-primary waves-effect waves-light btn-sm edit" href="{{ url('admin/guard-rosters') }}/${row.id}/edit">`;
                        actions += '<i class="fas fa-pencil-alt"></i>';
                        actions += '</a>';
                        @endcan

                        @can('delete guard roaster')
                            actions += `<a data-source="Guard Roster" class="guard-delete-btn btn btn-danger waves-effect waves-light btn-sm" href="#" data-id="${row.id}"> <i class="fas fa-trash-alt"></i></a>`;
                        @endcan

                        actions += '</div>';
                        return actions;
                    }
                }
            ],
            paging: true,
            pageLength: 10,
            lengthMenu: [10, 25, 50, 100],
            order: [[0, 'asc']]
        });
        $('#searchBtn').on('click', function() {
            guardRoasterTable.ajax.reload();
        });
        // $(document).on('change', '[name="guard_id"], [name="client_id"], [name="client_site_id"]', function() {
        //     guardRoasterTable.ajax.reload();
        //     });

        $('#list-view').on('click', '.guard-delete-btn', function() {
            let source = $(this).data('source');
            let guardId = $(this).data('id');
            var deleteApiEndpoint = "{{ route('guard-rosters.destroy', '') }}/" + guardId;

            swal({
                title: "Are you sure?",
                text: `You really want to remove this ${source}?`,
                type: "warning",
                showCancelButton: true,
                closeOnConfirm: false,
            }, function(isConfirm) {
                if (isConfirm) {
                    $.ajax({
                        url: deleteApiEndpoint,
                        method: 'DELETE',
                        data: {
                            '_token': '{{ csrf_token() }}'
                        },
                        success: function(response) {
                            if(response.success){
                                swal({
                                    title: "Success!",
                                    text: response.message,
                                    type: "success",
                                    showConfirmButton: false
                                }) 

                                setTimeout(() => {
                                    location.reload();
                                }, 2000);
                            }
                        }
                    })
                }
            });
        })

    });
</script>
