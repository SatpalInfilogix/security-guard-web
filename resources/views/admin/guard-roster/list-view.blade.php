<table id="list-view" class="table table-bordered dt-responsive  nowrap w-100">
    <thead>
        <tr>
            <th>#</th>
            <th>Guard Name</th>
            <th>Client Name</th>
            <th>Date</th>
            <th>Start Time</th>
            <th>End Time</th>
            @canany(['edit security guards', 'delete security guards'])
            <th>Action</th>
            @endcanany
        </tr>
    </thead>
    <tbody>
    </tbody>
</table>

<x-include-plugins :plugins="['sweetAlert']"></x-include-plugins>

<script>
    $(function() {
        let guardRoasterTable = $('#list-view').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('get-guard-roaster-list') }}",
                type: "POST",
                data: function(d) {
                    d._token = "{{ csrf_token() }}";
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
                        
                        @can('edit security guards')
                        actions += `<a class="btn btn-outline-secondary btn-sm edit" href="{{ url('admin/guard-rosters') }}/${row.id}/edit">`;
                        actions += '<i class="fas fa-pencil-alt"></i>';
                        actions += '</a>';
                        @endcan

                        @can('delete security guards')
                            actions += `<a data-source="Guard Roster" class="guard-delete-btn btn btn-outline-secondary btn-sm" href="#" data-id="${row.id}"> <i class="fas fa-trash-alt"></i></a>`;
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
