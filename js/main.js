$(document).ready(function(){
    // Mobile Menu Toggle
    $('.mobile-menu-btn').click(function(e) {
        e.stopPropagation();
        $(this).toggleClass('active');
        $('.nav-links').toggleClass('active');
        $('body').toggleClass('no-scroll');
    });

    // Close menu when clicking outside
    $(document).click(function(e) {
        if (!$(e.target).closest('.nav-links, .mobile-menu-btn').length) {
            $('.mobile-menu-btn').removeClass('active');
            $('.nav-links').removeClass('active');
            $('body').removeClass('no-scroll');
        }
    });

    // Close menu when clicking menu items
    $('.nav-links a').click(function() {
        if ($(window).width() <= 768) {
            $('.mobile-menu-btn').removeClass('active');
            $('.nav-links').removeClass('active');
            $('body').removeClass('no-scroll');
        }
    });

    // Initialize Owl Carousel
    $('.granite-carousel').owlCarousel({
        loop: true,
        margin: 30,
        nav: true,
        dots: true,
        autoplay: true,
        autoplayTimeout: 5000,
        autoplayHoverPause: true,
        navText: [
            '<i class="fas fa-chevron-left"></i>',
            '<i class="fas fa-chevron-right"></i>'
        ],
        responsive: {
            0: {
                items: 1
            },
            600: {
                items: 2
            },
            1000: {
                items: 3
            }
        }
    });

    // Smooth scrolling for navigation links
    $('a[href^="#"]').on('click', function(e) {
        e.preventDefault();
        var target = $(this.hash);
        if (target.length) {
            $('html, body').animate({
                scrollTop: target.offset().top - 70
            }, 1000);
        }
    });

    // Header background on scroll
    $(window).scroll(function() {
        if ($(this).scrollTop() > 50) {
            $('.header').css('background', 'rgba(31, 31, 31, 0.95)');
        } else {
            $('.header').css('background', 'transparent');
        }
    });

    // Active menu item on scroll
    $(window).scroll(function() {
        var scrollDistance = $(window).scrollTop();
        
        $('section').each(function(i) {
            if ($(this).position().top <= scrollDistance + 100) {
                $('.nav-links a.active').removeClass('active');
                $('.nav-links a').eq(i).addClass('active');
            }
        });
    }).scroll();

    // Form Submission
    $('#contactForm').submit(function(e) {
        e.preventDefault();
        
        var formData = $(this).serialize();
        
        $.ajax({
            url: 'send_email.php',
            type: 'POST',
            data: formData,
            success: function(response) {
                alert('Message sent successfully!');
                $('#contactForm')[0].reset();
            },
            error: function() {
                alert('Failed to send message. Please try again later.');
            }
        });
    });
});