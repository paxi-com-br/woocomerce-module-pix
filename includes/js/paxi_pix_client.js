jQuery(function($){
    let paxi_pix_el = document.getElementById("paxi-pix-qrcode-container");

    if (!paxi_pix_el) {
        return;
    }

    new QRCode(paxi_pix_el, {
        text: paxi_pix_el.dataset.emv,
        width: 200,
        height: 200
    });

    $(document).on("click", "#paxi-pix-qrcode-button", function() {
        navigator.clipboard.writeText(paxi_pix_el.dataset.emv);
    })
});
