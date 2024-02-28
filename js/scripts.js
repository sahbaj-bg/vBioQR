function domReady(fn) {
    if (
        document.readyState === "complete" ||
        document.readyState === "interactive"
    ) {
        setTimeout(fn, 1000);
    } else {
        document.addEventListener("DOMContentLoaded", fn);
    }
}

var html5QrcodeScanner = new Html5QrcodeScanner(
    "qr-reader", { fps: 10, qrbox: 250 });
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
        html5QrcodeScanner.render(app.onQRScanSuccess, app.onQRScanError);
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