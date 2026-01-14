<div class="card">
    <div class="card-body">
        <div style="overflow-x: auto;">
            <table id="grid-view-listing" class="table table-bordered dt-responsive nowrap w-100 guard-roaster">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Guard Name</th>
                        <th>Client Name</th>
                        <th>Guard Type</th>
                        <th>Location Code</th>
                        @php
                            $startOfFortnight = \Carbon\Carbon::parse($fortnight->start_date);
                        @endphp

                        @foreach (range(0, 13) as $dayOffset)
                            @php
                                $date = $startOfFortnight->copy()->addDays($dayOffset);
                            @endphp
                            <th colspan="2">{{ $date->format('D, d-M-Y') }}</th>
                        @endforeach
                    </tr>

                    <tr>
                        <th colspan="5"></th>
                        @foreach (range(0, 13) as $dayOffset)
                            <th>Time In</th>
                            <th>Time Out</th>
                        @endforeach
                    </tr>
                </thead>

                <tbody>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    $(function() {
        let guardRoasterTable = $('#grid-view-listing').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('get-guard-roster') }}",
                type: "POST",
                data: function(d) {
                    d._token = "{{ csrf_token() }}";
                },
                dataSrc: function(json) {
                    // return Object.values(json.data);
                    return json.data;
                }
            },
            columns: [{
                    data: null,
                    render: function(data, type, row, meta) {
                        return meta.row + 1;
                    }
                },
                {
                    data: 'guard_name'
                },
                {
                    data: 'client_name'
                },
                {
                    data: 'guardType'
                },
                {
                    data: 'location_code'
                },

                @foreach (range(0, 13) as $dayOffset)
                    {
                        data: function(row) {
                            // var date = '{{ $startOfFortnight->copy()->addDays($dayOffset)->format('Y-m-d') }}';
                            // var schedule = row.time_in_out.find(function(item) {
                            //     return item.date === date;
                            // });
                            // return schedule && schedule.time_in ? moment(schedule.time_in, 'h:mm A').format('h:mm A') : '-';
                            var date =
                                '{{ $startOfFortnight->copy()->addDays($dayOffset)->format('Y-m-d') }}';
                            var cell = row[date + '_time_in'];
                            if (cell && cell.time !== '-') {
                                return `<a href="{{ url('admin/guard-rosters') }}/${cell.id}/edit?date=${date}&field=start_time">${cell.time}</a>`;
                            }
                            return '-';
                        },
                        title: 'Time In'
                    }, {
                        data: function(row) {
                            // var date = '{{ $startOfFortnight->copy()->addDays($dayOffset)->format('Y-m-d') }}';
                            // var schedule = row.time_in_out.find(function(item) {
                            //     return item.date === date;
                            // });
                            // return schedule && schedule.time_out ? moment(schedule.time_out, 'h:mm A').format('h:mm A') : '-';
                            var date =
                                '{{ $startOfFortnight->copy()->addDays($dayOffset)->format('Y-m-d') }}';
                            var cell = row[date + '_time_out'];
                            if (cell && cell.time !== '-') {
                                return `<a href="{{ url('admin/guard-rosters') }}/${cell.id}/edit?date=${date}&field=end_time">${cell.time}</a>`;
                            }
                            return '-';
                        },
                        title: 'Time Out'
                    },
                @endforeach
            ],
            paging: true,
            pageLength: 50,
            lengthMenu: [10, 25, 50, 100],
            order: [
                [0, 'asc']
            ]
        });
    })
</script>
