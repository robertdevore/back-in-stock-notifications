// js/bisn.js

jQuery(document).ready(function($) {
    // Check if product is out of stock
    if ($('.stock.out-of-stock').length) {
        // Create the waitlist form HTML
        var waitlistForm = `
            <div id="waitlist-form" style="margin-top: 15px;">
                <p>Be notified when this product is back in stock!</p>
                <input type="email" id="waitlist-email" placeholder="Enter your email" required style="margin-right: 5px;" />
                <button id="waitlist-submit">Notify Me</button>
            </div>`;

        // Append the form directly below the "Out of Stock" text
        $('.stock.out-of-stock').after(waitlistForm);
    }

    // Handle the form submission
    $(document).on('click', '#waitlist-submit', function(e) {
        e.preventDefault();

        var email = $('#waitlist-email').val();
        var productID = $('[name="product_id"]').val(); // Get product ID from hidden field

        if (!email) {
            alert('Please enter a valid email.');
            return;
        }

        // Send AJAX request to add email to waitlist
        $.post(bisnAjax.ajaxurl, {
            action: 'bisn_add_to_waitlist',
            email: email,
            product_id: productID,
            security: bisnAjax.nonce
        }, function(response) {
            if (response.success) {
                alert(response.data.message);
                $('#waitlist-form').remove(); // Remove form after successful submission
            } else {
                alert(response.data.message);
            }
        });
    });
});
