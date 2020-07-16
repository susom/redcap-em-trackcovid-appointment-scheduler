Form = {
    ajaxURL: '',
    instrument: '',
    init: function () {

        var body = $('body');

        function goToNextInput(e) {
            var key = e.which,
                t = $(e.target),
                sib = t.next().find('.class');
            var type = t.data('type')
            var num = parseInt(t.data('num')) + 1
            var search = "input[data-type=" + type + "][data-num=" + num + "]"
            sib = $(search);

            //special case for second and third input

            // special case for delete
            if (key == 8) {
                t.val('')
                num = parseInt(t.data('num')) - 1
                search = "input[data-type=" + type + "][data-num=" + num + "]"
                sib = $(search);
                sib.select().focus();
                return false;
            }

            if (key != 9 && (key < 48)) {
                e.preventDefault();
                return false;
            }

            if (key === 9) {
                return true;
            }

            if (!sib || !sib.length) {
                sib = body.find('input').eq(0);
            }
            sib.select().focus();
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
        console.log(Form.ajaxURL)
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
                alert(data.message);
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
