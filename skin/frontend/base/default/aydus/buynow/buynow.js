/**
 * BuyNow js
 * 
 * @category   Aydus
 * @package    Aydus_BuyNow
 * @author     Aydus Consulting <davidt@aydus.com>
 */

var BuyNow = function ($)
{
    var loggedin;
    var addedtocart = false;
    var button;
    var addToCartUrl;
    var registerUrl;
    var onepageUrl;
    var shippingMethodsUrl;
    var addToCartForm;

    //dialog options
    var dialog;
    var options = {
        autoOpen: false,
        height: 'auto',
        width: 350,
        modal: true,
        fluid: true,
        buttons: {
            Close: function () {
                dialog.dialog("close");
            }
        },
        close: function () {
        },
        resize: function () {
            fluidDialog();
        }
    };
    //form in dialog
    var varienForm;
    var form;
    //dialog submit button labels
    var loginLabel;
    var checkoutLabel;
    var registerLabel;
    //inline style for progress
    var progressBackground;

    //not logged in button click
    var login = function ()
    {
        if (addToCartForm.validator.validate()) {
            varienForm = buyNowLoginForm;
            form = varienForm.form;
            options.buttons[registerLabel] = register;
            options.buttons[loginLabel] = loginSubmit;
            dialog = $("#buynow-login").dialog(options);
            dialog.dialog("open");
        }
    };

    var register = function ()
    {
        window.location.href = registerUrl;
    }

    var loginSubmit = function ()
    {
        if (varienForm.validator.validate()) {

            progress('#buynow-login .content', true);
            var url = $(form).attr('action');

            var data = $(form).serialize();

            $.post(url, data, function (res) {

                if (!res.error) {

                    loginSuccess(res.data);
                    progress('#buynow-login .content', false);

                } else {

                    $('#buynow-login-form .messages').show().find('span').text(res.data);
                    progress('#buynow-login .content', false);

                }

            });

        }

    };

    var loginSuccess = function (data)
    {
        $('#buynow-checkout').html(data.checkout);
        loggedin = true;
        updateHeader(data.header);

        dialog.dialog("close");
        delete options.buttons[loginLabel];
        delete options.buttons[registerLabel];
        addToCart(checkout);
    };

    //on button click, add item to cart if NOT already in cart
    var addToCart = function ()
    {
        if (!addedtocart) {

            if (addToCartForm.validator.validate()) {

                dialog = $("#buynow-popup").dialog(options);
                dialog.dialog("open");

                var $form = $('#product_addtocart_form');
                var data = $form.serialize();

                $.post(addToCartUrl, data, function (res) {
                    dialog.dialog("close");

                    if (!res.error) {

                        addedtocart = true;
                        loadCheckout(res.data);

                    } else {

                        $('#buynow-checkout-form .messages').show().find('span').text(res.data);
                    }

                });

            }

        } else {

            callback();
        }

    };
    
    var loadCheckout = function(data)
    {
        updateHeader(data.header);    	
        
        $('#buynow-checkout').html(data.checkout);

        $('#ba_agreement_id').change(function () {
            var method = $(this).children(":selected").attr("method");
            $('#payment-method').val(method);
        });
        
        $('#shipping-address-select').change(updateShippingMethods);

        checkout();
    };
    
    var updateShippingMethods = function(e)
    {
    	var shippingAddressId = $(this).val();
    	shippingAddressId = parseInt(shippingAddressId);
    	if (!isNaN(shippingAddressId) && shippingAddressId > 0){
    		var data = { shipping_address_id : shippingAddressId };
    		
        	$.get(shippingMethodsUrl, data, function(res){
        		if (!res.error){
            		$('#shipping_method_select').replaceWith(res.data)
        		} else {
        			log(res.data);
        		}
        	});
    		
    	}
    };

    //logged in, checkout button click
    var checkout = function ()
    {
        if (typeof buyNowCheckoutForm != 'undefined') {

            varienForm = buyNowCheckoutForm;
            form = varienForm.form;

            checkoutLabel = $('#buynow-checkout-button').attr('title');
            options.buttons[checkoutLabel] = checkoutSubmit;
            dialog = $("#buynow-checkout").dialog(options);
            dialog.dialog("open");

        } else {

            window.location.href = onepageUrl;
        }
    };

    var checkoutSubmit = function ()
    {
        if (varienForm.validator.validate()) {

            progress('#buynow-checkout .content', true);
            var url = $(form).attr('action');

            var data = $(form).serialize();

            $.post(url, data, function (res) {

                progress('#buynow-checkout .content', false);
                if (!res.error) {

                    updateHeader(res.data.header);
                    checkoutSuccess(res.data.success);

                } else {

                    $('#buynow-checkout-form .messages').show().find('span').text(res.data);
                }

            });

        }

    };

    var checkoutSuccess = function (successHtml)
    {
        $('#buynow-success').html(successHtml);
        dialog = $("#buynow-success").dialog(options);
        dialog.dialog("open");
    };

    //on button click, add to cart if not already in cart, then open dialog
    var buyNowButtonClick = function (e)
    {
        if (!loggedin) {
            login();
        } else {
            addToCart(checkout);
        }
    };

    //update the header
    var updateHeader = function (elements)
    {
        for (var selector in elements) {

            var element = elements[selector];

            if (element.html) {
                var html = element.html;
                if (element.parent) {
                    $(selector).parent().html(html);

                } else {
                    $(selector).html(html);
                }
            }
            if (element.attributes) {
                for (var attributeKey in element.attributes) {
                    var attributeValue = element.attributes[attributeKey];
                    $(selector).attr(attributeKey, attributeValue);
                }
            }
            if (element.hide) {
                $(selector).hide();
            }
            if (element.addClass && element.addClass.length > 0) {
                $(selector).addClass(element.addClass);
            }
            if (element.removeClass && element.removeClass.length > 0) {
                $(selector).removeClass(element.removeClass);
            }
            if (element.remove) {
                if (element.parent) {
                    $(selector).remove();

                } else {
                    $(selector).parent().remove();
                }
            }
        }
    };

    //progress background
    var progress = function (selector, show, position)
    {
        var $container = $(selector);

        if (show) {

            $container.attr('style', progressBackground);

            if (position) {

                $container.css('background-position', position);
            }

            dim($container, true);

        } else {

            $container.attr('style', '');
            dim($container, false);

        }

    };

    //dim the container when in progress
    var dim = function ($container, dim)
    {
        $container.children().each(function () {

            if (dim) {
                $(this).css('opacity', '.3');
            } else {
                $(this).css('opacity', '1');
            }

        });

    };

    //@see http://stackoverflow.com/questions/16471890/responsive-jquery-ui-dialog-and-a-fix-for-maxwidth-bug
    var fluidDialog = function ()
    {
        var $visible = $(".ui-dialog:visible");
        // each open dialog
        $visible.each(function () {
            var $this = $(this);
            var dialog = $this.find(".ui-dialog-content").data("ui-dialog");
            // if fluid option == true
            if (dialog.options.fluid) {
                var wWidth = $(window).width();
                // check window width against dialog width
                if (wWidth < (parseInt(dialog.options.maxWidth) + 50)) {
                    // keep dialog from filling entire screen
                    $this.css("max-width", "90%");
                } else {
                    // fix maxWidth bug
                    $this.css("max-width", dialog.options.maxWidth + "px");
                }
                //reposition dialog
                dialog.option("position", dialog.options.position);
            }

        });

    };

    var log = function (message)
    {
        if (typeof console == 'object') {
            console.log(message);
        } else {
            alert(message);
        }

    };

    return {
        init: function ()
        {
            $(function () {

                if (buyNowOptions) {

                    loggedin = buyNowOptions.loggedIn;

                    checkoutLabel = buyNowOptions.checkoutLabel;
                    loginLabel = buyNowOptions.loginLabel;
                    progressBackground = buyNowOptions.progressBackground;
                    addToCartUrl = buyNowOptions.addToCartUrl;
                    registerLabel = buyNowOptions.registerLabel;
                    registerUrl = buyNowOptions.registerUrl;
                    onepageUrl = buyNowOptions.onepageUrl;
                    shippingMethodsUrl = buyNowOptions.shippingMethodsUrl;
                    addToCartForm = (productAddToCartForm.form.id =='product_addtocart_form') ? productAddToCartForm : new VarienForm('product_addtocart_form');

                    // on window resize run function
                    $(window).resize(function () {
                        fluidDialog();
                    });

                    // catch dialog if opened within a viewport smaller than the dialog width
                    $(document).on("dialogopen", ".ui-dialog", function (event, ui) {
                        fluidDialog();
                    });

                    var $buynowButton = $('#buynow-button');
                    $buynowButton.click(buyNowButtonClick);
                    button = $buynowButton[0];
                } else {
                	$('#buynow-button').hide();
                    log('buyNowOptions not set.');
                }

            });

        }

    };


};

if (!window.jQuery) {

    document.write('<script src="//ajax.googleapis.com/ajax/libs/jquery/1.11.2/jquery.min.js">\x3C/script><script>jQuery.noConflict(); var buyNow = BuyNow(jQuery); buyNow.init();</script>');
    document.write('<link rel="stylesheet" href="//ajax.googleapis.com/ajax/libs/jqueryui/1.11.2/themes/smoothness/jquery-ui.css" />');
    document.write('<script src="//ajax.googleapis.com/ajax/libs/jqueryui/1.11.2/jquery-ui.min.js">\x3C/script>');

} else {

    var buyNow = BuyNow(jQuery);
    buyNow.init();

    if (!jQuery.ui) {
        document.write('<link rel="stylesheet" href="//ajax.googleapis.com/ajax/libs/jqueryui/1.11.2/themes/smoothness/jquery-ui.css" />');
        document.write('<script src="//ajax.googleapis.com/ajax/libs/jqueryui/1.11.2/jquery-ui.min.js">\x3C/script>');
    }
}

