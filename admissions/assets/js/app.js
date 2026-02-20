(function( $ ){
	    jQuery(window).scroll(function(){
	      if (jQuery(this).scrollTop() > 300) {
	        jQuery('.scrollToTop').fadeIn();
	      } else {
	        jQuery('.scrollToTop').fadeOut();
	      }
	    });
	     
	    //Click event to scroll to top
	    jQuery('.scrollToTop').click(function(){
	      jQuery('html, body').animate({scrollTop : 0},800);
	      return false;
	    });
	
})( jQuery );

jQuery(document).ready(function($) {
        // Scroll helper
    	function scrollToError($el) {
    	    $('html, body').animate({
    	        scrollTop: $el.offset().top - 100
    	    }, 600, () => $el.focus());
    	}
        
        let clickedButton = '';
    
        document.querySelectorAll('.track-btn').forEach(btn => {
          btn.addEventListener('click', function () {
            clickedButton = this.dataset.button || '';
          });
        });
        
    	// Common validation function
    	function validateForm($form) {
    	    let proceed = true;
    	    let firstErrorField = null;
    
    	    const emailReg = /^[\w-.]+@([\w-]+\.)+[\w-]{2,4}$/;
    	    const phoneReg = /^\d{10}$/;
    
    	    $form.find("input[data-required=true], select[data-required=true], textarea[data-required=true]").each(function() {
    	        const $field = $(this);
    	        const value = $.trim($field.val());
    	        $field.siblings().removeClass('err-txt-active');
    
    	        // Empty validation
    	        if (!value) {
    	            $field.siblings().addClass('err-txt-active');
    	            proceed = false;
    	            if (!firstErrorField) firstErrorField = $field;
    	            return; // continue to next
    	        }
    
    	        // Email validation
    	        if ($field.attr("type") === "email" && !emailReg.test(value)) {
    	            $field.siblings().addClass('err-txt-active');
    	            proceed = false;
    	            if (!firstErrorField) firstErrorField = $field;
    	        }
    
    	        // Phone validation
                if ($field.attr("type") === "number") {
                    // Check pattern and length
                    if (!phoneReg.test(value) || value.length < 10 ) {
                        $field.siblings().addClass('err-txt-active');
                        proceed = false;
                        if (!firstErrorField) firstErrorField = $field;
                    }
                }
    
    	    });
    
    	    // Input correction listener
    	    $form.find("input, textarea, select").off("input.validation").on("input.validation", function() {
    	        $(this).siblings().removeClass('err-txt-active');
    	        $(this).css('border-color', '');
    	    });
    
    	    return { proceed, firstErrorField };
    	}
    
    	// Common submit handler
    	function handleFormSubmit($form, extraAction) {
    	    $form.on("submit", function(e) {
    	        e.preventDefault();
    
    	        const { proceed, firstErrorField } = validateForm($form);
    	        const $submitBtn = $form.find('button[type="submit"], input[type="submit"]');
    	        const $loadingIcon = $form.find(".loading-icon");
    	        let $message = $form.find('.form-message');
    	         
    	        if($message.length === 0){
                    $message = $('<div class="form-message" style="margin-bottom:10px;"></div>');
                    $loadingIcon.before($message); // insert after the loading icon
                }
    
    	        $submitBtn.prop('disabled', true).addClass('disabled');
    	        $message.html('');
    	        
    	        // --- reCAPTCHA v2 validation ---
    	        /*var formId = $form.attr('id');
                var widgetId = recaptchaWidgets[formId];
                var recaptchaResponse = grecaptcha.getResponse(widgetId);
                
                if (recaptchaResponse.length == 0) {
                    $message.css('color','red').html("Please verify that you are not a robot.");
                    $loadingIcon.removeClass("show-loading");
                    $submitBtn.prop('disabled', false).removeClass('disabled');
                    if (firstErrorField) scrollToError(firstErrorField);
                    return false;
                }*/
                
                // --- reCAPTCHA validation ---
                var formId = $form.attr('id');
                var widgetId = recaptchaWidgets[formId];
                var recaptchaResponse = grecaptcha.getResponse(widgetId);
        
                if (recaptchaResponse.length === 0) {
                    $message.css('color','red').html('Please verify that you are not a robot.');
                    $loadingIcon.removeClass("show-loading");
                    $submitBtn.prop('disabled', false).removeClass('disabled');
                    return false;
                }
    
    	        if (!proceed) {
    	            $submitBtn.prop('disabled', false).removeClass('disabled');
    	            $loadingIcon.removeClass("show-loading");
    	            if (firstErrorField) scrollToError(firstErrorField);
    	            return false;
    	        }
    
    	        const post_url = $form.attr("action");
    	        const request_method = $form.attr("method");
    	        const form_data = new FormData($form[0]);
    	        form_data.append('btncta', clickedButton);
                //form_data.append("source", $('input[name="source"]').val());
    
    
    	        $loadingIcon.addClass("show-loading");
    
    	        // Optional additional action
    	        if (typeof extraAction === 'function') extraAction();
    
    	
                var post_url_new = "https://thenest.school/admissions/" + post_url;
                
                $.ajax({
                    url: post_url_new,
                    type: request_method,
                    data: form_data,
                    dataType: "json",
                    contentType: false,
                    cache: false,
                    processData: false
                })
                .done(function(res){
                    if(res.status === 'success'){
                        //$message.css('color','green').html(res.message);
                        $form[0].reset();
                        
                        if (post_url === "contact-download-brochure") {
                            $("#d-brochure").click();
                        }
    
                         setTimeout(() => {
        
                            const currentPageURL = window.location.href;
                            const encodedURL = btoa(currentPageURL); // Base64 encode
                            const currentPath = window.location.pathname.replace(/\/$/, ""); // normalize
                            console.log(currentPath);
                            console.log('dd');
                            
                            const currentURL = window.location.pathname;
                        
                            let thankYouURL = "";
                        
                           if (clickedButton === "EYP") {
                               
                                if (currentPath === "/admissions/international-school-chennai") {
                                    thankYouURL = "/admissions/international-school-chennai/eyp/thank-you.php";
                                }
                                else if (currentPath === "/admissions/ib-preschool-eyp") {
                                    thankYouURL = "/admissions/ib-preschool-eyp/eyp/thank-you.php";
                                }
                                else if (currentPath === "/admissions/ib-primary-school") {
                                    thankYouURL = "/admissions/ib-primary-school/eyp/thank-you.php";
                                }
                                else if (currentPath === "/admissions/cambridge-admission") {
                                    thankYouURL = "/admissions/cambridge-admission/eyp/thank-you.php";
                                }
                                else if (currentPath === "/admissions/igcse-admission") {
                                    thankYouURL = "/admissions/igcse-admission/eyp/thank-you.php";
                                }
                                else if (currentPath === "/admissions/ibdp-as-a-level") {
                                    thankYouURL = "/admissions/ibdp-as-a-level/eyp/thank-you.php";
                                }
                                else{
                                    thankYouURL = "/admissions/eyp/thank-you.php";  // kgs thank-you URL
                                }
                            } 
                            else if (clickedButton === "grade1-5") {
                                if (currentPath === "/admissions/international-school-chennai") {
                                    thankYouURL = "/admissions/international-school-chennai/pyp/thank-you.php";
                                }
                                else if (currentPath === "/admissions/ib-preschool-eyp") {
                                    thankYouURL = "/admissions/ib-preschool-eyp/pyp/thank-you.php";
                                }
                                else if (currentPath === "/admissions/ib-primary-school") {
                                    thankYouURL = "/admissions/ib-primary-school/pyp/thank-you.php";
                                }
                                else if (currentPath === "/admissions/cambridge-admission") {
                                    thankYouURL = "/admissions/cambridge-admission/pyp/thank-you.php";
                                }
                                else if (currentPath === "/admissions/igcse-admission") {
                                    thankYouURL = "/admissions/igcse-admission/pyp/thank-you.php";
                                }
                                else if (currentPath === "/admissions/ibdp-as-a-level") {
                                    thankYouURL = "/admissions/ibdp-as-a-level/pyp/thank-you.php";
                                }
                                else{
                                    thankYouURL = "/admissions/pyp/thank-you.php";   // grade1-5 thank-you URL
                                }
                            }
                            else if (clickedButton === "grade6-8") {
                                if (currentPath === "/admissions/international-school-chennai") {
                                    thankYouURL = "/admissions/international-school-chennai/cls/thank-you.php";
                                }
                                else if (currentPath === "/admissions/ib-preschool-eyp") {
                                    thankYouURL = "/admissions/ib-preschool-eyp/cls/thank-you.php";
                                }
                                else if (currentPath === "/admissions/ib-primary-school") {
                                    thankYouURL = "/admissions/ib-primary-school/cls/thank-you.php";
                                }
                                else if (currentPath === "/admissions/cambridge-admission") {
                                    thankYouURL = "/admissions/cambridge-admission/cls/thank-you.php";
                                }
                                else if (currentPath === "/admissions/igcse-admission") {
                                    thankYouURL = "/admissions/igcse-admission/cls/thank-you.php";
                                }
                                else if (currentPath === "/admissions/ibdp-as-a-level") {
                                    thankYouURL = "/admissions/ibdp-as-a-level/cls/thank-you.php";
                                }
                                else{
                                    thankYouURL = "/admissions/cls/thank-you.php";   // grade6-8 thank-you URL
                                }
                            }
                            else if (clickedButton === "grade9-10") {
                                if (currentPath === "/admissions/international-school-chennai") {
                                    thankYouURL = "/admissions/international-school-chennai/igcse/thank-you.php";
                                }
                                else if (currentPath === "/admissions/ib-preschool-eyp") {
                                    thankYouURL = "/admissions/ib-preschool-eyp/igcse/thank-you.php";
                                }
                                else if (currentPath === "/admissions/ib-primary-school") {
                                    thankYouURL = "/admissions/ib-primary-school/igcse/thank-you.php";
                                }
                                else if (currentPath === "/admissions/cambridge-admission") {
                                    thankYouURL = "/admissions/cambridge-admission/igcse/thank-you.php";
                                }
                                else if (currentPath === "/admissions/igcse-admission") {
                                    thankYouURL = "/admissions/igcse-admission/igcse/thank-you.php";
                                }
                                else if (currentPath === "/admissions/ibdp-as-a-level") {
                                    thankYouURL = "/admissions/ibdp-as-a-level/igcse/thank-you.php";
                                }
                                else{
                                    thankYouURL = "/admissions/igcse/thank-you.php";   // grade9-10 thank-you URL
                                }
                            }
                            else if (clickedButton === "grade11-12") {
                                if (currentPath === "/admissions/international-school-chennai") {
                                    thankYouURL = "/admissions/international-school-chennai/ibdp/thank-you.php";
                                }
                                else if (currentPath === "/admissions/ib-preschool-eyp") {
                                    thankYouURL = "/admissions/ib-preschool-eyp/ibdp/thank-you.php";
                                }
                                else if (currentPath === "/admissions/ib-primary-school") {
                                    thankYouURL = "/admissions/ib-primary-school/ibdp/thank-you.php";
                                }
                                else if (currentPath === "/admissions/cambridge-admission") {
                                    thankYouURL = "/admissions/cambridge-admission/ibdp/thank-you.php";
                                }
                                else if (currentPath === "/admissions/igcse-admission") {
                                    thankYouURL = "/admissions/igcse-admission/ibdp/thank-you.php";
                                }
                                else if (currentPath === "/admissions/ibdp-as-a-level") {
                                    thankYouURL = "/admissions/ibdp-as-a-level/ibdp/thank-you.php";
                                }
                                else{
                                    thankYouURL = "/admissions/ibdp/thank-you.php";   // grade11-12 thank-you URL
                                }
                            }
                            else if (clickedButton === "schedule") {
                                if (currentPath === "/admissions/international-school-chennai") {
                                    thankYouURL = "/admissions/international-school-chennai/schedule-visit/thank-you.php";
                                }
                                else if (currentPath === "/admissions/ib-preschool-eyp") {
                                    thankYouURL = "/admissions/ib-preschool-eyp/schedule-visit/thank-you.php";
                                }
                                else if (currentPath === "/admissions/ib-primary-school") {
                                    thankYouURL = "/admissions/ib-primary-school/schedule-visit/thank-you.php";
                                }
                                else if (currentPath === "/admissions/cambridge-admission") {
                                    thankYouURL = "/admissions/cambridge-admission/schedule-visit/thank-you.php";
                                }
                                else if (currentPath === "/admissions/igcse-admission") {
                                    thankYouURL = "/admissions/igcse-admission/schedule-visit/thank-you.php";
                                }
                                else if (currentPath === "/admissions/ibdp-as-a-level") {
                                    thankYouURL = "/admissions/ibdp-as-a-level/schedule-visit/thank-you.php";
                                }
                                else{
                                    thankYouURL = "/admissions/schedule-visit/thank-you.php";   // schedule visit thank-you URL
                                }
                            }
                            else if (clickedButton === "open-house") {
                                if (currentPath === "/admissions/international-school-chennai") {
                                    thankYouURL = "/admissions/international-school-chennai/open-house-registration/thank-you.php";
                                }
                                else if (currentPath === "/admissions/ib-preschool-eyp") {
                                    thankYouURL = "/admissions/ib-preschool-eyp/open-house-registration/thank-you.php";
                                }
                                else if (currentPath === "/admissions/ib-primary-school") {
                                    thankYouURL = "/admissions/ib-primary-school/open-house-registration/thank-you.php";
                                }
                                else if (currentPath === "/admissions/cambridge-admission") {
                                    thankYouURL = "/admissions/cambridge-admission/open-house-registration/thank-you.php";
                                }
                                else if (currentPath === "/admissions/igcse-admission") {
                                    thankYouURL = "/admissions/igcse-admission/open-house-registration/thank-you.php";
                                }
                                else if (currentPath === "/admissions/ibdp-as-a-level") {
                                    thankYouURL = "/admissions/ibdp-as-a-level/open-house-registration/thank-you.php";
                                }
                                else{
                                    thankYouURL = "/admissions/open-house-registration/thank-you.php";   // open house thank-you URL
                                }
                            }
                            else if (clickedButton === "brochure") {
                                if (currentPath === "/admissions/international-school-chennai") {
                                    thankYouURL = "/admissions/international-school-chennai/download-brochure/thank-you.php";
                                }
                                else if (currentPath === "/admissions/ib-preschool-eyp") {
                                    thankYouURL = "/admissions/ib-preschool-eyp/download-brochure/thank-you.php";
                                }
                                else if (currentPath === "/admissions/ib-primary-school") {
                                    thankYouURL = "/admissions/ib-primary-school/download-brochure/thank-you.php";
                                }
                                else if (currentPath === "/admissions/cambridge-admission") {
                                    thankYouURL = "/admissions/cambridge-admission/download-brochure/thank-you.php";
                                }
                                else if (currentPath === "/admissions/igcse-admission") {
                                    thankYouURL = "/admissions/igcse-admission/download-brochure/thank-you.php";
                                }
                                else if (currentPath === "/admissions/ibdp-as-a-level") {
                                    thankYouURL = "/admissions/ibdp-as-a-level/download-brochure/thank-you.php";
                                }
                                else{
                                    thankYouURL = "/admissions/download-brochure/thank-you.php";   // brochure thank-you URL
                                }
                            }
                            else if (clickedButton === "banner") {
                                if (currentPath === "/admissions/international-school-chennai") {
                                    thankYouURL = "/admissions/international-school-chennai/banner-enquiry/thank-you.php";
                                }
                                else if (currentPath === "/admissions/ib-preschool-eyp") {
                                    thankYouURL = "/admissions/ib-preschool-eyp/banner-enquiry/thank-you.php";
                                }
                                else if (currentPath === "/admissions/ib-primary-school") {
                                    thankYouURL = "/admissions/ib-primary-school/banner-enquiry/thank-you.php";
                                }
                                else if (currentPath === "/admissions/cambridge-admission") {
                                    thankYouURL = "/admissions/cambridge-admission/banner-enquiry/thank-you.php";
                                }
                                else if (currentPath === "/admissions/igcse-admission") {
                                    thankYouURL = "/admissions/igcse-admission/banner-enquiry/thank-you.php";
                                }
                                else if (currentPath === "/admissions/ibdp-as-a-level") {
                                    thankYouURL = "/admissions/ibdp-as-a-level/banner-enquiry/thank-you.php";
                                }
                                else{
                                    thankYouURL = "/admissions/banner-enquiry/thank-you.php";   // banner form thank-you URL
                                }
                            }
                            else if (clickedButton === "mobile") {
                                if (currentPath === "/admissions/international-school-chennai") {
                                    thankYouURL = "/admissions/international-school-chennai/mobile-enquiry-form/thank-you.php";
                                }
                                else if (currentPath === "/admissions/ib-preschool-eyp") {
                                    thankYouURL = "/admissions/ib-preschool-eyp/mobile-enquiry-form/thank-you.php";
                                }
                                else if (currentPath === "/admissions/ib-primary-school") {
                                    thankYouURL = "/admissions/ib-primary-school/mobile-enquiry-form/thank-you.php";
                                }
                                else if (currentPath === "/admissions/cambridge-admission") {
                                    thankYouURL = "/admissions/cambridge-admission/mobile-enquiry-form/thank-you.php";
                                }
                                else if (currentPath === "/admissions/igcse-admission") {
                                    thankYouURL = "/admissions/igcse-admission/mobile-enquiry-form/thank-you.php";
                                }
                                else if (currentPath === "/admissions/ibdp-as-a-level") {
                                    thankYouURL = "/admissions/ibdp-as-a-level/mobile-enquiry-form/thank-you.php";
                                }
                                else{
                                    thankYouURL = "/admissions/mobile-enquiry-form/thank-you.php";   // mobile form thank-you URL
                                }
                            }
                            else {
                                if (currentPath === "/admissions/international-school-chennai") {
                                    thankYouURL = "/admissions/international-school-chennai/bottom-enquiry-form/thank-you.php";
                                }
                                else if (currentPath === "/admissions/ib-preschool-eyp") {
                                    thankYouURL = "/admissions/ib-preschool-eyp/bottom-enquiry-form/thank-you.php";
                                }
                                else if (currentPath === "/admissions/ib-primary-school") {
                                    thankYouURL = "/admissions/ib-primary-school/bottom-enquiry-form/thank-you.php";
                                }
                                else if (currentPath === "/admissions/cambridge-admission") {
                                    thankYouURL = "/admissions/cambridge-admission/bottom-enquiry-form/thank-you.php";
                                }
                                else if (currentPath === "/admissions/igcse-admission") {
                                    thankYouURL = "/admissions/igcse-admission/bottom-enquiry-form/thank-you.php";
                                }
                                else if (currentPath === "/admissions/ibdp-as-a-level") {
                                    thankYouURL = "/admissions/ibdp-as-a-level/bottom-enquiry-form/thank-you.php";
                                }
                                else{
                                    thankYouURL = "/admissions/bottom-enquiry-form/thank-you.php";  // bottom admission form thank-you URL
                                }
                            }
                        window.location.replace(thankYouURL + "?form=" + post_url + "&cta=" + encodeURIComponent(clickedButton) + "&from=" + encodeURIComponent(encodedURL));
                        }, 2000);
                        
                    } else {
                        $message.css('color','red').html(res.message);
                    }
                })
                .fail(function(xhr){
                    $message.css('color','red').html("An error occurred. Please try again.");
                    console.log(xhr.responseText);
                })
                .always(function(){
                    $submitBtn.prop('disabled', false);
                    $loadingIcon.removeClass('show-loading');
                });
    	    });
    	}
    
    	// Attach handler for all forms with optional extra actions
    	//handleFormSubmit($("#form-admission"));
    	handleFormSubmit($("#form-stat-brochure"));
    	handleFormSubmit($("#form-download-brochure"));
    	handleFormSubmit($("#form-open-house"));
    	handleFormSubmit($("#form-campus"));
    	handleFormSubmit($("#form-enquiry-for-admission"));
    	handleFormSubmit($("#form-enquiry-for-admission-kgs"));
    	handleFormSubmit($("#form-enquiry-for-admission-grade1to5"));
    	handleFormSubmit($("#form-enquiry-for-admission-grade6to8"));
    	handleFormSubmit($("#form-enquiry-for-admission-grade9-10"));
    	handleFormSubmit($("#form-enquiry-for-admission-grade11-12"));
    
        function scrollToDiv(selector, offset) {
            $('html, body').animate({
                scrollTop: $(selector).offset().top - offset
            }, 500);
        }
    
        $(document).on("click", '.grades_filter_menu li', function () {
    
            $('.grades_filter_menu li').removeClass('active');
            $(this).addClass('active');
    
            var year = $(this).attr('data-grade-year');
    
            if (year) {
                if (window.innerWidth < 768) {
                    $('.grades_filter_container .grades_filter').html($(this).html());
                }
    
                var $currentContent = $('#grades-container .grade-content:visible');
                var $nextContent = $('#grades-container .grade-content[data-grade-year="' + year + '"]');
    
                if (!$nextContent.is(':visible')) {
                    $currentContent.fadeOut(300, function () {
                        $nextContent.fadeIn(300);
                    });
                }
    
                scrollToDiv('#grades-container', 150);
            }
    
            $('.grades_filter_menu').slideUp(280);
            $('.grades_filter').removeClass("grades_filter_active");
        });
    
        // Toggle dropdown
        $('.grades_filter').on('click', function (e) {
            e.stopPropagation();
            $('.grades_filter_menu').slideToggle(280);
            $(this).toggleClass("grades_filter_active");
        });
    
        $(document).on('click', function (e) {
            if (!$(e.target).closest('.grades_filter, .grades_filter_menu').length) {
                $('.grades_filter_menu').slideUp(280);
                $('.grades_filter').removeClass("grades_filter_active");
            }
        });
    
        $('#grades-container .grade-content').hide();
        
         // Get active menu item
        var activeItem = $('.grades_filter_menu li.active');
        var year = activeItem.data('grade-year');
    
        if (year) {
            $('#grades-container .grade-content[data-grade-year="' + year + '"]').show();
    
            // Optional: set dropdown text on mobile
            if (window.innerWidth < 768) {
                $('.grades_filter_container .grades_filter').text(activeItem.text());
            }
        } else {
            // Fallback (if no active class exists)
            $('#grades-container .grade-content').first().show();
        }
        
        // tab sticky in desktop
        function handleDesktopOverflow() {
            if (window.innerWidth >= 1024) { // desktop
                $('body').css('overflow', 'visible');
            } else { // tablet & mobile
                $('body').css('overflow', 'hidden');
            }
        }
    
        // Run on load
        handleDesktopOverflow();
    
        // Run on resize
        $(window).on('resize', function () {
            handleDesktopOverflow();
        });

});



$(document).ready(function() {
        
        $('#event-slider').owlCarousel({
            loop: false,
            margin: 10,
            autoplay: false,
            responsiveClass: true,
            nav: false,
            onInitialized: counter,
            onTranslated: counter,
            navText: ["<i class='fa fa-chevron-left thickness-reduce'></i>", "<i class='fa fa-chevron-right thickness-reduce'></i>"],
            responsive: {
                0: {
                    items: 1,
                },
                579: {
                    items: 1,
                },
                1000: {
                    items: 1,
                    slideBy: 1
                }
            }
        })
        
         var $videoSlider = $('#video-slider');
          var totalVideos = $videoSlider.find('.event-image').length;
          var currentVideoIndex = 0;
          
          if (totalVideos <= 1) {
          $('#video_counter').hide();
          $('.lightbox-counter').hide();
          $('.lightbox-prev, .lightbox-next').addClass('disabled').hide();
          $videoSlider.find('.owl-dots').hide();
        }

        
        $('#video-slider').owlCarousel({
            loop: false,
            margin: 0,
            autoplay: false,
            responsiveClass: true,
            nav: false,
            onInitialized: syncCounter,
            onTranslated: syncCounter,
            navText: ["<i class='fa fa-chevron-left thickness-reduce'></i>", "<i class='fa fa-chevron-right thickness-reduce'></i>"],
            responsive: {
                0: {
                    items: 1,
                },
                579: {
                    items: 1,
                },
                1000: {
                    items: 1,
                    slideBy: 1
                }
            }
        })

        $('.carousel-control.left').click(function () {
            $('#carouselgallery').carousel('prev');
        });

        $('.carousel-control.right').click(function () {
            $('#carouselgallery').carousel('next');
        });
        
        function syncCounter(event) {
          if (!event) return;
          currentVideoIndex = event.item.index;
          updateCounter(currentVideoIndex);
          toggleArrows(currentVideoIndex);
        }

        
          function updateCounter(index) {
            $('#video_counter').text((index + 1) + ' / ' + totalVideos);
            $('.lightbox-counter').text((index + 1) + ' / ' + totalVideos);
          }


        function counter(event) {
            var element = event.target;   
            var items = event.item.count;
            var item = event.item.index + 1;

            // if loop is true then reset counter from 1
            if (item > items) {
                item = item - items
            }
            $('#counter').html(+item + " / " + items)
        }
        

        $('.gallery a').simpleLightbox({
            sourceAttr:'data-url',
            showCounter:true,
            animationSpeed: 50,
         });
         
        
       /* ---------------------------
     OPEN VIDEO POPUP
  ---------------------------- */
  $('.yt-popup').on('click', function (e) {
    e.preventDefault();

    currentVideoIndex = $(this).closest('.event-image').index();
    openVideo(currentVideoIndex);

    $('.lightbox').fadeIn();
  });

  /* ---------------------------
     LOAD VIDEO INTO POPUP
  ---------------------------- */
  function openVideo(index) {
      var videoURL = $videoSlider
        .find('.event-image')
        .eq(index)
        .find('.yt-popup')
        .data('lightbox-content');
    
      $('.lightbox-column').html(
        '<div class="lightbox-video">' +
          '<iframe src="' + videoURL + '" frameborder="0" allow="autoplay; fullscreen" allowfullscreen></iframe>' +
        '</div>'
      );
    
      updateCounter(index);
      toggleArrows(index);
    }


  /* ---------------------------
     POPUP NEXT / PREV
  ---------------------------- */
  $('.lightbox-next').on('click', function () {
    if (currentVideoIndex < totalVideos - 1) {
      currentVideoIndex++;
      $videoSlider.trigger('to.owl.carousel', [currentVideoIndex, 300]);
      openVideo(currentVideoIndex);
    }
  });

  $('.lightbox-prev').on('click', function () {
    if (currentVideoIndex > 0) {
      currentVideoIndex--;
      $videoSlider.trigger('to.owl.carousel', [currentVideoIndex, 300]);
      openVideo(currentVideoIndex);
    }
  });
  
  function toggleArrows(index) {
      $('.lightbox-prev').toggleClass('disabled', index === 0);
      $('.lightbox-next').toggleClass('disabled', index === totalVideos - 1);
    }
    
    $('.lightbox-prev, .lightbox-next').on('click', function (e) {
      if ($(this).hasClass('disabled')) {
        e.preventDefault();
        return false;
      }
    });



  /* ---------------------------
     CLOSE POPUP
  ---------------------------- */
  $('.lightbox-close').on('click', function (e) {
    e.preventDefault();
    $('.lightbox').fadeOut();
    $('.lightbox-column').empty();
  });

  /* ---------------------------
     ESC KEY CLOSE
  ---------------------------- */
  $(document).on('keydown', function (e) {
    if (e.key === 'Escape') {
      $('.lightbox').fadeOut();
      $('.lightbox-column').empty();
    }
  });


    });
