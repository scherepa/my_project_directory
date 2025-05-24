$('#reps').DataTable({
    searching: false,
    ordering:  false,
    paging: true,
    lengthMenu: [2, 5, 10, 20, 50],
    pageLength: 2,
    dataSrc: 'data',
    language: {
        emptyTable: 'Have No Available Users'
    },
    stateSave: true,
    processing: true,
    serverside: true,
    ajax: '/rep/agents',
    columns: [
        { data: 'id' },
        { data: 'username' },
        {
            data: 'manager_id',
            render: function (data, type, row, meta) {
                let currentLabel = row.manager_name ?? 'N/A';
                let html = `
                <fieldset disabled>
                    <div class="input-group" size="1">
                        <select class="agent-select-manager form-select form-select-sm" data-user-id="${row.id}">
                        <option value="${data ?? ''}" selected>${!data ? '' : '('+data+') '}${currentLabel}</option>
                        </select>
                        <div class="input-group-append disabled">
                        <button class="btn btn-sm btn-outline-primary assign-manager-btn disabled" data-user-id="${row.id}">OK</button>
                        </div>
                    </div>
                    </fieldset>
                `;
                return html;
            }
        },
        {
            data: 'login_time',
            render: function (data) {
                return data ?? 'N/A';
            }
        },
        { 
            data: 'role',
            render: function (data) {
                if (!data) {return 'N/A';}
                let replaced = data.replace('ROLE_', '');

                return `${replaced[0]}${replaced.slice(1).toLowerCase()}`
            }
        },
    ]
});
