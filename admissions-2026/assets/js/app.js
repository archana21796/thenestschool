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
                    $message = $('<div class="form-message" style="margin-top:10px;position: absolute;bottom: 0px;"></div>');
                    $loadingIcon.after($message); // insert after the loading icon
                }
    
    	        $submitBtn.prop('disabled', true).addClass('disabled');
    	        $message.html('');
    	        
    	       
    
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
    
    	        $.ajax({
                    url: post_url,
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
                            
                            const currentURL = window.location.pathname;
                        
                            let thankYouURL = "";
                        
                           if (clickedButton === "EYP") {
                                thankYouURL = "/admissions-2026/eyp/thank-you.php";  // kgs thank-you URL
                            } 
                            else if (clickedButton === "grade1-5") {
                                thankYouURL = "/admissions-2026/pyp/thank-you.php";   // grade1-5 thank-you URL
                            }
                            else if (clickedButton === "grade6-8") {
                                thankYouURL = "/admissions-2026/cls/thank-you.php";   // grade6-8 thank-you URL
                            }
                            else if (clickedButton === "grade9-10") {
                                thankYouURL = "/admissions-2026/igcse/thank-you.php";   // grade9-10 thank-you URL
                            }
                            else if (clickedButton === "grade11-12") {
                                thankYouURL = "/admissions-2026/ibdp/thank-you.php";   // grade11-12 thank-you URL
                            }
                            else if (clickedButton === "schedule") {
                                thankYouURL = "/admissions-2026/schedule-visit/thank-you.php";   // schedule visit thank-you URL
                            }
                            else if (clickedButton === "open-house") {
                                thankYouURL = "/admissions-2026/open-house-registration/thank-you.php";   // open house thank-you URL
                            }
                            else if (clickedButton === "brochure") {
                                thankYouURL = "/admissions-2026/download-brochure/thank-you.php";   // brochure thank-you URL
                            }
                            else if (clickedButton === "banner") {
                                thankYouURL = "/admissions-2026/banner-enquiry/thank-you.php";   // banner form thank-you URL
                            }
                            else if (clickedButton === "mobile") {
                                thankYouURL = "/admissions-2026/mobile-enquiry-form/thank-you.php";   // mobile form thank-you URL
                            }
                            else {
                              thankYouURL = "/admissions-2026/bottom-enquiry-form/thank-you.php";  // bottom admission form thank-you URL
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
                    $('.grades_filter_container .grades_filter').text($(this).text());
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
        $('#grades-container .grade-content').first().show();
        
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
