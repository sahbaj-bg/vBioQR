

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

                $("#qr-reader-results").append( `<div>[${countResults}] - ${decodedText}</div>`);

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

});

