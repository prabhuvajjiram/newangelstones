$(document).ready(function() {
    $("#contactForm").submit(function(event) {
        event.preventDefault();

        $.ajax({
            type: "POST",
            url: "send_email.php",
            data: $(this).serialize(),
            success: function(response) {
                if (response === "success") {
                    $("#response").html("<div class='alert alert-success'>Message sent successfully.</div>");
                    $("#contactForm")[0].reset(); // Clear the form
                } else {
                    $("#response").html("<div class='alert alert-danger'>Message sending failed. Please try again later.</div>");
                }
            }
        });
    });
});
