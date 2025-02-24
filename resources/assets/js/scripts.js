$(document).ready(function () {
    $('#languages').select2({
        theme: "bootstrap-5",
        width: $(this).data('width') ? $(this).data('width') : $(this).hasClass('w-100') ? '100%' : 'style',
        placeholder: $(this).data('placeholder'),
    });

    $('#new-row-modal').on('show.bs.modal', function () {
        setTimeout(() => $(this).find('#key').focus(), 50);
    });
});

function addNewRow(element) {
    const translationTable = $('#translation-table');
    const languages = tryParseJSON($(element).closest('.modal').find('input[name="languages"]').val());
    const key = $(element).closest('.modal').find('#key').val().trim();

    if (isEmpty(key)) {
        return showToast('Please enter a key', 'danger');
    }

    if (!/^[A-Za-z0-9_-\s]+$/.test(key)) {
        return showToast('Invalid key. Only letters, numbers, underscores, and dashes are allowed', 'danger');
    }

    if (languages.some(l => translationTable.find(`textarea[name="${l}[${key}]"]`).length)) {
        return showToast('This key already exists', 'danger');
    }

    let newRow = `<tr><td>${key}</td>`;

    languages.forEach(function (language) {
        newRow += `<td><textarea name="${language}[${key}]" class="form-control" rows="2"></textarea></td>`;
    });

    newRow += `<td><a href="javascript:void(0)" class="btn btn-danger" onclick="deleteRow(this)">Delete</a></td>`;

    newRow += `</tr>`;

    translationTable.find('tbody').append(newRow);

    $(element).closest('.modal').find('#key').val('');
    $('#new-row-modal').modal('hide');
}

function deleteRow(element) {
    if (confirm('Are you sure you want to delete this row?')) {
        $(element).closest('tr').remove();
    }
}

function saveChanges(element) {
    const form = document.getElementById($(element).data('form'));
    const url = $(form).attr('action');
    const btnTxt = $(element).text();

    $.ajax({
        type: 'POST',
        url: url,
        data: new FormData(form),
        processData: false,
        contentType: false,
        beforeSend: function() {
            $(element).html('Loading ...').prop("disabled", true);
        },
        success: function(response) {
            if (response?.success) {
                showToast(response.success);
            }
        },
        error: function(response) {
            response?.responseJSON?.errors && $.each(response?.responseJSON?.errors, (_, value) => showToast(value, 'danger'));
        },
        complete: function() {
            $(element).html(btnTxt).prop("disabled", false);
        },
    });
}

function showToast(message, type = 'info') {
    const toastContainer = $('#toast-container');
    const toast = $(`<div class="toast align-items-center text-bg-${type} border-0 show mb-1" role="alert">
        <div class="d-flex">
            <div class="toast-body">${message}</div>
        </div>
    </div>`);

    toastContainer.append(toast);
    setTimeout(() => toast.remove(), 3000);
}

function tryParseJSON(jsonString) {
    try {
        const obj = JSON.parse(jsonString);
        return obj;
    } catch (e) {
        return null;
    }
}

function isEmpty(property) {
    return (property === null || property === "" || typeof property === "undefined");
}
