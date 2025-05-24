
let $table = $('#users');

$table.DataTable({
    ajax:{
        url: `${window.location.pathname}/users`,    
    dataSrc: function (json) {
            if (!json || typeof json !== 'object' || !Array.isArray(json.data)) {
                alert('Unexpected server response. Please reload the page.');
                return [];
            }
            return json.data;
        },
        error: function (xhr, status, error) {
            const isHTML = xhr.responseText && xhr.responseText.includes('<!DOCTYPE html>');

            if (xhr.status === 401 || xhr.status === 403 || isHTML) {
                window.location.href = '/login';
            } else {
                console.error('DataTable load error:', status, error);
                alert('Failed to load table data. Please try again.');
            }
        }
    },
    processing: true,
    serverSide: true,
    searching: false,
    ordering:  false,
    paging: true,
    lengthMenu: [2, 5, 10, 20, 50],
    pageLength: 2,
    stateSave: true,
    language: {
        emptyTable: 'Have No Available Users'
    },
    columns: [
        { data: 'id' },
        { data: 'username' },
        {
            data: 'manager_id',
            render: function (data, type, row, meta) {
                let currentLabel = row.manager_name ?? 'N/A';
                let html = `
                    <div class="input-group" size="1">
                        <select class="agent-select form-select form-select-sm" data-user-id="${row.id}">
                        <option value="${data ?? ''}" selected>${!data ? '' : '('+data+') '}${currentLabel}</option>
                        </select>
                        <div class="input-group-append">
                        <button class="btn btn-sm btn-outline-primary assign-btn" data-user-id="${row.id}">OK</button>
                        </div>
                    </div>
                `;
                return html;
            }
        },
        {
            data: 'login_time',
            render: function (data) {
                return data ?? 'N/A';
            }
        }
    ],
    drawCallback: function () {
        $('.assign-btn').off('click').on('click', function () {
            let userId = $(this).data('user-id');
            let managerId = $(`.agent-select[data-user-id="${userId}"]`).val();
            $.ajax({
                url: `${window.location.pathname}/users/${userId}/assign-manager`,
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="_csrf_token"]').attr('content')
                },
                xhrFields: {
                    withCredentials: true
                },
                data: JSON.stringify({ userId, managerId }),
                contentType: 'application/json',
                dataType: 'json',
                success: function () {
                    alert('Manager assigned!');
                    $table.DataTable().ajax.reload(null, false);
                },
                error: function (xhr,status,error) {
                    let isHTML = xhr.responseText && xhr.responseText.includes('<!DOCTYPE html>');
                    if (xhr.status === 401 || xhr.status === 403 || isHTML) {
                        window.location.href = '/login';
                        return;
                    }
                    if (xhr.status === 422) {
                        let message = 'Validation failed:\n';
                        try {
                            const response = JSON.parse(xhr.responseText);
                            if (response.errors) {
                                Object.entries(response.errors).forEach(([field, messages]) => {
                                    message += `\n${field}: ${messages.join(', ')}`;
                                });
                            } else {
                                message += xhr.responseText;
                            }
                        } catch (e) {
                            message += xhr.responseText;
                        }
                        alert(message);
                        $table.DataTable().ajax.reload(null, false);
                    } else {
                        console.error('Unexpected error:', status, error);
                        alert('Failed to assign: ' + xhr.responseText);
                        $table.DataTable().ajax.reload(null, false);
                    }
                }
            });
        });
    }
});

$table.on('focus', '.agent-select', function () {
    let $select = $(this);
    let loaded = $select.data('loaded');
    let userId = $select.data('user-id');
    if (loaded) return;

    $.ajax({
        url: `${window.location.pathname}/users/${userId}/available-managers`,
        method: 'GET',
        success: function (options) {
            const currentVal = $select.val();
            const currentEmp = !$select.val() || $select.val() =='' ? 'selected disabled' : '';
            const emptyOp = window.location.pathname === '/admin' ? `<option value="" ${currentEmp}>-- Choose/Remove --</option>` : '<option value="" disabled>-- Choose --</option>';
            $select.empty();
            $select.append(emptyOp);
            options.forEach(agent => {
                const selected = agent.id == currentVal ? 'selected disabled' : '';
                $select.append(`<option value="${agent.id}" ${selected}>${agent.label}</option>`);
            });
            $select.data('loaded', true);
        },
        error: function (xhr, status, error) {
            let isHTML = xhr.responseText && xhr.responseText.includes('<!DOCTYPE html>');
            if (isHTML) {
                window.location.href = '/login';
            } else {
                console.warn('Failed to load options:', status, error);
                $select.empty().append('<option disabled>Failed to load options</option>');
                $table.DataTable().ajax.reload(null, false);
            }
        }
    });
});
