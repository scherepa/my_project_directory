/**
*
* Simple (ugly) code to handle loading of users for admin
* Using var and not let as otherwise rediclare error
*
*/
let $container1 = $('#usersForAd');
$container1.DataTable({
    ajax:  '/admin/users',
    language: {
        emptyTable: 'Have No Available Users'
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
        }

    ],
    paging: false,
    searching: false,
    ordering:  false,
    processing: true,
    retrieve: true,
    serverside: true
});
