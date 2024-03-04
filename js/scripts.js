

var html5QrcodeScanner = new Html5QrcodeScanner(
    "qr-reader", { fps: 10, qrbox: 250 });
var lastResult, countResults = 0;
var app = {
    init: function () {
        $("#v-qr-scanner").hide();
        $('#v-qr-scanner-btn').on('click', app.qrScanner);
        $('#v-qr-cancel').on('click', app.qrScannerCancel);
    },
    qrScanner: function () {
        $("#v-qr-scanner").show();
        // var html5QrcodeScanner = new Html5QrcodeScanner(
        //     "qr-reader", { fps: 10, qrbox: 250 });
        function onScanSuccess(decodedText, decodedResult) {
            if (decodedText !== lastResult) {
                ++countResults;
                lastResult = decodedText;
                console.log(`Scan result = ${decodedText}`, decodedResult);

                $("#qr-reader-results").append(`<div>[${countResults}] - ${decodedText}</div>`);

                // Optional: To close the QR code scannign after the result is found
                html5QrcodeScanner.clear();
            }
        }
        html5QrcodeScanner.render(onScanSuccess);
    },
    onQRScanSuccess: function (decodedText, decodedResult) {
        console.log(`Scan result: ${decodedText}`, decodedResult);
        alert("You Qr is : " + decodedText, decodedResult);
        // html5QrcodeScanner.clear();
    },
    onQRScanError: function (errorMessage) {
        console.log("Scan error: ", errorMessage);
        // alert(errorMessage);
        // html5QrcodeScanner.clear();
    },
    qrScannerCancel: function () {
        $("#v-qr-scanner").hide();

        html5QrcodeScanner.clear();
    }
};

$(document).ready(function () {
    app.init();


    $('#iregisterform').submit(function (ev) {
        var self = $(this);
        ev.preventDefault();
        var cp="No";
        // var cp = $('select[name=cp]').val();
        // alert(cp);
        // if (cp == "") {
        //     $('.cerror').show().text("Please choose cross-platform setting - see note below about what this means");
        //     return;
        // }

        $('.cerror').empty().hide();

        $.ajax({
            url: AJAX_URL,
            method: 'POST',
            data: { registerusername: self.find('[name=registerusername]').val(), crossplatform: cp },
            dataType: 'json',
            success: function (j) {
                $('#iregisterform,#iregisterdokey').toggle();
                /* activate the key and get the response */
                webauthnRegister(j.challenge, function (success, info) {
                    if (success) {
                        $.ajax({
                            url: AJAX_URL,
                            method: 'POST',
                            data: { register: info },
                            dataType: 'json',
                            success: function (j) {
                                $('#iregisterform,#iregisterdokey').toggle();
                                $('.cdone').text("Registration completed successfully").show();
                                setTimeout(function () { $('.cdone').hide(300); }, 2000);
                            },
                            error: function (xhr, status, error) {
                                $('.cerror').text("Registration failed: " + error + ": " + xhr.responseText).show();
                            }
                        });
                    } else {
                        $('.cerror').text(info).show();
                    }
                });
            },

            error: function (xhr, status, error) {
                $('#iregisterform').show();
                $('#iregisterdokey').hide();
                $('.cerror').text("Couldn't initiate registration: " + error + ": " + xhr.responseText).show();
            }
        });
    });

    $('#iloginform').submit(function (ev) {
        var self = $(this);
        ev.preventDefault();
        $('.cerror').empty().hide();

        $.ajax({
            url: AJAX_URL,
            method: 'POST',
            data: { loginusername: self.find('[name=loginusername]').val() },
            dataType: 'json',
            success: function (j) {
                $('#iloginform,#ilogindokey').toggle();
                /* activate the key and get the response */
                webauthnAuthenticate(j.challenge, function (success, info) {
                    if (success) {
                        $.ajax({
                            url: AJAX_URL,
                            method: 'POST',
                            data: { login: info },
                            dataType: 'json',
                            success: function (j) {
                                $('#iloginform,#ilogindokey').toggle();
                                $('.cdone').text("Login completed successfully").show();
                                setTimeout(function () { $('.cdone').hide(300); }, 2000);
                            },
                            error: function (xhr, status, error) {
                                $('.cerror').text("login failed: " + error + ": " + xhr.responseText).show();
                            }
                        });
                    } else {
                        $('.cerror').text(info).show();
                    }
                });
            },

            error: function (xhr, status, error) {
                $('#iloginform').show();
                $('#ilogindokey').hide();
                $('.cerror').text("couldn't initiate login: " + error + ": " + xhr.responseText).show();
            }
        });
    });

});