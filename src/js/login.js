Form = {
    ajaxURL: '',
    instrument: '',
    init: function () {

        var body = $('body');

        function goToNextInput(e) {
            var key = e.which;
            var t = $(e.target);
            var type = t.data('type');
            var num = parseInt(t.data('num'));
            var search = "input[data-type=" + type + "][data-num=" + (num + 1) + "]";
            var sib = $(search);

            if (type === 'newuniq' && (num === 2 || num === 3)) {
                if (key >= 48 && key <= 57) {
                    // Skip numbers
                    key == 8;
                }

                // Uppercase
                t.val(t.val().toUpperCase());
            }

            // Delete
            if (key == 8) {
                if (t.val() !== '') {
                    // Clear the current value if present
                    t.val('');
                } else {
                    // Goto the previous input and clear it
                    if (num === 1) {
                        if (type === 'zipcode') {
                            // Go back to last from previous section
                            $('input[data-type="newuniq"]').last().select().focus();
                        } else {
                            // Can't go back
                        }
                    } else {
                        $('input[data-type="' + type + '"][data-num="' + (num - 1) + '"]').select().focus();
                    }
                }
                e.preventDefault();
                return false;
            }

            // Let tabs work like normal...
            if (key === 9) {
                return true;
            }

            // if it is empty, don't move forward
            if (t.val() === '') {
                e.preventDefault();
                return false;
            }

            if ((key >= 48 && key <= 57) ||
                (key >= 65 && key <= 90) ||
                (key >= 97 && key <= 122)) {
                // Find the next input if no siblings
                if (!sib || !sib.length) {
                    var tabindex = t.attr('tabindex');
                    tabindex++; //increment tabindex
                    sib = $('[tabindex=' + tabindex + ']'); //.focus();

                    // if (type === 'newuniq' && num === 8) {
                    //     sib = $('input[data-type="zipcode"][data-num="1"]');
                    // }
                }
                sib.select().focus();
            } else {
                e.preventDefault();
                return false;
            }
        }

        function onKeyDown(e) {
            var key = e.which;

            if (key === 9 || (key >= 48 && key <= 57)) {
                return true;
            }

            e.preventDefault();
            return false;
        }

        function onFocus(e) {
            $(e.target).select();
        }

        $(document).on('keyup', 'input', function (e) {
            goToNextInput(e)
        });

        $(document).on('click', 'input', function (e) {
            goToNextInput(e)
        });

        $(document).on('click', '#verify', function () {
            var unique = ''
            var zipcode = ''
            $(".newuniq").each(function (index) {
                var val = $(this).val();
                if (val === '') {
                    alert('code cant be empty');
                    unique = false;
                    $(this).focus()
                    return unique;
                    //second and third char of the code should be alphabet
                } else if (val.match(/^[a-zA-Z]+/) === null && (index === 1 || index === 2)) {
                    alert('code must be alphabet');
                    unique = false;
                    $(this).focus()
                    return unique;
                } else if (val.match(/^[0-9]+/) === null && index !== 1 && index !== 2) {
                    alert('code must be numeric');
                    unique = false;
                    $(this).focus()
                    return unique;
                } else {
                    unique += val;
                }

            });
            // if we got all unique code values
            if (unique !== false) {
                $(".zipcode").each(function (index) {
                    var val = $(this).val();
                    if (val === '') {
                        alert('please complete the zipcode');
                        zipcode = false;
                        $(this).focus()
                        return zipcode;
                    } else if (val.match(/^[0-9]+/) === false) {
                        alert('zipcode has to be a number');
                        zipcode = false;
                        $(this).focus()
                        return zipcode;
                    } else {
                        zipcode += val;
                    }

                });
            }

            if (unique !== false && zipcode !== false) {
                Form.ajaxVerify(unique, zipcode)
            }
        })
    },
    ajaxVerify: function (unique, zipcode) {
        $.ajax({
            url: Form.ajaxURL,
            data: {newuniq: unique, zipcode_abs: zipcode, instrument: Form.instrument},
            type: 'POST',
            success: function (response) {
                data = JSON.parse(response);
                setCookie('login', data.cookie, 1)

                window.location.replace(data.link);
            },
            error: function (request, error) {
                data = JSON.parse(request.responseText)
                $('#errors').html('<strong>' + data.message + '</strong>').show()
            }
        });
    }
}

function setCookie(name, value, days) {
    var expires = "";
    if (days) {
        var date = new Date();
        date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
        expires = "; expires=" + date.toUTCString();
    }
    document.cookie = name + "=" + (value || "") + expires + "; path=/";
}

function getCookie(name) {
    var nameEQ = name + "=";
    var ca = document.cookie.split(';');
    for (var i = 0; i < ca.length; i++) {
        var c = ca[i];
        while (c.charAt(0) == ' ') c = c.substring(1, c.length);
        if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length, c.length);
    }
    return null;
}

function eraseCookie(name) {
    document.cookie = name + '=; Path=/; Expires=Thu, 01 Jan 1970 00:00:01 GMT;';
}

window.onload = function () {
    Form.init();
}
