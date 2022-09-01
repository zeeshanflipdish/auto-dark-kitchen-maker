jQuery(window).scroll(function() {
    if (jQuery(this).scrollTop() > 1){  
    jQuery('.et-l--header').addClass("sticky");
    }
    else{
    jQuery('.et-l--header').removeClass("sticky");
    }
});