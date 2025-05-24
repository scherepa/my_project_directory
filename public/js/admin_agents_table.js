/**
 * Simple (ugly) code to handle loading of agents for admin
 */
let $container0 = $('#reps');
$container0.DataTable({
    paging: false,
    searching: false,
    ordering:  false,
    processing: true,
    retrieve: true,
    serverside: true,
    language: {
        emptyTable: 'Have No Available Agents'
    },
    columns: [
        { data: 'id' },
        { data: 'username' },
        { data: 'manager_id', render: function (data, type, row, meta) {
                return row.html;
            }
        },
        { data: 'login_time', render: function (data, type, row, meta) {
                return data ?? 'N/A';
            }
        },
        { data: 'role' },
    ],
    ajax: {
        type:'GET',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="_csrf_token"]').attr('content')
        },
        url: '/admin/agents',
        dataType: 'json',
    },   
});

